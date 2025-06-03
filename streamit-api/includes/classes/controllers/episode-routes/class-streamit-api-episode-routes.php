<?php

class St_Episode_Route_Controller
{
    public $base    = 'tv-show/season';
    public $module  = 'episodes';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_episode_routes']);
    }
    

    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_episode_routes() : void
    {
        $callbacks = new St_Episode_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->base . '/' . $this->module,
            '/(?P<episode_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'episode_details'],
                'permission_callback' => '__return_true'
            )
        );
    }
}

new St_Episode_Route_Controller();