<?php

namespace GymManagement\Controllers;

use GymManagement\Models\Transaction;
use GymManagement\Models\Installment;
use DateTime;

class AccountingController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_accounting_menu'));
        add_action('init', array($this, 'create_tables'));
        add_action('wp_ajax_my_gym_pay_installment', array($this, 'ajax_pay_installment'));
        add_action('wp_ajax_my_gym_get_dashboard_data', array($this, 'get_dashboard_data'));
    }

    public function add_accounting_menu()
    {
        add_submenu_page(
            'rame-gym',
            'حسابداری',
            'حسابداری',
            'manage_options',
            'my-gym-accounting',
            array($this, 'render_accounting_page'),
            null
        );
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
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE $installments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            due_date date NOT NULL,
            payment_date datetime DEFAULT NULL,
            status varchar(50) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    public function render_accounting_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/accounting-page.php';
    }

    public function ajax_pay_installment()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die('شما اجازه دسترسی به این بخش را ندارید.');
        }

        $installment_id = isset($_POST['installment_id']) ? intval($_POST['installment_id']) : 0;
        if ($installment_id > 0) {
            $this->process_installment_payment($installment_id);
            wp_send_json_success('پرداخت قسط با موفقیت ثبت شد.');
        }

        wp_die();
    }

    public function process_installment_payment($installment_id)
    {
        global $wpdb;
        $installments_table = $wpdb->prefix . 'gym_installments';
        $transactions_table = $wpdb->prefix . 'gym_transactions';

        $installment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $installments_table WHERE id = %d", $installment_id));

        if ($installment && $installment->status !== 'paid') {
            $wpdb->update(
                $installments_table,
                ['status' => 'paid', 'payment_date' => current_time('mysql')],
                ['id' => $installment_id]
            );

            $wpdb->insert(
                $transactions_table,
                [
                    'user_id' => $installment->user_id,
                    'amount' => $installment->amount,
                    'type' => 'دریافت',
                    'payment_type' => 'اقساط',
                    'description' => 'پرداخت قسط',
                    'date' => current_time('mysql')
                ]
            );
            return true;
        }
        return false;
    }

    public function get_dashboard_data()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');

        $data = array(
            'income' => $this->get_total_income_this_month(),
            'expense' => $this->get_total_expense_this_month(),
            'overdue_installments' => $this->get_overdue_installments_count(),
            'total_members' => count_users()->total_users,
            'monthly_data' => $this->get_monthly_data(),
            'disciplines_data' => $this->get_disciplines_data()
        );
        wp_send_json_success($data);
    }

    private function get_total_income_this_month()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';
        $year = date('Y');
        $month = date('m');
        return $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE type = 'دریافت' AND YEAR(date) = $year AND MONTH(date) = $month");
    }

    private function get_total_expense_this_month()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_transactions';
        $year = date('Y');
        $month = date('m');
        return $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE type = 'هزینه' AND YEAR(date) = $year AND MONTH(date) = $month");
    }

    private function get_overdue_installments_count()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gym_installments';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'overdue'");
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
        global $wpdb;
        $users = get_users();
        $disciplines = [];

        foreach ($users as $user) {
            $discipline_id = get_user_meta($user->ID, 'sport_discipline', true);
            if ($discipline_id) {
                $discipline_name = get_the_title($discipline_id);
                if (!isset($disciplines[$discipline_name])) {
                    $disciplines[$discipline_name] = 0;
                }
                $disciplines[$discipline_name]++;
            }
        }

        return ['labels' => array_keys($disciplines), 'counts' => array_values($disciplines)];
    }
}