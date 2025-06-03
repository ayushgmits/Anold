<?php

namespace Includes\baseClasses;

use Includes\CPT\iqonicCPTLiveTV;
use WP_Error;

class STActivate extends STBase
{

	public static function activate()
	{
		$require_plugins = [
			'jwt-authentication-for-wp-rest-api',
			'masvideos',
			'wp-ulike',
			'advanced-custom-fields',
			'paid-memberships-pro',
			'woocommerce',
			'pmpro-woocommerce'
		];
		(new STGetDependency($require_plugins))->getPlugin();
	}

	public function init()
	{
		// register option group
		register_setting('app_options_group', 'st_app_options');
		// app_options
		add_action('admin_init', [$this, 'add_app_options']);
		add_action("rest_api_init", function () {
			global $st_app_options;
			$st_app_options = get_option("st_app_options");
		});

		if (isset($_REQUEST['page']) && (strpos($_REQUEST['page'], "streamit-app-configuration") !== false || strpos($_REQUEST['page'], "app-options") !== false)) {
			// Enqueue Admin-side assets...
			add_action('admin_enqueue_scripts', array($this, 'enqueueStyles'));
			add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
		}

		// API handle
		(new STApiHandler())->init();
		// migrate pms to pmp
		(new STPmp)->init();
		// notificaions
		if (is_admin()) (new STNotifications());
		// Action to add option in the sidebar...
		add_action('admin_menu', array($this, 'adminMenu'));
		// register custom post types
		(new iqonicCPTLiveTV());

		// Action to change authentication api response ...
		add_filter('jwt_auth_token_before_dispatch', array($this, 'jwtAuthenticationResponse'), 10, 2);

		// Provided filter for REST request after callbacks
		add_filter("rest_request_after_callbacks", function ($response, $handler, $request) {
			$reqArr = $request->get_params();

			if (is_array($handler['callback']) && $handler['callback'][1] == "generate_token" && isset($reqArr['player_id']) && isset($reqArr['username'])) {
				$user = get_user_by('email', $reqArr['username'] ?? '');
				$player_ids = [];
				if ($firebase_player_ids = get_user_meta($user->ID, STREAMIT_API_PREFIX . 'firebase_player_ids', true)) {
					$player_ids = $firebase_player_ids;
				}
				if (isset($reqArr['player_id']) && !empty($reqArr['player_id'])) {
					array_push($player_ids, $reqArr['player_id']);
				}
				update_user_meta($user->ID, STREAMIT_API_PREFIX . 'firebase_player_ids', array_unique($player_ids));
			}
			return $response;
		}, 10, 3);
	}

	public function add_app_options()
	{
		$page = $_GET["page"] ?? false;
		if (!in_array($page, ["app-options", "pmpro-membershiplevels"])) return;

		global $st_app_options;
		$st_app_options = get_option("st_app_options");

		$general_class = "";
		$comment_class = "d-none";
		$device_limit_class = "d-none";
		$st_pmp_class = "d-none";
		if (isset($_GET["settings"]) && !empty(trim($_GET["settings"]))) {
			if ($_GET['settings'] === "comment") {
				$comment_class = "";
				$general_class = "d-none";
			} else if ($_GET['settings'] === "device-limit") {
				$device_limit_class = "";
				$general_class = "d-none";
			} else if ($_GET['settings'] === "st-pmp") {
				$st_pmp_class = "";
				$general_class = "d-none";
			}
		}
		get_general_settings($st_app_options, $general_class);
		get_comment_settings($st_app_options, $comment_class);
		get_device_limit_settings($st_app_options, $device_limit_class);
		get_st_pmp_settings($st_app_options, $st_pmp_class);
	}

	public function adminMenu()
	{
		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		if (in_array('administrator', $roles)) {
			add_menu_page(
				__('App Options', STA_TEXT_DOMAIN),
				__('App Options', STA_TEXT_DOMAIN),
				'read',
				'streamit-app-configuration',
				[$this, 'adminDashboard'],
				$this->plugin_url . 'assets/images/sidebar-icon.png',
				4
			);

			add_submenu_page(
				"streamit-app-configuration",
				__('App Content', STA_TEXT_DOMAIN),
				__('App Content', STA_TEXT_DOMAIN),
				'read',
				'streamit-app-configuration',
				[$this, 'adminDashboard'],
				$this->plugin_url . 'assets/images/sidebar-icon.png',
			);

			// app settings
			add_submenu_page(
				"streamit-app-configuration",
				__("App Settings", STA_TEXT_DOMAIN),
				__("Settings", STA_TEXT_DOMAIN),
				"manage_options",
				"app-options",
				[$this, "app_options_page"]
			);
		}
	}

	public function adminDashboard()
	{
		include(STREAMIT_API_DIR . 'resources/views/st_admin_panel.php');
	}

	public function app_options_page()
	{
		include(STREAMIT_API_DIR . 'resources/views/st_options_page.php');
	}

	public function adminDashboardHome()
	{
		include(STREAMIT_API_DIR . 'resources/views/st_admin_movie.php');
	}

	public function adminDashboardMovie()
	{
		include(STREAMIT_API_DIR . 'resources/views/st_admin_movie.php');
	}

	public function adminDashboardTVShow()
	{
		include(STREAMIT_API_DIR . 'resources/views/st_admin_tv_show.php');
	}

	public function enqueueStyles()
	{
		wp_enqueue_style('st_bootstrap_css', STREAMIT_API_DIR_URI . 'assets/css/bootstrap.min.css');
		// wp_enqueue_style( 'st_app_min_style', STREAMIT_API_DIR_URI . 'assets/css/app.min.css' );
		wp_enqueue_style('st_font_awesome', STREAMIT_API_DIR_URI . 'assets/css/font-awesome.min.css');
		wp_enqueue_style('st_bootstrap_select', STREAMIT_API_DIR_URI . 'admin/css/bootstrap-select.css');
		wp_enqueue_style('st_custom', STREAMIT_API_DIR_URI . 'assets/css/custom.css');
		wp_enqueue_style('st_admin_panel_css', STREAMIT_API_DIR_URI . 'admin/css/streamit-api-admin.css');
	}

	public function enqueueScripts()
	{
		wp_enqueue_script('st_bootstrap_js', STREAMIT_API_DIR_URI . 'assets/js/bootstrap.min.js', ['jquery'], false, true);
		wp_enqueue_script('st_js_bundle', STREAMIT_API_DIR_URI . 'assets/js/app.min.js', ['jquery'], false, true);
		wp_enqueue_script('st_js_popper', STREAMIT_API_DIR_URI . 'admin/js/popper.min.js', ['jquery'], false, false);
		wp_enqueue_script('st_bootstrap_select', STREAMIT_API_DIR_URI . 'admin/js/bootstrap-select.js', ['jquery'], false, true);
		wp_enqueue_script('st_sweetalert', STREAMIT_API_DIR_URI . 'admin/js/sweetalert.min.js', ['jquery'], false, true);
		wp_enqueue_script('st_custom', STREAMIT_API_DIR_URI . 'assets/js/custom.js', ['jquery'], false, true);
		wp_localize_script('st_custom', 'st_localize', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('get_st_admin_settings')
		));

		wp_localize_script('st_js_bundle', 'request_data', array(
			'ajaxurl'         => admin_url('admin-ajax.php'),
			'nonce'           => wp_create_nonce('ajax_post'),
			'streamitPluginURL' => STREAMIT_API_DIR_URI,
		));

		wp_enqueue_script('st_js_bundle');
	}

	public function jwtAuthenticationResponse($data, $user)
	{
		$user_id 	= $user->ID;
		$img       	= get_user_meta($user_id, 'streamit_profile_image', true);
		$user_info 	= get_userdata($user_id);

		$data['first_name'] 			= $user_info->first_name;
		$data['last_name']  			= $user_info->last_name;
		$data['user_id']    			= $user_id;
		$data['username']   			= $user->user_login;
		$data['user_email'] 			= $user->user_email;
		$data['profile_image'] 			= $img;


		$subscription_level = streamit_user_plans($user_id);
		// restrict login if limit exceeded
		$limit_settings = st_get_limit_login_settings();
		if (!empty($subscription_level) && $limit_settings && $limit_settings["is_enable"] === "yes") {
			$limit = $subscription_level->current_plan_login_limit ?? 0;
			if (!$limit)
				$limit = $subscription_level->default_login_limit ?? 0;


			$loggedin_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
			if ($loggedin_devices &&  $limit <= count($loggedin_devices))
				return new WP_Error(
					'streamit_login_limit_exceeded',
					__('Account login limit exceeded.', "streamit-api"),
					[
						'status' => 422,
					]
				);
		}
		$data['plan'] = $subscription_level;

		return $data;
	}
}
