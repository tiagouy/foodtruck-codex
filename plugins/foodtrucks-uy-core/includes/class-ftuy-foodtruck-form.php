<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Foodtruck_Form {
    public static function assets( $public = false ) {
        wp_enqueue_style( 'ftuy-truck-form', FTUY_URL . 'assets/foodtruck-form.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        wp_enqueue_script( 'ftuy-trucks', FTUY_URL . 'assets/foodtrucks.js', array(), FOODTRUCKS_UY_CORE_VERSION, true );
        wp_localize_script( 'ftuy-trucks', 'ftuyTruck', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'action' => $public ? 'ftuy_instagram_public_preview' : 'ftuy_instagram_preview', 'nonce' => wp_create_nonce( $public ? 'ftuy_instagram_public' : 'ftuy_instagram' ) ) );
    }
    public static function fields( $data = array(), $public = false ) {
        $data['images'] = FTUY_Foodtruck_Images::normalize( $data['images'] ?? array() );
        echo '<div class="ft-form-grid">';
        foreach ( array( 'name' => 'Nombre *', 'description' => 'Descripción *', 'food_offering' => 'Qué sirven *', 'department' => 'Departamento base *', 'locality' => 'Localidad base *', 'whatsapp' => 'WhatsApp (opcional)', 'instagram' => 'Instagram (opcional)' ) as $key => $label ) {
                echo '<label class="ft-field ft-field-' . esc_attr( $key ) . '"><span>' . esc_html( $label ) . '</span>';
            if ( in_array( $key, array( 'description', 'food_offering' ), true ) ) { echo '<textarea name="' . $key . '" rows="' . ( $key === 'description' ? 6 : 3 ) . '" required>' . esc_textarea( $data[$key] ?? '' ) . '</textarea>'; }
            elseif ( $key === 'department' ) { echo '<select name="department" required><option value="">Seleccioná un departamento</option>'; foreach ( FTUY_Events::departments() as $dep ) { echo '<option ' . selected( $data[$key] ?? '', $dep, false ) . '>' . esc_html( $dep ) . '</option>'; } echo '</select>'; }
            else { echo '<input name="' . $key . '" value="' . esc_attr( $data[$key] ?? '' ) . '"' . ( in_array( $key, array( 'name', 'locality' ), true ) ? ' required' : '' ) . '>'; }
            echo '</label>';
        }
        echo '</div><p><button type="button" class="button" data-instagram-lookup>Proponer nombre y logo desde Instagram</button></p><p role="status" data-instagram-status>Consulta opcional de datos públicos: puede fallar. Siempre podés cargar nombre y logo manualmente.</p><div data-instagram-result></div><h2>Rubros gastronómicos *</h2>';
        foreach ( FTUY_Foodtrucks::cuisines() as $c ) { echo '<label style="display:inline-block;margin:8px 20px 8px 0"><input type="checkbox" name="cuisine_ids[]" value="' . (int) $c['id'] . '" ' . checked( in_array( (int) $c['id'], array_map( 'intval', $data['cuisine_ids'] ?? array() ), true ), true, false ) . '> ' . esc_html( $c['name'] ) . '</label>'; }
        echo '<h2>Modalidades *</h2>';
        foreach ( array( 'serves_events' => 'Eventos', 'serves_private_events' => 'Catering / privados', 'has_fixed_location' => 'Punto fijo' ) as $key => $label ) { echo '<label style="margin-right:24px"><input type="checkbox" name="' . $key . '" value="1" ' . checked( ! empty( $data[$key] ), true, false ) . '> ' . esc_html( $label ) . '</label>'; }
        echo '<h2>Imágenes del foodtruck</h2><p>Logo: 500 × 500 px, hasta 120 KB. Foto del foodtruck: 900 × 900 px, hasta 300 KB. Al guardar recortamos al centro y comprimimos a JPEG, con fondo blanco y metadatos de 72 dpi.</p><p>Podés seleccionar JPG, PNG o WebP de hasta 5 MB para procesarlos; el archivo grande no se guarda. Para evitar pérdida de nitidez, elegí imágenes de al menos el tamaño final.</p>';
        foreach ( $data['images'] ?? array() as $image ) { echo '<div style="display:inline-block;margin:12px;vertical-align:top">' . ( $public ? FTUY_Foodtruck_Public::image( $image['attachment_id'], 'Imagen de la ficha', 'ft-truck-existing' ) : wp_get_attachment_image( $image['attachment_id'], 'thumbnail' ) ) . '<p><label><input type="checkbox" name="keep_images[]" checked value="' . esc_attr( $image['role'] . ':' . $image['attachment_id'] ) . '"> Conservar ' . esc_html( array( 'logo' => 'logo', 'truck_photo' => 'foto del foodtruck' )[$image['role']] ) . '</label></p></div>'; }
        foreach ( array( 'logo' => 'Logo (500 × 500 px)', 'truck_photo' => 'Foto del foodtruck (900 × 900 px)' ) as $key => $label ) { echo '<p><label>' . $label . ' <input type="file" name="truck_' . $key . '" accept="image/jpeg,image/png,image/webp"></label></p>'; }
    }
}
