# Visión y arquitectura inicial

## Propósito

Foodtrucks Uruguay se reconstruirá como una plataforma donde la comunidad puede descubrir foodtrucks y eventos, proponer información y administrar su presencia. La calidad del directorio se protege mediante revisión y aprobación desde WordPress.

## Alcance inicial (MVP)

1. Directorio público de foodtrucks, con ficha, fotos, rubros, redes, zona y estado de publicación.
2. Agenda pública de eventos, con fecha, lugar, ubicación y foodtrucks participantes.
3. Registro, inicio de sesión y perfil de cuenta.
4. Formulario para dar de alta/reclamar un foodtruck y sugerir un evento.
5. Bandeja de moderación y gestión completa desde el panel de WordPress.
6. Una API REST versionada que sirva tanto al sitio como a la futura app.

7. Publicaciones de fotos de la comunidad vinculadas a sus autores y administrables desde WordPress. Los detalles de puntajes y moderación se definirán al cerrar el MVP.

Publicidad y notificaciones se evaluarán al definir las etapas del relanzamiento.

## Decisión de arquitectura

Confirmado el 2026-10-05: continuar en el hosting compartido y usar tablas propias dentro de la base WordPress. Ver `03-versionado-y-operacion.md` para releases.

**Se usará una instalación nueva de WordPress como plataforma central, con una sola base de datos de WordPress y un plugin propio llamado `Foodtrucks UY Core`.**

No recomiendo una base separada al comienzo. Mantener los datos propios del producto en tablas del plugin dentro de la misma base permite:

- un único sistema de usuarios, permisos, copias de seguridad y moderación;
- administración nativa dentro de WordPress, sin mantener el `admin/` PHP histórico;
- una API coherente bajo el mismo dominio;
- migraciones y operación significativamente más simples.

Esto no significa guardar todo como `postmeta`. El plugin creará tablas propias con prefijo de WordPress para los datos relacionales y geográficos. WordPress conservará `wp_users`, sus roles, medios y contenido editorial. Si el producto necesitara servicios independientes o una carga que WordPress no resuelva, esas tablas se podrán extraer más adelante mediante la API, sin cambiar a los clientes.

## Componentes

```text
Sitio WordPress        App móvil nueva
       |                     |
       +------ API REST v1 --+
                    |
       Plugin Foodtrucks UY Core
                    |
          Base de datos WordPress
```

### Plugin propio

Responsabilidades:

- registrar roles y capacidades (`foodtruck_owner`, moderador, administrador);
- definir tablas, validaciones, flujos de aprobación y auditoría;
- ofrecer paneles de administración dentro de `/wp-admin`;
- exponer `wp-json/foodtrucks-uy/v1/...`;
- controlar permisos de cada acción y el acceso de cada propietario a sus datos;
- importar datos históricos sin conservar su estructura o nomenclatura.

### API única

No habrá `api.`, `api2.`, `apiapp` ni endpoints PHP paralelos. La única interfaz pública será WordPress REST API, inicialmente bajo:

```text
https://foodtruckuruguay.com/wp-json/foodtrucks-uy/v1/
```

Ejemplos de recursos iniciales: `foodtrucks`, `events`, `event-suggestions`, `me`, `account/reactivation` y `media`.

La app y el propio frontend web consumirán el mismo contrato. Las acciones autenticadas usarán el mecanismo de token que se defina dentro del plugin; las contraseñas no se expondrán ni se reutilizarán desde la base histórica.

## Modelo de datos de partida

Las tablas definitivas se diseñarán antes de implementar, pero la separación conceptual será:

| Concepto | Titular | Relación principal |
| --- | --- | --- |
| Cuenta | usuario de WordPress | puede administrar uno o más foodtrucks |
| Publicación de comunidad | plugin | foto, descripción, autor y ubicación; vínculos a evento/foodtruck por definir |
| Foodtruck | plugin | tiene propietario, categoría, ubicación y medios |
| Evento | plugin | tiene lugar, fechas, estado y foodtrucks participantes |
| Participación | plugin | une evento y foodtruck; permite estado/horario propio |
| Sugerencia | plugin | alta o cambio propuesto por una cuenta o visitante |
| Moderación | plugin | conserva estado, autor, revisor y fecha de revisión |

Las imágenes usarán la biblioteca de medios de WordPress; las tablas del plugin guardarán las referencias, no copias de archivos.

## Cuentas y usuarios heredados

El origen de las cuentas de comunidad es la app histórica (`users`). Las cuentas administrativas de WordPress se revisarán por separado; no se asume que existían cuentas públicas del sitio.

Se migrarán cuentas solamente después de depurarlas y de confirmar la base de origen. La propuesta es importar nombre, email y los datos permitidos, pero **no** las contraseñas antiguas. Cada persona recibirá un email de reactivación para establecer una contraseña nueva y aceptar las condiciones actuales.

No se deben activar automáticamente cuentas duplicadas, sin email válido ni datos de consentimiento dudosos. Esos casos se registrarán en una cola de revisión.

## Límites con lo histórico

- El directorio `foodtruckuruguay.com/admin/` se considera legado y se retira del producto nuevo.
- Las APIs `api/`, `api2/` y `apiapp/` no se extenderán.
- Los nombres `offers`, `servicios`, `categories` y similares solo se usan para mapear importaciones; no aparecen en el sistema nuevo.
- El snapshot contiene posibles secretos y datos personales: no debe subirse a un repositorio público ni desplegarse tal cual.

## Decisiones aún abiertas

1. Qué campos serán obligatorios al crear un foodtruck y un evento.
2. Si la sugerencia de evento será anónima, con cuenta, o ambas.
3. Criterio de verificación para que un usuario reclame un foodtruck existente.
4. Tecnología de la app móvil nueva y si el MVP puede comenzar como web responsiva/PWA.
5. Modelo de ingresos: perfiles destacados, publicidad local, comisión por eventos/reservas u otro. Esto debe definirse antes de crear pagos o planes.
