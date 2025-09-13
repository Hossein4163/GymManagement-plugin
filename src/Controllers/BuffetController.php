<?php

namespace GymManagement\Controllers;

use GymManagement\PostTypes\BuffetProduct;

class BuffetController
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_buffet_menu'));
        add_action('init', array($this, 'register_buffet_sale_cpt')); // استفاده از پست تایپ به جای جدول
    }

    public function add_buffet_menu()
    {
        add_submenu_page(
            'my-gym-accounting', // منوی والد
            'مدیریت بوفه',
            'بوفه',
            'manage_options',
            'my-gym-buffet',
            array($this, 'render_buffet_page')
        );
    }

    public function register_buffet_sale_cpt()
    {
        $labels = array(
            'name' => 'فروش بوفه',
            'singular_name' => 'فروش بوفه',
        );
        $args = array(
            'labels' => $labels,
            'public' => false, // این پست‌ها نباید در سایت نمایش داده شوند
            'show_ui' => true,
            'show_in_menu' => 'my-gym-accounting', // نمایش در زیرمنوی حسابداری
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
}