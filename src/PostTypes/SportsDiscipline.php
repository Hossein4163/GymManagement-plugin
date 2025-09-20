<?php

namespace GymManagement\PostTypes;

class SportsDiscipline
{
    public function __construct()
    {
        // Change priority to ensure early registration
        add_action('init', array($this, 'register'), 1); // Changed from 0 to 1
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post', array($this, 'save_custom_meta_fields'));
    }

    public function register()
    {
        $labels = array(
            'name' => __('رشته‌های ورزشی', 'rame-gym'),
            'singular_name' => __('رشته ورزشی', 'rame-gym'),
            'add_new' => __('افزودن رشته جدید', 'rame-gym'),
            'edit_item' => __('ویرایش رشته', 'rame-gym'),
            'new_item' => __('رشته جدید', 'rame-gym'),
            'view_item' => __('مشاهده رشته', 'rame-gym'),
            'search_items' => __('جستجوی رشته‌ها', 'rame-gym'),
            'all_items' => __('همه رشته‌ها', 'rame-gym'), // Added this
            'menu_name' => __('رشته‌های ورزشی', 'rame-gym'), // Added this
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'supports' => array('title'),
            'show_in_menu' => false, // Changed to false since we're adding manually
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'publicly_queryable' => false,
            'show_ui' => true, // Make sure UI is shown
            'show_in_admin_bar' => true, // Show in admin bar
            'menu_position' => null,
            'hierarchical' => false,
        );

        register_post_type('sports_discipline', $args);
    }

    public function add_custom_meta_boxes()
    {
        add_meta_box(
            'sports_discipline_details',
            __('جزئیات رشته', 'rame-gym'),
            array($this, 'render_meta_box'),
            'sports_discipline',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        $age_min = get_post_meta($post->ID, 'age_min', true);
        $age_max = get_post_meta($post->ID, 'age_max', true);
        $price = get_post_meta($post->ID, 'price', true);
        wp_nonce_field('sports_discipline_nonce', 'sports_discipline_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="age_min"><?php _e('حداقل سن', 'rame-gym'); ?></label></th>
                <td><input type="number" name="age_min" id="age_min" value="<?php echo esc_attr($age_min); ?>"
                           class="regular-text" min="0"/></td>
            </tr>
            <tr>
                <th><label for="age_max"><?php _e('حداکثر سن', 'rame-gym'); ?></label></th>
                <td><input type="number" name="age_max" id="age_max" value="<?php echo esc_attr($age_max); ?>"
                           class="regular-text" min="0"/></td>
            </tr>
            <tr>
                <th><label for="price"><?php _e('قیمت شهریه (تومان)', 'rame-gym'); ?></label></th>
                <td><input type="number" name="price" id="price" value="<?php echo esc_attr($price); ?>"
                           class="regular-text" min="0" step="0.01"/></td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_meta_fields($post_id)
    {
        if (!isset($_POST['sports_discipline_nonce']) || !wp_verify_nonce($_POST['sports_discipline_nonce'], 'sports_discipline_nonce')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        if (isset($_POST['age_min'])) {
            update_post_meta($post_id, 'age_min', sanitize_text_field($_POST['age_min']));
        }
        if (isset($_POST['age_max'])) {
            update_post_meta($post_id, 'age_max', sanitize_text_field($_POST['age_max']));
        }
        if (isset($_POST['price'])) {
            update_post_meta($post_id, 'price', sanitize_text_field($_POST['price']));
        }
    }
}