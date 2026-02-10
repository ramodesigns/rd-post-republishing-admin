<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$preferences_service_for_auth = new Preferences_Service();
$auth_helper = new Authorisation_Helper($preferences_service_for_auth);

require_once plugin_dir_path(__FILE__) . 'Preferences_Controller.php';
new Preferences_Controller($auth_helper);

require_once plugin_dir_path(__FILE__) . 'License_Controller.php';
new License_Controller($auth_helper);
