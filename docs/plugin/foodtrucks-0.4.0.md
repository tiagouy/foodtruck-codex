# Foodtrucks · operación 0.4.0

En WordPress: **Foodtrucks UY → Foodtrucks**. Capacidad específica `manage_ft_foodtrucks`, otorgada a administradores al instalar el esquema. Usuarios de comunidad no acceden a esta administración. No hay todavía formulario público de alta, reclamación ni notificaciones de foodtrucks: siguiente etapa tras validar la ficha.

## Tablas

Con el prefijo WordPress del entorno: `ft_foodtrucks`, `ft_cuisine_categories`, `ft_foodtruck_cuisines`, `ft_foodtruck_images`, `ft_foodtruck_reviews`. Esquema propio en `ftuy_foodtruck_schema=1`, sin renombrar tablas de los SQL antiguos ni alterar las de eventos.

## Moderación y medios

Guardar propuesta crea una revisión pendiente; no reemplaza una ficha pública. Aprobar aplica campos, rubros y medios dentro de una transacción. Correcciones/rechazo dejan intacta la versión publicada. Desmarcar «Conservar» quita el vínculo en la propuesta; no elimina archivos antiguos de la biblioteca.

Imágenes opcionales: un logo, una portada y fotos oficiales hasta diez medios totales, JPG/PNG/WebP ≤5 MB y ≤40 MP por archivo. No se reutiliza ni mezcla automáticamente la galería de publicaciones de usuarios. Campos mínimos y límites están en [definición](../07-foodtrucks.md).

WhatsApp uruguayo `09…` se normaliza a `5989…`; formatos internacionales se ingresan con código de país. No se interpreta un teléfono histórico como WhatsApp confirmado.

## Instagram

Ingresar perfil y pulsar «Proponer nombre y logo desde Instagram». Se consulta solo metadata de la página pública, sin cookies, credenciales, APIs privadas o elusión de restricciones. Si responde, muestra propuesta; pulsar «Usar nombre y foto como logo» confirma. Los campos siguen editables. Una carga manual de logo prevalece sobre la propuesta al guardar.

Esta consulta **no es una integración oficial estable de Meta**: puede dejar de funcionar, devolver página de login o bloquearse. Siempre ofrecer carga manual; no implica titularidad ni que la foto de perfil sea un logo. No se extrae ni publica automáticamente descripción, seguidores o imágenes de publicaciones.

El token de propuesta vence en diez minutos, está ligado a la cuenta y al perfil. El servidor conserva la URL de imagen; no acepta URLs de descarga arbitrarias del cliente. Solo HTTPS a CDN de Instagram/Facebook, sin redirecciones para el archivo; se valida tamaño y contenido y se copia a medios WordPress. No guardar enlaces temporales de CDN como logo permanente ni exponer tokens en API. Diez consultas por cuenta en diez minutos.

La prueba real `quechurrouy` devuelve nombre e imagen desde el formulario local. No se creó su ficha automáticamente. Una revisión sin logo importado por fallo de Instagram puede continuarse con archivo manual.

## Muestra

WP-CLI: `wp ftuy sample-foodtrucks` (solo localhost). Fuente exacta: cuatro posts históricos `speaker`: 76, 79, 2922, 2810. Robin, Shufa, Route, Scaronne, todos pendientes. La cuenta administradora es responsable provisional de carga, no titular verificado. Rubros, base/localidad y modalidades de la muestra son datos de ensayo a confirmar; WhatsApp queda vacío. Las fotos disponibles se proponen como portada, no como logo. No se importa el catálogo completo ni se sobrescriben muestras ya creadas.

## API

- `GET /wp-json/foodtrucks-uy/v1/foodtrucks`: `page`, `per_page` (1–50), `department`; items y total, encabezado X-WP-Total.
- `GET /wp-json/foodtrucks-uy/v1/foodtrucks/{slug}`: ficha aprobada, rubros y medios.
- Pendientes/rechazados sin versión pública devuelven 404. No incluye responsables, notas, trazas de muestra ni email.

Listado y detalle públicos del sitio no se han implementado todavía: las fichas se revisan mediante el preview privado de WordPress. La API queda preparada para sitio y app sin decidir estética ni shortcodes en esta etapa.

## Pruebas

Ejecutar en localhost con plugin activo: `wp eval-file tests/foodtrucks-integration.php`, `wp eval-file tests/events-integration.php`, `wp eval-file tests/events-http.php`. Crean registros temporales y los limpian, sin alterar las cuatro muestras ni eventos reales. Prueba de importación de logo usa respuestas HTTP controladas; la búsqueda real de Instagram se verificó también visualmente en el formulario.
