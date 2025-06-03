<?php
final class streamit_api_admin_pannel_controler
{

    /**
     * add banner section in admin options.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response|WP_Error Response object on success, error object on failure.
     */
    public function add_banner(WP_REST_Request $request)
    {
        try {
            $data = $request->get_param('data');

            $value['type'] = $data['selected_type'];
            
           
            ob_start();
            $i = $data['i'];
            require STREAMIT_API_DIR . 'admin/view/banners/html-admin-banner.php';
            $banner_html = ob_get_clean();

            return wp_send_json($banner_html);
        } catch (Exception $e) {
            // Handle exceptions and provide meaningful error messages.
            $code = $e->getCode();
            $message = $e->getMessage();

            return new WP_Error($code, $message, array('status' => $code));
        }
    }


    /**
     * add slider section in admin options.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response|WP_Error Response object on success, error object on failure.
     */
    public function add_slider(WP_REST_Request $request)
    {
        try {
            $data = $request->get_param('data');
            ob_start();
            $type = $data['type'];
            $i = $data['i'];
            require STREAMIT_API_DIR . 'admin/view/sliders/html-admin-slider.php';
            $slider_html = ob_get_clean();

            return wp_send_json($slider_html);
        } catch (Exception $e) {
            // Handle exceptions and provide meaningful error messages.
            $code = $e->getCode();
            $message = $e->getMessage();

            return new WP_Error($code, $message, array('status' => $code));
        }
    }

    /**
     * Add dashboard data from admin options.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response|WP_Error Response object on success, error object on failure.
     */
    public function submit_dashboard_data(WP_REST_Request $request)
    {
        try {
            // Retrieve parameters from the request
            $data = $request->get_params();
            // Check if the option and formData are set
            if (!isset($data['option']) || empty($data['option'])) {
                return new WP_Error('missing_option', esc_html__('Option parameter is missing or empty.', 'streamit-api'), array('status' => 400));
            }

            if (!isset($data['formData'])) {
                return new WP_Error('missing_form_data', esc_html__('Form data is missing.', 'streamit-api'), array('status' => 400));
            }

            // Update the option in the database
            $result = update_option($data['option'], $data['formData']);

            // Return a success response
            return wp_send_json_success(array('success' => true, 'updated_option' => $data['option']));
        } catch (Exception $e) {
            // Handle exceptions and provide meaningful error messages.
            $code = $e->getCode() ? $e->getCode() : 500; // Default to 500 if no code is set
            $message = $e->getMessage() ?: esc_html__('An unexpected error occurred.', 'streamit-api');

            return new WP_Error($code, $message, array('status' => $code));
        }
    }
}
