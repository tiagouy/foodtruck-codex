# Nombre y apellido históricos — 0.13.1

El SQL original conserva `firstname` y `lastname` separados. La migración inicial los había combinado únicamente en `display_name`. Ahora las importaciones nuevas también completan `first_name` y `last_name` nativos de WordPress.

Para las cuentas ya importadas:

```text
wp --user=ADMIN_ID ftuy migrate-user-names --dry-run --source=/ruta/backup-app.sql
wp --user=ADMIN_ID ftuy migrate-user-names --apply --source=/ruta/backup-app.sql --backup=/ruta/respaldo-wp.sql
```

Solo local y administrador. Identifica al usuario mediante su ID histórico principal y verifica la huella del SQL de su migración; no aplica nombres de alias de cuentas fusionadas. Completa exclusivamente campos vacíos y omite administradores. No modifica nombre de usuario interno, nombre público, contraseña, email, estado de reactivación, avatar ni publicaciones. No envía correos. El registro web/app sigue con un solo campo; separar esos formularios es un cambio independiente.

Aplicado en local el 7 de octubre de 2026: 3.659 nombres y 3.649 apellidos. Diez cuentas tienen apellido vacío en el origen. Respaldo privado previo: `Bds/backups-local/food-before-user-names-2026-10-07.sql`, permisos 0600, excluido de Git.

Verificaciones: siete pruebas con cuentas ficticias; segunda simulación sin cambios pendientes; huellas idénticas antes/después de la tabla de usuarios, metadata ajena a nombre/apellido y las 51 publicaciones. Las tablas de usuarios MyISAM no permiten prometer rollback global; el proceso es reanudable y conserva el respaldo.
