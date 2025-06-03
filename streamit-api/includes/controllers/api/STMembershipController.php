<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\STBase;
use MemberOrder;
use PMProEmail;
use stdClass;
use WP_REST_Server;

class STMembershipController extends STBase
{

	public $module = 'membership';

	public $nameSpace;

	function __construct()
	{

		$this->nameSpace = STREAMIT_API_NAMESPACE;

		add_action('rest_api_init', function () {

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/levels',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'rest_membership_levels'],
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				$this->nameSpace . '/api/v1/' . $this->module,
				'/orders',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [$this, 'rest_order_list'],
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => [$this, 'generate_order'],
						'permission_callback' => '__return_true',
					),
				)
			);
		});
	}


			public function rest_membership_levels($request) {
		$data    = stValidationToken($request);
		$user_id = $data['user_id'] ?? 0;
		$logged_in_devices = get_user_meta($user_id, 'streamit_loggedin_devices', true);
		$is_valid_token = !empty($logged_in_devices);
	
		$levels_filter = $request->get_param('levels');
	
		// Get all levels (fetching from products in your case)
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => '_is_membership_level',
					'value'   => 'yes',
					'compare' => '='
				]
			]
		];
	
		$query = new \WP_Query($args);
		$products = [];
	
		// Get the active subscription IDs for the user
		$active_subscriptions = $this->get_user_active_subscription_ids($user_id);
	
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$product_id = get_the_ID();
				$product    = wc_get_product($product_id);
	
				$level_id = get_post_meta($product_id, '_membership_product_level', true);
				if (empty($level_id)) continue;
	
				// Check if the current product's level is in the active subscriptions
				if (in_array($level_id, $active_subscriptions)) {
					continue; // Skip if this level is already active for the user
				}
	
				$level = pmpro_getLevel($level_id);
				if (empty($level)) continue;
	
				// Build base product data dynamically
				$product_data = [
					'id'         => (string) $product_id,
					'name'       => $product->get_name(),
					'price'      => $product->get_price(),
					'permalink'  => get_permalink($product_id),
					'thumbnail'  => get_the_post_thumbnail_url($product_id, 'medium'),
					'checkout_url' => add_query_arg('level', $level_id, site_url('/checkout-2/')),
					'add_to_cart_url' => wc_get_cart_url() . '?add-to-cart=' . $product_id,
					'membership_level_name' => $level->name,
				];
	
				// Add PMPro level object properties dynamically
				$level_props = get_object_vars($level);
				foreach ($level_props as $key => $val) {
					$product_data[$key] = is_scalar($val) ? $val : null;
				}
				
				$product_data['description'] = html_entity_decode(wp_strip_all_tags($level->description));
                $product_data['confirmation'] = html_entity_decode(wp_strip_all_tags($level->confirmation));

				// Add PMPro meta fields (like coins, bonus_coins, etc.) dynamically
				$meta_fields = get_post_meta($level_id);

				// Avoid overwriting values that are already added to the $product_data array
				foreach ($meta_fields as $meta_key => $meta_value) {
					// Add to $product_data only if it's not a coin-related field
					if (!in_array($meta_key, ['_coins', '_bonus_coins', '_free_coins', '_total_coins'])) {
						$product_data[$meta_key] = maybe_unserialize($meta_value[0]);
					}
				}

				$coins        = maybe_unserialize($meta_fields['_coins'][0] ?? '0');
				$bonus_coins  = maybe_unserialize($meta_fields['_bonus_coins'][0] ?? '0');
				$free_coins   = maybe_unserialize($meta_fields['_free_coins'][0] ?? '0');
				$total_coins  = maybe_unserialize($meta_fields['_total_coins'][0] ?? '0');

				// Only add coins-related fields once
				$product_data['coins']       = $coins ?: "0";
				$product_data['bonus_coins'] = $bonus_coins ?: "0";
				$product_data['free_coins']  = $free_coins ?: "0";
				$product_data['total_coins'] = $total_coins ?: "0";

				
				$products[] = $product_data;
			}
			wp_reset_postdata();
		}
	
		// ✅ Filter dynamically based on _custom_coins_level presence
		if (empty($levels_filter)) {
			$products = array_filter($products, fn($p) => empty($p['_custom_coins_level']));
		} else {
			if ($levels_filter === 's' || $levels_filter === 'subscription_level') {
				$products = array_filter($products, fn($p) => empty($p['_custom_coins_level']));
			} elseif ($levels_filter === 'c' || $levels_filter === 'coins_level') {
				$products = array_filter($products, fn($p) => !empty($p['_custom_coins_level']));
			}
		}
	
		return comman_custom_response([
			'status'  => true,
			'message' => __('Membership products fetched successfully.', STA_TEXT_DOMAIN),
			'data'    => array_values($products),
			'is_valid_token' => $is_valid_token
		]);
	}

		private function get_user_active_subscription_ids($user_id)
		{
			global $wpdb;
			$table = $wpdb->prefix . 'pmpro_subscriptions';

			$results = $wpdb->get_col($wpdb->prepare(
				"SELECT membership_level_id FROM {$table} WHERE user_id = %d AND status = 'active'",
				$user_id
			));

			return $results ?: [];
		}


	
	public function rest_order_list($request)
	{
		$data = stValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$user_id = $data['user_id'];

		// Retrieve logged-in devices and check token validity.
		$logged_in_devices = get_user_meta($user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);

		global $wpdb;

		$parameters = $request->get_params();

		$page 		= $parameters["page"] ?? 1;
		$per_page 	= $parameters["per_page"] ?? 10;


		$pgstrt = absint(($page - 1) * $per_page) . ', ';

		$limits = 'LIMIT ' . $pgstrt . $per_page;

		$order_result = $wpdb->get_results($wpdb->prepare("SELECT o.* FROM $wpdb->pmpro_membership_orders AS o LEFT JOIN $wpdb->postmeta AS pm ON pm.post_id = o.membership_id AND pm.meta_key = '_custom_coins_level' AND (pm.meta_value IS NOT NULL AND pm.meta_value != '') WHERE o.user_id = %d AND pm.post_id IS NULL ORDER BY o.id DESC $limits", $user_id));

		if (!$order_result)
			return comman_custom_response([
				"status" 	=> true,
				"messate" 	=>  __("No data found.", STA_TEXT_DOMAIN),
				"data" 		=> [],
				"is_valid_token" => $is_valid_token
			]);

		$response = st_get_pmp_orders($order_result);

		return comman_custom_response([
			"status" 	=> true,
			"messate" 	=>  __("User orders.", STA_TEXT_DOMAIN),
			"data" 		=> $response,
			"is_valid_token" => $is_valid_token
		]);
	}

	public function generate_order($request)
	{
		$data = stValidationToken($request);

		if (!isset($data['user_id']))
			return comman_custom_response($data, $data['status_code']);

		global $wpdb;

		$current_user_id = $data['user_id'];
		$user = get_userdata($current_user_id);

		$parameters = $request->get_params();
		$parameters = stRecursiveSanitizeTextFields($parameters);

		$leve_id = $parameters["level_id"];


		session_start();

		$pmpro_level = pmpro_getLevel($leve_id);
		$discount_code = isset($parameters["discount_code"]) ? $parameters["discount_code"] : "";

		$morder                   = new MemberOrder();
		$morder->user_id          = $current_user_id;
		$morder->membership_id    = $pmpro_level->id;
		$morder->membership_name  = $pmpro_level->name;
		$morder->discount_code    = $discount_code;
		$morder->InitialPayment   = pmpro_round_price($pmpro_level->initial_payment);
		$morder->PaymentAmount    = pmpro_round_price($parameters['billing_amount']);
		$morder->couponamount	= pmpro_round_price($parameters['coupon_amount']);
		$morder->subtotal		= pmpro_round_price($parameters['billing_amount']);
		$morder->total	= pmpro_round_price($parameters['coupon_amount']);
		$morder->ProfileStartDate = date_i18n("Y-m-d\TH:i:s", current_time("timestamp"));
		$morder->BillingPeriod    = $pmpro_level->cycle_period;
		$morder->BillingFrequency = $pmpro_level->cycle_number;
		if ($pmpro_level->billing_limit) {
			$morder->TotalBillingCycles = $pmpro_level->billing_limit;
		}
		if (pmpro_isLevelTrial($pmpro_level)) {
			$morder->TrialBillingPeriod    = $pmpro_level->cycle_period;
			$morder->TrialBillingFrequency = $pmpro_level->cycle_number;
			$morder->TrialBillingCycles    = $pmpro_level->trial_limit;
			$morder->TrialAmount           = pmpro_round_price($pmpro_level->trial_amount);
		}

		$is_card_payment = ($parameters["payment_by"] === "card");
		if ($is_card_payment) {
			// Credit card values.
			$morder->cardtype              = $parameters["card_details"]["card_type"];
			$morder->accountnumber         = "XXXX XXXX XXXX " . $parameters["card_details"]["card_number"];
			$morder->expirationmonth       = $parameters["card_details"]["exp_month"];
			$morder->expirationyear        = $parameters["card_details"]["exp_year"];
			$morder->ExpirationDate        = "";
			$morder->ExpirationDate_YdashM = "";
			$morder->CVV2                  = "";
		}

		// Not saving email in order table, but the sites need it.
		$morder->Email = $user->user_email;

		// Save the user ID if logged in.
		if ($current_user_id) {
			$morder->user_id = $current_user_id;
		}

		$billing_details = $parameters["billing_details"];
		// Sometimes we need these split up.
		$morder->FirstName = $billing_details["first_name"];
		$morder->LastName  = $billing_details["last_name"];
		$morder->Address1  = $billing_details["user_address"];
		$morder->Address2  = "";

		// Set other values.
		$morder->billing          		= new stdClass();
		$morder->billing->name    		= $morder->FirstName . " " . $morder->LastName;
		$morder->billing->street  		= trim($morder->Address1 . " " . $morder->Address2);
		$morder->billing->city    		= $billing_details["user_city"];
		$morder->billing->state   		= $billing_details["user_state"];
		$morder->billing->country 		= $billing_details["user_country"];
		$morder->billing->zip     		= $billing_details["user_postal_code"];
		$morder->billing->phone   		= $billing_details["user_phone"];
		$morder->gateway 				= $parameters["gateway"];
		$morder->gateway_environment 	= $parameters["gateway_mode"];
		$morder->setGateway();


		$morder->payment_transaction_id 		= $parameters["transation_id"];
		$morder->subscription_transaction_id 	= $parameters["transation_id"];

		// Set up level var.
		$morder->getMembershipLevelAtCheckout();

		// Set tax.
		$initial_tax = $morder->getTaxForPrice($morder->InitialPayment);
		$recurring_tax = $morder->getTaxForPrice($morder->PaymentAmount);

		// Set amounts.
		$morder->initial_amount = pmpro_round_price((float)$morder->InitialPayment + (float)$initial_tax);
		$morder->subscription_amount = pmpro_round_price((float)$morder->PaymentAmount + (float)$recurring_tax);

		// die;
		// Filter for order, since v1.8
		$morder = apply_filters('pmpro_checkout_order', $morder);

		$order_id = (int) $morder->saveOrder();

		do_action("pmpro_after_checkout", $morder->user_id, $morder);

		// Check if we should send emails.
		if (apply_filters('pmpro_send_checkout_emails', true, $morder)) {
			// Set up some values for the emails.
			$user                   = get_userdata($morder->user_id);
			$user->membership_level = $pmpro_level;        // Make sure that they have the right level info.

			// Send email to member.
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutEmail($user, $morder);

			// Send email to admin.
			$pmproemail = new PMProEmail();
			$pmproemail->sendCheckoutAdminEmail($user, $morder);
		}


		session_destroy();

		if (!$order_id)
			return comman_custom_response([
				"status" => false,
				"message" =>  __("Something Wrong ! Try Again.", "streamit-api")
			]);

		// add in-app purchase identifer if payment type is in-app
		if (isset($parameters['in_app_purchase_identifier']))
			add_pmpro_membership_order_meta($order_id, 'st_in_app_purchase_identifier', trim($parameters['in_app_purchase_identifier']));

		if (!$is_card_payment) {
			add_pmpro_membership_order_meta($order_id, $parameters["payment_by"], $parameters["meta_value"]);
		}
		if (isset($parameters["discount_code"]))
			add_pmpro_membership_order_meta($order_id, "discount_code", $parameters["discount_code"]);

		st_change_membership_level($leve_id, $current_user_id, ["discount_code" => $discount_code]);

		$order_result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->pmpro_membership_orders WHERE `id` = %d", $order_id));

		if (!$order_result) return comman_custom_response([
			"status" => true,
			"message" =>  __("Membership order not found", "streamit-api")
		]);

		$response = st_get_pmp_orders($order_result);
		// print_r(reset($response));die;

		// Retrieve logged-in devices to check token validity.
		$logged_in_devices = get_user_meta($current_user_id, "streamit_loggedin_devices", true);
		$is_valid_token = !empty($logged_in_devices);
		
		return comman_custom_response([
			"status" 	=> true,
			"message" 	=>  __("Membership orders", "streamit-api"),
			"data" 		=> reset($response),
			"is_valid_token" => $is_valid_token
		]);
	}
}
