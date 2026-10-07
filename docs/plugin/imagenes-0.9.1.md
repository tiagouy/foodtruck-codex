# Imágenes y respaldos — 0.9.1

Las nuevas imágenes de eventos y foodtrucks usan `media/` en la raíz de la instalación, fuera de `wp-content` y del plugin. No se cambia la configuración global de subidas de WordPress: otros plugins y la biblioteca general siguen usando sus rutas existentes.

```text
media/
├── eventos/{UUID de imagen}/
├── foodtrucks/{UUID de imagen}/
├── perfiles/{UUID de imagen}/
└── publicaciones/{UUID de imagen}/
```

Cada adjunto tiene un identificador UUID independiente del evento/usuario/foodtruck: las propuestas se cargan antes de existir el registro definitivo, y una propuesta puede reemplazarse o rechazarse. La relación con cada entidad se conserva en la BD, no se deduce del nombre de carpeta. Original procesado y miniaturas quedan juntos. Los nombres originales son saneados por WordPress.

En local: `/Users/Santi/CLIENTES/_localhost/foodtruck/media/`, URL `http://localhost:8888/foodtruck/media/`. El backend valida las subidas; la app enviará archivos a la API, nunca escribirá directamente en el sistema de archivos. Perfiles y publicaciones tienen almacenamiento preparado, pero su importación y los endpoints de subida de la app todavía no están implementados.

Los archivos siguen registrados como adjuntos WordPress. Metadata privada `_ftuy_media_path` conserva una ruta relativa portable, y `_ftuy_media_category` su categoría. Los filtros resuelven archivo, URL y srcset según el entorno. Las miniaturas continúan funcionando. Al eliminar definitivamente un adjunto propio se borran sus archivos registrados, restringidos a su directorio; no se borran medios históricos al reemplazar una imagen.

## Configuración fuera de la instalación

Opcionalmente definir en `wp-config.php`, antes de cargar WordPress:

```php
define( 'FTUY_MEDIA_DIR', '/ruta/absoluta/foodtruck-media' );
define( 'FTUY_MEDIA_URL', 'https://dominio.example/media' );
```

La carpeta debe ser escribible por PHP y el servidor debe servirla en esa URL. Cambiar estas constantes NO mueve archivos: copiar la carpeta y comprobar permisos/URL antes de activar la nueva ubicación. Los dos valores se configuran juntos. En Apache 2.4 se crea `.htaccess` que bloquea PHP/PHTML/PHAR, e índices vacíos para evitar listados. No se sobrescribe configuración existente. En Nginx u otro servidor hay que configurar explícitamente el bloqueo de ejecución y listados; comprobarlo antes de producción. Solo se admiten los formatos raster validados por cada formulario, no SVG/HTML/scripts.

## Respaldos y migración

- Base de datos completa: usuarios, relaciones, adjuntos, revisiones y rutas.
- `media/`: nuevas imágenes y miniaturas.
- `wp-content/uploads/`: todavía contiene eventos y otros medios históricos. No excluirlo del respaldo ni borrarlo.
- Fuentes antiguas `api2/users/uploads/` y `api2/offers/uploads/`: conservar mientras la migración no esté aplicada y verificada.
- Código del plugin en Git; imágenes y backups de BD fuera de Git.

No se han movido archivos reales existentes ni implementado todavía redirecciones de URLs antiguas o `/fotousuarios/`. Antes de retirarlas hay que preparar un mapa de compatibilidad. Los filtros se aplican solo durante subidas del plugin, con restauración incluso si ocurre un error.

Verificación local: 27 comprobaciones de almacenamiento en las cuatro categorías (URL HTTP 200, miniaturas, srcset, aislamiento y eliminación), 17 comprobaciones de compresión de foodtrucks y 74 HTTP de formularios, incluida carga real de imagen. Archivos temporales eliminados; medios reales conservados.
