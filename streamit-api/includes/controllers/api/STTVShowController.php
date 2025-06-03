<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STTVShowController extends STBase
{

    public $module = 'tv-shows';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {
            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/(?P<tv_show_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_tv_show_details'],
                    'permission_callback' => '__return_true'
                )
            );
            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '(?P<tv_show_id>\d+)/seasons/(?P<season_id>\d+)',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_rest_season'],
                    'permission_callback' => '__return_true'
                )
            );
        });
    }
    public function streamit_rest_tv_show_details($request)
    {
        $data = stValidationToken($request);
        $user_id = null;
    
        if (!is_wp_error($data) && !empty($data['status'])) {
            $user_id = $data['user_id'];
        }
    
        $response = [];
        $parameters = $request->get_params();
        $episode_id = $parameters['episode_id'] ?? 0;
        $tv_show_id = $parameters['tv_show_id'] ?? 0;
    
        if (empty($tv_show_id)) {
            error_log('TV show ID is missing.');
            return comman_custom_response([
                "status" => false,
                "message" => __('TV show ID is required.', STA_TEXT_DOMAIN)
            ]);
        }
    
        $args = [
            'p' => $tv_show_id,
            'post_type' => 'tv_show',
            'post_status' => 'publish'
        ];
    
        $wp_query = new WP_Query($args);
        $response_data = $wp_query->post;
    
        if (empty($response_data)) {
            error_log('No TV show data found for ID: ' . $tv_show_id);
            return comman_custom_response([
                "status" => true,
                "message" => __('No Details found.', STA_TEXT_DOMAIN),
                "data" => (object) [],
                "is_valid_token" => false
            ]);
        }
    
        $response = streamit_tv_show_details($tv_show_id, $user_id, false);
    
        $post_type = get_post_type($episode_id);
        $views = ($post_type === 'tv_show')
            ? get_post_meta($episode_id, 'tv_show_views_count', true)
            : get_post_meta($episode_id, 'post_views_count', true);
    
        $views = !empty($views) ? (int) $views : 0;
    
        $custom_fields = [
            'is_free' => (int) get_post_meta($episode_id, 'is_free', true),
            'can_access' => false,
            'ads_count' => (int) get_post_meta($episode_id, 'ads_count', true),
            'coins' => (int) get_post_meta($episode_id, 'coins', true),
            'views_count' => $views
        ];
    
        if (!empty($user_id)) {
            $user_meta = get_user_meta($user_id, 'my_stream_access', true);
            if (!is_array($user_meta)) {
                $user_meta = json_decode($user_meta, true);
            }
    
            if (is_array($user_meta)) {
                foreach ($user_meta as $stream) {
                    if (isset($stream['id']) && $stream['id'] == $episode_id) {
                        $custom_fields['can_access'] = true;
                        break;
                    }
                }
            }
    
            $continue_watch = get_user_meta($user_id, '_watch_content', true);
            if (!empty($continue_watch)) {
                $watch_data = json_decode($continue_watch, true);
                if (isset($watch_data[$episode_id])) {
                    $response['watched_duration'] = array_merge([
                        "watchedTime" => "0",
                        "watchedTotalTime" => "0",
                        "watchedTimePercentage" => "0"
                    ], $watch_data[$episode_id]);
                } else {
                    $response['watched_duration'] = [
                        "watchedTime" => "0",
                        "watchedTotalTime" => "0",
                        "watchedTimePercentage" => "0"
                    ];
                }
            } else {
                $response['watched_duration'] = [
                    "watchedTime" => "0",
                    "watchedTotalTime" => "0",
                    "watchedTimePercentage" => "0"
                ];
            }
    
            $logged_in_devices = get_user_meta($user_id, 'streamit_loggedin_devices', true);
            $is_valid_token = !empty($logged_in_devices);
        } else {
            $response['watched_duration'] = [
                "watchedTime" => "0",
                "watchedTotalTime" => "0",
                "watchedTimePercentage" => "0"
            ];
            $is_valid_token = false;
        }
    
        $response['meta_data'] = $custom_fields;
    
        return comman_custom_response([
            "status" => true,
            "message" => __('TV shows details.', STA_TEXT_DOMAIN),
            "data" => [
                "details" => $response,
                "meta_data" => $response['meta_data'] ?? []
            ],
            "is_valid_token" => $is_valid_token
        ]);
    }
    
    public function streamit_rest_season($request)
    {
        $data       = stValidationToken($request);
        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];
    
        $parameters     = $request->get_params();
        $tv_show_id     = $parameters['tv_show_id'] ?? 0;
        $season_id      = $parameters['season_id'] ?? 0;
        $posts_per_page = $parameters['posts_per_page'] ?? 10;
        $page           = $parameters['page'] ?? 1;
    
        $season = self::streamit_seasons_data([
            "tv_show_id"        => $tv_show_id,
            "season_id"         => $season_id,
            "user_id"           => $user_id,
            "posts_per_page"    => $posts_per_page,
            "page"              => $page,
        ]);
    
        // Retrieve logged-in devices to determine token validity.
        $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
        $is_valid_token = !empty($logged_in_devices);
    
        if (empty($season)) {
            return comman_custom_response([
                "status"    => true,
                "message"   => __("No data found.", STA_TEXT_DOMAIN),
                "data"      => [],
                "is_valid_token" => $is_valid_token
            ]);
        }
    
        // Check if 'episodes' exist in response
        if (!empty($season['episodes']) && is_array($season['episodes'])) {
            foreach ($season['episodes'] as $key => $episode) {
                if (!isset($episode['id'])) {
                    continue; // Skip invalid entries
                }
    
                $post_id = intval($episode['id']);
                $unlock_time = get_user_meta($user_id, 'unlock_time_' . $post_id, true);
                $unlock_time = intval($unlock_time);
                $current_time = time();
            
                $remaining_minutes = ($unlock_time > $current_time) ? ceil(($unlock_time - $current_time) / 60) : 0;
            
                $formatted_unlock_time = ($unlock_time > 0) ? date('Y-m-d\TH:i', $unlock_time) : '';

                if ($remaining_minutes > 0) {
                    $hours = floor($remaining_minutes / 60);
                    $minutes = $remaining_minutes % 60;
                    if ($hours > 0) {
                        $formatted_time = "{$hours}h" . ($minutes > 0 ? ":{$minutes}m" : "");
                    } else {
                        $formatted_time = "{$minutes}m";
                    }
                } else {
                    $formatted_time = "0h:0m";
                } 
                // Add unlock_time to each episode
                $season['episodes'][$key]['unlock_time'] = $formatted_time;
            }
        }
    
        return comman_custom_response([
            "status"    => true,
            "message"   => __("Season data.", STA_TEXT_DOMAIN),
            "data"      => $season,
            "is_valid_token" => $is_valid_token
        ]);
    }
    

    public static function streamit_seasons_data($args)
{
    if (empty($args)) return [];

    $args = wp_parse_args(
        $args,
        [
            "tv_show_id"        => 0,
            "season_id"         => 0,
            "user_id"           => null,
            "posts_per_page"    => 10,
            "page"              => 1
        ]
    );

    if (!$args["tv_show_id"]) return [];

    $tv_show_seasons = get_post_meta($args['tv_show_id'], '_seasons', true);
    $seasons_data = $tv_show_seasons[$args['season_id']] ?? 0;

    if (!$seasons_data || empty($seasons_data)) return [];

    $season = [
        "name"        => $seasons_data["name"],
        "description" => $seasons_data["description"],
        "year"        => $seasons_data["year"],
        "position"    => $seasons_data["position"],
    ];

    $full_image = wp_get_attachment_image_src($season['image_id'] ?? 0, [300, 300]);
    $season['image'] = $full_image[0] ?? null;

    if (!empty($seasons_data['episodes'])) {
        $user_id = $args['user_id'];
        $is_logged_in = !empty($user_id);

        $query_args = [
            "post_type"      => "episode",
            "post_status"    => "publish",
            "post__in"       => array_reverse($seasons_data['episodes']),
            "posts_per_page" => $args["posts_per_page"],
            "paged"          => $args["page"],
            "orderby"        => 'meta_value_num post__in'
        ];

        $episodes = new WP_Query($query_args);
        $episodes = $episodes->posts ?? [];
        $season["episodes"] = [];

        if (!$episodes) return $season;

        $watch_data = [];
        if ($is_logged_in) {
            $watch_content = get_user_meta($user_id, "_watch_content", true);
            $watch_data = $watch_content ? json_decode($watch_content, true) : [];

            $logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
            $is_valid_token = !empty($logged_in_devices);
        } else {
            $is_valid_token = false;
        }

        foreach ($episodes as $episode) {
            $episode_id = $episode->ID;
            $post_meta = get_post_meta($episode_id);
            $attachment_id = $post_meta['_episode_attachment_id'][0] ?? null;

            $audio = $attachment_id ? wp_get_attachment_url($attachment_id) : null;

            $metadata = $attachment_id ? get_post_meta($attachment_id, '_wp_attachment_metadata', true) : [];
            $artist = $metadata['artist'] ?? '';
            $album = $metadata['album'] ?? '';
            $caption = $attachment_id ? get_post_field('post_excerpt', $attachment_id) : '';
            $description = $attachment_id ? get_post_field('post_content', $attachment_id) : '';

            $is_free = (int) get_post_meta($episode_id, 'is_free', true);
            $unlock_time = (int) get_post_meta($episode_id, 'unlock_time', true);
            $current_time = time();
            $can_access = false;

            // ✅ Allow access if free and unlocked
            if ($is_free && (!$unlock_time || $unlock_time <= $current_time)) {
                $can_access = true;
            }

            $is_pro = get_user_meta($user_id, 'is_pro', true);
$can_access = false;

if ($is_logged_in && $is_pro) {
    $can_access = true;
} else {
    // ✅ Allow access if free and unlocked
    if ($is_free && (!$unlock_time || $unlock_time <= $current_time)) {
        $can_access = true;
    }

    if ($is_logged_in) {
        $user_meta = get_user_meta($user_id, 'my_stream_access', true);
        if (!is_array($user_meta)) {
            $user_meta = [];
        }

        foreach ($user_meta as $stream) {
            if (isset($stream['id']) && (int)$stream['id'] === (int)$episode_id) {
                $can_access = true;
                break;
            }
        }

        if ((new self)->is_post_unlocked_for_user($episode_id, $user_id)) {
            $can_access = true;
        }
    }
}




            // ⏱️ Calculate formatted unlock time left
            $formatted_time = "0h:0m";
            if ($is_logged_in) {
                $user_unlock_time = intval(get_user_meta($user_id, 'unlock_time_' . $episode_id, true));
                if ($user_unlock_time && $user_unlock_time > $current_time) {
                    $remaining_minutes = ceil(($user_unlock_time - $current_time) / 60);
                    $hours = floor($remaining_minutes / 60);
                    $minutes = $remaining_minutes % 60;
                    $formatted_time = ($hours > 0) ? "{$hours}h" . ($minutes > 0 ? ":{$minutes}m" : "") : "{$minutes}m";
                }
            }

            // ⛳ Watched info
            $watched = $watch_data[$episode_id] ?? [];

            $episode_data = streamit_episode_details($episode, $user_id);
            $episode_data = array_merge($episode_data, [
                'audio'         => $audio,
                'artist'        => $artist,
                'album'         => $album,
                'caption'       => $caption,
                'description'   => $description,
                'is_free'       => $is_free,
                'ads_count'     => (int) get_post_meta($episode_id, 'ads_count', true),
                'coins'         => (int) get_post_meta($episode_id, 'coins', true),
                'can_access'    => $can_access,
                'unlock_time'   => $formatted_time,
                'is_valid_token' => $is_valid_token,
                'is_pro'        => (bool) $is_pro,
                'watched_duration' => [
                    'watchedTime'           => strval($watched['watchedTime'] ?? "0"),
                    'watchedTotalTime'      => strval($watched['watchedTotalTime'] ?? "0"),
                    'watchedTimePercentage' => strval($watched['watchedTimePercentage'] ?? "0"),
                ]
            ]);

            $season['episodes'][] = $episode_data;
        }
    }

    return $season;
}

    
    function is_post_unlocked_for_user($post_id, $user_id) {
    $current_time = time();
    $user_unlock_time = get_user_meta($user_id, 'unlock_time_' . $post_id, true);

    return ($user_unlock_time && $current_time >= $user_unlock_time);
}

}
