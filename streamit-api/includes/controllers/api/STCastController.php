<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use WP_REST_Server;
use WP_Query;

class STCastController extends STBase
{

	public $module = 'cast';

	public $nameSpace;

	function __construct()
	{

		$this->nameSpace = STREAMIT_API_NAMESPACE;

		add_action('rest_api_init', function () {

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/person/(?P<cast_id>\d+)',
				array(
					'methods'             	=> WP_REST_Server::READABLE,
					'callback'            	=> [$this, 'streamit_rest_person_details'],
					'permission_callback' 	=> '__return_true'
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/person/(?P<cast_id>\d+)/work-history',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'streamit_rest_person_work_history'],
					'permission_callback' => '__return_true',
				)
			);
		});
	}

	public function streamit_rest_person_details($request)
	{
		$parameters = $request->get_params();

		$cast_id 					= $parameters['cast_id'];
		$most_viewed_content 		= [];
		$response 					= null;
		$args = [
			'p' 		=> $cast_id,
			'post_type'	=> 'tv_show'
		];

		$wp_query 		= new WP_Query($args);
		$response_data 	= $wp_query->post;
		if ($response_data === null || empty($response_data))
			return comman_custom_response(array(
				"status"	=> true,
				"message"	=> __("Details not found.", STA_TEXT_DOMAIN),
				"data" 		=> (object) []
			));


		$term_names = "";
		$terms 		= get_the_terms($cast_id, 'person_cat');
		if (!is_wp_error($terms) && $terms) {
			$term_names = wp_list_pluck($terms, 'name');
			$term_names = implode(',', $term_names);
		}

		$movie_cast 	= (array) get_post_meta($cast_id, '_movie_cast', true);
		$movie_crew 	= (array) get_post_meta($cast_id, '_movie_crew', true);
		$tv_show_cast 	= (array) get_post_meta($cast_id, '_tv_show_cast', true);
		$tv_show_crew	= (array) get_post_meta($cast_id, '_tv_show_crew', true);
		$cast_image 	= wp_get_attachment_image_src(get_post_thumbnail_id($cast_id), "full");
		$birthday		= get_post_meta($cast_id, '_birthday', true);
		$birthday 		= empty($birthday) ? null : date_i18n('Y-m-d', $birthday);
		$deathday 		= get_post_meta($cast_id, '_deathday', true);
		$deathday 		= empty($deathday) ? null : date_i18n('Y-m-d', $deathday);
		$credits = array_merge(
			$movie_cast ?? [],
			$movie_crew ?? [],
			$tv_show_cast ?? [],
			$tv_show_crew ?? []
		); 
		$credits = array_unique(array_filter($credits));

		// most-viewed content
		$response = [
			'id'				=> $cast_id,
			'title'				=> $response_data->post_title,
			'description' 		=> wp_strip_all_tags(get_the_content(null, false, $cast_id)),
			'image'     		=> $cast_image[0] ?? null,
			'category'  		=> $term_names,
			'credits'			=> count($credits),
			'also_known_as' 	=> get_post_meta($cast_id, '_also_known_as', true),
			'place_of_birth'	=> get_post_meta($cast_id, '_place_of_birth', true),
			'birthday' 			=> $birthday,
			'deathday' 			=> $deathday,
		];

		$worked_in_ids = array_merge($movie_cast,$movie_crew, $tv_show_cast, $tv_show_crew);
		$args = array(
			'post_type' 		=> array('movie', 'tv_show'),
			'post_status' 		=> 'publish',
			'post__in' 			=> $worked_in_ids,
			'orderby'           => 'meta_value_num',
			'order'             => 'DESC',
			'posts_per_page' 	=> 4,
			'meta_query' 		=> array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key' => 'post_views_count',
					),
					array(
						'key' => 'tv_show_views_count',
					)
				),
			)
		);
		$most_viewed = new WP_Query($args);

		if ($most_viewed->have_posts()) {
			while ($most_viewed->have_posts()) {
				$most_viewed->the_post();
				$most_viewed_content[] = self::streamit_person_work_history(get_the_ID(), $cast_id);
			}
		}

		$responses = array(
			"status"	=> true,
			"message"	=> __("Cast details.", STA_TEXT_DOMAIN),
			"data" 		=> [
				'details' 				=> $response,
				'most_viewed_content' 	=> $most_viewed_content
			]
		);

		return comman_custom_response($responses);
	}

	public function streamit_rest_person_work_history($request)
	{
		$response 			= [];
		$parameters 		= $request->get_params();
		$cast_id		 	= $parameters['cast_id'];
		$args = [
			'p' 		=> $cast_id,
			'post_type'	=> 'person'
		];
		$wp_query 		= new WP_Query($args);
		$response_data 	= $wp_query->post ?? null;

		if ($response_data === null || empty($response_data))
			return comman_custom_response(array(
				"status"	=> true,
				"message"	=> __("No data found.", STA_TEXT_DOMAIN),
				"data" 		=> []
			));

		$type 			= $parameters['type'] ?? "all";
		$movie_cast 	= (array) get_post_meta($cast_id, '_movie_cast', true);
		$movie_crew 	= (array) get_post_meta($cast_id, '_movie_crew', true);
		$tv_show_cast 	= (array) get_post_meta($cast_id, '_tv_show_cast', true);
		$tv_show_crew 	= (array) get_post_meta($cast_id, '_tv_show_crew', true);

		$args = array(
			'post_type' 		=> array("movie", "tv_show"),
			'post_status' 		=> 'publish',
			'order'             => 'ASC',
			'posts_per_page'    => $parameters['posts_per_page'] ?? 10,
			'paged' 			=> $parameters['paged'] ?? 1,
			'suppress_filters'  => 0
		);

		switch ($type):
			case 'movie':
				$args['post__in'] = $movie_cast;
				continue;
			case 'movie':
				$args['post__in'] = $movie_crew;
				continue;
			case 'tv_show':
				$args['post__in'] = $tv_show_cast;
				continue;
			case 'tv_show':
				$args['post__in'] = $tv_show_crew;
				continue;
			case 'most_viewed':
				$args['orderby'] 	= 'meta_value_num';
				$args['order']		= 'DESC';
				$args['meta_query']	= array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key' => 'post_views_count',
						),
						array(
							'key' => 'tv_show_views_count',
						)
					),
				);
			default:
				$args['post__in'] = array_merge($movie_cast, $movie_crew, $tv_show_cast, $tv_show_crew);
		endswitch;
		
		$cast_movie = new WP_Query($args);

		if ($cast_movie->have_posts()) {
			while ($cast_movie->have_posts()) {
				$cast_movie->the_post();
				$response[] = self::streamit_person_work_history(get_the_ID(), $cast_id);
			}
		}


		if (count($response) > 0)
			return comman_custom_response([
				"status"	=> true,
				"message"	=> __("Details.", STA_TEXT_DOMAIN),
				"data" 		=>  $response
			]);

		return comman_custom_response([
			"status"	=> true,
			"message"	=> __("No data found.", STA_TEXT_DOMAIN),
			"data" 		=>  []
		]);
	}

	public static function streamit_person_work_history($id, $cast_id)
	{
		$list = get_post($id);

		if (empty($list) && $list === null) {
			return [];
		}
		$id = $list->ID;

		$image = wp_get_attachment_image_src(get_post_thumbnail_id($id), [300, 300]);
		$character_name = streamit_cast_detail_helper($id, '_cast', $cast_id);
		$temp = [
			'id'                => $id,
			'title'             => get_the_title($id),
			'image'             => !empty($image) ? $image[0] : null,
			'post_type'         => $list->post_type,
			'character_name'    => is_array($character_name) ? "" : $character_name,
			'share_url'         => get_the_permalink($id)
		];

		if ($list->post_type == 'tv_show') {
			$tv_show_season = get_post_meta($id, '_seasons', true);
			$temp['total_seasons'] = 0;
			$year = null;
			if (!empty($tv_show_season)) {
				$temp['total_seasons'] = count($tv_show_season);
				$year = collect($tv_show_season)->map(function ($tv_show_season) use ($year) {
					return $tv_show_season;
				})->pluck('year')->implode('-');
			}
			$temp['release_year'] = $year;
		} else {
			$release_year 	= get_post_meta($id, '_movie_release_date', true);
			$run_time 		= get_post_meta($id, '_movie_run_time', true);
			$temp['run_time']     = $run_time;
			$temp['release_year'] = !empty($release_year) ? date('Y', $release_year) : '';
		}

		return $temp;
	}
}
