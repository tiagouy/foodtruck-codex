# Iconos — 0.10.1 · 2026-10-09

Fuente aprobada: redes.png de Santi, 1024 × 1024 RGB opaco. Master normalizado sin metadatos en `apps/mobile/assets/branding/app-icon-1024.png`. Sin IA ni redibujo; conserva colores y márgenes. No se modificó el archivo en iCloud.

- iOS: AppIcon.appiconset con todos los slots existentes de iPhone más iPad (20, 29, 40, 76 y 83.5 pt), escalas 1/2/3 según corresponda y marketing 1024 px. 18 slots, 13 PNG distintos, todos opacos y sin esquinas dibujadas.
- Android legacy: launcher y round a 48/72/96/144/192 px; reemplaza los iconos genéricos. Round pre-26 usa máscara circular solo en su variante específica.
- Android adaptativo: capas de 108 dp, fuente completa a 66 dp con fondo #F8F8F8 y márgenes que mantienen todo el dibujo dentro del círculo seguro. Las esquinas/formas las aplica Android. XML API26 y API33; monochrome derivado de la tinta para tema del usuario, no un cuadrado opaco.
- Android notificaciones FCM: silueta alpha a 24/36/48/72/96 px, default_notification_icon en manifest. iOS usa su icono de app para notificaciones.
- Exportación Google Play 512 px y vista comparativa iOS/Android. No incluye screenshots de tiendas ni banner/feature graphic: son piezas diferentes del icono.

Generación reproducible: `scripts/generate-icons.cjs` usando sharp del runtime bundled con NODE_PATH, desde el master versionado o ruta explícita. PNG sin perfiles/metadatos heredados. Verificación de medidas/ausencia de alpha en iOS en el script; revisión visual circular contra margen. Preview solo ilustrativo: no se aplica su máscara redondeada a los PNG iOS.

Validación: Android ARM64 Debug final BUILD SUCCESSFUL, APK actualizado con `install -r` y actividad abierta en emulador API35 sin borrar datos. iOS Debug BUILD SUCCEEDED, catálogo aceptado; binario iOS no instalado. 64 pruebas y lint pasan. Recepción de notificación con icono pequeño nuevo pendiente; no es publicación de tiendas ni cambio de firma.

Referencias: [Android adaptive icons](https://developer.android.com/develop/ui/compose/system/icon_design_adaptive), [Firebase Android cliente](https://firebase.google.com/docs/cloud-messaging/android/client), [Apple app icons](https://developer.apple.com/design/human-interface-guidelines/app-icons).
