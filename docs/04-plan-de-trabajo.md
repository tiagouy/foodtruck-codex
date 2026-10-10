# Plan de trabajo

Estado inicial: 2026-10-05. Este checklist define el trabajo pendiente; las funcionalidades de cada etapa se ajustarán con el enfoque de producto y el MVP acordados.

## Actualización · 2026-10-10 · plugin 0.21.0 / tema Astra 0.1.0

[Home nueva con Astra/Elementor](web-astra-elementor.md) activa en local: fotos originales, datos del plugin, mapa diferido, cabecera y pie comunes. Dependencias Eventchamp/WPBakery retiradas de ejecución, originales y demos conservados como borradores. 44 comprobaciones; páginas institucionales antiguas y reCAPTCHA local pendientes. Online intacto.

## Actualización · 2026-10-10 · plugin 0.20.0

[Notificaciones desde WordPress](plugin/notificaciones-0.20.0.md): panel para administradores, credenciales por entorno PHP, prueba por IDs y envío general con confirmación. Historial e idempotencia; 38 verificaciones con HTTP interceptado, sin push reales. Variables del servicio web y recepción real pendientes; integración móvil sin cambios.

## Actualización · 2026-10-09 · app 0.10.6

Home: logo con nombre del splash en lugar de la frase introductoria, centrado y sin recorte. Cabezal y bloques conservados.

## Actualización · 2026-10-09 · app 0.10.5

Afiches de eventos en formato vertical 4:5 sin recorte en carrusel y detalle de la app, compatible con los originales para los posteos del fin de semana. Web ya usa esta proporción. Sin alterar archivos ni formatos de otras categorías; QA visual pendiente.

## Actualización · 2026-10-09 · app 0.10.4

Botones con naranja más oscuro, texto blanco y tipografía aumentada a 17. Se conserva la paleta del logo para la marca y se verifica contraste automáticamente.

## Actualización · 2026-10-09 · app 0.10.3

[Paleta del logo](app/paleta-0.10.3.md) aplicada a la app: naranja/azul exactos, fondos claros y textos con contraste verificado. 66 pruebas, TypeScript y lint. WordPress sin cambios; QA visual pendiente.

## Actualización · 2026-10-09 · app 0.10.2

[Splash de marca](app/splash-0.10.2.md) con logo/nombre en Android e iOS. Compilaciones verificadas, Android actualizado y arranque frío correcto; 64 pruebas y lint. Sin segundo splash JS ni espera artificial. Android12+ respeta máscara del sistema; QA visual en más dispositivos pendiente.

## Actualización · 2026-10-09 · app 0.10.1

[Iconos](app/iconos-0.10.1.md) iPhone/iPad/tiendas y Android legacy/adaptativo/monocromático/notificaciones derivados del logo aprobado. Android actualizado en emulador sin borrar datos, iOS compilado; 64 pruebas y lint pasan. Usuario confirmó token guest, asociación a cuenta 100 y recepción push; logout/refresh real, Android físico e iOS push siguen pendientes.

## Actualización · 2026-10-09 · app 0.10.0

[Pushv3 cliente](app/pushv3-integracion.md) integrado con Firebase histórico, guest/login/refresh/logout y credenciales locales excluidas. Falta Android físico e instalación limpia, registro real en servidor, envío dirigido desde panel y confirmación de success/failure/provider=fcm_v1. APNs/firma iOS también pendientes. Ningún service account en la app, sin publicación productiva.

## Actualización · 2026-10-08 · app 0.9.0 / plugin 0.19.1

Home y agenda incorporan [carrusel de eventos](app/carrusel-eventos-0.9.0.md) con imágenes cuadradas, centro destacado y vecinos escalados. Conserva próximos/pasados y paginación. QA visual con históricos, 53 pruebas; pendiente gesto manual y Android. Subida real de la foto de flores confirmada por el usuario; nombre del lugar, dirección postal y coordenadas guardados. Lista/detalle de fotos alineados con enlaces Maps. No hay despliegue productivo ni envío a tiendas.

Actualización posterior: app 0.8.0 incorpora recorte nativo cuadrado; plugin 0.18.1 corrige autocompletado local reutilizando la clave web existente. Google real y LAT/LONG verificados. Para producción todavía se debe configurar la clave privada dedicada; no hace falta otra activación para probar en local.

## Actualización · 2026-10-08 · plugin 0.18.0 / app 0.7.0

App nativa local con lectura pública, sesiones seguras, perfil/avatar, Mis fotos, denuncia y [subida directa de fotos](app/subir-fotos-0.7.0.md) (foto + texto + dirección, sin puntaje ni aprobación previa). Administración y despublicación en el plugin. Subida HTTP, optimización e idempotencia probadas con fixtures; selector/formulario comprobados en el simulador.

Pendiente inmediato: configurar la clave privada de Places para servidor y probar autocompletado real; hasta entonces funciona dirección manual. Luego probar el circuito completo desde la app y Android/XR, resolver identidad/firma de tiendas, enlaces universales, entrega productiva de email, términos/privacidad y despliegue. No hay aún versión enviada a tiendas ni cambios productivos. Las actualizaciones de abajo son históricas, no el estado actual.

## Actualización · 2026-10-07 · 0.13.0

Migración completa aplicada **solo en local**: 3.659 suscriptores pendientes de reactivación, administrador conservado, 1.311 avatares y 51 publicaciones de 28 autores. IDs/alias históricos preservados; 16 emails inválidos excluidos y un duplicado unificado. Dos falsos avatares HTML quedan marcados para revisión. Respaldos privados y fuentes intactas, sin correos reales. Ver [resultado y procedimiento](plugin/migracion-completa-0.13.0.md).

Las fotos ya se pueden ver en la web y administrar dentro del plugin: editar/despublicar, historial y denuncias manuales. Pendientes principales: nueva app y sus sesiones/subidas/denuncias, asociaciones Android/iOS, retirada física de imágenes cuando corresponda, diseño/home y despliegue productivo. Despublicar oculta listado, detalle y API; no bloquea la URL física del archivo.

## Actualización · 2026-10-06

- Implementados localmente eventos/foodtrucks, moderación, páginas, API de lectura e imágenes reducidas.
- Implementadas pantallas de cuentas en 0.8.0: suscriptores WordPress, registro/confirmación por email, login, perfil/nombre, recuperación y reactivación. Correos locales capturados sin envío real.
- Preparado ID histórico privado para conservar asociación con fotos, sin migrar usuarios reales todavía.
- Próximo paso de cuentas: auditoría de emails/IDs antiguos y migración pequeña de ensayo, luego sesiones de app. Ver [cuentas](plugin/cuentas-0.8.0.md).
- **Descartada la relación entre eventos y foodtrucks**; no implementar participantes ni asociaciones. Fotos de comunidad se suben solo desde la app, no desde el sitio.
- La home/tema histórico sigue pendiente; la navegación propia ya es común a los módulos del plugin.
- 0.9.0: simulación de migración local implementada y ejecutada. Propone 3.659 suscriptores nuevos más la cuenta administrativa existente; conserva la correspondencia de las 51 publicaciones. Muestra de cinco cuentas seleccionada en memoria, aún sin importar. Ver [simulación](plugin/migracion-usuarios-0.9.0.md).
- 0.9.1: nuevas subidas del plugin en `/media/`, fuera de `wp-content`; categorías para eventos/foodtrucks y futuras fotos de perfil/publicaciones. Medios históricos sin mover: respaldar también `uploads` y las fuentes antiguas. Ver [imágenes y respaldos](plugin/imagenes-0.9.1.md).
- 0.10.0: [muestra real importada](plugin/migracion-muestra-0.10.0.md): cuatro suscriptores pendientes, administrador vinculado sin cambiar credenciales/permisos y tres avatares en media/perfiles. Alias y reactivación probados con datos sintéticos. Importación masiva y 51 publicaciones pendientes.
- 0.11.0: [Publicaciones en el admin](plugin/publicaciones-0.11.0.md), editar texto/ubicación, publicar/despublicar, denuncias manuales e historial. API nueva de lectura solo de publicadas. Sin importar aún las 51 antiguas; subida/denuncias de app y compatibilidad de URLs pendientes.
- 0.12.0: [listado/detalle web de fotos](plugin/fotos-web-0.12.0.md) en `/fotosusuarios/` y `/fotousuario/{slug}/`, solo publicadas, 404 sin imagen ni preview para pendientes/despublicadas. share_url en API. Rutas web listas; asociaciones de nueva app Android/iOS y migración real pendientes.

## 0. Base preparada

- [x] Crear Git y conectar el repositorio de GitHub.
- [x] Separar código nuevo de los respaldos históricos.
- [x] Centralizar documentación en `docs/`.
- [x] Preparar plugin base `0.1.0`, changelog y tag.
- [x] Confirmar hosting compartido y tablas propias dentro de la base WordPress.

## 1. Entorno local — siguiente paso

Responsable inicial: Santi prepara WordPress local; luego conectamos el plugin al entorno.

- [ ] Crear una instalación limpia de WordPress local, con base independiente y cuenta administrativa de desarrollo.
- [ ] Informar la URL local, ruta de instalación y versiones PHP/MySQL o MariaDB del hosting compartido para mantener compatibilidad.
- [x] Instalar el plugin desde `plugins/foodtrucks-uy-core/`, definiendo cómo sincronizar el código del repositorio con `wp-content/plugins/`.
- [ ] Configurar correo de pruebas capturado localmente y desactivar envíos reales, push, pagos y tareas externas en cualquier copia histórica.
- [ ] Activar logs de desarrollo y comprobar REST API, enlaces permanentes y carga de imágenes.
- [ ] Decidir si hace falta una segunda instalación local del sitio viejo para comparar tema, contenidos y comportamiento; mantenerla separada de la nueva.

Resultado: WordPress local funcional con el plugin base activado.

## 2. Producto y auditoría

- [ ] Cerrar el cambio de enfoque, público objetivo y propuesta de valor.
- [ ] Definir funcionalidades del MVP y etapas posteriores, incluida la prioridad de publicidad y notificaciones.
- [ ] Definir campos de foodtruck, evento, publicación de foto y perfil.
- [ ] Definir roles: comunidad, propietario, moderador y administrador; permisos y proceso para reclamar un foodtruck.
- [ ] Definir estados y reglas de publicación, sugerencias, revisión y denuncias.
- [ ] Revisar navegación e identidad visual nueva.
- [x] Inventariar tablas, cantidades, relaciones, imágenes, duplicados y calidad de los datos históricos.
- [x] Separar usuarios de la app de cuentas administrativas del sitio; identificar el uso real de `usuarios` frente a `users`.
- [ ] Mapear cada operación del panel `admin/` a la nueva administración.
- [x] Definir qué datos y fotos migrar, conservar como archivo o descartar del sistema nuevo.

Resultado: alcance acordado y mapa de datos verificado.

## 3. Plugin: datos, cuentas y administración

- [ ] Diseñar tablas propias, relaciones, índices y versión del esquema.
- [ ] Implementar instalación y migraciones idempotentes con `$wpdb->prefix`.
- [ ] Implementar cuentas y permisos compartidos por web/app, sin acceso al panel para usuarios de comunidad.
- [ ] Implementar registro, verificación, login, cierre de sesión, recuperación y reactivación; definir sesiones/tokens de app.
- [ ] Crear menú Foodtrucks UY en WordPress y gestión de foodtrucks, rubros y eventos (sin participantes ni relación evento–foodtruck).
- [ ] Crear gestión de publicaciones de fotos, categorías, denuncias y moderación.
- [ ] Implementar propiedad de registros y revisión de altas/cambios enviados por usuarios.
- [ ] Implementar subida de imágenes con validación, límites, miniaturas y eliminación coherente.
- [ ] Definir política de eliminación de cuentas/contenido y conservación al desactivar o desinstalar el plugin.

Resultado: los datos se administran desde WordPress con permisos verificables.

## 4. API común

Se implementa junto con los módulos del plugin, antes de construir todos los clientes.

- [ ] Documentar recursos, campos, errores, paginación, filtros y autenticación bajo `foodtrucks-uy/v1`.
- [x] Implementar lectura pública de catálogo, eventos y publicaciones según sus estados.
- [ ] Implementar cuenta, altas, cambios, sugerencias, fotos y denuncias autenticadas según permisos.
- [ ] Validar que cada usuario solo pueda modificar los registros autorizados.
- [ ] Implementar límites de solicitudes, caché de lecturas públicas e invalidación tras cambios.
- [ ] Probar contratos con datos de ejemplo, errores y varias páginas de resultados.

Resultado: API usable por sitio y app con un contrato compartido.

## 5. Sitio web

- [ ] Elegir tema y ubicación del código visual propio; documentar y versionar las personalizaciones.
- [ ] Construir inicio, directorio y detalle de foodtruck, agenda y detalle de evento.
- [x] Implementar listado/detalle web en rutas históricas verificadas: `/fotosusuarios/` y `/fotousuario/{slug}/`. Sin subida web; pendientes/despublicadas ocultas en listado/autor/detalle y con 404 sin foto ni metadata de preview.
- [ ] Migrar slugs/datos antiguos y configurar/probar enlaces asociados Android/iOS de la nueva app, con fallback a la web; la app debe revalidar estado al abrir enlaces compartidos.
- [x] Construir cuenta, perfil, alta/gestión de foodtruck y sugerencia de evento. Las publicaciones de fotos se crean desde la app.
- [ ] Mostrar estados de revisión y mensajes claros para formularios y errores.
- [ ] Implementar búsqueda, filtros, diseño móvil, accesibilidad y SEO.
- [ ] Probar un flujo completo: registro → publicación/sugerencia → revisión → aparición pública.

Resultado: primera experiencia completa que valida el plugin y los datos. La web puede llamar directamente a servicios del plugin desde PHP; no necesita hacer peticiones HTTP a sí misma. Ambos clientes comparten reglas y datos.

## 6. Migración de ensayo

- [x] Construir importador idempotente con modo de simulación e informe de errores.
- [ ] Importar muestra de usuarios de la app, foodtrucks, eventos y publicaciones conservando las relaciones de autoría.
- [ ] Migrar medios y verificar archivos/referencias, fechas, coordenadas y codificación.
- [x] Resolver duplicados y datos inválidos; conservar equivalencias de IDs históricos.
- [ ] Probar reactivación con correo local y contraseñas nuevas.
- [x] Comparar cantidades y revisar muestras manualmente.
- [x] Definir redirecciones de enlaces públicos históricos.

Resultado: migración repetible y revisada antes del corte de producción.

## 7. App nueva

- [x] Elegir React Native/TypeScript y crear base móvil separada, adaptando estructura/componentes de BuenCafé. Ver [base 0.1.0](app/base-0.1.0.md).
- [ ] Confirmar identificadores de Android/iOS, firma y cuentas de tiendas existentes; la base usa identidad provisional de desarrollo.
- [x] Crear proyecto móvil y configuración de entorno local iOS Simulator/Android Emulator.
- [x] Conectar formularios nativos de registro, recuperación y reactivación a la API de cuentas; elección de contraseña por enlace del correo. Ver [cuentas 0.2.0](app/cuentas-0.2.0.md).
- [x] Ingreso móvil por email/contraseña, persistencia segura y cierre con revocación. Ver [ingreso 0.3.0](app/ingreso-0.3.0.md). QA de dispositivo real y Android pendiente.
- [ ] Validar entrega de correo en producción, DKIM/DMARC y proveedor SMTP. Ver [diagnóstico](plugin/correos-transaccionales.md).
- [ ] Configurar y verificar entornos HTTPS de pruebas/producción antes de distribuir.
- [ ] Implementar navegación, catálogo, eventos, publicaciones y mapas del MVP.
- [ ] Implementar sesión, perfil y flujos de contribución acordados.
- [ ] Integrar cámara/galería, permisos, subida de fotos y tratamiento de errores de conexión.
- [ ] Probar desde dispositivos la API local, accesible en la red de desarrollo.
- [ ] Implementar push, enlaces profundos y analítica según la etapa acordada.
- [ ] Validar Android/iOS, paginación, recuperación de sesión y permisos.

Resultado: app conectada desde sus primeras pantallas a la API existente.

## 8. Pruebas y publicación en el compartido

- [ ] Preparar entorno de pruebas en el hosting con base y credenciales separadas.
- [ ] Validar compatibilidad PHP/base, correo, SSL, subidas y tareas programadas.
- [ ] Al desplegar a producción, configurar `PUSH_API_KEY` y `FTUY_PUSH_TOKEN_APP` en el entorno del servicio PHP, fuera del repositorio/carpeta pública. Verificar disponibilidad en el panel y realizar un envío autorizado de prueba antes de habilitar envíos generales. Por decisión del usuario, configuración local pospuesta; sin valores guardados por ahora. Ver [notificaciones](plugin/notificaciones-0.20.0.md).
- [ ] Medir API y procesamiento de fotos con carga representativa; ajustar índices, caché y límites.
- [ ] Verificar permisos, sesiones, moderación, recuperación y actualización del esquema.
- [ ] Preparar textos de privacidad, condiciones y reactivación, y metadatos de tiendas.
- [ ] Preparar backup, recuperación, paquete versionado y checklist de release.
- [ ] Definir convivencia/corte de clientes antiguos y ventana de congelación de datos.
- [ ] Ejecutar migración final y comprobar sitio/API antes de enviar campañas de reactivación.
- [ ] Publicar app y coordinar retiro de APIs/admin antiguos cuando ya no sean necesarios.
- [ ] Monitorear errores, correo, consumo del compartido y restauración de backups.

## Orden y criterio de avance

WordPress local → alcance/datos → plugin + API → primer flujo web completo → migración de ensayo → app → validación y publicación.

Diseño web y diseño móvil pueden avanzar en paralelo una vez acordados los flujos. La conexión se construye durante el desarrollo de cada cliente; no se deja para el final. No hace falta terminar todo el sitio para iniciar la app si la API y los módulos que necesita ya están probados.
