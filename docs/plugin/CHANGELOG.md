# Changelog

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
