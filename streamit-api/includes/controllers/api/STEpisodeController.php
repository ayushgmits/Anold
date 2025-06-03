<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STEpisodeController extends STBase
{

    public $base    = 'tv-show/season';
    public $module  = 'episodes';
    public $nameSpace;
    public $view = 'views';

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->base . '/' . $this->module,
                '/(?P<episode_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_episode_details'],
                    'permission_callback' => '__return_true'
                )
            );
        });
    }

    public function streamit_rest_episode_details($request) {
        $data = stValidationToken($request);
        $user_id = null;
    
        if (!empty($data['token'])) { // Check only if token is passed
            if ($data['status']) {
                $user_id = $data['user_id'];
                $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
                $is_valid_token = !empty($logged_in_devices);
            } else {
                $is_valid_token = false;
            }
        }
        
        if ($data['status']) {
            $user_id = $data['user_id'];
        }
    
        $parameters = $request->get_params();
        $episode_id = isset($parameters['episode_id']) ? (int)$parameters['episode_id'] : 0;
    
        $response = streamit_episode_details($episode_id, $user_id, false);
    
        if (empty($response)) {
            return comman_custom_response([
                "status"  => false,
                "message" => __("No data found.", STA_TEXT_DOMAIN),
                "data"    => (object)[]
            ]);
        }
    
        // Retrieve user meta 'my_stream_access'
        $user_meta = get_user_meta($user_id, 'my_stream_access', true);
        if (!is_array($user_meta)) {
            $user_meta = json_decode($user_meta, true);
        }
        // Default to no access
        $can_access = false;
        // Check if episode ID exists in 'my_stream_access' data
        if (is_array($user_meta)) {
            foreach ($user_meta as $stream) {
                if (isset($stream['id']) && $stream['id'] == $episode_id) {
                    $can_access = true;
                    break;
                }
            }
        }

        // Check if the episode is unlocked for the user
        if (!$can_access) {
            $unlock_status = $this->is_post_unlocked_for_user($episode_id, $user_id);

            if ($unlock_status !== false) { // Ensure we don't misinterpret 0 as false
                if ($unlock_status === 0) {
                    $can_access = true; // Episode is unlocked
                } else {
                    $remaining_time = (int) $unlock_status; // Episode is locked, store remaining time
                }
            }
        }

        // Check if coins meta is 0 or blank, grant access
        $coins_meta = get_post_meta($episode_id, 'coins', true);
        if (empty($coins_meta)) {
            $can_access = true;
        }
        
        // // Check if the user is marked as 'pro'
        $is_pro = get_user_meta($user_id, 'is_pro', true);
        // If the user is pro, grant access to all episodes
        if ($is_pro) {
            $can_access = true;
        }
    
        // --- NEW: Get the views count based on post type ---
        $post_type = get_post_type($episode_id);
        if ($post_type === 'tv_show') {
            $views = get_post_meta($episode_id, 'tv_show_views_count', true);
        } else {
            $views = get_post_meta($episode_id, 'post_views_count', true);
        }
        if (empty($views)) {
            $views = 0;
        }
        // -------------------------------------------------------
    
        // Retrieve and cast custom meta fields
        $custom_fields = [
            'is_free'    => (int) get_post_meta($episode_id, 'is_free', true),
            'can_access' => $can_access,
            'ads_count'  => (int) get_post_meta($episode_id, 'ads_count', true),
            'coins'      => (int) get_post_meta($episode_id, 'coins', true),
            'views_count'=> (int) $views
        ];
    
        if (is_array($response)) {
            $response = array_merge($response, $custom_fields);
        } else {
            $response = [
                'episode_details' => $response,
                'custom_fields'   => $custom_fields
            ];
        }
        // Retrieve user's watch history
        $continue_watch = get_user_meta($user_id, "_watch_content", true);

        if (!empty($continue_watch)) {
            $watch_data = json_decode($continue_watch, true);

            // Debug: Check if the episode exists in watch data
            if (isset($watch_data[$episode_id])) {
                $response["watched_duration"] = $watch_data[$episode_id];
            } else {
                $response["watched_duration"] = [
                    "watchedTime" => "0",
                    "watchedTotalTime" => "0",
                    "watchedTimePercentage" => "0"
                ]; 
            }
        } else {
            $response["watched_duration"] = [
                "watchedTime" => "0",
                "watchedTotalTime" => "0",
                "watchedTimePercentage" => "0"
            ]; 
        }

        // user_posts_view_countS($episode_id);
    
        // Retrieve logged-in devices for the user and determine token validity.
        // $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        // $is_valid_token = !empty($logged_in_devices);
        
        return comman_custom_response([
            "status"  => true,
            "message" => __("Episode Details", STA_TEXT_DOMAIN),
            "data"    => $response,
            "is_valid_token" => $is_valid_token
        ]);
    }
    
    
    public function is_post_unlocked_for_user($post_id, $user_id)
    {
        $current_time = time();
        $user_unlock_time = get_user_meta($user_id, 'unlock_time_' . $post_id, true);
        
        // If no unlock time is set, return false (episode is locked)
        if (empty($user_unlock_time)) {
            return false;
        }
    
        // Convert unlock time to integer (if stored as string)
        $user_unlock_time = intval($user_unlock_time);
    
        // If the episode is unlocked, return 0
        if ($current_time >= $user_unlock_time) {
            return 0;  // Fully unlocked
        }
    
        // Otherwise, return the remaining time
        return $user_unlock_time - $current_time;
    }

}
