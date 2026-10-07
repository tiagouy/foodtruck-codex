<?php
defined( 'ABSPATH' ) || exit;

/** Restricted dump reader: extracts literal INSERT tuples, never executes SQL. */
class FTUY_Legacy_SQL {
    public static function read( $path, $wanted ) {
        if ( ! is_string( $path ) || ! is_file( $path ) || ! is_readable( $path ) || filesize( $path ) > 64 * MB_IN_BYTES ) { throw new RuntimeException( 'SQL no disponible o mayor a 64 MB.' ); }
        $sql = file_get_contents( $path );
        if ( $sql === false || ! preg_match( '//u', $sql ) ) { throw new RuntimeException( 'El SQL debe estar codificado en UTF-8.' ); }
        $result = array_fill_keys( array_keys( $wanted ), array() );
        preg_match_all( '/^INSERT INTO `([^`]+)`\s*\(([^\n]+)\) VALUES\s*/m', $sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
        foreach ( $matches as $match ) {
            $table = $match[1][0]; if ( ! isset( $wanted[$table] ) ) { continue; }
            preg_match_all( '/`([^`]+)`/', $match[2][0], $columns ); $columns = $columns[1];
            if ( count( $columns ) !== count( array_unique( $columns ) ) || array_diff( $wanted[$table], $columns ) ) { throw new RuntimeException( 'Columnas incompatibles en ' . $table ); }
            $i = $match[0][1] + strlen( $match[0][0] ); $length = strlen( $sql ); $ended = false;
            while ( $i < $length ) {
                while ( $i < $length && ( ctype_space( $sql[$i] ) || $sql[$i] === ',' ) ) { $i++; }
                if ( $i < $length && $sql[$i] === ';' ) { $ended = true; break; }
                if ( $i >= $length || $sql[$i] !== '(' ) { throw new RuntimeException( 'Fila inválida en ' . $table ); }
                $i++; $values = array();
                while ( $i < $length ) {
                    while ( $i < $length && ctype_space( $sql[$i] ) ) { $i++; } $value = '';
                    if ( $i < $length && $sql[$i] === "'" ) {
                        $i++; $closed = false;
                        while ( $i < $length ) {
                            $c = $sql[$i++];
                            if ( $c === '\\' ) {
                                if ( $i >= $length ) { break; }
                                $escape = $sql[$i++]; $map = array( '0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'Z' => "\x1a" );
                                $value .= $map[$escape] ?? ( in_array( $escape, array( '%', '_' ), true ) ? '\\' . $escape : $escape );
                            } elseif ( $c === "'" ) {
                                if ( $i < $length && $sql[$i] === "'" ) { $value .= "'"; $i++; } else { $closed = true; break; }
                            } else { $value .= $c; }
                        }
                        if ( ! $closed ) { throw new RuntimeException( 'Texto sin cerrar en ' . $table ); }
                    } else {
                        while ( $i < $length && $sql[$i] !== ',' && $sql[$i] !== ')' ) { $value .= $sql[$i++]; }
                        $value = trim( $value );
                        if ( strtoupper( $value ) === 'NULL' ) { $value = null; }
                        elseif ( ! preg_match( '/^-?\d+(?:\.\d+)?$/D', $value ) ) { throw new RuntimeException( 'Valor no literal en ' . $table ); }
                    }
                    $values[] = $value; while ( $i < $length && ctype_space( $sql[$i] ) ) { $i++; }
                    if ( $i < $length && $sql[$i] === ',' ) { $i++; continue; }
                    if ( $i >= $length || $sql[$i] !== ')' ) { throw new RuntimeException( 'Separador inválido en ' . $table ); }
                    $i++; break;
                }
                if ( count( $columns ) !== count( $values ) ) { throw new RuntimeException( 'Cantidad de columnas inválida en ' . $table ); }
                $row = array_combine( $columns, $values );
                // Passwords, push tokens, Facebook IDs and unrelated fields never enter the plan.
                $result[$table][] = array_intersect_key( $row, array_flip( $wanted[$table] ) );
            }
            if ( ! $ended ) { throw new RuntimeException( 'INSERT incompleto en ' . $table ); }
        }
        foreach ( $result as $table => $rows ) { if ( ! $rows ) { throw new RuntimeException( 'No hay filas de ' . $table . ' en este snapshot.' ); } }
        return $result;
    }
}
