# Eventos: alcance y flujo propuesto

Referencias revisadas el 2026-10-05: `/evento/expo-cafe-uruguay-2023/` y `/eventos/` en la copia local. Se conserva la idea de ficha con imagen, descripción y datos prácticos, y listado en tarjetas. Los detalles visuales se verificarán al diseñar el sitio nuevo.

## Requisitos indicados

- La gente puede cargar un evento para revisión.
- El equipo recibe un email y puede revisar una vista previa antes de aprobar.
- Solo los eventos publicados aparecen en el sitio y en el listado de la app.
- Imagen principal adecuada para piezas usadas en Instagram.
- Sección de eventos pasados.
- Filtro por departamento como propuesta para el listado.

## Formulario propuesto

Obligatorios: nombre, descripción, fecha de inicio y fin, departamento, localidad, lugar/dirección, imagen principal y email de quien lo envía. Si no se conoce la hora, permitir indicarlo sin inventar horarios; un evento de un día puede tener la misma fecha de inicio y fin.

Opcionales: resumen breve, coordenadas/mapa, organizador, Instagram/web, enlace de entradas, entrada gratuita o información de precio y foodtrucks participantes. No exigir que el usuario complete campos propios de congresos como speakers, sponsors, agenda o venta interna de tickets.

La categoría y la participación de foodtrucks quedan por definir para el MVP. La relación con foodtrucks debe ser estructurada cuando existan en el catálogo, sin bloquear la sugerencia si todavía no están registrados.

## Revisión y publicación

1. El usuario completa el formulario; propuesta inicial: cuenta verificada, aprovechando la cuenta común de app y sitio. La posibilidad de envío sin cuenta queda pendiente de decisión.
2. Se guarda como pendiente y se confirma la recepción al remitente.
3. Se notifica al equipo por email con enlace al panel, sin publicar automáticamente. Registrar/reintentar fallos de correo; la bandeja del panel es la fuente de pendientes.
4. El equipo puede editar, ver una vista previa privada de la ficha pública y aprobar, pedir correcciones o rechazar.
5. Al aprobar, el mismo registro aparece en la web y en la API de la app; no se copia entre sistemas.
6. Los cambios posteriores enviados por el usuario vuelven a revisión; conservar la versión publicada hasta aprobar el cambio.

Estados editoriales propuestos: borrador, pendiente, requiere correcciones, publicado y rechazado. Cancelación es una condición del evento distinta del estado editorial: debe poder comunicarse a quien ya lo vio.

La vista previa requiere permisos de revisión; un evento pendiente no debe aparecer en búsquedas, API pública ni sitemap. Guardar autor, revisor y fechas de envío/publicación.

## Imagen

Propuesta para acordar: pieza principal vertical 4:5, ideal 1080 × 1350, más adecuada para afiches y tarjetas móviles. Admitir imágenes cuadradas y otros tamaños; generar variantes y mostrar el afiche completo sin recortar fechas/textos. No pedir una segunda imagen horizontal obligatoria.

Validar tipo, peso y dimensiones; generar miniaturas. Para el sitio usar tarjetas consistentes y una vista ampliable en la ficha. Web y app consumen las mismas variantes del archivo.

## Listado público

- Vista inicial: próximos y en curso, ordenados por inicio ascendente.
- Vista Eventos pasados: publicados que terminaron, ordenados por fin descendente, manteniendo sus fichas y enlaces.
- Filtro por departamento entre los 19 departamentos de Uruguay, con opción Todos.
- Búsqueda por nombre/lugar y filtros de fechas como mejoras posteriores.
- Tarjeta: imagen, título, fecha/rango, lugar y departamento; resumen breve si aporta.
- Cancelados claramente identificados, con política de inclusión en listado a definir.
- Resultados paginados y mensajes claros cuando no haya eventos en el departamento.

Usar zona `America/Montevideo`. Próximo/en curso/pasado se calcula por fecha y hora de inicio/fin, sin mover registros a otra tabla ni depender de un cron para actualizar su condición. Para eventos sin hora, considerar el final del día local como cierre.

## Datos compartidos con la app

Listado: ID, slug, título, resumen, imagen/variantes, fechas y horas conocidas, departamento, localidad, lugar, condición temporal, cancelación y enlace web. El detalle agrega descripción, coordenadas, organizador, enlaces y participantes autorizados para publicación. Email privado del remitente y notas de moderación nunca forman parte del contrato público.

La API filtra publicados, permite departamento y vista temporal, y devuelve paginación. Sitio y app aplican las mismas reglas del plugin.

## Tareas del módulo

- [ ] Acordar campos obligatorios, proporción de imagen y requisitos de cuenta.
- [ ] Comparar eventos WordPress/Eventchamp con `tipsSalud` de la app y deduplicar.
- [ ] Diseñar tablas y versión publicada/cambios pendientes.
- [ ] Implementar formulario, medios, validaciones y control de propiedad.
- [ ] Implementar bandeja de revisión, preview privado y emails.
- [ ] Implementar ficha y tarjetas, próximos/pasados y departamentos.
- [ ] Implementar API y probar que no exponga pendientes ni datos privados.
- [ ] Verificar eventos de varios días, sin hora, cancelados, cambios pendientes y fallos de correo.
