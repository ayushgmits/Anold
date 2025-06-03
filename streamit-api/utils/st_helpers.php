<?php

use Tmeister\Firebase\JWT\JWT;
use Tmeister\Firebase\JWT\Key;

function stValidationToken($request, $access_request_without_auth = false)
{
    if ($access_request_without_auth) {
        if (empty($request->get_header('Authorization'))) {
            return [
                'status'        => true,
                'status_code'   => 200,
                'message'       => 'Valid token.',
                'user_id'       => 0
            ];
        }
    }
    $response = collect((new Jwt_Auth_Public('jwt-auth', '1.1.0'))->validate_token($request, false));

    if ($response->has('errors'))
        return [
            'status_code'   => array_values($response['error_data'])[0]["status"] ??  401,
            'status'        => false,
            'message'       => array_values($response['errors'])[0][0] ??  __("Authorization failed", STA_TEXT_DOMAIN),
        ];

    return [
        'status'        => true,
        'status_code'   => 200,
        'message'       => 'Valid token.',
        'user_id'       => get_current_user_id()
    ];
}

function streamit_validate_custom_token($custom_token = false)
{
    /*
     * Looking for the Authorization header
     *
     * There is two ways to get the authorization token
     *  1. via WP_REST_Request
     *  2. via custom_token, we get this for all the other API requests
     *
     * The get_header( 'Authorization' ) checks for the header in the following order:
     * 1. HTTP_AUTHORIZATION
     * 2. REDIRECT_HTTP_AUTHORIZATION
     *
     * @see https://core.trac.wordpress.org/ticket/47077
     */

    $auth_header = $custom_token;

    if (!$auth_header) {
        return new WP_Error(
            'jwt_auth_no_auth_header',
            'Authorization header not found.',
            [
                'status' => 403,
            ]
        );
    }

    /*
     * Extract the authorization header
     */
    [$token] = sscanf($auth_header, 'Bearer %s');

    /**
     * if the format is not valid return an error.
     */
    if (!$token) {
        return new WP_Error(
            'jwt_auth_bad_auth_header',
            'Authorization header malformed.',
            [
                'status' => 403,
            ]
        );
    }

    /** Get the Secret Key */
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    if (!$secret_key) {
        return new WP_Error(
            'jwt_auth_bad_config',
            'JWT is not configured properly, please contact the admin',
            [
                'status' => 403,
            ]
        );
    }

    /** Try to decode the token */
    try {
        $algorithm = "HS256";
        if ($algorithm === false) {
            return new WP_Error(
                'jwt_auth_unsupported_algorithm',
                __(
                    'Algorithm not supported, see https://www.rfc-editor.org/rfc/rfc7518#section-3',
                    'wp-api-jwt-auth'
                ),
                [
                    'status' => 403,
                ]
            );
        }

        $token = JWT::decode($token, new Key($secret_key, $algorithm));

        /** The Token is decoded now validate the iss */
        if ($token->iss !== get_bloginfo('url')) {
            /** The iss do not match, return error */
            return new WP_Error(
                'jwt_auth_bad_iss',
                'The iss do not match with this server',
                [
                    'status' => 403,
                ]
            );
        }

        /** So far so good, validate the user id in the token */
        if (!isset($token->data->user->id)) {
            /** No user id in the token, abort!! */
            return new WP_Error(
                'jwt_auth_bad_request',
                'User ID not found in the token',
                [
                    'status' => 403,
                ]
            );
        }

        /** Everything looks good return the decoded token if we are using the custom_token */
        if ($custom_token) {
            return $token;
        }
    } catch (Exception $e) {
        /** Something were wrong trying to decode the token, send back the error */
        return new WP_Error(
            'jwt_auth_invalid_token',
            $e->getMessage(),
            [
                'status' => 403,
            ]
        );
    }
}
function stValidateRequest($rules, $request, $message = [])
{
    $error_messages     = [];
    $required_message   = __(' field is required', STA_TEXT_DOMAIN);
    $email_message      =  __(' has invalid email address', STA_TEXT_DOMAIN);

    if (count($rules)) {
        foreach ($rules as $key => $rule) {
            if (strpos($rule, '|') !== false) {
                $ruleArray = explode('|', $rule);
                foreach ($ruleArray as $r) {
                    if ($r === 'required') {
                        if (!isset($request[$key]) || $request[$key] === "" || $request[$key] === null) {
                            $error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $required_message;
                        }
                    } elseif ($r === 'email') {
                        if (isset($request[$key])) {
                            if (!filter_var($request[$key], FILTER_VALIDATE_EMAIL) || !is_email($request[$key])) {
                                $error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $email_message;
                            }
                        }
                    }
                }
            } else {
                if ($rule === 'required') {
                    if (!isset($request[$key]) || $request[$key] === "" || $request[$key] === null) {
                        $error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $required_message;
                    }
                } elseif ($rule === 'email') {
                    if (isset($request[$key])) {
                        if (!filter_var($request[$key], FILTER_VALIDATE_EMAIL) || !is_email($request[$key])) {
                            $error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $email_message;
                        }
                    }
                }
            }
        }
    }

    return $error_messages;
}

function stRecursiveSanitizeTextFields($array)
{
    $filterParameters = [];
    foreach ($array as $key => $value) {

        if ($value === '') {
            $filterParameters[$key] = null;
        } else {
            if (is_array($value)) {
                $filterParameters[$key] = stRecursiveSanitizeTextFields($value);
            } else if (preg_match("/<[^<]+>/", $value, $m) !== 0) {
                $filterParameters[$key] = $value;
            } else {
                $filterParameters[$key] = sanitize_text_field($value);
            }
        }
    }

    return $filterParameters;
}

function stGenerateString($length_of_string = 10)
{
    // String of all alphanumeric character
    $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($str_result), 0, $length_of_string);
}

function comman_custom_response($res, $status_code = 200)
{
    $response = new WP_REST_Response($res);
    $response->set_status($status_code);
    return $response;
}

function streamit_dashboard_filters($type, $args, $parameters)
{
    return [
        "none" => [],
        "latest" => [
            'order'     => 'DESC',
            'orderby'   => 'publish_date'
        ],
        "upcoming" => [
            'posts_per_page'    => $parameters['posts_per_page'] ?? 6,
            'paged'             => $parameters['paged'] ?? 1,
            'meta_query' => array(
                'relation'         => 'AND',
                [
                    'key'       => 'name_upcoming',
                    'compare'   => 'EXISTS',
                ],
                [
                    'key'       => 'name_upcoming',
                    'compare'   => '!=',
                    'value'     => ''
                ]
            ),
        ],
        "most_liked" => [
            'post__in'  => streamit_get_most_liked($args['post_type'], [], 'id'),
            'orderby'   => 'post__in'
        ],
        "most_viewed" => [
            '   ' => array(
                'relation' => 'OR',
                array(
                    'key' => 'post_views_count',
                ),
                array(
                    'key' => 'tv_show_views_count',
                )
            ),
            'orderby' => array(
                'post_views_count'      => 'DESC',
                'tv_show_views_count'   => 'DESC',
            )
        ],
        "top_ten" => [
            'post_status'   => 'publish',
            'meta_key'      => 'post_views_count',
            'orderby'       => 'meta_value_num',
            'order'         => 'DESC',
            'meta_query'    => [
                [
                    'key'     => 'name_upcoming',
                    'value'   => '',
                    'compare' => '=='
                ]
            ]
        ],
        "tax_query" => [
            $type     => [
                "taxonomies"        => $type . '_genre',
                "tag_taxonomies"    => $type . '_tag',
                "cat_taxonomies"    => ""
            ],
            "home"     => [
                "taxonomies"        => ["movie_genre", "tv_show_genre", "live_tv_cat", "video_cat"],
                "tag_taxonomies"    => ["movie_tag", "tv_show_tag", "video_tag"],
                "cat_taxonomies"    => ["video_cat", "live_tv_cat"]
            ],
            "video" => [
                "taxonomies"        => "",
                "tag_taxonomies"    => "video_tag",
                "cat_taxonomies"    => "video_cat",
            ],
            "live_tv" => [
                "taxonomies"        => "",
                "tag_taxonomies"    => "",
                "cat_taxonomies"    => "live_tv_cat",
            ]
        ]
    ];
}

function streamit_taxonomy_query($taxonomies, $terms)
{
    if (empty($terms) || empty($taxonomies)) return [];

    if (!is_array($taxonomies))
        return [[
            'taxonomy'  => $taxonomies,
            'field'     => 'term_id',
            'operator'  => 'IN',
            'terms'     => $terms
        ]];

    $query_args = [];
    foreach ($taxonomies as $taxonomy) {
        $query_args[] = [
            'taxonomy'  => $taxonomy,
            'field'     => 'term_id',
            'operator'  => 'IN',
            'terms'     => $terms
        ];
    }
    return $query_args;
}

function streamit_movie_details($post, $user_id = null, $is_short = true)
{
    if (is_object($post))
        $data = $post;
    else
        $data = get_post($post);

    if (empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = get_post_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($post_id), [300, 300]);

    if ($is_short) {
        return [
            'id'            => $post_id,
            'title'         => $data->post_title,
            'image'         => !empty($image) ? $image[0] : null,
            'run_time'      => $post_meta['_movie_run_time'][0],
            'post_type'     => $post_type,
            'stream_type'   => $post_meta['_movie_choice'][0]
        ];
    }

    $avg_rating     = $post_meta['_masvideos_average_rating'][0];
    $trailer_link   = !empty($post_meta['name_trailer_link'][0]) ? $post_meta['name_trailer_link'][0] : null;
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        // 'description'       => !empty($data->post_content) ? apply_filters( 'the_content', get_the_content(null,false,$post_id) ): '',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => streamit_check_video_url_type($trailer_link),
        'is_liked'              => st_is_user_liked_post($post_id, $user_id),
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_watchlist'          => st_is_item_in_watchlist($post_id, $user_id),
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'embed_content'         => $post_meta['_movie_embed_content'][0],
        'movie_choice'          => $post_meta['_movie_choice'][0],
        'sources'               => streamit_source_list($post_id),
        'url_link'              => $post_meta['_movie_url_link'][0],
        'genre'                 => get_taxonomy_terms_helper($post_id, 'movie', 'genre'),
        'tag'                   => get_taxonomy_terms_helper($post_id, 'movie', 'tag'),
        'run_time'              => $post_meta['_movie_run_time'][0],
        'censor_rating'         => $post_meta['_movie_censor_rating'][0],
        'release_date'          => date_i18n(get_option('date_format'), $post_meta['_movie_release_date'][0]),
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => streamit_cast_detail_helper($post_id),
        'crews'                 => streamit_cast_detail_helper($post_id, '_crew'),
        'comments'              => streamit_get_post_comments(['post_id' => $post_id]),
        'is_password_protected' => post_password_required($post_id)
    ];

    $movie_file         = isset($post_meta['_movie_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_movie_attachment_id'][0]) : null;
    $temp['movie_file'] = $movie_file ? $movie_file : null;

    $logo               = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = restrictedPlanList($post_id, $user_id);
    $temp = array_merge($temp, $plan_list);

    return $temp;
}

function streamit_tv_show_details($post, $user_id = null, $is_short = true)
{
    if (is_object($post))
        $data = $post;
    else
        $data = get_post($post);

    if (empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = get_post_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($post_id), [300, 300]);

    if ($is_short) {
        return [
            'id'                => $post_id,
            'title'             => $data->post_title,
            'image'             => !empty($image) ? $image[0] : null,
            'post_type'         => $post_type
        ];
    }

    $avg_rating     = $post_meta['_masvideos_average_rating'][0];
    $trailer_link   = !empty($post_meta['name_trailer_link'][0]) ? $post_meta['name_trailer_link'][0] : null;
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        // 'description'       => !empty($data->post_content) ? apply_filters( 'the_content', get_the_content(null,false,$post_id) ): '',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => streamit_check_video_url_type($trailer_link),
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_liked'              => st_is_user_liked_post($post_id, $user_id),
        'is_watchlist'          => st_is_item_in_watchlist($post_id, $user_id),
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'genre'                 => get_taxonomy_terms_helper($post_id, 'tv_show', 'genre'),
        'tag'                   => get_taxonomy_terms_helper($post_id, 'tv_show', 'tag'),
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => streamit_cast_detail_helper($post_id),
        'crews'                 => streamit_cast_detail_helper($post_id, '_crew'),
        'comments'              => streamit_get_post_comments(['post_id' => $post_id]),
        'is_password_protected' => post_password_required($post_id)
    ];

    $seasons            = get_post_meta($post_id, "_seasons")[0] ?? [];
    $total_seasons      = count($seasons);
    if ($total_seasons > 0) {
        $seasons = collect($seasons);
        $seasons = $seasons->map(function ($item, $index) {
            return [
                "id"    => $index,
                "name"  => $item["name"]
            ];
        });
        $temp['seasons'] = ["count" => $total_seasons, "data" => $seasons];
    } else {
        $temp['seasons'] = (object) [];
    }


    $logo           = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']   = $logo[0] ?? null;

    $plan_list      = restrictedPlanList($post_id, $user_id);
    $temp           = array_merge($temp, $plan_list);

    return $temp;
}

function streamit_episode_details($post, $user_id = null, $is_short = true)
{
    if (is_object($post))
        $data = $post;
    else
        $data = get_post($post);

    if (empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = get_post_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($post_id), [300, 300]);

    if ($is_short) {
        return [
            'id'                => $post_id,
            'title'             => $data->post_title,
            'image'             => !empty($image) ? $image[0] : null,
            'post_type'         => $post_type,
            'stream_type'       => $post_meta['_episode_choice'][0],
            'run_time'          => $post_meta['_episode_run_time'][0],
            'release_date'      => date_i18n(get_option('date_format'), $post_meta['_episode_release_date'][0])
        ];
    }
    $trailer_link = !empty($post_meta['name_trailer_link'][0]) ? $post_meta['name_trailer_link'][0] : null;
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        // 'description'       => !empty($data->post_content) ? apply_filters( 'the_content', get_the_content(null,false,$post_id) ): '',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => streamit_check_video_url_type($trailer_link),
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_liked'              => st_is_user_liked_post($post_id, $user_id),
        'is_watchlist'          => st_is_item_in_watchlist($post_id, $user_id),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'tv_show_id'            => $post_meta['_tv_show_id'][0],
        'embed_content'         => $post_meta['_episode_embed_content'][0],
        'episode_choice'        => $post_meta['_episode_choice'][0],
        'sources'               => streamit_source_list($post_id),
        'url_link'              => $post_meta['_episode_url_link'][0],
        'run_time'              => $post_meta['_episode_run_time'][0],
        'release_date'          => date_i18n(get_option('date_format'), $post_meta['_episode_release_date'][0]),
        'comments'              => streamit_get_post_comments(['post_id' => $post_id]),
        'is_password_protected' => post_password_required($post_id)
    ];

    $episode_file           = isset($post_meta['_episode_attachment_id']) && !empty($post_meta['_episode_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_episode_attachment_id'][0]) : null;
    $temp['episode_file']   = $episode_file ? $episode_file : null;

    $plan_list              = restrictedPlanList($post_id, $user_id);
    $temp                   = array_merge($temp, $plan_list);

    return $temp;
}

function streamit_video_details($post, $user_id = null, $is_short = true)
{
    if (is_object($post))
        $data = $post;
    else
        $data = get_post($post);

    if (empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = get_post_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($post_id), [300, 300]);

    if ($is_short) {
        return [
            'id'                => $post_id,
            'title'             => $data->post_title,
            'image'             => !empty($image) ? $image[0] : null,
            'run_time'          => $post_meta['_video_run_time'][0],
            'post_type'         => $post_type,
            'stream_type'       => $post_meta['_video_choice'][0]
        ];
    }
    $trailer_link = !empty($post_meta['name_trailer_link'][0]) ? $post_meta['name_trailer_link'][0] : null;
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        // 'description'       => !empty($data->post_content) ? apply_filters( 'the_content', get_the_content(null,false,$post_id) ): '',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => streamit_check_video_url_type($trailer_link),
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_liked'              => st_is_user_liked_post($post_id, $user_id),
        'is_watchlist'          => st_is_item_in_watchlist($post_id, $user_id),
        'embed_content'         => $post_meta['_video_embed_content'][0],
        'video_choice'          => $post_meta['_video_choice'][0],
        'url_link'              => $post_meta['_video_url_link'][0],
        'genre'                 => get_taxonomy_terms_helper($post_id, 'video', 'cat'),
        'tag'                   => get_taxonomy_terms_helper($post_id, 'video', 'tag'),
        'run_time'              => $post_meta['_video_run_time'][0],
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => streamit_cast_detail_helper($post_id),
        'crews'                 => streamit_cast_detail_helper($post_id, '_crew'),
        'comments'              => streamit_get_post_comments(['post_id' => $post_id]),
        'is_password_protected' => post_password_required($post_id)
    ];

    $video_file         = isset($post_meta['_video_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_video_attachment_id'][0]) : null;
    $temp['video_file'] = $video_file ? $video_file : null;

    $logo               = isset($post_meta['name_logo'][0]) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = restrictedPlanList($post_id, $user_id);
    $temp               = array_merge($temp, $plan_list);

    return $temp;
}

function streamit_live_tv_details($post, $user_id = null, $is_short = true)
{
    if (is_object($post))
        $data = $post;
    else
        $data = get_post($post);

    if (empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = get_post_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($post_id), [300, 300]);

    if ($is_short) {
        return [
            'id'                => $post_id,
            'title'             => $data->post_title,
            'image'             => !empty($image) ? $image[0] : null,
            // 'run_time'          => $post_meta['_video_run_time'][0],
            'post_type'         => $post_type,
            'stream_type'       => $post_meta['iqonic_live_tv_channel_url_type'][0] ?? ''
        ];
    }

    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        'stream_type'           => $post_meta['iqonic_live_tv_channel_url_type'][0] ?? '',
        'url'                   => $post_meta['iqonic_live_tv_channel_url'][0] ?? '',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_liked'              => st_is_user_liked_post($post_id, $user_id),
        'is_watchlist'          => st_is_item_in_watchlist($post_id, $user_id),
        'genre'                 => get_taxonomy_terms_helper($post_id, 'live_tv', 'cat', 'id'),
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'comments'              => streamit_get_post_comments(['post_id' => $post_id]),
        'is_password_protected' => post_password_required($post_id)
    ];

    $plan_list          = restrictedPlanList($post_id, $user_id);
    $temp               = array_merge($temp, $plan_list);

    return $temp;
}

function streamit_movie_video_detail_helper($post, $user_id = null)
{
    $post_type = get_post_type($post);

    if (!$post_type || !in_array($post_type, ["movie", "tv_show", "episode", "video", 'live_tv'])) return [];

    $streamit_function_post_details = "streamit_{$post_type}_details";
    return $streamit_function_post_details($post, $user_id);
}

//check if video url type is youtube | vimeo | other 
function streamit_check_video_url_type($url)
{
    if ($url == null || empty($url)) return '';
    // Check if the URL is from Vimeo
    if (strpos($url, 'vimeo.com') !== false) {
        return 'Vimeo';
    } else if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) { // Check if the URL is from YouTube
        return 'YouTube';
    } else {
        return "other";
    }
}

function streamit_get_post_comments($args = [])
{
    $args = wp_parse_args(
        $args,
        array(
            'number'    => '3',
            'status'    => 'approve',
            'post_id'   => 0
        )
    );

    if (!$args['post_id']) return [];

    $comments = get_comments($args);

    if (!$comments) return [];

    return $comments;
}

function streamit_post_args($params = null, $filter = null, $post_in = null)
{
    $params = wp_parse_args(
        $params,
        [
            "post_type"         => ['movie', 'tv_show', 'episode', 'video','live_tv'],
            "post_status"       => 'publish',
            "posts_per_page"    => 20,
            "paged"             => 1,
            "search"            => null

        ]
    );

    $args['post_type']              = $params["post_type"];
    $args['post_status']            = $params["post_status"];
    $args['posts_per_page']         = $params['posts_per_page'];
    $args['paged']                  = $params['paged'];
    $args['streamit_title_filter']  = $params['search'];

    if ($post_in)
        $args['post__in'] = $post_in;

    if ($filter === 'recent') {
        $args['order']      = 'DESC';
        $args['orderby']    = 'ID';
    }

    return $args;
}

add_filter("get_comment", function ($comment) {
    $comment->rating = (int) get_comment_meta($comment->comment_ID, 'rating', true);
    return $comment;
});

function get_taxonomy_terms_helper($post_id, $type, $taxonomy = 'cat', $return_with = false)
{
    $terms = array();
    $with_id = [];
    foreach (wp_get_object_terms($post_id, $type . '_' . $taxonomy) as $term) {
        $terms[] = $term->name;
        $with_id[$term->term_id] = $term->name;
    }

    if ($return_with === "id")
        return $with_id;

    return $terms;
}

function st_is_item_in_watchlist($post_id, $user_id = null)
{
    if ($user_id === null) return null;

    $watchlist = get_user_meta($user_id, '_user_watchlist', true);

    $watchlist_array = explode(', ', $watchlist);

    return in_array($post_id, $watchlist_array);
}

function st_is_user_liked_post($post_id, $user_id = null)
{
    if ($user_id === null) return null;

    global $wpdb;
    $table_name = $wpdb->prefix . 'ulike';

    $liked_post = $wpdb->get_row("SELECT * FROM {$table_name} WHERE `user_id`=" . $user_id . " AND `post_id` =" . $post_id . " AND `status`='like' ", OBJECT);

    return ($liked_post != null && $liked_post->status == 'like') ? 'like' : 'unlike';
}

function streamit_title_filter($where, $wp_query)
{
    global $wpdb;
    if ($search_term = $wp_query->get('streamit_title_filter')) :
        $search_term = $wpdb->esc_like($search_term);
        $search_term = ' \'%' . $search_term . '%\'';
        $title_filter_relation = (strtoupper($wp_query->get('title_filter_relation')) == 'OR' ? 'OR' : 'AND');
        $where .= ' ' . $title_filter_relation . ' ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
    endif;
    return $where;
}
add_filter('posts_where', 'streamit_title_filter', 10, 2);

function get_cast_crew_helper($post_id, $type = '_cast')
{
    $data = get_post_meta($post_id, $type, true);
    if (!empty($data))  return "";

    $data = collect($data)->map(function ($cast) {
        return get_the_title($cast['id']);
    })->toArray();

    return implode(',', $data);
}

function streamit_get_most_liked($post_type = '', $args = array(), $return = '')
{
    if (!function_exists("wp_ulike_get_most_liked_posts")) return [];
    $liked = wp_ulike_get_most_liked_posts(10, $post_type, 'post', 'all', 'like');
    if ($liked) {
        foreach ($liked as $post) {
            $most_liked_ids[] = $post->ID;
        }
        return $most_liked_ids;
    }

    return [];
}

function user_posts_view_count($post_id)
{
    global $wpdb;
    // $post_id = get_the_ID();

    $post_type = get_post_type($post_id);
    $ipaddress = $_SERVER['REMOTE_ADDR'];

    $table_name = $wpdb->prefix . 'streamit_postview';

    $already_exist = $wpdb->get_row("SELECT * FROM $table_name WHERE ip_address = '{$ipaddress}' AND post_id = {$post_id} ", OBJECT);

    if (gettype($already_exist) !== 'object') {
        $wpdb->insert($table_name, array(
            'ip_address' => $ipaddress,
            'post_id' => $post_id
        ));

        set_postview($post_id);
        if (in_array($post_type, ['tv_show', 'episode'])) {
            $key = 'tv_show_views_count';

            $tv_show_id = get_post_meta($post_id, '_tv_show_id', true);
            $seasons = get_post_meta($tv_show_id, '_seasons');

            $ep_id = array_column($seasons[0], 'episodes');
            $id = call_user_func_array('array_merge', $ep_id);

            $count = count($id);
            $sum = 0;
            $args = array(
                'post_type' => 'episode',
                'post__in' => $id,
            );

            $query = new WP_Query($args);
            $query = $query->posts ?? [];
            if ($query && count($query) > 0) {
                foreach ($query as $post) {
                    $view = get_post_meta($post->ID, 'post_views_count', true);
                    if ($view)
                        $sum += $view;
                }
            }

            update_post_meta($tv_show_id, $key, ceil($sum / $count));
        }
    }
}

function set_postview($post_id)
{
    $key = 'post_views_count';

    $count = (int) get_post_meta($post_id, $key, true);
    $count++;
    update_post_meta($post_id, $key, $count);
}

function streamit_cast_detail_helper($post_id, $type = "_cast", $cast_id = null)
{
    $cast = get_post_meta($post_id, $type, true);

    if (!empty($cast) && count($cast) > 0) {
        $cast = collect($cast)->map(function ($cast) {
            $cast_image = wp_get_attachment_image_src(get_post_thumbnail_id($cast['id']), [300, 300]);
            $data["id"] = $cast['id'];
            $data['image'] = !empty($cast_image) ? $cast_image[0] : null;
            $data['name'] = get_the_title($cast['id']);
            return $data;
        });

        if ($cast_id != null) {
            $cast = $cast->where('id', $cast_id)->pluck('character')->implode(',');
        }
        return $cast;
    }
    return [];
}

function streamit_source_list($post_id)
{
    $source = get_post_meta($post_id, '_sources', true);

    if (!empty($source) && count($source) > 0) {
        $source = collect($source)->map(function ($source) {
            return $source;
        });
    } else {
        $source = [];
    }

    return $source;
}
/**
 * Get current user's playlists.
 *
 * @since  1.0.0
 * @return array|boolean
 */
function streamit_get_current_user_playlists($type = "movie_playlist", $args = [])
{
    if (is_user_logged_in()) {
        $key_prefix = rtrim($type, "_playlist");
        $current_user_id = get_current_user_id();
        $masvideos_get_movie_playlist_visibility_options = "masvideos_get_{$type}_visibility_options";
        $playlist_args = array(
            'post_type'         => $type,
            'post_status'       => array_keys($masvideos_get_movie_playlist_visibility_options()),
            'posts_per_page'    => -1,
            'author'            => $current_user_id,
        );

        $current_user_posts = get_posts($playlist_args);
        $collection = collect($current_user_posts);
        $posts = $collection->map(function ($item) use ($args, $key_prefix) {
            $item->is_in_playlist = is_added_to_playlist($args["post_id"], $key_prefix, $item->ID); // You can set any value you want for this key
            return $item;
        });
        $posts = $posts->sortByDesc('is_in_playlist')->values();
        return $posts;
    }

    return false;
}

function streamit_add_to_playlist($playlist_id, $post_id, $type)
{
    $playlist_id    = absint($playlist_id);
    $post_id        = absint($post_id);
    $type           = str_replace("_playlist", "", $type);
    $function       = "masvideos_add_{$type}_to_playlist";

    if ($function($playlist_id, $post_id))
        return __('Media added to playlist successfully.', STA_TEXT_DOMAIN);

    return false;
}

function streamit_remove_from_playlist($playlist_id, $post_id, $type)
{
    $playlist_id    = absint($playlist_id);
    $post_id        = absint($post_id);
    $type           = str_replace("_playlist", "", $type);
    $function       = "masvideos_remove_{$type}_from_playlist";

    if ($function($playlist_id, $post_id))
        return __('Media removed from playlist successfully.', STA_TEXT_DOMAIN);

    return false;
}
function get_user_history($subscription_id)
{
    global  $wpdb;
    return $wpdb->get_var("SELECT `status`  FROM $wpdb->pmpro_memberships_users WHERE id = '$subscription_id' ORDER BY id DESC");
}
function is_added_to_playlist($post_id, $type, $playlist_id = 0)
{
    if (!$playlist_id)
        return false;

    $post_ids = get_post_meta($playlist_id, "_{$type}_ids", true);
    return ($post_ids && is_array($post_ids) && in_array($post_id, $post_ids));
}

add_action('rest_api_init', function () {
    $field_rating = 'rating';
    register_rest_field('comment', 'rating', array(
        'get_callback' => function ($comment_arr) use ($field_rating) {
            $comment_obj = get_comment($comment_arr['id']);
            $comment_obj->rating = get_comment_meta($comment_obj->comment_ID, 'rating', true);
            return (int) $comment_obj->rating;
        },
        'update_callback' => function ($rating_value, $comment_obj) {
            $response = wp_update_comment(array(
                'comment_ID'    => $comment_obj->comment_ID,
            ));
            add_comment_meta($comment_obj->comment_ID, 'rating', $rating_value);
            if ($response === false) {
                return new WP_Error('rest_comment_rating_failed', __('Failed to update comment rating.', STA_TEXT_DOMAIN), ['status' => 500]);
            }
            return true;
        },
        'schema' => array(
            'description' => __('Comment rating.'),
            'type'        => 'integer'
        ),
    ));
});

function is_streamit_theme_active()
{
    $theme_data = wp_get_theme();
    return in_array(strtolower($theme_data->get("Name")), ["streamit", "streamit child"]);
}

function st_rest_prepare_post($response)
{

    $is_close_comments_for_old_posts = get_option("close_comments_for_old_posts", false);
    if ($is_close_comments_for_old_posts) {
        $close_comments_days_old = get_option("close_comments_days_old", false);
        $now        = time();
        $your_date  = strtotime($response->data['date']);
        $datediff   = abs($now - $your_date);
        $total_days = floor($datediff / (60 * 60 * 24));
        $response->data['st_is_comment_open'] = (int) $close_comments_days_old > (int) $total_days;
    } else {
        $response->data['st_is_comment_open'] = true;
    }

    // pmp Restrictions
    $membership = restrictedPlanList($response->data['ID'], get_current_user_id());
    $response->data['user_has_access']      = $membership["user_has_access"];
    $response->data['subscription_levels']  = $membership["subscription_plans"];

    return $response;
}
add_filter("rest_prepare_post", "st_rest_prepare_post");


function streamit_order_pay_without_login($allcaps, $caps, $args)
{
    if (isset($caps[0], $_GET['key'])) {
        if ($caps[0] == 'pay_for_order') {
            $order_id = isset($args[2]) ? $args[2] : null;
            $order = wc_get_order($order_id);
            if ($order) {
                $allcaps['pay_for_order'] = true;
            }
        }
    }
    return $allcaps;
}
add_filter('user_has_cap', 'streamit_order_pay_without_login', 9999, 3);
add_filter('woocommerce_order_email_verification_required', '__return_false', 9999);


// ----------------------- Gener --------------------------------
// Add the select dropdown to the add genre form
function tv_show_add_genre_field() {
    ?>
    <div class="form-field">
        <label for="genre_type"><?php _e('Genre Type', STA_TEXT_DOMAIN); ?></label>
        <select name="genre_type" id="genre_type">
            <option value="tv_show_series" selected><?php _e('TV Show Audio Series', STA_TEXT_DOMAIN); ?></option>
            <option value="movies_series"><?php _e('Movies Audio Series', STA_TEXT_DOMAIN); ?></option>
            <option value="video_series"><?php _e('Video Audio Series', STA_TEXT_DOMAIN); ?></option>
        </select>
        <p><?php _e('Select the type of genre.', STA_TEXT_DOMAIN); ?></p>
    </div>
    <?php
}
add_action('tv_show_genre_add_form_fields', 'tv_show_add_genre_field', 10, 2);

// Save custom meta when a genre is created or edited
function tv_show_save_genre_meta($term_id) {
    if (isset($_POST['genre_type'])) {
        $selected_value = sanitize_text_field($_POST['genre_type']);

        // Map dropdown values to stored values
        $mapped_values = [
            'movies_series' => 'movies_audio_series',
            'video_series' => 'video_audio_series',
            'tv_show_series' => 'tv_show_audio_series'
        ];

        if (array_key_exists($selected_value, $mapped_values)) {
            update_term_meta($term_id, 'genre_type', $mapped_values[$selected_value]);
        }
    }
}
add_action('created_tv_show_genre', 'tv_show_save_genre_meta', 10, 2);
add_action('edited_tv_show_genre', 'tv_show_save_genre_meta', 10, 2);

// Add a column to show the genre type
function tv_show_add_genre_column($columns) {
    $columns['genre_type'] = __('Genre Type', STA_TEXT_DOMAIN);
    return $columns;
}
add_filter('manage_edit-tv_show_genre_columns', 'tv_show_add_genre_column');

// Populate the genre type column with saved values
// Populate the genre type column with saved values
function tv_show_manage_genre_columns($content, $column_name, $term_id) {
    if ($column_name === 'genre_type') {
        $genre_type = get_term_meta($term_id, 'genre_type', true);

        // Map database values to readable labels
        $display_values = [
            'movies_audio_series' => __('Movies Audio Genre', STA_TEXT_DOMAIN),
            'video_audio_series' => __('Video Audio Genre', STA_TEXT_DOMAIN),
            'tv_show_audio_series' => __('TV Show Audio Genre', STA_TEXT_DOMAIN)
        ];

        return $display_values[$genre_type] ?? __('TV Show Audio Genre', STA_TEXT_DOMAIN);
    }
    return $content;
}
add_filter('manage_tv_show_genre_custom_column', 'tv_show_manage_genre_columns', 10, 3);


// JavaScript to reload the table dynamically after adding or editing a genre
function tv_show_reload_table_after_add() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'edit-tv_show_genre') {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.data && settings.data.indexOf("action=add-tag") !== -1) {
                        setTimeout(function() {
                            $('.wp-list-table').load(location.href + ' .wp-list-table > *');
                        }, 1000);
                    }
                });
            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'tv_show_reload_table_after_add');


// Add the select dropdown to the edit genre form
function tv_show_edit_genre_field($term) {
    // Get the saved genre type from the database
    $saved_value = get_term_meta($term->term_id, 'genre_type', true);

    // Reverse map database values to dropdown options
    $reverse_map = [
        'movies_audio_series' => 'movies_series',
        'video_audio_series' => 'video_series',
        'tv_show_audio_series' => 'tv_show_series'
    ];

    $genre_type = $reverse_map[$saved_value] ?? 'tv_show_series'; // Default to 'TV Show Series'
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="genre_type"><?php _e('Genre Type', STA_TEXT_DOMAIN); ?></label></th>
        <td>
            <select name="genre_type" id="genre_type">
                <option value="tv_show_series" <?php selected($genre_type, 'tv_show_series'); ?>><?php _e('TV Show Series', STA_TEXT_DOMAIN); ?></option>
                <option value="movies_series" <?php selected($genre_type, 'movies_series'); ?>><?php _e('Movies Series', STA_TEXT_DOMAIN); ?></option>
                <option value="video_series" <?php selected($genre_type, 'video_series'); ?>><?php _e('Video Series', STA_TEXT_DOMAIN); ?></option>
            </select>
            <p class="description"><?php _e('Select the type of genre.', STA_TEXT_DOMAIN); ?></p>
        </td>
    </tr>
    <?php
}
add_action('tv_show_genre_edit_form_fields', 'tv_show_edit_genre_field', 10, 2);


// ----------------------- For TV Show --------------------------------

function add_show_type_meta_box() {
    add_meta_box(
        'show_type_meta_box', // Unique ID
        __('Seleact Show Type', STA_TEXT_DOMAIN), // Box title
        'render_show_type_meta_box', // Callback function
        'tv_show', // Your custom post type slug
        'side',
        'default' // Priority
    );
}
add_action('add_meta_boxes', 'add_show_type_meta_box');

function render_show_type_meta_box($post) {
    // Retrieve the saved value
    $saved_value = get_post_meta($post->ID, '_show_type', true);

    // Reverse mapping to get the corresponding dropdown option
    $reverse_map = [
        'movies_audio_series' => 'movies_series',
        'video_audio_series' => 'video_series',
        'tv_show_audio_series' => 'tv_show_series',
    ];

    $show_type = $reverse_map[$saved_value] ?? 'tv_show_series'; // Default to 'TV Show Series'

    // Security nonce for verification
    wp_nonce_field('save_show_type_meta', 'show_type_nonce');

    ?>
    <label for="show_type"><?php _e('Select Show Type:', STA_TEXT_DOMAIN); ?></label>
    <select name="show_type" id="show_type">
        <option value="tv_show_series" <?php selected($show_type, 'tv_show_series'); ?>>TV Show Audio Series</option>
        <option value="movies_series" <?php selected($show_type, 'movies_series'); ?>>Movies Audio Series</option>
        <option value="video_series" <?php selected($show_type, 'video_series'); ?>>Video Audio Series</option>
    </select>
    <?php
}

function save_show_type_meta($post_id) {
    // Verify the nonce
    if (!isset($_POST['show_type_nonce']) || !wp_verify_nonce($_POST['show_type_nonce'], 'save_show_type_meta')) {
        return;
    }

    // Stop execution for autosave or unauthorized users
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Map the dropdown values to stored values
    $allowed_values = [
        'movies_series' => 'movies_audio_series',
        'video_series' => 'video_audio_series',
        'tv_show_series' => 'tv_show_audio_series',
    ];

    // Save the selected value
    if (isset($_POST['show_type']) && array_key_exists($_POST['show_type'], $allowed_values)) {
        update_post_meta($post_id, '_show_type', $allowed_values[$_POST['show_type']]);
    }
}
add_action('save_post_tv_show', 'save_show_type_meta');


// Add a new column for Show Type
function add_show_type_column($columns) {
    $columns['show_type'] = __('Show Type', STA_TEXT_DOMAIN);
    return $columns;
}
add_filter('manage_tv_show_posts_columns', 'add_show_type_column');

// Display the correct Show Type value in the column
function show_type_column_content($column, $post_id) {
    if ($column === 'show_type') {
        $show_type = get_post_meta($post_id, '_show_type', true);

        // Map stored values to readable labels
        $display_values = [
            'movies_audio_series' => __('Movies Audio Series', STA_TEXT_DOMAIN),
            'video_audio_series' => __('Video Audio Series', STA_TEXT_DOMAIN),
            'tv_show_audio_series' => __('TV Show Audio Series', STA_TEXT_DOMAIN),
        ];

        echo $display_values[$show_type] ?? __('TV Show Series', STA_TEXT_DOMAIN);
    }
}
add_action('manage_tv_show_posts_custom_column', 'show_type_column_content', 10, 2);

// Hide Post Type's Video & Movie
function exclude_hidden_post_types($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type')) {
        $hidden_post_types = ['video', 'movie'];
        $post_types = (array) $query->get('post_type'); // Ensure it's an array

        $filtered_post_types = array_diff($post_types, $hidden_post_types);

        if (empty($filtered_post_types)) {
            $filtered_post_types = ['post'];
        }

        // If there's only one post type left, convert it to a string
        if (count($filtered_post_types) === 1) {
            $filtered_post_types = reset($filtered_post_types);
        }

        $query->set('post_type', $filtered_post_types);
    }
}
add_action('pre_get_posts', 'exclude_hidden_post_types');


function hide_custom_post_types_from_admin() {
    remove_menu_page('edit.php?post_type=video');
    remove_menu_page('edit.php?post_type=movie');
}
add_action('admin_menu', 'hide_custom_post_types_from_admin', 999);
