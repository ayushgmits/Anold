<?php

class St_Movie_Route_Controller
{
    public $module = 'movies';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_movies_routes']);
    }


    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_movies_routes() : void {
        $callbacks = new St_Movie_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<movie_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'movie_details'],
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/recommended',
            array(
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [$callbacks, 'recommended_movies'],
                'permission_callback'   => '__return_true'
            )
        );
    }
}   

new St_Movie_Route_Controller();