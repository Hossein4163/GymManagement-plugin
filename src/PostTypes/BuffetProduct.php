<?php

namespace GymManagement\PostTypes;

final class BuffetProduct
{
    private const POST_TYPE = 'buffet_product';
    private const TAXONOMY = 'buffet_category';

    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_fields']);

        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_custom_column'], 10, 2);
    }

    public function register_post_type()
    {
        $labels = [
            'name' => __('محصولات بوفه', 'rame-gym'),
            'singular_name' => __('محصول بوفه', 'rame-gym'),
            'add_new_item' => __('افزودن محصول جدید', 'rame-gym'),
            'edit_item' => __('ویرایش محصول', 'rame-gym'),
            'all_items' => __('همه محصولات', 'rame-gym'),
            'menu_name' => __('محصولات بوفه', 'rame-gym'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'rame-gym',
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'publicly_queryable' => false,
            'menu_icon' => 'dashicons-cart',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register_taxonomy()
    {
        $labels = [
            'name' => __('دسته‌بندی‌های بوفه', 'rame-gym'),
            'singular_name' => __('دسته‌بندی', 'rame-gym'),
            'search_items' => __('جستجوی دسته‌بندی‌ها', 'rame-gym'),
            'all_items' => __('همه دسته‌بندی‌ها', 'rame-gym'),
            'edit_item' => __('ویرایش دسته‌بندی', 'rame-gym'),
            'update_item' => __('بروزرسانی دسته‌بندی', 'rame-gym'),
            'add_new_item' => __('افزودن دسته‌بندی جدید', 'rame-gym'),
            'new_item_name' => __('نام دسته‌بندی جدید', 'rame-gym'),
            'menu_name' => __('دسته‌بندی‌ها', 'rame-gym'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        ];

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'buffet_product_details',
            __('جزئیات محصول', 'rame-gym'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post)
    {
        wp_nonce_field('buffet_product_meta_box', 'buffet_product_nonce');
        $price = get_post_meta($post->ID, '_price', true);
        $stock = get_post_meta($post->ID, '_stock', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="price"><?php _e('قیمت فروش (تومان)', 'rame-gym'); ?></label></th>
                <td>
                    <input type="number" name="price" id="price" value="<?php echo esc_attr($price); ?>"
                           class="regular-text" min="0">
                </td>
            </tr>
            <tr>
                <th><label for="stock"><?php _e('موجودی انبار', 'rame-gym'); ?></label></th>
                <td>
                    <input type="number" name="stock" id="stock" value="<?php echo esc_attr($stock); ?>"
                           class="regular-text" min="0">
                    <p class="description"><?php _e('برای موجودی نامحدود، این فیلد را خالی بگذارید.', 'rame-gym'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_fields(int $post_id)
    {
        if (!isset($_POST['buffet_product_nonce']) || !wp_verify_nonce($_POST['buffet_product_nonce'], 'buffet_product_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['price'])) {
            update_post_meta($post_id, '_price', sanitize_text_field($_POST['price']));
        }
        if (isset($_POST['stock'])) {
            // Allow empty string for unlimited stock
            $stock = $_POST['stock'] === '' ? '' : (int)$_POST['stock'];
            update_post_meta($post_id, '_stock', $stock);
        }
    }

    public function add_custom_columns(array $columns): array
    {
        $new_columns = [];
        // Insert new columns after the title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['price'] = __('قیمت', 'rame-gym');
                $new_columns['stock'] = __('موجودی', 'rame-gym');
            }
        }
        return $new_columns;
    }

    public function render_custom_column(string $column, int $post_id)
    {
        switch ($column) {
            case 'price':
                $price = get_post_meta($post_id, '_price', true);
                echo $price ? '<strong>' . number_format((float)$price) . ' تومان</strong>' : '—';
                break;
            case 'stock':
                $stock = get_post_meta($post_id, '_stock', true);
                if ($stock === '') {
                    echo 'نامحدود';
                } elseif ((int)$stock > 0) {
                    echo (int)$stock;
                } else {
                    echo '<strong style="color: #dc3545;">' . __('تمام شده', 'rame-gym') . '</strong>';
                }
                break;
        }
    }
}