<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Form {
    public static function assets() {
        wp_enqueue_style( 'ftuy-event-form', FTUY_URL . 'assets/event-form.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        wp_print_styles( 'ftuy-event-form' );
        $legacy = get_option( 'option_tree', array() );
        $key = get_option( 'ftuy_google_maps_key', '' ) ?: ( is_array( $legacy ) ? ( $legacy['googlemapapi'] ?? '' ) : '' );
        wp_enqueue_script( 'ftuy-event-form', FTUY_URL . 'assets/event-form.js', array(), FOODTRUCKS_UY_CORE_VERSION, true );
        wp_localize_script( 'ftuy-event-form', 'ftuyForm', array( 'googleKey' => $key, 'departments' => FTUY_Events::departments() ) );
    }
    public static function hours( $event ) {
        $rows = json_decode( $event['schedule_json'] ?? '[]', true ) ?: array();
        if ( $rows ) {
            foreach ( $rows as $row ) { echo '<div>' . esc_html( date_i18n( 'd/m', strtotime( $row['date'] ) ) . ': ' . ( $row['start'] ?: 'Apertura a confirmar' ) . ( $row['end'] ? ' — ' . $row['end'] : '' ) ) . '</div>'; }
        } elseif ( $event['start_time'] || $event['end_time'] ) {
            echo esc_html( ( $event['start_time'] ?: 'Inicio no informado' ) . ( $event['end_time'] ? ' — ' . $event['end_time'] : '' ) ) . ' hs';
        } else { echo 'A confirmar'; }
    }
}
