<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Init_Setup {

    const TABLE_NAME_PREF = 'rd_admin_pref';
    const TABLE_NAME_LI_LOG = 'rd_admin_li_log';
    const TABLE_NAME_ACT_LOG = 'rd_admin_act_log';

    public static function get_preferences_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME_PREF;
    }

    public static function get_license_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME_LI_LOG;
    }

    public static function get_action_log_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME_ACT_LOG;
    }

    public static function create_preferences_table() {
        global $wpdb;
        $table_name = self::get_preferences_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `key` varchar(50) NOT NULL,
            `value` text NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function create_license_table() {
        global $wpdb;
        $table_name = self::get_license_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            username varchar(255) NOT NULL,
            domain_name varchar(255) NOT NULL,
            activation_key varchar(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function create_action_log_table() {
        global $wpdb;
        $table_name = self::get_action_log_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            username varchar(255) NOT NULL,
            action varchar(255) NOT NULL,
            comments longtext NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function drop_preferences_table() {
        global $wpdb;
        $table_name = self::get_preferences_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public static function drop_license_table() {
        global $wpdb;
        $table_name = self::get_license_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public static function drop_action_log_table() {
        global $wpdb;
        $table_name = self::get_action_log_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
