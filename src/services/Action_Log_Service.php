<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Action_Log_Service {

    public function log_action($action, $comments = '') {
        global $wpdb;
        $table_name = Init_Setup::get_action_log_table_name();

        $current_user = wp_get_current_user();
        $username = ($current_user && $current_user->ID) ? $current_user->user_login : 'system';

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'username'  => $username,
                'action'    => sanitize_text_field($action),
                'comments'  => sanitize_textarea_field($comments),
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            return new WP_Error('action_log_error', 'Failed to log action.');
        }

        return array('id' => $wpdb->insert_id);
    }

    public function get_all_logs() {
        global $wpdb;
        $table_name = Init_Setup::get_action_log_table_name();
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC", ARRAY_A);
        return $results ?: array();
    }
}
