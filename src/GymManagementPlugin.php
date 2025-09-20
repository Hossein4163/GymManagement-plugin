<?php
// src/MyGymPlugin.php

namespace GymManagement;

use GymManagement\Controllers\AccountingController;
use GymManagement\Controllers\FrontendController;
use GymManagement\Controllers\MembershipController;
use GymManagement\Controllers\BuffetController;
use GymManagement\Controllers\NotificationController;
use GymManagement\Controllers\ReportController;
use GymManagement\Controllers\SmsController;
use GymManagement\PostTypes\Expense;
use GymManagement\PostTypes\SportsDiscipline;
use GymManagement\PostTypes\BuffetProduct;

class GymManagementPlugin
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->define_hooks();
    }

    private function define_hooks()
    {
        register_activation_hook(MY_GYM_PLUGIN_PATH . 'rame-gym-management.php', array($this, 'activate'));
        register_deactivation_hook(MY_GYM_PLUGIN_PATH . 'rame-gym-management.php', array($this, 'deactivate'));

        add_action('init', array($this, 'load_post_types'), 0);
        add_action('init', array($this, 'load_textdomain'), 5);

        add_action('admin_menu', array($this, 'add_main_menu'), 10);
        add_action('admin_menu', array($this, 'load_controllers'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('rame-gym', false, dirname(plugin_basename(MY_GYM_PLUGIN_PATH)) . '/languages/');
    }

    public function load_post_types()
    {
        new SportsDiscipline();
        new BuffetProduct();
        new Expense();
    }

    public function load_controllers()
    {
        new AccountingController();
        new MembershipController();
        new BuffetController();
        new SmsController();
        new FrontendController();
        new NotificationController();
        new ReportController();
    }

    public function add_main_menu()
    {
        add_menu_page(
            __('داشبورد Rame Gym', 'rame-gym'),
            __('Rame Gym', 'rame-gym'),
            'manage_options',
            'rame-gym',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-bar',
            6
        );
        add_submenu_page('rame-gym', __('مدیریت اعضا', 'rame-gym'), __('اعضا', 'rame-gym'), 'manage_options', 'users.php', null, 1);
        add_submenu_page('rame-gym', __('مدیریت رشته‌ها', 'rame-gym'), __('رشته‌ها', 'rame-gym'), 'manage_options', 'edit.php?post_type=sports_discipline', null, 2);
        add_submenu_page('rame-gym', __('مدیریت بوفه', 'rame-gym'), __('بوفه', 'rame-gym'), 'manage_options', 'my-gym-buffet', null, 3);
        add_submenu_page('rame-gym', __('ارسال پیامک', 'rame-gym'), __('پیامک', 'rame-gym'), 'manage_options', 'my-gym-sms', null, 4);
        add_submenu_page('rame-gym', __('مدیریت مالی', 'rame-gym'), __('حسابداری', 'rame-gym'), 'manage_options', 'my-gym-accounting', null, 5);
        add_submenu_page('rame-gym', __('مدیریت هزینه‌ها', 'rame-gym'), __('هزینه‌ها', 'rame-gym'), 'manage_options', 'edit.php?post_type=expense', null, 6);
    }

    public function render_dashboard_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/dashboard-page.php';
    }

    public function activate()
    {
        (new Controllers\AccountingController())->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'rame-gym') !== false || strpos($hook, 'users.php') !== false || $hook == 'post.php' || $hook == 'post-new.php' || $hook == 'edit.php' || $hook == 'edit-tags.php') {
            wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.2');
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
            wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'select2-js'), '1.0.2', true);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
            wp_localize_script('my-gym-admin-script', 'my_gym_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security_nonce' => wp_create_nonce('my-gym-security-nonce'),
                'is_rtl' => is_rtl(),
            ));
        }
    }
}