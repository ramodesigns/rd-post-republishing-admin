<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.paulramotowski.com
 * @since      1.0.0
 *
 * @package    Rd_Post_Republishing_Admin
 * @subpackage Rd_Post_Republishing_Admin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rd_Post_Republishing_Admin
 * @subpackage Rd_Post_Republishing_Admin/includes
 * @author     Paul Ramotowski <paulramotowski@gmail.com>
 */
class Rd_Post_Republishing_Admin_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'setup/Init_Setup.php';
		Init_Setup::create_preferences_table();
		Init_Setup::create_license_table();
		Init_Setup::create_action_log_table();
	}

}
