<?php

class St_WPPost_Route_Controller
{
    public $module = 'wp-posts';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_wppost_routes']);
    }

    public function register_wppost_routes(): void
    {
        $callbacks = new St_WPPost_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/comment',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$callbacks, 'edit_comments'],
                    'permission_callback' => [$callbacks, 'is_user_allowed'],
                    'args' => [
                        'content'       => array(
                            'required'          => true,
                            'validate_callback' => 'sanitize_textarea_field',
                            'sanitize_callback' => 'sanitize_textarea_field'
                        )
                    ]
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$callbacks, 'delete_comments'],
                    'permission_callback' => [$callbacks, 'is_user_allowed']
                ]
            ]
        );
    }
}

new St_WPPost_Route_Controller();