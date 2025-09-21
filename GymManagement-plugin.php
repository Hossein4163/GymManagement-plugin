<?php
/**
 * Plugin Name:       Rame Gym Management
 * Description:       A comprehensive and refactored system for managing a sports club.
 * Version:           2.0.0
 * Author:            RameStudio (Refactored by God DD)
 * Author URI:        https://ramestudio.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rame-gym
 * Domain Path:       /languages
 */

// Block direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure the Composer autoloader exists.
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Optional: Add an admin notice to inform the user to run composer install.
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Rame Gym Management:</strong> نیازمندی‌های پلاگین (Composer) نصب نشده است. لطفاً دستور <code>composer install</code> را در پوشه پلاگین اجرا کنید.</p></div>';
    });
    return;
}
require_once __DIR__ . '/vendor/autoload.php';

// Define core plugin constants.
define('MY_GYM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MY_GYM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Instantiate the main plugin class to start the magic.
if (class_exists('GymManagement\\GymManagementPlugin')) {
    \GymManagement\GymManagementPlugin::getInstance();
}