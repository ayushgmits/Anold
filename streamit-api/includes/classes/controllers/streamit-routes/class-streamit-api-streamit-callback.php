<?php

class St_Streamit_Route_Callback
{
    /**
     * Create a new user.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function search_list(WP_REST_Request $request)
    {
        $parameters = $request->get_params();
    }

    /**
     * Create a new user.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function dashboard(WP_REST_Request $request)
    {
        $parameters         = $request->get_params();
        $type                = $parameters['type'] ?? '';

        if (!in_array($type, ['home', 'movie', 'tv_show', 'video']))
            return st_comman_custom_response([
                'status'     => true,
                'message'     => esc_html__('No data found.', 'streamit-api'),
                'data'         => []
            ]);
        $final_result = array();

        $final_result = [
            'banner'             => [],
            'sliders'            => [],
            'continue_watch'     => []
        ];

        $pannel_option = get_option('streamit_api_' . $type);

        if (!empty($pannel_option)) {

            $banner_data = [];

            // Banner Section
            if (isset($pannel_option['banners']) && !empty($pannel_option['banners']) && count($pannel_option['banners']) > 0) {
                foreach ($pannel_option['banners'] as $banner) {
                    $function_name = 'streamit_get_' . $banner['type'];
                    if (function_exists($function_name)) {
                        $data = $function_name((int)$banner['selectItem']);
                        $format_function = 'st_' . $banner['type'] . '_content_format';
                        $banner_data[] = $format_function($data);
                    }
                }
            }

            $final_result['banner'] = $banner_data;
            //Slider Section
            $slider_final = array();
            if (isset($pannel_option['sliders']) && !empty($pannel_option['sliders']) && count($pannel_option['sliders']) > 0) {
                foreach ($pannel_option['sliders'] as $slider) {
                    $slider_data = [];
                    $slider_data['title'] = isset($slider['title']) ? $slider['title'] : '';
                    $slider_data['view_all'] = isset($slider['view_all']) ? $slider['view_all'] : '';
                    $display_data = array();

                    if ($slider['select_type'] == 'genre') {
                        if (isset($slider['genres']) && !empty($slider['genres']) && is_array($slider['genres'])) {
                            $slider_type = 'none';
                            $args = array(
                                'per_page' => -1
                            );
                            foreach ($slider['genres'] as $term_id) {
                                $parts = explode('_', $term_id);
                                if (count($parts) >= 2) {
                                    $post_type = $parts[0];
                                    $id = $parts[1];
                                    $args['tax_query'] = array(
                                        array(
                                            'field'    => 'term_id',
                                            'value'    => $id,
                                            'compare'  => '=',
                                        ),
                                    );

                                    $function_name = 'streamit_get_' . $post_type . 's';
                                    if (function_exists($function_name)) {
                                        $post_datas = $function_name($args);
                                        if (!empty($post_datas['results'])) {
                                            foreach ($post_datas['results'] as $data) {
                                                $format_function = 'st_' . $post_type . '_content_format';
                                                $display_data[] = $format_function($data);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } elseif ($slider['select_type'] == 'tag') {
                        $slider_type = 'none';

                        if (isset($slider['tags']) && !empty($slider['tags']) && is_array($slider['tags'])) {
                            foreach ($slider['tags'] as $term_id) {
                                $parts = explode('_', $term_id);
                                if (count($parts) >= 2) {
                                    $post_type = $parts[0];
                                    $id = $parts[1];
                                    $args['tax_query'] = array(
                                        array(
                                            'field'    => 'term_id',
                                            'value'    => $id,
                                            'compare'  => '=',
                                        ),
                                    );

                                    $function_name = 'streamit_get_' . $post_type . 's';
                                    if (function_exists($function_name)) {
                                        $post_datas = $function_name($args);
                                        if (!empty($post_datas['results'])) {
                                            foreach ($post_datas['results'] as $data) {
                                                $format_function = 'st_' . $post_type . '_content_format';
                                                $display_data[] = $format_function($data);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } elseif ($slider['select_type'] == 'filter') {
                        //filter
                        $slider_type = 'none';

                    } elseif ($slider['select_type'] == 'selected') {
                        $slider_type = 'none';
                        if (isset($slider['datas']) && !empty($slider['datas']) && is_array($slider['datas'])) {
                            foreach ($slider['datas'] as $data) {
                                $parts = explode('_', $data);
                                if (count($parts) >= 2) {
                                    $post_type = $parts[0];
                                    $id = $parts[1];

                                    $function_name = 'streamit_get_' . $post_type;
                                    if (function_exists($function_name)) {
                                        $data = $function_name((int)$id);
                                        $format_function = 'st_' . $post_type . '_content_format';
                                        $display_data[] = $format_function($data);
                                    }
                                }
                            }
                        }
                    }

                    $slider_data['type'] = $slider_type;
                    $slider_data['data'] = $display_data;

                    $slider_final[] = $slider_data;
                }
            }
            $final_result['sliders'] = $slider_final;
        }

        if (empty($final_result))
            return st_comman_custom_response([
                "status"     => true,
                "message"     => esc_html__('No data found.', 'streamit-api'),
                "data"         => []
            ]);

        return st_comman_custom_response([
            "status"     => true,
            "message"     => esc_html__('Dashboard result.', 'streamit-api'),
            "data"         => $final_result
        ]);
    }

    /**
     * Get Genre / category List.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function genre_list(WP_REST_Request $request)
    { 
        $parameters = $request->get_params();

        $paged             = $parameters['paged'] ?? 1;
        $posts_per_page = $parameters['posts_per_page'] ?? 10;
        $taxonomy       = in_array($parameters['type'], ['video']) ? $parameters['type'] . '_category' :  $parameters['type'] . '_genre';
        if ($paged < 1) $paged = 1;

        $args = array(
            'taxonomy'   => $taxonomy,
            'paged'      => $paged,
            'per_page'   => $posts_per_page,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        $term_list = function_exists('streamit_get_terms') ? streamit_get_terms($args) : '';
        if (empty($term_list['results'])) {
            return st_comman_custom_response([
                "status"     => true,
                "message"     => esc_html__('No genres found.', 'streamit-api'),
                "data"         => []
            ]);
        }

        $response = array();
        foreach ($term_list['results'] as $term) {
            $response[] = [
                'id'             => $term->term_id,
                'name'           => $term->term_name,
                'slug'           => $term->term_slug,
                'genre_image'    => !empty($term->thumbnail) ?  wp_get_attachment_thumb_url($term->thumbnail) : ""
            ];
        }

        return st_comman_custom_response([
            'status'     => true,
            'message'    => esc_html__('Genre results.', 'streamit-api'),
            'data'       => $response
        ]);
    }

    /**
     * Get Data List based on genre name.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function data_by_genre(WP_REST_Request $request)
    {
        $data = st_token_validation($request);

        $user_id = null;
        if (isset($data['user_id']))
            $user_id = $data['user_id'];

        $data           = array();
        $parameters     = $request->get_params();
        $post_type      = $parameters['type'];
        $taxonomy       = ($post_type === 'tv_show') ? 'tv_show_genre' : $post_type . '_genre';

        $genre_list     = $parameters['genre'];
        $genre_array     = explode(',', $genre_list);
        $args = [
			'posts_per_page' 	=> $parameters['posts_per_page'] ?? 10,
			'paged' 			=> $parameters['paged'] ?? 1,
			'post_status'    	=> 'publish',
			'tax_query' 		=> array(
				array(
					'taxonomy' 	=> $taxonomy,
					'field' 	=> 'term_slug',
					'terms' 	=> $genre_array,
                    'operator' => 'IN',
				)
			)
		];
        $display_data = array();
        $function_name = 'streamit_get_' . $post_type . 's';
        if (function_exists($function_name)) {
            $post_datas = $function_name($args);
            if (!empty($post_datas['results'])) {
                foreach ($post_datas['results'] as $data) {
                    $format_function = 'st_' . $post_type . '_content_format';
                    $display_data[] = $format_function($data);
                }
            }
        }

        if(empty($display_data)){
            return st_comman_custom_response([
				"status" 	=> true,
				"message" 	=> esc_html__('No data found.', 'streamit-api'),
				"data" 		=> []
			]);
        }
        
        return st_comman_custom_response([
			"status" 	=> true,
			"message" 	=> esc_html__('Result.', 'streamit-api'),
			"data" 		=> $display_data
		]);
    }

    /**
     * Get Data List based on genre name.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function settings(WP_REST_Request $request)
	{
        return;
		global $pmpro_currency, $st_app_options;

		$show_titles = $st_app_options["st_general"]["show_titles"] ?? false;
		$pmpro_paymnet_type = $st_app_options["st_pmp_options"]["payment_type"] ?? false;

		$settings = [
			"show_titles"		=> ($show_titles) ? 1 : 0,
			"comment" 			=> st_get_comment_settings(),
			"pmpro_currency"	=> "",
			"currency_symbol"	=> "",
			"pmpro_payments"	=> []
		];

		if (function_exists("pmpro_get_currency")) {
			$settings["pmpro_currency"] = pmpro_get_currency()["symbol"] ?? "";
			$settings["currency_symbol"] = $pmpro_currency;
		}
		if ($pmpro_paymnet_type) {
			if ($pmpro_paymnet_type == 2) {
				$settings["pmpro_payments"][] = [
					"type"				=> "in-app",
					"entitlement_id" 	=> $st_app_options["st_pmp_options"]["in_app"]["entitlement_id"] ?? "",
					'google_api_key' 	=> $st_app_options["st_pmp_options"]["in_app"]["google_api_key"] ?? "",
					'apple_api_key' 	=> $st_app_options["st_pmp_options"]["in_app"]["apple_api_key"] ?? ""
				];
			}
		}

		return comman_custom_response([
			"status" 	=> true,
			"message" 	=> __('App comman settings.', STA_TEXT_DOMAIN),
			"data" 		=> $settings
		]);
	}
}

new St_Streamit_Route_Callback();
