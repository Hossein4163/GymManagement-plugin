<?php

namespace GymManagement\PostTypes;

class BuffetProduct
{
    public function __construct()
    {
        add_action('init', array($this, 'register'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post', array($this, 'save_custom_meta_fields'));
    }

    public function register()
    {
        $labels = array('name' => 'محصولات بوفه', 'singular_name' => 'محصول بوفه');
        $args = array(
            'labels' => $labels,
            'public' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_menu' => 'rame-gym'
        );
        register_post_type('buffet_product', $args);
    }

    public function add_custom_meta_boxes()
    {
        add_meta_box(
            'buffet_product_details',
            'جزئیات محصول',
            array($this, 'render_meta_box'),
            'buffet_product',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        $stock = get_post_meta($post->ID, 'stock', true);
        $price = get_post_meta($post->ID, 'price', true);
        wp_nonce_field('buffet_product_nonce', 'buffet_product_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="stock">موجودی</label></th>
                <td><input type="number" name="stock" id="stock" value="<?php echo esc_attr($stock); ?>"
                           class="regular-text" min="0"/></td>
            </tr>
            <tr>
                <th><label for="price">قیمت (تومان)</label></th>
                <td><input type="number" name="price" id="price" value="<?php echo esc_attr($price); ?>"
                           class="regular-text" min="0" step="0.01"/></td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_meta_fields($post_id)
    {
        if (!isset($_POST['buffet_product_nonce']) || !wp_verify_nonce($_POST['buffet_product_nonce'], 'buffet_product_nonce')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        if (isset($_POST['stock'])) {
            update_post_meta($post_id, 'stock', sanitize_text_field($_POST['stock']));
        }
        if (isset($_POST['price'])) {
            update_post_meta($post_id, 'price', sanitize_text_field($_POST['price']));
        }
    }
}