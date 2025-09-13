<?php
/**
 * Plugin Name:       Gym Management
 * Description:       A comprehensive accounting plugin for managing a sports club.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// لود کردن Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use GymManagement\GymManagementPlugin;

define('GYM_Management_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GYM_Management_PLUGIN_URL', plugin_dir_url(__FILE__));

if (class_exists('GymManagement\\GymManagementPlugin')) {
    GymManagementPlugin::getInstance();
}

add_action('admin_enqueue_scripts', 'gym_management_plugin_enqueue_assets');

function my_gym_plugin_enqueue_assets($hook)
{
    // فقط در صفحات مربوط به پلاگین، فایل‌ها را بارگذاری کن
    if (strpos($hook, 'my-gym') !== false || strpos($hook, 'users.php') !== false) {
        wp_enqueue_style('my-gym-admin-style', GYM_Management_PLUGIN_PATH . 'assets/css/admin-style.css', array(), '1.0.0');
        wp_enqueue_script('my-gym-admin-script', GYM_Management_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
    }
}