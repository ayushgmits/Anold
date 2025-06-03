<?php
/**
 * Streamit Content Cache handler 
 */

defined( 'ABSPATH' ) || exit;

/**
 * Session handler class.
 */
class streamit_Api_Cache_Handler {

    /**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 **/
    public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( 'streamit_api_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) :
			$prefix = microtime();
			wp_cache_set( 'streamit_api_' . $group . '_cache_prefix', $prefix, $group );
        endif;

		return 'streamit_api_cache_' . $prefix . '_';
	}

}