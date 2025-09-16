<?php

namespace GymManagement\PostTypes;

class Expense
{
    public function __construct()
    {
        add_action('init', array($this, 'register_expense_cpt'));
        add_action('init', array($this, 'register_expense_taxonomy'));
    }

    public function register_expense_cpt()
    {
        $labels = array('name' => 'هزینه‌ها', 'singular_name' => 'هزینه');
        $args = array(
            'labels' => $labels,
            'public' => true,
            'supports' => array('title', 'editor'),
            'show_in_menu' => 'rame-gym'
        );
        register_post_type('expense', $args);
    }

    public function register_expense_taxonomy()
    {
        $labels = array('name' => 'دسته‌بندی هزینه‌ها');
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
        );
        register_taxonomy('expense_category', 'expense', $args);
    }
}