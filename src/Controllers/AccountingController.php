<?php

namespace GymManagement\Controllers;

use GymManagement\Models\Transaction;
use GymManagement\Models\Installment;

class AccountingController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_accounting_menu'));
        add_action('init', array($this, 'create_tables'));
        add_action('wp_ajax_my_gym_pay_installment', array($this, 'ajax_pay_installment'));
    }

    public function add_accounting_menu()
    {
        add_menu_page(
            'حسابداری باشگاه',
            'حسابداری',
            'manage_options',
            'my-gym-accounting',
            array($this, 'render_accounting_page'),
            'dashicons-chart-pie',
            7
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
}