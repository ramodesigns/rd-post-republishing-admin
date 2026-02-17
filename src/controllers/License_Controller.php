<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class License_Controller {

    private $namespace = 'postrepublishing/v1/license';
    private $service;
    private $authorisation_helper;

    public function __construct($authorisation_helper) {
        $this->authorisation_helper = $authorisation_helper;
        $this->service = new License_Service();
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function register_rest_routes() {
        // Create a license
        register_rest_route($this->namespace, '/create', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_create'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'domain_name' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The domain name to generate a license for.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/createpublic', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_create'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'domain_name' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The domain name to generate a license for.',
                ),
            ),
        ));

        // Delete a license
        register_rest_route($this->namespace, '/clear', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array($this, 'handle_clear'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'The ID of the license to delete.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/clearpublic', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array($this, 'handle_clear'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'The ID of the license to delete.',
                ),
            ),
        ));

        // Retrieve license by ID
        register_rest_route($this->namespace, '/id', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_get_by_id'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'The ID of the license to retrieve.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/idpublic', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_get_by_id'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'id' => array(
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'The ID of the license to retrieve.',
                ),
            ),
        ));

        // List licenses for the current user
        register_rest_route($this->namespace, '/list', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_list'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        register_rest_route($this->namespace, '/listpublic', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_list'),
            'permission_callback' => array($this, 'check_debug_authorization'),
        ));

        // Retrieve license by domain
        register_rest_route($this->namespace, '/domain', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_get_by_domain'),
            'permission_callback' => array($this, 'check_authentication'),
            'args'                => array(
                'domain_name' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The domain name to search for.',
                ),
            ),
        ));
        register_rest_route($this->namespace, '/domainpublic', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'handle_get_by_domain'),
            'permission_callback' => array($this, 'check_debug_authorization'),
            'args'                => array(
                'domain_name' => array(
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'The domain name to search for.',
                ),
            ),
        ));
    }

    public function handle_create($request) {
        try {
            $domain_name = $request->get_param('domain_name');
            $result = $this->service->create_license($domain_name);

            if (is_wp_error($result)) {
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => array($result->get_error_message()),
                    'timestamp' => current_time('mysql'),
                ), 400);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'License created successfully.',
                'data'      => $result,
                'timestamp' => current_time('mysql'),
            ), 201);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_list($request) {
        try {
            $current_user = wp_get_current_user();
            $username = $current_user->user_login;
            $result = $this->service->get_licenses_by_user($username);

            if (is_wp_error($result)) {
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => array($result->get_error_message()),
                    'timestamp' => current_time('mysql'),
                ), 400);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'Licenses retrieved successfully.',
                'data'      => $result,
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_clear($request) {
        try {
            $id = $request->get_param('id');
            $current_user = wp_get_current_user();
            $username = $current_user->user_login;
            $result = $this->service->delete_license($id, $username);

            if (is_wp_error($result)) {
                $status = $result->get_error_data() && isset($result->get_error_data()['status'])
                    ? $result->get_error_data()['status'] : 400;
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => array($result->get_error_message()),
                    'timestamp' => current_time('mysql'),
                ), $status);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'License deleted successfully.',
                'data'      => array(),
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_get_by_id($request) {
        try {
            $id = $request->get_param('id');
            $result = $this->service->get_license_by_id($id);

            if (is_wp_error($result)) {
                $status = $result->get_error_data() && isset($result->get_error_data()['status'])
                    ? $result->get_error_data()['status'] : 400;
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => array($result->get_error_message()),
                    'timestamp' => current_time('mysql'),
                ), $status);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'License retrieved successfully.',
                'data'      => $result,
                'timestamp' => current_time('mysql'),
            ), 200);
        } catch (Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_get_by_domain($request) {
        try {
            $domain_name = $request->get_param('domain_name');
            $result = $this->service->get_license_by_domain($domain_name);

            if (is_wp_error($result)) {
                $status = $result->get_error_data() && isset($result->get_error_data()['status'])
                    ? $result->get_error_data()['status'] : 400;
                return new WP_REST_Response(array(
                    'success'   => false,
                    'message'   => $result->get_error_message(),
                    'errors'    => array($result->get_error_message()),
                    'timestamp' => current_time('mysql'),
                ), $status);
            }

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'License retrieved successfully.',
                'data'      => $result,
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
        if (!current_user_can('read')) {
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
