<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


function st_movie_content_format($data, $is_short = true)
{
    if (is_wp_error($data) || empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = streamit_get_movie_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? '';
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src($post_meta['thumbnail_id'][0], [300, 300]);
    $user_id            = get_current_user_id();
    $trailer_link       = !empty($post_meta['_name_trailer_img'][0]) ? $post_meta['_name_trailer_img'][0] : null;

    if ($is_short) {
        return [
            'id'               => $post_id,
            'title'            => $data->post_title,
            'image'            => !empty($image) ? $image[0] : null,
            'run_time'         => isset($post_meta['_movie_run_time']) ? $post_meta['_movie_run_time'][0] : '',
            'post_type'        => 'movie',
            'stream_type'      => isset($post_meta['_movie_choice']) ? $post_meta['_movie_choice'][0] : '',
            'trailer_link'     => $trailer_link,
            'trailer_link_type' => st_check_video_url_type($trailer_link),
        ];
    }

    $avg_rating     = isset($post_meta['_average_rating']) ? $post_meta['_average_rating'][0] : '';
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => 'movie',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags($data->post_excerpt) : '',
        'share_url'             => '', //get_the_permalink($post_id),
        'is_comment_open'       => $data->comment_status,
        'no_of_comments'        => $data->comment_count,
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => st_check_video_url_type($trailer_link),
        'is_liked'              => function_exists("streamit_get_like_count") ? streamit_is_like($post_id, $user_id, 'movie') : false,
        'likes'                 => function_exists("streamit_get_like_count") ? streamit_get_like_count($post_id, 'movie') : 0,
        'is_watchlist'          => function_exists("streamit_is_watchlist")   ? streamit_is_watchlist($user_id, $post_id, 'movie') : false,
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'embed_content'         => isset($post_meta['_movie_embed_content']) ? $post_meta['_movie_embed_content'][0] : '',
        'movie_choice'          => isset($post_meta['_movie_choice']) ? $post_meta['_movie_choice'][0] : '',
        'sources'               => isset($post_meta['_sources']) ? $post_meta['_sources'][0] : '',
        'url_link'              => isset($post_meta['_movie_url_link']) ? $post_meta['_movie_url_link'][0] : '',
        'genre'                 => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'movie_genre') : null,
        'tag'                   => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'movie_tag') : null,
        'run_time'              => isset($post_meta['_movie_run_time']) ? $post_meta['_movie_run_time'][0] : '',
        'censor_rating'         => isset($post_meta['_movie_censor_rating']) ? $post_meta['_movie_censor_rating'][0] : '',
        'release_date'          => isset($post_meta['_movie_release_date']) ? $post_meta['_movie_release_date'][0] : '',
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => st_cast_details($post_id, 'movie', '_cast'),
        'crews'                 => st_cast_details($post_id, 'movie', '_crew'),
        'comments'              => '',
        'is_password_protected' => null
    ];

    $movie_file         = isset($post_meta['_movie_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_movie_attachment_id'][0]) : null;
    $temp['movie_file'] = $movie_file ? $movie_file : null;

    $logo               = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = ''; //restrictedPlanList($post_id, $user_id);
    //$temp = '';//array_merge($temp, $plan_list);

    return $temp;
}


function st_video_content_format($data, $is_short = true)
{
    if (is_wp_error($data) || empty($data) || $data === null)
        return [];

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = streamit_get_video_meta($post_id);
    $portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? '';
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src($post_meta['thumbnail_id'][0], [300, 300]);
    $user_id            = get_current_user_id();
    $trailer_link       = isset($post_meta['_name_trailer_img']) ? $post_meta['_name_trailer_img'][0] : null;

    if ($is_short) {
        return [
            'id'            => $post_id,
            'title'         => $data->post_title,
            'image'         => !empty($image) ? $image[0] : null,
            'run_time'      => isset($post_meta['_video_run_time']) ? $post_meta['_video_run_time'][0] : '',
            'post_type'     => 'video',
            'stream_type'   => isset($post_meta['_video_run_time']) ? $post_meta['_video_choice'][0] : '',
            'trailer_link'  => $trailer_link,
            'trailer_link_type' => st_check_video_url_type($trailer_link),
        ];
    }

    $avg_rating     = isset($post_meta['_average_rating']) ? $post_meta['_average_rating'][0] : '';
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => 'video',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => $data->comment_status,
        'no_of_comments'        => $data->comment_count,
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => st_check_video_url_type($trailer_link),
        'is_liked'              => function_exists("streamit_get_like_count") ? streamit_is_like($post_id, $user_id, 'video') : false,
        'likes'                 => function_exists("streamit_get_like_count") ? streamit_get_like_count($post_id, 'video') : 0,
        'is_watchlist'          => function_exists("streamit_is_watchlist")   ? streamit_is_watchlist($user_id, $post_id, 'video') : false,
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'embed_content'         => isset($post_meta['_video_embed_content']) ? $post_meta['_video_embed_content'][0] : '',
        'video_choice'          => isset($post_meta['_video_choice']) ? $post_meta['_video_choice'][0] : '',
        'url_link'              => isset($post_meta['_video_url_link']) ? $post_meta['_video_url_link'][0] : '',
        'genre'                 => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'video_category') : null,
        'tag'                   => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'video_tag') : null,
        'run_time'              => isset($post_meta['_video_run_time']) ? $post_meta['_video_run_time'][0] : '',
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => st_cast_details($post_id, 'video', '_cast'),
        'crews'                 => st_cast_details($post_id, 'video', '_crew'),
        'comments'              => '',
        'is_password_protected' => ''
    ];

    $video_file         = isset($post_meta['_video_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_video_attachment_id'][0]) : null;
    $temp['video_file'] = $video_file ? $video_file : null;

    $logo               = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = ''; // Restricted plan logic for video can be added here
    //$temp = array_merge($temp, $plan_list); // Merge plan list if needed

    return $temp;
}


function st_tv_show_content_format($data, $is_short = true)
{
    if (is_wp_error($data) || empty($data) || $data === null) {
        return [];
    }

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = streamit_get_tvshow_meta($post_id);
    $portrait_image_id  = isset($post_meta['_portrait_thumbnail']) ? $post_meta['_portrait_thumbnail'][0] : '';
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src($post_meta['thumbnail_id'][0], [300, 300]);
    $user_id            = get_current_user_id();
    $trailer_link   = !empty($post_meta['name_trailer_link'][0]) ? $post_meta['name_trailer_link'][0] : null;

    if ($is_short) {
        return [
            'id'            => $post_id,
            'title'         => $data->post_title,
            'image'         => !empty($image) ? $image[0] : null,
            'post_type'     => 'tv_show',
        ];
    }

    $avg_rating    = isset($post_meta['_average_rating']) ? $post_meta['_average_rating'][0] : '';
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => 'tv_show',
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags($data->post_excerpt) : '',
        'share_url'             => '',//get_the_permalink($post_id),
        'is_comment_open'       => $data->comment_status,
        'no_of_comments'        => $data->comment_count,
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => st_check_video_url_type($trailer_link),
        'is_liked'              => function_exists("streamit_get_like_count") ? streamit_is_like($post_id, $user_id, 'tvshow') : false,
        'likes'                 => function_exists("streamit_get_like_count") ? streamit_get_like_count($post_id, 'tvshow') : 0,
        'is_watchlist'          => function_exists("streamit_is_watchlist")   ? streamit_is_watchlist($user_id, $post_id, 'tvshow') : false,
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['name_custom_imdb_rating'][0]) ? floatval($post_meta['name_custom_imdb_rating'][0]) / 2  : 0,
        'genre'                 => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'video_category') : null,
        'tag'                   => function_exists('streamit_get_term_by_post') ? streamit_get_term_by_post($post_id, 'video_tag') : null,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => st_cast_details($post_id, 'tvshow', '_cast'),
        'crews'                 => st_cast_details($post_id, 'tvshow', '_crew'),
        'comments'              => '',
        'is_password_protected' => ''
    ];

    $seasons            = isset($post_meta['_seasons']) ? $post_meta['_seasons'][0] : [];
    $total_seasons      = count($seasons);
    if ($total_seasons > 0) {
        $seasons_data = array();
        foreach($seasons as $season)
        {
            $seasons_data[] = array(
                'id'    => $season['id'],
                'name'  => $season['name']
            ); 
        }
        $temp['seasons'] = ["count" => $total_seasons, "data" => $seasons_data];
    } else {
        $temp['seasons'] = (object) [];
    }

    $logo           = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']   = $logo[0] ?? null;

    $plan_list          = ''; // Restricted plan logic for TV show can be added here
    //$temp = array_merge($temp, $plan_list); // Merge plan list if needed

    return $temp;
}

function st_episode_content_format($data, $is_short = true)
{
    if (is_wp_error($data) || empty($data) || $data === null) {
        return [];
    }

    $post_id            = $data->ID;
    $post_type          = $data->post_type;
    $post_meta          = streamit_get_episode_meta($post_id); // Adjusted to retrieve episode meta
    $portrait_image_id  = $post_meta['_portrait_thumbnail'][0] ?? '';
    $image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src($post_meta['thumbnail_id'][0], [300, 300]);

    if ($is_short) {
        return [
            'id'            => $post_id,
            'title'         => $data->post_title,
            'image'         => !empty($image) ? $image[0] : null,
            'post_type'     => $post_type,
            'season'        => $post_meta['_episode_season'][0] ?? '', // Adjusted to episode season meta
            'trailer_link'  => $post_meta['_episode_trailer_link'][0] ?? '',
            'trailer_link_type' => st_check_video_url_type($post_meta['_episode_trailer_link'][0] ?? ''),
            'episode_number' => $post_meta['_episode_number'][0] ?? '', // Adjusted to episode number
        ];
    }

    $avg_rating     = $post_meta['_masvideos_average_rating'][0] ?? 0; // Adjusted to episode rating meta
    $trailer_link   = $post_meta['_episode_trailer_link'][0] ?? null;
    $temp = [
        'id'                    => $post_id,
        'title'                 => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        'description'           => !empty($data->post_content) ? $data->post_content : '',
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'trailer_link'          => $trailer_link,
        'trailer_link_type'     => st_check_video_url_type($trailer_link),
        'is_liked'              => '', // Replace with episode like status
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_watchlist'          => '', // Replace with episode watchlist status
        'avg_rating'            => ($avg_rating === null ? 0 : $avg_rating),
        'imdb_rating'           => !empty($post_meta['custom_imdb_rating'][0]) ? floatval($post_meta['custom_imdb_rating'][0]) / 2 : 0,
        'embed_content'         => $post_meta['_episode_embed_content'][0] ?? '', // Adjusted to episode embed content
        'season'                => $post_meta['_episode_season'][0] ?? '', // Adjusted to episode season
        'episode_number'        => $post_meta['_episode_number'][0] ?? '', // Adjusted to episode number
        'url_link'              => $post_meta['_episode_url_link'][0] ?? '', // Adjusted to episode URL link
        'genre'                 => '', // Adjusted to episode genre
        'tag'                   => '', // Adjusted to episode tag
        'run_time'              => $post_meta['_episode_run_time'][0] ?? '', // Adjusted to episode runtime
        'censor_rating'         => $post_meta['_episode_censor_rating'][0] ?? '', // Adjusted to episode censor rating
        'release_date'          => date_i18n(get_option('date_format'), $post_meta['_episode_release_date'][0] ?? ''), // Adjusted to episode release date
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'casts'                 => '', // Adjusted to episode cast details
        'crews'                 => '', // Adjusted to episode crew details
        'comments'              => '', // Adjusted to episode comments
        'is_password_protected' => post_password_required($post_id)
    ];

    $video_file         = isset($post_meta['_episode_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_episode_attachment_id'][0]) : null;
    $temp['video_file'] = $video_file ? $video_file : null;

    $logo               = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = ''; // Restricted plan logic for episodes can be added here
    //$temp = array_merge($temp, $plan_list); // Merge plan list if needed

    return $temp;
}

function st_person_content_format($data, $is_short = true)
{
    if (is_wp_error($data) || empty($data) || $data === null) {
        return [];
    }

    $post_id   = $data->ID;
    $post_type = $data->post_type;
    $post_meta = streamit_get_person_meta($post_id); // Adjusted to retrieve person meta
    $portrait_image_id = $post_meta['_portrait_thumbnail'][0] ?? '';
    $image = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src($post_meta['thumbnail_id'][0], [300, 300]);

    if ($is_short) {
        return [
            'id'        => $post_id,
            'name'      => $data->post_title,
            'image'     => !empty($image) ? $image[0] : null,
            'post_type' => $post_type,
            'role'      => $post_meta['_person_role'][0] ?? '', // Adjusted to person role meta
        ];
    }

    $biography      = !empty($data->post_content) ? $data->post_content : '';
    $temp = [
        'id'                    => $post_id,
        'name'                  => $data->post_title,
        'image'                 => !empty($image) ? $image[0] : null,
        'post_type'             => $post_type,
        'biography'             => $biography,
        'excerpt'               => !empty($data->post_excerpt) ? wp_strip_all_tags(get_the_excerpt($data)) : '',
        'share_url'             => get_the_permalink($post_id),
        'is_comment_open'       => comments_open($post_id),
        'no_of_comments'        => (int) get_comments_number($post_id),
        'is_liked'              => '', // Replace with person like status
        'likes'                 => function_exists("wp_ulike_get_post_likes") ? wp_ulike_get_post_likes($post_id) : 0,
        'is_watchlist'          => '', // Replace with person watchlist status
        'imdb_rating'           => !empty($post_meta['custom_imdb_rating'][0]) ? floatval($post_meta['custom_imdb_rating'][0]) / 2 : 0,
        'cast_credits'          => '', // Adjusted to person cast credits
        'crew_credits'          => '', // Adjusted to person crew credits
        'url_link'              => $post_meta['_person_url_link'][0] ?? '', // Adjusted to person URL link
        'birth_date'            => !empty($post_meta['_person_birth_date'][0]) ? date_i18n(get_option('date_format'), $post_meta['_person_birth_date'][0]) : '', // Adjusted to person birth date
        'views'                 => isset($post_meta['post_views_count']) ? (int) $post_meta['post_views_count'][0] : 0,
        'publish_date'          => $data->post_date,
        'publish_date_gmt'      => $data->post_date_gmt,
        'is_password_protected' => post_password_required($post_id)
    ];

    $video_file         = isset($post_meta['_person_attachment_id'][0]) ? wp_get_attachment_url($post_meta['_person_attachment_id'][0]) : null;
    $temp['video_file'] = $video_file ? $video_file : null;

    $logo               = isset($post_meta['name_logo']) ? wp_get_attachment_image_src($post_meta['name_logo'][0], [300, 300]) : null;
    $temp['logo']       = $logo[0] ?? null;

    $plan_list          = ''; // Restricted plan logic for persons can be added here
    //$temp = array_merge($temp, $plan_list); // Merge plan list if needed

    return $temp;
}

//check if video url type is youtube | vimeo | other 
function st_check_video_url_type($url)
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


function st_cast_details($post_id, $post_type, $meta_key = '_cast', $cast_id = null)
{
    $post_meta = 'streamit_get_' . $post_type . '_meta';
    $cast_list = function_exists($post_meta) ? $post_meta($post_id, $meta_key) : [];

    // If the cast list is not empty, process the data.
    if (!empty($cast_list) && is_array($cast_list)) {
        // Initialize arrays for character names and cast data.
        $character = [];
        $cast_data = [];

        // Loop through the cast list.
        foreach ($cast_list as $cast) {
            // If a specific cast ID is provided, check for matches.
            if ($cast_id !== null) {
                if ($cast['id'] == $cast_id) {
                    $character[] = $cast['character'];
                }
            } else {
                $person_details = streamit_get_person((int)$cast['id']);
                $person_name = !is_wp_error($person_details->post_title) ? $person_details->post_title : "";
                $cast_image = wp_get_attachment_image_src(streamit_get_person_meta($cast['id'], 'thumbnail_id'), [300, 300]);
                $cast_data[] = [
                    'id' => $cast['id'],
                    'image' => !empty($cast_image) ? $cast_image[0] : null,
                    'name' =>  $person_name,
                ];
            }
        }

        // Return character names if a specific cast ID is provided, otherwise return full cast data.
        return $cast_id !== null ? $character : $cast_data;
    }

    return []; // Return an empty array if no cast data is found.
}
