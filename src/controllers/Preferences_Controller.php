<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Preferences_Controller {

    private $namespace = 'postrepublishing/v1/preferences';
    private $service;
    private $authorisation_helper;

    public function __construct($authorisation_helper) {
        $this->authorisation_helper = $authorisation_helper;
        $this->service = new Preferences_Service();
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function register_rest_routes() {
        // Retrieve all preferences
        register_rest_route($this->namespace, '/retrieve', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_retrieve'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        register_rest_route($this->namespace, '/retrievepublic', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_retrieve'),
            'permission_callback' => array($this, 'check_debug_authorization'),
        ));

        // Update preferences
        register_rest_route($this->namespace, '/update', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_update'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'preferences' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Key-value pairs of preferences to update.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/updatepublic', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_update'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'preferences' => array(
                    'required'    => true,
                    'type'        => 'object',
                    'description' => 'Key-value pairs of preferences to update.',
                ),
            ),
        ));

        // Delete a preference by key
        register_rest_route($this->namespace, '/clear', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array($this, 'handle_delete'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'key' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The preference key to delete.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/clearpublic', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array($this, 'handle_delete'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'key' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The preference key to delete.',
                ),
            ),
        ));
    }

    public function handle_retrieve($request) {
        try {
            $data = $this->service->get_all_preferences();
            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'Preferences retrieved successfully.',
                'data'      => $data,
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_update($request) {
        try {
            $preferences = $request->get_param('preferences');
            $result = $this->service->update_preferences($preferences);

            if (is_wp_error($result)) {
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => $result->get_error_data(),
                    'timestamp' => current_time('mysql'),
                ), 400);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'Preferences updated successfully.',
                'data'      => $result,
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_delete($request) {
        try {
            $key = $request->get_param('key');
            $deleted = $this->service->delete_preference_by_key($key);

            if (!$deleted) {
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => 'Preference not found or could not be deleted.',
                    'timestamp' => current_time('mysql'),
                ), 404);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'Preference deleted successfully.',
                'data'      => array(),
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function check_authentication($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden',
                __('Authentication required. Please provide valid application password credentials.'),
                array('status' => 401));
        }
        if (!current_user_can('edit_posts')) {
            return new WP_Error('rest_forbidden',
                __('You do not have sufficient permissions to access this endpoint.'),
                array('status' => 403));
        }
        return true;
    }

    public function check_debug_authorization($request) {
        if ($this->authorisation_helper->is_debug_authorized()) {
            return true;
        }
        return new WP_Error('rest_forbidden',
            __('Public access is restricted. Please enable Debug mode in settings.'),
            array('status' => 403));
    }
}
