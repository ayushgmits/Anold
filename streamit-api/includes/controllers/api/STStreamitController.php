<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;
use WP_Term_Query;

class STStreamitController extends STBase
{

	public $module = 'content';

	public $nameSpace;

	function __construct()
	{
		$this->nameSpace = STREAMIT_API_NAMESPACE;

		add_action('rest_api_init', function () {

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/search',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_search_list'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/dashboard/(?P<type>home|movie|tv_show|video|live_tv)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_dashboard'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/view-all',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_view_all'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/(?P<type>home|movie|tv_show|video|live_tv)/genre',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_genre'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'(?P<type>movie|tv_show|video|live_tv)/genre/(?P<genre>[a-zA-Z0-9_-]+)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_by_genre'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/settings',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_settings'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/total-coins',
                array(
                    'methods'              => WP_REST_Server::READABLE,
                    'callback'             => [$this, 'streamit_get_users_coins'],
                    'permission_callback'  => '__return_true',
                )
            );

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/coins-history',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_get_coins_history'],
					'permission_callback' => '__return_true'
				)
			);
			

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/unlock-stream',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [$this, 'streamit_unlock_stream'],
					'permission_callback' => '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/credit_coins',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [$this, 'streamit_credit_coins'],
					'permission_callback' => '__return_true'
				)
			);

		});
	}

	public function streamit_rest_search_list($request)
	{
		// $data = stValidationToken($request);
		// $user_id = null;
		// if (!$data['status'])
		// 	return comman_custom_response($data, $data['status_code']);

		$user_id = isset($data['user_id']) ? $data['user_id'] : 0;

		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
	
		$result = $args = array();

		$parameters 			 	= $request->get_params();
		$parameters['post_type'] 	= ['movie', 'tv_show', 'video','live_tv'];
		$args 						= streamit_post_args($parameters);

		$wp_query 	= new WP_Query($args);
		$wp_query	= $wp_query->posts ?? [];
		if ($wp_query && count($wp_query) > 0) {
			foreach ($wp_query as $post) {
				$result[]	= streamit_movie_video_detail_helper($post, $user_id);
			}
		}
		if (empty($result))
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __("No data found.", STA_TEXT_DOMAIN),
				"data"		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status"	=> true,
			"message"	=> __("Search reasult.", STA_TEXT_DOMAIN),
			"data"		=> $result,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_dashboard($request)
    {

        $data = stValidationToken($request);
        $user_id = null;

        if (isset($data['user_id']))
            $user_id = $data['user_id'];

        $masterarray        = array();
        $parameters         = $request->get_params();
        $type                 = $parameters['type'] ?? '';
		error_log('Dashboard API - Type: ' . print_r($type, true)); // Debugging
        if (!in_array($type, ['home', 'movie', 'live_tv', 'tv_show', 'video']))
            return comman_custom_response([
                "status"     => true,
                "message"     => __("No data found.", STA_TEXT_DOMAIN),
                "data"         => []
            ]);

        $masterarray = [
            'banner'             => [],
            'sliders'             => [],
            'continue_watch'     => []
        ];

        $home_option = get_option('streamit_app_' . $type);

        if (!empty($home_option)) {

            // banner
            if (!empty($home_option['banner']) && count($home_option['banner']) > 0) {
                $banner_data = [];
                foreach ($home_option['banner'] as $banner) {
                    $bdata = streamit_movie_video_detail_helper($banner['show'], $user_id);

                    if (!empty($bdata)) {
                        $banner_image                 = wp_get_attachment_image_src($banner['attachment'], [768, 432]);
                        $banner_image                 = !empty($banner_image) ? $banner_image : wp_get_attachment_image_src(get_post_thumbnail_id($banner['show']), [768, 432]);
                        $bdata['image']              = $banner_image[0] ?? null;
                        $bdata["trailer_link"]         = get_post_meta($banner['show'], 'name_trailer_link', true) ?? "";
                        $bdata['trailer_link_type'] = streamit_check_video_url_type($bdata["trailer_link"]);
                        $banner_data[] = $bdata;
                    }
                }
                $masterarray['banner'] = $banner_data;
            }

            // sliders
            if (!empty($home_option['sliders']) && count($home_option['sliders']) > 0) {
                $slider_data = [];
                $arg['post_status']     = 'publish';
                $arg['post_type'] = ['movie', 'tv_show', 'live_tv', 'video'];

                $posts_per_page            = $parameters['posts_per_page'] ?? 6;
                $arg['paged']             = $parameters['paged'] ?? 1;

                $filter = streamit_dashboard_filters($type, $arg, $parameters);

                foreach ($home_option['sliders'] as $sliders) {
					// print_r ($sliders);

                    $arg['post__in'] = $arg['tax_query'] = $taxargs = array();
                    $arg['tax_query']['relation'] = '';

                    $sliderdata['title']     = $sliders['title'];
                    $sliderdata['view_all'] = (bool)$sliders['view_all'];
                    $arg['posts_per_page']     = !$sliderdata['view_all'] ? -1 : $posts_per_page;

                    $genre     = $sliders['genre'];
                    $tag     = $sliders['tag'];
                    $cat     = $sliders['cat'];

                    $tax_genre_query    = streamit_taxonomy_query($filter["tax_query"][$type]["taxonomies"], $genre);
                    $tax_tag_query         = streamit_taxonomy_query($filter["tax_query"][$type]["tag_taxonomies"], $tag);
                    $tax_cat_query         = streamit_taxonomy_query($filter["tax_query"][$type]["cat_taxonomies"], $cat);
                    $taxargs = array_merge($taxargs, $tax_genre_query, $tax_tag_query, $tax_cat_query);

                    if (!empty($taxargs)) {
                        $arg['tax_query'] = $taxargs;
                        $arg['tax_query']['relation'] = 'OR';
                    }
					error_log('WP_Query post__in: ' . print_r($arg['post__in'], true));

					error_log('Slider Data: ' . print_r($sliders, true));
					error_log('WP_Query Arguments: ' . print_r($arg, true));
					error_log('Check select_movie_show: ' . print_r($sliders['select_movie_show'], true));

                    // if (!empty($sliders['select_movie_show'])) {
					// // echo $sliders;die;
                    //     $arg['post__in'] = $sliders['select_movie_show'];
                    // }
					// if (!is_array($sliders['select_movie_show'])) {
					// 	$sliders['select_movie_show'] = explode(',', $sliders['select_movie_show']);
					// }
					if (!empty($sliders['select_movie_show'])) {
						if (!is_array($sliders['select_movie_show'])) {
							$sliders['select_movie_show'] = explode(',', $sliders['select_movie_show']);
						}
						$arg['post__in'] = array_map('intval', $sliders['select_movie_show']); // Ensure IDs are integers
					}
					
                    // merge argument with filter
                    $arg = array_merge($arg, $filter[$sliders['filter'] ?? "none"]);

                    $movie_show_data = new WP_Query($arg);
                    $movie_show_data = $movie_show_data->posts ?? [];
					// print_r ($movie_show_data);
                    $sdata = [];
                    if ($movie_show_data && count($movie_show_data) > 0) {
                        foreach ($movie_show_data as $post) {
							$post_data = streamit_movie_video_detail_helper($post->ID, $user_id);
							if (!empty($post_data)) {
								$sdata[] = $post_data;
							}
						}
						
                    }

                    $sliderdata['type']    = $sliders['filter'];
                    $sliderdata['data'] = $sdata;
					error_log('Dashboard sdata - Type: ' . print_r($sdata, true)); // Debugging
					// echo print_r($sdata);die;
                    $slider_data[]         = $sliderdata;
                }
                $masterarray['sliders'] = $slider_data;
            }
        }

        // continue_watch
        $continue_watch = get_user_meta($user_id, "_watch_content", true);
        if ($continue_watch) {
            $watch_data = json_decode($continue_watch, true);
            foreach ($watch_data as $id => $watched_data) {
                $content = streamit_movie_video_detail_helper($id, $user_id);
                if (!empty($content)) {
                    $content["watched_duration"] = $watch_data[$id];
                    $masterarray['continue_watch'][] = $content;
                }
            }
        }

        if (empty($masterarray))
            return comman_custom_response([
                "status"     => true,
                "message"     => __("No data found.", STA_TEXT_DOMAIN),
                "data"         => []
            ]);

        return comman_custom_response([
            "status"     => true,
            "message"     => __("Dashboard result.", STA_TEXT_DOMAIN),
            "data"         => $masterarray
        ]);
    }


	public function streamit_rest_view_all($request)
	{

		$data = stValidationToken($request);

		$user_id = null;
		if ($data['status'])
			$user_id = $data['user_id'];
		
		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		// $user_id = $data['user_id'];
		$slider_data 	= [];
		$parameters 	= $request->get_params();
		$type 			= $parameters['type'] ?? "";

		if (!in_array($type, ['home', 'movie', 'tv_show', 'video', 'live_tv']))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __("No data found.", STA_TEXT_DOMAIN),
				"data" 		=> []
			]);

		$slider_id 		= $parameters['slider_id'] ?? 0;
		$home_option 	= get_option('streamit_app_' . $type);

		if (isset($home_option['sliders']) && count($home_option['sliders']) >= $slider_id) {
			$sliders = $home_option['sliders'][$slider_id];

			$sliderdata['title'] = $sliders['title'];

			$arg['post_status'] 	= 'publish';
			$arg['post_type']       = ['movie', 'tv_show', 'video', 'live_tv']; // Include all valid post types
			$arg['posts_per_page']	= $parameters['posts_per_page'] ?? 6;
			$arg['paged']         	= $parameters['paged'] ?? 1;

			$filter = streamit_dashboard_filters($type, $arg, $parameters);
			$arg = array_merge($arg, $filter[$sliders['filter'] ?? "none"]);

			$taxargs = array();

			$genre 	= $sliders['genre'];
			$tag 	= $sliders['tag'];
			$cat 	= $sliders['cat'];

			$tax_genre_query	= streamit_taxonomy_query($filter["tax_query"][$type]["taxonomies"], $genre);
			$tax_tag_query 		= streamit_taxonomy_query($filter["tax_query"][$type]["tag_taxonomies"], $tag);
			$tax_cat_query 		= streamit_taxonomy_query($filter["tax_query"][$type]["cat_taxonomies"], $cat);

			$taxargs = array_merge($taxargs, $tax_genre_query, $tax_tag_query, $tax_cat_query);

			if (!empty($taxargs)) {
				$arg['tax_query'] = $taxargs;
				$arg['tax_query']['relation'] = 'OR';
			}
			if (!empty($sliders['select_movie_show'])) {
				$arg['post__in'] = $sliders['select_movie_show'];
			}
			$movie_show_data = new WP_Query($arg);
			$movie_show_data = $movie_show_data->posts ?? [];
			$sdata = [];
			if ($movie_show_data && count($movie_show_data) > 0) {
				foreach ($movie_show_data as $post) {
					$sdata[] = streamit_movie_video_detail_helper($post, $user_id);
				}
			}
			$sliderdata['data'] = $sdata;
			$slider_data = $sliderdata;
		}

		if (empty($slider_data))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __("No data found.", STA_TEXT_DOMAIN),
				"data" 		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __("View-all result.", STA_TEXT_DOMAIN),
			"data" 		=> $slider_data,
			"is_valid_token" => $is_valid_token
		]);
		return comman_custom_response($slider_data);
	}

	// public function streamit_rest_genre($request)
	// {

	// 	$parameters = $request->get_params();

	// 	$paged 			= $parameters['paged'] ?? 1;
	// 	$posts_per_page = $parameters['posts_per_page'] ?? 10;
	// 	$taxonomy 		= in_array($parameters['type'], ['video', 'live_tv']) ? $parameters['type'] . '_cat' : $parameters['type'] . '_genre';

	// 	if ($paged < 1) $paged = 1;

	// 	$args = array(
	// 		'taxonomy'  	=> $taxonomy,
	// 		'number'    	=> $posts_per_page,
	// 		'offset'    	=> ($paged - 1) * $posts_per_page,
	// 		'orderby'   	=> 'name',
	// 		'order'     	=> 'ASC',
	// 		'hide_empty'    => false
	// 	);

	// 	$query 		= new WP_Term_Query($args);
	// 	$generList 	= $query->terms;
	// 	$response 	= collect($generList)->map(function ($res, $key) {
	// 		$thumbnail_id 	= get_term_meta($res->term_id, 'thumbnail_id', true);
	// 		$image 			= !empty($thumbnail_id) ? wp_get_attachment_thumb_url($thumbnail_id) : "";
	// 		return [
	// 			'id' 			=> $res->term_id,
	// 			'name' 			=> $res->name,
	// 			'slug' 			=> $res->slug,
	// 			'genre_image' 	=> $image
	// 		];
	// 	})->values();

	// 	if ($response && !empty($response))
	// 		return comman_custom_response([
	// 			"status" 	=> true,
	// 			"message" 	=> __('Genre result.', STA_TEXT_DOMAIN),
	// 			"data" 		=> $response
	// 		]);

	// 	return comman_custom_response([
	// 		"status" 	=> true,
	// 		"message" 	=> __('No genres found.', STA_TEXT_DOMAIN),
	// 		"data" 		=> []
	// 	]);
	// }

	public function streamit_rest_genre($request)
	{
		$parameters = $request->get_params();
		
		$user_id = null;
		if ($data['status']) {
			$user_id = $data['user_id'];
		}

		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		
		$paged          = $parameters['paged'] ?? 1;
		$posts_per_page = $parameters['posts_per_page'] ?? 10;
		$type           = $parameters['type'] ?? '';
	
		// Define valid genre types
		$valid_genre_types = [
			'tv_show' => 'tv_show_audio_series',
			'movie'   => 'movies_audio_series',
			'video'   => 'video_audio_series',
			'live_tv' => 'live_tv_audio_series',
		];
	
		// Validate requested type
		if (!isset($valid_genre_types[$type])) {
			return comman_custom_response([
				"status"  => false,
				"message" => __('Invalid type provided.', STA_TEXT_DOMAIN),
				"data"    => [],
				"is_valid_token" => $is_valid_token
			]);
		}
	
		$required_genre_type = $valid_genre_types[$type];
	
		// **Use 'tv_show_genre' for movie and video, but 'live_tv_cat' for live_tv**
		$taxonomy = ($type === 'live_tv') ? 'live_tv_cat' : 'tv_show_genre';
	
		if ($paged < 1) $paged = 1;
	
		// Fetch terms
		$args = array(
			'taxonomy'    => $taxonomy,
			'number'      => $posts_per_page,
			'offset'      => ($paged - 1) * $posts_per_page,
			'orderby'     => 'name',
			'order'       => 'ASC',
			'hide_empty'  => false
		);
	
		$query = new WP_Term_Query($args);
		$termList = $query->terms;
	
		if (empty($termList)) {
			return comman_custom_response([
				"status"  => true,
				"message" => __('No categories found.', STA_TEXT_DOMAIN),
				"data"    => [],
				"is_valid_token" => $is_valid_token
			]);
		}
	
		$filtered_response = [];
	
		// Filter terms based on genre_type
		foreach ($termList as $res) {
			$genre_type = get_term_meta($res->term_id, 'genre_type', true);
	
			if ($type === 'live_tv' || $genre_type === $required_genre_type) {
				$thumbnail_id = get_term_meta($res->term_id, 'thumbnail_id', true);
				$image = !empty($thumbnail_id) ? wp_get_attachment_thumb_url($thumbnail_id) : "";
	
				$filtered_response[] = [
					'id'           => $res->term_id,
					'name'         => $res->name,
					'slug'         => $res->slug,
					'category_image'  => $image
				];
			}
		}
	
		return comman_custom_response([
			"status"  => true,
			"message" => __('Category result.', STA_TEXT_DOMAIN),
			"data"    => $filtered_response,
			"is_valid_token" => $is_valid_token
		]);
	}
	
	
	

	public function streamit_rest_by_genre($request)
	{
		$data = stValidationToken($request, true);

		$user_id = null;
		if (isset($data['user_id']))
			$user_id = $data['user_id'];
		
		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		$data				= array();
		$parameters 		= $request->get_params();
		$taxonomy 			= in_array($parameters['type'], ['video', 'live_tv']) ? $parameters['type'] . '_cat' : $parameters['type'] . '_genre';
		$movie_genre_list 	= $parameters['genre'];
		$movie_genre_array 	= explode(',', $movie_genre_list);

		$args = [
			'post_type' 		=> $parameters['type'],
			'posts_per_page' 	=> $parameters['posts_per_page'] ?? 10,
			'paged' 			=> $parameters['paged'] ?? 1,
			'post_status'    	=> 'publish',
			'tax_query' 		=> array(
				array(
					'taxonomy' 	=> $taxonomy,
					'field' 	=> 'slug',
					'terms' 	=> $movie_genre_array,
				)
			)
		];

		$wp_query = new WP_Query($args);
		$wp_query = $wp_query->posts ?? [];
		if ($wp_query && !empty($wp_query)) {
			foreach ($wp_query as $post) {
				$data[] = streamit_movie_video_detail_helper($post, $user_id);
			}
		}

		if (empty($data))
			return comman_custom_response([
				"status" 	=> true,
				"message" 	=> __('No data found.', STA_TEXT_DOMAIN),
				"data" 		=> [],
				"is_valid_token" => $is_valid_token
			]);

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('Result.', STA_TEXT_DOMAIN),
			"data" 		=> $data,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function streamit_rest_settings($request)
{
    global $pmpro_currency, $st_app_options, $wpdb;

    $user_id = absint($request->get_param('user_id'));

    $settings = [
        "show_titles"        => !empty($st_app_options["st_general"]["show_titles"]) ? 1 : 0,
        "comment"            => st_get_comment_settings(),
        "pmpro_currency"     => "",
        "currency_symbol"    => "",
        "pmpro_payments"     => [],
        "woo_auth"           => (object) []  // Default to empty object
    ];

    // ✅ Generate WooCommerce Keys for Given User
    if ($user_id && function_exists('wc_rand_hash')) {
        $consumer_key    = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();
        $hashed_key      = wc_api_hash($consumer_key);

        // Delete existing keys for fresh ones (optional)
        $wpdb->delete("{$wpdb->prefix}woocommerce_api_keys", ['user_id' => $user_id]);

        // Insert new key
        $wpdb->insert("{$wpdb->prefix}woocommerce_api_keys", [
            'user_id'         => $user_id,
            'description'     => 'Auto-generated via API',
            'permissions'     => 'read_write',
            'consumer_key'    => $hashed_key,
            'consumer_secret' => $consumer_secret,
            'truncated_key'   => substr($consumer_key, -7),
        ]);

        // Update woo_auth with generated keys
        $settings['woo_auth'] = (object) [
            'user_id'         => $user_id,
            'key_id'          => $wpdb->insert_id,
            'consumer_key'    => $consumer_key,
            'consumer_secret' => $consumer_secret,
            'permission'      => 'read_write'
        ];
    }

    // Currency data
    if (function_exists("pmpro_get_currency")) {
        $settings["pmpro_currency"] = pmpro_get_currency()["symbol"] ?? "";
        $settings["currency_symbol"] = $pmpro_currency;
    }

    // In-app payment details
    $pmpro_payment_type = $st_app_options["st_pmp_options"]["payment_type"] ?? false;
    if ($pmpro_payment_type == 2) {
        $settings["pmpro_payments"][] = [
            "type"              => "in-app",
            "entitlement_id"    => $st_app_options["st_pmp_options"]["in_app"]["entitlement_id"] ?? "",
            'google_api_key'    => $st_app_options["st_pmp_options"]["in_app"]["google_api_key"] ?? "",
            'apple_api_key'     => $st_app_options["st_pmp_options"]["in_app"]["apple_api_key"] ?? ""
        ];
    }

    return comman_custom_response([
        "status"   => true,
        "message"  => __('App comman settings.', STA_TEXT_DOMAIN),
        "data"     => $settings
    ]);
}

	
	public function streamit_get_users_coins($request)
    {
        $data = stValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}

        $user_id = null;

        if ($data['status']) {
            $user_id = $data['user_id'];
        }

        if (empty($user_id)) {
            return comman_custom_response([
                "status"  => false,
                "message" => __("User ID is required.", STA_TEXT_DOMAIN),
                "data"    => []
            ]);
        }

        $total_coins = get_user_meta($user_id, 'streamit_get_user_coins', true);

        if ($total_coins === '') {
            $total_coins = 0;
        }
		
		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		
        return comman_custom_response([
            "status" => true,
            "message" => __("User Coins", STA_TEXT_DOMAIN),
            "data" => [
                'user_id' => (string) $user_id,
                'total_coins' => (int) $total_coins,
			],
			"is_valid_token" => $is_valid_token
        ]);
    }

	// public function streamit_get_coins_history($request) {
	// 	$data = stValidationToken($request);
	
	// 	if (!$data['status']) {
	// 		return comman_custom_response([
	// 			"status"  => false,
	// 			"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
	// 			"data"    => []
	// 		]);
	// 	}
	
	// 	$user_id = isset($data['user_id']) ? $data['user_id'] : null;
	
	// 	if (is_null($user_id)) {
	// 		return comman_custom_response([
	// 			"status"  => false,
	// 			"message" => __("User ID is required.", STA_TEXT_DOMAIN),
	// 			"data"    => []
	// 		]);
	// 	}
	
	// 	$coin_transaction = get_user_meta($user_id, 'stream_coin_transaction', true);
	
	// 	$credit_transactions = [];
	// 	$debit_transactions = [];
	
	// 	if (!empty($coin_transaction) && is_array($coin_transaction)) {
	// 		foreach ($coin_transaction as $transaction_data) {
	// 			if (isset($transaction_data['transaction']) && is_array($transaction_data['transaction'])) {
	// 				// Using the status from the parent entry (top level)
	// 				$transaction_status = $transaction_data['status'];
	
	// 				$credit_entry = [
	// 					'total_coins' => $transaction_data['total_coins'],
	// 					'status'      => $transaction_status,
	// 					'date'        => $transaction_data['date'] ?? null,
	// 					'transaction' => []
	// 				];
	
	// 				$debit_entry = [
	// 					'total_coins' => $transaction_data['total_coins'],
	// 					'status'      => $transaction_status,
	// 					'date'        => $transaction_data['date'] ?? null,
	// 					'transaction' => []
	// 				];
	
	// 				foreach ($transaction_data['transaction'] as $transaction) {
	// 					// Assign status based on the parent if missing
	// 					if (!isset($transaction['status'])) {
	// 						$transaction['status'] = $transaction_status; // Use parent's status
	// 					}
	
	// 					if ($transaction['status'] === 'credit') {
	// 						$credit_entry['transaction'][] = $transaction;
	// 					} elseif ($transaction['status'] === 'debit') {
	// 						$debit_entry['transaction'][] = $transaction;
	// 					} else {
	// 						// Log if status is unknown
	// 						error_log('Unknown transaction status: ' . print_r($transaction, true));
	// 					}
	// 				}
	
	// 				// Only add non-empty entries to their respective sections
	// 				if (!empty($credit_entry['transaction'])) {
	// 					$credit_transactions[] = $credit_entry;
	// 				}
	// 				if (!empty($debit_entry['transaction'])) {
	// 					$debit_transactions[] = $debit_entry;
	// 				}
	// 			} else {
	// 				// Log if transaction data is missing or invalid
	// 				error_log('Transaction data is missing or invalid for user: ' . print_r($transaction_data, true));
	// 			}
	// 		}
	// 	}
	
	// 	// Return the response
	// 	return comman_custom_response([
	// 		"status"  => true,
	// 		"message" => __('Coins transaction.', STA_TEXT_DOMAIN),
	// 		"data"    => [
	// 			'credit' => $credit_transactions,
	// 			'debit'  => $debit_transactions
	// 		]
	// 	]);
	// }
	
	public function streamit_get_coins_history($request) {
		$data = stValidationToken($request);
	
		if (!$data['status']) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
	
		$user_id = isset($data['user_id']) ? $data['user_id'] : null;
	
		if (is_null($user_id)) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("User ID is required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
	
		$coin_transaction = get_user_meta($user_id, 'stream_coin_transaction', true);
	
		$credit_transactions = [];
		$debit_transactions = [];
	
		if (!empty($coin_transaction) && is_array($coin_transaction)) {
			foreach ($coin_transaction as $transaction_data) {
				if (isset($transaction_data['transaction']) && is_array($transaction_data['transaction'])) {
					// Using the status from the parent entry (top level)
					$transaction_status = $transaction_data['status'];
	
					// Initialize entries for credit and debit
					$credit_entry = [
						'total_coins' => $transaction_data['total_coins'],
						'status'      => $transaction_status,
						'date'        => $transaction_data['date'] ?? null,
						'stream_name' => null // Initialize stream_name as null
					];
	
					$debit_entry = [
						'total_coins' => $transaction_data['total_coins'],
						'status'      => $transaction_status,
						'date'        => $transaction_data['date'] ?? null,
						'transaction' => []
					];
	
					// Loop through each transaction
					foreach ($transaction_data['transaction'] as $transaction) {
						// Add status if missing (use parent status)
						if (!isset($transaction['status'])) {
							$transaction['status'] = $transaction_status;
						}
	
						// Separate by status
						if ($transaction['status'] === 'credit') {
							// For credit, add the stream_name directly to the credit entry
							$credit_entry['stream_name'] = $transaction['stream_name']; // Directly assign stream_name
							// Only add the credit entry once, no need to push to an array
							if (!in_array($credit_entry, $credit_transactions)) {
								$credit_transactions[] = $credit_entry;
							}
						} elseif ($transaction['status'] === 'debit') {
							// For debit, include all transaction details
							$debit_entry['transaction'][] = [
								'coins'       => $transaction['coins'], 
								'stream_name' => $transaction['stream_name'],
								'post_image'  => $transaction['post_image'] ?? null, // Image is optional
								'status'      => 'debit'  // Status
							];
						} else {
							// Log if status is unknown
							error_log('Unknown transaction status: ' . print_r($transaction, true));
						}
					}
	
					// Only add non-empty entries to their respective sections
					if (!empty($debit_entry['transaction'])) {
						$debit_transactions[] = $debit_entry;
					}
				} else {
					// Log if transaction data is missing or invalid
					error_log('Transaction data is missing or invalid for user: ' . print_r($transaction_data, true));
				}
			}
		}
	
		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		
		// Return the response
		return comman_custom_response([
			"status"  => true,
			"message" => __('Coins transaction.', STA_TEXT_DOMAIN),
			"data"    => [
				'credit' => $credit_transactions,
				'debit'  => $debit_transactions 
			],
			"is_valid_token" => $is_valid_token
		]);
	}
	
	// public function streamit_unlock_stream($request) { 
	// 	// Validate token and get user ID
	// 	$data = stValidationToken($request);
		
	// 	if (!isset($data['user_id'])) {
	// 		return comman_custom_response([
	// 			"status"  => false,
	// 			"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
	// 			"data"    => []
	// 		]);
	// 	}
	
	// 	$user_id = $data['user_id'];
		
	// 	// Fetch the JSON request body properly
	// 	$json_params = $request->get_json_params();
		
	// 	// Check if unlock_type and id are set in the request
	// 	if (!isset($json_params['unlock_type']) || !isset($json_params['stream'])) {
	// 		return comman_custom_response([
	// 			"status"  => false,
	// 			"message" => __("Invalid request. 'unlock_type' and 'id' are required.", STA_TEXT_DOMAIN),
	// 			"data"    => []
	// 		]);
	// 	}
		
	// 	$unlock_type = $json_params['unlock_type'];
	// 	$streams = $json_params['stream'];
	
	// 	if (!is_array($streams)) {
	// 		return comman_custom_response([
	// 			"status"  => false,
	// 			"message" => __("Stream ID must be an array.", STA_TEXT_DOMAIN),
	// 			"data"    => []
	// 		]);
	// 	}
		
	// 	// Fetch existing access data
	// 	$existing_access = get_user_meta($user_id, 'my_stream_access', true);
	// 	if (!is_array($existing_access)) {
	// 		$existing_access = [];
	// 	}
	
	// 	$updated_access = [];
	// 	$total_required_coins = 0;
	// 	$new_transactions = [];
	
	// 	foreach ($streams as $stream) {
	// 		if (!isset($stream['id']) || !isset($stream['coins'])) {
	// 			continue;
	// 		}
			
	// 		$stream_id = $stream['id'];
	// 		$coins = $stream['coins'];
	
	// 		if ($unlock_type == 'coins' && $coins !== null) {
	// 			$total_required_coins += $coins;
	
	// 			$stream_name = html_entity_decode(get_the_title($stream_id), ENT_QUOTES, 'UTF-8');
	// 			$post_meta          = get_post_meta($stream_id);
	// 			$portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
	// 			$image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($stream_id), [300, 300]);
	
	// 			$transaction = [
	// 				'status' => 'debit',
	// 				'coins'  => $coins,
	// 				'stream_name' => $stream_name,
	// 				'post_image' => !empty($image) ? $image[0] : null,
	// 				'date' => current_time('mysql')
	// 			];
	
	// 			$new_transactions[] = $transaction;
	// 		}
			
	// 		$updated_access[] = [
	// 			'id' => $stream_id,
	// 			'coins' => $coins,
	// 			'type' => $unlock_type
	// 		];
	// 	}
	
	// 	if ($unlock_type == 'coins') {
	// 		// Get available coins for the user
	// 		$available_coins = get_user_meta($user_id, 'streamit_get_user_coins', true);
			
	// 		// If the total required coins exceed available coins, return an error
	// 		if ($total_required_coins > $available_coins) {
	// 			return comman_custom_response([
	// 				"status"  => false,
	// 				"message" => __("You do not have enough coins in your wallet.", STA_TEXT_DOMAIN),
	// 				"data"    => []
	// 			]);
	// 		}
	
	// 		// Subtract the required coins from the user's wallet
	// 		$new_available_coins = $available_coins - $total_required_coins;
	// 		update_user_meta($user_id, 'streamit_get_user_coins', $new_available_coins);
	
	// 		// Get existing coin transaction history
	// 		$existing_transactions = get_user_meta($user_id, 'stream_coin_transaction', true);
	
	// 		// Ensure it's an array
	// 		if (!is_array($existing_transactions)) {
	// 			$existing_transactions = [];
	// 		}
	
	// 		// Store transactions as a single object under `total_coins`
	// 		$new_transaction_entry = [
	// 			'total_coins' => $total_required_coins,
	// 			'transaction' => $new_transactions
	// 		];
	
	// 		// **Insert new transaction at the beginning**
	// 		array_unshift($existing_transactions, $new_transaction_entry);
	
	// 		// Update the 'stream_coin_transaction' user meta
	// 		update_user_meta($user_id, 'stream_coin_transaction', $existing_transactions);
	// 	}
	
	// 	// Merge new stream access with existing ones
	// 	$merged_access = array_merge($existing_access, $updated_access);
	
	// 	// Ensure unique values
	// 	$merged_access = array_unique($merged_access, SORT_REGULAR);
	
	// 	// Check if there are any new additions (avoid unnecessary updates)
	// 	if ($merged_access !== $existing_access) {
	// 		update_user_meta($user_id, 'my_stream_access', $merged_access);
	// 	}
		
	// 	return comman_custom_response([
	// 		"status"  => true,
	// 		"message" => __("Streams unlocked successfully.", STA_TEXT_DOMAIN),
	// 		"data"    => []
	// 	]);
	// }

	public function streamit_unlock_stream($request) { 
		// Validate token and get user ID
		$data = stValidationToken($request);
		
		if (!isset($data['user_id'])) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
	
		$user_id = $data['user_id'];
		
		// Fetch the JSON request body properly
		$json_params = $request->get_json_params();
		
		// Check if unlock_type and id are set in the request
		if (!isset($json_params['unlock_type']) || !isset($json_params['stream'])) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("Invalid request. 'unlock_type' and 'id' are required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
		
		$unlock_type = $json_params['unlock_type'];
		$streams = $json_params['stream'];
	
		if (!is_array($streams)) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("Stream ID must be an array.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
		
		// Fetch existing access data
		$existing_access = get_user_meta($user_id, 'my_stream_access', true);
		if (!is_array($existing_access)) {
			$existing_access = [];
		}
	
		$updated_access = [];
		$total_required_coins = 0;
		$new_transactions = [];
	
		foreach ($streams as $stream) {
			if (!isset($stream['id']) || !isset($stream['coins'])) {
				continue;
			}
			
			$stream_id = $stream['id'];
			$coins = $stream['coins'];
		
			if ($unlock_type == 'coins' && $coins !== null) {
				$total_required_coins += $coins;
		
				$stream_name = html_entity_decode(get_the_title($stream_id), ENT_QUOTES, 'UTF-8');
				$post_meta          = get_post_meta($stream_id);
				$portrait_image_id  = $post_meta['_portrait_thumbmail'][0] ?? 0;
				$image              = !empty($portrait_image_id) ? wp_get_attachment_image_src($portrait_image_id, "full") : wp_get_attachment_image_src(get_post_thumbnail_id($stream_id), [300, 300]);
		
				// Add transaction data for each stream
				$transaction = [
					'coins'        => $coins,
					'stream_name'  => $stream_name,
					'post_image'   => !empty($image) ? $image[0] : null,
				];
		
				// Push the transaction to the new transactions array
				$new_transactions[] = $transaction;
			}
		
			$updated_access[] = [
				'id' => $stream_id,
				'coins' => $coins,
				'type' => $unlock_type
			];
		}
		
		if ($unlock_type == 'coins') {
			// Get available coins for the user
			$available_coins = get_user_meta($user_id, 'streamit_get_user_coins', true);
			
			// If the total required coins exceed available coins, return an error
			if ($total_required_coins > $available_coins) {
				return comman_custom_response([
					"status"  => false,
					"message" => __("You do not have enough coins in your wallet.", STA_TEXT_DOMAIN),
					"data"    => []
				]);
			}
		
			// Subtract the required coins from the user's wallet
			$new_available_coins = $available_coins - $total_required_coins;
			update_user_meta($user_id, 'streamit_get_user_coins', $new_available_coins);
		
			// Get existing coin transaction history
			$existing_transactions = get_user_meta($user_id, 'stream_coin_transaction', true);
		
			// Ensure it's an array
			if (!is_array($existing_transactions)) {
				$existing_transactions = [];
			}
		
			// Store transactions as a single object under `total_coins`
			$new_transaction_entry = [
				'total_coins' => $total_required_coins,
				'status'      => 'debit',  // Added here instead of inside the transaction
				'date'        => current_time('mysql'),  // Added here instead of inside the transaction
				'transaction' => $new_transactions,  // Only the transactions data
			];
		
			// **Insert new transaction at the beginning**
			array_unshift($existing_transactions, $new_transaction_entry);
		
			// Update the 'stream_coin_transaction' user meta
			update_user_meta($user_id, 'stream_coin_transaction', $existing_transactions);
		}		
	
		// Merge new stream access with existing ones
		$merged_access = array_merge($existing_access, $updated_access);
	
		// Ensure unique values
		$merged_access = array_unique($merged_access, SORT_REGULAR);
	
		// Check if there are any new additions (avoid unnecessary updates)
		if ($merged_access !== $existing_access) {
			update_user_meta($user_id, 'my_stream_access', $merged_access);
		}

		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
	
		
		return comman_custom_response([
			"status"  => true,
			"message" => __("Streams unlocked successfully.", STA_TEXT_DOMAIN),
			"data"    => [],
			"is_valid_token" => $is_valid_token
		]);
	}

	// Demo code fore Payment Aftre Buy the Coin Packaeg
	public function streamit_credit_coins($request) { 
		// Validate token and get user ID
		$data = stValidationToken($request);
		
		if (!isset($data['user_id'])) {
			return comman_custom_response([
				"status"  => false,
				"message" => __("JWT token is required.", STA_TEXT_DOMAIN),
				"data"    => []
			]);
		}
	
		$user_id = $data['user_id'];
		// Retrieve logged-in devices to determine token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		// Static data for crediting coins
		$coins_to_credit = 100;
		$note = "Test Credit";
		$status = "credit";
	
		// Get current coin balance
		$available_coins = get_user_meta($user_id, 'streamit_get_user_coins', true);
		if (!$available_coins) {
			$available_coins = 0;
		}
	
		// Add coins to the user's wallet
		$new_available_coins = $available_coins + $coins_to_credit;
		update_user_meta($user_id, 'streamit_get_user_coins', $new_available_coins);
	
		// Get existing coin transaction history
		$existing_transactions = get_user_meta($user_id, 'stream_coin_transaction', true);
	
		// Ensure it's an array
		if (!is_array($existing_transactions)) {
			$existing_transactions = [];
		}
	
		// Store the credit transaction with additional details
		$new_transaction_entry = [
			'total_coins' => $coins_to_credit,
			'status'      => $status,
			'date'        => current_time('mysql'),
			'transaction' => [
				[
					'coins'       => $coins_to_credit,
					'stream_name' => 'Final TEst 123',
					'post_image'  => null
				]
			]
		];
	
		// Insert new transaction at the beginning
		array_unshift($existing_transactions, $new_transaction_entry);
	
		// Update the 'stream_coin_transaction' user meta
		update_user_meta($user_id, 'stream_coin_transaction', $existing_transactions);
	
		return comman_custom_response([
			"status"  => true,
			"message" => __("Coins credited successfully.", STA_TEXT_DOMAIN),
			"data"    => [
				"new_balance" => $new_available_coins
			],
			"is_valid_token" => $is_valid_token
		]);
	}
	
}
