<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_Query;
use WP_REST_Server;

class STPlayListController extends STBase
{

    public $module = 'playlists';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/(?P<post_type>movie_playlist|tv_show_playlist|video_playlist)',
                [
                    array(
                        'methods'             => WP_REST_Server::READABLE,
                        'callback'            => [$this, 'streamit_rest_playlist'],
                        'permission_callback' => '__return_true'
                    ),
                    array(
                        'methods'             => WP_REST_Server::EDITABLE,
                        'callback'            => [$this, 'streamit_rest_create_playlist'],
                        'permission_callback' => '__return_true'
                    ),
                    array(
                        'methods'             => WP_REST_Server::DELETABLE,
                        'callback'            => [$this, 'streamit_rest_delete_playlist'],
                        'permission_callback' => '__return_true'
                    )
                ]
            );

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/(?P<post_type>movie_playlist|tv_show_playlist|video_playlist)/(?P<playlist_id>\d+)',
                [
                    array(
                        'methods'             => WP_REST_Server::READABLE,
                        'callback'            => [$this, 'streamit_rest_playlist_items'],
                        'permission_callback' => '__return_true'
                    ),
                    array(
                        'methods'             => WP_REST_Server::EDITABLE,
                        'callback'            => [$this, 'streamit_rest_playlist_add_items'],
                        'permission_callback' => '__return_true'
                    ),
                    array(
                        'methods'             => WP_REST_Server::DELETABLE,
                        'callback'            => [$this, 'streamit_rest_playlist_remove_items'],
                        'permission_callback' => '__return_true'
                    )
                ]

            );
        });
    }

    public function streamit_rest_playlist($request)
    {

        $data = stValidationToken($request);
        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $user_id    = $data['user_id'];
        $parameters = $request->get_params();
        $post_id    = $parameters["post_id"] ?? 0;

        $post_type  = $parameters['post_type'] ?? false;
        if (!$post_type || !in_array($post_type, ["movie_playlist", "tv_show_playlist", "video_playlist"]))
            return comman_custom_response([
                "status"    => true,
                "message"   => __('Playlists not found.', STA_TEXT_DOMAIN),
                "data"      => []
            ]);
        $playlists = streamit_get_current_user_playlists($post_type, ["user_id" => $user_id, "post_id" => $post_id]);
        if (!$playlists)
            return comman_custom_response([
                "status"    => true,
                "message"   => __('Playlists not found.', STA_TEXT_DOMAIN),
                "data"      => []
            ]);

            // Retrieve logged-in devices to determine token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);

        return comman_custom_response([
            "status"    => true,
            "message"   => __('Playlists.', STA_TEXT_DOMAIN),
            "data"      => $playlists,
            "is_valid_token" => $is_valid_token
        ]);
        
    }

        public function streamit_rest_create_playlist($request)
    {
        $data = stValidationToken($request);
        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $parameters = $request->get_params();

        $post_type = $parameters['post_type'] ?? false;

        if (!$post_type || !in_array($post_type, ["movie_playlist", "tv_show_playlist", "video_playlist"]))
            return comman_custom_response([
                "status"  => false,
                "message" => __('Playlist type not found.', STA_TEXT_DOMAIN)
            ]);

        $args = [
            'post_title'   => $parameters['title'] ?? '',
            'post_type'    => $post_type,
            'post_status'  => 'publish'
        ];

        // Retrieve logged-in devices to determine token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);
    
        if (empty($parameters['id'])) {
            // Creating a new playlist
            $playlist_id = wp_insert_post($args);
            if (is_wp_error($playlist_id)) {
                return comman_custom_response([
                    "status"  => false,
                    "message" => __('Failed to create playlist.', STA_TEXT_DOMAIN),
                    "is_valid_token" => $is_valid_token
                ]);
            }

            return comman_custom_response([
                "status"  => true,
                "message" => __('Playlist created successfully.', STA_TEXT_DOMAIN),
                "id"      => $playlist_id,
                "is_valid_token" => $is_valid_token
            ]);
        } else {
            // Updating an existing playlist
            $args['ID'] = $parameters['id'];
            $masvideos_update_playlist_function = "masvideos_update_" . $post_type;
            $playlist = $masvideos_update_playlist_function($parameters['id'], $args);

            if (!is_wp_error($playlist))
                return comman_custom_response([
                    "status"  => true,
                    "message" => __('Playlist updated successfully.', STA_TEXT_DOMAIN),
                    "is_valid_token" => $is_valid_token
                ]);
        }

        return comman_custom_response([
            "status"  => false,
            "message" => __('Something went wrong. Try again.', STA_TEXT_DOMAIN),
            "is_valid_token" => $is_valid_token
        ]);
    }


    public function streamit_rest_delete_playlist($request)
    {
        $data = stValidationToken($request);

        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $parameters = $request->get_params();
        if ($parameters['action'] == 'delete') {
            wp_delete_post($parameters['id'], true);
        } elseif ($parameters['action'] == 'trash') {
            wp_trash_post($parameters['id']);
        }

        // Retrieve logged-in devices to check token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);

        return comman_custom_response([
            "status"    => true,
            "message"   => sprintf(__('Playlist %s successfully', STA_TEXT_DOMAIN), $parameters['action']),
            "is_valid_token" => $is_valid_token
        ]);
    }

    public function streamit_rest_playlist_items($request)
    {
        $parameters = $request->get_params();

        $data = stValidationToken($request);
        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];

        // Retrieve logged-in devices and determine token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);
    
        $playlist_content   = array();
        $type               = $parameters['post_type'];
        $key_prefix         = rtrim($type, "_playlist");
        $post_ids           = get_post_meta($parameters['playlist_id'], "_{$key_prefix}_ids", true);

        if (empty($post_ids))
            return comman_custom_response([
                "status"    => true,
                "message"   => __('Playlist is empty.', STA_TEXT_DOMAIN),
                "data"      => [],
                "is_valid_token" => $is_valid_token
            ]);

        $arg = array(
            'post_type'         => $key_prefix,
            'post_status'       => 'publish',
            'post__in'          => $post_ids,
            'posts_per_page'    => $parameters['posts_per_page'] ?? 10,
            'paged'             => $parameters['page'] ?? 1
        );

        $content    = new WP_Query($arg);
        $posts      = $content->posts ?? [];
        if (count($posts) > 0) {
            foreach ($posts as $post) {
                $playlist_content[] = streamit_movie_video_detail_helper($post, $user_id);
            }
        }

        if (empty($playlist_content))
            return comman_custom_response([
                "status"    => true,
                "message"   => __('Playlist is empty.', STA_TEXT_DOMAIN),
                "data"      => [],
                "is_valid_token" => $is_valid_token
            ]);

        return comman_custom_response([
            "status"    => true,
            "message"   => __('Playlist.', STA_TEXT_DOMAIN),
            "data"      => $playlist_content,
            "is_valid_token" => $is_valid_token
        ]);
    }

    public function streamit_rest_playlist_add_items($request)
    {
        $data = stValidationToken($request);
        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);

        $parameters     = $request->get_params();
        $playlist_id    = $parameters['playlist_id'];
        $post_id        = absint($parameters['post_id']);
        $type           = $parameters['post_type'];

        $response_msg   = streamit_add_to_playlist($playlist_id, $post_id, $type);


        if (!$response_msg)
            return comman_custom_response([
                "status"    => false,
                "message"   => __('Playlist media Not added. Try Again.', STA_TEXT_DOMAIN),
                "is_valid_token" => $is_valid_token
            ]);

        return comman_custom_response([
            "status"    => true,
            "message"   => $response_msg,
            "is_valid_token" => $is_valid_token
        ]);
    }

    public function streamit_rest_playlist_remove_items($request)
    {
        $data = stValidationToken($request);
        if (!$data['status'])
            return comman_custom_response($data, $data['status_code']);

        $user_id = $data['user_id'];
        $parameters     = $request->get_params();
        $playlist_id    = $parameters['playlist_id'];
        $post_id        = absint($parameters['post_id']);
        $type           = $parameters['post_type'];

        $response_msg   = streamit_remove_from_playlist($playlist_id, $post_id, $type);

        // Retrieve logged-in devices to check token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);
    
        if (!$response_msg)
            return comman_custom_response([
                "status"    => false,
                "message"   => __('Playlist media not removed. Try again.', STA_TEXT_DOMAIN),
                "is_valid_token" => $is_valid_token
            ]);

        return comman_custom_response([
            "status"    => true,
            "message"   => $response_msg,
            "is_valid_token" => $is_valid_token
        ]);
    }
}
