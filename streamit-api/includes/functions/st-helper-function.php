<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Custom response wrapper for WP REST API.
 *
 * @param array $res         The response data to be returned.
 * @param int   $status_code The HTTP status code for the response. Default is 200.
 * 
 * @return WP_REST_Response Returns a formatted WP REST API response.
 */
function st_comman_custom_response($res, $status_code = 200) {
    // Create a new WP_REST_Response object with the provided data.
    $response = new WP_REST_Response($res);
    
    // Set the HTTP status code for the response.
    $response->set_status($status_code);
    
    // Return the response object.
    return $response;
}

/**
 * Validate JWT token for authorization.
 *
 * @param $request The REST API request object.
 * @param bool $access_request_without_auth Optional flag to allow access without authentication.
 * 
 * @return array Contains the validation result.
 */
function st_token_validation($request, $access_request_without_auth = false)
{

    if ($access_request_without_auth) {
        if (empty($request->get_header('Authorization'))) {
            return [
                'status'        => true,
                'status_code'   => 200,
                'message'       => 'Valid token.',
                'user_id'       => 0
            ];
        }
    }
    $response = collect((new Jwt_Auth_Public('jwt-auth', '1.1.0'))->validate_token($request, false));

    if ($response->has('errors'))
        return [
            'status_code'   => array_values($response['error_data'])[0]["status"] ??  401,
            'status'        => false,
            'message'       => array_values($response['errors'])[0][0] ??  __("Authorization failed", 'streamit-api'),
        ];

    return [
        'status'        => true,
        'status_code'   => 200,
        'message'       => 'Valid token.',
        'user_id'       => get_current_user_id()
    ];
}


/**
 * Generate a random string of a specified length.
 *
 * @param int $length_of_string The length of the generated string. Default is 10.
 * 
 * @return string The generated random string.
 */
function st_string_generator( $length_of_string = 10 ) {
    // Ensure the length is a positive integer.
    $length_of_string = absint( $length_of_string );

    // Define the characters to use for string generation.
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $characters_length = strlen( $characters );
    $random_string = '';

    // Generate the random string securely.
    for ( $i = 0; $i < $length_of_string; $i++ ) {
        $random_string .= $characters[ random_int( 0, $characters_length - 1 ) ];
    }

    return $random_string;
}


/**
 * Recursively sanitize text fields in an array.
 *
 * This function sanitizes an array of values by recursively applying the
 * sanitize_text_field() function to each element. HTML is removed from all
 * text fields except arrays.
 *
 * @param array $data The array of data to sanitize.
 * @return array The sanitized array.
 */
function st_sanitize_recursive_text_fields(array $data): array
{
    $sanitized_data = [];

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            // Recursively sanitize arrays.
            $sanitized_data[$key] = st_sanitize_recursive_text_fields($value);
        } else {
            // Sanitize individual values.
            $sanitized_data[$key] = ('' === $value) ? null : sanitize_text_field($value);
        }
    }

    return $sanitized_data;
}


