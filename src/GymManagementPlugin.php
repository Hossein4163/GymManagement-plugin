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
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies()
    {
        new SportsDiscipline();
        new BuffetProduct();
        new MembershipController();
        new AccountingController();
        new BuffetController();
        new SmsController();
        new FrontendController();
        new NotificationController();
        new ReportController();
        new Expense();
    }

    private function define_hooks()
    {
        register_activation_hook(MY_GYM_PLUGIN_PATH . 'rame-gym-management.php', array($this, 'activate'));
        register_deactivation_hook(MY_GYM_PLUGIN_PATH . 'rame-gym-management.php', array($this, 'deactivate'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_main_menu'));
    }

    public function add_main_menu()
    {
        add_menu_page(
            'داشبورد Rame Gym',
            'Rame Gym',
            'manage_options',
            'rame-gym',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-bar',
            6
        );
        add_submenu_page('rame-gym', 'مدیریت اعضا', 'اعضا', 'manage_options', 'users.php', null, 1);
        add_submenu_page('rame-gym', 'مدیریت رشته‌ها', 'رشته‌ها', 'manage_options', 'edit.php?post_type=sports_discipline', null, 2);
        add_submenu_page('rame-gym', 'مدیریت بوفه', 'بوفه', 'manage_options', 'my-gym-buffet', null, 3);
        add_submenu_page('rame-gym', 'ارسال پیامک', 'پیامک', 'manage_options', 'my-gym-sms', null, 4);
        add_submenu_page('rame-gym', 'مدیریت مالی', 'حسابداری', 'manage_options', 'my-gym-accounting', null, 5);
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
        if (strpos($hook, 'rame-gym') !== false || strpos($hook, 'users.php') !== false || $hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.0');
            wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
        }
    }
}