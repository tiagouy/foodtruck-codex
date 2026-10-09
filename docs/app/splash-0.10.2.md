# Splash — 0.10.2 · 2026-10-09

Fuente aprobada: logo-1024.png (foodtruck + nombre), sin cambiar el archivo de iCloud ni redibujar. Master opaco normalizado sin metadatos: `apps/mobile/assets/branding/splash-logo-1024.png`. Fondo #F8F8F8.

iOS: reemplaza las etiquetas genéricas FoodtrucksUY/Powered by React Native en LaunchScreen.storyboard. SplashLogo.imageset 1x/2x/3x, centrado, aspect-fit, 280 pt máximo y límites de 80% del ancho/alto para pantallas pequeñas, iPad y orientación horizontal. Sin máscaras ni texto agregado.

Android API24–30: starting window con bitmap centrado 280 dp y fondo claro. Android API31+: starting theme con windowSplashScreenBackground/windowSplashScreenAnimatedIcon y bitmap de 288 dp cuya imagen completa ocupa 164 dp, conservando dibujo/letras dentro del círculo visible de 192 dp. Por la restricción del sistema se muestra más pequeño que iOS/Android antiguo; no usa branding inferior separado ni una segunda Activity. MainActivity vuelve a AppTheme antes de crear ReactActivity. Fondo de la ventana normal también claro para no contrastar durante el primer frame.

Sin dependencia nueva, pantalla JS extra, temporizadores ni espera de API/push. Duración y aparición quedan a cargo del arranque nativo; un hot start puede no mostrar splash. No garantiza que Debug con Metro lento mantenga la imagen durante toda la carga de JavaScript.

Generador reproducible `scripts/generate-splash.cjs`, con sharp del runtime vía NODE_PATH. Preview ilustrativo de la composición grande en `assets/branding/splash-preview.png`; no es una captura del sistema ni representa el tamaño reducido de Android12+.

Validación: iOS Debug BUILD SUCCEEDED y Android ARM64 Debug final BUILD SUCCESSFUL. APK actualizado con install -r, sin borrar datos; arranque API35 Status ok/LaunchState COLD/TotalTime 2014ms. Esto confirma arranque, no medición visual de duración del splash. 64 pruebas y lint pasan; inspección visual en dispositivos/iPad y Android24–30 pendiente. No cambia cuenta, token push, navegación ni firma; iOS nuevo no instalado ni publicado.

Referencia de restricciones Android: [Splash screens](https://developer.android.com/develop/ui/views/launch/splash-screen).
