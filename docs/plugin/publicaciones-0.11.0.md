# Publicaciones de la comunidad — 0.11.0

## Administración

WordPress → Foodtrucks UY → Publicaciones (`admin.php?page=ftuy-publications`). Capacidad propia `manage_ft_publications`, concedida solo al rol administrador. No se otorgan permisos de moderación a suscriptores.

- Listado paginado con foto, texto/ubicación, autor, fecha y estado.
- Filtros Todas, Pendiente, Publicada, Despublicada y Denunciadas (con denuncias abiertas).
- Detalle privado con foto, autor y fecha originales.
- Edición de texto, dirección, coordenadas y estado. Botones Guardar, Despublicar o Publicar, con nota interna opcional.
- Autor y archivo de imagen no se modifican desde este formulario, para conservar la atribución. No existe formulario web para subir publicaciones.
- Historial de creación/ediciones/denuncias: administrador, fecha UTC, acción y nota. La BD conserva snapshots antes/después.
- Registro manual de denuncias recibidas por email, con motivo y estado abierta/revisada. Marcar una denuncia como revisada no republica el contenido. Registrar una denuncia tampoco lo oculta automáticamente: el administrador decide el estado.

Guardar valida permisos y nonce. Versiones de registro evitan sobrescribir un formulario viejo después de otra moderación. Las operaciones y el historial usan nuevas tablas InnoDB con transacción; si falla el historial, la edición se revierte. No se modifica el motor de las tablas antiguas de WordPress.

## Modelo

Tablas propias con prefijo WordPress:

- `ft_publications`: autor WordPress, adjunto, texto, dirección, latitud/longitud opcionales, estado, versión, fechas y campos de identidad histórica.
- `ft_publication_reports`: motivo, estado, administrador que registró/revisó y fechas.
- `ft_publication_audit`: actor, acción, snapshots y nota.

Estados: `pending`, `published`, `unpublished`. No hay borrado definitivo en esta interfaz. Despublicar conserva foto/autor y permite volver a publicar. Para publicar, el archivo debe existir, ser una imagen en categoría `publicaciones` y pertenecer al autor. Los adjuntos se almacenarán en `/media/publicaciones/` mediante el almacenamiento ya preparado.

## API de lectura para la nueva app

```text
GET /wp-json/foodtrucks-uy/v1/publications?page=1&per_page=20
GET /wp-json/foodtrucks-uy/v1/publications?author=ID_WORDPRESS
GET /wp-json/foodtrucks-uy/v1/publications/ID
```

Solo devuelve `published`, incluso al consultar por autor. Pendientes/despublicadas/inexistentes devuelven 404 en detalle. Respuestas publicadas llevan `Cache-Control: no-store`. Paginación máxima 50. No expone emails, IDs de usuario históricos, denuncias, notas internas ni historial. Autor público: ID WordPress, nombre y avatar propio si existe.

**No está conectado a la app vieja** ni a sus APIs anteriores. Para que la moderación se refleje en la nueva app, esta debe usar nuestros endpoints y respetar/revalidar los cambios de estado. El archivo físico conserva su URL pública: despublicar no retira automáticamente enlaces directos, copias descargadas, cachés externos o capturas. Si un contenido requiere retirada física, definir ese procedimiento aparte, sin confundirlo con el control de visibilidad del feed.

No existen endpoints públicos POST para subir publicaciones o denuncias en esta versión. La futura app tendrá subida autenticada y recepción de denuncias con controles de abuso y aviso por email; el flujo antiguo de denuncia por email no se altera ahora.

## Datos reales y verificaciones

La sección inicialmente está vacía: las 51 publicaciones históricas **todavía no se importaron**. Fotos/SQL antiguos intactos. Tampoco se importaron los 3.655 usuarios que faltaban tras la muestra.

- 31 comprobaciones de modelo/API: permisos, estados, edición, sanitización, invariantes de autor/foto, denuncias, historial, versiones, paginación y rollback si falla el historial.
- 12 comprobaciones HTTP: pantalla administrativa real mediante sesión temporal, nonce/CSRF, bloqueo a suscriptores, despublicar/republicar, detalle y feed por autor, denuncia y revisión.
- Fixtures, medios y sesiones temporales eliminados. Sin modificar credenciales del administrador ni publicar contenido real.
- El navegador de revisión requería login, por lo que no se completó inspección visual autenticada en esa sesión. No se solicitaron ni introdujeron credenciales. Los formularios autenticados sí se verificaron por HTTP.

Próximo paso: migrar el resto de usuarios y después importar las 51 publicaciones con sus propietarios, fechas, estados e identificadores/URLs antiguos; revisar campos históricos adicionales antes de retirar APIs/rutas anteriores. No inferir asociaciones con eventos o foodtrucks durante esta etapa.
