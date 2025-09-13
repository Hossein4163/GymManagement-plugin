<?php

namespace GymManagement;

use GymManagement\Controllers\AccountingController;
use GymManagement\Controllers\MembershipController;
use GymManagement\PostTypes\SportsDiscipline;
use GymManagement\PostTypes\BuffetProduct;
use GymManagement\Controllers\BuffetController;
use GymManagement\Controllers\SmsController;

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
    }

    public function activate()
    {
        (new Controllers\AccountingController())->create_tables();
    }

    public function deactivate()
    {
        // عملیات هنگام غیرفعال‌سازی پلاگین
    }
}