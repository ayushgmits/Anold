<?php

/**
 * @wordpress-plugin
 * Plugin Name:       streamit-api
 * Plugin URI:        https://iqonic.design
 * Description:       Streamit api mobile plugin
 * Version:           9.3.0 
 * Author:            Iqonic Design
 * Author URI:        https://iqonic.design
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       streamit-api
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use Includes\baseClasses\STActivate;
use Includes\baseClasses\STDeactivate;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

defined('ABSPATH') or die('Something went wrong');
define('STA_TEXT_DOMAIN', "streamit-api");

// Require once the Composer Autoload
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
	require_once dirname(__FILE__) . '/vendor/autoload.php';
} else {
	die('Something went wrong');
}

if (!function_exists('get_plugin_data'))
	include_once ABSPATH . 'wp-admin/includes/plugin.php';


define('STREAMIT_API_VERSION', '9.3.0 ');

if (!defined('STREAMIT_API_DIR')) {
	define('STREAMIT_API_DIR', plugin_dir_path(__FILE__));
}

if (!defined('STREAMIT_API_DIR_URI')) {
	define('STREAMIT_API_DIR_URI', plugin_dir_url(__FILE__));
}


if (!defined('STREAMIT_API_NAMESPACE')) {
	define('STREAMIT_API_NAMESPACE', "streamit");
}

if (!defined('STREAMIT_API_PREFIX')) {
	define('STREAMIT_API_PREFIX', "iq_");
}

if (!defined('JWT_AUTH_SECRET_KEY')) {
	define('JWT_AUTH_SECRET_KEY', 'your-top-secrect-key');
}

if (!defined('JWT_AUTH_CORS_ENABLE')) {
	define('JWT_AUTH_CORS_ENABLE', true);
}

add_filter('onesignal_send_notification', 'st_onesignal_send_notification_filters', 10, 4);
function st_onesignal_send_notification_filters($fields, $new_status, $old_status, $post)
{
	unset($fields['url']);
	$fields['data'] = [
		"id" => (string) $post->ID,
		"post_type" => (string) $post->post_type,
	];
	$fields['included_segments'] = 'All';
	return $fields;
}
/**
 * The code that runs during plugin activation
 */
register_activation_hook(__FILE__, [STActivate::class, 'activate']);

/**
 * The code that runs during plugin deactivation
 */
register_deactivation_hook(__FILE__, [STDeactivate::class, 'init']);


(new STActivate)->init();
