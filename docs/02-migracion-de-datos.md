# Migración de datos históricos

## Inventario confirmado

| Origen | Contenido visible | Tratamiento |
| --- | --- | --- |
| `Bds/usefsgir_wp757.sql` | WordPress histórico, usuarios, contenido y plugins | Referencia; no importar masivamente. |
| `Bds/usefsgir_foodTruck.sql` | Usuarios, categorías, servicios, ofertas, reclamos y administración histórica | Fuente principal para selección y mapeo. |
| `foodtruckuruguay.com/` | Snapshot de WordPress, panel PHP y APIs históricas | Referencia técnica y fuente de medios. |
| `Cosas viejas/foodtruckuruguay-v2/` | Cliente Cordova/PhoneGap | Referencia funcional; no migrar código. |

## Principios

1. Instalar WordPress nuevo y el plugin nuevo antes de importar datos.
2. Trabajar sobre copias de las bases SQL; jamás importar sobre producción histórica.
3. Hacer primero una importación de ensayo que produzca un informe de filas creadas, omitidas, duplicadas y con errores.
4. Conservar un identificador de origen solo para trazabilidad durante la migración, no como identificador público.
5. No importar contraseñas, tokens, llaves, logs ni configuraciones de plugins heredados.

## Mapeo preliminar

| Entidad histórica | Destino nuevo | Nota |
| --- | --- | --- |
| `users` | usuarios WordPress de comunidad | Cuentas de la app; normalizar email, deduplicar y reactivar. Revisar `usuarios` por separado. |
| `servicios`, `marcas`, `vehicles` | foodtrucks | Verificar si las tres entidades se solapan antes de migrar. |
| `categoriasServicio`, `categories` | categorías/rubros de foodtruck | Unificar vocabulario y eliminar duplicados. |
| `tipsSalud` | eventos o contenido editorial | El panel de Eventos usa esta tabla; revisar fechas y vínculos. |
| `offers` | publicaciones de fotos de la comunidad | Conservar autor, foto, descripción, ubicación y puntaje tras validar cada registro. |
| `complaint` | no migrar como contenido | Solo análisis operacional o archivo privado, si es necesario. |

## Fases

### 1. Auditoría

Extraer cantidades, campos, fechas, emails válidos, duplicados y estado de cada tabla. Revisar licencias/consentimiento de fotografías y datos de usuarios.

### 2. Diseño y herramientas

Cerrar el modelo de datos del plugin y construir un comando de importación idempotente: puede ejecutarse más de una vez sin duplicar registros.

### 3. Ensayo local

Crear una instalación nueva local de WordPress, importar una muestra y validar fichas, relaciones, imágenes, roles y enlaces.

### 4. Reactivación

Importar los usuarios elegibles como pendientes de activación, emitir emails con enlace de un solo uso y medir rebotes/activaciones. No enviar correos hasta contar con texto legal, dominio y servicio de correo configurados.

### 5. Migración final y corte

Pausar modificaciones en el sistema viejo, ejecutar la importación final, hacer controles de cantidad y muestras manuales, y publicar la nueva plataforma.

## Criterios acordados de cuentas y fotos · 2026-10-07

- El usuario confirma que la cuenta de la app cuyo email coincide con el administrador WordPress es suya. Usar la cuenta existente y conservar sus permisos; vincular su ID histórico sin duplicarla ni exigir una reactivación que bloquee el acceso administrativo.
- Las 16 cuentas sin email utilizable se descartan del conjunto a migrar. No modificar ni borrar filas del SQL de respaldo original. La auditoría confirmó que ninguna es autora de las 51 publicaciones del snapshot.
- Las dos cuentas que comparten email se unifican en una cuenta de destino. La selección del ID histórico principal y el tratamiento de la equivalencia del segundo ID se resolverán en el importador; no eliminar una identidad de origen arbitrariamente. Ninguna tiene publicaciones entre las 51 actuales.
- Conservar las 51 publicaciones, sus imágenes y la asociación con sus 28 autores: son contenido inicial de la app, no datos de prueba para descartar. La migración no significa publicarlas automáticamente sin conservar/revisar su estado histórico.
- Conservar las fotos de perfil existentes y mantener la posibilidad de subir/cambiar la foto desde la app. Preparar un medio propio referenciado por metadata del usuario WordPress, no depender únicamente de Gravatar. No implementado aún. Auditoría: 1.314 archivos localizados de 1.318 referencias de avatar; revisar las 4 referencias restantes y usar una imagen predeterminada si no se recuperan.
- Las 546 fechas `0000-00-00 00:00:00` son fechas históricas desconocidas, no usuarios sin ID ni fotos sin autor. No descartar esas cuentas ni inventar su fecha original. Separar fecha de creación del usuario nuevo y dato histórico desconocido.

Estos acuerdos definen el filtro y la preservación para la futura migración; todavía no se ejecutó una importación ni se eliminaron cuentas reales.
