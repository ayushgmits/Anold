<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STVideoController extends STBase
{

    public $module = 'videos';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/',
                '/' . $this->module,
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_videos'],
                    'permission_callback' => '__return_true'
                )
            );

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/(?P<video_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_video_details'],
                    'permission_callback' => '__return_true'
                )
            );
        });
    }

    public function streamit_rest_videos($request)
    {
        $data = stValidationToken($request);
        $user_id = null;
        $is_valid_token = false;
        if ($data['status'])
            $user_id = $data['user_id'];
        
            $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
            $is_valid_token = !empty($logged_in_devices);

        $video_array    = array();
        $parameters     = $request->get_params();
        $page           = $parameters['page'] ?? 1;
        $per_page       = $parameters['per_page'] ?? 10;
        $args           = [
            'post_type'         => 'video',
            'post_status'       => 'publish',
            'posts_per_page'    => $per_page,
            'paged'             => $page
        ];

        $wp_query   = new WP_Query($args);
        $videos     = $wp_query->posts ?? [];
        if ($videos && count($videos) > 0) {
            foreach ($videos as $video) {
                $video_array[] = streamit_video_details($video, $user_id);
            }
        }

        if (empty($video_array))
            return comman_custom_response([
                "status"    => true,
                "message"   => __("No videos found.", STA_TEXT_DOMAIN),
                "data"      => [],
                "is_valid_token" => $is_valid_token
            ]);

        return comman_custom_response([
            "status"    => true,
            "message"   => __("Video list.", STA_TEXT_DOMAIN),
            "data"      => $video_array,
            "is_valid_token" => $is_valid_token
        ]);
    }

    public function streamit_rest_video_details($request)
    {
        $data = stValidationToken($request);
        $user_id = null;
        if ($data['status'])
            $user_id = $data['user_id'];
            $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
            $is_valid_token = !empty($logged_in_devices);

        $parameters = $request->get_params();
        $response   = [];
        $video_id   = $parameters['video_id'];


        $response = streamit_video_details($video_id, $user_id, false);
        if (empty($response))
            return comman_custom_response([
                "status"    => true,
                "message"   => __("No details found.", STA_TEXT_DOMAIN),
                "data"      => (object) [],
                "is_valid_token" => $is_valid_token
            ]);
        user_posts_view_count($video_id);

         // Retrieve user meta 'my_stream_access'
        $user_meta = get_user_meta($user_id, 'my_stream_access', true);
        
        // Ensure it's an array, if it's stored as a string, convert it into an array
        if (!is_array($user_meta)) {
            $user_meta = json_decode($user_meta, true); // Assuming it's stored as a JSON string
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
        
        // Retrieve user's watch history
            $continue_watch = get_user_meta($user_id, "_watch_content", true);

            if (!empty($continue_watch)) {
                $watch_data = json_decode($continue_watch, true);

                // Check if the video exists in watch data
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

        // upcoming-videos
        $upcoming_videos = [];
        $arg = array(
            'post_type'         => 'video',
            'post_status'       => 'publish',
            'post__not_in'      => array($video_id),
            'posts_per_page'    => 4,
            'meta_key'          => 'name_upcoming',
            'meta_query'        => array(
                'key'       => 'name_upcoming',
                'value'     => 'yes',
                'compare'   => 'LIKE'
            )
        );
        $upcoming = new WP_Query($arg);
        $upcoming = $upcoming->posts ?? [];
        if ($upcoming && count($upcoming) > 0) {
            foreach ($upcoming as $video) {
                $upcoming_videos[] = streamit_video_details($video, $user_id);
            }
        }

        return comman_custom_response([
            "status"    => true,
            "message"   => __("Details found.", STA_TEXT_DOMAIN),
            "data"      => array(
                'details'           => $response,
                'upcoming_videos'   => $upcoming_videos,
                'meta_data'        => $custom_fields,
            ),
            "is_valid_token" => $is_valid_token
        ]);
    }
}
