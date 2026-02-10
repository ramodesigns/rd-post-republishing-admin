<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Preferences_Service {

    public function get_all_preferences() {
        global $wpdb;
        $table_name = Init_Setup::get_preferences_table_name();
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        return $results ?: array();
    }

    public function get_preference_by_key($key) {
        global $wpdb;
        $table_name = Init_Setup::get_preferences_table_name();
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT `value` FROM $table_name WHERE `key` = %s", $key)
        );
        return $result;
    }

    public function update_preferences($preferences) {
        global $wpdb;
        $table_name = Init_Setup::get_preferences_table_name();
        $errors = array();

        foreach ($preferences as $key => $value) {
            $key = sanitize_text_field($key);
            $value = sanitize_text_field($value);

            if (empty($key)) {
                $errors[] = 'Preference key cannot be empty.';
                continue;
            }

            $existing = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM $table_name WHERE `key` = %s", $key)
            );

            if ($existing) {
                $wpdb->update(
                    $table_name,
                    array('value' => $value),
                    array('key' => $key),
                    array('%s'),
                    array('%s')
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array('key' => $key, 'value' => $value),
                    array('%s', '%s')
                );
            }
        }

        if (!empty($errors)) {
            return new WP_Error('preferences_update_error', 'Some preferences could not be updated.', $errors);
        }

        return $this->get_all_preferences();
    }

    public function delete_preference_by_key($key) {
        global $wpdb;
        $table_name = Init_Setup::get_preferences_table_name();
        $deleted = $wpdb->delete(
            $table_name,
            array('key' => $key),
            array('%s')
        );
        return $deleted !== false;
    }
}
