# Formulario de eventos · 0.3.0

El formulario de sugerencias y el de revisión en WordPress comparten campos y validación. El resumen de las tarjetas se genera de la descripción, no se pide al usuario.

## Horarios

- Mismo horario todos los días: apertura y cierre opcionales.
- Horarios distintos por día: las fechas seleccionadas generan filas independientes; los campos vacíos quedan a confirmar.
- Se guardan en `ft_events.schedule_json` y se exponen como `schedule` en la API. Los campos `start_time` y `end_time` siguen representando los límites para la agenda.
- Las horas de cierre son del mismo día; el formulario no modela todavía jornadas que terminan después de medianoche. No introducir un cierre anterior a la apertura.
- Máximo 366 días para horarios individuales. Los eventos de mayor duración pueden usar horario común.

## Ubicación y Google

Nombre del lugar primero; luego buscador de Google y dirección editable. Al elegir una sugerencia de Uruguay se guardan latitud/longitud y se completan departamento/localidad según la respuesta. Revisar esos datos antes de enviar: Google puede omitir componentes administrativos. Cambiar manualmente la dirección borra las coordenadas anteriores para no guardar una ubicación incorrecta.

La clave se obtiene de la opción propia `ftuy_google_maps_key`, con alternativa al ajuste existente de Eventchamp `option_tree.googlemapapi`. No se copia al repositorio. Es una clave de cliente: debe restringirse en Google Cloud por dominios y servicios apropiados.

**Pendiente externo:** el 05/10/2026 Google devolvió 403 porque **Places API (New)** está deshabilitada en el proyecto de la clave actual. Habilitarla en el mismo proyecto, verificar facturación y restricciones para el dominio final y `http://localhost:8888/*`. No se modificaron permisos ni facturación. El formulario puede enviarse con dirección manual mientras tanto.

Referencia oficial: [Autocomplete de Places en Maps JavaScript](https://developers.google.com/maps/documentation/javascript/place-autocomplete-new).

## Entradas y compatibilidad

`entry_type`: `free` o `paid`. Gratis limpia enlaces de venta y muestra Gratis; Con entrada exige enlace HTTP(S). Las propuestas antiguas pueden conservar tipo vacío: no se cambia el precio ni se asume gratuidad. Al editarlas, elegir la clasificación correcta. El campo `price` histórico se conserva aunque ya no se solicita como texto libre.

El esquema 3 agrega columnas sin borrar los eventos anteriores. Las propuestas pendientes anteriores también conservan sus datos durante la revisión.
