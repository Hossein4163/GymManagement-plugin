<?php

namespace GymManagement\Controllers;

use DateTime;
use Morilog\Jalali\Jalalian;

final class MembershipController
{
    public function __construct()
    {
        // Hooks for displaying and saving membership data on the user's admin profile page
        add_action('show_user_profile', [$this, 'render_membership_management_section']);
        add_action('edit_user_profile', [$this, 'render_membership_management_section']);
        add_action('personal_options_update', [$this, 'save_user_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_user_profile_fields']);

        // Hook for our daily automated tasks
        add_action('rame_gym_daily_tasks', [$this, 'run_daily_tasks']);
    }

    /**
     * Renders the entire membership management UI on the user profile page.
     */
    public function render_membership_management_section(\WP_User $user)
    {
        // This file contains the HTML form for adding/renewing memberships and the history table.
        require_once MY_GYM_PLUGIN_PATH . 'views/user-profile-membership-section.php';
    }

    /**
     * Handles the saving of all custom fields from the user profile page.
     */
    public function save_user_profile_fields(int $user_id)
    {
        if (!current_user_can('edit_user', $user_id) || !isset($_POST['rame_gym_membership_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['rame_gym_membership_nonce'], 'save_rame_gym_membership')) {
            return;
        }

        // 1. Save basic user meta fields
        if (isset($_POST['national_id'])) {
            update_user_meta($user_id, 'national_id', sanitize_text_field($_POST['national_id']));
        }
        if (isset($_POST['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        }

        // 2. Create a new membership if a discipline was selected in the form
        if (!empty($_POST['discipline_id'])) {
            $this->create_new_membership($user_id, $_POST);
        }
    }

    /**
     * Handles the logic for creating a new membership record and its installments.
     */
    private function create_new_membership(int $user_id, array $data)
    {
        global $wpdb;
        $discipline_id = intval($data['discipline_id']);
        $duration = intval($data['membership_duration']);
        $total_amount = floatval($data['total_amount']);
        $payment_type = sanitize_text_field($data['payment_type']);
        $installment_count = ($payment_type === 'installments') ? intval($data['installment_count']) : 0;

        if ($duration <= 0 || $total_amount < 0) {
            return; // Do not save if data is invalid
        }

        $start_date = new DateTime();
        $end_date = (clone $start_date)->modify("+$duration months");
        $memberships_table = $wpdb->prefix . 'gym_memberships';

        // Deactivate any previous active memberships for this user to avoid conflicts
        $wpdb->update($memberships_table, ['status' => 'expired'], ['user_id' => $user_id, 'status' => 'active']);

        // Insert the new active membership record
        $wpdb->insert($memberships_table, [
            'user_id' => $user_id,
            'discipline_id' => $discipline_id,
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
            'total_amount' => $total_amount,
            'payment_type' => $payment_type,
            'status' => 'active',
        ]);
        $membership_id = $wpdb->insert_id;

        if ($membership_id > 0) {
            // Create a financial transaction for this new membership
            (new AccountingController())->create_transaction($user_id, $total_amount, 'دریافت', 'عضویت', 'ثبت عضویت جدید برای رشته ' . get_the_title($discipline_id));

            // If payment is by installments, create the installment records
            if ($payment_type === 'installments' && $installment_count > 1) {
                $this->create_installments_for_membership($membership_id, $user_id, $total_amount, $installment_count);
            }
        }
    }

    /**
     * Creates installment records for a given membership.
     */
    private function create_installments_for_membership(int $membership_id, int $user_id, float $total_amount, int $count)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gym_installments';
        $installment_amount = round($total_amount / $count, 0); // Round to nearest Toman
        $current_date = new DateTime();

        for ($i = 0; $i < $count; $i++) {
            $due_date = (clone $current_date)->modify("+$i months")->format('Y-m-d');
            $wpdb->insert(
                $table_name,
                [
                    'membership_id' => $membership_id,
                    'user_id' => $user_id,
                    'amount' => $installment_amount,
                    'due_date' => $due_date,
                    'status' => 'pending'
                ]
            );
        }
    }

    /**
     * Main callback for the daily cron job.
     */
    public function run_daily_tasks()
    {
        $this->update_statuses();
        $this->send_reminders();
    }

    /**
     * Updates statuses for expired memberships and overdue installments.
     */
    private function update_statuses()
    {
        global $wpdb;
        $current_date_sql = current_time('Y-m-d');

        // Expire memberships
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gym_memberships SET status = 'expired' WHERE status = 'active' AND end_date < %s", $current_date_sql));

        // Mark installments as overdue
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gym_installments SET status = 'overdue' WHERE status = 'pending' AND due_date < %s", $current_date_sql));
    }

    /**
     * Sends automated SMS reminders for expiring memberships and overdue installments.
     */
    private function send_reminders()
    {
        global $wpdb;
        $sms_controller = new SmsController();
        $current_date = new DateTime(current_time('mysql'));

        // Send expiry warnings for memberships expiring in exactly 7 days
        $warning_date = (clone $current_date)->modify('+7 days')->format('Y-m-d');
        $expiring_memberships = $wpdb->get_results($wpdb->prepare("SELECT user_id, end_date FROM {$wpdb->prefix}gym_memberships WHERE status = 'active' AND end_date = %s", $warning_date));

        foreach ($expiring_memberships as $membership) {
            $end_date_jalali = Jalalian::fromDateTime($membership->end_date)->format('Y/m/d');
            $message = "کاربر گرامی، عضویت شما در تاریخ $end_date_jalali منقضی خواهد شد. لطفاً جهت تمدید اقدام نمایید.";
            $sms_controller->send_automated_sms($membership->user_id, $message);
        }

        // Send reminders for newly overdue installments (we can add a flag to prevent sending reminders every day)
        $overdue_installments = $wpdb->get_results("SELECT user_id, amount, due_date FROM {$wpdb->prefix}gym_installments WHERE status = 'overdue'");
        foreach ($overdue_installments as $installment) {
            $due_date_jalali = Jalalian::fromDateTime($installment->due_date)->format('Y/m/d');
            $amount_formatted = number_format($installment->amount);
            $message = "کاربر گرامی، قسط شما به مبلغ $amount_formatted تومان با سررسید $due_date_jalali معوق شده است. لطفاً پرداخت نمایید.";
            $sms_controller->send_automated_sms($installment->user_id, $message); // Uncomment when ready to send
        }
    }

    /**
     * Helper function to get a human-readable label for a status slug.
     */
    public static function get_status_label(string $status): string
    {
        $labels = [
            'active' => 'فعال',
            'expired' => 'منقضی شده',
            'cancelled' => 'لغو شده',
            'paid' => 'پرداخت شده',
            'pending' => 'در انتظار',
            'overdue' => 'معوق',
        ];
        return $labels[$status] ?? ucfirst($status);
    }
}