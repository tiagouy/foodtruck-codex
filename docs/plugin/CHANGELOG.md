# Changelog

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
