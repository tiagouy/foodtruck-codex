<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
if ( defined( 'FTUY_GOOGLE_PLACES_SERVER_KEY' ) ) { throw new RuntimeException( 'Ejecutar con configuración de prueba sin clave real.' ); }
define( 'FTUY_GOOGLE_PLACES_SERVER_KEY', 'fixture-not-a-real-key' );
$user = 0; $count = 0; $calls = array(); $session = wp_generate_uuid4();
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$mock = function ( $pre, $args, $url ) use ( &$calls ) {
    if ( strpos( $url, 'https://places.googleapis.com/v1/' ) !== 0 ) { return $pre; }
    $calls[] = array( 'url' => $url, 'args' => $args );
    $data = strpos( $url, 'places:autocomplete' ) !== false ? array( 'suggestions' => array( array( 'placePrediction' => array( 'placeId' => 'FixturePlace', 'text' => array( 'text' => 'Plaza de prueba, Montevideo' ) ) ) ) ) : array( 'id' => 'FixturePlace', 'formattedAddress' => 'Dirección de prueba, Montevideo', 'location' => array( 'latitude' => -34.9, 'longitude' => -56.2 ) );
    return array( 'headers' => array(), 'body' => wp_json_encode( $data ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
};
add_filter( 'pre_http_request', $mock, 10, 3 );
try {
    $user = wp_insert_user( array( 'user_login' => 'places-' . wp_generate_uuid4(), 'user_email' => wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
    if ( is_wp_error( $user ) ) { throw new RuntimeException( 'Fixture.' ); }
    $secret = bin2hex( random_bytes( 32 ) );
    update_user_meta( $user, FTUY_App_Sessions::PREFIX . hash( 'sha256', $secret ), array( 'expires' => time() + 3600, 'password_signature' => hash_hmac( 'sha256', get_userdata( $user )->user_pass, wp_salt( 'auth' ) ) ) );
    $r = new WP_REST_Request( 'POST' ); $r['session_id'] = $session; $r['input'] = 'Plaza';
    $assert( is_wp_error( FTUY_App_Places::autocomplete( $r ) ) && ! $calls, 'No permite consultas anónimas ni gasto sin token.' );
    $r->set_header( 'authorization', 'Bearer ' . $user . '.' . $secret );
    $r['input'] = 'ab'; $assert( is_wp_error( FTUY_App_Places::autocomplete( $r ) ) && ! $calls, 'Entrada corta no consulta Google.' );
    $r['input'] = 'Plaza'; $result = FTUY_App_Places::autocomplete( $r );
    $assert( $result instanceof WP_REST_Response && $result->get_data()['suggestions'][0]['id'] === 'FixturePlace', 'Predicciones válidas.' );
    $body = json_decode( $calls[0]['args']['body'], true );
    $assert( $body['includedRegionCodes'] === array( 'uy' ) && $body['languageCode'] === 'es', 'Uruguay y español.' );
    $assert( $calls[0]['args']['headers']['X-Goog-Api-Key'] === 'fixture-not-a-real-key' && strpos( wp_json_encode( $result->get_data() ), 'fixture-not-a-real-key' ) === false, 'Clave solo en servidor.' );
    $r['place_id'] = 'https://evil.invalid'; $assert( is_wp_error( FTUY_App_Places::details( $r ) ) && count( $calls ) === 1, 'No admite URL arbitraria ni lugar fuera de sugerencias.' );
    $r['place_id'] = 'FixturePlace'; $result = FTUY_App_Places::details( $r );
    $assert( $result instanceof WP_REST_Response && $result->get_data()['latitude'] === -34.9, 'Selecciona coordenadas.' );
    $assert( strpos( $calls[1]['url'], 'sessionToken=' . $body['sessionToken'] ) !== false && $calls[1]['args']['headers']['X-Goog-FieldMask'] === 'id,formattedAddress,location', 'Termina la misma sesión; campos mínimos.' );
    $assert( is_wp_error( FTUY_App_Places::details( $r ) ) && count( $calls ) === 2, 'Sesión consumida no duplica llamada de detalle.' );
    update_user_meta( $user, 'ftuy_account_status', 'email_pending' );
    $assert( is_wp_error( FTUY_App_Places::autocomplete( $r ) ), 'Cuenta pendiente no usa el proxy.' );
    echo "OK: $count comprobaciones de Places (respuestas simuladas; sin gasto real).\n";
} finally {
    remove_filter( 'pre_http_request', $mock, 10 );
    if ( $user && ! is_wp_error( $user ) ) {
        $hash = substr( hash_hmac( 'sha256', $user . ':' . $session, wp_salt( 'auth' ) ), 0, 32 ); delete_transient( 'ftuy_places_' . $hash );
        delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', 'places-user-' . $user, wp_salt( 'auth' ) ) );
        require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user );
    }
}
