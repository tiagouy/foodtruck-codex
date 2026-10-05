# Plan de trabajo

Estado inicial: 2026-10-05. Este checklist define el trabajo pendiente; las funcionalidades de cada etapa se ajustarán con el enfoque de producto y el MVP acordados.

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
- [ ] Instalar el plugin desde `plugins/foodtrucks-uy-core/`, definiendo cómo sincronizar el código del repositorio con `wp-content/plugins/`.
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
- [ ] Inventariar tablas, cantidades, relaciones, imágenes, duplicados y calidad de los datos históricos.
- [ ] Separar usuarios de la app de cuentas administrativas del sitio; identificar el uso real de `usuarios` frente a `users`.
- [ ] Mapear cada operación del panel `admin/` a la nueva administración.
- [ ] Definir qué datos y fotos migrar, conservar como archivo o descartar del sistema nuevo.

Resultado: alcance acordado y mapa de datos verificado.

## 3. Plugin: datos, cuentas y administración

- [ ] Diseñar tablas propias, relaciones, índices y versión del esquema.
- [ ] Implementar instalación y migraciones idempotentes con `$wpdb->prefix`.
- [ ] Implementar cuentas y permisos compartidos por web/app, sin acceso al panel para usuarios de comunidad.
- [ ] Implementar registro, verificación, login, cierre de sesión, recuperación y reactivación; definir sesiones/tokens de app.
- [ ] Crear menú Foodtrucks UY en WordPress y gestión de foodtrucks, rubros, eventos y participantes.
- [ ] Crear gestión de publicaciones de fotos, categorías, denuncias y moderación.
- [ ] Implementar propiedad de registros y revisión de altas/cambios enviados por usuarios.
- [ ] Implementar subida de imágenes con validación, límites, miniaturas y eliminación coherente.
- [ ] Definir política de eliminación de cuentas/contenido y conservación al desactivar o desinstalar el plugin.

Resultado: los datos se administran desde WordPress con permisos verificables.

## 4. API común

Se implementa junto con los módulos del plugin, antes de construir todos los clientes.

- [ ] Documentar recursos, campos, errores, paginación, filtros y autenticación bajo `foodtrucks-uy/v1`.
- [ ] Implementar lectura pública de catálogo, eventos y publicaciones según sus estados.
- [ ] Implementar cuenta, altas, cambios, sugerencias, fotos y denuncias autenticadas según permisos.
- [ ] Validar que cada usuario solo pueda modificar los registros autorizados.
- [ ] Implementar límites de solicitudes, caché de lecturas públicas e invalidación tras cambios.
- [ ] Probar contratos con datos de ejemplo, errores y varias páginas de resultados.

Resultado: API usable por sitio y app con un contrato compartido.

## 5. Sitio web

- [ ] Elegir tema y ubicación del código visual propio; documentar y versionar las personalizaciones.
- [ ] Construir inicio, directorio y detalle de foodtruck, agenda y detalle de evento.
- [ ] Construir feed/detalle de fotos y mapas según el MVP.
- [ ] Construir cuenta, perfil, alta/gestión de foodtruck, sugerencia de evento y publicación de fotos.
- [ ] Mostrar estados de revisión y mensajes claros para formularios y errores.
- [ ] Implementar búsqueda, filtros, diseño móvil, accesibilidad y SEO.
- [ ] Probar un flujo completo: registro → publicación/sugerencia → revisión → aparición pública.

Resultado: primera experiencia completa que valida el plugin y los datos. La web puede llamar directamente a servicios del plugin desde PHP; no necesita hacer peticiones HTTP a sí misma. Ambos clientes comparten reglas y datos.

## 6. Migración de ensayo

- [ ] Construir importador idempotente con modo de simulación e informe de errores.
- [ ] Importar muestra de usuarios de la app, foodtrucks, eventos y publicaciones conservando las relaciones de autoría.
- [ ] Migrar medios y verificar archivos/referencias, fechas, coordenadas y codificación.
- [ ] Resolver duplicados y datos inválidos; conservar equivalencias de IDs históricos.
- [ ] Probar reactivación con correo local y contraseñas nuevas.
- [ ] Comparar cantidades y revisar muestras manualmente.
- [ ] Definir redirecciones de enlaces públicos históricos.

Resultado: migración repetible y revisada antes del corte de producción.

## 7. App nueva

- [ ] Elegir tecnología y revisar identificadores de Android/iOS, firma y cuentas de las tiendas existentes.
- [ ] Crear proyecto móvil y configuración de entornos local/pruebas/producción.
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
