<?php

namespace GymManagement\PostTypes;

class SportsDiscipline
{
    public function __construct()
    {
        add_action('init', array($this, 'register'));
    }

    public function register()
    {
        $labels = array(
            'name' => 'رشته‌های ورزشی',
            'singular_name' => 'رشته ورزشی',
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'),
            'show_in_menu' => true
        );
        register_post_type('sports_discipline', $args);
    }
}