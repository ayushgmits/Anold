<?php

/**
 * Class St_WPPost_Route_Callback
 *
 * Handles REST API routes for managing comments.
 */
class St_WPPost_Route_Callback
{
    /**
     * Check if the user is allowed to perform the action on the comment.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
    public function is_user_allowed(WP_REST_Request $request): WP_REST_Response
    {
        $data = st_token_validation($request);

        if ($data['status'] && isset($data['user_id'])) {
            $current_user_id = $data['user_id'];
        } else {
            return st_comman_custom_response($data, $data['status_code']);
        }

        $parameters = st_sanitize_recursive_text_fields($request->get_params());
        $id = $parameters['id'] ?? '';

        if (empty(trim($id))) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('Request ID not found', 'streamit-api'),
            ]);
        }

        $comment = get_comment($id);

        if (!$comment) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('Comment ID not found', 'streamit-api'),
            ]);
        }
        
        return st_comman_custom_response([
            'status' => true,
            'message' => esc_html__('Comment author matches the current user', 'streamit-api'),
        ]);
    }

    /**
     * Edit a comment.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
    public function edit_comments(WP_REST_Request $request): WP_REST_Response
    {
        $parameters = st_sanitize_recursive_text_fields($request->get_params());
        $id = $parameters['id'] ?? '';

        if (empty($parameters['content'])) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('You cannot leave the comment empty.', 'streamit-api'),
            ]);
        }

        // Retrieve the comment here instead of relying on the class property
        $comment = get_comment($id);
        if (!$comment) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('Comment ID not found.', 'streamit-api'),
            ]);
        }

        if (!wp_update_comment(['comment_content' => $parameters['content'], 'comment_ID' => $id])) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('Something went wrong! Try again.', 'streamit-api'),
            ]);
        }

        // Update the comment's content
        $comment->comment_content = $parameters['content'];
        $comment_obj = (new WP_REST_Comments_Controller())->prepare_item_for_response($comment, $request);

        return st_comman_custom_response([
            'status' => true,
            'message' => esc_html__('Comment edited successfully.', 'streamit-api'),
            'data' => $comment_obj->data,
        ]);
    }

    /**
     * Delete a comment.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
    public function delete_comments(WP_REST_Request $request): WP_REST_Response
    {
        $parameters = st_sanitize_recursive_text_fields($request->get_params());
        $id = $parameters['id'] ?? '';

        if (!wp_delete_comment($id, true)) {
            return st_comman_custom_response([
                'status' => false,
                'message' => esc_html__('Something went wrong! Try again.', 'streamit-api'),
            ]);
        }

        return st_comman_custom_response([
            'status' => true,
            'message' => esc_html__('Comment has been deleted.', 'streamit-api'),
        ]);
    }
}