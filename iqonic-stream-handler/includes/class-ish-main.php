<?php
if (!defined('ABSPATH')) {
    exit;
}

class ISH_Main {

    /**
     * Constructor.
     * Loads required files, initializes plugin functionality, and sets up admin hooks.
     */
    public function __construct() {
        $this->includes();
        $this->init();
        $this->admin_hooks();
    }
    
    /**
     * Include necessary files.
     **/
    private function includes() {
        require_once ISH_PLUGIN_PATH . 'includes/class-ish-meta-fields.php';
        require_once ISH_PLUGIN_PATH . 'includes/class-ish-membership-transaction.php';
        require_once ISH_PLUGIN_PATH . 'includes/class-ish-membership-transaction-data.php';
        require_once ISH_PLUGIN_PATH . 'includes/class-ish-membership-sales.php';
        require_once ISH_PLUGIN_PATH . 'includes/class-ish-membership-sales-data.php';
    }

    /**
     * Registers admin-specific hooks.
     *
     * This function hooks into the WordPress admin_enqueue_scripts action 
     * to load the plugin's admin assets (CSS, JS, etc.) on the admin side.
     */
    public function admin_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueues the admin assets for the plugin.
     *
     * This function loads the CSS and JavaScript files required for the plugin's admin interface.
     * It also localizes the JavaScript file with dynamic data (such as the AJAX URL, loading text,
     * select state text, and a security nonce) for use in the script.
     */
    public function enqueue_admin_assets() {
        wp_enqueue_style('ish-admin-css', ISH_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('ish-admin-js', ISH_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);

        wp_localize_script("ish-admin-js", "ishPluginData", array(
            "ajax_url" => admin_url("admin-ajax.php"),
            "loading_text" => __("Loading...", "my-membership-plugin"),
            "select_state_text" => __("Select State", "my-membership-plugin"),
            "nonce" => wp_create_nonce("ish_plugin_nonce") // Security nonce
        ));
    }
    
    /**
     * Initialize Plugin Components.
     *
     * This method creates instances of the primary plugin classes:
     * - ISH_Meta_Fields: Manages additional meta fields for the plugin.
     * - Membership_Transactions: Handles membership transaction functionality.
     * - Membership_Sales: Manages and reports membership sales data.
     */
    public function init() {
        new ISH_Meta_Fields();
        new Membership_Transactions();
        new Membership_Sales();
    }
}
