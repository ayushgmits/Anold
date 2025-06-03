<?php
/**
 * Plugin Name: Iqonic Stream Handler
 * Description: A WordPress plugin to manage episodes/videos with custom fields: 
 * Version: 1.0.0
 * Author: Iqonic Design
 * Author URI: https://iqonic.design

 * Text Domain: iqonic-stream-handler
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ISH_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ISH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('STREAM_HANDLER', 'iqonic-stream-handler');
// Include main class
require_once ISH_PLUGIN_PATH . 'includes/class-ish-main.php';

// Initialize the plugin
function ish_initialize_plugin() {
    new ISH_Main();
}
add_action('plugins_loaded', 'ish_initialize_plugin');
?>