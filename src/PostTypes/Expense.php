<?php

namespace GymManagement\PostTypes;

class Expense
{
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => 'هزینه‌ها',
            'singular_name' => 'هزینه',
            'menu_name' => 'هزینه‌ها',
            'name_admin_bar' => 'هزینه',
            'add_new' => 'افزودن هزینه جدید',
            'add_new_item' => 'افزودن هزینه جدید',
            'new_item' => 'هزینه جدید',
            'edit_item' => 'ویرایش هزینه',
            'view_item' => 'نمایش هزینه',
            'all_items' => 'همه هزینه‌ها',
            'search_items' => 'جستجوی هزینه‌ها',
            'parent_item_colon' => 'هزینه والد:',
            'not_found' => 'هیچ هزینه‌ای یافت نشد',
            'not_found_in_trash' => 'هیچ هزینه‌ای در زباله‌دان یافت نشد'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            // مهم: اینجا باعث میشه زیرمنو زیر منوی اصلی rame-gym بیاد
            'show_in_menu' => 'rame-gym',
            'supports' => array('title', 'editor', 'custom-fields'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
        );

        register_post_type('expense', $args);
    }
}