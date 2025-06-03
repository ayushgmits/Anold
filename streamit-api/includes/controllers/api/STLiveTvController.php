<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STLiveTvController extends STBase
{

    public $module = 'live-tv';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                'channels/(?P<channel_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_channel_details'],
                    'permission_callback' => '__return_true'
                )
            );
        });
    }

    public function streamit_rest_channel_details($request)
    {
        $data       = stValidationToken($request);
        $user_id    = null;
    
        if ($data['status']) {
            $user_id = $data['user_id'];
        }
    
        $response   = [];
        $parameters = $request->get_params();
        $channel_id = $parameters['channel_id'] ?? null;
    
        if (empty($channel_id)) {
            error_log("Channel ID is missing.");
            return comman_custom_response([
                "status" => false,
                "message" => __("Channel ID is required.", STA_TEXT_DOMAIN),
                "data" => []
            ]);
        }
    
        $post_type = get_post_type($channel_id);
        $allowed_post_types = ['live_tv', 'channel'];
    
        if (!$post_type || !in_array($post_type, $allowed_post_types, true)) {
            error_log("Invalid or unsupported post type for Channel ID: " . $channel_id);
            return comman_custom_response([
                "status" => false,
                "message" => __("Invalid or unsupported channel type.", STA_TEXT_DOMAIN),
                "data" => []
            ]);
        }
    
        try {
            $response = streamit_live_tv_details($channel_id, $user_id, false);
        } catch (Exception $e) {
            error_log("Error fetching channel details: " . $e->getMessage());
            return comman_custom_response([
                "status" => false,
                "message" => __("Failed to fetch channel details.", STA_TEXT_DOMAIN),
                "data" => []
            ]);
        }
    
        if (empty($response) || !is_array($response)) {
            error_log("No details found for Channel ID: " . $channel_id);
            return comman_custom_response([
                "status" => true,
                "message" => __("No details found.", STA_TEXT_DOMAIN),
                "data" => []
            ]);
        }
    
        user_posts_view_count($channel_id);
    
        $user_meta = get_user_meta($user_id, 'my_stream_access', true);
    
        if (!is_array($user_meta)) {
            $user_meta = json_decode($user_meta, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error decoding user meta for user_id: " . $user_id);
                $user_meta = [];
            }
        }
    
        $can_access = false;
    
        if (is_array($user_meta)) {
            foreach ($user_meta as $stream) {
                if (isset($stream['id']) && $stream['id'] == $channel_id) {
                    $can_access = true;
                    break;
                }
            }
        }

        // Check if coins meta is 0 or blank, grant access
        $coins_meta = get_post_meta($episode_id, 'coins', true);
        if ($coins_meta = ' ') {
            $can_access = true;
        }
    
        $views = ($post_type === 'tv_show') 
            ? get_post_meta($channel_id, 'tv_show_views_count', true) 
            : get_post_meta($channel_id, 'post_views_count', true);
    
        $views = !empty($views) ? (int)$views : 0;
    
        $custom_fields = [
            'is_free'    => (int) get_post_meta($channel_id, 'is_free', true),  
            'can_access' => $can_access,
            'ads_count'  => (int) get_post_meta($channel_id, 'ads_count', true),
            'coins'      => (int) get_post_meta($channel_id, 'coins', true),   
            'views_count'=> $views 
        ];
    
        $genres = $response['genre'] ?? [];
        $recommended_channels = $this->recommended_channels_by_category(array_keys($genres), $user_id, [$channel_id]);
        $response['genre'] = array_values($genres);
    
        $continue_watch = get_user_meta($user_id, "_watch_content", true);
        $watched_duration = [
            'watchedTime'           => "0",
            'watchedTotalTime'      => "0",
            'watchedTimePercentage' => "0"
        ];

        if (!empty($continue_watch)) {
            $watch_data = json_decode($continue_watch, true);

            if (isset($watch_data[$channel_id])) {
                $watched_duration = [
                    'watchedTime'           => strval($watch_data[$channel_id]['watchedTime'] ?? "0"),
                    'watchedTotalTime'      => strval($watch_data[$channel_id]['watchedTotalTime'] ?? "0"),
                    'watchedTimePercentage' => strval($watch_data[$channel_id]['watchedTimePercentage'] ?? "0")
                ];
            }
        }
    
        $response = [
            "status"    => true,
            "message"   => __("Channel Details.", STA_TEXT_DOMAIN),
            'data'      => [
                'details'               => $response,
                'recommended_channels'  => $recommended_channels ?? [],
                'meta_data'             => $custom_fields,
                'watched_duration'      => $watched_duration
            ]
        ];
        
        // Retrieve logged-in devices and determine token validity
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);

        // Add the token validity info to the response
        $response["is_valid_token"] = $is_valid_token;

        return comman_custom_response($response);
    }
    
    function recommended_channels_by_category($categories, $user_id = null, $exclude = [])
    {
        if (count($categories) == 0) return [];
        $data = [];
        $args = [
            'post_type'         => 'live_tv',
            'posts_per_page'    => 10,
            'paged'             => 1,
            'post_status'       => 'publish',
            'post__not_in'      => $exclude,
            'tax_query'         => array(
                array(
                    'taxonomy'  => 'live_tv_cat',
                    'field'     => 'term_id',
                    'terms'     => $categories,
                )
            )
        ];

        $wp_query = new WP_Query($args);
        $posts = $wp_query->posts ?? [];

        if (!$posts || empty($wp_query))
            return [];

        foreach ($posts as $post) {
            $data[] = streamit_live_tv_details($post, $user_id);
        }

        return $data;
    }
}
