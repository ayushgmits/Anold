<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://https://iqonic.design
 * @since      1.0.0
 *
 * @package    Streamit_Api
 * @subpackage Streamit_Api/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Streamit_Api
 * @subpackage Streamit_Api/includes
 * @author     Iqonic Design <iqonic@gmail.com>
 */
class Streamit_Api
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Streamit_Api_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('STREAMIT_API_VERSION')) {
			$this->version = STREAMIT_API_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'streamit-api';

		$this->load_dependencies();
		$this->load_files();

		if (is_admin())
			$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Streamit_Api_Loader. Orchestrates the hooks of the plugin.
	 * - Streamit_Api_i18n. Defines internationalization functionality.
	 * - Streamit_Api_Admin. Defines all hooks for the admin area.
	 * - Streamit_Api_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-streamit-api-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-streamit-api-admin.php';

	}

	private function load_files(){

		//Functions Files
		require_once STREAMIT_API_DIR . 'includes/functions/st-helper-function.php';
		require_once STREAMIT_API_DIR . 'includes/functions/st-content-helper-function.php';

		//Class Files
		require_once STREAMIT_API_DIR . 'includes/classes/class-streamit-api-helper.php';
		require_once STREAMIT_API_DIR . 'includes/classes/class-streamit-api-cache-handler.php';

		//controller file
		require_once STREAMIT_API_DIR . 'includes/classes/controllers/class-streamit-api-route-controller.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		new Streamit_Api_Admin($this->plugin_name, $this->version);
	}


}
