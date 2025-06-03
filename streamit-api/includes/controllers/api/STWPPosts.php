<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Comments_Controller;
use WP_REST_Server;

class STWPPosts extends STBase
{

    public $module = 'wp-posts';

    public $nameSpace;

    public $comment;

    function __construct()
    {

        $this->nameSpace = STREAMIT_API_NAMESPACE;

        add_action('rest_api_init', function () {
            register_rest_route(
                $this->nameSpace . '/api/v1/' . $this->module,
                '/comment',
                [
                    [
                        'methods'             => WP_REST_Server::EDITABLE,
                        'callback'            => [$this, 'edit_comments'],
                        'permission_callback' => [$this, "is_user_allowed"]
                    ],
                    [
                        'methods'             => WP_REST_Server::DELETABLE,
                        'callback'            => [$this, 'delete_comments'],
                        'permission_callback' => [$this, 'is_user_allowed']
                    ]
                ]
            );
        });
    }

    public function is_user_allowed($request)
    {
  
        $data = stValidationToken($request);

        if ($data['status'] && isset($data['user_id']))
            $current_user_id = $data['user_id'];
        else
            return comman_custom_response($data, $data['status_code']);

        $parameters = $request->get_params();
        $parameters = stRecursiveSanitizeTextFields($parameters);

        $id = isset($parameters["id"]) ? $parameters["id"] : "";

        if (empty(trim($id))) return comman_custom_response([
            "status" => false,
            "message" => __("Request ID not found", STA_TEXT_DOMAIN)
        ]);

        $this->comment = get_comment($id);

        if (!$this->comment) return comman_custom_response([
            "status" => false,
            "message" => __("Comment ID not found", STA_TEXT_DOMAIN)
        ]);

        // return ($current_user_id == $this->comment->user_id);
        return comman_custom_response([
            "status" => true,
            "message" => __("Commen author match with current user", STA_TEXT_DOMAIN)
        ]);
    }

    public function edit_comments($request)
    {
        
        $parameters = $request->get_params();
        $parameters = stRecursiveSanitizeTextFields($parameters);

        $id = $parameters["id"];

        if (!isset($parameters["content"]) || empty(trim($parameters["content"])))
            return comman_custom_response([
                "status" => false,
                "message" => __("You can not leave comment empty.", STA_TEXT_DOMAIN)
            ]);

        if (!wp_update_comment(["comment_content" => $parameters["content"], "comment_ID" => $id]))
            return comman_custom_response([
                "status" => false,
                "message" => __("Something wrong! Try again.", STA_TEXT_DOMAIN)
            ]);

        $this->comment->comment_content = $parameters["content"];
        $comment_obj = (new WP_REST_Comments_Controller())->prepare_item_for_response($this->comment, $request);

        return comman_custom_response([
            "status" => true,
            "message" => __("Edit Comments", STA_TEXT_DOMAIN),
            "data" => $comment_obj->data
        ]);
    }

    public function delete_comments($request)
    {
        $parameters = $request->get_params();
        $parameters = stRecursiveSanitizeTextFields($parameters);

        $id = $parameters["id"];

        if (!wp_delete_comment($id, true))
            return comman_custom_response([
                "status" => false,
                "message" => __("Something wrong! Try again.", STA_TEXT_DOMAIN)
            ]);

        return comman_custom_response([
            "status" => true,
            "message" => __("Comment has been deleted.", STA_TEXT_DOMAIN)
        ]);
    }
}
