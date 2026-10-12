# Despliegue por Git · 2026-10-11

Solo código propio: plugin `foodtrucks-uy-core` y tema hijo `foodtrucks-astra`. No se despliega la app, la base local, uploads, media, wp-config.php, claves ni WordPress/Astra/Elementor. Los cambios en Elementor de producción se conservan.

## Primera configuración del servidor

En SSH, clonar **fuera de public_html**, sin poner credenciales en comandos o URLs:

```bash
git clone https://github.com/tiagouy/foodtruck-codex.git /home/usefsgir/foodtruck-code
chmod 700 /home/usefsgir/foodtruck-code
cd /home/usefsgir/foodtruck-code
```

Si el repo es privado, usar autenticación autorizada de GitHub o una deploy key de solo lectura. Nunca pegar tokens en el chat. Antes de clonar, comprobar que el destino no existe.

## Actualizar

Publicar primero el commit y tag probado en GitHub. Luego:

```bash
cd /home/usefsgir/foodtruck-code
git pull --ff-only
bash tools/deploy-web.sh foodtrucks-uy-core-v0.22.6
```

El script obtiene tags sin forzarlos, despliega el commit exacto del tag, comprueba sintaxis PHP, exporta la base de producción a un directorio privado único y aparta ambos directorios actuales antes de sustituirlos. No ejecuta instalaciones, imports ni limpieza de tablas. Verifica carga del plugin y HTTP 200 en home, eventos y API. No ejecutar dos despliegues simultáneos; hay una breve ventana durante el cambio de directorios.

Backups: `/home/usefsgir/foodtruck-deploy-backups/release-XXXXXXXX/`. Contienen `database.sql`, `old-plugin`, `old-theme`, `commit.txt`, `tag.txt`. Permisos privados. Si falla tras el cambio, restaura archivos automáticamente, **no la BD**. Un error de sintaxis o exportación previo al cambio deja producción intacta. Revisar manualmente el admin, imágenes, cachés y páginas tras cada actualización; los HTTP 200 no validan toda la funcionalidad.

Para un rollback posterior, detener nuevos despliegues, identificar el snapshot exacto y apartar el código actual antes de devolver `old-plugin` y `old-theme` a sus destinos. Nunca importar automáticamente una BD anterior: podría perder nuevas publicaciones/cuentas. Este release no cambia el esquema.

## Primera prueba

Versión 0.22.6 agrega papelera de eventos. El administrador elige el evento de prueba; el despliegue no borra eventos. Papelera oculta web y API, conserva imágenes y revisiones. Restaurar devuelve a pendiente, no republica. No se eliminaron físicamente registros históricos.
