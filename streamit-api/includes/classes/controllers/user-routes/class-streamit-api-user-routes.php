<?php

class St_User_Route_Controller
{
    public $module = 'user';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_user_routes']);
    }

    /**
     * Register REST routes for the user module.
     *
     * @return void
     */
    public function register_user_routes(): void
    {
        $callbacks = new St_User_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/registration',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$callbacks, 'create_user'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'user_login' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_user',
                    ],
                    'user_email' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_email',
                    ],
                    'first_name' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'last_name' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'user_pass' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/validate-token',
            [
                'methods'             => WP_REST_Server::ALLMETHODS,
                'callback'            => [$callbacks, 'validate_token'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'device_id' => [
                        'required' => true,
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/profile',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$callbacks, 'view_profile'],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$callbacks, 'update_profile'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/change-password',
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$callbacks, 'change_password'],
                'permission_callback' => '__return_true',
                'args'  => [
                    'old_password'      => array(
                        'required'          => true,
                        'validate_callback' => 'sanitize_text_field',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'new_password'      => array(
                        'required'          => true,
                        'validate_callback' => 'sanitize_text_field',
                        'sanitize_callback' => 'sanitize_text_field'
                    )
                ]
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/forgot-password',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$callbacks, 'forgot_password'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/delete-account',
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$callbacks, 'delete_user_account'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/nonce',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$callbacks, 'rest_nonce'],
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/devices',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$callbacks, 'get_devices'],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$callbacks, 'add_device'],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'device_id' => [
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'device_model' => [
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'login_token' => [
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$callbacks, 'remove_device'],
                    'permission_callback' => '__return_true',
                ]
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/watchlist',
            [
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$callbacks, 'get_watchlist'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => 'POST, PUT, DELETE',
                    'callback'            => [$callbacks, 'manage_watchlist'],
                    'permission_callback' => '__return_true'
                )
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/continue-watch',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_continue_watch'],
                    'permission_callback' => '__return_true'
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'add_continue_watch'],
                    'permission_callback' => '__return_true'
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'remove_continue_watch'],
                    'permission_callback' => '__return_true'
                ]
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/player-ids',
            [
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'add_player_id'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'remove_player_id'],
                    'permission_callback' => '__return_true'
                )
            ]
        );
        
    }
}

new St_User_Route_Controller();
