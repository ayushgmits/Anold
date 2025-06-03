<?php

class St_Cast_Route_Callback
{

    /**
     * Get Person Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function person_details(WP_REST_Request $request)
    {
        $parameters = $request->get_params();

        $cast_id    = $parameters['cast_id'];
        $most_viewed_content         = [];
        $response                     = null;

        $person_data = streamit_get_person((int)$cast_id);
        if (is_wp_error($person_data) || empty($person_data))
            return st_comman_custom_response(array(
                'status'    => true,
                'message'    => esc_html__('Details not found.', 'streamit-api'),
                'data'         => (object) []
            ));

        $term_name = array();
        $term_list      = streamit_get_term_relationships($cast_id, 'person_category');
        if (!is_wp_error($term_list) && !empty($term_list)) {
            foreach ($term_list as $term_id) {
                $term = streamit_get_term($term_id, 'person_category');
                $term_name[] = $term->term_name;
            }
        }

        $movie_cast      = (array) streamit_get_person_meta($cast_id, '_movie_cast', true);
        $movie_crew      = (array) streamit_get_person_meta($cast_id, '_movie_crew', true);
        $tv_show_cast    = (array) streamit_get_person_meta($cast_id, '_tv_show_cast', true);
        $tv_show_crew    = (array) streamit_get_person_meta($cast_id, '_tv_show_crew', true);

        $birthday        = streamit_get_person_meta($cast_id, '_birthday', true);
        $deathday        = streamit_get_person_meta($cast_id, '_deathday', true);
        $image_url       = wp_get_attachment_image_src(streamit_get_person_meta($cast_id, 'thumbnail_id'), 'full');


        $movie_cast   = array_filter($movie_cast);
        $movie_crew   = array_filter($movie_crew);
        $tv_show_cast = array_filter($tv_show_cast);
        $tv_show_crew = array_filter($tv_show_crew);

        $all_ids = array_merge($movie_cast, $movie_crew, $tv_show_cast, $tv_show_crew);
        $credits = count($all_ids);

        $response = [
            'id'                => $cast_id,
            'title'             => $person_data->post_title,
            'description'       => wp_strip_all_tags($person_data->post_content),
            'image'             => !empty($image_url) ? $image_url[0] : '',
            'category'          => $term_name,
            'credits'           => $credits,
            'also_known_as'     => streamit_get_person_meta($cast_id, '_also_known_as', true),
            'place_of_birth'    => streamit_get_person_meta($cast_id, '_place_of_birth', true),
            'birthday'          => $birthday,
            'deathday'          => $deathday,
        ];

        $most_viewed_content = array();
        if (!empty($movie_cast)) {
            foreach ($movie_cast as $movie_id) {
                $movie_data = streamit_get_movie((int)$movie_id);
                if (!is_wp_error($movie_data) && !empty($movie_data)) {
                    $most_viewed_content[] = $this->streamit_person_work_history($movie_data,  $cast_id, '_cast');
                }
            }
        }
        if (!empty($movie_crew)) {
            foreach ($movie_crew as $movie_id) {
                $movie_data = streamit_get_movie((int)$movie_id);
                if (!is_wp_error($movie_data) && !empty($movie_data)) {
                    $most_viewed_content[] = $this->streamit_person_work_history($movie_data,  $cast_id, '_crew');
                }
            }
        }
        if (!empty($tv_show_cast)) {
            foreach ($tv_show_cast as $tvshow_id) {
                $tvshow_data = streamit_get_tvshow((int)$tvshow_id);
                if (!is_wp_error($tvshow_data) && !empty($tvshow_data)) {
                    $most_viewed_content[] = $this->streamit_person_work_history($tvshow_data, $cast_id, '_cast');
                }
            }
        }
        if (!empty($tv_show_crew)) {
            foreach ($tv_show_cast as $tvshow_id) {
                $tvshow_data = streamit_get_tvshow((int)$tvshow_id);
                if (!is_wp_error($tvshow_data) && !empty($tvshow_data)) {
                    $most_viewed_content[] = $this->streamit_person_work_history($tvshow_data, $cast_id, '_crew');
                }
            }
        }
        return st_comman_custom_response(array(
            'status'    => true,
            'message'    => esc_html__('Cast details.', 'streamit-api'),
            'data'         => [
                'details'                 => $response,
                'most_viewed_content'     => $most_viewed_content
            ]
        ));
    }

    /**
     * Get Person Work History.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function person_work_history(WP_REST_Request $request)
    {
        $response             = [];
        $parameters         = $request->get_params();
        $cast_id             = $parameters['cast_id'];
        $cats_data  = streamit_get_person((int) $cast_id);
        if ($cats_data === null || empty($cats_data))
            return st_comman_custom_response(array(
                'status'    => true,
                'message'    => esc_html__('No data found.', 'streamit-api'),
                'data'         => []
            ));

        $type            = $parameters['type'] ?? "all";
        $movie_cast      = (array) streamit_get_person_meta($cast_id, '_movie_cast', true);
        $movie_crew      = (array) streamit_get_person_meta($cast_id, '_movie_crew', true);
        $tv_show_cast    = (array) streamit_get_person_meta($cast_id, '_tv_show_cast', true);
        $tv_show_crew    = (array) streamit_get_person_meta($cast_id, '_tv_show_crew', true);

        $movie_cast   = array_filter($movie_cast);
        $movie_crew   = array_filter($movie_crew);
        $tv_show_cast = array_filter($tv_show_cast);
        $tv_show_crew = array_filter($tv_show_crew);

        $response = array();
        if (!empty($movie_cast)) {
            foreach ($movie_cast as $movie_id) {
                $movie_data = streamit_get_movie((int)$movie_id);
                if (!is_wp_error($movie_data) && !empty($movie_data)) {
                    $response[] = $this->streamit_person_work_history($movie_data,  $cast_id, '_cast');
                }
            }
        }
        if (!empty($movie_crew)) {
            foreach ($movie_crew as $movie_id) {
                $movie_data = streamit_get_movie((int)$movie_id);
                if (!is_wp_error($movie_data) && !empty($movie_data)) {
                    $response[] = $this->streamit_person_work_history($movie_data,  $cast_id, '_crew');
                }
            }
        }
        if (!empty($tv_show_cast)) {
            foreach ($tv_show_cast as $tvshow_id) {
                $tvshow_data = streamit_get_tvshow((int)$tvshow_id);
                if (!is_wp_error($tvshow_data) && !empty($tvshow_data)) {
                    $response[] = $this->streamit_person_work_history($tvshow_data, $cast_id, '_cast');
                }
            }
        }
        if (!empty($tv_show_crew)) {
            foreach ($tv_show_cast as $tvshow_id) {
                $tvshow_data = streamit_get_tvshow((int)$tvshow_id);
                if (!is_wp_error($tvshow_data) && !empty($tvshow_data)) {
                    $response[] = $this->streamit_person_work_history($tvshow_data, $cast_id, '_crew');
                }
            }
        }

        if (count($response) > 0)
            return st_comman_custom_response([
                'status'    => true,
                'message'    => esc_html__('Details.', 'streamit-api'),
                'data'         =>  $response
            ]);

        return st_comman_custom_response([
            "status"    => true,
            "message"    => esc_html__('No data found.', 'streamit-api'),
            "data"         =>  []
        ]);
    }

    public function streamit_person_work_history($post_data, $person_id, $meta_key)
    {
        $thubnail_function  = 'streamit_get_' . $post_data->post_type . '_meta';
        $thubnail_image = function_exists($thubnail_function) ? $thubnail_function($post_data->ID, 'thumbnail_id') : '';
        $image = wp_get_attachment_image_src($thubnail_function, [300, 300]);
        $permalink = get_option("streamit_{$post_data->post_type}_slug");
        $shareurl = '';
        if (!is_wp_error($permalink)) {
            $slug = !empty($permalink) ? $permalink : $post_data->post_type;
            $slug = sanitize_title($slug);
            $post_slug = sanitize_title($post_data->post_name);
            $shareurl = home_url("{$slug}/{$post_slug}");
        }


        $character_name = st_cast_details($post_data->ID, $post_data->post_type,  $meta_key, $person_id);
        $temp = [
            'id'                => $post_data->ID,
            'title'             => $post_data->post_title,
            'image'             => !empty($image) ? $image[0] : null,
            'post_type'         => $post_data->post_type,
            'character_name'    => is_array($character_name) ? $character_name : '',
            'share_url'         => $shareurl
        ];

        if ($post_data->post_type  == 'tvshow') {
            $tv_show_season = streamit_get_tvshow_meta($post_data->ID, '_seasons', true);
            $temp['total_seasons'] = 0;
            $year = null;
            if (!empty($tv_show_season)) {
                $temp['total_seasons'] = count($tv_show_season);
            }
            $temp['release_year'] = $year;
        } else {
            $temp['release_year']     = streamit_get_movie_meta($post_data->ID, '_movie_release_date', true);
            $temp['run_time']         = streamit_get_movie_meta($post_data->ID, '_movie_run_time', true);
        }

        return $temp;
    }
}
