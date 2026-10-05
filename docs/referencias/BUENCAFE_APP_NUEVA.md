# BuenCafe: app nueva, origen y modelo de datos

## Resumen ejecutivo

La app móvil actual de BuenCafe es una reconstrucción nueva del cliente móvil anterior. Se volvió a implementar la experiencia desde cero con React Native moderno y TypeScript, usando la aplicación previa como referencia funcional. La renovación se hizo en el cliente: **no implicó reemplazar el backend histórico ni diseñar una base de datos nueva**.

La app nueva sigue consultando y actualizando los datos por medio de la API PHP existente en `https://buencafe.app/apiapp/`. Esa API conserva contratos y vocabulario heredados. A su vez, la API consulta el modelo relacional histórico de BuenCafe. Por eso hay nombres que no coinciden con el lenguaje de producto: por ejemplo, el endpoint `cervecerias` devuelve cafeterías/servicios y las publicaciones de usuarios siguen apareciendo como `ofertas`.

## Historia del proyecto

1. Existía una app móvil anterior de BuenCafe, hecha también con React Native, con años de lógica funcional y dependencias nativas acumuladas.
2. Se decidió construir una app nueva en un proyecto separado, tomando la vieja como referencia de funcionalidades y del comportamiento esperado, no como base para seguir arrastrando toda su implementación.
3. La nueva aplicación se organizó en TypeScript, pantallas y componentes por responsabilidad, utilidades de API/storage e integraciones Firebase actualizadas.
4. Para conservar continuidad de cuentas y contenido, se mantuvieron la API de producción y sus contratos de datos. La app nueva y la vieja no poseen bases de datos móviles separadas: ambas leen/escriben en el backend BuenCafe.
5. La serie 3.1 incorporó o validó push con Firebase Messaging y Useful Push v3, Firebase Analytics, mejoras a la paginación del Home y documentación técnica.

## Qué significa “base de datos vieja”

La app no se conecta directamente a MySQL ni contiene una copia local del esquema. La cadena de acceso es:

```text
App React Native nueva
        |
        | HTTPS / JSON o multipart FormData
        v
API PHP legacy: https://buencafe.app/apiapp/
        |
        | consultas y escrituras del servidor
        v
Base relacional histórica de BuenCafe
```

El cliente conoce los nombres de endpoints y algunos campos que devuelve la API, pero no administra tablas, consultas SQL ni migraciones. La API es la frontera entre el móvil y la base. Por ello:

- la app nueva reutiliza los datos y las cuentas que ya existen;
- los cambios de esquema deben coordinarse con el backend y con los demás clientes que consumen la API;
- una modificación en el cliente no cambia por sí sola la base;
- los nombres antiguos se mantienen para compatibilidad aunque la interfaz los presente con nombres actuales.

En la copia local disponible del backend se observaron entidades/tablas y consultas históricas como `servicios` y `categoriasServicio`, además de contratos de publicaciones, usuarios y noticias. Esos nombres describen detalles internos del backend; la app debe tratar las respuestas de la API como contrato y no asumir acceso directo a esas tablas.

## Aplicación nueva

### Tecnología y organización

- React Native `0.84`, React `19` y TypeScript.
- React Navigation con un stack raíz y pestañas principales.
- `src/screens/`: pantallas y casos de uso.
- `src/components/`: piezas visuales reutilizables.
- `src/lib/api.ts`: transporte al backend, tipos de respuesta y URLs de imágenes.
- `src/lib/storage.ts`: persistencia local con AsyncStorage.
- `src/lib/analytics.ts`: eventos y propiedades Firebase Analytics.
- `src/lib/pushToken.ts` y `src/lib/usefulPush.ts`: token FCM y registro en Useful Push v3.
- `src/lib/i18n.ts` y `src/lib/locales/`: español y portugués.
- `android/` e `ios/`: proyectos nativos para publicar en Google Play y App Store.

No hay una base de estado global como Redux. Las pantallas mantienen su estado local y leen preferencias/sesión desde AsyncStorage.

### Funcionalidades de producto

- **Home:** feed de fotos/publicaciones de la comunidad, filtro por país, paginación, vista de detalle, compartir y reportar; edición/borrado disponibles cuando corresponde al usuario autenticado.
- **Cafeterías:** catálogo filtrable por país, mapa/listado y detalle con información, amenities, ubicación y enlaces disponibles.
- **Mapa:** contenido geolocalizado y navegación hacia los elementos.
- **Publicar foto:** selección de imagen, descripción, valoración y ubicación; requiere sesión.
- **Detalle de publicación:** contenido, autor, mapa/ubicación y acciones permitidas.
- **Cuenta:** registro, login, recuperación de contraseña, perfil, edición y publicaciones del usuario.
- **Noticias:** listado, apertura de enlace y envío de una sugerencia para revisión.
- **Idioma:** español y portugués.

La navegación principal contiene Home, Subir Foto, Mapa, Cafeterías y Noticias; el resto de flujos se abre desde el stack raíz. Subir Foto requiere iniciar sesión.

## Cómo usa los datos

### Lectura

Las pantallas llaman a `Api.get` o `Api.post` y reciben JSON. Algunos endpoints de lectura histórica se llaman mediante `POST`, porque la API legacy espera ciertos parámetros en el cuerpo. No se debe cambiar el método basándose solo en convenciones REST: primero hay que comprobar el contrato del servidor.

Ejemplos funcionales de endpoints existentes:

| Área | Contratos de API | Uso en la app |
|---|---|---|
| Home/publicaciones | `ofertas`, `filtrar_ofertas`, `ofertas_user`, `oferta_slug` | Feed, filtro, perfil y detalle |
| Cafeterías | `categories`, `cervecerias` | Países/categorías y catálogo de servicios/cafeterías |
| Cuenta | `iniciarSesion`, `registro`, `resetearPass`, `updateUsuario` | Login, alta, recuperación y edición de perfil |
| Publicaciones | `subirOferta`, `editaOferta`, `borrarOferta`, `denunciar` | Alta, edición, eliminación y reporte |
| Noticias | `noticias`, `sugerirNoticia` | Listado y sugerencias |

Estos nombres son contratos legacy, no recomendación para nombrar endpoints nuevos.

### Paginación del Home

El feed carga bloques de 10. La API heredada espera el valor `limit` enviado en `POST` para `ofertas` y `filtrar_ofertas`. El cliente incrementa el límite según los elementos ya cargados y deduplica por `idOffer` para evitar repetir publicaciones. Hubo un problema en que la app mandaba solicitudes repetidas con límites incorrectos y se repetían las primeras fotos; se corrigió coordinando el uso del parámetro y el flujo de carga. Al tocar la paginación se deben probar varias páginas, filtros y refresh, no solo la primera carga.

### Envío de cambios y archivos

Los formularios usan `POST`. Para imágenes, la app crea `FormData` con el archivo seleccionado (URI, nombre y tipo MIME) y los campos del formulario; no sube las imágenes como Base64. El servidor procesa y persiste la operación y luego entrega/expone la URL que utilizarán los clientes.

### URLs de imágenes

La app resuelve imágenes existentes desde los servidores configurados, según el tipo de contenido:

- publicaciones y perfiles: `api2.buencafe.app`;
- noticias: `api2.buencafe.app`;
- fotografías de servicios/cafeterías: `buencafe.app/admin/views/services/uploads`.

Si la API ya devuelve una URL absoluta, el cliente la usa directamente. Si no hay foto, muestra un recurso local de reemplazo. Las imágenes viven en el servidor, no en la base de datos local del dispositivo.

### País/categoría de cafetería

El modelo heredado expresa la relación de cafeterías mediante campos como `idCategoria`; la app obtiene `categories` y cruza identificadores para mostrar/filtrar por país. Es un contrato histórico todavía funcional, aunque los nombres “categoría” y “país” no representan limpiamente el concepto de producto.

## Persistencia del dispositivo

AsyncStorage guarda valores JSON bajo claves funcionales. Entre las claves utilizadas están:

- `user`: sesión/datos básicos del usuario autenticado;
- `app_lang`: idioma;
- `filtro_home` y `filtro_cafeterias`: filtros seleccionados;
- `token`, `tokenPush`, `fcmToken`: compatibilidad/persistencia del token de push.

Esta persistencia no sustituye a la base remota. La mayoría de colecciones se vuelven a pedir al servidor al entrar/refrescar; no existe una réplica local completa del catálogo o del feed.

## Firebase y notificaciones push

### Firebase Analytics

Firebase Analytics está integrado en la aplicación nueva. Registra vistas de pantallas y eventos de producto, incluyendo login, registro, logout, publicación de foto, sugerencia de noticia, apertura de detalle de cafetería, apertura de sus redes y navegación a un mapa.

Se configuran propiedades para distinguir la nueva aplicación del tráfico histórico: `app_line = new_app`, `release_series = 3.1`, ambiente de build, plataforma y estado de autenticación. Durante las pruebas Android se confirmó en los logs nativos la formación/subida de eventos (`screen_view`) con respuesta HTTP 204 y luego se observaron vistas en Firebase. Analytics no consulta ni escribe la base MySQL de BuenCafe.

### Push

Firebase Messaging obtiene el token FCM del dispositivo; la API de Useful Push v3 administra su registro para campañas. Son sistemas distintos de la API de contenido BuenCafe.

Flujo funcional validado:

- al abrir la app, se solicita permiso cuando aplica, se obtiene y persiste el token y se registra como guest si no hay sesión;
- al hacer login o registro, el token se asocia al usuario;
- al logout, se conserva el token del dispositivo y se desvincula de la cuenta, volviendo a estado guest;
- la renovación de token FCM vuelve a sincronizarlo.

La validación de desarrollo observó el mismo dispositivo pasando `guest (user 0) -> usuario (user 11) -> guest`. Android e iOS fueron probados contra el backend local; la app usa la URL productiva configurada en builds de producción. La clave y credenciales de la integración no se documentan aquí ni deben publicarse en repositorios.

## Servicios externos adicionales

- **Google Places/Maps:** autocomplete y selección de direcciones/lugares, coordenadas y datos de ubicación.
- **Ubicación del dispositivo:** ayuda a posicionar publicaciones y mapas; depende de permisos del sistema.
- **Image Picker:** selección de imágenes para publicar/editar.
- **API principal de BuenCafe:** datos, autenticación y operaciones de contenido.
- **Servidores de imágenes:** entrega de medios cargados previamente.

## Versiones y publicación

La serie funcional 3.1 se preparó para Android y iOS. La documentación de release de la app registra Android `3.1 (310)` enviado a Google Play y builds iOS `3.1 (310)` y `3.1 (311)` procesados en App Store Connect al 10 de abril de 2026. La configuración local quedó en build `311` para futuras reconstrucciones de la serie 3.1. El estado final de revisión/publicación en las tiendas depende de sus consolas, no del código fuente.

Principales cambios incluidos en 3.1:

- registro de tokens push incluso antes del login;
- asociación/desasociación del token con la cuenta según login/logout;
- integración de Firebase Analytics y vistas de pantalla;
- corrección de paginación y repetición de fotos del Home;
- documentación de API heredada, flujo de datos, operaciones y releases.

## Cómo aprobar una cafetería nueva

El alta y moderación de cafeterías ocurre en el sistema administrativo/backend, no desde la app móvil. La copia local de administración inspeccionada define un indicador `status` (“Activo”) para los registros del recurso Cafeterias; la API pública de catálogo filtra los registros para entregar solo los activos (`status = 1`). En consecuencia, la aprobación operativa consiste en revisar los datos en el panel y activar el registro. Una vez guardado, el endpoint `cervecerias` puede incluirlo y la app lo mostrará al volver a consultar/refrescar.

La URL y los permisos del panel en producción deben confirmarse con el operador autorizado; no se debe inferir acceso por disponer de una copia local del código.

## Consideraciones para mantenimiento

1. Tratar `apiapp` como contrato compartido con la app antigua, la nueva y potencialmente otros consumidores. Antes de alterar formato, método o campos, validar compatibilidad.
2. Mantener la lógica de SQL y las reglas de negocio en el servidor; no conectar el móvil directamente a MySQL.
3. Al introducir endpoints o cambiar tablas, coordinar despliegues de backend y cliente, y documentar migraciones de base.
4. No guardar secretos, claves privadas, tokens de usuarios ni credenciales de firma en documentación o commits. Los archivos Firebase del cliente contienen configuración de proyecto; gestionar su exposición de acuerdo con políticas del proyecto.
5. Probar lectura y escritura en ambas plataformas, paginación más allá del primer bloque, filtros, imágenes y permisos antes de publicar.
6. Revisar `docs/` de `buencafe-codex2` para el detalle actualizado de arquitectura, API, pantallas, storage, Analytics, Push y operación de releases.

## Referencias de implementación

En el repositorio `buencafe-codex2`:

- `App.tsx`: navegación, inicialización de integraciones y ciclo de sesión.
- `src/lib/api.ts`: URL de API, contratos de datos y resolución de imágenes.
- `src/lib/storage.ts`: almacenamiento local.
- `src/lib/analytics.ts`: Firebase Analytics.
- `src/lib/pushToken.ts` y `src/lib/usefulPush.ts`: FCM y Useful Push v3.
- `src/screens/`: casos de uso y pantallas.
- `docs/architecture.md`, `docs/data-flow.md`, `docs/api.md`, `docs/screens.md`, `docs/storage.md`, `docs/push.md`, `docs/analytics.md`, `docs/releases.md`, `docs/release-and-ops.md`.

En el repositorio local de backend `_serverBC` se inspeccionaron copias de `apiapp` y `admin` para corroborar nombres legacy y el filtro de cafeterías activas. Esas copias sirven como referencia; producción puede diferir si no está sincronizada.
