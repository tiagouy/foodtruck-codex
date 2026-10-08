# Foodtrucks UY Core

Plugin propio de WordPress que concentrará el dominio de la plataforma:

- foodtrucks, eventos y publicaciones de la comunidad;
- sugerencias y flujos de moderación;
- perfiles, propiedad y reactivación de cuentas;
- pantallas de administración dentro de WordPress;
- API REST versionada para web y app;
- migración controlada desde las bases heredadas.

El código está en `plugins/foodtrucks-uy-core/`. Versión `0.13.0`: [migración completa local](migracion-completa-0.13.0.md) aplicada y verificada: 3.659 suscriptores más el administrador existente, 1.311 avatares y 51 publicaciones de 28 autores. Incluye [fotos web y enlaces compartidos históricos](fotos-web-0.12.0.md), [moderación y API de publicaciones](publicaciones-0.11.0.md), [almacenamiento propio de imágenes y respaldos](imagenes-0.9.1.md), eventos, administración/API de foodtrucks, directorio/detalle web, imágenes reducidas y [cuentas propias](cuentas-0.8.0.md): registro, confirmación, login, perfil y recuperación/reactivación destacada. Todas las páginas del plugin comparten navegación pública y menú Mi cuenta. Ver [eventos](eventos-0.2.0.md) y [foodtrucks](foodtrucks-0.4.0.md). Autenticación/subidas móviles y despliegue productivo pendientes; la app vieja todavía no consulta estos datos.

Estado actual `0.18.0`: autenticación móvil y actualización de nombre/avatar, denuncias y [subida directa de publicaciones](../app/subir-fotos-0.7.0.md). Proxy privado Places implementado, pendiente configurar clave y probar Google real. Mantiene los datos migrados indicados arriba. Despliegue productivo y actualización de la app vieja en tiendas aún pendientes.
