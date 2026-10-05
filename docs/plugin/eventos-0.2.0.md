# Eventos 0.2.0: operación local

## Instalación y datos

El plugin está activado en `/Users/Santi/CLIENTES/_localhost/foodtruck/wp-content/plugins/foodtrucks-uy-core/`. El código versionado está en `plugins/foodtrucks-uy-core/` de este repositorio; la instalación MAMP usa una copia que debe sincronizarse después de editar.

Tablas creadas con el prefijo de la instalación: `wpcd_ft_events`, `wpcd_ft_event_reviews` y `wpcd_ft_event_mail`. Esquema versión `1`, con tablas InnoDB. No se eliminan datos al desactivar el plugin.

Migración local realizada para los cuatro registros publicados más recientes por fecha de creación de WordPress:

| ID original | Evento | Fechas originales |
| --- | --- | --- |
| 3051 | Expo Café Uruguay 2023 | 12–13 agosto 2023 |
| 3047 | Street Food Festival - GarageGourmet 2023 | 15–16 abril 2023 |
| 3020 | Expo Café Uruguay 2022 | 3–4 septiembre 2022 |
| 3015 | Ollas del Mundo - GarageGourmet 2022 | 11–12 junio 2022 |

Los originales de Eventchamp permanecen intactos. El plugin muestra los migrados en sus mismas URLs; los demás detalles siguen utilizando Eventchamp. El listado `/eventos/` ahora muestra únicamente la tabla propia: los cuatro importados están en Pasados. La descripción de Ollas menciona 4–5 junio mientras sus campos de fecha indican 11–12 junio; se conservó el dato original y debe revisarse editorialmente.

## Páginas y administración

- `/eventos/`: próximos y en curso (inicialmente vacío porque la muestra es histórica).
- `/eventos/pasados/`: histórico con filtro de departamento.
- `/evento/{slug}/`: ficha con afiche ampliable, datos y enlace al mapa.
- `/sugerir-evento/`: exige sesión WordPress; formulario y upload.
- `/mis-eventos/`: estado, mensajes del revisor y propuesta de cambios.
- Panel WordPress → Foodtrucks UY → Eventos: crear/editar, pendientes y moderación.
- Guardar para revisar / preview conserva una propuesta; abrir preview muestra los datos guardados. El preview requiere sesión con `manage_ft_events` y nonce.
- Panel → Foodtrucks UY → Correos: capturas locales, estado y reintentos de fallos.

Las pantallas públicas del módulo usan una plantilla autónoma del plugin: no cargan el header/footer, constructor visual ni scripts de Eventchamp. Es un primer diseño funcional inspirado en la distribución existente. El resto del sitio conserva su tema. No se cambió de tema.

Los cambios de una ficha publicada se guardan como propuestas; el público sigue viendo la versión aprobada. Aprobación, corrección y rechazo registran autor/revisor y fecha. Los afiches aceptan JPG/PNG/WebP hasta 5 MB y 40 megapíxeles.

## API de lectura

`GET /wp-json/foodtrucks-uy/v1/events?view=past&department=Montevideo&page=1&per_page=12`

`GET /wp-json/foodtrucks-uy/v1/events/{slug}`

`view` acepta `upcoming` o `past`. Devuelve `items`, `total`, `page`, `pages`; cada evento tiene datos públicos explícitos. No expone pendientes, emails, notas de revisión ni autor/ID histórico. La autenticación móvil y endpoints de envío por app se incorporarán con el módulo de cuentas.

## Importación repetible

Con WordPress local y plugin activo, ejecutar WP-CLI:

```sh
wp ftuy import-events --limit=4 --dry-run
wp ftuy import-events --limit=4
```

El importador conserva los registros ya migrados y reporta los que necesitan revisión; nunca sobreescribe las modificaciones del sistema nuevo. Un registro con campos obligatorios incompletos no se importa hasta resolverlos. El resto del histórico y `tipsSalud` aún no se migraron.

## Verificación

Pruebas ejecutadas con el PHP/WP-CLI de MAMP:

```sh
wp eval-file /Users/Santi/CLIENTES/_repo-git/foodtruck-codex/tests/events-integration.php
wp eval-file /Users/Santi/CLIENTES/_repo-git/foodtruck-codex/tests/events-http.php
```

Las pruebas crean cuentas, propuestas y medios temporales en localhost, y limpian exclusivamente sus fixtures. Cubren validación, permisos, publicación, cambios pendientes, rechazo, cancelación, filtros, paginación, importación repetible, correo capturado, CSRF, upload HTTP y preview privado. También se verificaron las imágenes y la distribución de escritorio/móvil en navegador.

## Pendientes para próximas etapas

- Migración del resto de los eventos y comparación con la base antigua de la app.
- Registro/verificación/reactivación común de usuarios; por ahora se usan sesiones WordPress existentes. No se habilitó registro público en la copia sin acordarlo.
- Relación estructurada con foodtrucks participantes y sus pantallas.
- Integrar la identidad visual definitiva, header/footer con el resto del sitio y mapa embebido si se acuerda; ahora hay enlace a ubicación.
- Integración de SEO/sitemap y caché para las tablas propias; las plantillas actuales tienen título/canonical propio y noindex en pantallas privadas.
- Paginación de la bandeja de revisión (primeros 100 pendientes) y políticas de conservación de correos/medios.
- Validación en el compartido y configuración real de correo. `wp_mail` exitoso indica aceptación por el transporte, no entrega garantizada al destinatario.

En MAMP se activó log de desarrollo con display de errores desactivado para evitar que avisos de WPBakery/Eventchamp antiguos contaminen las páginas y JSON. Los componentes antiguos siguen requiriendo revisión de compatibilidad; no se editaron sus archivos.
