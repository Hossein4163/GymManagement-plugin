<?php

namespace GymManagement\Controllers;

final class BuffetController
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_rame_gym_buffet_sale', [$this, 'process_sale_form']);

        add_action('wp_ajax_my_gym_get_users', [$this, 'ajax_get_users']);
        add_action('wp_ajax_nopriv_my_gym_get_users', [$this, 'ajax_get_users']); // For potential frontend use
        add_action('wp_ajax_my_gym_load_products_by_category', [$this, 'ajax_load_products_by_category']);
    }

    public function add_submenu_page()
    {
        add_submenu_page(
            'rame-gym',
            __('مدیریت بوفه', 'rame-gym'),
            __('بوفه', 'rame-gym'),
            'manage_options',
            'rame-gym-buffet',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/buffet-page.php';
    }

    public function process_sale_form()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'my_gym_buffet_sale_nonce')) {
            wp_die('Invalid nonce.');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Access Denied.');
        }

        global $wpdb;
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $quantities = isset($_POST['quantities']) ? (array)$_POST['quantities'] : [];

        if ($customer_id <= 0) {
            add_settings_error('my_gym_messages', 'invalid_customer', 'لطفاً مشتری را انتخاب کنید.', 'error');
            wp_redirect(admin_url('admin.php?page=rame-gym-buffet'));
            exit;
        }

        $sale_items = [];
        $total_sale_price = 0;

        // Step 1: Validate all items and calculate total before touching the database.
        foreach ($quantities as $product_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity <= 0) continue;

            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'buffet_product') {
                add_settings_error('my_gym_messages', 'invalid_product', "محصول با شناسه $product_id نامعتبر است.", 'error');
                wp_redirect(admin_url('admin.php?page=rame-gym-buffet'));
                exit;
            }

            $current_stock = get_post_meta($product_id, '_stock', true);
            if ($current_stock !== '' && intval($current_stock) < $quantity) {
                add_settings_error('my_gym_messages', 'insufficient_stock', 'موجودی محصول "' . $product->post_title . '" کافی نیست.', 'error');
                wp_redirect(admin_url('admin.php?page=rame-gym-buffet'));
                exit;
            }

            $price = floatval(get_post_meta($product_id, '_price', true));
            $subtotal = $price * $quantity;
            $total_sale_price += $subtotal;

            $sale_items[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price_per_item' => $price,
                'subtotal' => $subtotal,
                'current_stock' => $current_stock,
            ];
        }

        if (empty($sale_items)) {
            add_settings_error('my_gym_messages', 'no_items', 'حداقل یک محصول با تعداد معتبر انتخاب کنید.', 'error');
            wp_redirect(admin_url('admin.php?page=rame-gym-buffet'));
            exit;
        }

        // Step 2: Begin database transaction.
        $wpdb->query('START TRANSACTION');

        try {
            // Step 3: Insert the main sale record.
            $sales_table = $wpdb->prefix . 'gym_buffet_sales';
            $wpdb->insert(
                $sales_table,
                ['customer_id' => $customer_id, 'total_amount' => $total_sale_price],
                ['%d', '%f']
            );
            $sale_id = $wpdb->insert_id;

            if (!$sale_id) {
                throw new \Exception('خطا در ثبت فاکتور فروش در پایگاه داده.');
            }

            // Step 4: Insert sale items and update stock for each.
            $sale_items_table = $wpdb->prefix . 'gym_buffet_sale_items';
            foreach ($sale_items as $item) {
                $item_inserted = $wpdb->insert(
                    $sale_items_table,
                    [
                        'sale_id' => $sale_id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price_per_item' => $item['price_per_item'],
                        'subtotal' => $item['subtotal'],
                    ]
                );

                if (!$item_inserted) {
                    throw new \Exception('خطا در ثبت آیتم‌های فاکتور.');
                }

                // Update stock for this item
                if ($item['current_stock'] !== '') {
                    $new_stock = intval($item['current_stock']) - $item['quantity'];
                    update_post_meta($item['product_id'], '_stock', $new_stock);
                }
            }

            // Step 5: Create a single accounting transaction for the whole sale.
            (new AccountingController())->create_transaction(
                $customer_id,
                $total_sale_price,
                'دریافت',
                'بوفه',
                'فروش بوفه - فاکتور شماره ' . $sale_id
            );

            // Step 6: If all is well, commit the transaction.
            $wpdb->query('COMMIT');
            add_settings_error('my_gym_messages', 'sale_added', 'فروش با موفقیت ثبت شد.', 'updated');

        } catch (\Exception $e) {
            // Step 7: If anything fails, roll back all database changes.
            $wpdb->query('ROLLBACK');
            add_settings_error('my_gym_messages', 'sale_failed', 'خطای بحرانی در پردازش فروش: ' . $e->getMessage(), 'error');
        }

        wp_redirect(admin_url('admin.php?page=rame-gym-buffet'));
        exit;
    }

    public function ajax_get_users()
    {
        check_ajax_referer('my_gym_security_nonce', 'security');

        $term = isset($_REQUEST['q']) ? sanitize_text_field($_REQUEST['q']) : '';
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $per_page = 20;

        $args = [
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'search' => !empty($term) ? '*' . esc_attr($term) . '*' : '',
            'search_columns' => ['display_name', 'user_email', 'user_login'],
        ];

        $user_query = new \WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->ID, 'text' => $user->display_name . ' (' . $user->user_email . ')'];
        }

        wp_send_json_success([
            'results' => $results,
            'pagination' => ['more' => ($per_page * $page) < $total_users]
        ]);
    }

    public function ajax_load_products_by_category()
    {
        check_ajax_referer('my_gym_security_nonce', 'security');

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        $args = [
            'post_type' => 'buffet_product',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        if ($category_id > 0) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'buffet_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ];
        }

        $products = get_posts($args);
        $data = [];

        foreach ($products as $product) {
            $stock = get_post_meta($product->ID, '_stock', true);
            $price = get_post_meta($product->ID, '_price', true);
            $categories = wp_get_post_terms($product->ID, 'buffet_category', ['fields' => 'names']);

            $data[] = [
                'id' => $product->ID,
                'title' => $product->post_title,
                'categories' => !empty($categories) ? implode(', ', $categories) : '—',
                'stock' => $stock,
                'stock_display' => $stock !== '' ? intval($stock) : 'نامحدود',
                'price' => floatval($price)
            ];
        }

        wp_send_json_success($data);
    }
}