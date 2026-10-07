# Actualizar las apps existentes · 2026-10-07

Santi confirmó que la reconstrucción debe subir como actualización de las fichas actuales, no como aplicaciones nuevas. La identidad provisional de la base no es la identidad de publicación.

## Datos verificados en el respaldo

| Dato | Evidencia | Resultado |
| --- | --- | --- |
| Android applicationId | APK firmado `Cosas viejas/foodtruckuruguay-v2/finalAPK/foodtruck-app-release.apk`, config.xml y google-services.json | `org.useful_media_app.foodtruck` |
| Última versión local Android | `aapt dump badging` del APK | 4.2.0 / versionCode 40200 |
| Certificado APK SHA-256 | `apksigner verify --print-certs` | `2c84319cfa108a9dfa53e4724401a7bc0ad92cb5afd1ec317d79d014b6c8dc69` |
| Bundle iOS en Firebase | `GoogleService-Info.plist`, campo BUNDLE_ID | `org.useful-media-app.foodtruck` |
| Identidad iOS en enlaces antiguos | config.xml / apple-app-site-association | `3Y7GC99X3J.org.useful_media_app.foodtruck` |
| Team ID de referencia | config.xml / asociación antigua | `3Y7GC99X3J`, pendiente de verificar vigencia |
| Proyecto Firebase histórico | ambos archivos de configuración, solo campo project ID leído | `steadfast-crane-88717` |

La consulta pública de Apple para el bundle con guiones y la búsqueda por nombre no devolvieron resultados. Esto **no prueba** que la cuenta o la app hayan desaparecido; no reemplaza App Store Connect. No hay archivo IPA/proyecto iOS compilado en esta copia que resuelva la discrepancia. No configurar asociaciones ni bundle productivo suponiendo que el archivo antiguo estaba correcto.

El SQL histórico de WordPress contiene el enlace `https://apps.apple.com/app/id1200507576`; la consulta pública de Apple de ese ID para Uruguay tampoco devolvió ficha. Se conserva como referencia a comprobar en la cuenta, no como confirmación del bundle. Las búsquedas fueron públicas; no se entró ni se cambió nada en consolas de las tiendas.

## Aplicado

Android `applicationId` de release pasa al del APK. `namespace`/paquete Kotlin `com.foodtrucksuy.dev` queda interno: no determina la ficha de Play. Debug usa `org.useful_media_app.foodtruck.dev` y certificado debug propio, para no reemplazar la app instalada desde tiendas. Cambiar de certificado no puede convertirse en una actualización instalable de esa app.

Código del proyecto 0.1.1; numeración de tiendas independiente. Defaults Android 4.2.1-dev/40201 solo locales, posteriores al APK disponible pero **no necesariamente a la última carga en Play**. Las tareas release exigen `-PFTUY_ANDROID_VERSION_CODE=N` y `-PFTUY_STORE_VERSION=X.Y.Z`, con código mayor a 40200; el operador debe confirmar además que supera el máximo ya usado en cualquier pista de Play. Firma release sigue sin configurar y API HTTPS productiva pendiente; no hay artefacto publicable ni envío a tiendas.

iOS permanece con identidad de desarrollo `com.foodtrucksuy.dev` hasta recibir el bundle exacto de **App Store Connect → app existente → Información de la app → Bundle ID**. Versión/build de desarrollo no son una propuesta de numeración para la tienda. No se crea un registro nuevo ni se fija automáticamente el Team antiguo.

## Firma y publicación pendientes

- Hay `foodtruck.keystore` en el respaldo, excluido de Git. No se copió, sobrescribió, cambió su contraseña ni generó otra clave release. Su mera existencia no confirma que sea la clave correcta: verificar certificado/alias y configuración de Play App Signing/upload key antes de usarlo. Contraseñas y claves se manejan por configuración privada, no documentación ni commits.
- Confirmar en Play la última versión/código de todas las pistas y las huellas de firma/upload key; no inferirlas del APK de 2021.
- Confirmar bundle, Team actual y última versión/build iOS en App Store Connect. Certificados/perfiles para esa app y equipo; no usar los de BuenCafé.
- Reutilizar el proyecto Firebase de Foodtrucks si se mantiene push/analytics, verificando clientes para ambos IDs. No se copiaron keys ni se activaron SDKs en esta etapa.
- Restaurar iconos/branding finales, configurar enlaces Android/iOS y revalidación, probar actualización sobre instalación antigua, reactivación y perfil/fotos. El cambio Cordova → React Native no migra automáticamente la sesión local: se requiere ingresar/reactivar con la cuenta conservada en WordPress.
- La versión nueva usa el backend nuevo. No publicar hasta tener plugin/datos/medios desplegados, HTTPS confirmado y flujos móviles completos.

## Verificación del cambio

12 comprobaciones de configuración (`node tests/mobile-store-identity.mjs`), TypeScript y las 14 pruebas existentes de API/pantallas. Defaults de release, sufijo debug y firma no heredada verificados. No se garantiza actualización instalable solo por compartir el ID: firma y números actuales requieren las comprobaciones anteriores.

La comprobación nativa `:app:processDebugMainManifest` con JDK 17 quedó interrumpida al extraer `react-android-0.84.0-debug.aar`: `No space left on device`. No se afirma manifiesto ni APK final verificado. El entorno de shell también tenía Java 8 seleccionado; se usó JDK 17 explícito sin cambiar ajustes globales. Se retiró únicamente el DerivedData temporal iOS creado para la base (`/private/tmp/ftuy-native-0.1.0`, aproximadamente 1 GB, regenerable al compilar); fuentes, respaldos, keystore y la app instalada en el simulador siguen intactos. Liberar más espacio antes de retomar compilaciones nativas.

Referencias oficiales: [condiciones de actualización Android](https://developer.android.com/google/play/app-updates), [firma y Play App Signing](https://developer.android.com/studio/publish/app-signing), [asociación de builds en Apple](https://developer.apple.com/help/app-store-connect/manage-builds/upload-builds).
