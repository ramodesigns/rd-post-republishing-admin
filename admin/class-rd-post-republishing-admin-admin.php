<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.paulramotowski.com
 * @since      1.0.0
 *
 * @package    Rd_Post_Republishing_Admin
 * @subpackage Rd_Post_Republishing_Admin/admin
 */

class Rd_Post_Republishing_Admin_Admin {

	private $plugin_name;
	private $version;

	private $hook_licensed_domains;
	private $hook_subscription_details;
	private $hook_logs;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_admin_menu() {
		// Top-level menu — accessible to all users (read capability)
		add_menu_page(
			__( 'RD Republishing', 'rd-post-republishing-admin' ),
			__( 'RD Republishing', 'rd-post-republishing-admin' ),
			'read',
			'rd-post-republishing-admin',
			array( $this, 'display_licensed_domains_page' ),
			'dashicons-admin-network',
			80
		);

		// Sub-page: Licensed Domains — all users
		$this->hook_licensed_domains = add_submenu_page(
			'rd-post-republishing-admin',
			__( 'Licensed Domains', 'rd-post-republishing-admin' ),
			__( 'Licensed Domains', 'rd-post-republishing-admin' ),
			'read',
			'rd-post-republishing-admin',
			array( $this, 'display_licensed_domains_page' )
		);

		// Sub-page: Subscription Details — all users
		$this->hook_subscription_details = add_submenu_page(
			'rd-post-republishing-admin',
			__( 'Subscription Details', 'rd-post-republishing-admin' ),
			__( 'Subscription Details', 'rd-post-republishing-admin' ),
			'read',
			'rd-post-republishing-admin-subscription-details',
			array( $this, 'display_subscription_details_page' )
		);

		// Sub-page: Logs — admin level only
		$this->hook_logs = add_submenu_page(
			'rd-post-republishing-admin',
			__( 'Logs', 'rd-post-republishing-admin' ),
			__( 'Logs', 'rd-post-republishing-admin' ),
			'manage_options',
			'rd-post-republishing-admin-logs',
			array( $this, 'display_logs_page' )
		);
	}

	public function display_licensed_domains_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/rd-post-republishing-admin-licensed-domains-display.php';
	}

	public function display_subscription_details_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/rd-post-republishing-admin-subscription-details-display.php';
	}

	public function display_logs_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/rd-post-republishing-admin-logs-display.php';
	}

	public function enqueue_styles( $hook ) {
		// Global admin CSS for this plugin
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rd-post-republishing-admin-admin.css', array(), $this->version, 'all' );

		// Per-page CSS
		if ( $hook === $this->hook_licensed_domains ) {
			wp_enqueue_style( $this->plugin_name . '-licensed-domains', plugin_dir_url( __FILE__ ) . 'css/rd-post-republishing-admin-licensed-domains.css', array(), $this->version, 'all' );
		}

		if ( $hook === $this->hook_subscription_details ) {
			wp_enqueue_style( $this->plugin_name . '-subscription-details', plugin_dir_url( __FILE__ ) . 'css/rd-post-republishing-admin-subscription-details.css', array(), $this->version, 'all' );
		}

		if ( $hook === $this->hook_logs ) {
			wp_enqueue_style( $this->plugin_name . '-logs', plugin_dir_url( __FILE__ ) . 'css/rd-post-republishing-admin-logs.css', array(), $this->version, 'all' );
		}
	}

	public function enqueue_scripts( $hook ) {
		// Global admin JS for this plugin
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rd-post-republishing-admin-admin.js', array( 'jquery' ), $this->version, false );

		// Per-page JS
		if ( $hook === $this->hook_licensed_domains ) {
			wp_enqueue_script( $this->plugin_name . '-licensed-domains', plugin_dir_url( __FILE__ ) . 'js/rd-post-republishing-admin-licensed-domains.js', array( 'jquery' ), $this->version, false );
		}

		if ( $hook === $this->hook_subscription_details ) {
			wp_enqueue_script( $this->plugin_name . '-subscription-details', plugin_dir_url( __FILE__ ) . 'js/rd-post-republishing-admin-subscription-details.js', array( 'jquery' ), $this->version, false );
		}

		if ( $hook === $this->hook_logs ) {
			wp_enqueue_script( $this->plugin_name . '-logs', plugin_dir_url( __FILE__ ) . 'js/rd-post-republishing-admin-logs.js', array( 'jquery' ), $this->version, false );
		}
	}

}
