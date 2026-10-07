# Versionado y operación

## Decisiones confirmadas — 2026-10-05

- Continuar con el hosting compartido actual.
- Tablas propias dentro de la base WordPress, usando `$wpdb->prefix`.
- Administrar la app y el sitio desde WordPress.
- Migrar las cuentas de la app; las cuentas administrativas del sitio son un origen diferente.
- Incluir las fotos de la comunidad en el alcance del producto.
- Git para código y documentación.

## Releases del plugin

Versión inicial `0.1.0`. Usar `MAJOR.MINOR.PATCH`: cambios incompatibles incrementan MAJOR, funcionalidades MINOR y correcciones PATCH. Durante `0.x`, documentar explícitamente incompatibilidades.

Actualizar juntos la cabecera PHP, `FOODTRUCKS_UY_CORE_VERSION` y `docs/plugin/CHANGELOG.md`. Cada release se identifica mediante commit y tag anotado `foodtrucks-uy-core-vX.Y.Z`. `0.1.0` es una base sin funcionalidad de negocio.

La versión del esquema y la API (`v1`) son independientes de la versión del plugin. Los cambios de tablas requieren migraciones incrementales e idempotentes que conserven datos. Desactivar el plugin conserva sus tablas.

## Releases de la app

Primera base móvil `0.1.0`, independiente del plugin. Actualizar `apps/mobile/package.json`, versiones nativas Android/iOS y `docs/app/CHANGELOG.md`; incrementar números de build al distribuir. Tag anotado `foodtrucks-uy-app-vX.Y.Z`. Versionar lockfiles propios, no dependencias instaladas, Pods, cachés, credenciales ni keystores. El identificador provisional `com.foodtrucksuy.dev` y la configuración solo local no sirven para una actualización de tiendas; confirmar identidad/firma productiva antes de distribuir.

Desde 0.1.1, objetivo explícito de actualizar las fichas existentes y versionado de código independiente de la numeración de tiendas. Android toma el ID del APK histórico y mantiene debug separado. iOS/firma/últimos números publicados pendientes de confirmación; ver [continuidad de tiendas](app/continuidad-tiendas.md).

## Git y despliegue

Rama inicial `main`. Los snapshots, SQL y la app histórica están excluidos mediante `.gitignore`. El remoto es `https://github.com/tiagouy/foodtruck-codex.git`; no existe despliegue automático.

Antes de publicar: validar en WordPress local, respaldar base y plugin de producción, empaquetar el tag y subir únicamente el plugin a `wp-content/plugins/`. Verificar la actualización y las migraciones. Un rollback de archivos no revierte cambios de base; cada release con migraciones debe definir su recuperación.

## Hosting

Usar consultas indexadas, paginación, imágenes dimensionadas y caché de lecturas públicas. Medir latencia, errores y límites del compartido antes de reconsiderar alojamiento. AWS queda como alternativa futura.
