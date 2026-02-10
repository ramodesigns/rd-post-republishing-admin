# WordPress Plugin Architecture Guide — Controller / Helper / Service Pattern

Use this document as a blueprint when scaffolding a new WordPress REST API plugin. It describes the exact folder structure, file conventions, bootstrapping order, endpoint security model, and coding patterns to follow. When given this guide alongside a blank WordPress Plugin Boilerplate, reproduce this structure exactly, substituting the plugin-specific names and domain logic.

## 1. Directory Structure

```
rd-{plugin-slug}/
├── rd-{plugin-slug}.php          # Main plugin bootstrap file
├── uninstall.php                 # Cleanup on uninstall (drop tables, etc.)
├── composer.json
├── includes/
│   ├── class-rd-{plugin-slug}.php              # Core plugin class (loader, hooks, admin/public registration)
│   ├── class-rd-{plugin-slug}-loader.php       # Hook orchestration (actions & filters registry)
│   ├── class-rd-{plugin-slug}-activator.php    # Runs on plugin activation (create DB tables)
│   ├── class-rd-{plugin-slug}-deactivator.php  # Runs on plugin deactivation
│   └── class-rd-{plugin-slug}-i18n.php         # Internationalisation
├── setup/
│   ├── index.php                 # Requires & instantiates Init_Setup
│   └── Init_Setup.php            # Database table definitions, create/drop helpers
├── src/
│   ├── helpers/
│   │   ├── index.php             # Requires all helper files
│   │   ├── Authorisation_Helper.php
│   │   └── {Feature}_Helper.php  # One per domain that needs reusable logic
│   ├── services/
│   │   ├── index.php             # Requires & instantiates all services + wires shared deps
│   │   ├── Preferences_Service.php
│   │   └── {Feature}_Service.php # One per domain
│   └── controllers/
│       ├── index.php             # Requires & instantiates all controllers, injecting Authorisation_Helper
│       ├── Preferences_Controller.php
│       └── {Feature}_Controller.php  # One per REST resource
├── admin/                        # WP admin area (styles, scripts, menus)
├── public/                       # Public-facing assets
└── languages/                    # Translation files
```

## 2. Bootstrapping Order (Main Plugin File)

The main plugin file (`rd-{plugin-slug}.php`) loads everything in this exact order:

```php
// 1. WordPress direct-access guard
if ( ! defined( 'WPINC' ) ) { die; }

// 2. Version constant
define( 'RD_{PLUGIN_CONST}_VERSION', '1.0.0' );

// 3. Activation / Deactivation hooks
register_activation_hook( __FILE__, 'activate_rd_{plugin_slug}' );
register_deactivation_hook( __FILE__, 'deactivate_rd_{plugin_slug}' );

// 4. Core class (includes/class-rd-{plugin-slug}.php)
require plugin_dir_path( __FILE__ ) . 'includes/class-rd-{plugin-slug}.php';

// 5. Setup (database tables, static config)
require plugin_dir_path( __FILE__ ) . 'setup/index.php';

// 6. Helpers FIRST (they have no dependencies)
require plugin_dir_path( __FILE__ ) . 'src/helpers/index.php';

// 7. Services SECOND (may depend on helpers)
require plugin_dir_path( __FILE__ ) . 'src/services/index.php';

// 8. Controllers LAST (depend on both helpers and services)
require plugin_dir_path( __FILE__ ) . 'src/controllers/index.php';

// 9. Run
function run_rd_{plugin_slug}() {
    $plugin = new Rd_{Plugin_Class}();
    $plugin->run();
}
run_rd_{plugin_slug}();
```

**Key rule:** Helpers -> Services -> Controllers. Always load in this dependency order.

## 3. The Three Layers

### 3a. Helpers (`src/helpers/`)

**Purpose:** Stateless utility/validation logic reusable across services and controllers.

**Conventions:**
- Class name: `{Feature}_Helper` (e.g. `Authorisation_Helper`, `Calculation_Helper`)
- Contains pure functions for validation, formatting, token generation, etc.
- May receive a Service via constructor injection if it needs to look up config values
- Never interacts with the database directly
- Never registers REST routes

`Authorisation_Helper` is **mandatory** in every plugin. It provides:
- `is_debug_authorized()` — checks if public/debug access is enabled (via a stored `debug_timestamp` preference or a secret Bearer token)
- `generate_token()` — creates a secure random token

```php
class Authorisation_Helper {
    private $preferences_service;

    public function __construct($preferences_service) {
        $this->preferences_service = $preferences_service;
    }

    public function is_debug_authorized() {
        // 1. Check for secret token (query param ?token= or Authorization: Bearer header)
        // 2. Fall back to checking a debug_timestamp preference (epoch > current time)
        // Returns true/false
    }

    public function generate_token() {
        // Returns sha256 hash of domain + microtime + random_bytes
    }
}
```

### 3b. Services (`src/services/`)

**Purpose:** All business logic and database interaction. Services are the **only** layer that talks to `$wpdb` or calls WordPress data functions (`WP_Query`, `wp_update_post`, etc.).

**Conventions:**
- Class name: `{Feature}_Service` (e.g. `Preferences_Service`, `Republish_Service`)
- Constructor may accept other services or helpers via DI, with fallback to `new` if not provided:

```php
public function __construct($preferences_service = null, $helper = null) {
    $this->preferences_service = $preferences_service ?: new Preferences_Service();
    $this->helper = $helper ?: new Calculation_Helper();
}
```

- Uses `Init_Setup::get_table_name()` for custom table names (never hardcodes table names)
- Returns plain arrays or `WP_Error` — never returns `WP_REST_Response` (that is the controller's job)
- Validates input by delegating to the appropriate Helper class
- Uses `$wpdb->prepare()` for all database queries

`Preferences_Service` is **mandatory** in every plugin. It manages a key-value preferences table and provides:
- `get_all_preferences()`
- `get_preference_by_key($key)`
- `update_preferences($preferences)` — bulk upsert with validation
- `delete_preference_by_key($key)`

### 3c. Controllers (`src/controllers/`)

**Purpose:** REST API route registration, request/response handling, and permission checks. Controllers are the **only** layer that registers routes and returns `WP_REST_Response`.

**Conventions:**
- Class name: `{Feature}_Controller` (e.g. `Republish_Controller`, `Calculation_Controller`)
- Receives `Authorisation_Helper` via constructor injection
- Creates its own service instance in the constructor
- Registers routes in `register_rest_routes()` hooked to `rest_api_init`
- Every controller defines two permission callbacks (see Section 4)
- Handler methods extract params from `WP_REST_Request`, delegate to the service, and wrap the result in `WP_REST_Response`
- All handler methods wrapped in try/catch returning `WP_Error` on exception

Controller constructor pattern:

```php
class {Feature}_Controller {
    private $namespace = '{pluginnamespace}/v1/{feature}';
    private $service;
    private $authorisation_helper;

    public function __construct($authorisation_helper) {
        $this->authorisation_helper = $authorisation_helper;
        $this->service = new {Feature}_Service();
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
}
```

## 4. Dual Endpoint Security Model (`/endpoint` and `/endpointpublic`)

Every REST route is registered **twice** — once as a protected endpoint and once as a public/debug endpoint.

### Protected endpoint (`/endpoint`)
- Uses `check_authentication` as the `permission_callback`
- Requires the user to be logged in (WordPress Application Passwords or cookie auth)
- Checks `current_user_can('edit_posts')` (or a more appropriate capability)
- Returns 401 if not logged in, 403 if insufficient permissions

```php
register_rest_route($this->namespace, '/execute', array(
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => array($this, 'handle_request'),
    'permission_callback' => array($this, 'check_authentication')
));
```

### Public/debug endpoint (`/endpointpublic`)
- Uses `check_debug_authorization` as the `permission_callback`
- Delegates to `Authorisation_Helper::is_debug_authorized()`
- Accessible without WordPress login when debug mode is enabled or a valid secret token is provided
- Returns 403 if debug mode is not active and no valid token
- Same callback handler as the protected version — only the permission check differs

```php
register_rest_route($this->namespace, '/executepublic', array(
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => array($this, 'handle_request'),
    'permission_callback' => array($this, 'check_debug_authorization')
));
```

### Permission callback implementations (copy into every controller):

```php
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
```

### Naming convention

| Protected Route | Public Route |
|----------------|-------------|
| `/execute` | `/executepublic` |
| `/retrieve` | `/retrievepublic` |
| `/update` | `/updatepublic` |
| `/calculate` | `/calculatepublic` |
| `/operations` | `/operationspublic` |

The pattern is always: `/{action}` (secure) and `/{action}public` (debug/testing).

## 5. Setup & Database Layer (`setup/`)

`Init_Setup` is a static utility class that owns all custom database table definitions.

**Conventions:**
- Table name constants: `const TABLE_NAME_PREF = 'rd_{prefix}_pref';`
- Static methods: `get_table_name()`, `create_{table}_table()`, `drop_{table}_table()`
- Always uses `$wpdb->prefix` for table names
- Uses `dbDelta()` for safe create/upgrade operations
- Called by the Activator on activation and by `uninstall.php` for cleanup

```php
class Init_Setup {
    const TABLE_NAME_PREF = 'rd_{prefix}_pref';

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME_PREF;
    }

    public static function create_preferences_table() {
        global $wpdb;
        $table_name = self::get_table_name();
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

    public static function drop_preferences_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
```

## 6. Index Files (Wiring / Dependency Injection)

Each `src/` subdirectory has an `index.php` that requires and instantiates its classes. This is where manual dependency injection happens.

### `src/helpers/index.php`

```php
require_once plugin_dir_path(__FILE__) . 'Authorisation_Helper.php';
require_once plugin_dir_path(__FILE__) . '{Feature}_Helper.php';
```

Helpers are only required here (not instantiated) — they are instantiated where needed.

### `src/services/index.php`

```php
require_once plugin_dir_path(__FILE__) . 'Preferences_Service.php';
new Preferences_Service();

require_once plugin_dir_path(__FILE__) . '{Feature}_Service.php';
new {Feature}_Service();

// Wire shared dependencies for services that need them
$preferences_service_shared = new Preferences_Service();
$authorisation_helper_shared = new Authorisation_Helper($preferences_service_shared);

require_once plugin_dir_path(__FILE__) . 'Authentication_Service.php';
new Authentication_Service($preferences_service_shared, $authorisation_helper_shared);
```

### `src/controllers/index.php`

```php
$preferences_service_for_auth = new Preferences_Service();
$auth_helper = new Authorisation_Helper($preferences_service_for_auth);

require_once plugin_dir_path(__FILE__) . 'Preferences_Controller.php';
new Preferences_Controller($auth_helper);

require_once plugin_dir_path(__FILE__) . '{Feature}_Controller.php';
new {Feature}_Controller($auth_helper);
```

All controllers receive the same `$auth_helper` instance.

## 7. REST API Namespace Convention

```
{pluginnamespace}/v1/{feature}
```

Example: `postmetadata/v1/preferences`, `postmetadata/v1/republish`, `postmetadata/v1/calculation`

Full URL: `https://yoursite.com/wp-json/{pluginnamespace}/v1/{feature}/{action}`

## 8. Standard Response Format

All endpoints return a consistent JSON shape:

**Success:**
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { },
    "timestamp": "2025-01-15 10:30:00"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Description of what went wrong",
    "errors": ["error detail 1"],
    "timestamp": "2025-01-15 10:30:00"
}
```

Always include `"timestamp": current_time('mysql')` in every response.

## 9. File Security

Every PHP file in `src/`, `setup/`, and `includes/` starts with:

```php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
```

## 10. Adding a New Feature (Checklist)

When adding a new REST API resource (e.g. "Invoices"):

1. **Helper** (if needed): Create `src/helpers/Invoice_Helper.php` with validation logic. Add its `require_once` to `src/helpers/index.php`.
2. **Service**: Create `src/services/Invoice_Service.php` with business logic and DB calls. Add its `require_once` and `new Invoice_Service()` to `src/services/index.php`.
3. **Controller**: Create `src/controllers/Invoice_Controller.php`:
   - Set `$namespace = '{pluginnamespace}/v1/invoice'`
   - Accept `Authorisation_Helper` in constructor
   - Instantiate `Invoice_Service` in constructor
   - Register both `/{action}` and `/{action}public` route pairs
   - Include both `check_authentication` and `check_debug_authorization` methods
   - Add its `require_once` and `new Invoice_Controller($auth_helper)` to `src/controllers/index.php`
4. **Database** (if needed): Add table constants and create/drop methods to `Init_Setup.php`. Call create in the Activator, drop in `uninstall.php`.
5. **HTTP Methods**: Use `WP_REST_Server::READABLE` (GET), `WP_REST_Server::CREATABLE` (POST), `WP_REST_Server::EDITABLE` (PUT/PATCH), `WP_REST_Server::DELETABLE` (DELETE).
6. **Arg validation**: Define `'args'` arrays on routes that accept input, specifying `required`, `type`, and `description` for each parameter.
