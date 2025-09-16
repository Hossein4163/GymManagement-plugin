<?php

namespace GymManagement\PostTypes;

class BuffetProduct
{
    public function __construct()
    {
        add_action('init', array($this, 'register'));
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
}