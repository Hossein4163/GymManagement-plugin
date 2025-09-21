<?php

namespace GymManagement\Controllers;

use Morilog\Jalali\Jalalian;

final class AccountingController
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        // Use admin-post for form handling for better security and structure
        add_action('admin_post_rame_gym_add_transaction', [$this, 'process_transaction_form']);
        add_action('wp_ajax_my_gym_pay_installment', [$this, 'ajax_pay_installment']);
        add_action('wp_ajax_my_gym_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'rame-gym',
            __('حسابداری', 'rame-gym'),
            __('حسابداری', 'rame-gym'),
            'manage_options',
            'rame-gym-accounting',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/accounting-page.php';
    }

    public function process_transaction_form()
    {
        if (!isset($_POST['my_gym_transaction_nonce']) || !wp_verify_nonce($_POST['my_gym_transaction_nonce'], 'my_gym_transaction_nonce')) {
            wp_die('Invalid nonce.');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Access Denied.');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $type = sanitize_text_field($_POST['transaction_type'] ?? 'expense');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'cash');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if ($amount <= 0) {
            add_settings_error('my_gym_messages', 'invalid_amount', 'مبلغ وارد شده نامعتبر است.', 'error');
        } else {
            $this->create_transaction(
                $user_id,
                $amount,
                $type === 'income' ? 'دریافت' : 'هزینه',
                $payment_method,
                $description
            );
            add_settings_error('my_gym_messages', 'transaction_added', 'تراکنش با موفقیت ثبت شد.', 'success');
        }

        wp_redirect(admin_url('admin.php?page=rame-gym-accounting'));
        exit;
    }

    public function create_transaction(int $user_id, float $amount, string $type, string $payment_type, string $description): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';

        $result = $wpdb->insert(
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

        // Clear dashboard cache after a new transaction
        delete_transient('rame_gym_dashboard_data');

        return (bool)$result;
    }

    public function ajax_pay_installment()
    {
        check_ajax_referer('my_gym_security_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'عدم دسترسی.']);
        }

        $installment_id = isset($_POST['installment_id']) ? intval($_POST['installment_id']) : 0;
        if ($installment_id <= 0) {
            wp_send_json_error(['message' => 'شناسه قسط نامعتبر است.']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gym_installments';
        $installment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $installment_id));

        if ($installment && $installment->status !== 'paid') {
            $wpdb->update(
                $table,
                ['status' => 'paid', 'payment_date' => current_time('mysql')],
                ['id' => $installment_id]
            );

            $this->create_transaction(
                $installment->user_id,
                $installment->amount,
                'دریافت',
                'اقساط',
                'پرداخت قسط مربوط به عضویت #' . $installment->membership_id
            );
            wp_send_json_success(['message' => 'پرداخت با موفقیت ثبت شد.']);
        } else {
            wp_send_json_error(['message' => 'قسط نامعتبر یا قبلاً پرداخت شده است.']);
        }
    }

    public function ajax_get_dashboard_data()
    {
        check_ajax_referer('my_gym_security_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'عدم دسترسی.']);
        }

        $transient_key = 'rame_gym_dashboard_data';
        if (false !== ($cached_data = get_transient($transient_key))) {
            wp_send_json_success($cached_data);
        }

        global $wpdb;
        $data = [
            'income' => (float)$wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}gym_transactions WHERE type = 'دریافت' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())"),
            'expense' => (float)$wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}gym_transactions WHERE type = 'هزینه' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())"),
            'overdue_installments' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gym_installments WHERE status = 'overdue'"),
            'active_members' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gym_memberships WHERE status = 'active'"),
            'monthly_data' => $this->get_monthly_chart_data(),
            'disciplines_data' => $this->get_disciplines_chart_data()
        ];

        set_transient($transient_key, $data, HOUR_IN_SECONDS);
        wp_send_json_success($data);
    }

    private function get_monthly_chart_data(): array
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT MONTH(date) as month,
                 SUM(CASE WHEN type='دریافت' THEN amount ELSE 0 END) as income,
                 SUM(CASE WHEN type='هزینه' THEN amount ELSE 0 END) as expense
                 FROM {$wpdb->prefix}gym_transactions
                 WHERE YEAR(date) = %d
                 GROUP BY MONTH(date)
                 ORDER BY MONTH(date) ASC",
                date('Y')
            ), ARRAY_A
        );

        $labels = [];
        $income_data = [];
        $expense_data = [];
        for ($m = 1; $m <= 12; $m++) {
            $jalali_month = Jalalian::fromFormat('Y-m-d', date('Y') . '-' . $m . '-01')->format('F');
            $labels[] = $jalali_month;
            $income_data[$m] = 0;
            $expense_data[$m] = 0;
        }

        foreach ($results as $row) {
            $income_data[(int)$row['month']] = (float)$row['income'];
            $expense_data[(int)$row['month']] = (float)$row['expense'];
        }

        return ['labels' => array_values($labels), 'income' => array_values($income_data), 'expense' => array_values($expense_data)];
    }

    private function get_disciplines_chart_data(): array
    {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT p.post_title, COUNT(m.id) as member_count
            FROM {$wpdb->prefix}gym_memberships m
            INNER JOIN {$wpdb->posts} p ON m.discipline_id = p.ID
            WHERE m.status = 'active' AND p.post_type = 'sports_discipline'
            GROUP BY p.post_title
            ORDER BY member_count DESC
        ");

        $labels = [];
        $counts = [];
        foreach ($results as $row) {
            $labels[] = $row->post_title;
            $counts[] = (int)$row->member_count;
        }

        return ['labels' => $labels, 'counts' => $counts];
    }
}