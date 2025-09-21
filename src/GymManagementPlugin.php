<?php

namespace GymManagement;

use GymManagement\Controllers\AccountingController;
use GymManagement\Controllers\BuffetController;
use GymManagement\Controllers\DashboardWidgetController;
use GymManagement\Controllers\FrontendController;
use GymManagement\Controllers\MembershipController;
use GymManagement\Controllers\PaymentController;
use GymManagement\Controllers\ReportController;
use GymManagement\Controllers\SettingsController;
use GymManagement\Controllers\SmsController;
use GymManagement\PostTypes\BuffetProduct;
use GymManagement\PostTypes\Expense;
use GymManagement\PostTypes\SportsDiscipline;

final class GymManagementPlugin
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
        $this->load_controllers();
    }

    private function define_hooks()
    {
        register_activation_hook(MY_GYM_PLUGIN_PATH . 'GymManagement-plugin.php', [Installer::class, 'run']);
        register_deactivation_hook(MY_GYM_PLUGIN_PATH . 'GymManagement-plugin.php', [Installer::class, 'deactivate']);

        add_action('init', [$this, 'load_post_types'], 0);
        add_action('init', [$this, 'load_textdomain'], 5);
        add_action('admin_menu', [$this, 'add_main_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
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
        new SettingsController();
        new FrontendController();
        new ReportController();
        new PaymentController();
        new DashboardWidgetController();
    }

    public function add_main_menu()
    {
        add_menu_page(
            __('داشبورد Rame Gym', 'rame-gym'),
            __('Rame Gym', 'rame-gym'),
            'manage_options',
            'rame-gym',
            [$this, 'render_dashboard_page'],
            'dashicons-analytics',
            6
        );
    }

    public function render_dashboard_page()
    {
        require_once MY_GYM_PLUGIN_PATH . 'views/dashboard-page.php';
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        // Only load assets on our plugin's pages to optimize performance.
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'rame-gym') === false && $screen->base !== 'users') {
            return;
        }

        wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', [], '2.0.0');
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true);
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery', 'select2-js', 'chart-js'], '2.0.0', true);

        wp_localize_script('my-gym-admin-script', 'my_gym_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security_nonce' => wp_create_nonce('my-gym-security-nonce')
        ]);
    }

    public function enqueue_frontend_assets()
    {
        wp_enqueue_style('my-gym-frontend-style', MY_GYM_PLUGIN_URL . 'assets/css/frontend-style.css', [], '2.0.0');
    }
}