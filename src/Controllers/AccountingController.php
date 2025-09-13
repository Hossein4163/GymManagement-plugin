<?php

namespace GymManagement\Controllers;

class AccountingController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_accounting_menu'));
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
        $table_name = $wpdb->prefix . 'gym_transactions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            type varchar(50) NOT NULL,
            payment_type varchar(50) NOT NULL,
            description text,
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function render_accounting_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/accounting-page.php';
    }
}