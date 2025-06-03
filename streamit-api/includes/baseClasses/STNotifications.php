<?php

namespace Includes\baseClasses;

use Error;
use \Firebase\JWT\JWT;

class STNotifications
{
    public function __construct()
    {
        add_action('transition_post_status', [$this, 'new_content_notifications'], 10, 3);
        add_action('pmpro_after_checkout', [$this, 'send_membership_subscription_notification'], 10, 2);
    }

    public static function get_firebase_settings()
    {
        global $st_app_options;
        if (!isset($st_app_options['firebase']))
            $st_app_options = get_option("st_app_options");

        if (isset($st_app_options['firebase']['firebase_private_key']))
            return $st_app_options['firebase'];

        return [];
    }

    function new_content_notifications($new_status, $old_status, $post)
    {
        // Return if the post is old
        if (get_post_meta($post->ID, "st_is_old", true))
            return;

        // Return if post status is not 'publish'
        if ($new_status !== 'publish')
            return;

        // Only proceed for post_types = 'movie', 'tv_show', or 'video' or 'episode'
        if (!in_array($post->post_type, ['movie', 'tv_show', 'video', 'episode']))
            return;

        // Prepare data array to send notification
        $data = [
            "data"          => [
                "id"    => $post->ID,
                "type"  => $post->post_type,//'new-content'
            ],
            "heading"       => [
                "en"    => __("New Arrival", STA_TEXT_DOMAIN)
            ],
            "content"       => [
                "en"    => sprintf(_x("%s", "Firebase notification for new content", STA_TEXT_DOMAIN), get_the_title($post->ID))
            ]
        ];

        // Mark post as old
        update_post_meta($post->ID, "st_is_old", 1);
        self::send($data);
    }

    public function send_membership_subscription_notification($user_id, $morder)
    {   
        // Prepare data to send notification
        $data = [
            "data" => [
                "user_id" => $user_id,
                "type"    => 'new-subscription'
            ],
            "heading" => [
                "en" => __("Membership Subscription Successful", STA_TEXT_DOMAIN)
            ],
            "content" => [
                "en" => sprintf(__("Congratulations! You have successfully subscribed to the %s membership plan.", STA_TEXT_DOMAIN), $morder->membership_level->name)
            ]
        ];
        $fields_data = array_merge($data, $this->get_notification_data(['user_id' => $user_id]));
        // Send the notification
        self::send($fields_data);
    }

    public static function send($fields_data)
    {
        if (empty($fields_data) || !is_array($fields_data)) return;

        $content    = $fields_data["content"] ?? [];
        $heading    = $fields_data["heading"] ?? [];
        $data       = $fields_data["data"] ?? [];

        if (!is_array($content) || !is_array($heading) || !is_array($data)) {
            error_log('Invalid data structure: content, heading, or data is not an array.');
            return;
        }

        if (isset($url)) {
            $fields['url'] = $url;
        }
        if (!function_exists('curl_init')) {
            die('cURL library is not enabled in PHP');
        }

        // FCM endpoint
        $firebase_settings = get_option('st_app_options');
        $firebase = $firebase_settings['firebase'] ?? []; // Access nested 'firebase' array
        $projectId = $firebase["project_id"] ?? "";
        $clientEmail = $firebase["client_email"] ?? "";
        $appName = $firebase["app_name"] ?? "";
        $formattedAppName = strtolower(str_replace(' ', '_', $appName));
        $privateKey = $firebase["private_key"] ?? "";
        $fcmEndpoint = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        if (empty($projectId) || empty($clientEmail) || empty($privateKey) || empty($appName)) {
            return false;
        }
        error_log(print_r($privateKey ,true));

        $formatted_key = self::format_pem_key($privateKey);
        error_log(print_r($formatted_key ,true));
        
        $accessToken = self::generate_firebase_oauth2_token($clientEmail, $formatted_key);

        $addition = [
            "story_id" => "story_12345",
            "additional_data" => json_encode($fields_data['data'] ?? [])
        ];
        
        if(isset($fields_data['user_id'])){
            $formattedAppName = "user_" . $fields_data['user_id'];
        }
        
        $data = [
            'message' => [
                "topic" => $formattedAppName,
                'notification' => [
                    'title' => $heading["en"] ?? '',
                    'body'  => $content["en"] ?? '',
                ],
                'data' => $addition,
                "android" => [
                    "notification" => [
                        "click_action" => "TOP_STORY_ACTIVITY",
                    ],
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "category" => "NEW_MESSAGE_CATEGORY",
                        ],
                    ],
                ],
            ]
        ];
        // Set headers
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        // Initialize cURL session
        $ch = curl_init($fcmEndpoint);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute cURL session
        $response = curl_exec($ch);
        error_log($response);

        // Close cURL session
        curl_close($ch);
    }

    private function get_notification_data($fields_data)
    {
        $player_ids = [];
        if (is_array($fields_data['user_id'])) {
            foreach ($fields_data['user_id'] as $id) {
                $player_ids = array_merge($player_ids, get_user_meta($id, 'streamit_firebase_tokens', true) ?? []);
            }
        } else {
            $player_ids = get_user_meta($fields_data['user_id'], 'streamit_firebase_tokens', true) ?? [];
        }
        return  [
            "player_ids" => is_array($player_ids) ? array_unique(array_values($player_ids)) : []
        ];
    }

    public static function generate_firebase_oauth2_token($clientEmail, $privateKey)
    {
        $payload = [
            'iss' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => time() + 3600,  // Token validity period
            'iat' => time(),
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        // Request an access token
        $tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]),
            ],
        ]));

        $tokenData = json_decode($tokenResponse, true);
        return $tokenData['access_token'] ?? null;
    }

    public static function format_pem_key($pem_string)
    {
        // Remove extra newline characters and ensure proper line breaks
        $pem_string = str_replace("\\n", "\n", $pem_string);
        $pem_string = trim($pem_string, "\"'");
        return $pem_string;
    }
}
