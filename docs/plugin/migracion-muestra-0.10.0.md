# Muestra de usuarios — 0.10.0

## Resultado local — 2026-10-07

Aplicada la muestra determinista de cinco identidades prevista en 0.9.0:

- Cuatro suscriptores nuevos con contraseña aleatoria desconocida y estado `legacy_pending`.
- Una cuenta administrativa existente vinculada al ID histórico. Contraseña, nombre, roles, fecha de alta y estado conservados; comprobación por huella antes/después.
- Tres fotos de perfil copiadas y reducidas a JPEG 500×500, hasta 120 KB, con fondo blanco, sin modificar fuentes. Guardadas en `/media/perfiles/{UUID}/` y asociadas al propietario.
- Un grupo de email duplicado convertido en una cuenta con ID principal más alias. Ambos IDs resuelven al mismo usuario mediante `FTUY_Accounts::legacy_owner()`.
- Dos fechas originales desconocidas conservadas como tales; no inventamos una fecha histórica. `user_registered` de usuarios nuevos refleja la creación en WordPress; `ftuy_legacy_created_at` y `ftuy_legacy_date_unknown` conservan la procedencia.
- Sin generación ni envío de correos durante la importación. Las solicitudes de reactivación locales se capturan en Usuarios → Correos de cuentas.
- Las 51 publicaciones NO están importadas. Sus archivos y relación con los autores siguen conservados en las fuentes. Dos cuentas de la muestra son autoras de publicaciones.

Segunda ejecución: cero usuarios/avatares nuevos, cinco cuentas reconocidas y tres avatares reutilizados. Quedan **3.655 suscriptores por importar** del plan original, no contar nuevamente los cuatro de esta muestra.

## Ejecución

```text
wp --user=ADMIN_ID ftuy migrate-users --apply-sample \
  --source=/ruta/al/backup-app.sql \
  --media-root=/ruta/al/sitio-antiguo \
  --sample-size=5 \
  --backup=/ruta/al/respaldo-wordpress.sql
```

Solo local, usuario con `manage_options`, hasta cinco cuentas, respaldo de WordPress presente y distinto del SQL fuente. `--dry-run` sigue disponible; no combinarlo con `--apply-sample`. No hay importación masiva ni endpoint público para aplicar planes. La validación del backup es básica (archivo legible con tabla de opciones): el operador debe producir un respaldo completo y comprobar que puede restaurarse; el importador no ejecuta el backup ni el SQL fuente.

El registro `ftuy_user_migration_{ID antiguo}` en opciones conserva hash de fuente, ID de destino y estado `running`/`complete`. No guarda emails, claves ni tokens. `ftuy_user_migration_lock` evita dos importadores simultáneos y se libera con `finally`; si el proceso termina abruptamente puede quedar un bloqueo que debe revisar un administrador antes de retirarlo. No quitar un bloqueo de una ejecución activa.

Las tablas actuales son MyISAM: **no se promete rollback transaccional**. Si una foto falla, la cuenta y su vínculo pueden haber quedado creados; corregir la causa y retomar con la misma fuente, sin borrar/recrear identidades. Un respaldo previo permite recuperación manual. Las reservas privadas `ftuy_legacy_owner_{ID}` impiden usar un principal/alias en dos cuentas; conflictos se detienen para revisión.

## Perfiles y reactivación

La foto propia se registra como adjunto de WordPress y se referencia desde `ftuy_profile_image_id`. Se usa mediante el filtro nativo de avatar (por ejemplo en Usuarios), siempre comprobando que el adjunto pertenece al usuario y a la categoría perfiles. No se depende de Gravatar cuando existe foto propia. La API de carga/cambio desde la nueva app sigue pendiente; la web no recibe un formulario de subida de fotos de comunidad.

La prueba de reactivación utilizó una cuenta sintética, no cambió contraseñas reales: solicitar enlace local, validar llave, elegir contraseña, activar y autenticar, conservando IDs y avatar. Las cuatro cuentas reales quedan pendientes para sus propietarios; no se les ha restablecido la contraseña durante QA. Tu administrador no requiere reactivación.

## Verificación y respaldo

- 18 comprobaciones sintéticas: creación, permisos, duplicados, aliases, compresión/avatar, reactivación, límite de muestra, autorización y recuperación tras fallo de imagen; fixtures limpiadas.
- 22 comprobaciones sobre muestra real: administrador intacto, sin correo nuevo, solo cuatro altas, estados/permisos, alias y tres avatares HTTP 200; hashes de SQL/avatares fuente/51 imágenes sin cambios.
- 20 comprobaciones del plan y 24 de regresión de cuentas.
- Respaldo anterior a la muestra: `Bds/backups-local/food-before-users-sample-2026-10-07.sql`, privado y excluido de Git. Contiene credenciales/datos personales: no compartir ni subir al repositorio.

Próximos pasos: importación completa controlada de usuarios, modelo/importación de las 51 publicaciones con sus permisos/estados y URLs históricas, y autenticación/subida de perfil de la app. No abrir registro en producción antes de resolver la migración para evitar cuentas antiguas creadas como nuevas.
