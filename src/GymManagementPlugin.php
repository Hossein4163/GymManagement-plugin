<?php

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
        register_activation_hook(MY_GYM_PLUGIN_PATH . 'GymManagement-plugin.php', array($this, 'activate'));
        register_deactivation_hook(MY_GYM_PLUGIN_PATH . 'GymManagement-plugin.php', array($this, 'deactivate'));

        // Load post types first, before everything else
        add_action('init', array($this, 'load_post_types'), 0);
        add_action('init', array($this, 'load_textdomain'), 5);

        // Load menus after post types are registered
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

        // Make sure the post type exists before adding submenu
        if (post_type_exists('sports_discipline')) {
            add_submenu_page('rame-gym', __('مدیریت رشته‌ها', 'rame-gym'), __('رشته‌ها', 'rame-gym'), 'manage_options', 'edit.php?post_type=sports_discipline', null, 2);
        }

        // Add buffet page directly here
        add_submenu_page('rame-gym', __('مدیریت بوفه', 'rame-gym'), __('بوفه', 'rame-gym'), 'manage_options', 'my-gym-buffet', array($this, 'render_buffet_page'), 3);

        // Add SMS page directly here
        add_submenu_page('rame-gym', __('ارسال پیامک', 'rame-gym'), __('پیامک', 'rame-gym'), 'manage_options', 'my-gym-sms', array($this, 'render_sms_page'), 4);

        // Add accounting page directly here
        add_submenu_page('rame-gym', __('مدیریت مالی', 'rame-gym'), __('حسابداری', 'rame-gym'), 'manage_options', 'my-gym-accounting', array($this, 'render_accounting_page'), 5);

//        if (post_type_exists('expense')) {
//            add_submenu_page('rame-gym', __('مدیریت هزینه‌ها', 'rame-gym'), __('هزینه‌ها', 'rame-gym'), 'manage_options', 'edit.php?post_type=expense', null, 6);
//        }
    }

    public function render_dashboard_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/dashboard-page.php';
    }

    public function render_buffet_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }

        // Get the buffet controller instance to handle the page
        $buffet_controller = new BuffetController();
        $buffet_controller->render_buffet_page();
    }

    public function render_sms_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }

        // Get the SMS controller instance to handle the page
        $sms_controller = new SmsController();
        $sms_controller->render_sms_page();
    }

    public function render_accounting_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.'));
        }

        // Get the accounting controller instance to handle the page
        $accounting_controller = new AccountingController();
        $accounting_controller->render_accounting_page();
    }

    public function activate()
    {
        // Load post types before creating tables
        $this->load_post_types();

        (new AccountingController())->create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function enqueue_assets($hook)
    {
        $screen = get_current_screen();
        $is_gym_page = strpos($hook, 'rame-gym') !== false || $hook === 'users.php' || $hook === 'post.php' || $hook === 'post-new.php' || $hook === 'edit.php' || (isset($screen->base) && $screen->base === 'rame-gym_page_my-gym-buffet');

        if (!$is_gym_page) {
            return;
        }

        // استایل‌ها
        wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.4');

        // اسکریپت‌ها با وابستگی به jQuery
        wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.4', true);

        // Chart.js با فال‌بک محلی
        $chart_js_url = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js';
        $chart_js_local = MY_GYM_PLUGIN_URL . 'assets/js/chart.js';
        wp_register_script('chart-js', $chart_js_url, array('jquery'), '4.4.0', true);
        wp_enqueue_script('chart-js');
        wp_script_add_data('chart-js', 'async', true);

        // Select2 با فال‌بک محلی
        $select2_css_url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
        $select2_css_local = MY_GYM_PLUGIN_URL . 'assets/css/select2.min.css';
        wp_register_style('select2', $select2_css_url, array(), '4.1.0');
        wp_enqueue_style('select2');
        wp_style_add_data('select2', 'alt', $select2_css_local);

        $select2_js_url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        $select2_js_local = MY_GYM_PLUGIN_URL . 'assets/js/select2.min.js';
        wp_register_script('select2', $select2_js_url, array('jquery'), '4.1.0', true);
        wp_enqueue_script('select2');
        wp_script_add_data('select2', 'alt', $select2_js_local);

        // متغیرهای JS
        wp_localize_script('my-gym-admin-script', 'my_gym_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security_nonce' => wp_create_nonce('my-gym-security-nonce')
        ));

        // دیباگ در محیط توسعه
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), time(), true);
        }
    }

//    public function enqueue_assets($hook)
//    {
//        $screen = get_current_screen();
//        $is_gym_page = strpos($hook, 'rame-gym') !== false || $hook === 'users.php' || $hook === 'post.php' || $hook === 'post-new.php' || $hook === 'edit.php' || (isset($screen->base) && $screen->base === 'rame-gym_page_my-gym-buffet');
//
//        if (!$is_gym_page) {
//            return;
//        }
//
//        // استایل‌ها
//        wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.4');
//
//        // اسکریپت‌ها با وابستگی به jQuery
//        wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.4', true);
//
//        // Chart.js با فال‌بک محلی
//        $chart_js_url = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js';
//        $chart_js_local = MY_GYM_PLUGIN_URL . 'assets/js/chart.js';
//        wp_register_script('chart-js', $chart_js_url, array('jquery'), '4.4.0', true);
//        wp_enqueue_script('chart-js');
//        wp_script_add_data('chart-js', 'async', true); // بارگذاری غیرهمزمان برای عملکرد بهتر
//
//        // Select2 با فال‌بک محلی
//        $select2_css_url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
//        $select2_css_local = MY_GYM_PLUGIN_URL . 'assets/css/select2.min.css';
//        wp_register_style('select2', $select2_css_url, array(), '4.1.0');
//        wp_enqueue_style('select2');
//        wp_style_add_data('select2', 'alt', $select2_css_local); // فال‌بک محلی
//
//        $select2_js_url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
//        $select2_js_local = MY_GYM_PLUGIN_URL . 'assets/js/select2.min.js';
//        wp_register_script('select2', $select2_js_url, array('jquery'), '4.1.0', true);
//        wp_enqueue_script('select2');
//        wp_script_add_data('select2', 'alt', $select2_js_local); // فال‌بک محلی
//
//        // متغیرهای JS
//        wp_localize_script('my-gym-admin-script', 'my_gym_vars', array(
//            'ajax_url' => admin_url('admin-ajax.php'),
//            'security_nonce' => wp_create_nonce('my-gym-security-nonce')
//        ));
//
//        // دیباگ در محیط توسعه
//        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
//            wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), time(), true); // برای جلوگیری از cache
//        }
//    }
}