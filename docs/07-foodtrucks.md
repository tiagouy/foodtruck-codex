# Foodtrucks · definición acordada

## Alcance de esta etapa

Primero definir y probar la ficha con **tres o cuatro foodtrucks**. Luego se cargarán todos nuevamente con datos actualizados. Los SQL y archivos antiguos son referencia para la muestra, no una autorización para importar todo el catálogo ni para publicar negocios antiguos como activos.

## Campos acordados

1. Nombre, descripción y qué sirven: tres campos independientes.
2. Uno o varios rubros gastronómicos.
3. Departamento y localidad base: no representan ubicación actual del foodtruck.
4. Logo (500×500 px) y foto del foodtruck (900×900 px), sin portada ni galería; no confundirlos con las fotos de usuarios.
5. WhatsApp.
6. Instagram.
7. **Sin email en la ficha ni en sus datos públicos.** El email privado de la cuenta WordPress sigue perteneciendo a la cuenta, no al foodtruck.
8. Modalidades mediante checkboxes independientes: eventos, catering/privados y punto fijo. Se puede seleccionar más de una.
9. Cuenta responsable, estado de revisión y última actualización. Son datos de gestión: el usuario no elige su estado de aprobación ni modifica la fecha manualmente. No publicar identidad de la cuenta responsable en la API pública.

Facebook y sitio web no forman parte de esta primera ficha. La estética queda para una etapa posterior.

## Nombres nuevos y claros

Mantener la convención del plugin: prefijo WordPress + `ft_`, entidades descriptivas y campos coherentes. Propuesta de tablas para implementar:

- `ft_foodtrucks`: fichas; `name`, `description`, `food_offering`, `department`, `locality`, `whatsapp`, `instagram`, `serves_events`, `serves_private_events`, `has_fixed_location`, `responsible_user_id`, `status`, `created_at`, `updated_at`.
- `ft_cuisine_categories`: rubros gastronómicos, no nombres de negocios.
- `ft_foodtruck_cuisines`: relación de un foodtruck con varios rubros.
- `ft_foodtruck_images`: referencias a medios WordPress con rol `logo`/`truck_photo` y orden. Los roles históricos se interpretan de forma compatible sin eliminar medios originales.
- `ft_foodtruck_reviews`: propuestas y revisión; conservar la ficha pública hasta aprobar cambios, igual que en eventos.

No crear otra tabla de cuentas: la cuenta responsable se referencia mediante el ID de usuario WordPress. Los nombres históricos `servicios`, `offers`, `categories`, `speakers` no se reutilizan como entidades nuevas ni se renombran dentro del respaldo original.

Para la prueba inicial son obligatorios nombre, descripción, qué sirven, departamento/localidad, al menos un rubro y una modalidad. WhatsApp, Instagram y medios son opcionales. Desde 0.7.0 se guardan hasta dos imágenes: logo 500×500 px/120 KB y foto del foodtruck 900×900 px/300 KB. Recorte cuadrado centrado, JPEG comprimido y metadatos 72 dpi; transparencias sobre blanco. El archivo de entrada puede ser JPG/PNG/WebP hasta 5 MB y 40 MP, pero no se incorpora su original grande a WordPress: solo el resultado reducido. Una dirección de punto fijo, sus horarios, la participación en eventos y las publicaciones de comunidad se definirán por separado.

## Estado

- [x] Auditoría de las dos fuentes históricas.
- [x] Campos y alcance de muestra acordados.
- [x] Implementar tablas, administración, moderación, preview privado y API pública de lectura dentro del plugin (0.4.0).
- [x] Cargar cuatro fichas históricas como pendientes: Robin, Shufa Deli, Route y Scaronne.
- [x] Consulta opcional de nombre/foto pública de Instagram con confirmación explícita, carga manual alternativa y copia del logo a medios al guardar.
- [x] Directorio y detalle web con filtros; vista privada de muestras/propuestas sin publicación automática (0.5.0).
- [ ] Revisar y confirmar datos/imágenes de las cuatro muestras; no están publicadas automáticamente.
- [ ] Validar la ficha antes de continuar con catálogo completo y estética.
- [x] Formulario web `/agregar-foodtruck/` y `/mis-foodtrucks/` para cuentas existentes, con edición propia y revisión (0.6.0).
- [x] Pantallas web de registro/confirmación, login, perfil/nombre, recuperación y reactivación; API compartida de solicitudes y campo interno para ID histórico (0.8.0). Solo cuentas de prueba/local.
- [ ] Migración de usuarios e IDs vinculados a fotos y autenticación móvil. No se importaron usuarios reales todavía.
