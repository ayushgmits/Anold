<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_Query;
use WP_REST_Server;

class STUserController extends STBase
{

	public $module = 'user';

	public $nameSpace;

	function __construct()
	{

		$this->nameSpace = STREAMIT_API_NAMESPACE;

		add_action('rest_api_init', function () {
			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/registration',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [$this, 'createUser'],
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/validate-token',
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => [$this, 'validateToken'],
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/profile',
				[
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [$this, 'streamit_rest_view_profile'],
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => [$this, 'streamit_rest_update_profile'],
						'permission_callback' => '__return_true'
					)
				]
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/change-password',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [$this, 'streamit_rest_change_password'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/forgot-password',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [$this, 'streamit_rest_forgot_password'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/delete-account',
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [$this, 'rest_delete_user_account'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/nonce',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [$this, 'streamit_rest_nonce'],
					'permission_callback' => '__return_true'
				)
			);


			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/devices',
				[
					[
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [$this, 'getDevices'],
						'permission_callback' => '__return_true',
					],
					[
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => [$this, 'addDevice'],
						'permission_callback' => '__return_true',
					],
					[
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => [$this, 'removeDevice'],
						'permission_callback' => '__return_true',
					]
				]
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/watchlist',
				[
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [$this, 'streamit_rest_watchlist'],
						'permission_callback' => '__return_true'
					),
					array(
						'methods'             => 'POST, PUT, DELETE',
						'callback'            => [$this, 'streamit_rest_manage_watchlist'],
						'permission_callback' => '__return_true'
					)
				]
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/continue-watch',
				[
					[
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [$this, 'streamit_rest_continue_watch'],
						'permission_callback' => '__return_true'
					],
					[
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => [$this, 'streamit_rest_continue_watch_add'],
						'permission_callback' => '__return_true'
					],
					[
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => [$this, 'streamit_rest_continue_watch_remove'],
						'permission_callback' => '__return_true'
					]
				]
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/like-dislike',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [$this, 'streamit_rest_like_dislike'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/player-ids',
				[
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => [$this, 'streamit_add_player_id'],
						'permission_callback' => '__return_true'
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => [$this, 'streamit_remove_player_id'],
						'permission_callback' => '__return_true'
					)
				]
			);
		});
	}

	public function createUser($request)
	{

		$reqArr = $request->get_params();

		$validation = stValidateRequest([
			'user_login' 	=> 'required',
			'first_name' 	=> 'required',
			'last_name' 	=> 'required',
			'user_email' 	=> 'email|required',
			'user_pass' 	=> 'required',
		], $reqArr);

		if (count($validation)) {
			return comman_custom_response([
				"status"	=> false,
				"message"	=> $validation[0],
			]);
		}

		$res = wp_insert_user($reqArr);

		if (is_wp_error($res)) {
			return comman_custom_response([
				"status"	=> false,
				"message"	=> array_values($res->errors)[0][0] ?? __("Internal server error", STA_TEXT_DOMAIN),
			]);
		}

		$free_level = array_keys(st_get_pmp_free_plans());
		if (count($free_level) > 0)
			pmpro_changeMembershipLevel($free_level[0], $res);

		wp_update_user([
			'ID' 			=> $res,
			'first_name' 	=> $reqArr['first_name'],
			'last_name' 	=> $reqArr['last_name']
		]);

		return comman_custom_response([
			"status"	=> true,
			"message"	=> __('User registered succesfully.', STA_TEXT_DOMAIN),
		]);
	}

	public function streamit_rest_view_profile($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$user_id = $data['user_id'];
		$user = get_userdata($user_id);
		$img = get_user_meta($user_id, 'streamit_profile_image', true);

		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		$response['first_name']     = $user->first_name;
		$response['last_name']      = $user->last_name;
		$response['user_id']        = $user->ID;
		$response['username']       = $user->user_login;
		$response['user_email']     = $user->user_email;
		$response['plan']           = streamit_user_plans($user->ID);
		$response['profile_image']  = $img;
		$response['is_valid_token'] = $is_valid_token;

		return comman_custom_response([
			"status" => true,
			"message" => __("User profile.", STA_TEXT_DOMAIN),
			"data" => $response
		]);
	}

	public function streamit_rest_update_profile($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');

		$reqArr = $request->get_params();

		$userid = $data['user_id'];

		wp_update_user([
			'ID' => $userid,
			'first_name' 	=> $reqArr['first_name'],
			'last_name' 	=> $reqArr['last_name'],
			'display_name' 	=> $reqArr['first_name'] . ' ' . $reqArr['last_name']
		]);

		$users = get_userdata($userid);
		$logged_in_devices = get_user_meta($userid, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		$response = [
			'ID'			=> $users->ID,
			'first_name' 	=> $users->first_name,
			'last_name' 	=> $users->last_name,
			'user_email' 	=> $users->user_email,
			'user_login' 	=> $users->user_login,
			'display_name'  => $users->display_name,
			'is_valid_token' => $is_valid_token
		];

		if (isset($_FILES['profile_image']) && $_FILES['profile_image'] != null) {
			$profile_image = media_handle_upload('profile_image', 0);
			update_user_meta($userid, 'streamit_profile_image', wp_get_attachment_url($profile_image));
		}
		$response['profile_image'] 			= get_user_meta($userid, 'streamit_profile_image', true);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('Profile has been updated succesfully', STA_TEXT_DOMAIN),
			"data" 		=> $response
		]);
	}

	public function streamit_rest_change_password($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$user_id = $data['user_id'];
		$parameters = $request->get_params();
		$userdata 	= get_user_by('ID', $user_id);

		if ($userdata == null)
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __('User not found.', STA_TEXT_DOMAIN),
			]);

		if (!wp_check_password($parameters['old_password'], $userdata->data->user_pass))
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __("Old password is invalid.", STA_TEXT_DOMAIN),
			]);

		wp_set_password($parameters['new_password'], $userdata->ID);
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		return comman_custom_response([
			"status"	=> true,
			"message"	=> __("Password has been changed successfully.", STA_TEXT_DOMAIN),
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_forgot_password($request)
	{
		$parameters = $request->get_params();
		$email 		= $parameters['email'] ?? "";
		$user 		= get_user_by('email', $email);

		if (!$user)
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __("User not found with this email address.", STA_TEXT_DOMAIN),
			]);

		$title 		= 'New Password';
		$password 	= stGenerateString();
		$message 	= '<label><b>Hello,</b></label>';
		$message 	.= '<p>Your recently requested to reset your password. Here is the new password for your App</p>';
		$message 	.= '<p><b>New Password </b> : ' . $password . '</p>';
		$message 	.= '<p>Thanks,</p>';

		$headers 	= "MIME-Version: 1.0" . "\r\n";
		$headers 	.= "Content-type:text/html;charset=UTF-8" . "\r\n";

		if (wp_mail($email, $title, $message, $headers)) {
			wp_set_password($password, $user->ID);
			$message = __('Password has been sent successfully to your email address.');
		} elseif (mail($email, $title, $message, $headers)) {
			wp_set_password($password, $user->ID);
			$message = __('Password has been sent successfully to your email address.');
		} else {
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __("Something went wrong ! Email not sent. Please try agin.", STA_TEXT_DOMAIN),
			]);
		}

		return comman_custom_response([
			"status"	=> true,
			"message"	=> $message,
		]);
	}

	public function rest_delete_user_account($request)
	{

		$data = stValidationToken($request);
		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		require_once(ABSPATH . 'wp-admin/includes/user.php');
		$user_id = $data['user_id'];
		$user = wp_delete_user($user_id, true);
		if ($user)
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __('User Deleted Successfully.', STA_TEXT_DOMAIN),
			]);

		return comman_custom_response([
			"status"	=> false,
			"message"	=> __('User not Deleted.', STA_TEXT_DOMAIN),
		]);
	}

	public function validateToken($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, 401);

		$device_id = $request->get_param("device_id") ?? 0;
		if (!$device_id)
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __('Valid token.', STA_TEXT_DOMAIN),
			]);


		$user_id = $data['user_id'];
		$loggedin_devices = (array) get_user_meta($user_id, "streamit_loggedin_devices", true);

		if (!array_key_exists($device_id, $loggedin_devices))
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __('Token has been expired.', STA_TEXT_DOMAIN),
			]);


		return comman_custom_response([
			"status"	=> true,
			"message"	=> __('Valid token.', STA_TEXT_DOMAIN),
		]);
	}

	public function streamit_rest_nonce($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		// $nonce_for = $request->get_param("nonce_for") ?? 0;
		$action = 'wc_store_api';
		$nonce = wp_create_nonce($action);

		return comman_custom_response([
			"status"	=> true,
			"message" 	=> __("Store api nonce.", STA_TEXT_DOMAIN),
			"data" 		=> ["nonce" => $nonce]
		]);
	}

	public function getDevices($request)
	{
		$data = stValidationToken($request);
		if (!$data['status'])
			return comman_custom_response($data, 401);

		$user_id = $data['user_id'];
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		if ($logged_in_devices)
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __("List of logged-in devices.", STA_TEXT_DOMAIN),
				"is_valid_token" => $is_valid_token,
				"data" 		=> $logged_in_devices
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __("No devices found.", STA_TEXT_DOMAIN),
			"is_valid_token" => $is_valid_token,
			"data" 		=> []
		]);
	}
	
	public function addDevice($request)
	{
		$data = stValidationToken($request);
		if (!$data['status'])
			return comman_custom_response($data, 401);

		$user_id 		= $data['user_id'];
		$paramaters 	= $request->get_params();
		$device_id		= sanitize_text_field($paramaters["device_id"]);
		$device_model	= sanitize_text_field($paramaters["device_model"]);
		$token			= sanitize_text_field($paramaters["login_token"]);

		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		if (!$logged_in_devices)
			$logged_in_devices = [];

		$logged_in_devices[$device_id] = [
			"device_id"		=> $device_id,
			"device_model"	=> $device_model,
			"login_time"	=> current_time('mysql'),
			"token"			=> $token
		];
		update_user_meta($user_id, "streamit_loggedin_devices", $logged_in_devices);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __("List of logged-in devices.", STA_TEXT_DOMAIN),
			"data" 		=> $logged_in_devices
		]);
	}

	public function removeDevice($request)
{
	$data = stValidationToken($request);
	if (!$data['status'])
		return comman_custom_response($data, 401);

	$user_id = $data['user_id'];
	$paramaters = $request->get_params();
	$device_id = $paramaters["device_id"] ?? false;

	// If no specific device_id provided, clear all devices
	if (empty($device_id)) {
		update_user_meta($user_id, "streamit_loggedin_devices", false);
		return comman_custom_response([
			"status" => true,
			"message" => __("All devices are removed.", STA_TEXT_DOMAIN),
			"data" => []
		]);
	}

	$device_id = sanitize_text_field($device_id);

	// Get the logged-in devices list
	$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);

	// If the list exists and this device_id exists, remove it
	if (!empty($logged_in_devices) && array_key_exists($device_id, $logged_in_devices)) {
		unset($logged_in_devices[$device_id]);
		update_user_meta($user_id, "streamit_loggedin_devices", $logged_in_devices);
	}

	// If no devices remain after removal, clear the meta
	if (empty($logged_in_devices)) {
		update_user_meta($user_id, "streamit_loggedin_devices", false);
		return comman_custom_response([
			"status" => true,
			"message" => __("No devices found.", STA_TEXT_DOMAIN),
			"data" => [],
			"is_valid_token" => false
		]);
	}

	// Return the updated list of devices
	return comman_custom_response([
		"status" => true,
		"message" => __("Device successfully removed. Updated list of logged-in devices.", STA_TEXT_DOMAIN),
		"data" => $logged_in_devices,
		"is_valid_token" => true
	]);
}

	public function streamit_rest_watchlist($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$user_id = $data['user_id'];
		$userdata = get_user_by('ID', $user_id);
		if ($userdata == null)
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __('User not found.', STA_TEXT_DOMAIN),
				"data"		=> []
			]);


		$response = [];
		$parameters = $request->get_params();
		$posts_per_page = $parameters["posts_per_page"];
		$page 			= $parameters["page"];
		$post_watchlist = get_user_meta($user_id, '_user_watchlist', true);

		if (!empty($post_watchlist)) {
			$watchlist_array = explode(', ', $post_watchlist);
			$args = array(
				'post_type'         => ['movie', 'tv_show', 'episode', 'video'],
				'post_status'       => 'publish',
				'post__in'          => $watchlist_array,
				'posts_per_page'	=> $posts_per_page,
				'paged'				=> $page
			);

			$wp_query = new WP_Query($args);
			$wp_query = $wp_query->posts ?? [];
			if ($wp_query && count($wp_query) > 0) {
				foreach ($wp_query as $post) {
					$response[] = streamit_movie_video_detail_helper($post, $user_id);
				}
			}
		}

		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
	
		if (empty($response))
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __('No data found.', STA_TEXT_DOMAIN),
				"data"		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status"	=> true,
			"message"	=> __('Watchlist result.', STA_TEXT_DOMAIN),
			"data"		=> $response,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_manage_watchlist($request)
	{

		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$user_id = $data['user_id'];
		$post_id 	= $request->get_param('post_id') ?? 0;
		$post 		= get_post($post_id);

		if ($post == null)
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __('No data found.', STA_TEXT_DOMAIN),
				"data"		=> (object) []
			]);

		$watch_list 		= st_is_item_in_watchlist($post_id, $user_id);
		$post_watchlist 	= get_user_meta($user_id, '_user_watchlist', true);

		if (!$watch_list) {
			$newvalue = !empty($post_watchlist) ? $post_watchlist . ', ' . $post_id : $post_id;
			update_user_meta($user_id, '_user_watchlist', $newvalue, $post_watchlist);

			$isAdded 	= true;
			$message  	= __("Added to watchlist.", STA_TEXT_DOMAIN);
		} else {
			$watchlist_array	= explode(', ', $post_watchlist);
			$key 				= array_search($post_id, $watchlist_array);

			unset($watchlist_array[$key]);
			update_user_meta($user_id, '_user_watchlist', implode(", ", $watchlist_array), $post_watchlist);

			$isAdded 	= false;
			$message  	= __("Removed from watchlist.", STA_TEXT_DOMAIN);
		}

		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		return comman_custom_response([
			"status"	=> true,
			"message"	=> $message,
			"data"		=> array('is_added' => $isAdded),
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_continue_watch($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$userid = $data['user_id'];
		$response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

		$logged_in_devices = get_user_meta($userid, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		
		if (empty($response))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __('No data found.', STA_TEXT_DOMAIN),
				"data" 		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('Continue watch Result.', STA_TEXT_DOMAIN),
			"data" 		=> $response,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_continue_watch_add($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$userid = $data['user_id'];
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
	
		$parameters 			= $request->get_params();
		$postID 				= $parameters['post_id'];
		$watchedTime 			= floor($parameters['watched_time']);
		$watchedTotalTime 		= floor($parameters['watched_total_time']);
		$watchedTimePercentage 	= floor(($watchedTime * 100) / $watchedTotalTime);

		$watchedMeta = (array) json_decode(get_user_meta($userid, '_watch_content', true));

		$watchedMeta[$postID] = array(
			'watchedTime' 			=> $watchedTime,
			'watchedTotalTime' 		=> $watchedTotalTime,
			'watchedTimePercentage' => $watchedTimePercentage
		);

		$temp = [$postID => $watchedMeta[$postID]];
		unset($watchedMeta[$postID]);
		$watchedMeta = $temp + $watchedMeta;

		update_user_meta($userid, '_watch_content', json_encode($watchedMeta));

		$response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

		if (empty($response))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __('No data found.', STA_TEXT_DOMAIN),
				"data" 		=> []
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('Continue watch Result.', STA_TEXT_DOMAIN),
			"data" 		=> $response,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_continue_watch_remove($request)
	{
		$parameters = $request->get_params();

		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$userid = $data['user_id'];
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		$postID	= $parameters['post_id'] ?? false;

		if (empty($postID)) {
			update_user_meta($userid, "_watch_content", false);
			return comman_custom_response([]);
		}

		$watchedMeta = (array) json_decode(get_user_meta($userid, '_watch_content', true));
		if ($watchedMeta && key_exists($postID, $watchedMeta))
			unset($watchedMeta[$postID]);

		update_user_meta($userid, '_watch_content', json_encode($watchedMeta));

		$response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

		if (empty($response))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __('No data found.', STA_TEXT_DOMAIN),
				"data" 		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('Continue watch Result.', STA_TEXT_DOMAIN),
			"data" 		=> $response,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_like_dislike($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		global $wpdb;
		$user_id = $data['user_id'];
		$table_name 		= $wpdb->prefix . 'ulike';
		$table_ulike_meta 	= $wpdb->prefix . 'ulike_meta';

		$parameters = $request->get_params();
		$post_id 	= $parameters['post_id'];
		$user_id 	= $data['user_id'];
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		$post = get_post($post_id);

		if ($post == null)
			return comman_custom_response([
				"status"	=> false,
				"message"	=> __('Post not found.', STA_TEXT_DOMAIN),
				"data"		=> (object) []
			]);

		$post_status 		= $wpdb->get_row("SELECT * FROM {$table_name} WHERE `user_id`=" . $user_id . " AND `post_id` =" . $post_id . "", OBJECT);
		$ulike_post_meta 	= $wpdb->get_row("SELECT * FROM {$table_ulike_meta} WHERE `item_id`=" . $post_id . " AND `meta_key`='count_distinct_like'", OBJECT);
		$message = null;
		$isLiked = null;

		if ($ulike_post_meta == null) {
			$wpdb->insert($table_ulike_meta, array(
				"item_id"		=> $post_id,
				"meta_group" 	=> 'post',
				"meta_key"      => 'count_distinct_like',
				"meta_value"	=> 0
			));
		}

		if ($post_status != null) {

			if ($post_status->status == 'like') {
				$wpdb->query("UPDATE $table_ulike_meta SET `meta_value`= meta_value - 1 WHERE `item_id` =" . $post_id . " AND  `meta_key`='count_distinct_like' AND `meta_group` = 'post' ");
				$wpdb->query("UPDATE $table_name SET `status`='unlike' WHERE `user_id`=" . $user_id . " AND `post_id` =" . $post_id . "");
				$isLiked = false;
				$message = __("You unliked this.", STA_TEXT_DOMAIN);
			}

			if ($post_status->status == 'unlike') {
				$wpdb->query("UPDATE $table_ulike_meta SET `meta_value`= meta_value + 1 WHERE `item_id` =" . $post_id . " AND  `meta_key`='count_distinct_like' AND `meta_group` = 'post' ");
				$wpdb->query("UPDATE $table_name SET `status`='like' WHERE `user_id`=" . $user_id . " AND `post_id` =" . $post_id . "");
				$isLiked = true;
				$message = __("You liked this.", STA_TEXT_DOMAIN);
			}
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'user_id'   => $user_id,
					'post_id'   => $post_id,
					'date_time'	=> wp_date('Y-m-d H:i:s'),
					'status'    => 'like'
				)
			);
			$wpdb->query("UPDATE $table_ulike_meta SET `meta_value`= meta_value + 1 WHERE `item_id` =" . $post_id . " AND  `meta_key`='count_distinct_like' AND `meta_group` = 'post' ");
			$isLiked = true;
			$message = __("You liked this.", STA_TEXT_DOMAIN);
		}

		return comman_custom_response([
			"status"	=> true,
			"message"	=> $message,
			"data"		=> array('is_added' => $isLiked),
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_add_player_id($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$current_user_id = $data['user_id'];
		$logged_in_devices = get_user_meta($current_user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		$parameters = $request->get_params();
		$parameters = stRecursiveSanitizeTextFields($parameters);
		$player_id = $parameters['player_id'];

		$player_ids = [];
			// FireBase Notification Token
			$firebase_tokens = [];
			if ($user_firebase_tokens = get_user_meta($current_user_id, 'streamit_firebase_tokens', true)) {
				$firebase_tokens = $user_firebase_tokens;
			}
			if ($request->has_param('firebase_token') && !empty($request->get_param('firebase_token'))) {
				array_push($firebase_tokens, $request->get_param('firebase_token'));
			}
			update_user_meta($current_user_id, 'streamit_firebase_tokens', array_unique($firebase_tokens));

			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __("Player ID's.", STA_TEXT_DOMAIN),
				"data" 		=> $firebase_tokens,
				"is_valid_token" => $is_valid_token
			], 200);
	}

	public function streamit_remove_player_id($request)
	{
		$data = stValidationToken($request);

		if (!$data['status'])
			return comman_custom_response($data, $data['status_code']);

		$current_user_id = $data['user_id'];
		$logged_in_devices = get_user_meta($current_user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		$parameters = $request->get_params();
		$parameters = stRecursiveSanitizeTextFields($parameters);
		$player_id = $parameters['player_id'];

		// if (!empty($player_id)) {
			// FireBase Notification Token remove
			$firebase_tokens = get_user_meta($current_user_id, 'streamit_firebase_tokens', true);
			$firebase_tokens = json_encode($firebase_tokens);

			if (is_array($firebase_tokens) && in_array($player_id, $firebase_tokens)) {
				$index  = array_search($player_id, $firebase_tokens);
				unset($firebase_tokens[$index]);
				update_user_meta($current_user_id, "streamit_firebase_tokens", json_encode(array_filter($firebase_tokens)));
				return comman_custom_response([
					"status" 	=> true,
					"message" 	=> __("Player ID's", STA_TEXT_DOMAIN),
					"data" 		=> $firebase_tokens,
					"is_valid_token" => $is_valid_token
				], 200);
			}
		// }
		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __("Player Id not present | records not available.", STA_TEXT_DOMAIN),
			"data" 		=> [],
			"is_valid_token" => $is_valid_token
		], 200);
	}
}
