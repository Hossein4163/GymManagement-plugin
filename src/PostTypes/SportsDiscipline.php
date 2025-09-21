<?php

namespace GymManagement\PostTypes;

final class SportsDiscipline
{
    private const POST_TYPE = 'sports_discipline';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_fields']);

        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_custom_column'], 10, 2);
    }

    public function register()
    {
        $labels = [
            'name' => __('رشته‌های ورزشی', 'rame-gym'),
            'singular_name' => __('رشته ورزشی', 'rame-gym'),
            'add_new' => __('افزودن رشته', 'rame-gym'),
            'add_new_item' => __('افزودن رشته جدید', 'rame-gym'),
            'edit_item' => __('ویرایش رشته', 'rame-gym'),
            'new_item' => __('رشته جدید', 'rame-gym'),
            'view_item' => __('مشاهده رشته', 'rame-gym'),
            'search_items' => __('جستجوی رشته‌ها', 'rame-gym'),
            'not_found' => __('رشته‌ای یافت نشد', 'rame-gym'),
            'not_found_in_trash' => __('رشته‌ای در زباله‌دان یافت نشد', 'rame-gym'),
            'all_items' => __('همه رشته‌ها', 'rame-gym'),
            'menu_name' => __('رشته‌ها', 'rame-gym'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'rame-gym',
            'supports' => ['title'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'publicly_queryable' => false,
            'menu_icon' => 'dashicons-awards',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'sports_discipline_details',
            __('جزئیات رشته', 'rame-gym'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post)
    {
        wp_nonce_field('sports_discipline_meta_box', 'sports_discipline_nonce');
        $price = get_post_meta($post->ID, '_price', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="price"><?php _e('قیمت شهریه ماهانه (تومان)', 'rame-gym'); ?></label></th>
                <td>
                    <input type="number" name="price" id="price" value="<?php echo esc_attr($price); ?>"
                           class="regular-text" min="0" step="1000">
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_fields(int $post_id)
    {
        if (!isset($_POST['sports_discipline_nonce']) || !wp_verify_nonce($_POST['sports_discipline_nonce'], 'sports_discipline_meta_box')) {
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
    }

    public function add_custom_columns(array $columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['price'] = __('قیمت شهریه', 'rame-gym');
            }
        }
        return $new_columns;
    }

    public function render_custom_column(string $column, int $post_id)
    {
        if ($column === 'price') {
            $price = get_post_meta($post_id, '_price', true);
            echo $price ? '<strong>' . number_format((float)$price) . ' تومان</strong>' : '—';
        }
    }
}