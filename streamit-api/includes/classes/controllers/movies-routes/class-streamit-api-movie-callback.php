<?php

class St_Movie_Route_Callback
{

    /**
     * Get Movie Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function movie_details(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);

        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $response   = [];
        $parameters = $request->get_params();
        $movie_id   = $parameters['movie_id'];
        $movie = streamit_get_movie((int)$movie_id);

        if (function_exists('streamit_update_movie_view_count'))
            streamit_update_movie_view_count((int)$movie_id);

        if (empty($movie))
            return st_comman_custom_response([
                "status" => true,
                "message" => esc_html__("No details found.", 'streamit-api'),
                "data" => []
            ]);

        $response = st_movie_content_format($movie, false);

        $recommended_movies = $this->recommended_movies_helper($movie_id);

        //upcoming-movies
        $upcoming_movies    = [];
        $arg = array(
            'post_type'        => 'movie',
            'post_status'       => 'publish',
            'posts_per_page'    => 4,
            'exclude'           => array($movie_id),
            'meta_query'        => array(
                array(
                    'key'     => 'name_upcoming',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );

        $upcoming = streamit_get_movies($arg);
        foreach ($upcoming['results'] as $movie) {
            $upcoming_movies[] = st_movie_content_format($movie);
        }

        return st_comman_custom_response([
            'status' => true,
            'message' => esc_html__('Movie Details.', 'streamit-api'),
            'data' =>  [
                'details'               => $response,
                'recommended_movies'    => $recommended_movies,
                'upcoming_movies'       => $upcoming_movies
            ]
        ]);
    }

    /**
     * Recommended Movie List.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function recommended_movies(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);
        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $parameters             = $request->get_params();
        $movie_id               = $parameters['movie_id'];
        $posts_per_page         = $parameters["posts_per_page"] ?? 10;
        $page                   = $parameters["page"] ?? 10;
        $args  = array(
            "user_id"           => $user_id,
            "posts_per_page"    => $posts_per_page,
            "page"              => $page
        );

        $recommended_movies = $this->recommended_movies_helper($movie_id, $args);

        if (empty($recommended_movies))
            return st_comman_custom_response([
                'status' => true,
                'message' => esc_html__('No movies found.', 'streamit-api'),
                'data' => []
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('Recommended movies.', 'streamit-api'),
            'data'      => $recommended_movies
        ]);
    }

    public function recommended_movies_helper($movie_id, $args = [])
    {
        $recommended_movies_list = array();
        $recommended_movies_ides = streamit_get_movie_meta($movie_id, 'linked_recommended_movie_ids');
        if (empty($recommended_movies_ides)) {
            return $recommended_movies_list;
        }

        $args = wp_parse_args(
            $args,
            [
                'post_stype' => 'movie',
                'per_page' => 4,
                'paged' => 1,
                'include' => $recommended_movies_ides,
            ]
        );

        $recommended_movies =  streamit_get_movies($args, true);
        if (empty($recommended_movies['results'] || !array($recommended_movies['results']))) {
            return $recommended_movies_list;
        }

        foreach ($recommended_movies['results'] as $movie) {
            $recommended_movies_list[] = st_movie_content_format($movie);
        }

        return $recommended_movies_list;
    }
}
