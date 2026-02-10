<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class License_Helper {

    public function generate_activation_key($domain_name) {
        return hash('sha256', $domain_name . microtime(true) . bin2hex(random_bytes(16)));
    }

    public function validate_domain($domain_name) {
        if (empty($domain_name)) {
            return false;
        }

        $domain_name = trim($domain_name);

        if (strlen($domain_name) > 255) {
            return false;
        }

        return true;
    }
}
