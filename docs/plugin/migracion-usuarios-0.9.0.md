# Migración de usuarios · simulación 0.9.0

Esta versión implementa únicamente el lector y el planificador. **No tiene una operación de importación real**. No crea, modifica o elimina usuarios, medios, publicaciones, contraseñas, roles o correos. El SQL original nunca se ejecuta ni se modifica. Los criterios son los acordados en [migración de datos](../02-migracion-de-datos.md).

## Comando

Solo en localhost/entorno local, mediante WP-CLI:

```sh
wp ftuy migrate-users --dry-run --source=/ruta/Bds/usefsgir_foodTruck.sql --media-root=/ruta/foodtruckuruguay.com --sample-size=5
```

`--dry-run`, `--source` y `--media-root` son obligatorios. Sin modo simulación se rechaza el comando. No hay acción web/REST para ejecutarlo. El lector se carga exclusivamente en WP-CLI, admite el formato literal del snapshot UTF-8 de hasta 64 MB y nunca evalúa expresiones ni ejecuta sentencias SQL. Excluye campos de contraseñas, Facebook y push del plan. No analiza `usuarios` como cuentas adicionales.

## Reglas del plan

- Emails comparados tras trim y minúsculas, usando `is_email()` de WordPress y máximo 100 caracteres.
- Las 16 cuentas sin email utilizable no tienen destino; quedan intactas en el respaldo.
- El email duplicado forma una sola identidad de destino. Se propone el menor ID histórico como principal, conservando el segundo como alias privado. No significa borrarlo del respaldo. La implementación futura deberá reservar ambos IDs a la misma cuenta y permitir que sus publicaciones apunten al mismo autor canónico.
- Nuevas cuentas propuestas como suscriptor y pendientes de reactivación. No importar contraseñas ni asumir verificación por el estado antiguo.
- Una coincidencia con WordPress propone solo vincular los IDs, sin sobrescribir nombre, contraseña, rol, permisos o estado de la cuenta existente. La coincidencia administrativa fue confirmada por Santi como propia.
- IDs repetidos/incorrectos en origen se rechazan. Varias cuentas WordPress con el mismo email o un ID ya vinculado a otra identidad bloquean el plan. Identidades ya vinculadas se distinguen de las nuevas; ninguna acción se aplica.
- `0000-00-00 00:00:00` queda como fecha histórica desconocida, no como exclusión ni una fecha inventada.
- Referencias de avatar y publicaciones se buscan dentro de las carpetas recuperadas api/api2. No se descargan medios externos ni se publican automáticamente fotos. El estado histórico de las publicaciones se conserva en el plan.

El informe de consola contiene solo totales y huella del snapshot, sin nombres, emails, claves, IDs individuales ni rutas de fotos. Las filas detalladas del plan se conservan únicamente en memoria para futuros procesos; no se guardaron informes con datos personales en Git.

## Resultado sobre el snapshot · 2026-10-07

Huella SHA-256: `f6ce9ba53fcfd54f2fa2fb4b0d4ee98ad39b6932ae393378e8e3492027f67bda`.

| Resultado | Cantidad |
| --- | ---: |
| Cuentas de origen | 3.677 |
| Excluidas por email no utilizable | 16 |
| Identidades de destino después de unificar el email repetido | 3.660 |
| Nuevos suscriptores propuestos | 3.659 |
| Cuenta existente a vincular, preservando administración | 1 |
| Grupos con email repetido | 1 |
| IDs secundarios a conservar como alias | 1 |
| Conflictos detectados en las cuentas actuales | 0 |
| Fechas históricas desconocidas | 546 |
| Referencias a avatar / archivos localizados | 1.318 / 1.314 |
| Publicaciones / autores | 51 / 28 |
| Publicaciones con correspondencia de autor / archivo localizado | 51 / 51 |

## Muestra propuesta, todavía no importada

Se seleccionan cinco cuentas de forma determinista, incluyendo la administrativa confirmada, dos autores con avatar y publicaciones, la identidad de email duplicado y una cuenta con fecha desconocida. Los casos pueden solaparse: esta muestra tiene tres archivos de avatar y dos fechas desconocidas. Los IDs de la muestra no se muestran públicamente.

## Verificaciones y siguiente paso

`wp eval-file tests/user-migration-plan.php`: datos sintéticos y snapshot real. Comprueba exclusiones, duplicados/alias, conservación de autoría, administrador, conflictos, repetibilidad independiente del orden, parser de literales/escapes y hashes de usuarios, metadata, correo y SQL antes/después. No crea cuentas de prueba en la base: las pruebas del plan usan arrays en memoria y un archivo SQL sintético temporal, eliminado al terminar.

Pendiente: implementar aplicación transaccional/reanudable de la muestra, reservas de alias, copia/redimensionado de avatars, modelo de publicaciones y sus medios, verificación de reactivación con correo capturado y asociación final de IDs WordPress. Ante un conflicto nuevo debe revalidar y detenerse antes de escribir. No importar ni activar producción desde esta versión.
