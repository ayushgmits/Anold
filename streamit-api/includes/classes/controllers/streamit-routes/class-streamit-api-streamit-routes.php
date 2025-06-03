<?php

class St_Streamit_Route_Controller
{
    public $module = 'content';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_streamit_routes']);
    }

    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_streamit_routes() : void {
        $callbacks = new St_Streamit_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/search',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'search_list'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/dashboard/(?P<type>home|movie|tv_show|video)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'dashboard'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<type>home|movie|tv_show|video)/genre',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'genre_list'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '(?P<type>movie|tv_show|video|live_tv)/genre/(?P<genre>[a-zA-Z_-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'data_by_genre'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/settings',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'settings'],
                'permission_callback' => '__return_true'
            )
        );

    }
}

new St_Streamit_Route_Controller();