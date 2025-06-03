<?php

class St_User_Route_Callback
{
    /**
     * Create a new user.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function create_user(WP_REST_Request $request)
    {
        $parameters = $request->get_params();

        $user_login = $parameters['user_login'];
        $user_email = $parameters['user_email'];
        $first_name = $parameters['first_name'];
        $last_name  = $parameters['last_name'];
        $user_pass  = $parameters['user_pass'];

        $errors = [];

        // Username validation.
        $valid_format = array('-', '_');
        if (username_exists($user_login)) {
            $errors[] = esc_html__('Username already exists.', 'streamit-api');
        } elseif (!ctype_alnum(str_replace($valid_format, '', $user_login))) {
            $errors[] = esc_html__('Username can only contain letters, numbers, "_", and "-".', 'streamit-api');
        }

        // Email validation.
        if (email_exists($user_email)) {
            $errors[] = esc_html__('Email already exists.', 'streamit-api');
        }

        // Password validation.
        if (empty($user_pass) || strlen($user_pass) < 6) {
            $errors[] = esc_html__('Password must be at least 6 characters long.', 'streamit-api');
        }

        // If errors exist, return them.
        if (!empty($errors)) {
            return st_comman_custom_response([
                'status'  => false,
                'message' => esc_html__('Registration failed.', 'streamit-api'),
                'errors'  => $errors,
            ], 400);
        }

        // User data array.
        $user_data = array(
            'user_login'   => $user_login,
            'user_pass'    => $user_pass,
            'user_email'   => $user_email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
        );

        // Attempt to create the user.
        $user_id = wp_insert_user($user_data);

        // Check if user creation was successful.
        if (is_wp_error($user_id)) {
            return st_comman_custom_response([
                'status'  => false,
                'message' => esc_html__('Registration failed.', 'streamit-api'),
                'errors'  => $user_id->get_error_messages(),
            ], 500);
        }

        // If successful.
        return st_comman_custom_response([
            'status'  => true,
            'message' => esc_html__('User registered successfully.', 'streamit-api'),
            'user_id' => $user_id,
        ], 201);
    }

    /**
     * Validate Token.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function validate_token(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, 401);

        $device_id = $request->get_param('device_id') ?? 0;
        if (!$device_id)
            return st_comman_custom_response([
                'status'    => true,
                'message'    => esc_html__('Valid token.', 'streamit-api'),
            ]);


        $user_id = $data['user_id'];
        $loggedin_devices = (array) get_user_meta($user_id, 'streamit_loggedin_devices', true);

        if (!array_key_exists($device_id, $loggedin_devices))
            return st_comman_custom_response([
                'status'    => false,
                'message'    => esc_html__('Token has been expired.', 'streamit-api'),
            ]);


        return st_comman_custom_response([
            'status'    => true,
            'message'    => esc_html__('Valid token.', 'streamit-api'),
        ]);
    }

    /**
     * View user profile.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function view_profile(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status']) {
            return st_comman_custom_response($data, $data['status_code']);
        }

        $user_id = $data['user_id'];
        $user    = get_userdata($user_id);
        $img     = get_user_meta($user_id, 'streamit_profile_image', true);

        $response = [
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'user_id'      => $user->ID,
            'username'     => $user->user_login,
            'user_email'   => $user->user_email,
            'profile_image' => $img,
        ];

        return st_comman_custom_response([
            'status'  => true,
            'message' => esc_html__('User profile.', 'streamit-api'),
            'data'    => $response,
        ]);
    }

    /**
     * Update user profile.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function update_profile(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status']) {
            return st_comman_custom_response($data, $data['status_code']);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $reqArr = $request->get_params();
        $userid = $data['user_id'];

        wp_update_user([
            'ID' => $userid,
            'first_name'    => $reqArr['first_name'],
            'last_name'     => $reqArr['last_name'],
            'display_name'  => $reqArr['first_name'] . ' ' . $reqArr['last_name'],
        ]);

        $users = get_userdata($userid);

        $response = [
            'ID'            => $users->ID,
            'first_name'    => $users->first_name,
            'last_name'     => $users->last_name,
            'user_email'    => $users->user_email,
            'user_login'    => $users->user_login,
            'display_name'  => $users->display_name,
        ];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image'] != null) {
            $profile_image = media_handle_upload('profile_image', 0);
            update_user_meta($userid, 'streamit_profile_image', wp_get_attachment_url($profile_image));
        }

        $response['profile_image'] = get_user_meta($userid, 'streamit_profile_image', true);

        return st_comman_custom_response([
            'status'  => true,
            'message' => esc_html__('Profile has been updated successfully', 'streamit-api'),
            'data'    => $response,
        ]);
    }

    /**
     * Change user account password.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function change_password(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];
        $parameters = $request->get_params();
        $userdata     = get_user_by('ID', $user_id);

        if (!$userdata)
            return st_comman_custom_response([
                'status'    => false,
                'message'    => esc_html__('User not found.', 'streamit-api'),
            ]);

        if (!wp_check_password($parameters['old_password'], $userdata->data->user_pass))
            return st_comman_custom_response([
                'status'    => false,
                'message'    => esc_html__('Old password is invalid.', 'streamit-api'),
            ]);

        wp_set_password($parameters['new_password'], $userdata->ID);
        return st_comman_custom_response([
            'status'    => true,
            'message'    => esc_html__('Password has been changed successfully.', 'streamit-api'),
        ]);
    }

    /**
     * Forgot Password.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function forgot_password(WP_REST_Request $request)
    {
        $parameters = $request->get_params();
        $email         = $parameters['email'] ?? "";
        $user         = get_user_by('email', $email);

        if (!$user)
            return st_comman_custom_response([
                "status"    => false,
                "message"    => __("User not found with this email address.", 'streamit-api'),
            ]);

        $title         = 'New Password';
        $password     = st_string_generator();
        $message     = '<label><b>Hello,</b></label>';
        $message     .= '<p>Your recently requested to reset your password. Here is the new password for your App</p>';
        $message     .= '<p><b>New Password </b> : ' . $password . '</p>';
        $message     .= '<p>Thanks,</p>';

        $headers     = "MIME-Version: 1.0" . "\r\n";
        $headers     .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        if (wp_mail($email, $title, $message, $headers)) {
            wp_set_password($password, $user->ID);
            $message = esc_html__('Password has been sent successfully to your email address.');
        } elseif (mail($email, $title, $message, $headers)) {
            wp_set_password($password, $user->ID);
            $message = esc_html__('Password has been sent successfully to your email address.');
        } else {
            return st_comman_custom_response([
                "status"    => false,
                "message"    => esc_html__("Something went wrong ! Email not sent. Please try agin.", 'streamit-api'),
            ]);
        }

        return st_comman_custom_response([
            "status"    => true,
            "message"    => $message,
        ]);
    }

    /**
     * Delete User Account.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function rest_delete_user_account(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $user_id = $data['user_id'];
        $user = wp_delete_user($user_id, true);
        if ($user)
            return st_comman_custom_response([
                "status"    => true,
                "message"   => esc_html__('User Deleted Successfully.', 'streamit-api'),
            ]);

        return st_comman_custom_response([
            "status"    => false,
            "message"   => esc_html__('User not Deleted.', 'streamit-api'),
        ]);
    }

    /**
     * Rest Nonce.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function rest_nonce(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        // $nonce_for = $request->get_param("nonce_for") ?? 0;
        $action = 'wc_store_api';
        $nonce = wp_create_nonce($action);

        return st_comman_custom_response([
            "status"    => true,
            "message"     => esc_html__('Store api nonce.', 'streamit-api'),
            "data"         => ['nonce' => $nonce]
        ]);
    }

    /**
     * Get User Devices.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function get_devices(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, 401);

        $user_id = $data['user_id'];

        $logged_in_devices = get_user_meta($user_id, 'streamit_loggedin_devices', true);
        if ($logged_in_devices)
            return st_comman_custom_response([
                "status"     => true,
                "message"     => esc_html__('List of logged-in devices.', 'streamit-api'),
                "data"         => $logged_in_devices
            ]);

        return st_comman_custom_response([
            "status"     => true,
            "message"     => esc_html__('No devices found.', 'streamit-api'),
            "data"         => []
        ]);
    }

    /**
     * Add User Devices.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function add_device(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, 401);

        $user_id         = $data['user_id'];
        $paramaters     = $request->get_params();
        $device_id        = $paramaters['device_id'];
        $device_model    = $paramaters['device_model'];
        $token            = $paramaters['login_token'];

        $logged_in_devices = get_user_meta($user_id, 'streamit_loggedin_devices', true);
        if (!$logged_in_devices)
            $logged_in_devices = [];

        $logged_in_devices[$device_id] = [
            'device_id'        => $device_id,
            'device_mdel'    => $device_model,
            'login_time'    => current_time('mysql'),
            'token'            => $token
        ];
        update_user_meta($user_id, 'streamit_loggedin_devices', $logged_in_devices);

        return st_comman_custom_response([
            "status"     => true,
            "message"     => esc_html__('List of logged-in devices.', 'streamit-api'),
            "data"         => $logged_in_devices
        ]);
    }

    /**
     * Remove User Devices.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function remove_device(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, 401);

        $user_id        = $data['user_id'];
        $paramaters     = $request->get_params();
        $device_id      = $paramaters["device_id"] ?? false;

        if (empty($device_id)) {
            update_user_meta($user_id, 'streamit_loggedin_devices', false);
            return st_comman_custom_response([
                "status"     => true,
                "message"    => esc_html__('All devices are removed.', 'streamit-api'),
                "data"       => []
            ]);
        }

        $device_id = sanitize_text_field($paramaters['device_id']);

        $logged_in_devices = get_user_meta($user_id, 'streamit_loggedin_devices', true);
        if ($logged_in_devices && key_exists($device_id, $logged_in_devices))
            unset($logged_in_devices[$device_id]);

        update_user_meta($user_id, 'streamit_loggedin_devices', $logged_in_devices);

        if (empty($logged_in_devices)) {
            update_user_meta($user_id, 'streamit_loggedin_devices', false);
            return st_comman_custom_response([
                "status"     => true,
                "message"    => esc_html__('No devices found.', 'streamit-api'),
                "data"       => []
            ]);
        }

        return st_comman_custom_response([
            "status"     => true,
            "message"    => esc_html__('List of logged-in devices.', 'streamit-api'),
            "data"       => $logged_in_devices
        ]);
    }

    /**
     * Get User WatchList.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function get_watchlist(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];
        $userdata = get_user_by('ID', $user_id);
        if ($userdata == null)
            return st_comman_custom_response([
                "status"    => false,
                "message"    => esc_html__('User not found.', 'streamit-api'),
                "data"        => []
            ]);


        $response = [];
        $parameters = $request->get_params();
        $posts_per_page = $parameters["posts_per_page"];
        $page             = $parameters["page"];
        $post_watchlist = get_user_meta($user_id, '_user_watchlist', true);

        if (!empty($post_watchlist)) {
            $watchlist_array = explode(', ', $post_watchlist);
            $args = array(
                'post_type'         => ['movie', 'tv_show', 'episode', 'video'],
                'post_status'       => 'publish',
                'post__in'          => $watchlist_array,
                'posts_per_page'    => $posts_per_page,
                'paged'                => $page
            );

            $wp_query = new WP_Query($args);
            $wp_query = $wp_query->posts ?? [];
            if ($wp_query && count($wp_query) > 0) {
                foreach ($wp_query as $post) {
                    $response[] = ''; //streamit_movie_video_detail_helper($post, $user_id);
                }
            }
        }

        if (empty($response))
            return st_comman_custom_response([
                "status"    => true,
                "message"    => esc_html__('No data found.', 'streamit-api'),
                "data"        => []
            ]);

        return st_comman_custom_response([
            "status"    => true,
            "message"    => esc_html__('Watchlist result.', 'streamit-api'),
            "data"        => $response
        ]);
    }

    /**
     * Manage User WatchList.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function manage_watchlist(WP_REST_Request $request)
    {

        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];
        $post_id     = $request->get_param('post_id') ?? 0;
        $post         = get_post($post_id);

        if ($post == null)
            return st_comman_custom_response([
                "status"    => true,
                "message"    => esc_html__('No data found.', 'streamit-api'),
                "data"        => (object) []
            ]);

        $watch_list         = ''; //st_is_item_in_watchlist($post_id, $user_id);
        $post_watchlist     = get_user_meta($user_id, '_user_watchlist', true);

        if (!$watch_list) {
            $newvalue = !empty($post_watchlist) ? $post_watchlist . ', ' . $post_id : $post_id;
            update_user_meta($user_id, '_user_watchlist', $newvalue, $post_watchlist);

            $isAdded     = true;
            $message      = esc_html__('Added to watchlist.', 'streamit-api');
        } else {
            $watchlist_array    = explode(', ', $post_watchlist);
            $key                 = array_search($post_id, $watchlist_array);

            unset($watchlist_array[$key]);
            update_user_meta($user_id, '_user_watchlist', implode(", ", $watchlist_array), $post_watchlist);

            $isAdded     = false;
            $message      = esc_html__('Removed from watchlist.', 'streamit-api');
        }

        return st_comman_custom_response([
            "status"    => true,
            "message"    => $message,
            "data"        => array('is_added' => $isAdded)
        ]);
    }

    /**
     * Get User continue watch list.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function get_continue_watch(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $userid = $data['user_id'];
        $response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

        if (empty($response))
            return st_comman_custom_response([
                "status"     => true,
                "message"     => esc_html__('No data found.', 'streamit-api'),
                "data"         => []
            ]);

        return st_comman_custom_response([
            "status"     => true,
            "message"     => esc_html__('Continue watch Result.', 'streamit-api'),
            "data"         => $response
        ]);
    }

    /**
     * Add User continue watch list.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function add_continue_watch(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $userid = $data['user_id'];
        $parameters             = $request->get_params();
        $postID                 = $parameters['post_id'];
        $watchedTime            = floor($parameters['watched_time']);
        $watchedTotalTime       = floor($parameters['watched_total_time']);
        $watchedTimePercentage  = floor(($watchedTime * 100) / $watchedTotalTime);

        $watchedMeta = (array) json_decode(get_user_meta($userid, '_watch_content', true));

        $watchedMeta[$postID] = array(
            'watchedTime'           => $watchedTime,
            'watchedTotalTime'      => $watchedTotalTime,
            'watchedTimePercentage' => $watchedTimePercentage
        );

        $temp = [$postID => $watchedMeta[$postID]];
        unset($watchedMeta[$postID]);
        $watchedMeta = $temp + $watchedMeta;

        update_user_meta($userid, '_watch_content', json_encode($watchedMeta));

        $response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

        if (empty($response))
            return st_comman_custom_response([
                'status'     => true,
                'message'    => esc_html__('No data found.', 'streamit-api'),
                'data'       => []
            ]);

        return st_comman_custom_response([
            'status'     => true,
            'message'    => esc_html__('Continue watch Result.', 'streamit-api'),
            'data'       => $response
        ]);
    }

    /**
     * Remove User continue watch list.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function remove_continue_watch(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $userid = $data['user_id'];
        $parameters = $request->get_params();
        $postID = $parameters['post_id'] ?? false;

        if (empty($postID)) {
            update_user_meta($userid, '_watch_content', false);
            return st_comman_custom_response([]);
        }

        $watchedMeta = (array) json_decode(get_user_meta($userid, '_watch_content', true));
        if ($watchedMeta && key_exists($postID, $watchedMeta))
            unset($watchedMeta[$postID]);

        update_user_meta($userid, '_watch_content', json_encode($watchedMeta));

        $response = (array) json_decode(get_user_meta($userid, '_watch_content', true));

        if (empty($response))
            return st_comman_custom_response([
                "status"     => true,
                "message"    => esc_html__('No data found.', 'streamit-api'),
                "data"       => []
            ]);

        return st_comman_custom_response([
            "status"     => true,
            "message"    => esc_html__('Continue watch Result.', 'streamit-api'),
            "data"       => $response
        ]);
    }


    /**
     * Add Player Id.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function add_player_id(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $current_user_id = $data['user_id'];
        $parameters = $request->get_params();
        $parameters = st_sanitize_recursive_text_fields($parameters);
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

        return st_comman_custom_response([
            "status"     => true,
            "message"     => esc_html__('Player ID`s.', 'streamit-api'),
            "data"         => $firebase_tokens
        ], 200);
    }

    /**
     * Remove Player Id.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function remove_player_id($request)
	{
		$data = st_token_validation($request);

		if (!$data['status'])
			return st_comman_custom_response($data, $data['status_code']);

		$current_user_id = $data['user_id'];
		$parameters = $request->get_params();
		$parameters = st_sanitize_recursive_text_fields($parameters);
		$player_id = $parameters['player_id'];

		if (!empty($player_id)) {
			$firebase_tokens = get_user_meta($current_user_id, 'streamit_firebase_tokens', true);
			$firebase_tokens = json_encode($firebase_tokens);

			if (is_array($firebase_tokens) && in_array($player_id, $firebase_tokens)) {
				$index  = array_search($player_id, $firebase_tokens);
				unset($firebase_tokens[$index]);
				update_user_meta($current_user_id, "streamit_firebase_tokens", json_encode(array_filter($firebase_tokens)));
				return st_comman_custom_response([
					'status' 	=> true,
					'message' 	=> esc_html__('Player ID`s', 'streamit-api'),
					'data' 		=> $firebase_tokens
				], 200);
			}
		}

		return st_comman_custom_response([
			'status' 	=> true,
			'message' 	=> esc_html__('Player Id not present | records not available.', 'streamit-api'),
			'data' 		=> []
		], 200);
	}
}
