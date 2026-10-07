# Fotos web y enlaces compartidos — 0.12.0

## Rutas disponibles en local

- `/fotosusuarios/`: listado público paginado, doce fotos por página, solo publicaciones publicadas.
- `/fotosusuarios/?autor=ID_WORDPRESS`: fotos publicadas de una persona, sin exponer email ni IDs históricos de usuario.
- `/fotousuario/{slug}/`: detalle de foto con texto, autor, fecha, dirección y enlace de compartir.
- `/fotosusuarios/index.php` y listado sin barra: redirección al listado canónico.
- `/fotosusuarios/oferta.php?slug=...`: compatibilidad con el script antiguo; solo redirige al detalle si la publicación está publicada. Pendiente/despublicada/inexistente responde 404 sin redirigir hacia contenido.

Los slugs históricos válidos se conservan desde `legacy_slug`. Las publicaciones nuevas usan `p-{ID}`; editar su texto no cambia el enlace. Slugs históricos ambiguos no eligen arbitrariamente una foto. La API pública agrega `share_url`: la app debe compartir este enlace de publicación, no la URL física del archivo.

Estas páginas pertenecen al plugin, con plantilla y estilos propios, sin shortcode ni dependencia del tema Eventchamp. El menú común del plugin incorpora Fotos. La home y los menús del tema viejo no se alteraron.

## Visibilidad y privacidad

Detalle y listado consultan exclusivamente `published`. Despublicar hace desaparecer la foto de la API, del listado web, del listado por autor y de la URL compartida. Una URL no disponible responde HTTP 404, con mensaje genérico, sin foto, texto original, autor, URL del medio, metadatos Open Graph/Twitter ni motivo de moderación. No informa si el registro existe pero está pendiente/despublicado.

Páginas públicas y redirecciones llevan `no-store`; las 404 también incluyen `noindex`. Publicadas tienen canonical y vista previa Open Graph/Twitter. No se incluyen JavaScript de tema, enlaces de subida web ni formularios para crear publicaciones. Las fotos siguen subiéndose exclusivamente desde la app futura.

Se detectó y evitó el filtro de lazy-load de Eventchamp, que reemplazaba imágenes por SVG y esperaba scripts ausentes en las plantillas independientes. Las fotos y avatares propios se renderizan con URL real, dimensiones, srcset y carga nativa del navegador. Las imágenes conservan su proporción, sin recortar el contenido del archivo en la vista.

Despublicar no bloquea la URL física en `/media/` ni elimina copias descargadas/vistas previas anteriores. La acción de retirar imagen sigue pendiente. La nueva app deberá consultar/revalidar el estado al abrir un enlace; una caché offline no se invalida por sí sola.

## Android / iOS y despliegue

El fallback web está implementado y las rutas antiguas se conservan. **La apertura automática de la nueva app aún no está configurada:** hacen falta la identidad real de la app Android/iOS y sus certificados/entitlements. No se publicaron archivos de asociación con los identificadores de la app vieja ni se agregaron enlaces Android fingiendo que la integración actual ya funciona.

Antes de desplegar en el hosting antiguo, apartar de forma respaldada la carpeta física `fotosusuarios/` y sus scripts fuera de la raíz pública, y reemplazar la regla antigua de `.htaccess` que enviaba `/fotousuario/` a `oferta.php`. Si continúan sirviéndose por Apache, podrían saltarse las páginas del plugin y sus controles de moderación. No se borraron ni modificaron esos originales durante el desarrollo local. En el WordPress local no existen esas carpetas ni esa regla, y el front controller actual sirve las nuevas rutas sin necesitar cambios de permalinks.

Después configurar asociaciones HTTPS Android/iOS en el dominio real y probar con/sin app instalada. Migrar cada slug antiguo y verificar su autor/estado antes de retirar las APIs viejas.

## Pruebas y datos

- 25 comprobaciones HTTP de páginas: listado/detalle/autor, canonical, aliases antiguos, slugs estables, estados, ausencia de metadata/foto en 404, filtros manipulados, share_url y ausencia de placeholders del tema.
- 31 de regresión del modelo/API y 12 de regresión HTTP de moderación.
- Inspección visual del listado, detalle y 404; detalle móvil a 375 px, sin desbordamiento horizontal, imagen cargada realmente. Viewport temporal restaurado.
- Copia visual temporal de una foto histórica, cuenta ficticia y publicaciones de QA retiradas; original sin modificar. Captura de la página despublicada en `/private/tmp/foodtrucks-foto-despublicada-0.12.0.png`.

El listado final está vacío: las 51 publicaciones antiguas todavía no se importaron. Sigue pendiente importar los 3.655 usuarios restantes y después esas publicaciones, preservando datos originales y rutas. Esta versión no realiza importaciones reales.
