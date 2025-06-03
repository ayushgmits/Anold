<?php

namespace Includes\baseClasses;

use MemberOrder;

class STPmp
{

    public function init()
    {
        if (!is_streamit_theme_active()) {
            add_filter('pmpro_membershiplevels_page_action_links', array($this, 'st_import_pms_button'));
            add_action("admin_init", [$this, "st_import_pmp"]);
            add_action('admin_notices', [$this, 'st_import_pmp_notice']);
        }
    }

    function st_import_pms_button($pmpro_membershiplevels_page_action_links)
    {
        $pmpro_membershiplevels_page_action_links['import-pms-plans'] = array(
            'url' => admin_url('admin.php?page=pmpro-membershiplevels&st-pmp-import-plans=1&st-notice=1'),
            'name' => esc_html__('Import PMS Plans', STA_TEXT_DOMAIN),
            'icon' => 'plus import_pms_plans'
        );

        $pmpro_membershiplevels_page_action_links['import-pms-members-info'] = array(
            'url' => admin_url('admin.php?page=pmpro-membershiplevels&st-pmp-import-member=1&st-notice=1'),
            'name' => esc_html__('Import PMS Member\'s Detail', STA_TEXT_DOMAIN),
            'icon' => 'plus import_pms_members_info'
        );

        return $pmpro_membershiplevels_page_action_links;
    }
    function st_import_pmp_notice()
    {
        if (!isset($_GET["st-notice"])) return;
?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Imported Successfully!', STA_TEXT_DOMAIN); ?></p>
        </div>
<?php
    }
    function st_import_pmp()
    {
        if (isset($_GET["st-pmp-import-plans"]) && $_GET["st-pmp-import-plans"])
            $this->st_import_PMS_plans_and_restriction_content();

        if (isset($_GET["st-pmp-import-member"]) && $_GET["st-pmp-import-member"])
            $this->st_import_PMS_member_details();
    }

    function st_import_PMS_plans_and_restriction_content()
    {
        $this->st_import_PMS_plans();
        $this->st_import_restrict_content();
    }
    function st_import_PMS_plans()
    {
        global $wpdb;

        $subscription_plans = pms_get_subscription_plans(false); // get PMS subscription plans list
        $pmpro_levels = pmpro_sort_levels_by_order(pmpro_getAllLevels(true, false)); // get PMP subscription plans list and append at last

        //return if there is no PMS plans
        if (empty($subscription_plans)) {
            echo esc_html__('No PMS plans to import', STA_TEXT_DOMAIN);
            return;
        }

        foreach ($subscription_plans as $key => $pms_plan) {
            if ($pmpro_levels[$pms_plan->id]->id == $pms_plan->id) continue; //skips current loop if plan id is already present

            $import_pmp_plans = array(); //empty the array for next loop

            $expiration_number = (!empty($pms_plan->duration)) ? $pms_plan->duration : 0;
            $expiration_period = (!empty($pms_plan->duration_unit)) ? ucfirst($pms_plan->duration_unit) : 0;
            $allow_signup = ($pms_plan->status == 'active') ? 1 : 0;

            //==========WARNING: DO NOT CHANGE THE ARRAY ORDER============
            $import_pmp_plans = array(
                'id' => $pms_plan->id,                     // PMS Plan ID
                'name' => $pms_plan->name,                 // PMS Plan Name
                'description' => $pms_plan->description,   // PMS Plan Description
                'confirmation' => '',                      // Featured not provided by PMS, so default empty
                'initial_payment' => $pms_plan->price,     // PMS Plan Price
                'billing_amount' => $pms_plan->price,      // PMS Plan Price
                'cycle_number' => $expiration_number,      // PMS expiry number
                'cycle_period' => $expiration_period,      // PMS expiry period
                'billing_limit' => 0,                      // Featured not provided by PMS, so default 0
                'trial_amount' => 0,                       // Featured not provided by PMS, so default 0
                'trial_limit' => 0,                        // Featured not provided by PMS, so default 0
                'expiration_number' => 0,                  // Featured not provided by PMS, so default 0
                'expiration_period' => 0,                  // Featured not provided by PMS, so default 0
                'allow_signups' => $allow_signup           // PMS is plan active
            );

            pmpro_insert_or_replace(
                $wpdb->pmpro_membership_levels,
                $import_pmp_plans, //our newly created array to import PMS plans to PMP
                array(
                    '%d',        //id
                    '%s',        //name
                    '%s',        //description
                    '%s',        //confirmation
                    '%f',        //initial_payment
                    '%f',        //billing_amount
                    '%d',        //cycle_number
                    '%s',        //cycle_period
                    '%d',        //billing_limit
                    '%f',        //trial_amount
                    '%d',        //trial_limit
                    '%d',        //expiration_number
                    '%s',        //expiration_period
                    '%d',        //allow_signups
                )
            );
        }
        // echo esc_html__('PMS Plans Successfully Imported.', STA_TEXT_DOMAIN);
        return;
    }

    function st_import_restrict_content()
    {
        global $wpdb;

        $post_types = apply_filters('st_import_pms_restrict_post_type', array('movie', 'tv_show', 'episode', 'video', 'person', 'post', 'page'));
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'all',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'pms-content-restrict-subscription-plan',
                )
            ),
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );

        query_posts($args);
        if (have_posts()) :
            $sql_values = '';
            while (have_posts()) :
                the_post();
                $post_id = get_the_ID();
                $membership_plans_id = get_post_meta($post_id, 'pms-content-restrict-subscription-plan');

                if (!empty($membership_plans_id) && is_array($membership_plans_id)) {
                    foreach ($membership_plans_id as $membership_plan_id) {
                        $sql_values .= '(' . intval($membership_plan_id) . ',' . intval($post_id) . '),';
                    }
                }
            endwhile;
        endif;
        wp_reset_query();

        if (isset($sql_values) && !empty($sql_values)) {
            $sql_values = rtrim($sql_values, ','); // remove last comma
            $sql = "INSERT INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id)
                VALUES $sql_values";

            $result = $wpdb->query($sql);

            if (!$result) {
                // echo esc_html__("Posts Restricted Content Already Imported", STA_TEXT_DOMAIN);
                return;
            }
        }
        // echo esc_html__('Sucessfully Imported Posts Rescticted Content.', STA_TEXT_DOMAIN);
        return;
    }
    function st_import_PMS_member_details()
    {
        $this->st_import_PMS_members_data();
        $this->st_import_pms_payments();
    }


    function st_import_PMS_members_data()
    {
        global $wpdb;

        $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pms_member_subscriptions");
        if (empty($members)) {
            // echo esc_html__('No PMS Members Data to Import.', STA_TEXT_DOMAIN);
            return;
        }

        $user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users");
        if (!is_array(($user_ids))) die;

        $sql_values = '';

        foreach ($members as $member) {
            if (in_array($member->user_id, $user_ids)) continue;

            $user_id = $member->user_id;
            $member_id = $member->subscription_plan_id;
            $code_id = 0;
            $initial_payment = $billing_amount = $member->billing_amount;
            $cycle_number = 0;
            $cycle_period = '';
            $billing_limit = 0;
            $trial_amount = 0;
            $trial_limit = 0;
            $startdate = $member->start_date;
            $enddate = $member->expiration_date;

            $sql_values .= '(' .
                "'" . $user_id . "'," .           //user id
                "'" . $member_id . "'," .        //subscription_plan_id
                "'" . $code_id . "'," .          //discount code used or not value 0 or 1
                "'" . $initial_payment . "'," .  //plan amount
                "'" . $billing_amount . "'," .   //
                "'" . $cycle_number . "'," .
                "'" . $cycle_period . "'," .
                "'" . $billing_limit . "'," .
                "'" . $trial_amount . "'," .
                "'" . $trial_limit . "'," .
                "'" . $startdate . "'," .
                "'" . $enddate . "'" .
                '),';
        }

        if (isset($sql_values) && !empty($sql_values)) {
            $sql_values = rtrim($sql_values, ','); // remove last comma

            $sql = "INSERT INTO {$wpdb->pmpro_memberships_users}
            (`user_id`, `membership_id`, `code_id`, `initial_payment`, `billing_amount`, `cycle_number`, `cycle_period`, `billing_limit`, `trial_amount`, `trial_limit`, `startdate`, `enddate`)
            VALUES $sql_values;";

            $result = $wpdb->query($sql);

            if (!$result) {
                // echo esc_html__("Members Data Already Imported.", STA_TEXT_DOMAIN);
                return;
            }

            // echo esc_html__('Sucessfully Imported Members Data.', STA_TEXT_DOMAIN);
            return;
        } else {
            // echo esc_html__("Members Data Already Imported.", STA_TEXT_DOMAIN);
            return;
        }
    }

    function st_import_pms_payments()
    {
        global $wpdb;

        $pms_payments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pms_payments");
        if (empty($pms_payments)) {
            echo esc_html__("No Payments Data To Import.", STA_TEXT_DOMAIN);
            return;
        }

        $user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_membership_orders");
        $sql_values = '';
        $order = new MemberOrder();

        foreach ($pms_payments as $pms_payment) {

            if (in_array($pms_payment->user_id, $user_ids)) continue;

            $time = time();
            $timestamp = date("Y-m-d H:i:s", $time);

            $code = $order->getRandomCode();
            $user_id = $pms_payment->user_id;
            $membership_id = $pms_payment->subscription_plan_id;
            $status = $pms_payment->status;
            $subtotal = $total = $pms_payment->amount;
            $timestamp = $timestamp;

            $sql_values .= '(' .
                "'" . $code . "'," .          //random payment code
                "'" . $user_id . "'," .       //user id
                "'" . $membership_id . "'," . //subscription_plan_id
                "'" . $subtotal . "'," .      //purchased plan amount
                "'" . $total . "'," .         //purchased plan amount
                "'" . $status . "'," .        //payment status
                "'" . $timestamp . "'" .     //TIMESTAMP
                '),';
        }

        if (isset($sql_values) && !empty($sql_values)) {
            $sql_values = rtrim($sql_values, ','); // remove last comma

            $sql = "INSERT INTO $wpdb->pmpro_membership_orders
            (`code`, `user_id`, `membership_id`, `subtotal`, `total`, `status`, `timestamp`)
            VALUES $sql_values";

            $result = $wpdb->query($sql);

            if (!$result) {
                // echo esc_html__("Payments Data Already Imported.", STA_TEXT_DOMAIN);
                return;
            }

            // echo esc_html__('Sucessfully Imported Payments Data.', STA_TEXT_DOMAIN);
            return;
        } else {
            // echo esc_html__("Payments Data Already Imported.", STA_TEXT_DOMAIN);
            return;
        }
    }
}
