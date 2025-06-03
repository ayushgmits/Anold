<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Membership_Transactions
 *
 * Handles registration of the admin page for membership transactions.
 */
class Membership_Transactions {

    /**
     * Constructor.
     * Hooks into the admin menu.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        
        add_action('wp_ajax_get_states', [$this,'fetch_states_ajax']);
        add_action('wp_ajax_nopriv_get_states', [$this,'fetch_states_ajax']);
    }

    /**
     * Register the admin menu page.
     */
    public function register_admin_page() {
        add_submenu_page(
            'pmpro-dashboard',                       // Parent menu slug: PMPro dashboard or Membership menu slug
            __( 'Membership Transactions', 'mt-textdomain' ),  // Page title
            __( 'Transactions', 'mt-textdomain' ),   // Menu title
            'manage_options',                        // Capability required to access this submenu
            'mt-transactions',                       // Menu slug
            array( $this, 'admin_page_callback' ),   // Callback function to render the page
            'dashicons-list-view',                   // Icon for the menu item
            26                                        // Position (optional)
        );
    }

    /**
     * Callback for rendering the admin page.
     * Loads a template file.
     */
    public function admin_page_callback() {
        
        include ISH_PLUGIN_PATH . 'templates/transactions-data.php';

        $transactions_table = new Membership_Transactions_Table();
        $transactions_table->prepare_items();
         $transactions_table->display();
    }

    /**
     * Retrieve a list of countries from the external API.
     *
     * This function sends a GET request to the "countriesnow" API endpoint to fetch data on
     * all countries along with their states. It then extracts the country names and returns
     * them as an associative array where each country's name is used as both the key and the value.
     *
     * @return array An associative array of country names (e.g., array('India' => 'India', ...)).
     */
    public function get_countries_list() {
        $url = "https://countriesnow.space/api/v0.1/countries/states";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $countries = [];

        if (!empty($body['data'])) {
            foreach ($body['data'] as $country) {
                $countries[$country['name']] = $country['name'];
            }
        }
        return $countries; 
    }

    /**
     * Retrieve a list of states for a given country.
     *
     * This function fetches data from an external API that returns all countries with their states.
     * It then searches for a country matching the provided $country_name (case-insensitive) and returns
     * an array of state names. If any errors occur or if no matching country is found, an empty array is returned.
     *
     * @param string $country_name The name of the country for which to retrieve states.
     * @return array An array of state names or an empty array if not found.
     */
    public function get_states_list($country_name) {

        // Fetch all countries and states
        $url = "https://countriesnow.space/api/v0.1/countries/states";
        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['data'])) {
            return [];
        }

        // Find the states for the selected country
        foreach ($body['data'] as $country) {
            if (strcasecmp($country['name'], $country_name) === 0) {
                return wp_list_pluck($country['states'], 'name');
            }
        }

        return [];
    }



    /**
     * AJAX handler for fetching states based on the provided country name.
     *
     * This function validates the security nonce, retrieves the country name from the POST data,
     * calls the get_states_list() method to obtain an array of states for that country, and then returns
     * a JSON response with the states. If no country is provided or no states are found, it sends an error response.
     *
     * @return void Outputs a JSON response.
     */
    public function fetch_states_ajax() {
        check_ajax_referer('ish_plugin_nonce', 'security');

        $country_name = sanitize_text_field($_POST['country'] ?? '');

        if (!$country_name) {
            wp_send_json_error(['message' => 'No country provided']);
        }

        $states = $this->get_states_list($country_name);

        if (empty($states)) {
            wp_send_json_error(['message' => 'No states found']);
        }

        wp_send_json_success(['states' => $states]);
    }


}

