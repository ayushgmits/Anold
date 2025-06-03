<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve data (movies, videos, and/or TV shows) with ID and title based on the provided type.
 *
 * @param string|null $type Optional. The type of media to retrieve ('movie', 'video', 'tvshow').
 *                          If null, it retrieves all types.
 * @return array $all_data Associative array of media ID => title (with type).
 */
function streamit_api_get_all_data($type = '') {
    // Initialize an empty array to store media data.
    $all_data = array();

    // Get movies if type is 'movie' or if no type is specified (null).
    if ($type === 'movie' || $type === '') {
        $movies = function_exists('streamit_get_movies') ? streamit_get_movies(array('per_page' => -1)) : '';
        if (!is_wp_error($movies) && !empty($movies->results)) {
            foreach ($movies->results as $movie) {
                $all_data['movie_'.$movie->get_id()] = $movie->get_post_title() . ' - Movie';
            }
        }
    }

    // Get videos if type is 'video' or if no type is specified (null).
    if ($type === 'video' || $type === '') {
        $videos = function_exists('streamit_get_videos') ? streamit_get_videos(array('per_page' => -1)) : '';
        if (!is_wp_error($videos) && !empty($videos->results)) {
            foreach ($videos->results as $video) {
                $all_data['video_'.$video->get_id()] = $video->get_post_title() . ' - Video';
            }
        }
    }

    // Get TV shows if type is 'tvshow' or if no type is specified (null).
    if ($type === 'tvshow' || $type === '') {
        $tvshows = function_exists('streamit_get_tvshows') ? streamit_get_tvshows(array('per_page' => -1)) : '';
        if (!is_wp_error($tvshows) && !empty($tvshows->results)) {
            foreach ($tvshows->results as $tvshow) {
                $all_data['tvshow_'.$tvshow->get_id()] = $tvshow->get_post_title() . ' - TV Show';
            }
        }
    }

    // Return the final array of all data.
    return !empty($all_data) ? $all_data : array(esc_html__('No Data found', 'streamit-api'));
}



/**
 * Retrieve a list of filters for the Streamit API.
 *
 * This function returns an associative array of filter options 
 * where each key is the filter value and each value is the 
 * corresponding display text.
 *
 * @return array $filters An associative array of filter options.
 */
function streamit_api_get_filter_list() {
    $filters = apply_filters('streamit_api_filter_list', [
        'none'        => esc_html__('None', 'streamit-api'),
        'latest'      => esc_html__('Latest', 'streamit-api'),
        'upcoming'    => esc_html__('Upcoming', 'streamit-api'),
        'most_liked'  => esc_html__('Most Liked', 'streamit-api'),
        'most_viewed' => esc_html__('Most Viewed', 'streamit-api'),
        'top_ten'     => esc_html__('Top 10', 'streamit-api'),
    ]);

    return $filters;
}
