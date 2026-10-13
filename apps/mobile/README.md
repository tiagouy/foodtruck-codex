# Foodtrucks UY — app 0.13.0

Cliente React Native separado de BuenCafé. Guía de ejecución, alcance y pendientes en [docs/app/base-0.1.0.md](../../docs/app/base-0.1.0.md).

Backend actual: `https://foodtruckuruguay.com`, API del plugin WordPress. No conecta a MySQL ni a las APIs antiguas. Debug y Release usan el sitio público; en `src/lib/config.ts`, `USE_LOCAL_BACKEND` permite optar por MAMP solo en Debug.

Incluye lectura pública, cuenta compartida, fotos y Firebase Messaging. Identidades de tienda: iOS `org.useful-media-app.foodtruck`, Android `org.useful_media_app.foodtruck`. Versión pública 5.0; enlaces de fotos preparados para iOS con plugin 0.22.12, pendientes de prueba real de asociación/firma. Android pendiente del certificado de Play. No se ha subido a TestFlight.

Al probar login y cargas contra el backend actual se usan cuentas y datos **reales de producción**. Las pruebas automáticas de contrato público no publican ni cambian cuentas. Las sesiones guardadas en MAMP pueden requerir iniciar sesión nuevamente; las contraseñas cambiadas en la web pública son las vigentes.
