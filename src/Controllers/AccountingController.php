<?php
// src/Controllers/AccountingController.php

namespace GymManagement\Controllers;

use GymManagement\Models\Transaction;
use GymManagement\Models\Installment;
use DateTime;

class AccountingController
{
    public function __construct()
    {
        add_action('init', array($this, 'create_tables'));
        add_action('wp_ajax_my_gym_pay_installment', array($this, 'ajax_pay_installment'));
        add_action('wp_ajax_my_gym_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('admin_init', array($this, 'process_transaction'));
    }

    public function create_tables()
    {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'gym_transactions';
        $installments_table = $wpdb->prefix . 'gym_installments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql1 = "CREATE TABLE $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            type varchar(50) NOT NULL,
            payment_type varchar(50) NOT NULL,
            description text,
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            INDEX user_id (user_id),
            INDEX date (date)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE $installments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            due_date date NOT NULL,
            payment_date datetime DEFAULT NULL,
            status varchar(50) NOT NULL,
            PRIMARY KEY (id),
            INDEX user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    public function render_accounting_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }
        require_once MY_GYM_PLUGIN_PATH . 'views/accounting-page.php';
    }

    public function process_transaction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
            if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['my_gym_transaction_nonce'], 'my_gym_transaction_nonce')) {
                return;
            }

            $transaction_type = sanitize_text_field($_POST['transaction_type']);
            $amount = floatval($_POST['amount']);
            $payment_method = sanitize_text_field($_POST['payment_method']);
            $description = sanitize_textarea_field($_POST['description']);
            $user_id = get_current_user_id();

            if ($amount <= 0) {
                add_settings_error('my_gym_messages', 'invalid_amount', 'مبلغ نامعتبر است.', 'error');
                return;
            }

            $this->create_transaction($user_id, $amount, $transaction_type === 'income' ? 'دریافت' : 'هزینه', $payment_method, $description);
            add_settings_error('my_gym_messages', 'transaction_added', 'تراکنش با موفقیت ثبت شد.', 'success');
        }
    }

    public function create_transaction($user_id, $amount, $type, $payment_type, $description)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';

        return $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'amount' => $amount,
                'type' => $type,
                'payment_type' => $payment_type,
                'description' => $description,
                'date' => current_time('mysql')
            ],
            ['%d', '%f', '%s', '%s', '%s', '%s']
        );
    }

    public function ajax_pay_installment()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی.');
        }

        $installment_id = intval($_POST['installment_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'gym_installments';
        $installment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $installment_id));

        if ($installment && $installment->status !== 'paid') {
            $wpdb->update(
                $table,
                ['status' => 'paid', 'payment_date' => current_time('mysql')],
                ['id' => $installment_id],
                ['%s', '%s'],
                ['%d']
            );
            $this->create_transaction($installment->user_id, $installment->amount, 'دریافت', 'نقدی', 'پرداخت قسط');
            wp_send_json_success('پرداخت ثبت شد.');
        } else {
            wp_send_json_error('قسط نامعتبر یا قبلاً پرداخت شده است.');
        }
    }

    public function get_dashboard_data()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی.');
        }

        $transient_key = 'my_gym_dashboard_data';
        $cached_data = get_transient($transient_key);
        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
        }

        global $wpdb;
        $transactions_table = $wpdb->prefix . 'gym_transactions';
        $installments_table = $wpdb->prefix . 'gym_installments';

        $data = [
            'income' => floatval($wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'دریافت' AND MONTH(date) = MONTH(CURRENT_DATE)")),
            'expense' => floatval($wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'هزینه' AND MONTH(date) = MONTH(CURRENT_DATE)")),
            'overdue_installments' => intval($wpdb->get_var("SELECT COUNT(*) FROM $installments_table WHERE status = 'overdue'")),
            'total_members' => count(get_users(['role__in' => ['subscriber']])),
            'monthly_data' => $this->get_monthly_data(),
            'disciplines_data' => $this->get_disciplines_data()
        ];

        set_transient($transient_key, $data, HOUR_IN_SECONDS);
        wp_send_json_success($data);
    }

    private function get_monthly_data()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';
        $current_year = date('Y');

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(date) as month, SUM(CASE WHEN type='دریافت' THEN amount ELSE 0 END) as income, SUM(CASE WHEN type='هزینه' THEN amount ELSE 0 END) as expense
                 FROM $table
                 WHERE YEAR(date) = %d
                 GROUP BY month
                 ORDER BY month ASC",
                $current_year
            ), ARRAY_A
        );

        $labels = [];
        $income = [];
        $expense = [];
        $months = range(1, 12);

        foreach ($months as $month_num) {
            $found = false;
            foreach ($results as $row) {
                if ($row['month'] == $month_num) {
                    $labels[] = date('F', mktime(0, 0, 0, $month_num, 1));
                    $income[] = floatval($row['income']);
                    $expense[] = floatval($row['expense']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $labels[] = date('F', mktime(0, 0, 0, $month_num, 1));
                $income[] = 0;
                $expense[] = 0;
            }
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }

    private function get_disciplines_data()
    {
        $users = get_users(['role__in' => ['subscriber']]);
        $disciplines = [];

        foreach ($users as $user) {
            $discipline_id = get_user_meta($user->ID, 'sport_discipline', true);
            if ($discipline_id) {
                $discipline_name = get_the_title($discipline_id);
                if ($discipline_name && $discipline_name !== 'Auto Draft') {
                    if (!isset($disciplines[$discipline_name])) {
                        $disciplines[$discipline_name] = 0;
                    }
                    $disciplines[$discipline_name]++;
                }
            }
        }

        return ['labels' => array_keys($disciplines), 'counts' => array_values($disciplines)];
    }
}