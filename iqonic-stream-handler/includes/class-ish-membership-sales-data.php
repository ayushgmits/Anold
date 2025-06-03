<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Membership_Sales_Table extends WP_List_Table {

    protected $data; // Holds the sales data
    protected $total_amount = 0; // Initialize total_amount as 0
    
    /**
     * Constructor.
     * Initializes the Sales table settings by calling the parent WP_List_Table constructor.
     *
     * 'singular' => A label used for a single sale item (e.g. in bulk action messages).
     * 'plural'   => A label used for multiple sale items (e.g. in the table header).
     * 'ajax'     => Set to false because this table does not use AJAX for pagination or sorting.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Sale', 'my-membership-plugin' ),
            'plural'   => __( 'Sales', 'my-membership-plugin' ),
            'ajax'     => false
        ) );
    }

    /**
     * Retrieve an associative array of column headers for the Sales table.
     ** @return array The array of column keys and their labels.
    */
    public function get_columns() {
        $columns = array(
            'username'     => __( 'Username', 'my-membership-plugin' ),
            'display_name' => __( 'Display Name', 'my-membership-plugin' ),
            'email'        => __( 'Email', 'my-membership-plugin' ),
            'total_amount' => __( 'Total Sales', 'my-membership-plugin' ),
            'date'         => __( 'Date', 'my-membership-plugin' ),
            'country'      => __( 'Country', 'my-membership-plugin' ),
            'state'        => __( 'State', 'my-membership-plugin' ),
        );
    
        // Display the total amount in the table header
        if ( isset( $this->total_amount ) && $this->total_amount > 0 ) {
            $columns['total_amount'] .= " (Total: $" . number_format( $this->total_amount, 2 ) . ")";
        }
    
        return $columns;
    }
    
    /**
     * Set the total amount for the sales table.
     *
     * @param float $amount The total sales amount to be set.
     */
    public function set_total_amount($amount) {
        $this->total_amount = $amount;
    }

    /**
     * Prepares the items for the Transactions table.
     *
     * This function builds and executes the SQL query to retrieve transactions data,
     * applies filters from GET parameters, calculates the grand total sales amount,
     * and sets up pagination for the list table.
     */
    public function prepare_items() {
        global $wpdb;
    
        $per_page = 24;
        $current_page = $this->get_pagenum();
    
        $start_date       = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date         = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $user_id          = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        // $transaction_type = isset($_GET['transaction_type']) ? sanitize_text_field($_GET['transaction_type']) : '';
        $country          = isset($_GET['countrys']) ? sanitize_text_field($_GET['countrys']) : '';
        $state            = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
    
        $table_name = $wpdb->prefix . 'pmpro_membership_orders';
        $usermeta   = $wpdb->prefix . 'usermeta';
        $meta_table = $wpdb->prefix . 'postmeta';
    
        // Base Query
        $base_query = "
            FROM $table_name o
            LEFT JOIN $usermeta AS country ON o.user_id = country.user_id AND country.meta_key = 'country'
            LEFT JOIN $usermeta AS state ON o.user_id = state.user_id AND state.meta_key = 'state'
            LEFT JOIN $meta_table m ON o.membership_id = m.post_id
            WHERE o.status = 'success'
        ";
    
        // Apply Filters
            $start_timestamp = strtotime($start_date . ' 00:00:00 UTC');
            $end_timestamp   = strtotime($end_date . ' 23:59:59 UTC');

            // Apply filters
            if (!empty($start_date)) {
                $base_query .= $wpdb->prepare(" AND o.timestamp >= %s", date('Y-m-d 00:00:00', strtotime($start_date)));
            }
            if (!empty($end_date)) {
                $base_query .= $wpdb->prepare(" AND o.timestamp <= %s", date('Y-m-d 23:59:59', strtotime($end_date)));
            }
            
            if (!empty($user_id)) {
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

       
    
        // ✅ Calculate Grand Total Sales Amount BEFORE pagination
        $grand_total_query = "SELECT COALESCE(SUM(o.total), 0) " . $base_query;
        $grand_total = floatval($wpdb->get_var($grand_total_query));
        $this->set_total_amount($grand_total); // Store the grand total amount
    
        // ✅ Get total user count for pagination
        $total_items_query = "SELECT COUNT(DISTINCT o.user_id) " . $base_query;
        $total_items = $wpdb->get_var($total_items_query);
    
        // ✅ Apply Pagination
        $offset = ($current_page - 1) * $per_page;
        $paginated_query = "SELECT o.user_id, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total), 0) AS total_amount, MAX(o.timestamp) AS timestamp,
        country.meta_value AS country, state.meta_value AS state
        $base_query
        GROUP BY o.user_id
    ";

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
     * Default column rendering for each row in the Sales table.
     *
     * This function handles output for each column based on the column key.
     * It retrieves user data and formats values as needed.
     *
     * @param object $item The current row item (sales data).
     * @param string $column_name The current column being rendered.
     * @return string The formatted column output.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'username':
                $user_info = get_userdata( $item->user_id );
                if ( $user_info ) {
                    // Build the URL dynamically
                    $url = admin_url( 'admin.php?page=pmpro-member&user_id=' . $item->user_id );
                    return '<a href="' . esc_url( $url ) . '">' . esc_html( $user_info->user_login ) . '</a>';
                } else {
                    return __( 'N/A', 'my-membership-plugin' );
                }
            case 'display_name':
                $user_info = get_userdata( $item->user_id );
                return $user_info ? esc_html( $user_info->display_name ) : __( 'N/A', 'my-membership-plugin' );
    
            case 'email':
                $user_info = get_userdata( $item->user_id );
                return $user_info ? esc_html( $user_info->user_email ) : __( 'N/A', 'my-membership-plugin' );
    
            case 'total_amount':
                return esc_html( number_format( $item->total_amount, 2 ) );
    
                case 'date':
                    return esc_html(date('Y-m-d H:i:s', strtotime($item->timestamp)));
                
            case 'country':
                return esc_html( get_user_meta( $item->user_id, 'country', true ) ?: 'N/A' );
    
            case 'state':
                return esc_html( get_user_meta( $item->user_id, 'state', true ) ?: 'N/A' );
    
            default:
                return print_r( $item, true );
        }
    }
    
}
