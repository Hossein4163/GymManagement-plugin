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

    public function process_buffet_sale()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale']) && check_admin_referer('my_gym_buffet_sale_nonce')) {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $sale_price = floatval($_POST['sale_price']);
            $customer_name = sanitize_text_field($_POST['customer_name'] ?: 'نامشخص');

            if ($product_id <= 0 || $quantity <= 0 || $sale_price <= 0) {
                add_settings_error('my_gym_messages', 'invalid_sale', 'اطلاعات فروش نامعتبر است.', 'error');
                return;
            }

            $stock = get_post_meta($product_id, 'stock', true);
            if ($stock !== '' && intval($stock) < $quantity) {
                add_settings_error('my_gym_messages', 'insufficient_stock', 'موجودی کافی نیست.', 'error');
                return;
            }

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

                if ($stock !== '') {
                    update_post_meta($product_id, 'stock', intval($stock) - $quantity);
                }

                (new AccountingController())->create_transaction(0, $sale_price, 'دریافت', 'نقدی', 'فروش بوفه');
                add_settings_error('my_gym_messages', 'sale_added', 'فروش با موفقیت ثبت شد.', 'success');
            } else {
                add_settings_error('my_gym_messages', 'sale_error', 'خطا در ثبت فروش.', 'error');
            }
        }
    }
}