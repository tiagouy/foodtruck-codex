# Integración pushv3 — revisión inicial

Fecha: 2026-10-09. Estado: integración de cliente implementada; validación de dispositivo/backend real pendiente.

## Implementación local

Credenciales recibidas mediante `dato.txt.rtf`, excluido de Git. Conversión mecánica con `node apps/mobile/scripts/import-push-config.cjs` genera `apps/mobile/pushv3.local.json` ignorado y con permisos 0600. Ningún valor en documentos/logs/código versionado. El archivo de service account sigue intacto, ignorado y sin usar. La ruta del servidor indicada en el RTF no se consulta ni se copia.

Firebase App/Messaging fijados en 23.8.8, compatibles con el API namespaced del kit. Android Google Services 4.4.4, POST_NOTIFICATIONS y IDs históricos. iOS configura FirebaseCore, incluye plist en Resources, pods estáticos, background remote-notification, proxy Firebase habilitado y entitlements APNs por configuración; firma/Team/APNs en Firebase siguen requiriendo validación real. No se cambió ningún certificado.

`src/lib/push/` adapta el contrato del kit: el cliente recibe token explícito en lugar de leer el ambiguo Storage `token`; estado push aislado en Keychain. Guarda tokenAnterior hasta confirmación de updateToken y vuelve a registrar la asociación tras rotación. Operaciones serializadas, timeout HTTP y ninguna impresión de payload/respuesta/token. Error de update no deriva en register duplicado. Guest update envía user vacío conforme al campo del método del kit, aún pendiente de confirmar en servidor real.

Bootstrap restaura sesión antes de sincronizar; una instalación sin sesión registra como invitado, sin user. Permiso Android 13+ respeta aceptación/negación; no se solicita en sistemas anteriores. Login y restauración usan ID WordPress. Registro por email no crea sesión inmediata; asociación ocurre al autenticarse. El listener de refresh se limpia al desmontar. Foreground muestra Alert con título/texto; background maneja notification payload del OS sin logs. No se inventó navegación por payload.

Logout elimina token actual y anterior pendiente antes de revocar sesión y limpiar credenciales. Si delete falla, conserva datos para reintentar y no declara logout exitoso. Tras logout confirmado, un marcador local impide re-registrar automáticamente como invitado al reiniciar/refresh; login vuelve a habilitar push. Es una decisión de implementación para respetar la eliminación pedida, no la alternativa guest del kit.

Pruebas simuladas de cliente/sesión: 64 Jest, TypeScript y lint, incluyendo negación del permiso, guest rotation y fallo de update conservando tokenAnterior. Android processDebugGoogleServices/processDebugMainManifest: BUILD SUCCESSFUL (no equivale a APK completo). Pods iOS: instalación completa, 92 pods; build Debug iPhone 17 Simulator: BUILD SUCCEEDED tras ajustes de headers. No se instaló este binario nuevo ni se registró token real. ADB sin dispositivos conectados. No hubo envío desde panel ni se verificaron success/failure/provider. No confundir pruebas simuladas/compilación con validación pushv3 productiva.

Compatibilidad iOS: RNFirebase 23 + frameworks estáticos + React Native 0.84 requieren headers React no modulares y macros de bridge visibles explícitamente. Podfile usa headers textuales (CLANG_ENABLE_MODULES=NO) solo en RNFBApp/RNFBMessaging. Parches versionados (patch-package/postinstall) importan RCTBridgeModule.h directamente en tres archivos .m y el converter propio de RNFBApp en Messaging, siguiendo el problema documentado en https://github.com/invertase/react-native-firebase/pull/9026. Sin desactivar validaciones de red/auth. El intento de forzar ese header con OTHER_CFLAGS no funcionó y se retiró.

En otra máquina: instalar dependencias con npm ci (aplica parches), proporcionar configuraciones Firebase históricas ignoradas y `pushv3.local.json` con la forma del ejemplo versionado, nunca los placeholders. Sin ese archivo no se puede empaquetar la app. Las pruebas mockean una configuración virtual, sin usar claves reales. Luego pod install y recompilar ambas plataformas; Fast Refresh no incorpora los módulos Firebase a un binario anterior.

La instalación npm reportó 61 vulnerabilidades (8 moderate/53 high), incluidas dependencias de desarrollo. No se ejecutó audit fix --force ni se cambiaron paquetes ajenos automáticamente. Auditoría/remediación es requisito pendiente antes de distribución.

## Fuente de verdad

Decisión posterior (2026-10-09): el usuario confirma que las apps están despublicadas y autoriza usar los IDs Firebase históricos. Android Debug ya no agrega `.dev`; iOS Debug/Release usa `org.useful-media-app.foodtruck`. Entitlements de simulador usan el bundle configurado. No cambia el namespace Kotlin interno ni la firma. No se desinstala ni publica nada. Si existe Android instalado con otra firma, el APK Debug no podrá actualizarlo: requiere resolverlo con el usuario, sin borrar datos automáticamente. Cambio de ID puede crear un contenedor/sesión local distinto.

Kit: https://github.com/tiagouy/pushv3-app-kit
Commit inspeccionado: `8ec6ee98396f1d68caa89d056a5b7415328a0182`.
Leídos completos: README.md, INTEGRATION_CHECKLIST.md, examples/usage.md y ambos módulos TypeScript del kit.

API indicada: `https://useful-media-push.org/pushv3/api/`. idapp: `12`.
tokenApp y PUSH_API_KEY reales disponibles localmente desde el documento del usuario. No guardar valores en documentación, commits ni logs. Una configuración ignorada por Git evita publicarlos en el repo, pero no convierte una clave empaquetada en la app en un secreto: confirmar antes de producción que sean credenciales de cliente limitadas, no permisos administrativos/de envío.

## App real e identidades Firebase

Proyecto React Native 0.84 con Android e iOS existentes. Sin módulos Firebase instalados todavía. Sesiones WordPress en Keychain; no hay la capa Storage del kit. La integración debe mantener almacenamiento push aislado, sin confundir `token` FCM con bearer de sesión.

- Android Firebase provisto: `org.useful_media_app.foodtruck`.
- Android Debug actual: `org.useful_media_app.foodtruck.dev`.
- iOS Firebase provisto: `org.useful-media-app.foodtruck`.
- iOS actual: `com.foodtrucksuy.dev`.

Los IDs `.dev` de arriba describen el estado anterior: por la decisión posterior ahora se usan los IDs históricos y no hacen falta nuevas apps Firebase. Bundle iOS productivo sigue pendiente de confirmar en App Store Connect: el plist por sí solo no demuestra la identidad de tienda. APNs/capabilities/firma y prueba iOS real también pendientes.

Los archivos Firebase ya están en rutas de plataforma, pero aún no integrados en Gradle/Xcode. Se excluyeron de Git. `food-trucks-uy-fcm.json` también queda excluido; no se abrió, copió ni usó. Ningún service account se incorpora al cliente: pertenece exclusivamente al servidor pushv3.

## Contrato real del kit

- `UsefulPush.configure({ apiUrl, apiKey, appId, appToken })`.
- `UsefulPush.register(userId?)`: FormData con `metodo=register`, espera JSON `register: yes`.
- `UsefulPush.updateToken(userId, previousToken)`: `metodo=updateToken`, `tokenAnterior` y nuevo `token`, espera `update: yes`.
- `UsefulPush.deleteToken(userId?)`: `metodo=removeToken`, espera `delete: yes`.
- Funciones de token: `ensureDevicePushToken`, `syncGuestPushToken`, `syncPushTokenForUser`, `setupPushTokenRefresh`.

Adaptaciones necesarias, preservando ese contrato HTTP:

1. El kit imprime FormData (credenciales y token), token FCM completo, respuestas y errores sin filtrar: eliminar esos logs, no solo esconderlos en producción.
2. Invitado con rotación usa register sin updateToken en el kit: conservar tokenAnterior y ejecutar la actualización documentada también en este caso. El contrato de user vacío para guest requiere comprobación real; no inventar otro método.
3. Tras updateToken con usuario activo, asegurar asociación al ID WordPress actual. Serializar bootstrap/login/refresh/logout para evitar reasociar después de logout; conservar actualizaciones pendientes ante fallos.
4. Eliminar antes de revocar/borrar sesión; decidir cómo presentar un fallo de eliminación sin declarar logout exitoso ni perder información necesaria para reintentar. No aplicar la alternativa guest que permite el kit: el usuario pidió eliminación.
5. Android 13+: respetar resultado de POST_NOTIFICATIONS; la respuesta del permiso iOS no sustituye ese resultado en Android.
6. Registrar como invitado sin requerir login. Restaurar sesión activa y sincronizar usuario real. El registro actual solicita email y no crea sesión autenticada inmediata: asociar tras login/validación, no inventar un usuario a partir del email.

## Validación exigida (pendiente)

- Android físico, instalación limpia, permiso y token guest en pushv3 antes de login.
- Login: mismo token asociado al ID WordPress.
- Refresh: tokenAnterior, nuevo token y asociación activa correctos, sin duplicados/carreras.
- Logout: delete confirmado, luego limpieza local y revocación.
- Envío dirigido desde panel a un token real: comprobar recepción y resultado `success`, `failure`, `provider=fcm_v1`.
- Negación de permiso, offline, errores HTTP, reintento y no filtración de tokens/credenciales.

No se envió ninguna notificación ni se registró ningún dispositivo. Credenciales disponibles; falta Android físico/panel para cerrar la validación. Firebase histórico autorizado; ver estado nativo al principio.

Referencias nativas: https://rnfirebase.io/messaging/usage y https://firebase.google.com/docs/cloud-messaging/ios/get-started.
