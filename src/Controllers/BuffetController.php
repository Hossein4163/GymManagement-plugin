<?php

namespace GymManagement\Controllers;

class BuffetController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_buffet_menu'));
        add_action('init', array($this, 'register_buffet_sale_cpt'));
        add_action('admin_init', array($this, 'process_buffet_sale'));

        // AJAX handlers
        add_action('wp_ajax_my_gym_get_users', array($this, 'ajax_get_users'));
        add_action('wp_ajax_my_gym_load_products_by_category', array($this, 'ajax_load_products_by_category'));
    }

    public function add_buffet_menu()
    {
        add_submenu_page(
            'rame-gym',
            __('مدیریت بوفه', 'rame-gym'),
            __('بوفه', 'rame-gym'),
            'manage_options',
            'my-gym-buffet',
            array($this, 'render_buffet_page')
        );
    }

    public function register_buffet_sale_cpt()
    {
        $labels = array(
            'name' => __('فروش بوفه', 'rame-gym'),
            'singular_name' => __('فروش بوفه', 'rame-gym'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'publicly_queryable' => false,
        );

        register_post_type('buffet_sale', $args);
    }

    public function render_buffet_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'rame-gym'));
        }
        require_once MY_GYM_PLUGIN_PATH . 'views/buffet-page.php';
    }

    public function ajax_get_users()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');

        $term = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;

        $args = array(
            'role__in' => array('subscriber', 'administrator', 'editor'),
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
        );

        if (!empty($term)) {
            $args['search'] = '*' . $term . '*';
            $args['search_columns'] = array('display_name', 'user_email', 'user_login');
        }

        $users = get_users($args);

        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'id' => $user->ID,
                'text' => $user->display_name . ' (' . $user->user_email . ')'
            );
        }

        wp_send_json_success(array('results' => $results, 'pagination' => array('more' => count($users) === $per_page)));
    }

    public function ajax_load_products_by_category()
    {
        check_ajax_referer('my-gym-security-nonce', 'security');

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        $args = array(
            'post_type' => 'buffet_product',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if ($category_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'buffet_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            );
        }

        $products = get_posts($args);
        $data = array();

        foreach ($products as $product) {
            $stock = get_post_meta($product->ID, 'stock', true);
            $price = get_post_meta($product->ID, 'price', true);
            $categories = wp_get_post_terms($product->ID, 'buffet_category', array('fields' => 'names'));
            $data[] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'categories' => implode(', ', $categories) ?: '-',
                'stock' => $stock,
                'stock_display' => $stock !== '' ? intval($stock) : 'نامحدود',
                'price' => floatval($price)
            );
        }

        wp_send_json_success($data);
    }

    public function process_buffet_sale()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale']) && check_admin_referer('my_gym_buffet_sale_nonce')) {
            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            $quantities = isset($_POST['quantities']) ? array_map('intval', $_POST['quantities']) : array();

            if ($customer_id <= 0 || empty($quantities)) {
                add_settings_error('my_gym_messages', 'invalid_sale', 'اطلاعات فروش نامعتبر است.', 'error');
                return;
            }

            $customer = get_user_by('id', $customer_id);
            $customer_name = $customer ? $customer->display_name : 'نامشخص';

            $total_sale_price = 0;
            $sale_items = array();

            foreach ($quantities as $product_id => $quantity) {
                $product_id = intval($product_id);
                if ($product_id <= 0 || $quantity <= 0) continue;

                $product_price = floatval(get_post_meta($product_id, 'price', true));
                $sale_price = $product_price * $quantity;
                $total_sale_price += $sale_price;

                $stock = get_post_meta($product_id, 'stock', true);
                if ($stock !== '' && intval($stock) < $quantity) {
                    add_settings_error('my_gym_messages', 'insufficient_stock', 'موجودی کافی نیست برای محصول ' . get_the_title($product_id), 'error');
                    continue;
                }

                $sale_items[] = array(
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $sale_price,
                    'product_name' => get_the_title($product_id)
                );
            }

            if (empty($sale_items)) {
                add_settings_error('my_gym_messages', 'no_valid_items', 'هیچ محصول معتبری برای فروش وجود ندارد.', 'error');
                return;
            }

            foreach ($sale_items as $item) {
                $post_id = wp_insert_post(array(
                    'post_title' => $item['product_name'] . ' (' . $customer_name . ')',
                    'post_type' => 'buffet_sale',
                    'post_status' => 'publish'
                ));

                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, 'product_id', $item['product_id']);
                    update_post_meta($post_id, 'quantity', $item['quantity']);
                    update_post_meta($post_id, 'price', $item['price']);
                    update_post_meta($post_id, 'customer_name', $customer_name);
                    update_post_meta($post_id, 'customer_id', $customer_id);

                    if ($stock !== '') {
                        update_post_meta($item['product_id'], 'stock', intval($stock) - $item['quantity']);
                    }
                }
            }

            if ($total_sale_price > 0 && class_exists('GymManagement\Controllers\AccountingController')) {
                $accounting = new AccountingController();
                $accounting->create_transaction($customer_id, $total_sale_price, 'دریافت', 'نقدی', 'فروش بوفه - ' . count($sale_items) . ' محصول');
            }

            add_settings_error('my_gym_messages', 'sale_added', 'فروش با موفقیت ثبت شد. مجموع: ' . number_format($total_sale_price) . ' تومان', 'success');
        }
    }
}