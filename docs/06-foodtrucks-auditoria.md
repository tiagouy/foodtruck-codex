# Foodtrucks: auditoría de app y WordPress

Fecha: 2026-10-05. Inspección de los SQL descargados y del código histórico, sin importar ni modificar las bases. Cantidades corresponden a esos snapshots, no a un censo actual de negocios activos.

## Fuentes confirmadas

| Origen | Entidad | Cantidad | Interpretación |
| --- | --- | --- | --- |
| App: `servicios` | Fichas de foodtruck | 16 | Nombre, rubro, oferta gastronómica, departamento, teléfono, foto, coordenadas, descripción, Facebook. |
| App: `categoriasServicio` | Rubros gastronómicos | 13 | Chivitos, hamburguesas, cafés, etc. Revisar errores ortográficos y de codificación. |
| App: `categories` | Opciones para asociar fotos | 23 | Nombres de negocios mezclados con eventos/organizadores y opciones genéricas; no son rubros. |
| App: `offers` | Publicaciones de comunidad | 51 | Autor, texto, foto, ubicación, puntaje, fecha y estado. No son promociones comerciales pese al nombre de la tabla. |
| WordPress: `wpcd_posts`, tipo `speaker`, publicados | Fichas web | 8 | Robin FoodTruck, Shufa Deli, Route Food Truck, Scaronne Pizza, El Bunker Gourmet, Glamburger, Zona058, Angus Grill. |

El cliente Cordova llama a `apiapp` acciones `marcas` y `marca`; ambas consultan `servicios`. Su lista de fotos y filtros consulta `categories`. El panel `admin/views/services` edita `servicios`.

## Diferencias que importan

- **`servicios.direccion` significa «Sirven».** Tanto el panel como la app lo presentan así. Los seis valores no vacíos distintos de `-` describen comida. No migrarlo como dirección postal; mapearlo a oferta gastronómica.
- Las fichas app tienen departamento y coordenadas, pero no un dueño asociado ni Instagram como campo propio.
- WordPress reutiliza el tipo Eventchamp `speaker`. Contactos: `speaker_phone`, `speaker_email`, `speaker_website`; imágenes: `speaker-profile-photo` y `_thumbnail_id`; descripción en `post_content`, resumen en `speaker-short-biography`; Instagram y Facebook en `social-links` serializado.
- Las ocho fichas web tienen vacío `speaker_address`; hay ubicaciones o días mencionados dentro de algunas descripciones, pero no un modelo estructurado de puntos de venta.
- Tres coincidencias a revisar y unir: Route Food Truck, Glamburger y Scaronne Pizza / Scaronne Pizza Móvil. Route tiene teléfonos distintos; Glamburger tiene teléfono en app y vacío en web. Scaronne conserva `speaker_company=Robin FoodTruck`, un resumen copiado y un slug heredado de Robin. No considerar esos campos una identidad fiable.
- `offers.idCategory` apunta a `categories.idCategory`, **no** a `servicios.idServicio`. Para fotos hace falta un mapeo independiente por origen; IDs numéricos iguales no significan el mismo negocio.
- Hay 15 publicaciones vinculadas a las opciones genéricas «No esta en la Lista» o «Algun Evento o FoodTruck». Conservarlas sin inventarles un foodtruck.
- Existen valores `event_speakers` en 25 filas de metadata, incluyendo referencias antiguas a IDs que no forman parte de las ocho fichas publicadas. Verificar el evento, los destinos y posibles datos de demostración antes de migrar participaciones.
- Las coordenadas históricas no demuestran ubicación actual ni punto de venta. Separar base del negocio, ubicaciones recurrentes y participación en eventos.

## Medios recuperables

Se localizaron las 16 imágenes de fichas app en `admin/views/services/uploads/`, las ocho fotos de perfil web en `wp-content/uploads/` y las 51 fotos de publicaciones bajo `api2/offers/uploads/{idOffer}/` o `api/offers/uploads/{idOffer}/`. Existencia del archivo no implica revisión visual, vigencia o autorización para nueva publicación.

## Modelo propuesto, todavía no implementado

Una ficha canónica en las tablas propias del plugin dentro de la BD de WordPress; sitio y app consultan la misma entidad:

- `ft_foodtrucks`: nombre, slug, descripción, oferta gastronómica, departamento/localidad base, logo y portada, WhatsApp, Instagram, modalidades de servicio, cuenta responsable, estado y actualización. **Sin email en la ficha.** Campos acordados en [07-foodtrucks.md](07-foodtrucks.md).
- Rubros gastronómicos independientes, con varios rubros por foodtruck.
- Dueño/cuenta y solicitudes de reclamación de fichas, con verificación administrativa. No deducir el propietario por coincidencia de nombres o emails públicos.
- Ubicaciones/puntos recurrentes separados de la ficha; dirección y mapa solo cuando corresponda. Un negocio móvil no necesita dirección fija.
- Relación eventos–foodtrucks: participación confirmada, no ubicación permanente.
- Fotos de la comunidad separadas de las imágenes oficiales, con autor, estado de moderación y vínculos opcionales al foodtruck/evento.
- Mapeo de IDs y URLs de origen para importación idempotente y redirecciones de fichas web.

Publicaciones y reclamaciones deberían usar revisión, preview y notificaciones como los eventos. La información privada de cuentas y moderación no debe salir en la API pública.

## Orden sugerido

1. Acordar campos y modalidades (móvil, punto fijo, catering/privados).
2. Normalizar y comparar las fuentes; resolver conflictos de las tres coincidencias y las opciones de fotos sin ficha.
3. Implementar tablas, administración y API dentro del plugin.
4. Ensayo local: cargar solo tres o cuatro fichas de prueba. Después los foodtrucks deberán cargarse de nuevo, con datos actualizados; no importar automáticamente el catálogo viejo.
5. Formulario para subir foodtruck y reclamar ficha histórica con revisión.
6. Listado, detalle y relación con eventos. Resolver estética y shortcodes de presentación después.

No se inició la importación de foodtrucks, no se cambiaron fichas ni se contactó a sus titulares.
