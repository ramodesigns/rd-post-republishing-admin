<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Authorisation_Helper {

    private $preferences_service;

    public function __construct($preferences_service) {
        $this->preferences_service = $preferences_service;
    }

    public function is_debug_authorized() {
        // Check for secret token via query param or Authorization header
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
            if (strpos($auth_header, 'Bearer ') === 0) {
                $token = substr($auth_header, 7);
            }
        }

        if (!empty($token)) {
            $stored_token = $this->preferences_service->get_preference_by_key('debug_token');
            if ($stored_token && hash_equals($stored_token, $token)) {
                return true;
            }
        }

        // Fall back to checking debug_timestamp preference
        $debug_timestamp = $this->preferences_service->get_preference_by_key('debug_timestamp');
        if ($debug_timestamp && (int) $debug_timestamp > time()) {
            return true;
        }

        return false;
    }

    public function generate_token() {
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return hash('sha256', $domain . microtime(true) . bin2hex(random_bytes(16)));
    }
}
