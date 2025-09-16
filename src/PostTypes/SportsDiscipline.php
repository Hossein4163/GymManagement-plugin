<?php

namespace GymManagement\PostTypes;

class SportsDiscipline
{
    public function __construct()
    {
        add_action('init', array($this, 'register'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post', array($this, 'save_custom_meta_fields'));
    }

    public function register()
    {
        $labels = array('name' => 'رشته‌های ورزشی', 'singular_name' => 'رشته ورزشی');
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'),
            'show_in_menu' => 'rame-gym'
        );
        register_post_type('sports_discipline', $args);
    }

    public function add_custom_meta_boxes()
    {
        add_meta_box(
            'sports_discipline_details',
            'جزئیات رشته',
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
        wp_nonce_field(basename(__FILE__), 'sports_discipline_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="age_min">حداقل سن</label></th>
                <td><input type="number" name="age_min" id="age_min" value="<?php echo esc_attr($age_min); ?>"
                           class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="age_max">حداکثر سن</label></th>
                <td><input type="number" name="age_max" id="age_max" value="<?php echo esc_attr($age_max); ?>"
                           class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="price">قیمت شهریه (تومان)</label></th>
                <td><input type="number" name="price" id="price" value="<?php echo esc_attr($price); ?>"
                           class="regular-text"/></td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_meta_fields($post_id)
    {
        if (!isset($_POST['sports_discipline_nonce']) || !wp_verify_nonce($_POST['sports_discipline_nonce'], basename(__FILE__))) {
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