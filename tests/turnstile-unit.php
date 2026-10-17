<?php
// Standalone, no WordPress database, email, real keys or network requests.
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
$options = array( 'ftuy_turnstile_enabled' => false, 'ftuy_registration_enabled' => true );
$transients = array(); $calls = array(); $lookups = 0; $checks = 0;
class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message, $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_option( $key, $default = false ) { global $options; return $options[$key] ?? $default; }
function wp_get_environment_type() { return 'production'; }
function home_url( $path = '' ) { return 'https://foodtruckuruguay.com' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function wp_remote_post( $url, $args ) { global $calls, $response; $calls[] = array( $url, $args ); return $response; }
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function sanitize_text_field( $text ) { return strip_tags( $text ); }
function get_transient( $key ) { global $transients; return $transients[$key] ?? false; }
function set_transient( $key, $value, $ttl ) { global $transients; $transients[$key] = $value; }
function wp_salt( $scheme ) { return 'fixture-only-salt-' . $scheme; }
function get_user_by( $field, $value ) { global $lookups; $lookups++; return false; }
function register_meta( ...$args ) {}
function add_filter( ...$args ) {}
function add_action( $hook, $callback, ...$args ) { global $hooks; $hooks[$hook][] = $callback; }
function register_rest_route( $namespace, $route, $args ) { global $routes; $routes[$route] = $args['callback']; }
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $data, $status ) { $this->data = $data; $this->status = $status; }
}
require __DIR__ . '/../plugins/foodtrucks-uy-core/includes/class-ftuy-turnstile.php';
require __DIR__ . '/../plugins/foodtrucks-uy-core/includes/class-ftuy-accounts.php';
$assert = function ( $ok, $label ) use ( &$checks ) { if ( ! $ok ) { throw new RuntimeException( $label ); } $checks++; };
$assert( FTUY_Turnstile::verify( 'register', '' ) === true, 'Disabled rollout preserves old clients.' );
$options['ftuy_turnstile_enabled'] = true;
$assert( FTUY_Turnstile::verify( 'register', '' )->code === 'turnstile_config', 'Enabled without keys fails closed.' );
define( 'FTUY_TURNSTILE_SITE_KEY', 'fixture-public-key' );
define( 'FTUY_TURNSTILE_SECRET_KEY', 'fixture-secret-not-real' );
foreach ( array( '', ' ', array( 'token' ), str_repeat( 'x', 2049 ) ) as $token ) {
    $assert( FTUY_Turnstile::verify( 'register', $token )->code === 'turnstile_required', 'Missing/malformed token rejected.' );
}
$assert( count( $calls ) === 0, 'Invalid input never contacts Cloudflare.' );
foreach ( array( 'register', 'forgot-password', 'reactivate' ) as $action ) {
    $transients = array();
    $result = FTUY_Accounts::request( $action, 'fixture@example.invalid', 'Fixture', '' );
    $assert( $result->code === 'turnstile_required' && $lookups === 0, 'No user lookup/creation/mail without challenge: ' . $action );
    $assert( $result->data['continue_url'] === FTUY_Turnstile::page_url( $action ), 'Native clients get a web continuation.' );
}
$response = new WP_Error( 'timeout', 'Fixture' );
$assert( FTUY_Turnstile::verify( 'register', 'token' )->code === 'turnstile_unavailable', 'Network failure closed.' );
$response = array( 'status' => 503, 'body' => '{}' );
$assert( FTUY_Turnstile::verify( 'register', 'token' )->data['status'] === 503, 'Provider unavailable closed.' );
foreach ( array(
    'invalid-json',
    json_encode( array( 'success' => false, 'error-codes' => array( 'timeout-or-duplicate' ) ) ),
    json_encode( array( 'success' => 'true', 'hostname' => 'foodtruckuruguay.com', 'action' => 'ftuy_register' ) ),
    json_encode( array( 'success' => true, 'hostname' => 'evil.example', 'action' => 'ftuy_register' ) ),
    json_encode( array( 'success' => true, 'hostname' => 'foodtruckuruguay.com', 'action' => 'ftuy_reactivate' ) ),
    json_encode( array( 'success' => true ) ),
) as $body ) {
    $response = array( 'status' => 200, 'body' => $body );
    $assert( FTUY_Turnstile::verify( 'register', 'token' )->code === 'turnstile_required', 'Invalid/expired/replayed/cross-host/cross-action response rejected.' );
}
foreach ( array( 'register', 'forgot-password', 'reactivate' ) as $action ) {
    $response = array( 'status' => 200, 'body' => json_encode( array( 'success' => true, 'hostname' => 'foodtruckuruguay.com', 'action' => FTUY_Turnstile::action( $action ) ) ) );
    $assert( FTUY_Turnstile::verify( $action, 'valid-fixture-token' ) === true, 'Matching action/hostname accepted.' );
}
$last = end( $calls );
$assert( $last[0] === 'https://challenges.cloudflare.com/turnstile/v0/siteverify' && $last[1]['redirection'] === 0, 'HTTPS fixed endpoint and no redirect.' );
$assert( array_keys( $last[1]['body'] ) === array( 'secret', 'response' ), 'No account data sent to provider.' );
$transients = array(); $lookups = 0;
$assert( FTUY_Accounts::request( 'reactivate', 'fixture@example.invalid', '', 'valid-fixture-token' ) === true && $lookups === 1, 'Verified request reaches normal account flow.' );
ob_start(); FTUY_Turnstile::widget( 'register' ); $html = ob_get_clean();
$assert( strpos( $html, 'data-action="ftuy_register"' ) !== false && strpos( $html, 'fixture-public-key' ) !== false, 'Form widget has public key and action.' );
$assert( strpos( $html, 'fixture-secret-not-real' ) === false, 'Secret never rendered.' );
ob_start(); FTUY_Turnstile::widget( 'login' ); FTUY_Turnstile::widget( 'reset' ); $html = ob_get_clean();
$assert( $html === '', 'Login and setting password unchanged.' );
$accounts = file_get_contents( __DIR__ . '/../plugins/foodtrucks-uy-core/includes/class-ftuy-accounts.php' );
$assert( strpos( $accounts, '$request[\'turnstile_token\']' ) !== false && strpos( $accounts, '$input[\'cf-turnstile-response\']' ) !== false, 'REST and web forward tokens to shared gate.' );
// Exercise the registered callbacks, not just their source strings.
$hooks = array(); $routes = array(); FTUY_Accounts::init();
foreach ( $hooks['rest_api_init'] as $callback ) { $callback(); }
foreach ( array( 'register', 'forgot-password', 'reactivate' ) as $action ) {
    $transients = array(); $lookups = 0;
    $_SERVER['HTTP_USER_AGENT'] = 'Foodtrucks Android';
    $result = $routes['/accounts/' . $action]( array( 'email' => 'fixture@example.invalid', 'name' => 'Fixture', 'device' => 'ios', 'platform' => 'android' ) );
    $assert( is_wp_error( $result ) && $result->code === 'turnstile_required' && $lookups === 0, 'REST rejects tokenless requests, including forged app metadata: ' . $action );
}
$transients = array();
$response = array( 'status' => 200, 'body' => json_encode( array( 'success' => true, 'hostname' => 'foodtruckuruguay.com', 'action' => 'ftuy_forgot_password' ) ) );
$result = $routes['/accounts/forgot-password']( array( 'email' => 'fixture@example.invalid', 'turnstile_token' => 'valid-fixture-token' ) );
$assert( $result instanceof WP_REST_Response && $result->status === 202, 'REST verified recovery keeps generic 202 response.' );
echo "OK: $checks Turnstile checks, no network/database/mail.\n";
