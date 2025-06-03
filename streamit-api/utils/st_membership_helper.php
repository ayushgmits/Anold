<?php

use Includes\baseClasses\STNotifications;

add_filter("pmpro_rest_api_permissions", function ($permission) {
    return true;
});

function user_level_response($levels, $user_id)
{
    if (!$levels) return $levels;

    $levels->google_in_app_purchase_identifier = get_pmpro_membership_level_meta($levels->id, "st_google_in_app_purchase_identifier", true);
    $levels->apple_in_app_purchase_identifier = get_pmpro_membership_level_meta($levels->id, "st_apple_in_app_purchase_identifier", true);

    if (!empty($levels->enddate)) return $levels;

    $startingDate = $levels->startdate;
    if (!empty($levels->expiration_number)) {
        $expiration_number = $levels->expiration_number;
        $expiration_period = $levels->expiration_period;
        $enddate = date('Y-m-d', strtotime("+$expiration_number $expiration_period", $startingDate));
    } elseif (!empty($levels->cycle_number)) {
        $cycle_number = $levels->cycle_number;
        $cycle_period = $levels->cycle_period;
        $enddate = date('Y-m-d', strtotime("+$cycle_number $cycle_period", $startingDate));
    }

    $levels->enddate = isset($enddate) ? strtotime($enddate) : null;
    return $levels;
}
add_filter("pmpro_get_membership_level_for_user", "user_level_response", 10, 2);

function st_get_user_email_ids($user_id)
{
    if (is_array($user_id)) {
        $email_ids = [];
        foreach ($user_id as $id) {
            $user = get_userdata($id);
            if (!$user) continue;
            $email_ids[] = $user->user_email;
        }
        return $email_ids;
    }

    $user = get_userdata($user_id);
    if (!$user) return [];
    $email_id = $user->user_email;
    return $email_id;
}

function st_after_change_membership_level($level_id, $user_id)
{
    $level = pmpro_getLevel($level_id);
    if (!$level) return;

    $data = [
        "user_id"       => $user_id,
        "data"          => [
            "id"    => $level_id,
            "type"  => 'membership-level-changed'
        ],
        "heading"       => [
            "en"    => __("Membership", STA_TEXT_DOMAIN)
        ],
        "content"       => [
            "en"    => sprintf(__("You've subscribed to the %s", STA_TEXT_DOMAIN), $level->name)
        ]
    ];
    STNotifications::send($data);

    update_user_meta($user_id, "streamit_loggedin_devices", false);
}
add_action('pmpro_after_change_membership_level', 'st_after_change_membership_level', 10, 2);

function st_get_pmp_free_plans()
{
    $levels = pmpro_getAllLevels();
    $free_levels = [];
    foreach ($levels as $level) {
        if (pmpro_isLevelFree($level)) {
            $free_levels[$level->id] = $level;
        }
    }

    return $free_levels;
}

/**
 * Get all PMPro membership levels.
 *
 * @param bool $include_hidden Include levels marked as hidden/inactive.
 * @param bool $use_cache      If false, use $pmpro_levels global. If true use other caches.
 * @param bool $force          Resets the static var caches as well.
 */
function st_pmpro_getAllLevels($user_id = false, $include_hidden = false)
{
    global $pmpro_levels, $wpdb;

    // build query
    $sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels ";
    if (!$include_hidden) {
        $sqlQuery .= ' WHERE allow_signups = 1 ORDER BY id';
    }

    // get levels from the DB
    $raw_levels     = $wpdb->get_results($sqlQuery);
    if (!$raw_levels || is_wp_error($raw_levels)) return [];

    $pmpro_levels       = array();
    $user_levels        = $user_id ? array_column(pmpro_getMembershipLevelsForUser($user_id, true), "ID") : false;
    $pmpro_checkout_url = pmpro_url("checkout");
    foreach ($raw_levels as $raw_level) {
        $raw_level->initial_payment     = pmpro_round_price($raw_level->initial_payment);
        $raw_level->is_initial          = !$user_levels || !in_array($raw_level->id, $user_levels);
        $raw_level->billing_amount      = pmpro_round_price($raw_level->billing_amount);
        $raw_level->trial_amount        = pmpro_round_price($raw_level->trial_amount);
        $raw_level->checkout_url        = add_query_arg("level", $raw_level->id, $pmpro_checkout_url);
        $raw_level->product_id          = st_get_product_by_level_id($raw_level->id);
        $raw_level->google_in_app_purchase_identifier = get_pmpro_membership_level_meta($raw_level->id, "st_google_in_app_purchase_identifier", true);
        $raw_level->apple_in_app_purchase_identifier = get_pmpro_membership_level_meta($raw_level->id, "st_apple_in_app_purchase_identifier", true);
        $pmpro_levels[$raw_level->id]   = $raw_level;
    }

    return $pmpro_levels;
}

function streamit_user_plans($user_id)
{

    if (!is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) return [];

    $user_level = pmpro_getMembershipLevelsForUser($user_id, false)[0] ?? false;
    if (!$user_level) return [];

    $limit_login_settings   = st_get_limit_login_settings();
    $users_current_plan     = [
        "subscription_plan_id"      => $user_level->id,
        'subscription_id'           => $user_level->subscription_id,
        "start_date"                => (!empty($user_level->startdate) ? ucfirst(date_i18n(get_option('date_format'), $user_level->startdate)) : ''),
        "expiration_date"           => (!empty($user_level->enddate) ? ucfirst(date_i18n(get_option('date_format'), $user_level->enddate)) : ''),
        "subscription_plan_name"    => $user_level->name,
        "initial_payment"           => $user_level->initial_payment,
        "billing_amount"            => $user_level->billing_amount,
        "google_in_app_purchase_identifier" => get_pmpro_membership_level_meta($user_level->id, "st_google_in_app_purchase_identifier", true),
        "apple_in_app_purchase_identifier" => get_pmpro_membership_level_meta($user_level->id, "st_apple_in_app_purchase_identifier", true),
        'status'                    => get_user_history($user_level->subscription_id)
    ];

    if ($limit_login_settings) {
        $users_current_plan["default_login_limit"]       = $limit_login_settings["default_limit"];
        $users_current_plan["current_plan_login_limit"]  = $limit_login_settings[$user_level->id];
    }

    return (object) $users_current_plan;
}

function restrictedPlanList($post_id, $user_id)
{
    if (!is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) return [];

    $plan = pmpro_has_membership_access($post_id, $user_id, true);
    $data['user_has_access'] = pmpro_has_membership_access($post_id, $user_id, false);

    $plan_list = [];
    for ($i = 0; $i < count($plan[1]); $i++) {
        $plan_list[] = [
            'id'    => $plan[1][$i],
            'label' => $plan[2][$i]
        ];
    }
    $data['subscription_levels'] = $plan_list;


    return $data;
}

function st_get_pmp_orders($results)
{
    $orders = [];

    foreach ($results  as $order) {
        $billing = [
            "name"      => $order->billing_name,
            "street"    => $order->billing_street,
            "city"      => $order->billing_city,
            "state"     => $order->billing_state,
            "zip"       => $order->billing_zip,
            "country"   => $order->billing_country,
            "phone"     => $order->billing_phone
        ];

        $membership_level = pmpro_getLevel($order->membership_id);

        $orders[] = [
            "id"                            => $order->id,
            "code"                          => $order->code,
            "user_id"                       => $order->user_id,
            "in_app_purchase_identifier"    => get_pmpro_membership_order_meta($order->id, "st_in_app_purchase_identifier", true),
            "membership_id"                 => $order->membership_id,
            "membership_name"               => $membership_level ? $membership_level->name : "",
            "billing"                       => $billing,
            "subtotal"                      => $order->subtotal,
            "tax"                           => $order->tax,
            "total"                         => $order->total,
            "payment_type"                  => $order->payment_type,
            "cardtype"                      => $order->cardtype,
            "accountnumber"                 => $order->accountnumber,
            "expirationmonth"               => $order->expirationmonth,
            "expirationyear"                => $order->expirationyear,
            "status"                        => $order->status,
            "gateway"                       => $order->gateway,
            "gateway_environment"           => $order->gateway_environment,
            "payment_transaction_id"        => $order->payment_transaction_id,
            "subscription_transaction_id"   => $order->subscription_transaction_id,
            "timestamp"                     => $order->timestamp,
            "affiliate_id"                  => $order->affiliate_id,
            "affiliate_subid"               => $order->affiliate_subid,
            "notes"                         => $order->notes,
            "checkout_id"                   => $order->checkout_id
        ];
    }
    return $orders;
}
function st_change_membership_level($level_id, $user_id, $args = [])
{
    global $wpdb;

    $pmpro_level = pmpro_getLevel($level_id);
    $args = wp_parse_args(
        $args,
        ["discount_code" => ""]
    );

    $startdate = current_time("mysql");
    $startdate = apply_filters("pmpro_checkout_start_date", $startdate, $user_id, $pmpro_level);

    if (!empty($pmpro_level->expiration_number)) {
        if ($pmpro_level->expiration_period == 'Hour') {
            $enddate =  date("Y-m-d H:i:s", strtotime("+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time("timestamp")));
        } else {
            $enddate =  date("Y-m-d 23:59:59", strtotime("+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period, current_time("timestamp")));
        }
    } else {
        $enddate = "NULL";
    }

    $enddate = apply_filters("pmpro_checkout_end_date", $enddate, $user_id, $pmpro_level, $startdate);

    if (!empty($args["discount_code"])) {
        $discount_code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql($args["discount_code"]) . "' LIMIT 1");
    } else {
        $discount_code_id = "";
    }

    $custom_level = array(
        'user_id'         => $user_id,
        'membership_id'   => $pmpro_level->id,
        'code_id'         => $discount_code_id,
        'initial_payment' => pmpro_round_price($pmpro_level->initial_payment),
        'billing_amount'  => pmpro_round_price($pmpro_level->billing_amount),
        'cycle_number'    => $pmpro_level->cycle_number,
        'cycle_period'    => $pmpro_level->cycle_period,
        'billing_limit'   => $pmpro_level->billing_limit,
        'trial_amount'    => pmpro_round_price($pmpro_level->trial_amount),
        'trial_limit'     => $pmpro_level->trial_limit,
        'startdate'       => $startdate,
        'enddate'         => $enddate
    );

    return pmpro_changeMembershipLevel($custom_level, $user_id, "changed");
}
if (!function_exists("st_get_product_by_level_id")) {
    function st_get_product_by_level_id($level_id)
    {
        $args = [
            'post_type'         => 'product',
            'post_status'       => 'publish',
            'meta_key'          => '_membership_product_level',
            'meta_value'        => $level_id,
            'posts_per_page'    => 1
        ];
        $wc_query = get_posts($args);

        wp_reset_query();
        return $wc_query[0]->ID ?? 0;
    }
}

function st_pmp_web_view_do_user_login()
{
    $is_webview = $_SERVER["HTTP_STREAMIT_WEBVIEW"] ?? false;
    if (!$is_webview || is_admin()) return;

    if (!isset($_SERVER["HTTP_AUTHORIZATION"]) || empty($_SERVER["HTTP_AUTHORIZATION"])) return;

    $user   = streamit_validate_custom_token($_SERVER["HTTP_AUTHORIZATION"]);
    if (is_wp_error($user)) return;

    if (is_user_logged_in() && get_current_user_id() == $user->data->user->id)
        wp_set_auth_cookie(get_current_user_id());

    wp_clear_auth_cookie();
    $user   = get_userdata($user->data->user->id);

    if (!$user) return;
    $user = $user->data;

    wp_set_current_user($user->ID, $user->user_login);
    wp_set_auth_cookie($user->ID);
}
add_action("init", "st_pmp_web_view_do_user_login");

// ======= add fields of in-app purchase identifier keys for IOS and Google Stores ===== //
function st_additional_level_fields($level)
{
    global $st_app_options;
    $pmpro_paymnet_type = $st_app_options["st_pmp_options"]["payment_type"] ?? false;
    if ($pmpro_paymnet_type != 2) return;

    $google_purchase_identifier = get_pmpro_membership_level_meta($level->id, "st_google_in_app_purchase_identifier", true);
    $apple_purchase_identifier = get_pmpro_membership_level_meta($level->id, "st_apple_in_app_purchase_identifier", true);
?>
    <hr>
    <h3><?php _e("In-App Purchase"); ?></h3>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row" valign="top"><label><?php esc_html_e('Play store identifier (Google)', 'socialv-api'); ?></label></th>
                <td>
                    <input name="google_in_app_purchase_identifier" type="text" value="<?php echo esc_attr($google_purchase_identifier); ?>" class="regular-text" />
                    <p for="google_in_app_purchase_identifier">
                        <?php esc_html_e('Enter in-app purchase identifier for the Play Store (Andorid device: Google play store).', 'socialv-api'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><label><?php esc_html_e('App store identifier (IOS)', 'socialv-api'); ?></label></th>
                <td>
                    <input name="apple_in_app_purchase_identifier" type="text" value="<?php echo esc_attr($apple_purchase_identifier); ?>" class="regular-text" />
                    <p for="apple_in_app_purchase_identifier">
                        <?php esc_html_e('Enter in-app purchase identifier for the App Store (IOS device).', 'socialv-api'); ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
<?php
}
add_action("pmpro_membership_level_after_other_settings", "st_additional_level_fields", 1, 9);

// ======= save fields of in-app purchase identifier keys for IOS and Google Stores ===== //
function st_save_additional_level_fields($level_id)
{
    global $st_app_options;
    $pmpro_paymnet_type = $st_app_options["st_pmp_options"]["payment_type"] ?? false;
    if ($pmpro_paymnet_type != 2) return;

    if (isset($_REQUEST['google_in_app_purchase_identifier']))
        update_pmpro_membership_level_meta($level_id, 'st_google_in_app_purchase_identifier', esc_html(trim($_REQUEST['google_in_app_purchase_identifier'])));

    if (isset($_REQUEST['apple_in_app_purchase_identifier']))
        update_pmpro_membership_level_meta($level_id, 'st_apple_in_app_purchase_identifier', esc_html(trim($_REQUEST['apple_in_app_purchase_identifier'])));
}
add_action("pmpro_save_membership_level", "st_save_additional_level_fields");
function st_alter_pmpro_currencies($currencies)
{
    $currencies['INR'] = !is_array($currencies['INR']) ? [
        'name' => $currencies['INR'],
        'symbol' => '&#8377;'
    ] : $currencies['INR'];
    
    return $currencies;
}
add_filter("pmpro_currencies", 'st_alter_pmpro_currencies');
