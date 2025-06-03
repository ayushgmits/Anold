<?php


class St_notification_Route_Callback
{

    /**
     * Get Notification List.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function notifications_list(WP_REST_Request $request)
    {
        $data = st_token_validation($request);
        if (!$data['status'])
            return st_comman_custom_response($data, $data['status_code']);
    }
}