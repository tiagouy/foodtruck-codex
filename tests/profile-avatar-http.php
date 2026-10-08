<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
require_once ABSPATH . 'wp-admin/includes/file.php';
$id = 0; $files = array(); $attachments = array(); $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $email = 'ftuy-avatar-' . wp_generate_uuid4() . '@example.invalid'; $pass = wp_generate_password( 30 );
    $id = wp_insert_user( array( 'user_login' => 'avatar-' . wp_generate_uuid4(), 'user_email' => $email, 'user_pass' => $pass, 'role' => 'subscriber', 'meta_input' => array( 'ftuy_account_status' => 'active' ) ) );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Fixture.' ); }
    $url = rest_url( 'foodtrucks-uy/v1/accounts/' );
    $response = wp_remote_post( $url . 'login', array( 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'email' => $email, 'password' => $pass ) ) ) );
    $data = json_decode( wp_remote_retrieve_body( $response ), true ); $token = $data['token'] ?? ''; $assert( $token !== '', 'Token de fixture.' );
    $source = wp_tempnam( 'ftuy-avatar-jpeg' ); $files[] = $source;
    $image = imagecreatetruecolor( 1000, 800 ); imagefill( $image, 0, 0, imagecolorallocate( $image, 10, 140, 180 ) ); imagejpeg( $image, $source ); imagedestroy( $image );
    $hash = hash_file( 'sha256', $source );
    $upload = function ( $file, $authorization = true ) use ( $url, $token ) {
        $ch = curl_init( $url . 'avatar' );
        curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45, CURLOPT_HTTPHEADER => $authorization ? array( 'Authorization: Bearer ' . $token ) : array(), CURLOPT_POST => true, CURLOPT_POSTFIELDS => array( 'photo' => new CURLFile( $file, 'image/jpeg', 'foto.jpg' ), 'user_id' => '1' ) ) );
        $body = curl_exec( $ch ); $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch );
        return array( $status, json_decode( $body, true ) );
    };
    list( $code, $data ) = $upload( $source );
    wp_cache_delete( $id, 'user_meta' ); $first = FTUY_Profile_Images::attachment( $id ); if ( $first ) { $attachments[] = $first; }
    $assert( $code === 200 && $data['user']['id'] === $id && strpos( $data['user']['avatar'], '/media/perfiles/' ) !== false, 'Avatar propio; ignora user_id enviado.' );
    $path = get_attached_file( $first ); $info = wp_getimagesize( $path );
    $assert( $info && $info[0] === 500 && $info[1] === 500 && filesize( $path ) <= 120 * 1024 && $info['mime'] === 'image/jpeg', 'Avatar optimizado.' );
    $assert( hash_file( 'sha256', $source ) === $hash, 'Fuente intacta.' );
    list( $code ) = $upload( $source );
    wp_cache_delete( $id, 'user_meta' ); $second = FTUY_Profile_Images::attachment( $id ); if ( $second ) { $attachments[] = $second; }
    $assert( $code === 200 && $second !== $first && get_post( $first ) && is_file( $path ), 'Reemplazo conserva medio anterior.' );
    $bad = wp_tempnam( 'ftuy-avatar-invalid' ); $files[] = $bad; file_put_contents( $bad, '<?php echo "not an image";' );
    list( $code ) = $upload( $bad ); wp_cache_delete( $id, 'user_meta' );
    $assert( $code === 400 && FTUY_Profile_Images::attachment( $id ) === $second, 'Archivo falso rechazado sin perder avatar.' );
    $large = wp_tempnam( 'ftuy-avatar-large' ); $files[] = $large; file_put_contents( $large, str_repeat( 'x', 5 * MB_IN_BYTES + 1 ) );
    list( $code ) = $upload( $large ); $assert( in_array( $code, array( 400, 413 ), true ), 'Mayor a 5 MB rechazado.' );
    list( $code ) = $upload( $source, false ); $assert( $code === 401, 'Subida sin sesión rechazada.' );
    echo "OK: $count comprobaciones HTTP de avatar.\n";
} finally {
    foreach ( array_unique( $attachments ) as $attachment ) { wp_delete_attachment( $attachment, true ); }
    foreach ( $files as $file ) { wp_delete_file( $file ); }
    if ( $id && ! is_wp_error( $id ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $id ); delete_option( 'ftuy_avatar_lock_' . $id );
        foreach ( array( 'avatar-user-' . $id, 'app-login-email-' . $email ) as $bucket ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) ) ); }
    }
}
