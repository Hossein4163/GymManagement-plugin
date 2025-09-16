<?php

namespace GymManagement\Controllers;

class BuffetController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_buffet_menu'));
        add_action('init', array($this, 'register_buffet_sale_cpt'));
        add_action('admin_init', array($this, 'process_buffet_sale'));
    }

    public function add_buffet_menu()
    {
        add_submenu_page(
            'rame-gym',
            'مدیریت بوفه',
            'بوفه',
            'manage_options',
            'my-gym-buffet',
            array($this, 'render_buffet_page'),
            null
        );
    }

    public function register_buffet_sale_cpt()
    {
        $labels = array('name' => 'فروش بوفه', 'singular_name' => 'فروش بوفه');
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'rame-gym',
            'supports' => array('title'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
        );
        register_post_type('buffet_sale', $args);
    }

    public function render_buffet_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/buffet-page.php';
    }

    private function process_buffet_sale()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale'])) {
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
            $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : 'نامشخص';

            if ($product_id > 0 && $quantity > 0 && $sale_price > 0) {
                $post_id = wp_insert_post(array(
                    'post_title' => get_the_title($product_id) . ' (' . $customer_name . ')',
                    'post_type' => 'buffet_sale',
                    'post_status' => 'publish'
                ));

                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, 'product_id', $product_id);
                    update_post_meta($post_id, 'quantity', $quantity);
                    update_post_meta($post_id, 'price', $sale_price);
                    update_post_meta($post_id, 'customer_name', $customer_name);

                    (new AccountingController())->create_transaction(0, $sale_price, 'دریافت', 'نقدی', 'فروش بوفه');
                }
            }
        }
    }
}