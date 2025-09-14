<?php

namespace GymManagement;

use GymManagement\Controllers\AccountingController;
use GymManagement\Controllers\MembershipController;
use GymManagement\Controllers\BuffetController;
use GymManagement\Controllers\SmsController;
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
    }

    private function define_hooks()
    {
        register_activation_hook(MY_GYM_PLUGIN_PATH . 'my-gym-plugin.php', array($this, 'activate'));
        register_deactivation_hook(MY_GYM_PLUGIN_PATH . 'my-gym-plugin.php', array($this, 'deactivate'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
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
        if (strpos($hook, 'my-gym') !== false || strpos($hook, 'users.php') !== false || $hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_style('my-gym-admin-style', MY_GYM_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.0');
            wp_enqueue_script('my-gym-admin-script', MY_GYM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
        }
    }
}