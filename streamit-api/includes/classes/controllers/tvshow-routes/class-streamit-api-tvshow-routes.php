<?php

class St_TVShow_Route_Controller
{
    public $module = 'tv-shows';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_tvshows_routes']);
    }
    
    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_tvshows_routes() : void 
    {
        $callbacks = new St_TVShow_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<tv_show_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'tv_show_details'],
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '(?P<tv_show_id>\d+)/seasons/(?P<season_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'season'],
                'permission_callback' => '__return_true'
            )
        );
    }   
}

new St_TVShow_Route_Controller();
