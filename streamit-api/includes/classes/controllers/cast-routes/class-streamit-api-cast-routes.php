<?php

class St_Cast_Route_Controller
{
    public $module  = 'cast';

    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_cast_routes']);
    }


    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_cast_routes(): void
    {
        $callbacks = new St_Cast_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/person/(?P<cast_id>\d+)',
            array(
                'methods'                 => WP_REST_Server::READABLE,
                'callback'                => [$callbacks, 'person_details'],
                'permission_callback'     => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/person/(?P<cast_id>\d+)/work-history',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$callbacks, 'person_work_history'],
                'permission_callback' => '__return_true',
            )
        );
    }
}

new St_Cast_Route_Controller();
