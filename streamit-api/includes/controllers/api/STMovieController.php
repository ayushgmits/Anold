<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STMovieController extends STBase
{

    public $module = 'movies';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/(?P<movie_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_movie_details'],
                    'permission_callback' => '__return_true'
                )
            );
            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/recommended',
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => [$this, 'streamit_rest_recommended_movies'],
                    'permission_callback'   => '__return_true'
                )
            );
        });
    }

    // public function streamit_rest_movie_details($request)
    // {
    //     $data       = stValidationToken($request);
    //     $user_id    = null;

    //     if ($data['status'])
    //         $user_id = $data['user_id'];

    //     $response   = [];
    //     $parameters = $request->get_params();
    //     $movie_id   = $parameters['movie_id'];
    //     $response   = streamit_movie_details($movie_id, $user_id, false);
    //     user_posts_view_count($movie_id);

    //      // Retrieve user meta 'my_stream_access'
    //     $user_meta = get_user_meta($user_id, 'my_stream_access', true);
        
    //     // Ensure it's an array, if it's stored as a string, convert it into an array
    //     if (!is_array($user_meta)) {
    //         $user_meta = json_decode($user_meta, true); // Assuming it's stored as a JSON string
    //     }

    //     // Default to no access
    //     $can_access = false;
        
    //     // Check if episode ID exists in 'my_stream_access' data
    //     if (is_array($user_meta)) {
    //         foreach ($user_meta as $stream) {
    //             if (isset($stream['id']) && $stream['id'] == $episode_id) {
    //                 $can_access = true;
    //                 break;
    //             }
    //         }
    //     }

    //     // Retrieve and cast custom meta fields
    //     $custom_fields = [
    //         'is_free'    => (int) get_post_meta($episode_id, 'is_free', true),  
    //         'can_access' => $can_access,
    //         'ads_count'  => (int) get_post_meta($episode_id, 'ads_count', true),
    //         'coins'      => (int) get_post_meta($episode_id, 'coins', true),    
    //     ];
        
    //     if (empty($response))
    //         return comman_custom_response([
    //             "status" => true,
    //             "message" => __("No details found.", STA_TEXT_DOMAIN),
    //             "data" => []
    //         ]);

    //     //recommended-movies 
    //     $recommended_movies     = self::streamit_recommended_movies(
    //         $movie_id,
    //         [
    //             "user_id"           => $user_id,
    //             "posts_per_page"    => 4
    //         ]
    //     );

    //     //upcoming-movies
    //     $upcoming_movies    = [];
    //     $arg = array(
    //         'post_type'         => 'movie',
    //         'post_status'       => 'publish',
    //         'meta_key'          => 'name_upcoming',
    //         'posts_per_page'    => 4,
    //         'post__not_in'      => array($movie_id),
    //         'meta_query'        => array(
    //             'key'           => 'name_upcoming',
    //             'value'         => 'yes',
    //             'compare'       => 'LIKE'
    //         )
    //     );
    //     $upcoming   = new WP_Query($arg);
    //     $posts      = $upcoming->posts ?? [];
    //     if (count($posts) > 0) {
    //         foreach ($posts as $post) {
    //             $upcoming_movies[] = streamit_movie_details($post, $user_id);
    //         }
    //     }

    //     $response = array(
    //         "status"    => true,
    //         "message"   => __("Movie Details.", STA_TEXT_DOMAIN),
    //         'data'      => [
    //             'details'               => $response,
    //             'recommended_movies'    => $recommended_movies,
    //             'upcoming_movies'       => $upcoming_movies,
    //             'meta_data'             => $custom_fields,
    //         ]
    //     );
    //     return comman_custom_response($response);
    // }

    public function streamit_rest_movie_details($request)
    {
        $data       = stValidationToken($request);
        $user_id    = null;

        if ($data['status'])
            $user_id = $data['user_id'];

        $response   = [];
        $parameters = $request->get_params();
        $movie_id   = $parameters['movie_id'];
        $response   = streamit_movie_details($movie_id, $user_id, false);
        user_posts_view_count($movie_id);

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

        // Check if coins meta is 0 or blank, grant access
        $coins_meta = get_post_meta($episode_id, 'coins', true);
        if ($coins_meta = ' ') {
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
        
        if (empty($response))
            return comman_custom_response([
                "status" => true,
                "message" => __("No details found.", STA_TEXT_DOMAIN),
                "data" => []
            ]);

        //recommended-movies 
        $recommended_movies     = self::streamit_recommended_movies(
            $movie_id,
            [
                "user_id"           => $user_id,
                "posts_per_page"    => 4
            ]
        );

        //upcoming-movies
        $upcoming_movies    = [];
        $arg = array(
            'post_type'         => 'movie',
            'post_status'       => 'publish',
            'meta_key'          => 'name_upcoming',
            'posts_per_page'    => 4,
            'post__not_in'      => array($movie_id),
            'meta_query'        => array(
                'key'           => 'name_upcoming',
                'value'         => 'yes',
                'compare'       => 'LIKE'
            )
        );
        $upcoming   = new WP_Query($arg);
        $posts      = $upcoming->posts ?? [];
        if (count($posts) > 0) {
            foreach ($posts as $post) {
                $upcoming_movies[] = streamit_movie_details($post, $user_id);
            }
        }
        
        // Add continue watching data
        $continue_watch = get_user_meta($user_id, "_watch_content", true);
        $continue_watch_list = [];

        if ($continue_watch) {
            $watch_data = json_decode($continue_watch, true);
            foreach ($watch_data as $id => $watched_data) {
                $content = streamit_movie_video_detail_helper($id, $user_id);
                if (!empty($content)) {
                    $content["watched_duration"] = $watch_data[$id];
                    $continue_watch_list[] = $content;
                }
            }
        }

        // Retrieve logged-in devices to check token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);
    
        $response = array(
            "status"    => true,
            "message"   => __("Movie Details.", STA_TEXT_DOMAIN),
            'data'      => [
                'details'               => $response,
                'recommended_movies'    => $recommended_movies,
                'upcoming_movies'       => $upcoming_movies,
                'meta_data'             => $custom_fields,
                'continue_watch'        => $continue_watch_list
            ],
            "is_valid_token" => $is_valid_token
        );
        return comman_custom_response($response);
    }

    public function streamit_rest_recommended_movies($request)
    {
        $data       = stValidationToken($request);
        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $parameters             = $request->get_params();
        $movie_id               = $parameters['movie_id'];
        $posts_per_page         = $parameters["posts_per_page"] ?? 10;
        $page                   = $parameters["page"] ?? 10;

        $recommended_movies     = self::streamit_recommended_movies(
            $movie_id,
            [
                "user_id"           => $user_id,
                "posts_per_page"    => $posts_per_page,
                "page"              => $page
            ]
        );

        if (empty($recommended_movies))
            return comman_custom_response([
                "status" => true,
                "message" => __("No movies found.", STA_TEXT_DOMAIN),
                "data" => []
            ]);

            // Add custom meta data for each recommended movie
        foreach ($recommended_movies as $key => $movie) {
            $movie_id = $movie['id'];  // Assuming each movie in the response has an 'id' key

            // Fetch custom fields (meta data)
            $custom_fields = [
                'is_free'    => (int) get_post_meta($movie_id, 'is_free', true),
                'can_access' => '',
                'ads_count'  => (int) get_post_meta($movie_id, 'ads_count', true),
                'coins'      => (int) get_post_meta($movie_id, 'coins', true),
            ];

            // Add the custom fields to the movie data
            $recommended_movies[$key]['meta_data'] = $custom_fields;
        }

        // Add continue watching data
        $continue_watch = get_user_meta($user_id, "_watch_content", true);
        $continue_watch_list = [];

        if ($continue_watch) {
            $watch_data = json_decode($continue_watch, true);
            foreach ($watch_data as $id => $watched_data) {
                $content = streamit_movie_video_detail_helper($id, $user_id);
                if (!empty($content)) {
                    $content["watched_duration"] = $watch_data[$id];
                    $continue_watch_list[] = $content;
                }
            }
        }

        // Retrieve logged-in devices to check token validity.
    $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
    $is_valid_token = !empty($logged_in_devices);

        return comman_custom_response([
            "status"    => true,
            "message"   => __("Recommended movies.", STA_TEXT_DOMAIN),
            'data'      => $recommended_movies,
            'continue_watching'  => $continue_watch_list,
            "is_valid_token"    => $is_valid_token
        ]);
    }

    public static function streamit_recommended_movies($movie_id, $args = [])
    {

        if (empty($args)) return [];

        $recommended_movies      = [];
        $args = wp_parse_args(
            $args,
            [
                'user_id'           => null,
                'posts_per_page'    => 10,
                'page'              => 1
            ]
        );
        $ids  = get_post_meta($movie_id, '_recommended_movie_ids')[0] ?? [];

        if (empty($ids))
            return [];

        $arg = array(
            'post_type'         => 'movie',
            'post_status'       => 'publish',
            'post__in'          => $ids,
            'posts_per_page'    => $args['posts_per_page'],
            'paged'             => $args['page']
        );

        $recommended = new WP_Query($arg);
        $posts = $recommended->posts ?? [];
        if (count($posts) > 0) {
            foreach ($posts as $post) {
                $recommended_movies[] = streamit_movie_details($post, $args['user_id']);
            }
        }

        return $recommended_movies;
    }
}
