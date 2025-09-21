<?php

namespace GymManagement;

class Installer
{
    /**
     * The main method to run on plugin activation.
     */
    public function run()
    {
        $this->create_tables();
        $this->schedule_events();
        flush_rewrite_rules();
    }

    /**
     * Creates all necessary custom database tables.
     */
    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Accounting & Installments Tables
        $transactions_table = $wpdb->prefix . 'gym_transactions';
        $sql1 = "CREATE TABLE $transactions_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            type VARCHAR(50) NOT NULL,
            payment_type VARCHAR(50) NOT NULL,
            description TEXT,
            date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql1);

        // Buffet Sales Tables
        $sales_table = $wpdb->prefix . 'gym_buffet_sales';
        $sql3 = "CREATE TABLE $sales_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT(20) UNSIGNED NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id)
        ) $charset_collate;";
        dbDelta($sql3);

        $sale_items_table = $wpdb->prefix . 'gym_buffet_sale_items';
        $sql4 = "CREATE TABLE $sale_items_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sale_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            quantity INT(11) NOT NULL,
            price_per_item DECIMAL(10, 2) NOT NULL,
            subtotal DECIMAL(10, 2) NOT NULL,
            PRIMARY KEY (id),
            KEY sale_id (sale_id)
        ) $charset_collate;";
        dbDelta($sql4);

        // Memberships Table
        $memberships_table = $wpdb->prefix . 'gym_memberships';
        $sql5 = "CREATE TABLE $memberships_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            discipline_id BIGINT(20) UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            payment_type VARCHAR(20) NOT NULL DEFAULT 'full',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql5);

        // Installments Table (Tied to Memberships)
        $installments_table = $wpdb->prefix . 'gym_installments';
        $sql2 = "CREATE TABLE $installments_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            due_date DATE NOT NULL,
            payment_date DATETIME DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            PRIMARY KEY (id),
            KEY membership_id (membership_id)
        ) $charset_collate;";
        dbDelta($sql2);
    }

    /**
     * Schedules daily cron events for automated tasks.
     */
    public function schedule_events()
    {
        if (!wp_next_scheduled('rame_gym_daily_tasks')) {
            wp_schedule_event(time(), 'daily', 'rame_gym_daily_tasks');
        }
    }

    /**
     * The main method to run on plugin deactivation.
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('rame_gym_daily_tasks');
        flush_rewrite_rules();
    }
}