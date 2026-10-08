<?php
defined( 'ABSPATH' ) || exit;

/** Authenticated proxy; keys stay on the server. Local development can use the existing web key. */
class FTUY_App_Places {
    public static function init() {
        add_action( 'rest_api_init', function () {
            foreach ( array( 'autocomplete', 'details' ) as $action ) {
                register_rest_route( 'foodtrucks-uy/v1', '/places/' . $action, array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, $action ) ) );
            }
        } );
        add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
            if ( strpos( $request->get_route(), '/foodtrucks-uy/v1/places/' ) === 0 ) { $response->header( 'Cache-Control', 'no-store, private' ); }
            return $response;
        }, 10, 3 );
    }
    private static function key() {
        if ( defined( 'FTUY_GOOGLE_PLACES_SERVER_KEY' ) ) { return (string) FTUY_GOOGLE_PLACES_SERVER_KEY; }
        // Verified against Places New locally. Never silently reuse a public web key in production.
        if ( ! FTUY_Accounts::local() ) { return ''; }
        $legacy = get_option( 'option_tree', array() );
        return (string) ( get_option( 'ftuy_google_maps_key', '' ) ?: ( is_array( $legacy ) ? ( $legacy['googlemapapi'] ?? '' ) : '' ) );
    }
    private static function context( $request ) {
        $auth = FTUY_App_Sessions::authenticate( $request );
        if ( is_wp_error( $auth ) ) { return $auth; }
        if ( ! self::key() ) { return self::error(); }
        $session = $request['session_id'];
        if ( ! is_string( $session ) || ! preg_match( '/^[a-zA-Z0-9_-]{20,80}$/D', $session ) ) { return new WP_Error( 'places_session', 'Volvé a buscar la dirección.', array( 'status' => 400 ) ); }
        $token = substr( hash_hmac( 'sha256', $auth['user']->ID . ':' . $session, wp_salt( 'auth' ) ), 0, 32 );
        if ( FTUY_Accounts::limited( 'places-user-' . $auth['user']->ID, 120, HOUR_IN_SECONDS ) ) { return new WP_Error( 'places_rate', 'Hubo varias búsquedas. Podés completar la dirección manualmente.', array( 'status' => 429 ) ); }
        return array( 'token' => $token, 'key' => 'ftuy_places_' . $token );
    }
    private static function error() { return new WP_Error( 'places_unavailable', 'El buscador de direcciones no está disponible. Podés completar la dirección manualmente.', array( 'status' => 503 ) ); }
    private static function google( $path, $method, $mask, $body = null ) {
        $args = array( 'method' => $method, 'timeout' => 10, 'redirection' => 0, 'limit_response_size' => 65536, 'headers' => array( 'Content-Type' => 'application/json', 'X-Goog-Api-Key' => self::key(), 'X-Goog-FieldMask' => $mask ) );
        if ( $body !== null ) { $args['body'] = wp_json_encode( $body ); }
        $response = wp_remote_request( 'https://places.googleapis.com/v1/' . $path, $args );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) { return self::error(); }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $data ) ? $data : self::error();
    }
    public static function autocomplete( $request ) {
        $context = self::context( $request ); if ( is_wp_error( $context ) ) { return $context; }
        $input = $request['input'];
        if ( ! is_string( $input ) || strlen( $input ) > 240 || strlen( trim( $input ) ) < 3 ) { return new WP_Error( 'places_input', 'Escribí al menos tres letras para buscar.', array( 'status' => 400 ) ); }
        $data = self::google( 'places:autocomplete', 'POST', 'suggestions.placePrediction.placeId,suggestions.placePrediction.text.text', array( 'input' => trim( $input ), 'languageCode' => 'es', 'regionCode' => 'uy', 'includedRegionCodes' => array( 'uy' ), 'sessionToken' => $context['token'] ) );
        if ( is_wp_error( $data ) ) { return $data; }
        $items = array();
        foreach ( array_slice( (array) ( $data['suggestions'] ?? array() ), 0, 5 ) as $row ) {
            $p = $row['placePrediction'] ?? array();
            if ( is_string( $p['placeId'] ?? null ) && preg_match( '/^[a-zA-Z0-9_-]{1,255}$/D', $p['placeId'] ) && is_string( $p['text']['text'] ?? null ) ) { $items[] = array( 'id' => $p['placeId'], 'label' => sanitize_text_field( $p['text']['text'] ) ); }
        }
        // Only place IDs, not predictions or coordinates, are held briefly for this search.
        set_transient( $context['key'], array_column( $items, 'id' ), 5 * MINUTE_IN_SECONDS );
        return new WP_REST_Response( array( 'suggestions' => $items ) );
    }
    public static function details( $request ) {
        $context = self::context( $request ); if ( is_wp_error( $context ) ) { return $context; }
        $id = $request['place_id']; $allowed = get_transient( $context['key'] );
        if ( ! is_string( $id ) || ! is_array( $allowed ) || ! in_array( $id, $allowed, true ) ) { return new WP_Error( 'places_selection', 'Seleccioná una sugerencia reciente del buscador.', array( 'status' => 400 ) ); }
        $path = 'places/' . rawurlencode( $id ) . '?' . http_build_query( array( 'languageCode' => 'es', 'regionCode' => 'uy', 'sessionToken' => $context['token'] ) );
        $data = self::google( $path, 'GET', 'id,formattedAddress,location' );
        if ( is_wp_error( $data ) ) { return $data; }
        $lat = $data['location']['latitude'] ?? null; $lng = $data['location']['longitude'] ?? null;
        if ( ! is_string( $data['formattedAddress'] ?? null ) || ! is_numeric( $lat ) || ! is_numeric( $lng ) || abs( $lat ) > 90 || abs( $lng ) > 180 ) { return self::error(); }
        delete_transient( $context['key'] );
        return new WP_REST_Response( array( 'address' => sanitize_text_field( $data['formattedAddress'] ), 'latitude' => (float) $lat, 'longitude' => (float) $lng ) );
    }
}
