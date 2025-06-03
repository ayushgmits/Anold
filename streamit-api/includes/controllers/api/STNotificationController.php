<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use Includes\baseClasses\STNotifications;
use WP_REST_Server;

class STNotificationController extends STBase
{

    public $module = 'notificaton';

    public $nameSpace;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/list',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_notifications_list'],
                    'permission_callback' => '__return_true'
                )
            );

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/clear',
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'streamit_clear_notification'],
                    'permission_callback' => '__return_true'
                )
            );

            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/count',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'streamit_notification_count'],
                    'permission_callback' => '__return_true'
                )
            );
        });
    }

    public function streamit_notifications_list($request)
    {
        $data = stValidationToken($request);

        if (!isset($data['user_id']))
            return comman_custom_response($data, $data['status_code']);

        $current_user_id = $data['user_id'];
        $parameters = $request->get_params();
        $parameters = stRecursiveSanitizeTextFields($parameters);
        $notification_data = array(); // Array to store notification data

        $notifications = get_user_meta($current_user_id, '_php_prefix_notification', true);
        $post_types = apply_filters('php_prefix_notification_post_type', array('movie', 'tv_show', 'video', 'episode'));

        if ($notifications) {
            $newly_added_post_ids = array();
            foreach ($notifications['_php_prefix_newly_added'] as $key => $value) {
                if (isset($value['is_seen']) && $value['is_seen'] === false)
                    $newly_added_post_ids[] = $value['post_id'];
            }
            if (!empty($newly_added_post_ids)) {
                $args = array(
                    'post_type'         => $post_types,
                    'post_status'       => 'publish',
                    'posts_per_page'    => $parameters["per_page"],
                    'paged'             => $parameters["page"],
                    'post__in'          => $newly_added_post_ids,
                    'order'             => 'DESC',
                );

                $wp_query = new \WP_Query($args);

                if ($wp_query->have_posts()) {
                    while ($wp_query->have_posts()) {
                        $wp_query->the_post();

                        $post_id = get_the_ID();
                        $post_type = get_post_type();

                        $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
                        $run_time = get_post_meta($post_id, '_' . $post_type . '_run_time', true);

                        $src = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : get_lazy_load_image();
                        $key = array_search($post_id, array_column($notifications['_php_prefix_newly_added'], 'post_id'));
                        $is_seen = $notifications['_php_prefix_newly_added'][$key]['is_seen'] ?? 0;
                        $is_seen = (bool) $is_seen;
                        // Build an object for each notification
                        $notification_data[] = (object) array(
                            'post_id'   => $post_id,
                            'post_type' => $post_type,
                            'title'     => get_the_title(),
                            'run_time'  => $run_time,
                            'src'       => $src,
                            'is_seen'   => $is_seen,
                            // ... add other necessary details
                        );
                        if (!$is_seen) {
                            $notifications['_php_prefix_newly_added'][$key]['is_seen'] = 1;
                            update_user_meta($current_user_id, '_php_prefix_notification', $notifications);
                        }
                    }

                    wp_reset_postdata(); // Reset the post data to the main query

                    return comman_custom_response([
                        "status" => true,
                        "message" => __("List of Notification", STA_TEXT_DOMAIN),
                        "data" => $notification_data
                    ]);
                } else {
                    return comman_custom_response([
                        "status" => true,
                        "message" => __("No notifications found", STA_TEXT_DOMAIN),
                        "data" => array()
                    ]);
                }
            }
        }

        return comman_custom_response([
            "status" => true,
            "message" => __("No notifications found.", STA_TEXT_DOMAIN),
            "data" => array()
        ]);
    }

    public function streamit_clear_notification($request)
    {
        $data = stValidationToken($request);

        if ($data['status'] && isset($data['user_id']))
            $current_user_id = $data['user_id'];
        else
            return comman_custom_response($data, $data['status_code']);

        // Get user notifications
        $notifications = get_user_meta($current_user_id, '_php_prefix_notification', true);

        // Check if there are notifications
        if ($notifications) {
            // Clear all notifications
            $notifications['_php_prefix_newly_added'] = array();

            // Update user meta to remove notifications
            update_user_meta($current_user_id, '_php_prefix_notification', $notifications);

            // Return a response indicating success
            return comman_custom_response([
                "status"  => true,
                "message" => __("All notifications cleared successfully", STA_TEXT_DOMAIN),
            ]);
        } else {
            // If no notifications found, return a response indicating that there were no notifications to clear
            return comman_custom_response([
                "status"  => true,
                "message" => __("No notifications found to clear", STA_TEXT_DOMAIN),
            ]);
        }
    }

    public function streamit_notification_count($request)
    {
        $data = stValidationToken($request);

        if ($data['status'] && isset($data['user_id'])) {
            $current_user_id = $data['user_id'];

            // Get user notifications
            $notifications = get_user_meta($current_user_id, '_php_prefix_notification', true);

            // Check if there are notifications
            if ($notifications && isset($notifications['_php_prefix_newly_added'])) {
                // Count the number of unread notifications
                $unread_count = 0;
                foreach ($notifications['_php_prefix_newly_added'] as $value) {
                    if (isset($value['is_seen']) && $value['is_seen'] === false) {
                        $unread_count++;
                    }
                }

                // Return the total notification count and unread count
                return comman_custom_response([
                    "status" => true,
                    "message" => __("Notification Count", STA_TEXT_DOMAIN),
                    "data" => [
                        "total_notification_count" => $unread_count,
                    ]
                ]);
            }
        }

        // If no notifications found or other issues, return response accordingly
        return comman_custom_response([
            "status" => true,
            "message" => __("No notifications found", STA_TEXT_DOMAIN),
            "data" => ["total_notification_count" => 0, "unread_messages_count" => 0]
        ]);
    }
}
