<?php
// src/Controllers/ReportController.php

namespace GymManagement\Controllers;

class ReportController
{
    public function __construct()
    {
        add_submenu_page(
            'rame-gym',
            'گزارشات مالی',
            'گزارشات',
            'manage_options',
            'rame-gym-reports',
            array($this, 'render_reports_page')
        );
        add_action('wp_ajax_my_gym_get_financial_reports', array($this, 'get_financial_reports_data'));
    }

    public function render_reports_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }
        require_once MY_GYM_PLUGIN_PATH . 'views/reports-page.php';
    }

    public function get_financial_reports_data()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی.');
        }

        $transient_key = 'my_gym_financial_reports';
        $cached_data = get_transient($transient_key);
        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-01');
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-t');

        $data = [
            'balance_sheet' => $this->get_balance_sheet_data($start_date, $end_date),
            'profit_and_loss' => $this->get_profit_and_loss_data($start_date, $end_date),
            'discipline_income' => $this->get_discipline_income_data($start_date, $end_date)
        ];

        set_transient($transient_key, $data, HOUR_IN_SECONDS);
        wp_send_json_success($data);
    }

    private function get_balance_sheet_data($start_date, $end_date)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';
        $total_income = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = 'دریافت' AND date BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        $total_expense = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = 'هزینه' AND date BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        return [
            'assets' => floatval($total_income),
            'liabilities' => floatval($total_expense),
            'equity' => floatval($total_income - $total_expense)
        ];
    }

    private function get_profit_and_loss_data($start_date, $end_date)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT YEAR(date) as year, MONTH(date) as month,
                 SUM(CASE WHEN type='دریافت' THEN amount ELSE 0 END) as income,
                 SUM(CASE WHEN type='هزینه' THEN amount ELSE 0 END) as expense
                 FROM $table
                 WHERE date BETWEEN %s AND %s
                 GROUP BY year, month
                 ORDER BY year, month",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        return $results;
    }

    private function get_discipline_income_data($start_date, $end_date)
    {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'gym_transactions';
        $disciplines = get_posts(['post_type' => 'sports_discipline', 'numberposts' => -1]);
        $data = [];

        foreach ($disciplines as $discipline) {
            $user_ids = get_users(['meta_key' => 'sport_discipline', 'meta_value' => $discipline->ID, 'fields' => 'ID']);
            if (!empty($user_ids)) {
                $income = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(amount) FROM $transactions_table WHERE user_id IN (" . implode(',', array_map('intval', $user_ids)) . ") AND type = 'دریافت' AND date BETWEEN %s AND %s",
                    $start_date, $end_date
                ));
                $data[] = [
                    'label' => $discipline->post_title,
                    'income' => floatval($income)
                ];
            }
        }
        return $data;
    }
}