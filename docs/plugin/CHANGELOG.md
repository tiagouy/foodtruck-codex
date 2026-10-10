# Changelog

## 0.21.0 — 2026-10-10

- Bloques de Home independientes de Eventchamp: slider nativo con fotos originales, próximos eventos 4:5, foodtrucks, novedades, app, amigos, contacto y mapa diferido por coordenadas.
- Encabezado y pie compartidos con tema hijo Astra 0.1.0, mobile y escritorio. Datos/Core/API intactos. [Transición y límites](../web-astra-elementor.md); 44 verificaciones HTTP/integración.

## 0.20.0 — 2026-10-10

- Panel Notificaciones: envío PHP pushv3 para app 12, a usuarios de prueba o todos con confirmación. Credenciales solo en variables de entorno; historial propio y protección ante doble envío/resultados inciertos.
- [Configuración y límites](notificaciones-0.20.0.md). Apps sin cambios; sin envíos reales ni programación automática.

## 0.19.1 — 2026-10-08

- Lista y detalle web de fotos usan fecha/lugar juntos antes del texto, alineados con la app; tipografía/espaciado del detalle acercados a las tarjetas.
- Lugar enlazado a Maps con LAT/LONG válidas o búsqueda por texto si faltan, nueva pestaña segura. URLs escapadas, sin claves ni consultas al cargar la página. 34 comprobaciones web incluyendo orden, enlaces y despublicación.
- URLs universales según [documentación de Google Maps](https://developers.google.com/maps/documentation/urls/get-started). No cambia los datos guardados.

## 0.19.0 — 2026-10-08

- Publicaciones esquema 4 agrega `street_address` opcional: preserva dirección postal de Google separada del nombre/lugar visible guardado en `address`. Coordenadas se conservan. Nuevas publicaciones muestran el lugar en web/app mediante el campo público existente.
- Campo postal editable dentro del administrador, con validación y registro en el historial. Los datos históricos no se reemplazan: la columna nueva arranca vacía para ellos. Editar con un cliente anterior sin el campo no lo borra.
- 15 comprobaciones HTTP de subida y 31 de administración; fixtures limpiadas. Ver [flujo actualizado](../app/subir-fotos-0.7.0.md).

## 0.18.1 — 2026-10-08

- Corrige autocompletado móvil local: si no hay clave privada de servidor configurada, usa la clave que ya está configurada para la web. Consulta real a Places New confirmada con HTTP 200; no se cambiaron claves/restricciones en Google.
- La constante de servidor mantiene prioridad (también si está vacía). El fallback solo aplica en entorno local; producción sigue requiriendo clave privada dedicada. No expone la clave a la app ni la guarda en Git.
- Verificación real: búsqueda desde la app para Villa d y proxy completo con cuenta sintética para Plaza Villa Biarritz → dirección, latitud y longitud. Borrador real conservado y sin publicar; fixture limpiada. Diez regresiones simuladas pasan.

## 0.18.0 — 2026-10-08

- Subida multipart autenticada de publicaciones: identidad desde bearer, imagen optimizada en media/publicaciones, publicación directa e historial.
- Esquema de publicaciones 3 agrega recibos privados de reintento; no altera usuarios, fotos históricas ni estados anteriores. Mismo request_id/cuenta no duplica ni republica retiradas.
- Proxy autenticado de Places API New, clave únicamente en entorno privado del servidor, cuotas por cuenta y validación de sugerencia/sesión. Configuración de clave real pendiente.
- [Documentación](../app/subir-fotos-0.7.0.md): 14 comprobaciones HTTP reales y 10 de Places con respuestas simuladas.

## 0.17.0 — 2026-10-07

- POST autenticado `/publications/{id}/report`: denunciante derivado del token, publicación publicada y registro atómico con historial.
- Aparece en Publicaciones → Denunciadas y avisa al email administrador. No cambia el estado de la foto; repetir una denuncia abierta no duplica registro ni correo. Límite de diez nuevas denuncias por cuenta/hora.
- El registro se conserva aunque falle el envío de email; la entrega real depende del servidor. En local se capturan los correos. Diez comprobaciones de integración con fixtures descartables.

## 0.16.1 — 2026-10-07

- Listado web de fotos alineado con las tarjetas móviles: imagen con proporción 1.35, bordes redondeados, avatar/nombre, fecha/dirección y texto de hasta tres líneas visuales.
- Avatar circular actual en listado y detalle, icono de persona cuando falta o falla la imagen. Script externo de fallback, sin URLs de Gravatar ni JS inline.
- Diseño adaptable de tres/dos/una columnas y paleta cálida limitada a las páginas de fotos. Enlaces históricos, filtros por autor, paginación y despublicación conservados.

## 0.16.0 — 2026-10-07

- API autenticada de avatar por multipart; propietario derivado del token, validación de carga/MIME/peso real y procesamiento optimizado en media/perfiles.
- Reemplazo conserva el medio anterior y no pierde avatar si el procesamiento falla. Límite por usuario y exclusión de cargas simultáneas con recuperación de bloqueo vencido.
- Pruebas HTTP reales con cuenta/imágenes ficticias: subida, reemplazo, optimización, formato falso, exceso de peso y falta de sesión.

## 0.15.0 — 2026-10-07

- API autenticada para editar nombre y apellido propios, actualizando el nombre público. Ignora IDs, roles, email y contraseña del cliente.
- Perfil móvil incluye avatar migrado propio si existe. Sesiones y vínculos históricos conservados.
- Pruebas de autorización, campos y preservación de email/permisos/contraseña.

## 0.14.1 — 2026-10-07

- Mínimo de contraseña de ocho caracteres al confirmar, reactivar o recuperar cuenta, a pedido de Santi. Validación de servidor y formulario alineadas.
- Prueba HTTP rechaza siete caracteres, acepta ocho y verifica ingreso; sin modificar contraseñas existentes.

## 0.14.0 — 2026-10-07

- API de ingreso móvil, consulta y cierre de sesión; token aleatorio almacenado solo como hash, vencimiento, revocación y vínculo con contraseña.
- Cuentas pendientes y administrativas sin acceso móvil; HTTPS requerido fuera de local, límites por IP/email y respuestas de cuentas no cacheables.
- Pruebas locales de autenticación y regresiones de cuentas; diagnóstico SMTP/DNS sin cambios en producción.

## 0.13.1 — 2026-10-07

- Nombre y apellido originales en los campos nativos WordPress, también en importaciones nuevas.
- Comando local reanudable para completar únicamente campos vacíos de cuentas migradas, verificando fuente e identidad principal y preservando administradores y datos editados.
- Aplicado: 3.659 nombres y 3.649 apellidos; identidades, claves y 51 publicaciones sin cambios. Respaldo privado y pruebas de repetición/preservación.

## 0.13.0 — 2026-10-07

- Importación completa local y reanudable: 3.659 suscriptores pendientes de reactivación y administrador existente vinculado sin cambiar credenciales ni permisos. Duplicado unificado con alias; 16 emails inválidos excluidos.
- 1.311 avatares conservados en `/media/perfiles/`; dos archivos históricos HTML marcados para revisión, sin exponerlos como imágenes.
- 51 publicaciones de 28 autores importadas con estado, fecha, slug y metadata histórica privada; fotos proporcionales optimizadas sin recortar originales.
- Resolución pública por slug o ID histórico corto, enlaces canónicos y zona horaria histórica explícitamente desconocida. Repetir la importación no duplica ni sobrescribe moderación.
- Respaldos privados previos, fuentes intactas y correos sin cambios. 260 comprobaciones sobre publicaciones reales, prueba reversible de despublicación y regresiones de migración/modelo/web.

## 0.12.0 — 2026-10-07

- Listado/detalle web en las rutas históricas `/fotosusuarios/` y `/fotousuario/{slug}/`, listados por autor y compatibilidad con index.php/oferta.php.
- Solo publicadas; enlaces de pendientes/despublicadas responden 404 sin foto, texto ni vista previa. Páginas no cacheables, canonical y previews solo para publicadas.
- API agrega `share_url` estable. Slugs históricos preservados; el texto editable no determina el enlace.
- Navegación Fotos, estilos móviles y carga nativa de imágenes/avatares sin placeholders JavaScript de Eventchamp.
- 25 pruebas web, 31 de modelo y 12 HTTP de moderación. QA visual de listado/detalle/404 y móvil; fixtures retirados. Importación y asociaciones móviles pendientes.

## 0.11.0 — 2026-10-07

- Sección Publicaciones dentro de Foodtrucks UY: listado/filtros, edición de texto/ubicación, publicar/despublicar y notas/historial de moderación.
- Denuncias recibidas por email registrables manualmente, con filtro de abiertas y control de revisión, sin republicación automática.
- Tablas propias InnoDB, capacidad administrativa, nonces y versiones contra sobrescritura; historial y cambios guardados en transacción.
- API GET de publicaciones, detalle y listado por autor exclusivamente publicados; sin emails, notas o IDs históricos. Pendientes/despublicadas devuelven 404.
- 31 comprobaciones de modelo/API y 12 HTTP, incluyendo CSRF, permisos, ocultamiento y rollback. Fotos y publicaciones históricas sin importar ni modificar.

## 0.10.0 — 2026-10-07

- Importación local limitada y reanudable de hasta cinco usuarios con respaldo previo, permiso administrativo, bloqueo y registro por identidad. Sin importación masiva ni correos reales.
- Principal/alias históricos con reservas privadas, controles de duplicidad y resolución a un mismo usuario, preservando permisos/contraseña de cuentas existentes.
- Fotos propias de perfil optimizadas en `/media/perfiles/`, asociadas al usuario e integradas con avatar de WordPress; API de carga desde app pendiente.
- Aplicada muestra real: cuatro suscriptores pendientes, administrador vinculado y tres avatares. Repetición sin duplicados; fuentes y 51 publicaciones intactas, aún sin importar publicaciones.
- 18 pruebas sintéticas, 22 verificaciones de muestra real, 20 del plan y 24 de regresión de cuentas.

## 0.9.1 — 2026-10-07

- Nuevas subidas de eventos y foodtrucks a `/media/`, separadas de `wp-content`, con categorías y UUID por adjunto; perfiles/publicaciones preparados para futuras APIs e importación.
- Rutas relativas configurables, miniaturas/srcset y eliminación de archivos propios integrados con WordPress. Subidas ajenas y medios históricos intactos.
- Protección Apache contra ejecución PHP e índices vacíos; instrucciones para otros servidores y respaldo transitorio de ambos almacenes.
- 27 comprobaciones de almacenamiento, 17 de compresión de imágenes y 74 HTTP de formularios, incluida carga real.

## 0.9.0 — 2026-10-07

- Planificador `wp ftuy migrate-users --dry-run`, solo local, con lector de literales del SQL sin ejecutar el dump. Aplicación real aún no implementada.
- Exclusión de emails inválidos, unificación del duplicado con principal/alias, vínculo del administrador sin cambios de permisos y control de conflictos.
- Comprobación de referencias de avatar y conservación de la correspondencia de las 51 publicaciones con sus autores.
- Muestra determinista de cinco cuentas e informe agregado sin datos personales. Sin cambios de usuarios, medios, publicaciones ni correos.
- 20 comprobaciones del plan, incluyendo huellas de usuarios/metadata/correo/SQL antes y después; 24 de regresión de cuentas. Rechazo verificado al ejecutar sin --dry-run.

## 0.8.1 — 2026-10-07

- Reactivar cuenta destacado en un bloque antes de los formularios de ingreso/registro y en el menú Mi cuenta sin sesión.
- Verificación explícita de que registrar otro nombre con un email existente no duplica cuentas ni cambia nombre, contraseña o ID histórico. Sin cambios al comportamiento del servicio.
- Los emails de la app vieja todavía no se consultan: falta migrar sus cuentas antes de habilitar el registro público en producción.

## 0.8.0 — 2026-10-06

- Pantallas propias de registro, login por email, perfil/nombre, recuperación, reactivación y elección de contraseña; mismas cuentas WordPress, siempre suscriptor en registro.
- Confirmación de email por enlace nativo con vencimiento y un solo uso; cuentas pendientes sin login ni contraseñas de aplicación.
- API inicial compartida para solicitudes de registro/recuperación/reactivación. Sesiones móviles pendientes.
- Campo privado `ftuy_legacy_user_id` y servicio administrativo con control de duplicados; todavía sin migrar usuarios/fotos reales.
- Suscriptores sin barra ni acceso a wp-admin; administración existente conservada.
- Correos capturados localmente en Usuarios → Correos de cuentas, incluyendo notificaciones nativas; no se envían emails reales.
- Nonces anónimos por navegador, límites de solicitudes, redirecciones seguras y páginas privadas no cacheables.
- 22 comprobaciones de cuentas y 18 HTTP de registro/confirmación/login/perfil/logout; regresión de eventos/foodtrucks y revisión visual de login/registro en escritorio/móvil. Datos de prueba limpiados.

## 0.7.2 — 2026-10-06

- Cabecera única en páginas de eventos y foodtrucks: navegación pública estable, sin enlaces personales sueltos.
- Desplegable Mi cuenta: Mis eventos, Mis foodtrucks, Sugerir evento y Agregar mi foodtruck; iniciar/cerrar sesión según estado. Registro solo si WordPress lo tiene habilitado.
- Menú accesible mediante teclado y toque con `details`/`summary`, sin dependencia del tema ni JavaScript. No modifica cuentas ni permisos.
- 74 comprobaciones HTTP, incluyendo navegación con/sin sesión. Verificado visualmente en escritorio y a 375 px, sin desbordamiento horizontal del desplegable.

## 0.7.1 — 2026-10-06

- Formulario sin dimensiones, pesos finales ni explicación técnica: solo Logo y Foto del foodtruck. Redimensionado/compresión permanecen internos.
- Error claro al seleccionar un archivo de más de 5 MB; validación equivalente en servidor. Elegir otro archivo válido elimina el error.

## 0.7.0 — 2026-10-06

- Solo logo y foto del foodtruck; retiradas portada y galería del formulario y detalle, tarjetas cuadradas.
- Subidas procesadas antes de incorporarse a medios WordPress: logo JPEG 500×500 px/120 KB y foto JPEG 900×900 px/300 KB, recorte centrado, orientación EXIF, fondo blanco y densidad 72 dpi.
- Compresión adaptativa y rechazo si no cumple dimensiones/peso. Nunca se guarda el original grande de una nueva subida. Logos de Instagram usan el mismo proceso.
- Compatibilidad con roles históricos y conversión por copia al volver a guardar una ficha, sin borrar archivos anteriores ni conversión masiva.
- Pruebas específicas de tamaños, peso, dpi, transparencia y originales, más subidas HTTP reales de los dos roles.
- 15 comprobaciones de imágenes, 65 HTTP, 35 de foodtrucks y 44 de eventos, usando GD en MAMP.

## 0.6.0 — 2026-10-05

- Formulario web `/agregar-foodtruck/` para cuentas WordPress existentes, sin acceso al panel ni campos de gestión.
- `/mis-foodtrucks/`: fichas de la cuenta, edición, estado de revisión y mensajes de correcciones/rechazo; no expone fichas ajenas.
- Altas y cambios pendientes conservan versión publicada y responsable real; el formulario ignora manipulaciones de estado, cuenta y decisión.
- Imágenes propias/asociadas a la ficha, límite de envíos y limpieza de cargas nuevas fallidas.
- Consulta opcional de Instagram para usuarios autenticados mediante acción y nonce propios; carga manual alternativa.
- Formulario de datos compartido con administración, manteniendo las acciones de revisión exclusivamente en el panel.
- Login nativo con retorno a la página; registro se ofrece solo si está habilitado, sin cambiar ajustes ni implementar la migración/activación de cuentas en esta etapa.
- 61 comprobaciones HTTP, 35 de foodtrucks y 44 de eventos; revisión visual del formulario y de Instagram como suscriptor, en escritorio y celular.

## 0.5.0 — 2026-10-05

- Directorio `/foodtrucks/` con tarjetas, filtros por departamento/rubro, paginación y estado vacío.
- Detalle `/foodtruck/{slug}/` con portada, logo, descripción, oferta gastronómica, galería oficial, modalidades y contactos WhatsApp/Instagram.
- Navegación compartida con eventos, presentación responsive y enlaces a imágenes completas.
- Catálogo y detalles privados de propuestas mediante permisos y nonce, no indexables; enlazados desde administración sin publicar muestras.
- Solo fichas aprobadas en páginas públicas. Fichas pendientes/inexistentes devuelven 404 sin mostrar datos ni redirigir a Eventchamp.
- 42 comprobaciones HTTP, además de 35 de foodtrucks y 44 de eventos. Revisión visual de catálogo/detalle en escritorio y móvil sin desbordamiento horizontal.

## 0.4.0 — 2026-10-05

- Tablas propias de foodtrucks, rubros, relaciones, medios oficiales y propuestas de revisión; esquema independiente del módulo de eventos.
- Administración Foodtrucks UY → Foodtrucks con cuenta responsable, WhatsApp, Instagram, rubros múltiples, modalidades, logo/portada/galería y preview privado.
- Moderación de propuestas; mantiene la versión publicada hasta aprobar cambios.
- API paginada de lectura `/foodtrucks` y `/foodtrucks/{slug}`; solo fichas publicadas, sin email, cuenta responsable o notas internas.
- Consulta opcional de metadata pública de Instagram con propuesta de nombre e imagen confirmada antes de usar; tokens temporales ligados a cuenta/perfil, importación de logo a medios y carga manual alternativa.
- Cuatro muestras locales pendientes; comando idempotente limitado a esas fichas, sin importación masiva.
- 35 pruebas de foodtrucks, 44 de eventos y 31 HTTP; consulta real de QueChurro probada desde el formulario.

## 0.3.1 — 2026-10-05

- El bloque «Dónde será» del detalle y preview muestra primero el nombre del lugar y después la dirección, evitando duplicarlos si coinciden. El enlace al mapa conserva las coordenadas seleccionadas.

## 0.3.0 — 2026-10-05

- Formulario sin resumen breve: nombre, descripción, fechas, horarios, lugar y dirección, departamento/localidad, Instagram, web opcional y entradas.
- Horario común o independiente por día; opcional si aún no se confirmó. Se conserva y muestra en detalle/API.
- Radio Gratis / Con entrada, con enlace obligatorio para entradas y validación del lado servidor.
- Google Places Autocomplete restringido a Uruguay; obtiene dirección, departamento, localidad y coordenadas. Conserva ingreso manual como alternativa.
- Reutiliza clave local de Google sin incluirla en Git. Requiere habilitar Places API (New) en el proyecto de Google Cloud; diagnóstico local: servicio deshabilitado (403).
- Esquema 3 incremental; mantiene eventos, propuestas y precios históricos sin asumir que son gratuitos.
- 44 pruebas de integración y 19 HTTP; verificación visual de horarios y campos condicionales.

## 0.2.1 — 2026-10-05

- Recuperación de Instagram y sitio web de los eventos originales de Eventchamp.
- Instagram como campo independiente en formularios, detalle y API; admite @usuario o URL de perfil.
- Migración incremental al esquema 2, conservando datos y enlaces ya editados.

## 0.2.0 — 2026-10-05

- Tablas propias de eventos, propuestas de revisión y notificaciones de correo.
- Panel Foodtrucks UY con edición, aprobación, correcciones, rechazo y preview privado.
- Páginas de eventos, histórico, detalle, sugerencias autenticadas y mis eventos.
- Diseño independiente de Eventchamp, con tarjetas, afiches completos y filtro de departamentos.
- API pública de lectura versionada, paginada y limitada a eventos publicados.
- Importación idempotente por WP-CLI; ensayo de cuatro eventos conserva IDs de origen, fotos y URLs.
- Correos capturados en MAMP; registro de fallos y reintentos en otros entornos.
- Pruebas locales de permisos, moderación, API, migración y formulario con imagen.

## 0.1.0 — 2026-10-05

- Estructura inicial del plugin y entrada reconocible por WordPress.
- Versión declarada en cabecera y constante.
- Sin tablas, endpoints ni cambios de datos todavía.
