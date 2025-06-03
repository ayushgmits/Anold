<?php

class St_notification_Route_Controller
{
    public $module = 'notificaton';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_notification_routes']);
    }

    public function register_notification_routes():void
    {
        $callback = new St_notification_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/list',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'notifications_list'],
                'permission_callback' => '__return_true'
            )
        );
    }
}

new St_notification_Route_Controller();