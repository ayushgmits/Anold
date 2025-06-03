<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Membership_Transactions_Table extends WP_List_Table {

    protected $data; // Holds the transactions data
    protected $total_amount = 0; // Initialize total_amount as 0

    /**
     * Constructor.
     * Initializes the Transactions list table by calling the parent WP_List_Table constructor.
     */

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Transaction', 'my-membership-plugin' ),
            'plural'   => __( 'Transactions', 'my-membership-plugin' ),
            'ajax'     => false
        ) );

    }
    
    /**
     * Get the column headers for the Sales table.
     *
     * This function defines the columns that will be displayed in the Sales list table.
     * It includes columns for username, display name, email, amount, date, country, and state.
     * If a grand total sales amount (stored in $this->total_amount) is available and greater than zero,
     * that value is appended to the "Amount" column header.
     *
     * @return array An associative array of column keys and their display labels.
     */
    public function get_columns() {
        $columns = array(
            'username'     => __( 'Username', 'my-membership-plugin' ),
            'display_name' => __( 'Display Name', 'my-membership-plugin' ),
            'email'        => __( 'Email', 'my-membership-plugin' ),
            'amount'       => __( 'Amount', 'my-membership-plugin' ),
            'date'         => __( 'Date', 'my-membership-plugin' ),
            'country'      => __( 'Country', 'my-membership-plugin' ),
            'state'        => __( 'State', 'my-membership-plugin' ),
        );
    
        // If total amount is available, update the column title
        if ( isset( $this->total_amount ) && $this->total_amount > 0 ) {
            $columns['amount'] .= " (Total: $" . number_format( $this->total_amount, 2 ) . ")";
        }
    
        return $columns;
    }
    
    /**
     * Set the total sales amount.
     *
     * This method stores the calculated total sales amount in the class property 
     * $this->total_amount. This value is used later for displaying the overall 
     * total in the table header or elsewhere in the plugin.
     *
     * @param float $amount The total sales amount to set.
     */
    public function set_total_amount($amount) {
        $this->total_amount = $amount;
    }
    
    /**
     * Prepares the items for the Transactions table.
     *
     * This method builds and executes the SQL queries to fetch, filter, and paginate 
     * the transaction data from the orders table. It also calculates the grand total 
     * sales amount and stores it for later use (e.g. in the table header).
     */
    public function prepare_items() {
        global $wpdb;
    
        $per_page = 24; // Number of items per page
        $current_page = $this->get_pagenum();
    
        $start_date       = !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date         = !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $user_id          = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $transaction_type = isset($_GET['transaction_type']) ? sanitize_text_field($_GET['transaction_type']) : '';
        $country          = isset($_GET['countrys']) ? sanitize_text_field($_GET['countrys']) : '';
        $state            = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
    
        $table_name      = $wpdb->prefix . 'pmpro_membership_orders';
        $usermeta        = $wpdb->prefix . 'usermeta';
        $meta_table      = $wpdb->prefix . 'postmeta';
        
        // Base query without pagination
        $base_query = "
            FROM $table_name o
            LEFT JOIN $usermeta AS country ON o.user_id = country.user_id AND country.meta_key = 'country'
            LEFT JOIN $usermeta AS state ON o.user_id = state.user_id AND state.meta_key = 'state'
            LEFT JOIN $meta_table m ON o.membership_id = m.post_id AND (m.meta_key = '_custom_coins_level' OR m.meta_key IS NULL)
            WHERE o.status = 'success'
        ";
    
        // Apply filters
        if (!empty($start_date)) {
            $base_query .= $wpdb->prepare(" AND o.timestamp >= %s", $start_date . ' 00:00:00');
        }
        if (!empty($end_date)) {
            $base_query .= $wpdb->prepare(" AND o.timestamp <= %s", $end_date . ' 23:59:59');
        }
        
        if ($user_id > 0) {
            $base_query .= $wpdb->prepare(" AND o.user_id = %d", $user_id);
        }
        if (!empty($transaction_type)) {
            if ($transaction_type == 'coins') {
                $base_query .= " AND m.meta_key = '_custom_coins_level' AND m.meta_value = '1'";
            } elseif ($transaction_type == 'subscription') {
                $base_query .= " AND (m.meta_key IS NULL OR m.meta_key != '_custom_coins_level')";
            }
        }
        if (!empty($country)) {
            $base_query .= $wpdb->prepare(" AND country.meta_value = %s", $country);
        }
        if (!empty($state)) {
            $base_query .= $wpdb->prepare(" AND state.meta_value = %s", $state);
        }
    
        // ✅ Calculate Total Sales Amount BEFORE pagination (SUM only distinct orders)
        $total_sales_query = "SELECT SUM(o.total) " . $base_query;
        $total_amount = floatval($wpdb->get_var($total_sales_query));
        $this->set_total_amount($total_amount);
    
        // ✅ Get total items count for pagination
        $total_items_query = "SELECT COUNT(o.id) " . $base_query;
        $total_items = $wpdb->get_var($total_items_query);
    
        // ✅ Apply Pagination for displayed records
        $offset = ($current_page - 1) * $per_page;
        $paginated_query = "SELECT o.id, o.*, country.meta_value AS country, state.meta_value AS state " . $base_query;
        $paginated_query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
    
        // Fetch paginated results
        $this->items = $wpdb->get_results($paginated_query);
    
        // Set up columns
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
    
        // ✅ Set Pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }

    /**
     * Default column output for the Sales table.
     *
     * This function determines how each column's data is displayed for every row in the sales table.
     * Depending on the column key, it retrieves and formats user data, sales totals, dates, or meta values.
     *
     * @param object $item        The current row's data.
     * @param string $column_name The column key for which the output is being generated.
     * @return string             The formatted output for the column.
     */
    public function column_default( $item, $column_name ) {
        $user_info = get_userdata( $item->user_id );
    
        switch ( $column_name ) {
            case 'username':
                return $user_info ? esc_html( $user_info->user_login ) : __( 'N/A', 'my-membership-plugin' );
            case 'display_name':
                return $user_info ? esc_html( $user_info->display_name ) : __( 'N/A', 'my-membership-plugin' );
            case 'email':
                return $user_info ? esc_html( $user_info->user_email ) : __( 'N/A', 'my-membership-plugin' );
            case 'amount':
                return esc_html( $item->total );
            case 'date':
                return esc_html( date( 'Y-m-d H:i:s', strtotime( $item->timestamp ) ) );
            case 'country':
                return esc_html( get_user_meta( $item->user_id, 'country', true ) ?: 'N/A' );
            case 'state':
                return esc_html( get_user_meta( $item->user_id, 'state', true ) ?: 'N/A' );
            default:
                return print_r( $item, true );
        }
    }
}
