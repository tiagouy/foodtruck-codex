# Migración completa local — 0.13.0

## Procedimiento

Respaldo privado previo: `Bds/backups-local/food-before-full-migration-2026-10-07.sql`, excluido de Git. No se ejecuta el SQL antiguo: el lector extrae únicamente los campos necesarios y valida sus literales.

Usuarios:

```text
wp --user=ADMIN_ID ftuy migrate-users --apply-all \
  --source=/ruta/al/backup-app.sql --media-root=/ruta/al/sitio-antiguo \
  --backup=/ruta/al/respaldo-wordpress.sql
```

Publicaciones, después de completar sus propietarios:

```text
wp --user=ADMIN_ID ftuy migrate-publications --dry-run \
  --source=/ruta/al/backup-app.sql --media-root=/ruta/al/sitio-antiguo
wp --user=ADMIN_ID ftuy migrate-publications --apply \
  --source=/ruta/al/backup-app.sql --media-root=/ruta/al/sitio-antiguo \
  --backup=/ruta/al/respaldo-wordpress.sql
```

Ambos comandos solo funcionan localmente y con permisos administrativos para aplicar. Usuarios por lotes de 50, reservas de IDs, alias y registro por identidad, retomando cuentas ya creadas sin duplicar. Se conserva contraseña/nombre/rol de las cuentas existentes. Usuarios nuevos: suscriptor, contraseña aleatoria desconocida, `legacy_pending` hasta verificar email y elegir contraseña. No se copian MD5, tokens sociales ni credenciales antiguas. Sin enviar correos reales.

La importación de muestra conserva su límite de cinco: `--apply-all` es un modo explícito independiente, no se fuerza el límite de la muestra. Caches de ejecución se liberan entre lotes. La búsqueda de propietarios históricos evita escanear todos los usuarios por cada alta.

Las tablas de usuarios de WordPress son MyISAM: un fallo deja avance parcial, no rollback global. El primer recorrido se detuvo al detectar un falso avatar; el avance quedó conservado para retomar. La aplicación completa revisa formatos antes de procesar avatares y marca los archivos no gráficos como `ftuy_legacy_avatar_needs_review`, sin copiar HTML a `/media/`. Su contenido original sigue exclusivamente en los respaldos. La muestra estricta sigue reportando errores de imagen como antes.

## Conservación de publicaciones

Nueva versión 2 de `ft_publications`: agrega `legacy_metadata` privado, con la fila original completa, hash del SQL y zona horaria histórica desconocida. Así se preservan categoría/puntaje/campos de la adaptación sin convertirlos en asociaciones nuevas con foodtrucks o eventos.

La importación valida autor mediante su ID/alias histórico, existencia/formato de foto, fecha, estado, slug y duplicados. Mantiene el texto/dirección/coordenadas y la fecha original; `status=1` se conserva como publicada, `status=0` como despublicada. Guarda IDs, slug y autor históricos, y deja registro de importación en el historial. Repetir una publicación ya importada no sobrescribe una moderación posterior ni duplica fotos. Las escrituras de fila/historial usan InnoDB; si falla una importación nueva, se elimina únicamente el medio recién generado y quedan intactos los originales y registros anteriores.

Fotos copiadas/procesadas en `/media/publicaciones/{UUID}/`: JPEG hasta 900 px en el lado mayor y 300 KB, sin recorte cuadrado ni ampliación de originales pequeños. Los perfiles siguen en `/media/perfiles/`, 500×500 y hasta 120 KB. Las imágenes del foodtruck mantienen sus reglas anteriores.

Las fechas antiguas no tienen zona horaria comprobada: se conserva el valor original, sin inventar desplazamientos. El HTML usa una fecha sin afirmar UTC para publicaciones históricas. Las fechas nuevas de la plataforma siguen generándose en UTC. La API conserva `created_at` original y devuelve `created_timezone=null` para la publicación migrada; las nuevas usan `UTC`. La nueva app debe interpretar explícitamente esta procedencia al formatear fechas históricas.

## Pruebas

- 6 comprobaciones de modo completo con cuentas ficticias: superar límite de muestra, progresos, HTML no publicable como avatar, alias, estado y repetición.
- 17 de migración de publicaciones: estado privado preservado, fecha/autor/slug, metadata original, proporcionalidad 900×450, hash intacto, repetición, slugs/fechas inválidos, IDs cortos, resolver de enlaces, moderaciones posteriores y permisos.
- 18 de muestra de usuarios y 17 de imágenes de foodtrucks.
- 31 de modelo de publicaciones, 20 del plan de usuarios y 25 del listado/detalle web, estas dos últimas también después de importar los datos reales.

## Resultado aplicado en local

- 3.677 filas históricas: 16 excluidas por email inválido y un duplicado unificado. Resultado: 3.660 identidades WordPress, incluyendo el administrador existente y 3.659 suscriptores pendientes de reactivación. Un alias histórico conservado.
- 1.311 avatares preservados. Dos referencias apuntaban a archivos HTML, no imágenes, y quedaron marcadas para revisión sin copiar sus archivos al directorio público.
- 51 publicaciones importadas, todas publicadas según su estado original, correspondientes a 28 autores. Cada fila conserva su fecha, slug, autor y metadata original; originales gráficos intactos.
- 260 verificaciones sobre datos reales: correspondencia con el SQL, autores, fechas, estados, metadata, tamaños y enlaces. Despublicación reversible de una publicación verificada en web/API y restaurada a su estado original; las 51 están publicadas al finalizar.
- Repetición del importador: cero publicaciones nuevas y 51 existentes, sin duplicar medios. Auditoría final: administrador, correos y fuentes sin cambios.

Respaldos locales privados anteriores a usuarios y publicaciones respectivamente: `food-before-full-migration-2026-10-07.sql` y `food-before-publications-2026-10-07.sql`, dentro de `Bds/backups-local/`, excluidos de Git. Los datos de prueba se retiraron.

La finalización real se verifica con huellas del administrador, correos y todas las fuentes de avatar/publicación, además de contar identidades, roles y vínculos de cada foto. Los scripts de auditoría privados no publican emails ni nombres.

El despliegue productivo, la apertura de la app Android/iOS, la autenticación móvil y la retirada física de imágenes despublicadas siguen fuera de esta migración local.
