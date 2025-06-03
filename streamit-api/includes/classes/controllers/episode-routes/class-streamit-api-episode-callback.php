<?php

class St_Episode_Route_Callback
{

    /**
     * Get Episodes Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function episode_details(WP_REST_Request $request)
    {
        $data       = st_token_validation($request);

        $user_id    = null;
        if ($data['status'])
            $user_id = $data['user_id'];


        $response   = [];
        $parameters = $request->get_params();
        $episode_id = $parameters['episode_id'] ?? 0;

        $data = streamit_get_episode($episode_id);
        $response = st_episode_content_format($data, false);

        if (empty($response))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('No data found.', 'streamit-api'),
                'data'      => (object) []
            ]);

        if (function_exists('streamit_update_episode_view_count'))
            streamit_update_episode_view_count((int)$episode_id);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('Episode Details', 'streamit-api'),
            'data'      => $response
        ]);
    }
}
