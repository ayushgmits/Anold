<?php

class St_Video_Route_Controller
{
    public $module = 'videos';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_videos_routes']);
    }

    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_videos_routes() : void {
        $callbacks = new St_Video_Route_Callback();
        register_rest_route(
            $this->name_space . '/api/v1',
            '/' . $this->module,
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'videos_list'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<video_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'video_details'],
                'permission_callback' => '__return_true'
            )
        );
    }
}

new St_Video_Route_Controller();