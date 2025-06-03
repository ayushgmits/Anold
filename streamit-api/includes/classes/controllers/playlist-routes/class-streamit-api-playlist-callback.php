<?php

use Illuminate\Support\Arr;

class St_Playlist_Route_Callback
{

    /**
     * Get Playlist Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function get_playlist(WP_REST_Request $request)
    {

        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $user_id    = $data['user_id'];
        $parameters = $request->get_params();
        $post_id    = $parameters["post_id"] ?? 0;

        $post_type  = $parameters['post_type'] ?? false;
        if (!$post_type || !in_array($post_type, ['movie_playlist', 'tv_show_playlist', 'video_playlist']))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlists not found.', 'streamit-api'),
                'data'      => []
            ]);

        $args = array(
            'user_id'     => $user_id,
            'playlist_id' => $post_id
        );

        $playlist_function = ($post_type == 'tv_show_playlist') ? 'streamit_get_tvshow_playlists' : 'streamit_get_' . $post_type . 's';
        $playlists = $playlist_function($args);
        if (!$playlists['results'])
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlists not found.', 'streamit-api'),
                'data'      => []
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => esc_html__('Playlists.', 'streamit-api'),
            'data'      => $playlists
        ]);
    }

    /**
     * Creat Playlist Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function create_playlist(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $parameters = $request->get_params();
        $post_type  = $parameters['post_type'] ?? false;

        if (!$post_type || !in_array($post_type, ['movie_playlist', 'tv_show_playlist', 'video_playlist']))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlist type not found.', 'streamit-api'),
                'data'      => []
            ]);

        if (!isset($parameters['title']) || empty($parameters['title'])) {
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Please Add Playlist Name.', 'streamit-api'),
                'data'      => []
            ]);
        }
        $playlist_function = ($post_type == 'tv_show_playlist') ? 'streamit_add_tvshow_playlist' : 'streamit_add_' . $post_type;
        $args = array(
            'playlist_name'   => $parameters['title'],
            'user_id'         => $data['user_id']
        );
        $playlist = $playlist_function($args);

        if (!is_wp_error($playlist))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlist saved successfully.', 'streamit-api')
            ]);

        return st_comman_custom_response([
            'status'    => false,
            'message'   => esc_html__('Something went wrong. Try again.', 'streamit-api')
        ]);
    }

    /**
     * Delete Playlist Details.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function delete_playlist(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $parameters = $request->get_params();
        $post_type  = $parameters['post_type'] ?? false;

        if (!$post_type || !in_array($post_type, ['movie_playlist', 'tv_show_playlist', 'video_playlist']))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlist type not found.', 'streamit-api'),
                'data'      => []
            ]);

        $playlist_function = ($post_type == 'tv_show_playlist') ? 'streamit_delete_tvshow_playlist' : 'streamit_delete_' . $post_type;

        $playlist = $playlist_function((int)$parameters['id']);

        if (!is_wp_error($playlist))
            return st_comman_custom_response([
                'status'    => true,
                'message'   => esc_html__('Playlist Delete successfully', 'streamit-api'),
            ]);

        return st_comman_custom_response([
            'status'    => false,
            'message'   => esc_html__('Something went wrong. Try again.', 'streamit-api')
        ]);
    }

    /**
     * get Platylist Data
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function get_playlist_items(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $user_id            = $data['user_id'];
        $parameters         = $request->get_params();

        $playlist_id    = $parameters['playlist_id'];
        $type           = $parameters['post_type'];
        $key_prefix     = rtrim($type, "_playlist");
        $post_type      = ($key_prefix == 'tv_show') ? 'tvshow' : $key_prefix;

        $post_ids = streamit_get_playlist_item($playlist_id, $post_type);

        if (empty($post_ids) || !is_array($post_ids))
            return st_comman_custom_response([
                "status"    => true,
                "message"   => esc_html__('Playlist is empty.', 'streamit-api'),
                "data"      => []
            ]);

        foreach ($post_ids as $post_id) {
            $function_name = 'streamit_get_' . $post_type;
            if (function_exists($function_name)) {
                $data = $function_name((int)$post_id);
                $format_function = 'st_' . $post_type . '_content_format';
                $playlist_content[] = $format_function($data);
            }
        }

        return st_comman_custom_response([
            "status"    => true,
            "message"   => esc_html__('Playlist.', 'streamit-api'),
            "data"      => $playlist_content
        ]);
    }


    public function add_playlist_items(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);


        $parameters     = $request->get_params();
        $playlist_id    = $parameters['playlist_id'];
        $post_id        = absint($parameters['post_id']);
        $type           = $parameters['post_type'];
        $key_prefix     = rtrim($type, "_playlist");
        $post_type      = ($key_prefix == 'tv_show') ? 'tvshow' : $key_prefix;

        $args = array(
            'playlist_id'        => (int)$playlist_id,
            'post_type'          => $post_type,
            'post_id'            => (int)$post_id,
        );

        $response_msg   = streamit_add_playlist_relation($args);
        if (!is_wp_error($response_msg))
            return st_comman_custom_response([
                "status"    => false,
                "message"   => esc_html__('Playlist media Not added. Try Again.', 'streamit-api')
            ]);

        return st_comman_custom_response([
            "status"    => true,
            "message"   => $response_msg
        ]);
    }

    /**
     * Delete Platylist Data
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function delete_playlist_items(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);

        $parameters     = $request->get_params();
        $playlist_id    = $parameters['playlist_id'];
        $post_id        = absint($parameters['post_id']);
        $type           = $parameters['post_type'];
        $key_prefix     = rtrim($type, "_playlist");
        $post_type      = ($key_prefix == 'tv_show') ? 'tvshow' : $key_prefix;

        $response_msg   = streamit_delete_playlist_item($playlist_id, $post_type, $post_id);

        if (!$response_msg)
            return st_comman_custom_response([
                'status'    => false,
                'message'   => esc_html__('Playlist media not removed. Try again.', 'streamit-api')
            ]);

        return st_comman_custom_response([
            'status'    => true,
            'message'   => $response_msg
        ]);
    }
}
