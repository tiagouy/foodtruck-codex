# App — base 0.1.0 · 2026-10-07

## Qué se reutiliza

Referencia autorizada: `/Users/Santi/CLIENTES/_repo-git/buencafe-codex2`. Misma organización React Native/TypeScript con stack, pestañas, componentes y utilidades. Cabecera/tarjetas adaptadas desde AppHeader y HomeFeedItem. Se genera una estructura nativa limpia en `apps/mobile/`: no se clonan credenciales, claves de firma, Firebase, sesiones guardadas, países/puntajes ni los endpoints legacy. BuenCafé no se modifica.

React Native 0.84.0 y React 19.2.3 mantienen la línea de la referencia; no se afirma que sean las versiones más nuevas. React Navigation Native 7.5.0 resuelve los requisitos de los componentes actuales, bottom-tabs 7.14.0 y native-stack 7.13.0; lockfile propio. Generador oficial Community CLI 20.1.0. Entorno: Node 22.11+ (verificado 22.22.2), JDK 17 para Android y Xcode/CocoaPods para iOS. [Guía oficial de React Native](https://reactnative.dev/docs/getting-started-without-a-framework).

## Alcance

- Inicio: próximos eventos, foodtrucks públicos y fotos complementarias. Bloques independientes: fallar uno no oculta los otros, mensajes sin contenido ficticio.
- Eventos: próximos/pasados, listado paginado y detalle con ubicación, horarios por día y enlaces disponibles.
- Foodtrucks: catálogo/detalle público, base geográfica, qué sirven, rubros, modalidades y contactos. Las muestras pendientes no se publican para llenar el catálogo.
- Fotos: páginas de doce, deduplicación de IDs, fecha histórica explícita, detalle reconsultado al enfocarse y compartir enlace mediante el sistema operativo. Compartir no equivale a una integración especial de Instagram.
- Mi cuenta: explicación de reactivación y enlaces al sitio. No simula login nativo: una sesión del navegador no es una sesión de app.
- Estados de carga/vacío/error y reintento; timeout de 15 segundos y cancelación al cambiar de pantalla. Sin caché persistente de contenido en esta etapa.

API usada: `GET /events?view=upcoming|past&page=...&per_page=...`, `/events/{slug}`, `/foodtrucks`, `/foodtrucks/{slug}`, `/publications` y `/publications/{id}` bajo `foodtrucks-uy/v1`. Nombres/tipos nuevos, URLs gráficas entregadas por el servidor. Sin conexiones a BuenCafé.

## Ejecutar

Desde `apps/mobile/`, con Node 22 activo:

```sh
npm ci --ignore-scripts
npm run typecheck
npm run lint
npm test -- --runInBand
npm start
```

En otra terminal, `npm run ios` o `npm run android`. iOS requiere preparar CocoaPods conforme a `Gemfile`/`ios/Podfile`; se generó `Podfile.lock` propio. Android usa el keystore de depuración estándar generado localmente; no está versionado. En una clonación nueva, ejecutar `npm run android:prepare` con JDK 17 para generarlo, sin sobrescribir claves existentes. La firma release queda sin configurar.

MAMP debe estar activo. `src/lib/config.ts` configura iOS Simulator en `http://localhost:8888/foodtruck` y Android Emulator en `http://10.0.2.2:8888/foodtruck`; también transforma URLs de imágenes locales para Android. Un teléfono físico requiere una dirección LAN accesible y configuración de transporte de desarrollo; localhost no apunta al Mac desde el teléfono. No se anuncia prueba en dispositivos físicos todavía.

Producción deliberadamente sin URL: una build release no consulta accidentalmente el entorno local ni BuenCafé. Configurar HTTPS verificado y revisar transporte, firma e identificadores antes de distribuir. Identificador temporal de ambos proyectos: `com.foodtrucksuy.dev`; iconos nativos del template aún provisionales.

## Verificación y pendientes

Pruebas iniciales de API/presentación/pantallas: 14, incluyendo paginación sin `limit` legacy, duplicados, historia, errores vs vacío, 404 sin imágenes, fallos independientes y cuenta web explícita. TypeScript y lint. Los mocks no sustituyen QA nativo ni pruebas reales de servidor.

Verificado en esta etapa: compilación Debug iOS con Xcode 26.5 para iPhone 17 Simulator (iOS 26.2), inicio con eventos reales e imágenes, detalle de evento con dirección/Instagram, fotos históricas y pantalla Mi cuenta. Metro de Foodtrucks en puerto 8081. El transporte/parser real superó 19 comprobaciones de lectura contra WordPress (`node tests/mobile-public-api.mjs`), recorriendo las 51 publicaciones y verificando datos públicos/404, sin modificar registros. Android todavía no tiene compilación/QA nativo confirmado.

La instalación informa 52 alertas de dependencias (8 moderadas, 44 altas), en cadenas que incluyen CLI, Metro y Jest; `npm audit fix --ignore-scripts` no las resolvió. No se forzó una actualización mayor automáticamente. Revisar/actualizar y repetir compilaciones antes de distribuir: esta base de desarrollo no se presenta como lista para producción.

Pendiente: sesiones móviles seguras con cuentas WordPress, registro/reactivación/recuperación nativos, perfil/avatar, publicación/edición/denuncias desde app, apertura y revalidación de enlaces Android/iOS, Firebase propio si se decide usarlo, identidad gráfica, pruebas Android/dispositivos y preparación de tiendas. Ningún cambio al plugin ni a su versión 0.13.0 en esta etapa.
