<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

class St_Api_Helper
{
    /**
     * Constructor to initialize the JWT authentication response filter.
     */
    public function __construct()
    {
        // Add filter for JWT authentication response
        add_filter('jwt_auth_token_before_dispatch', [$this, 'jwtAuthenticationResponse'], 5, 2);
    }

    /**
     * Customize the JWT authentication response.
     *
     * @param array $data The data to be returned in the JWT response.
     * @param WP_User $user The authenticated user object.
     * @return array Modified JWT response data.
     */
    public function jwtAuthenticationResponse(array $data, WP_User $user)
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

		return $data;

    }
}

// Instantiate the St_Api_Helper class
new St_Api_Helper();
