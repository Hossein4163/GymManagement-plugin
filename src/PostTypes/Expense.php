<?php

namespace GymManagement\PostTypes;

final class Expense
{
    private const POST_TYPE = 'expense';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_fields']);
    }

    public function register()
    {
        $labels = [
            'name' => __('هزینه‌ها', 'rame-gym'),
            'singular_name' => __('هزینه', 'rame-gym'),
            'add_new_item' => __('افزودن هزینه جدید', 'rame-gym'),
            'edit_item' => __('ویرایش هزینه', 'rame-gym'),
            'all_items' => __('همه هزینه‌ها', 'rame-gym'),
            'menu_name' => __('هزینه‌ها', 'rame-gym'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'rame-gym',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'publicly_queryable' => false,
            'menu_icon' => 'dashicons-money-alt',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'expense_details',
            __('جزئیات هزینه', 'rame-gym'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post)
    {
        wp_nonce_field('expense_meta_box', 'expense_nonce');
        $amount = get_post_meta($post->ID, '_amount', true);
        ?>
        <p>
            <label for="amount"><strong><?php _e('مبلغ هزینه (تومان)', 'rame-gym'); ?></strong></label>
            <br>
            <input type="number" name="amount" id="amount" value="<?php echo esc_attr($amount); ?>" class="widefat"
                   min="0">
        </p>
        <?php
    }

    public function save_meta_fields(int $post_id)
    {
        if (!isset($_POST['expense_nonce']) || !wp_verify_nonce($_POST['expense_nonce'], 'expense_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['amount'])) {
            update_post_meta($post_id, '_amount', sanitize_text_field($_POST['amount']));
        }
    }
}