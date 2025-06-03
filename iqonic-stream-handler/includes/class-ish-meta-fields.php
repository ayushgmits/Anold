<?php
if (!defined('ABSPATH')) {
    exit;
}

class ISH_Meta_Fields {

    /**
     * Initialize Hooks
     * 
     * These hooks set up various parts of the plugin:
     * - Admin hooks manage backend settings and configurations.
     * - Membership hooks control membership levels and user subscriptions.
     * - Frontend hooks manage user-facing features and displays.
     * - Cron schedules automate recurring tasks and background processes.
     */
    public function __construct() {

        // Initialize Hooks
        $this->admin_hooks();
        $this->membership_hooks();
        $this->frontend_hooks();
        $this->cron_schedules();

    }

    /**
     * Admin Hooks
     * 
     * Sets up hooks related to admin functionality, like adding settings pages,
     * handling shortcodes, registering settings, and managing meta boxes.
     */
    public function admin_hooks(){

        add_action('admin_menu', [$this, 'add_settings_page']);
        add_shortcode('displayep', [$this, 'display_all_episodes_with_status']);
        add_action('admin_init', [$this, 'register_settings']); // Ensure settings are registered
        add_action('admin_init', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_fields']);
        
    }

    /**
     * Membership Hooks
     * 
     * Sets up hooks related to membership levels and user subscriptions,
     * including custom fields, saving user data, and handling order updates.
     */
    public function membership_hooks(){
        add_action( 'pmpro_membership_level_after_other_settings', [$this,'add_custom_checkbox_fields_to_pmpro_level_edit'] );
        add_action( 'pmpro_save_membership_level', [$this, 'save_custom_pmpro_checkbox_meta'] );
        add_action('pmpro_save_membership_level', [$this,'save_custom_pmpro_user_coin_meta']);
        add_action('pmpro_updated_order', [$this, 'wppoi_add_total_coins_to_user_after_order'], 10, 3);
        add_filter('pmpro_change_level', [$this, 'preventLevelChangeIfCoinsEnabled'], 10, 4);
        add_action('admin_menu', [$this,'register_pmpro_custom_submenu']);
        add_action('add_meta_boxes', [$this,'add_membership_meta_box']);
        add_action('save_post_product', [$this,'save_membership_meta_box_data']);
        add_action('woocommerce_order_status_completed', [$this,'wppoi_handle_pmpro_coins_from_woo_order']);
        add_filter('woocommerce_add_to_cart_redirect', [$this,'redirect_membership_products_to_checkout']);
        add_action('woocommerce_payment_complete', [$this,'wppoi_complete_membership_order_after_payment']);
        add_action('init', [$this,'add_customer_and_subscriber_permissions']);
    }

    /**
     * Frontend Hooks
     * 
     * Sets up hooks related to user-facing features and interactions,
     * like scheduling unlock reductions and handling new user registrations.
     */
    public function frontend_hooks(){
        add_action('init', [$this, 'wppoi_schedule_unlock_reduction']);
        add_action('user_register', [$this, 'handle_new_user_registration']);        
    }

    /**
     * Cron Schedules
     * 
     * Sets up hooks related to scheduled tasks and recurring jobs,
     * like custom cron schedules and unlock time reduction processes.
     */
    public function cron_schedules(){
        // Hook the cron job action to reduce unlock time
        add_filter('cron_schedules', [$this, 'wppoi_custom_cron_schedules']);
        add_action('wppoi_reduce_unlock_time', [$this, 'wppoi_reduce_unlock_time']);
    }
    
    /**
     * Add Membership Meta Box
     * 
     * Registers a meta box to add membership options to the product page,
     * allowing the admin to specify whether the product grants a membership level.
     */
    public function add_membership_meta_box() {
        add_meta_box(
            'membership_meta_box_id',                // Unique ID for the meta box
            __('Membership Options', 'textdomain'),  // Title of the meta box
            [$this, 'membership_meta_box_callback'], // Callback function to display the meta box content
            'product',                              // Post type: 'product'
            'side',                                 // Context: Display in the 'side' section of the post editor
            'default'                               // Priority: Default
        );
    }

    /**
     * Membership Meta Box Callback
     * 
     * Displays the content inside the membership options meta box,
     * including a checkbox to specify if the product grants a membership level.
     *
     * @param WP_Post $post The current post object.
     */
    public function membership_meta_box_callback($post) {
        // Retrieve the current value of the membership level option for this product
        $value = get_post_meta($post->ID, '_is_membership_level', true);

        // Add nonce field for security
        wp_nonce_field('membership_meta_box_nonce_action', 'membership_meta_box_nonce');
        
        // Output HTML for the meta box content
        echo '<p>';
        echo '<label for="is_membership_level">';
        echo '<input type="checkbox" name="is_membership_level" id="is_membership_level" value="yes" ' . checked($value, 'yes', false) . ' />';
        echo ' ' . __('This product grants a membership level', 'textdomain');
        echo '</label>';
        echo '</p>';
    }

    /**
     * Save Membership Meta Box Data
     * 
     * Saves the data from the membership options meta box when the product is saved.
     * It checks the nonce for security, and updates or deletes the membership level meta field.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_membership_meta_box_data($post_id) {
        // Verify the nonce for security
        if (!isset($_POST['membership_meta_box_nonce']) || !wp_verify_nonce($_POST['membership_meta_box_nonce'], 'membership_meta_box_nonce_action')) {
            return;
        }

        // Prevent auto-saving from affecting the meta data
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check if the 'is_membership_level' checkbox was checked and update the post meta accordingly
        if (isset($_POST['is_membership_level'])) {
            update_post_meta($post_id, '_is_membership_level', 'yes');
        } else {
            delete_post_meta($post_id, '_is_membership_level');
        }
    }
    
    public function wppoi_handle_pmpro_coins_from_woo_order($order_id) {
        
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) return;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // PMPro WooCommerce bridge adds this meta key to map product → membership level
            $level_id = get_post_meta($product_id, '_membership_product_level', true);

            if (!empty($level_id)) {
                // Create a PMPro-style object
                $fake_pmpro_order = (object) [
                    'user_id'       => $user_id,
                    'membership_id' => $level_id,
                    'status'        => 'success',
                ];

                // Call your existing logic
                $this->wppoi_add_total_coins_to_user_after_order($fake_pmpro_order);
            }
        }
    }
    
     /**
     * Add Coins to User After Order is Added (pmpro_added_order)
     */
    public function wppoi_add_total_coins_to_user_after_order($order) {
        // Only proceed if the order status is 'success'
        // if ($order->status === 'success') {
            // Get the user ID from the order
            $user_id = $order->user_id;
            
            if (!$user_id) {
                return;  // If no user ID, exit
            }

            // Get the membership level from the order
            $level_id = $order->membership_id;

            if (!$level_id) {
                return;  // If no level ID, exit
            }
            // Check the user's subscription level (assuming _custom_subscription_level is saved as user meta)
            $subscription_level = get_post_meta($level_id, '_custom_subscription_level', true);

            // If the subscription level is 1, update user meta to mark as 'pro'
            if (($subscription_level) == 1) {

                // Update the user's 'is_pro' meta field to 1 (true)
                update_user_meta($user_id, 'is_pro', 1);    
            }

            // Check if coins are enabled for this membership level
            $enable_coins = get_post_meta($level_id, '_custom_coins_level', true);
            if (empty($enable_coins)) {
                return; // If no coin data, exit
            }

            // Get the total coins for this membership level
            $total_coins = get_post_meta($level_id, '_total_coins', true);
            $total_coins = !empty($total_coins) ? intval($total_coins) : 0;

            // If there are coins to be added, update user's coins balance
            if ($enable_coins === '1' && $total_coins > 0) {
                // Get current coin balance
                $current_coins = get_user_meta($user_id, 'streamit_get_user_coins', true);
                $current_coins = !empty($current_coins) ? intval($current_coins) : 0;

                // Update the user's coin balance
                update_user_meta($user_id, 'streamit_get_user_coins', $current_coins + $total_coins);
            }

           
            // Get existing coin transaction history
            $existing_transactions = get_user_meta($user_id, 'stream_coin_transaction', true);
        
            // Ensure it's an array
            if (!is_array($existing_transactions)) {
                $existing_transactions = [];
            }
        
            // Store the credit transaction with additional details
            $new_transaction_entry = [
                'total_coins' => $total_coins,
                'status'      => "credit",
                'date'        => current_time('mysql'),
                'transaction' => [
                    [
                        'coins'       => $total_coins,
                        'stream_name' => 'coins added',
                        'post_image'  => null
                    ]
                ]
            ];
        
            // Insert new transaction at the beginning
            array_unshift($existing_transactions, $new_transaction_entry);
        
            // Update the 'stream_coin_transaction' user meta
            update_user_meta($user_id, 'stream_coin_transaction', $existing_transactions);
        // }
    }
    
    /**
     * Redirect membership products directly to the checkout page.
     *
     * This function intercepts the add-to-cart URL for WooCommerce products. If the product
     * being added to the cart is marked as a membership product (via the `_is_membership_level` meta key),
     * the user is redirected directly to the checkout page instead of the cart.
     *
     * @param string $url The original redirect URL after a product is added to the cart.
     * @return string The modified URL — either the default or the checkout URL if the product is a membership product.
     */
    public function redirect_membership_products_to_checkout($url) {
        // Get the product ID from the request
        if (!isset($_REQUEST['add-to-cart'])) {
            return $url;
        }

        $product_id = (int) $_REQUEST['add-to-cart'];

        // Check if it's a membership product
        $is_membership = get_post_meta($product_id, '_is_membership_level', true);

        // If it's a membership product, redirect to the checkout
        if ($is_membership === 'yes') {
            return wc_get_checkout_url();
        }

        // Otherwise, return the default redirect URL
        return $url;
    }
    
    /**
     * Automatically complete orders that contain membership products after successful payment.
     *
     * This function hooks into WooCommerce's `woocommerce_payment_complete` action and checks if the order
     * contains any product marked as a membership product (via `_is_membership_level` meta key).
     * If found, it updates the order status to 'completed' (if it isn't already).
     *
     * @param int $order_id The ID of the WooCommerce order that has just been paid for.
     * @return void
     */
    public function wppoi_complete_membership_order_after_payment($order_id) {
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            // Check if it's a membership product
            $is_membership = get_post_meta($product->get_id(), '_is_membership_level', true);
            if ($is_membership === 'yes') {
                // Mark the order as completed if it's not already
                if ($order->get_status() !== 'completed') {
                    $order->update_status('completed');
                }
                break; // Only one membership product needed to complete the order
            }
        }
    }
    
    
    public function add_customer_and_subscriber_permissions() {
        foreach (['customer', 'subscriber'] as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('read_private_shop_orders');
            }
        }
    }

    /**
     * Registers a custom submenu under the Paid Memberships Pro (PMPro) dashboard.
     * This submenu allows administrators to configure custom settings related to membership levels.
     *
     * @return void
     */
    public function register_pmpro_custom_submenu() {
        // Add a submenu under the PMPro main menu
        add_submenu_page(
            'pmpro-dashboard',           // Parent slug: PMPro's main dashboard
            'Coins List',           // Page title
            'Coins List',           // Menu title
            'manage_options',            // Capability required to access this submenu
            'coins-list',     // Menu slug
            array($this, 'display_pmpro_custom_settings') // Callback function to render the page
        );
    }

    /**
     * Adds a settings page under the "Settings" menu in the WordPress admin.
     * This page allows the user to configure settings for the Episode Meta Box.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function add_settings_page() {
        // Add a settings page under "Settings"
        add_options_page(
            __('Episode Meta Box Settings', STREAM_HANDLER),
            __('Episode Meta Box', STREAM_HANDLER),
            'manage_options',
            'stream_meta_box_settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Adds custom meta boxes for the specified post types.
     * This meta box allows users to configure additional settings for episodes, videos, TV shows, and movies.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function add_meta_boxes() {
        
        $post_types = get_option('stream_meta_box_post_types', ['episode', 'video', 'tv_show', 'movie']);

        add_meta_box(
            'ish_episode_meta',
            __('Additional Settings', STREAM_HANDLER),
            [$this, 'render_meta_box'],
            $post_types,
            'side',
            'default'
        );
    }

    /**
     * Renders the settings page for the Episode Meta Box settings.
     * This page allows administrators to configure the post types for which the meta box will appear.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('Episode Meta Box Settings', STREAM_HANDLER); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('stream_meta_box_settings_group');
                do_settings_sections('stream_meta_box_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registers settings for the Episode Meta Box settings page.
     * This function handles the registration of the setting group, sections, and fields
     * so that they can be displayed and saved via the WordPress settings API.
     */
    public function register_settings() {
        
        register_setting('stream_meta_box_settings_group', 'stream_meta_box_post_types');
        
        add_settings_section('stream_meta_box_section', '', null, 'stream_meta_box_settings');
        
        register_setting('stream_meta_box_settings_group', 'stream_meta_box_options', [$this, 'update_all_posts_on_settings_save']);

        add_settings_field(
            'stream_meta_box_post_types', 
            __('Select Post Types', STREAM_HANDLER),
            [$this, 'render_post_types_field'],
            'stream_meta_box_settings',
            'stream_meta_box_section'
        );

        // Add a field for unlock time
        add_settings_field(
            'unlock_time',
            __('Default Unlock Time (minutes)', STREAM_HANDLER),
            [$this, 'render_unlock_time_field'],
            'stream_meta_box_settings',
            'stream_meta_box_section'
        );
    }

    /**
     * Retrieves membership levels that have coins enabled and fetches their related meta values.
     *
     * This function queries the database for membership levels with the '_custom_coins_level' meta key
     * and then retrieves additional metadata values such as coins, bonus coins, free coins, and total coins.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return array List of membership levels with associated coin meta values.
     */
    public function get_membership_levels_with_coins() {
        global $wpdb;
    
        // Fetch membership levels that have '_custom_coins_level' set
        $levels = $wpdb->get_results("
            SELECT l.id, l.name 
            FROM {$wpdb->pmpro_membership_levels} l
            INNER JOIN {$wpdb->postmeta} pm
            ON l.id = pm.post_id
            WHERE pm.meta_key = '_custom_coins_level'
        ");
    
        // Fetch all meta values using get_post_meta()
        foreach ($levels as &$level) {
            $meta_values = get_post_meta($level->id);
            
            $level->coins       = $meta_values['_coins'][0] ?? 0;
            $level->bonus_coins = $meta_values['_bonus_coins'][0] ?? 0;
            $level->free_coins  = $meta_values['_free_coins'][0] ?? 0;
            $level->total_coins = $meta_values['_total_coins'][0] ?? 0;
        }
    
        return $levels;
    }
    
    /**
     * Displays the custom settings page for Paid Memberships Pro (PMPro).
     *
     * This function retrieves membership levels with coins enabled and includes a template 
     * to display the information. The template is responsible for rendering the data.
     */
    public function display_pmpro_custom_settings() {
        $membership_levels = $this->get_membership_levels_with_coins();
    
        // Include the template and pass data
        $template_path = plugin_dir_path(__FILE__) . '../templates/membership-coins.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
    

    /**
     * Renders the post types selection field in the settings page.
     *
     * This function retrieves all custom post types (excluding built-in ones),
     * fetches the saved selected post types from the options, and includes
     * the template file to display the selection UI.
     */
    public function render_post_types_field() {
        $post_types = get_post_types(['_builtin' => false], 'objects');

        $selected_post_types = get_option('stream_meta_box_post_types', []);
    
        if (!is_array($selected_post_types)) {
            $selected_post_types = [];
        }
            $template_path = plugin_dir_path(__FILE__) . '../templates/setting-fields.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo 'Template file not found: ' . $template_path;
        }
    }

    /**
     * Render the Unlock Time Field.
     *
     * This method retrieves the 'unlock_time' option from the plugin's options,
     * sanitizes its value, and then includes a template file (streamit-unlock.php)
     * that displays the unlock time field. If the template file is not found,
     * an error message is echoed.
     */
    public function render_unlock_time_field() {
        $options = get_option('stream_meta_box_options');
        $unlock_time = isset($options['unlock_time']) ? esc_attr($options['unlock_time']) : '';
        $template_path = plugin_dir_path(__FILE__) . '../templates/streamit-unlock.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo 'Template file not found: ' . $template_path;
        }
    }

    /**
     * Update all episodes' unlock times when settings are saved.
     *
     * This function is called during settings save and performs the following:
     * - Checks if an 'unlock_time' option is provided.
     * - Converts the provided unlock time (in hours) into minutes.
     * - Calculates a new unlock timestamp based on the current time (using the Asia/Kolkata timezone)
     *   and the total unlock minutes.
     * - Fetches all published episodes (of post type 'test').
     * - Updates each episode's meta fields for unlock time and total unlock minutes.
     * - Calls a helper method to update user meta for each user who has interacted with the episode.
     *
     * @param array $options The options array being saved.
     * @return array The (possibly modified) options array.
     */
    public function update_all_posts_on_settings_save($options) {
        if (!isset($options['unlock_time'])) {
            return $options;
        }
    
        $unlock_hours = intval($options['unlock_time']); // Get unlock time in hours
        $total_unlock_minutes = $unlock_hours * 60; // Convert hours to minutes
    
        // Get the current timestamp
        $current_time = new DateTime("now", new DateTimeZone('Asia/Kolkata'));
        $current_timestamp = $current_time->getTimestamp();
    
        // Calculate unlock timestamp based on the set hours
        $unlock_timestamp = $current_timestamp + ($total_unlock_minutes * 60);
    
        // Fetch all episodes
        $args = array(
            'post_type'      => 'test',
            'posts_per_page' => -1, // Get all episodes
            'post_status'    => 'publish'
        );
    
        $episodes = get_posts($args);
    
        foreach ($episodes as $episode) {
            update_post_meta($episode->ID, 'unlock_time', $unlock_timestamp);
            update_post_meta($episode->ID, 'total_unlock_minutes', $total_unlock_minutes);
    
            // Update user meta for each user who has interacted with this episode
            $this->update_user_unlock_times_for_tv_shows($episode->ID, $unlock_timestamp);
        }
    
        return $options;
    }

    /**
     * Update user unlock times for a specific TV show episode.
     *
     * This function retrieves all user IDs and then updates a custom user meta value for each user,
     * using a meta key that is unique for the given episode (constructed as 'unlock_time_{post_id}').
     * This allows you to store an unlock timestamp for that episode for every user.
     *
     * @param int $post_id         The ID of the episode for which the unlock time should be updated.
     * @param int $unlock_timestamp The unlock timestamp to be saved for each user.
     */
    public function update_user_unlock_times_for_tv_shows($post_id, $unlock_timestamp) {
        $users = get_users(array('fields' => 'ID')); // Get all user IDs
    
        foreach ($users as $user_id) {
            update_user_meta($user_id, 'unlock_time_' . $post_id, $unlock_timestamp);
        }
    }
    
    /**
     * Renders the field for selecting post types where the meta box should appear.
     * This function displays the available post types and allows the user to select
     * which post types should have the "Episode Meta Box" displayed.
     */
    public function render_meta_box($post) {
        // Fetch meta values
        $is_free      = get_post_meta($post->ID, 'is_free', true);
        $ads_count    = get_post_meta($post->ID, 'ads_count', true);
        $coins        = get_post_meta($post->ID, 'coins', true);
        $unlock_time  = get_post_meta($post->ID, 'unlock_time', true);
        $is_unlocked  = get_post_meta($post->ID, 'is_unlocked', true);
    
        date_default_timezone_set('Asia/Kolkata');
    
        $unlock_time = intval($unlock_time);
        $current_time = time();
    
        $remaining_minutes = ($unlock_time > $current_time) ? ceil(($unlock_time - $current_time) / 60) : 0;
    
        $formatted_unlock_time = ($unlock_time > 0) ? date('Y-m-d\TH:i', $unlock_time) : '';
    
        $template_path = plugin_dir_path(__FILE__) . '../templates/stream-meta-box.php';
    
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo 'Template not found!';
        }
    }
    
    /**
     * Update user unlock times for a specific episode.
     *
     * This function retrieves the unlock timestamp from the episode's post meta and calculates
     * the remaining unlock minutes based on the current time. It then updates each user's meta
     * with the unlock timestamp and the calculated total unlock minutes using unique meta keys.
     *
     * @param int $post_id             The ID of the episode.
     * @param int $total_unlock_minutes (Unused) The total unlock minutes, if needed for other logic.
     */
    public function update_user_unlock_times_for_episode($post_id, $total_unlock_minutes) {
        $users = get_users(array('fields' => 'ID')); // Get all user IDs
    
        $unlock_timestamp = get_post_meta($post_id, 'unlock_time', true);
        
        $current_time = time(); 
    
        foreach ($users as $user_id) {
            $calculated_total_unlock_minutes = max(1, ceil(($unlock_timestamp - $current_time) / 60));
    
            update_user_meta($user_id, 'unlock_time_' . $post_id, $unlock_timestamp);
            update_user_meta($user_id, 'total_unlock_minutes_' . $post_id, $calculated_total_unlock_minutes);
        }
    }
    
    /**
     * Saves the meta fields for the post when the post is saved.
     * This function processes the custom fields in the meta box and saves them to the database.
     *
     * @param int $post_id The ID of the post being saved.
     */
    
     public function save_meta_fields($post_id) {
        // Ensure nonce is valid
        if (!isset($_POST['ish_nonce']) || !wp_verify_nonce($_POST['ish_nonce'], 'ish_save_meta')) {
            return;
        }
    
        // Check if it's an autosave or not a proper post type
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    
        // Ensure user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    
        // Save basic metadata
        update_post_meta($post_id, 'is_free', isset($_POST['ish_is_free']) ? 1 : 0);
        update_post_meta($post_id, 'ads_count', sanitize_text_field($_POST['ish_ads_count']));
        update_post_meta($post_id, 'coins', sanitize_text_field($_POST['ish_coins']));
    
        // Get and sanitize unlock_time
        $selected_time = isset($_POST['unlock_time']) ? trim(sanitize_text_field($_POST['unlock_time'])) : '';
    
        if (!empty($selected_time)) {
            // ✅ FIX: Convert timestamp correctly
            try {
                if (is_numeric($selected_time)) {
                    $date_time = new DateTime("@$selected_time", new DateTimeZone('Asia/Kolkata'));
                } else {
                    $date_time = new DateTime($selected_time, new DateTimeZone('Asia/Kolkata'));
                }
    
                $unlock_timestamp = $date_time->getTimestamp();
                $current_timestamp = (new DateTime("now", new DateTimeZone('Asia/Kolkata')))->getTimestamp();
    
                if ($current_timestamp > $unlock_timestamp) {
                    $unlock_timestamp = strtotime('tomorrow ' . date('H:i', $unlock_timestamp));
                }
    
                $total_unlock_minutes = max(0, floor(($unlock_timestamp - $current_timestamp) / 60));
    
                // Save unlock time only if provided
                update_post_meta($post_id, 'unlock_time', $unlock_timestamp);
                update_post_meta($post_id, 'total_unlock_minutes', $total_unlock_minutes);
                $this->update_user_unlock_times_for_episode($post_id, $total_unlock_minutes);
            } catch (Exception $e) {
                // Handle invalid time format (optional)
            }
        } else {
            // ❌ If no unlock time is provided, remove existing meta values
            delete_post_meta($post_id, 'unlock_time');
            delete_post_meta($post_id, 'total_unlock_minutes');
        }
    }    
    
    /**
     * Update unlock times for a new user registration.
     *
     * This method is triggered when a new user registers. It loops through all posts (episodes) 
     * of post type 'test' (which you may adjust as needed), calculates the next unlock time and the 
     * total unlock minutes for each episode, and updates the new user's meta data accordingly.
     *
     * @param int $user_id The ID of the newly registered user.
     */
   public function handle_new_user_registration($user_id) {
    $args = [
        'post_type'      => 'any',        // All post types
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC',
        'posts_per_page' => -1
    ];
    
    $query = new WP_Query($args);
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
    
            // Retrieve the unlock_time from post meta.
            // This meta should have been saved in save_meta_fields() as a UNIX timestamp.
            $unlock_timestamp = get_post_meta($post_id, 'unlock_time', true);
            if ( empty($unlock_timestamp) ) {
                // If unlock_time is not set, you can choose to skip this post.
                continue;
            }
    
            // Get current time using the same timezone as used in save_meta_fields (e.g., Asia/Kolkata).
            $current_date = new DateTime("now", new DateTimeZone("Asia/Kolkata"));
            $current_timestamp = $current_date->getTimestamp();
    
            // If the current time is greater than the stored unlock time,
            // then shift the unlock time to tomorrow at the same hour:minute.
            if ( $current_timestamp > $unlock_timestamp ) {
                // Use the original unlock_time’s hour and minute.
                $unlock_hour   = date('H', $unlock_timestamp);
                $unlock_minute = date('i', $unlock_timestamp);
    
                // Build a timestamp for tomorrow at the same hour:minute.
                $tomorrow_date = date('Y-m-d', strtotime('+1 day', $current_timestamp));
                $unlock_timestamp = strtotime("$tomorrow_date $unlock_hour:$unlock_minute:00");
            }
    
            // Calculate total unlock minutes as the difference from now.
            $total_unlock_minutes = max(0, floor(($unlock_timestamp - $current_timestamp) / 60));
    
            // Update user meta for the new user.
            update_user_meta($user_id, 'unlock_time_' . $post_id, $unlock_timestamp);
            update_user_meta($user_id, 'total_unlock_minutes_' . $post_id, $total_unlock_minutes);
        }
        wp_reset_postdata();
    }
}



    /**
     * Add a custom cron schedule interval.
     *
     * This function adds a new cron schedule called 'one_minute' to the array of available schedules.
     * The new schedule runs every 60 seconds (1 minute) and is labeled "Every Minute" for display purposes.
     *
     * @param array $schedules The array of existing cron schedules.
     * @return array The modified array of cron schedules including the new 'one_minute' schedule.
     */
    public function wppoi_custom_cron_schedules($schedules) {
        $schedules['one_minute'] = [
            'interval' => 60, // Runs every 1 minute
            'display'  => __('Every Minute')
        ];
        return $schedules;
    }

    /**
     * Schedule the unlock reduction cron event.
     *
     * This function checks whether a cron event with the hook 'wppoi_reduce_unlock_time'
     * is already scheduled. If it is not, the function schedules the event to run every
     * minute using the custom 'one_minute' schedule. It logs a message indicating whether
     * the event was newly scheduled or if it was already in place.
     */
    public function wppoi_schedule_unlock_reduction() {
        if (!wp_next_scheduled('wppoi_reduce_unlock_time')) {
            error_log('⏳ Scheduling Cron Event: wppoi_reduce_unlock_time');
            wp_schedule_event(time(), 'one_minute', 'wppoi_reduce_unlock_time');
        } else {
            error_log('✅ Cron Event Already Scheduled');
        }
    }

    /**
     * Schedule the unlock reduction cron event.
     *
     * This function checks whether a cron event with the hook 'wppoi_reduce_unlock_time'
     * is already scheduled. If it is not, the function schedules the event to run every
     * minute using the custom 'one_minute' schedule. It logs a message indicating whether
     * the event was newly scheduled or if it was already in place.
     */
    public function wppoi_reduce_unlock_time() {
        $args = [
            'post_type'   => ['test', 'video', 'tv_show', 'movie'],
            'post_status' => 'publish',
            'meta_query'  => [
                [
                    'key'     => 'total_unlock_minutes',
                    'value'   => 0,
                    'compare' => '>'
                ]
            ]
        ];
    
        $query = new WP_Query($args);
    
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $remaining_minutes = intval(get_post_meta($post_id, 'total_unlock_minutes', true)); // Ensure integer
    
                // 🔹 Reduce post-level unlock time
                if ($remaining_minutes > 0) {
                    update_post_meta($post_id, 'total_unlock_minutes', max(0, $remaining_minutes - 1)); // Prevent negative values
                }
    
                // 🔹 Reduce user-level unlock time
                $users = get_users([
                    'meta_key'     => 'total_unlock_minutes_' . $post_id,
                    'meta_value'   => 0,
                    'meta_compare' => '>'
                ]);
    
                foreach ($users as $user) {
                    $user_id = $user->ID;
                    $user_remaining_minutes = intval(get_user_meta($user_id, 'total_unlock_minutes_' . $post_id, true));
    
                    if ($user_remaining_minutes > 0) {
                        update_user_meta($user_id, 'total_unlock_minutes_' . $post_id, max(0, $user_remaining_minutes - 1)); // Prevent negative values
                    }
                }
            }
        }
    
        wp_reset_postdata();
    }
    
    /**
     * Adds custom checkbox fields for Subscription and Coins 
     * to the Membership Level Edit page in PMPro.
     *
     * This function retrieves existing meta values for the selected membership level 
     * and includes a template to display the fields in the admin UI.
     *
     * @param object $level The membership level object.
     */
    public function add_custom_checkbox_fields_to_pmpro_level_edit( $level ) {

        $enable_coins  = get_post_meta($level->id, '_custom_coins_level', true);
        $coins         = get_post_meta($level->id, '_coins', true);
        $bonus_coins   = get_post_meta($level->id, '_bonus_coins', true);
        $free_coins    = get_post_meta($level->id, '_free_coins', true);
        $total_coins   = get_post_meta($level->id, '_total_coins', true);
        $template_path = plugin_dir_path(__FILE__) . '../templates/membership-level.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo 'Template not found!';
        }
    }

    /**
     * Saves custom checkbox meta for PMPro membership levels.
     *
     * This function handles the logic for enabling either "Coins Level" or 
     * "Subscription Level," ensuring that only one option can be active at a time.
     *
     * @param int $level_id The ID of the membership level being saved.
     */
    public function save_custom_pmpro_checkbox_meta( $level_id ) {
        // If Coins Level checkbox is selected, save it and disable the Subscription checkbox
        if ( isset( $_POST['custom_coins_level'] ) && $_POST['custom_coins_level'] == 1 ) {
            update_post_meta( $level_id, '_custom_coins_level', 1 );
            delete_post_meta( $level_id, '_custom_subscription_level' );
        } 
        // If Subscription Level checkbox is selected, save it and disable the Coins checkbox
        elseif ( isset( $_POST['custom_subscription_level'] ) && $_POST['custom_subscription_level'] == 1 ) {
            update_post_meta( $level_id, '_custom_subscription_level', 1 );
            delete_post_meta( $level_id, '_custom_coins_level' );
        } 
        // If neither is selected, delete both meta values
        else {
            delete_post_meta( $level_id, '_custom_subscription_level' );
            delete_post_meta( $level_id, '_custom_coins_level' );
        }
    }    
    
    /**
     * Saves custom coin-related meta data for a PMPro membership level.
     *
     * This function handles saving coin-related values when the "Coins Level" option is enabled.
     * It calculates bonus coins and total coins automatically.
     *
     * @param int $level_id The ID of the membership level being saved.
     */
    public function save_custom_pmpro_user_coin_meta( $level_id ) {
        // Check if Coins Level checkbox is selected
        if ( isset( $_POST['custom_coins_level'] ) && $_POST['custom_coins_level'] == 1 ) {
            update_post_meta( $level_id, '_custom_coins_level', 1 );
            delete_post_meta( $level_id, '_custom_subscription_level' );

            // Retrieve values from form submission
            $coins       = isset($_POST['coins']) ? intval($_POST['coins']) : 0;
            $bonus_rate  = isset($_POST['bonus_coins']) ? intval($_POST['bonus_coins']) : 0; // Percentage (e.g., 150)

            // Calculate Free Coins
            $free_coins = ($coins * ($bonus_rate / 100));

            // Total Coins = Coins + Bonus Coins + Free Coins
            $total_coins = ($coins + $free_coins);

            update_post_meta( $level_id, '_total_coins', $total_coins );
    
            // Save coins data only when coins level is enabled
            if ( isset( $_POST['coins'] ) ) {
                update_post_meta( $level_id, '_coins', sanitize_text_field( $_POST['coins'] ) );
            }
            if ( isset( $_POST['bonus_coins'] ) ) {
                update_post_meta( $level_id, '_bonus_coins', sanitize_text_field( $_POST['bonus_coins'] ) );
            }
                update_post_meta( $level_id, '_free_coins', $free_coins );
        } 
    }

    /**
     * Check if a post is unlocked for the current user.
     *
     * This function determines whether a given post (e.g., an episode) is unlocked for a user.
     * It retrieves the user's unlock time for the post from the user meta data and compares it to the current time.
     * Note: The function ignores the passed $user_id parameter and always uses the currently logged-in user.
     *
     * @param int $post_id The ID of the post/episode to check.
     * @param int $user_id (Ignored) The user ID parameter is not used; the current user ID is retrieved instead.
     * @return bool Returns true if the post is unlocked (i.e., current time is equal to or past the unlock time), false otherwise.
     */
    public function is_post_unlocked_for_user($post_id, $user_id) {
        
        $current_time = time();
        $user_id = get_current_user_id();
        $user_unlock_time = get_user_meta($user_id, 'unlock_time_' . $post_id, true);

        if ($user_unlock_time && $current_time >= $user_unlock_time) {
            return true; // Post is unlocked
        }
        return false; // Post is still locked
    }

    /**
     * Calculate the remaining time until an episode is unlocked for a given user.
     *
     * This function retrieves the unlock timestamp for a specified episode from the user's meta data
     * and calculates how many minutes remain until the episode is unlocked. If the remaining time is
     * less than or equal to zero, it indicates that the episode is already unlocked.
     *
     * */
    public function get_time_until_unlock($episode_id, $user_id) {
        $user_unlock_time = get_user_meta($user_id, 'unlock_time_' . $episode_id, true);
    
        if (!$user_unlock_time) {
            return ['status' => 'error', 'message' => 'No unlock time set for this post.'];
        }
    
        $current_time = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $current_timestamp = $current_time->getTimestamp();
        
        $remaining_seconds = $user_unlock_time - $current_timestamp;
    
        if ($remaining_seconds <= 0) {
            return ['status' => 'unlocked', 'message' => 'This episode is already unlocked for you.'];
        }
    
        $remaining_minutes = ceil($remaining_seconds / 60);
        
        return ['status' => 'locked', 'minutes' => $remaining_minutes];
    }
    
    /**
     * Display All Episodes with Their Unlock Status.
     *
     * This function retrieves all published episodes (of post type 'test') and checks 
     * whether each episode is unlocked for the current user by calling the 
     * is_post_unlocked_for_user() method. It then builds an HTML unordered list with each 
     * episode's title, ID, and a message indicating if the episode is locked or unlocked.
     *
     * @return string HTML output of the episodes list with their unlock status.
     */
    public function display_all_episodes_with_status() {
    // Start output buffering
    ob_start();

    // Get the current user ID
    $user_id = get_current_user_id();

    // Define the custom post type and other query parameters as needed
    $args = array(
        'post_type'      => 'test', // Replace 'test' with your actual post type
        'posts_per_page' => -1,     // Retrieve all episodes
        'post_status'    => 'publish', // Only show published episodes
    );

    // Execute the query
    $query = new WP_Query($args);

    // Check if any episodes were found
    if ($query->have_posts()) {
        echo '<ul>';
        // Loop through each episode
        while ($query->have_posts()) {
            $query->the_post();
            $episode_id = get_the_ID();
            $title = get_the_title();
            
            // Check if the post (episode) is unlocked
            $can_access = $this->is_post_unlocked_for_user($episode_id, $user_id);
            
            // Display message based on whether the episode is unlocked
            if ($can_access) {
                echo "<li>Episode ID: $episode_id - $title: This episode is unlocked.</li>";
            } else {
                echo "<li>Episode ID: $episode_id - $title: This episode is locked.</li>";
            }
        }
        echo '</ul>';
    } else {
        echo 'No episodes found.';
    }

    // Reset post data
    wp_reset_postdata();

    // Get the contents of the output buffer
    $output = ob_get_clean();

    // Return the buffered content
    return $output;
}

    /**
     * Prevent membership level change if coins are enabled for the level
     *
     * @param mixed $level The new membership level
     * @param int $user_id The user ID
     * @param string $old_level_status The status of the old membership level
     * @param int $cancel_level The level being cancelled
     * @return mixed The adjusted membership level
     */
    public function preventLevelChangeIfCoinsEnabled($level, $user_id, $old_level_status, $cancel_level) {
        $level_id = $this->getLevelId($level);

        if (empty($level_id)) {
            return $level;
        }

        $enable_coins = get_post_meta($level_id, '_custom_coins_level', true);

        if (!empty($enable_coins)) {
            error_log("Membership level change prevented for user {$user_id} because coins are enabled for level {$level_id}");
            $current_levels = pmpro_getMembershipLevelsForUser($user_id);
            if (!empty($current_levels)) {
                return $current_levels[0]->id;
            }
            return null;
        }

        return $level;
    }

    /**
     * Get the membership level ID from the $level parameter
     *
     * @param mixed $level The membership level data
     * @return int The membership level ID
     */
    private function getLevelId($level) {
        if (is_array($level) && !empty($level['membership_id'])) {
            return (int) $level['membership_id'];
        } elseif (is_numeric($level)) {
            return (int) $level;
        }
        return 0;
    }

}

add_action('woocommerce_add_to_cart', 'remove_previous_membership_product', 10, 6);

function remove_previous_membership_product($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Check if the product being added is a membership product
    $is_membership = get_post_meta($product_id, '_is_membership_level', true);

    if ($is_membership === 'yes') {
        // Loop through the cart and find the first membership product
        $cart = WC()->cart->get_cart();
        
        foreach ($cart as $key => $item) {
            // Check if it's a membership product
            if (get_post_meta($item['product_id'], '_is_membership_level', true) === 'yes' && $item['product_id'] !== $product_id) {
                // Remove the old membership product from the cart
                WC()->cart->remove_cart_item($key);
            }
        }
    }
}
    remove_action('woocommerce_single_product_summary', 'pmprowoo_purchase_disabled', 31);
// Allow both membership and other products in the cart
function allow_membership_and_products_together() {
    remove_action( 'woocommerce_before_calculate_totals', 'pmprowoo_limit_cart_items' );
    remove_action( 'woocommerce_single_product_summary', 'pmprowoo_purchase_disabled' );
    remove_filter( 'woocommerce_is_purchasable', 'pmprowoo_is_purchasable', 10 );
    add_filter( 'pmprowoo_limit_cart', '__return_false' );
}
add_action( 'init', 'allow_membership_and_products_together' );

// Force cart to contain only one product at a time (remove old, add new)
add_filter( 'woocommerce_add_to_cart_validation', 'replace_cart_with_new_product', 10, 3 );
function replace_cart_with_new_product( $passed, $product_id, $quantity ) {
    if ( ! WC()->cart->is_empty() ) {
        WC()->cart->empty_cart();
    }
    return $passed;
}

// Force quantity to always be 1
add_filter( 'woocommerce_add_cart_item_data', 'force_quantity_one_cart_item_data', 10, 2 );
function force_quantity_one_cart_item_data( $cart_item_data, $product_id ) {
    $cart_item_data['quantity'] = 1;
    return $cart_item_data;
}

// Prevent quantity changes in cart page
add_filter( 'woocommerce_cart_item_quantity', 'disable_quantity_input_cart', 10, 3 );
function disable_quantity_input_cart( $product_quantity, $cart_item_key, $cart_item ) {
    return $cart_item['quantity']; // Just show static quantity, no input box
}

add_action('woocommerce_payment_complete', 'custom_woocommerce_payment_complete', 10, 1);

function custom_woocommerce_payment_complete($order_id) {
    $order = wc_get_order($order_id);
    
    // Check if the payment is successful and the status is not already completed
    if ( $order->get_status() == 'on-hold' || $order->get_status() == 'processing' ) {
        // Only mark the order as completed if the payment is confirmed
        $order->update_status('completed', 'Payment successfully processed. Order marked as completed.');
    }
}

add_action('woocommerce_new_order', 'send_custom_order_status_email_to_customer', 10, 1);

function send_custom_order_status_email_to_customer($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $to_email = $order->get_billing_email();

    if (empty($to_email)) {
        $user = $order->get_user();
        if ($user && is_object($user)) {
            $to_email = $user->user_email;
        }
    }

    if (empty($to_email)) {
        error_log("❌ No email found for order {$order_id}");
        return;
    }

    $first_name = $order->get_billing_first_name();
    $order_status = wc_get_order_status_name($order->get_status());

    $subject = 'Order Confirmation - #' . $order->get_order_number();

    $message = "Hi {$first_name},\n\n";
    $message .= "Thank you for your order! Your order number is #{$order_id}.\n\n";
    $message .= "✅ Current Status: {$order_status}\n\n";
    $message .= "We’ll notify you if the status of your order changes.\n\n";
    $message .= "Regards,\n Celestial Witnesses";

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($to_email, $subject, $message, $headers);
}
add_action('woocommerce_thankyou', 'force_cod_to_pending_payment', 10, 1);
function force_cod_to_pending_payment($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Check if the payment method is COD
    if ($order->get_payment_method() === 'cod') {
        // Set status to pending payment if it's not already
        if ($order->get_status() !== 'pending') {
            $order->update_status('pending', 'Forced to pending because payment method is Cash on Delivery.');
        }
    }
}


add_action( 'woocommerce_order_details_after_order_table', 'custom_show_full_bacs_info_on_view_order_only', 10, 1 );

function custom_show_full_bacs_info_on_view_order_only( $order ) {
    // Skip if not BACS payment
    if ( $order->get_payment_method() !== 'bacs' ) {
        return;
    }

    // ✅ Avoid duplicate display on Thank You page
    if ( is_order_received_page() ) {
        return;
    }

    // Load BACS gateway
    $gateways = WC()->payment_gateways->get_available_payment_gateways();

    if ( ! isset( $gateways['bacs'] ) ) {
        return;
    }

    $bacs = $gateways['bacs'];

    echo '<div class="bacs-payment-info">';

    // Title
    if ( ! empty( $bacs->title ) ) {
        echo '<h2>' . esc_html( $bacs->title ) . '</h2>';
    }

    // Description
    if ( ! empty( $bacs->description ) ) {
        echo '<p>' . wp_kses_post( nl2br( $bacs->description ) ) . '</p>';
    }

    // Instructions
    if ( ! empty( $bacs->instructions ) ) {
        echo '<h3>Instructions:</h3>';
        echo '<p>' . wp_kses_post( nl2br( $bacs->instructions ) ) . '</p>';
    }

    // Bank Account Details
    $accounts = get_option( 'woocommerce_bacs_accounts' );
    if ( ! empty( $accounts ) && is_array( $accounts ) ) {
        echo '<h3>Bank Details:</h3>';
        foreach ( $accounts as $account ) {
            echo '<ul class="woocommerce-bacs-account-details">';
            if ( ! empty( $account['account_name'] ) ) echo '<li><strong>Account Name:</strong> ' . esc_html( $account['account_name'] ) . '</li>';
            if ( ! empty( $account['account_number'] ) ) echo '<li><strong>Account Number:</strong> ' . esc_html( $account['account_number'] ) . '</li>';
            if ( ! empty( $account['bank_name'] ) ) echo '<li><strong>Bank Name:</strong> ' . esc_html( $account['bank_name'] ) . '</li>';
            if ( ! empty( $account['sort_code'] ) ) echo '<li><strong>Transit Number:</strong> ' . esc_html( $account['sort_code'] ) . '</li>';
            if ( ! empty( $account['iban'] ) ) echo '<li><strong>IBAN:</strong> ' . esc_html( $account['iban'] ) . '</li>';
            if ( ! empty( $account['bic'] ) ) echo '<li><strong>BIC / Swift:</strong> ' . esc_html( $account['bic'] ) . '</li>';
            echo '</ul>';
        }
    }

    echo '</div>';
}




function display_payment_methods_shortcode() {
    // Get available payment gateways
    $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

    if ( ! empty( $payment_gateways ) ) {
        $output = '<ul>';

        // Loop through each payment gateway and display it
        foreach ( $payment_gateways as $gateway ) {
            $output .= '<li>' . $gateway->get_title() . '</li>';
        }

        $output .= '</ul>';
    } else {
        $output = 'No payment methods available.';
    }

    return $output;
}

add_shortcode( 'payment_methods_list', 'display_payment_methods_shortcode' );
