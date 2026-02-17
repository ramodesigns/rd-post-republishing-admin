<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class License_Service {

    private $license_helper;

    public function __construct($license_helper = null) {
        $this->license_helper = $license_helper ?: new License_Helper();
    }

    public function create_license($domain_name) {
        global $wpdb;
        $table_name = Init_Setup::get_license_table_name();

        if (!$this->license_helper->validate_domain($domain_name)) {
            return new WP_Error('invalid_domain', 'A valid domain name is required.');
        }

        $domain_name = sanitize_text_field(trim($domain_name));
        $activation_key = $this->license_helper->generate_activation_key($domain_name);

        $current_user = wp_get_current_user();
        $username = ($current_user && $current_user->ID) ? $current_user->user_login : 'system';

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'timestamp'      => current_time('mysql'),
                'username'       => $username,
                'domain_name'    => $domain_name,
                'activation_key' => $activation_key,
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            return new WP_Error('license_create_error', 'Failed to create license.');
        }

        return array(
            'id' => $wpdb->insert_id,
        );
    }

    public function delete_license($id, $username = null) {
        global $wpdb;
        $table_name = Init_Setup::get_license_table_name();

        $id = absint($id);
        if (!$id) {
            return new WP_Error('invalid_id', 'A valid license ID is required.');
        }

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$existing) {
            return new WP_Error('license_not_found', 'License not found.', array('status' => 404));
        }

        if ($username && $existing['username'] !== $username) {
            return new WP_Error('license_forbidden', 'You do not have permission to delete this license.', array('status' => 403));
        }

        $deleted = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );

        if ($deleted === false) {
            return new WP_Error('license_delete_error', 'Failed to delete license.');
        }

        return true;
    }

    public function get_license_by_id($id) {
        global $wpdb;
        $table_name = Init_Setup::get_license_table_name();

        $id = absint($id);
        if (!$id) {
            return new WP_Error('invalid_id', 'A valid license ID is required.');
        }

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$result) {
            return new WP_Error('license_not_found', 'License not found.', array('status' => 404));
        }

        return $result;
    }

    public function get_license_by_domain($domain_name) {
        global $wpdb;
        $table_name = Init_Setup::get_license_table_name();

        if (!$this->license_helper->validate_domain($domain_name)) {
            return new WP_Error('invalid_domain', 'A valid domain name is required.');
        }

        $domain_name = sanitize_text_field(trim($domain_name));

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE domain_name = %s", $domain_name),
            ARRAY_A
        );

        if (!$result) {
            return new WP_Error('license_not_found', 'License not found for this domain.', array('status' => 404));
        }

        return $result;
    }

    public function get_licenses_by_user($username) {
        global $wpdb;
        $table_name = Init_Setup::get_license_table_name();

        $username = sanitize_text_field($username);
        if (empty($username)) {
            return new WP_Error('invalid_username', 'A valid username is required.');
        }

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE username = %s ORDER BY timestamp DESC", $username),
            ARRAY_A
        );

        return $results ?: array();
    }
}
