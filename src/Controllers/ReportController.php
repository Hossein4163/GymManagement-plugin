<?php

namespace MyGym\Controllers;

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
        require_once MY_GYM_PLUGIN_PATH . 'views/reports-page.php';
    }

    public function get_financial_reports_data()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');

        $data = array(
            'balance_sheet' => $this->get_balance_sheet_data(),
            'profit_and_loss' => $this->get_profit_and_loss_data(),
            'discipline_income' => $this->get_discipline_income_data()
        );

        wp_send_json_success($data);
    }

    private function get_balance_sheet_data()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';
        $total_income = $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE type = 'دریافت'");
        $total_expense = $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE type = 'هزینه'");

        return [
            'assets' => floatval($total_income),
            'liabilities' => floatval($total_expense),
            'equity' => floatval($total_income - $total_expense)
        ];
    }

    private function get_profit_and_loss_data()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';

        $results = $wpdb->get_results(
            "SELECT YEAR(date) as year, MONTH(date) as month,
             SUM(CASE WHEN type='دریافت' THEN amount ELSE 0 END) as income,
             SUM(CASE WHEN type='هزینه' THEN amount ELSE 0 END) as expense
             FROM $table
             GROUP BY year, month
             ORDER BY year, month",
            ARRAY_A
        );
        return $results;
    }

    private function get_discipline_income_data()
    {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'gym_transactions';
        $disciplines = get_posts(['post_type' => 'sports_discipline', 'numberposts' => -1]);
        $data = [];

        foreach ($disciplines as $discipline) {
            $user_ids = get_users(['meta_key' => 'sport_discipline', 'meta_value' => $discipline->ID, 'fields' => 'ID']);
            if (!empty($user_ids)) {
                $income = $wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE user_id IN (" . implode(',', array_map('intval', $user_ids)) . ") AND type = 'دریافت'");
                $data[] = [
                    'label' => $discipline->post_title,
                    'income' => floatval($income)
                ];
            }
        }
        return $data;
    }
}