<?php

namespace GymManagement\Controllers;

final class ReportController
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('wp_ajax_my_gym_get_financial_reports', [$this, 'ajax_get_financial_reports']);
        add_action('admin_post_rame_gym_export_reports', [$this, 'export_reports_to_csv']);
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'rame-gym',
            __('گزارشات', 'rame-gym'),
            __('گزارشات', 'rame-gym'),
            'manage_options',
            'rame-gym-reports',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/reports-page.php';
    }

    public function ajax_get_financial_reports()
    {
        check_ajax_referer('my_gym_security-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'عدم دسترسی.']);
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-01');
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-t');
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        $data = [
            'profit_and_loss' => $this->get_profit_and_loss_data($start_date, $end_date, $user_id),
            'discipline_income' => $this->get_discipline_income_data($start_date, $end_date, $user_id),
        ];

        wp_send_json_success($data);
    }

    private function get_profit_and_loss_data(string $start_date, string $end_date, int $user_id = 0): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';

        $sql = "SELECT YEAR(date) as year, MONTH(date) as month,
                 SUM(CASE WHEN type='دریافت' THEN amount ELSE 0 END) as income,
                 SUM(CASE WHEN type='هزینه' THEN amount ELSE 0 END) as expense
                 FROM {$table}
                 WHERE date BETWEEN %s AND %s";

        $params = [$start_date, $end_date];
        if ($user_id > 0) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        }
        $sql .= " GROUP BY year, month ORDER BY year, month";

        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    private function get_discipline_income_data(string $start_date, string $end_date, int $user_id = 0): array
    {
        global $wpdb;

        $sql = "SELECT p.post_title AS label, SUM(t.amount) AS income
                FROM {$wpdb->prefix}gym_transactions t
                INNER JOIN {$wpdb->prefix}gym_memberships m ON t.user_id = m.user_id
                INNER JOIN {$wpdb->posts} p ON m.discipline_id = p.ID
                WHERE t.type = 'دریافت'
                AND t.date BETWEEN %s AND %s
                AND p.post_type = 'sports_discipline'";

        $params = [$start_date, $end_date];
        if ($user_id > 0) {
            $sql .= " AND t.user_id = %d";
            $params[] = $user_id;
        }
        $sql .= " GROUP BY p.post_title ORDER BY income DESC";

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        foreach ($results as $result) {
            $result->income = (float)$result->income;
        }
        return $results;
    }

    public function export_reports_to_csv()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'my_gym_security-nonce') || !current_user_can('manage_options')) {
            wp_die('عدم دسترسی!');
        }

        $start_date = sanitize_text_field($_GET['start_date']);
        $end_date = sanitize_text_field($_GET['end_date']);
        $user_id = intval($_GET['user_id']);

        $data = $this->get_profit_and_loss_data($start_date, $end_date, $user_id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=financial-report-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['سال', 'ماه', 'درآمد (تومان)', 'هزینه (تومان)', 'سود/زیان (تومان)']);

        if (!empty($data)) {
            foreach ($data as $row) {
                $profit_loss = $row['income'] - $row['expense'];
                fputcsv($output, [$row['year'], $row['month'], $row['income'], $row['expense'], $profit_loss]);
            }
        }

        fclose($output);
        exit;
    }
}