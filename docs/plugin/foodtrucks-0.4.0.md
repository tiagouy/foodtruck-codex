# Foodtrucks · operación 0.4.0

En WordPress: **Foodtrucks UY → Foodtrucks**. Capacidad específica `manage_ft_foodtrucks`, otorgada a administradores al instalar el esquema. Usuarios de comunidad no acceden a esta administración. Desde 0.6.0 existe alta/gestión en la web, sin panel administrativo; reclamación de fichas y notificaciones de foodtrucks siguen pendientes.

## Tablas

Con el prefijo WordPress del entorno: `ft_foodtrucks`, `ft_cuisine_categories`, `ft_foodtruck_cuisines`, `ft_foodtruck_images`, `ft_foodtruck_reviews`. Esquema propio en `ftuy_foodtruck_schema=1`, sin renombrar tablas de los SQL antiguos ni alterar las de eventos.

## Moderación y medios

Guardar propuesta crea una revisión pendiente; no reemplaza una ficha pública. Aprobar aplica campos, rubros y medios dentro de una transacción. Correcciones/rechazo dejan intacta la versión publicada. Desmarcar «Conservar» quita el vínculo en la propuesta; no elimina archivos antiguos de la biblioteca.

Desde 0.7.0 las imágenes opcionales son un logo y una foto del foodtruck, sin portada ni galería. Logo: 500×500 px/120 KB; foto: 900×900 px/300 KB. Se recibe JPG/PNG/WebP ≤5 MB y ≤40 MP, se corrige orientación EXIF, se recorta centrado y se guarda únicamente JPEG reducido con densidad 72 dpi, sin metadatos EXIF; transparencias sobre blanco. No se reutiliza ni mezcla la galería de publicaciones de usuarios. Los dpi no reducen el peso: lo hacen dimensiones y compresión. Campos mínimos y límites están en [definición](../07-foodtrucks.md).

El proceso se aplica tanto en administración como en el formulario de propietarios y en logos importados de Instagram. Las imágenes existentes se convierten al volver a guardar la ficha: se crea un medio nuevo, sin modificar ni borrar el original histórico. Para compatibilidad, una portada antigua se interpreta como foto del foodtruck; si no hay portada, se toma la primera foto oficial. No se ejecutó una conversión masiva. El servidor necesita GD o Imagick; ante fallos se rechaza la imagen, nunca se conserva el archivo grande como alternativa. Probado localmente con GD; Imagick no está instalado en MAMP.

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

## Páginas web · actualización 0.5.0

- `/foodtrucks/`: listado público de fichas aprobadas, doce por página, filtros `departamento` y `rubro` (ID gastronómico). No son páginas con shortcode: son rutas y plantillas del plugin, como eventos.
- `/foodtruck/{slug}/`: detalle aprobado con medios oficiales, qué sirven, descripción, base, modalidades y enlaces WhatsApp/Instagram cuando están cargados. La base no se presenta como ubicación actual. No muestra cuenta responsable, notas ni email.
- Ficha pendiente o inexistente: 404 propio, sin redirecciones automáticas a fichas antiguas.
- En administración hay botones «Ver página pública» y «Vista privada del catálogo con muestras». La vista privada requiere sesión con `manage_ft_foodtrucks` y nonce. Usa las propuestas guardadas, marca claramente la vista previa y responde noindex/nofollow.
- El botón de preview de cada ficha abre ahora el diseño real del detalle web. Los enlaces de las tarjetas privadas conservan autorización para navegar entre catálogo y detalles. Al aprobar la ficha aparece en el catálogo público; no se aprobaron automáticamente las cuatro muestras.
- Las páginas de eventos enlazan al nuevo directorio. Estética definitiva y shortcode para home siguen pendientes.

## Alta y gestión web · actualización 0.6.0

- `/agregar-foodtruck/`: ingresar con cuenta WordPress existente, completar ficha y enviar propuesta pendiente. Responsable se asigna desde la sesión, no desde campos del navegador. Sin email público, botones de aprobación ni selección de responsables.
- `/mis-foodtrucks/`: solo fichas de la cuenta actual, veinte por página; muestra estado de revisión y permite editar mediante `/agregar-foodtruck/?edit={id}`. Correcciones/rechazo muestran el mensaje correspondiente; un cambio pendiente no elimina la ficha aprobada.
- Los rubros y modalidades son múltiples. Medios propios nuevos y medios ya asociados a la ficha pueden conservarse; no admite adjuntar IDs de otras fichas por manipulación del formulario. Al fallar un envío se eliminan únicamente las imágenes nuevas de ese intento, no las históricas.
- Consulta opcional de Instagram para usuarios logueados: AJAX `ftuy_instagram_public_preview`, nonce `ftuy_instagram_public`; mismo límite de consultas y token temporal del panel, sin otorgar capacidades administrativas. La acción del panel conserva su requisito de administrador de foodtrucks.
- Máximo un envío correcto por minuto/cuenta; validaciones de campos no consumen el límite. Nonce obligatorio, cuenta y estado inmutables desde el formulario. Altas y cambios nunca se aprueban automáticamente, incluso si el usuario del formulario es administrador.
- Estas páginas son privadas/noindex y no almacenables en caché. La API pública sigue mostrando solo fichas aprobadas, no propuestas.
- Login mediante WordPress con `redirect_to` de vuelta a la página. Crear cuenta aparece solo si `users_can_register` está habilitado; en MAMP actualmente no lo está. No se cambió ese ajuste ni se agregaron flujos de registro, reactivación o sesiones de app. Usuarios históricos todavía no se migraron; no alterar IDs ni fotos.
- No se implementó carga de fotos de comunidad en el sitio: las imágenes del formulario son oficiales del foodtruck. Publicaciones de usuarios siguen reservadas a la app.

## Pruebas

Ejecutar en localhost con plugin activo: `wp eval-file tests/foodtrucks-integration.php`, `wp eval-file tests/events-integration.php`, `wp eval-file tests/events-http.php` y `wp eval-file tests/foodtruck-images.php`. Crean registros temporales y los limpian, sin alterar las cuatro muestras ni eventos reales. La prueba de imágenes verifica dimensiones, peso, JPEG/72 dpi, transparencia blanca, conservación de originales e idempotencia. Prueba de importación de logo usa respuestas HTTP controladas; la búsqueda real de Instagram se verificó también visualmente en el formulario.
