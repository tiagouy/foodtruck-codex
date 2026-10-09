# Paleta de marca — app 0.10.3

Se reemplaza la paleta marrón/crema heredada por los colores del logo aprobado
(`assets/branding/splash-logo-1024.png`). Muestreo de los píxeles dominantes:

- Naranja: `#FC590B`.
- Azul marino: `#05204B`.
- Fondo claro del logo: `#F8F8F8`.

Fuente compartida: `apps/mobile/src/theme.ts`. AppHeader reexporta los tokens para
mantener compatibilidad con las pantallas existentes. Cabezal azul, marca naranja,
botones naranjas con texto azul, fondos claros y bordes neutros azulados. El editor
de recorte también consume esta paleta. Las filas y atribución Google mantienen
su presentación propia.

No se usa naranja para texto pequeño sobre blanco: su contraste es insuficiente.
Texto de botones, fechas y navegación activa usa azul; las combinaciones de texto
principal/secundario y botones se verifican automáticamente con mínimo 4.5:1.

Este cambio alcanza la app, no modifica todavía el tema ni el plugin WordPress.
No cambia iconos, splash, datos, sesiones ni push. Verificación visual en dispositivos
pendiente; no requiere cambios nativos ni reinstalación en desarrollo con Metro.
