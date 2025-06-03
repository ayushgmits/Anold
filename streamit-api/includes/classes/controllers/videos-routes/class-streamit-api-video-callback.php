<?php

class St_Video_Route_Callback
{

    /**
     * Get Video List.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function videos_list(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);

        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $video_array    = array();
        $parameters     = $request->get_params();
        $page           = $parameters['page'] ?? 1;
        $per_page       = $parameters['per_page'] ?? 10;
        $args           = [
            'post_status'       => 'publish',
            'posts_per_page'    => $per_page,
            'paged'             => $page
        ];

        $videos = streamit_get_videos($args);
        if (!empty($videos['results']) && count($videos) > 0) {
            foreach ($videos['results'] as $video) {
                $video_array[] = st_video_content_format($video);
            }
        }

        if (empty($video_array))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('No videos found.', 'streamit-api'),
                'data'      => []
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('Video list.', 'streamit-api'),
            'data'      => $video_array
        ]);
    }

    /**
     * Get Video Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function video_details(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);

        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];

        $parameters = $request->get_params();
        $response   = [];
        $video_id   = $parameters['video_id'];
        $video_data = streamit_get_video((int)$video_id);
        if (empty($video_data))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('No details found.', 'streamit-api'),
                'data'      => []
            ]);

        if (function_exists('streamit_update_video_view_count'))
            streamit_update_video_view_count((int)$video_id);

        $response   = st_video_content_format($video_data, false);

        //upcoming-video
        $upcoming_videos    = [];
        $arg = array(
            'post_type'        => 'video',
            'post_status'       => 'publish',
            'posts_per_page'    => 4,
            'exclude'           => array($video_id),
            'meta_query'        => array(
                array(
                    'key'     => 'name_upcoming',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );
        $upcoming = streamit_get_videos($arg);
        foreach ($upcoming['results'] as $video) {
            $upcoming_videos[] = st_video_content_format($video);
        }

        return st_comman_custom_response([
            'status' => true,
            'message' => esc_html__('Movie Details.', 'streamit-api'),
            'data' =>  [
                'details'               => $response,
                'upcoming_videos'       => $upcoming_videos
            ]
        ]);
    }
}
