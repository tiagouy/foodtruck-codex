# Enlaces de fotos · 2026-10-13

## Implementado

- React Navigation enlaza `https://foodtruckuruguay.com/fotousuario/SLUG/` con Detalle de publicación. Incluye Fotos detrás del detalle para volver al catálogo.
- `/fotosusuarios/` y `/fotosusuarios/index.php` abren Fotos. Filtros de autor/página web no se transfieren; abre la primera página del catálogo público.
- `/fotosusuarios/oferta.php?slug=SLUG` resuelve por el endpoint existente `publications/by-slug`, también cuando el slug es numérico (ID histórico, no ID nuevo).
- No intercepta registro, login, recuperación de contraseña ni otros dominios. Una publicación despublicada sigue devolviendo 404 mediante la API pública; sin recuperar el archivo directamente ni saltar permisos.
- iOS: puente NSUserActivity a RCTLinkingManager, dominio `applinks:foodtruckuruguay.com` en entitlements de dispositivo y simulador.
- Plugin 0.22.12: JSON AASA en `/.well-known/apple-app-site-association` y `/apple-app-site-association`. Rutas limitadas a fotos. Identidad prevista: `3Y7GC99X3J.org.useful-media-app.foodtruck`, basada en identidad histórica y certificado Apple Distribution disponible. Verificar application-identifier exacto del archive firmado; si el prefijo difiere, corregir AASA.

## Validación y despliegue

85 pruebas Jest correctas, TypeScript/ESLint correctos; 10 pruebas locales AASA (ambas URLs HTTP 200, JSON válido sin redirects).

Desplegar plugin por Git, sin volver a importar BD:

```bash
cd /home/usefsgir/foodtruck-code
git pull --ff-only
bash tools/deploy-web.sh foodtrucks-uy-core-v0.22.12
curl -i https://foodtruckuruguay.com/.well-known/apple-app-site-association
```

El script de despliegue no habilita capacidades en Apple Developer, no publica la app ni comprueba la caché CDN de Apple. Revisar Associated Domains del App ID/perfil antes de TestFlight. Tras publicar el AASA y reinstalar una build firmada correctamente, abrir un enlace desde Notas/Mensajes: debe ir al detalle; sin app debe seguir en web. Probar inicio en frío, app abierta, links históricos y foto despublicada. Navegar dentro de Safari desde el mismo dominio puede seguir en Safari por comportamiento de iOS; no confundirlo con fallo de asociación.

La firma ad hoc de simulador actual no acredita una asociación de producción. No se da por verificado Universal Links extremo a extremo todavía.

## Android pendiente

No se añadieron intent-filters ni assetlinks.json con un certificado supuesto. Necesitamos SHA-256 del **certificado de firma de la app de Google Play**, no solo upload key/keystore de desarrollo. Con ese dato se preparará Android App Links y se probará verificación en Android físico.

Referencias: [Apple Associated Domains](https://developer.apple.com/documentation/Xcode/supporting-associated-domains), [diagnóstico Universal Links](https://developer.apple.com/documentation/technotes/tn3155-debugging-universal-links), [React Navigation](https://reactnavigation.org/docs/configuring-links/).
