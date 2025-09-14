<?php
/**
 * Plugin Name:       Rame Gym Accounting
 * Description:       A comprehensive accounting plugin for managing a sports club.
 * Version:           1.0.0
 * Author:            RameStudio
 * Author URI:        https://ramestudio.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use GymManagement\GymManagementPlugin;

define('MY_GYM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MY_GYM_PLUGIN_URL', plugin_dir_url(__FILE__));

if (class_exists('GymManagement\\GymManagementPlugin')) {
    GymManagementPlugin::getInstance();
}