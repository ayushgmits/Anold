<?php

class St_playlist_Route_Controller
{
    public $module = 'playlists';
    public $name_space;

    public function __construct()
    {
        $this->name_space = STREAMIT_API_NAMESPACE;

        // Register REST routes
        add_action('rest_api_init', [$this, 'register_playlist_routes']);
    }

    public function register_playlist_routes():void
    {
        $callbacks = new St_Playlist_Route_Callback();

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<post_type>movie_playlist|tv_show_playlist|video_playlist)',
            [
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$callbacks, 'get_playlist'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$callbacks, 'create_playlist'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$callbacks, 'delete_playlist'],
                    'permission_callback' => '__return_true'
                )
            ]
        );

        register_rest_route(
            $this->name_space . '/api/v1/' . $this->module,
            '/(?P<post_type>movie_playlist|tv_show_playlist|video_playlist)/(?P<playlist_id>\d+)',
            [
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$callbacks, 'get_playlist_items'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$callbacks, 'add_playlist_items'],
                    'permission_callback' => '__return_true'
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$callbacks, 'delete_playlist_items'],
                    'permission_callback' => '__return_true'
                )
            ]

        );

    }
}
new St_playlist_Route_Controller();