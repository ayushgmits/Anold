<?php

add_action('wp_ajax_get_st_admin_data', 'getSTAdminRequest');
add_action('wp_ajax_post_st_admin_data', 'postSTAdminData');

/**************************************************************************************************************
 * Post Method Are Here
 * Home key : 'streamit_app_home'
 * Movie key : 'streamit_app_movie'
 * TV Show key : 'streamit_app_tv_show'
 * Video key : 'streamit_app_video'
 */

function postSTAdminData()
{
    $status = false;
    $fields = [];
    if (
        isset($_POST['action'])
        && isset($_POST['_ajax_nonce'])
        && wp_verify_nonce($_POST['_ajax_nonce'], 'get_st_admin_settings')
        && 'post_st_admin_data' === $_POST['action']
    ) {

        $fields = isset($_POST['fields']) ? $_POST['fields'] : [];
        switch ($_POST['type']) {
            case 'dashboard_setting':
                $status = saveStDashboardData($fields);
                break;
            case 'movie_setting':
                $status = saveStMovieData($fields, 'streamit_app_movie');
                break;
            case 'tv_show_setting':
                $status = saveStTVShowData($fields);
                break;
            case 'video_setting':
                $status = saveStMovieData($fields, 'streamit_app_video');
                break;
            case 'live_tv_setting':
                $status = saveStLiveTvData($fields);
                break;
        }
    }
    wp_send_json(['status' => $status, 'data' => []]);
}

function arrangeStData($data, $banner_key = 'banner_movie_show')
{
    $result = [];


    if (count($data[$banner_key]) > 0) {
        foreach ($data[$banner_key] as $index => $banner) {
            if (!empty($banner)) {
                if (empty($result['banner'])) {
                    $result['banner'] = [];
                }
                $result['banner'][] = [
                    'show' => $banner,
                    'attachment' => isset($data['banner_image']) && isset($data['banner_image'][$index]) ? $data['banner_image'][$index] : ''
                ];
            }
        }
    }
    if (count($data['sub_title']) > 0) {
        foreach ($data['sub_title'] as $index => $title) {
            if (!empty($title)) {
                if (empty($result['sliders'])) {
                    $result['sliders'] = [];
                }
                $result['sliders'][] = [
                    'title' => $title,
                    'genre' => !empty($data['sub_genre']) && !empty($data['sub_genre'][$index]) ? $data['sub_genre'][$index] : [],
                    'tag' => !empty($data['sub_tag']) && !empty($data['sub_tag'][$index]) ? $data['sub_tag'][$index] : [],
                    'cat' => !empty($data['sub_cat']) && !empty($data['sub_cat'][$index]) ? $data['sub_cat'][$index] : [],
                    'filter' => !empty($data['filter']) && !empty($data['filter'][$index]) ? $data['filter'][$index] : 'none',
                    'select_movie_show' => !empty($data['select_movie_show']) && !empty($data['select_movie_show'][$index]) ? $data['select_movie_show'][$index] : [],
                    'select_live_tv_channels' => !empty($data['select_live_tv_channels']) && !empty($data['select_live_tv_channels'][$index]) ? $data['select_live_tv_channels'][$index] : [],
                    'view_all' => isset($data['view_all']) && isset($data['view_all'][$index]) ? $data['view_all'][$index] : '',
                ];
            }
        }
    }

    return $result;
}

function saveStDashboardData($data)
{
    $status = false;
    $dashboardData = arrangeStData($data);
    $old_options = get_option('streamit_app_home');
    if ($dashboardData !== $old_options) {
        // update new settings
        $status = update_option('streamit_app_home', $dashboardData);
    } else if ($dashboardData === $old_options) {
        // for same data
        $status = true;
    }
    return $status;
}

function saveStMovieData($data, $type)
{
    $status = false;
    $dashboardData = arrangeStData($data);

    $old_options = get_option($type);
    if ($dashboardData !== $old_options) {
        // update new settings
        $status = update_option($type, $dashboardData);
    } else if ($dashboardData === $old_options) {
        // for same data
        $status = true;
    }
    return $status;
}

function saveStTVShowData($data)
{
    $status = false;
    $dashboardData = arrangeStData($data);
    $old_options = get_option('streamit_app_tv_show');
    if ($dashboardData !== $old_options) {
        // update new settings
        $status = update_option('streamit_app_tv_show', $dashboardData);
    } else if ($dashboardData === $old_options) {
        // for same data
        $status = true;
    }
    return $status;
}
function saveStLiveTvData($data)
{
    $status = false;
    $dashboardData = arrangeStData($data, 'banner_live_tv_cahnnels');

    $old_options = get_option('streamit_app_live_tv');
    if ($dashboardData !== $old_options) {
        // update new settings
        $status = update_option('streamit_app_live_tv', $dashboardData);
    } else if ($dashboardData === $old_options) {
        // for same data
        $status = true;
    }
    return $status;
}
/**************************************************************************************************************
 * Get Methods Are Here
 */

function getSTAdminRequest()
{
    $status = false;
    $result = [];
    if (
        isset($_POST['action'])
        && isset($_POST['_ajax_nonce'])
        && wp_verify_nonce($_POST['_ajax_nonce'], 'get_st_admin_settings')
        && 'get_st_admin_data' === $_POST['action']
    ) {

        $status = true;
        switch ($_POST['type']) {
            case 'all_movie_tv_show':
                $result = getSTMovieTvShowList();
                break;
            case 'all_genres':
                $result = getSTGenreList();
                break;
            case 'all_tags':
                $result = getSTTagList();
                break;
        }
    }
    wp_send_json(['status' => $status, 'data' => $result]);
}

function getSTMovieTvShowList()
{
    $movie = getSTMovieList(true);
    $tvShow = getSTTVShowList(true);
    $video = getSTVideoList(true);
    $live_tv = getSTLiveTVList(true);
    $tvShow = $tvShow->merge($video,);
    $tvShow = $tvShow->merge($live_tv);
    return $movie->merge($tvShow);
}

function getSTGenreList()
{
    $movie = getSTMovieGenreList(true);
    $tvShow = getSTTVShowGenreList(true);
    $live_tv = getSTLiveTVCategoryList(true);
    $tvShow = $tvShow->merge($live_tv);

    return $movie->merge($tvShow);
}

function getSTTagList()
{
    $movie = getSTMovieTagList(true);
    $tvShow = getSTTVShowTagList(true);
    $video = getSTVideoTagList(true);
    $tvShow = $tvShow->merge($video);
    return $movie->merge($tvShow);
}

function getSTMovieList($addFix = false)
{
    $args = array(
        'post_type' => 'movie',
        'posts_per_page' => -1
    );
    $movies = get_posts($args);
    return collect($movies)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->ID, 'text' => $res->post_title . ($addFix ? esc_html__(' ( Movie )') : '')];
    });
}
function getSTLiveTVList($addFix = false)
{
    $args = array(
        'post_type' => 'live_tv',
        'posts_per_page' => -1
    );
    $live_tv = get_posts($args);
    return collect($live_tv)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->ID, 'text' => $res->post_title . ($addFix ? esc_html__(' ( Live TV )') : '')];
    });
}

// function getSTTVShowList($addFix = false)
// {
//     $args = array(
//         'post_type' => 'tv_show',
//         'posts_per_page' => -1
//     );
//     $tvShow = get_posts($args);
//     return collect($tvShow)->map(function ($res, $key) use ($addFix) {
//         return ['value' => $res->ID, 'text' => $res->post_title . ($addFix ? esc_html__(' ( TV Show )') : '')];
//     });
// }

function getSTTVShowList($addFix = false, $showType = 'movies_audio_series')
{
    $args = array(
        'post_type'      => 'tv_show',
        'posts_per_page' => -1,
    );
    
    if (!empty($showType)) {
        $args['meta_query'] = array(
            array(
                'key'     => '_show_type',
                'value'   => $showType,
                'compare' => '=',
            ),
        );
    }
    
    $tvShow = get_posts($args);
    
    return collect($tvShow)->map(function ($res, $key) use ($addFix) {
        return [
            'value' => $res->ID,
            'text'  => $res->post_title . ($addFix ? esc_html__(' ( TV Show )') : ''),
        ];
    });
}

function getSTVideoList($addFix = false)
{
    $args = array(
        'post_type' => 'video',
        'posts_per_page' => -1
    );
    $video = get_posts($args);
    return collect($video)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->ID, 'text' => $res->post_title . ($addFix ? esc_html__(' ( Video )') : '')];
    });
}

function getSTVideoTagList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'video_tag',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $video = $categories->terms;

    return collect($video)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( Video )') : '')];
    });
}

function getSTVideoCategoryList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'video_cat',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $video = $categories->terms;

    return collect($video)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( Video )') : '')];
    });
}

function getSTMovieGenreList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'movie_genre',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $movie = $categories->terms;

    return collect($movie)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( Movie )') : '')];
    });
}
function getSTLiveTVCategoryList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'live_tv_cat',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $movie = $categories->terms;

    return collect($movie)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( Live Tv )') : '')];
    });
}

function getSTTVShowGenreList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'tv_show_genre',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $tvShow = $categories->terms;

    return collect($tvShow)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( TV Show )') : '')];
    });
}

function getSTMovieTagList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'movie_tag',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $movie = $categories->terms;

    return collect($movie)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( Movie )') : '')];
    });
}

function getSTTVShowTagList($addFix = false)
{
    $show_count = 0; // 1 for yes, 0 for no
    $pad_counts = 0; // 1 for yes, 0 for no
    $hierarchical = 1; // 1 for yes, 0 for no
    $args = [
        'taxonomy' => 'tv_show_tag',
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'hide_empty' => false,
        'parent' => 0
    ];
    $categories = new WP_Term_Query($args);
    $tvShow = $categories->terms;

    return collect($tvShow)->map(function ($res, $key) use ($addFix) {
        return ['value' => $res->term_id, 'text' => $res->name . ($addFix ? esc_html__(' ( TV Show )') : '')];
    });
}

function getSTFilterList()
{
    $filters = [
        [
            'value' => 'none',
            'text' => esc_html__('None')
        ],
        [
            'value' => 'latest',
            'text' => esc_html__('Latest')
        ],
        [
            'value' => 'upcoming',
            'text' => esc_html__('Upcoming')
        ],
        [
            'value' => 'most_liked',
            'text' => esc_html__('Most Liked')
        ],
        [
            'value' => 'most_viewed',
            'text' => esc_html__('Most Viewed')
        ],
        [
            'value' => 'top_ten',
            'text' => esc_html__('Top 10')
        ]
    ];
    return collect($filters);
}
