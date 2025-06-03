<?php

class St_TVShow_Route_Callback
{

    /**
     * Get Movie Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function tv_show_details(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);

        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $response   = [];
        $parameters = $request->get_params();
        $tv_show_id = $parameters['tv_show_id'];

        $data = streamit_get_tvshow((int)$tv_show_id);
        if (!is_wp_error($data) && !empty($data))
            $response = st_tv_show_content_format($data, false);

        if (function_exists('streamit_update_tvshow_view_count'))
            streamit_update_tvshow_view_count((int)$tv_show_id);

        if (empty($response))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('No Details found.', 'streamit-api'),
                'data'      => (object) []
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('TV shows details.', 'streamit-api'),
            'data'      => ['details' => $response]
        ]);
    }


    /**
     * Get Movie Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function season(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);
        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];


        $parameters     = $request->get_params();
        $tv_show_id     = $parameters['tv_show_id'] ?? 0;
        $season_id      = $parameters['season_id'] ?? 0;
        $posts_per_page = $parameters['posts_per_page'] ?? 10;
        $page           = $parameters['page'] ?? 1;

        $season = $this->streamit_seasons_data([
            'tv_show_id'        => $tv_show_id,
            'season_id'         => $season_id,
            'user_id'           => $user_id,
            'posts_per_page'    => $posts_per_page,
            'page'              => $page
        ]);

        if (empty($season))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('No data found.', 'streamit-api'),
                'data'      => []
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('Season data.', 'streamit-api'),
            'data'      => $season
        ]);
    }

    public function streamit_seasons_data($args)
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
        $tv_show_seasons    = streamit_get_tvshow_meta($args['tv_show_id'], '_seasons');
        $seasons_data       = $tv_show_seasons[$args['season_id']] ?? 0;

        if (!$seasons_data || empty($seasons_data)) return [];

        $season = [
            'name'          => $seasons_data['name'],
            'description'   => $seasons_data['description'],
            'year'          => $seasons_data['year'],
            'position'      => $seasons_data['position']
        ];

        $full_image = wp_get_attachment_image_src($seasons_data['season_thumbnail'], [300, 300]);
        $season['image'] = $full_image && isset($full_image[0]) ? $full_image : null;


        if (count($seasons_data['episodes']) > 0) {
            $args = [
                'post_type'         => 'episode',
                'post_status'       => 'publish',
                'include'           => array_reverse($seasons_data['episodes']),
                'per_page'          => $args['posts_per_page'],
                'paged'             => $args['page'],
            ];

            $season['episodes'] = array();
            $episodes = streamit_get_episodes($args);
            if (!empty($episodes['results'])) {
                foreach ($episodes['results'] as $episode) {
                    $season['episodes'][] = st_episode_content_format($episode,);
                }
            }
        }

        return $season;
    }
}
