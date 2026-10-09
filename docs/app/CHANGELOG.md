# Changelog de la app

## 0.10.2 — 2026-10-09

- Splash nativo con logo y nombre: storyboard iOS centrado/responsivo y starting theme Android, adaptado al círculo del sistema Android12+. Fondo claro común, sin segunda pantalla ni demora artificial.
- [Assets reproducibles y límites](splash-0.10.2.md). No cambia sesiones ni push; icono launcher sigue siendo la variante sin texto de 0.10.1.

## 0.10.1 — 2026-10-09

- Icono aprobado integrado: iPhone/iPad/App Store, Android cinco densidades legacy/round, adaptativo/monocromático y pequeño para notificaciones FCM. Exportación Play 512 y master 1024, sin redibujar la marca.
- [Generación reproducible y validación](iconos-0.10.1.md). Android e iOS aceptan recursos; recepción con icono pequeño pendiente. No se publicaron fichas ni se cambió firma.

## 0.10.0 — 2026-10-09

- Integración cliente pushv3 basada en kit 8ec6ee9: bootstrap guest, login/restauración con ID WordPress, refresh con tokenAnterior y asociación, eliminación antes de logout. Estado separado en Keychain, operaciones serializadas, sin logs de claves/tokens. Error de delete conserva sesión para reintentar; logout confirmado no re-registra guest al reiniciar.
- Firebase App/Messaging 23.8.8, Android Google Services/permisos e iOS FirebaseCore/plist/pods/background/APNs. Por pedido del usuario se usan IDs históricos y no `.dev`; firma de tiendas/APNs reales pendientes. Configuración local ignorada, service account sin usar.
- [Detalle, configuración y validación pendiente](pushv3-integracion.md). Android Google Services y manifiesto verificados; sin Android conectado ni token real/envío de panel. No es validación productiva ni publicación de tiendas.
- 64 pruebas, TypeScript/lint e iOS Debug compilado con parches reproducibles de imports Firebase y headers textuales limitados a sus dos pods. Binario nuevo no instalado; recepción física/APNs y resultado fcm_v1 pendientes.

## 0.9.0 — 2026-10-08

- Home y agenda usan EventCarousel: poster cuadrado, tarjeta centrada y vecinos reducidos que crecen progresivamente al desplazarse, con snap y animación nativa. Sin autoplay ni duplicar eventos para simular un bucle.
- Agenda conserva próximos/pasados, recarga, errores y paginación. Flechas accesibles y contador; Reduce Motion desactiva el cambio de escala y desplazamiento animado por flechas.
- 53 pruebas Jest, TypeScript/lint. QA en iPhone 17: cuatro eventos históricos, segundo centrado con ambos vecinos visibles, avance por flecha y apertura del detalle. El gesto de arrastre automatizado no quedó validado (abrió el detalle); pendiente prueba manual del deslizamiento y QA Android.
- Plugin y datos intactos. Home sigue mostrando solo próximos reales; no usa históricos para rellenar un vacío.

## 0.8.3 — 2026-10-08

- Lista y detalle comparten PhotoMeta: nombre → fecha/lugar → texto. Lugar subrayado abre Google Maps, priorizando coordenadas válidas y usando búsqueda textual si faltan. No dispara a la vez la apertura del detalle de la tarjeta.
- 51 pruebas Jest, TypeScript/lint y QA nativa con Expo Café Uruguay. No modifica publicación/ubicación ni solicita GPS.

## 0.8.2 — 2026-10-08

- Seleccionar un lugar conserva el nombre del resultado (Expo Café Uruguay), no lo sustituye por su calle. Dirección postal y LAT/LONG se envían por separado, solo mientras la selección siga vigente.
- QA nativa real: elegir Expo Café Uruguay mantiene ese nombre en Dónde fue. Sin publicar. 49 pruebas Jest, TypeScript/lint y 15 comprobaciones HTTP de guardado.

## 0.8.1 — 2026-10-08

- Autocompletado de direcciones como desplegable de filas blancas con pin, nombre/dirección y separadores, en vez de botones naranjas individuales. Atribución Google Maps al pie.
- Conserva selección y coordenadas, sin alterar el borrador. QA visual con Expo Café Uruguay; regresión de selección. 48 pruebas Jest, TypeScript y lint.

## 0.8.0 — 2026-10-08

- Editor nativo de recorte cuadrado después de elegir una foto de publicación: mover/zoom, Usar foto o Cancelar; cancelar conserva la selección previa.
- Usa el archivo realmente recortado en preview/subida. Vista previa cuadrada, sin franjas. No cambia fotos históricas ni avatar.
- Dependencia nativa fijada en 0.52.0; sin pedir cámara ni almacenamiento general. [Documentación](recorte-0.8.0.md). 47 pruebas Jest, TypeScript y lint.
- iOS compilado e instalado en iPhone 17: editor y confirmación verificados; preview cuadrado sin publicar. Android: integración/manifiesto compilados, sin WRITE_EXTERNAL_STORAGE; gestos en dispositivo Android todavía pendientes.

## 0.7.0 — 2026-10-08

- Subir foto desde Fotos o Mi cuenta: biblioteca, preview, texto/dirección, sin puntaje; sesión obligatoria y publicación directa.
- Multipart y reintento con el mismo identificador ante resultado incierto, sin duplicar en el servidor. Vista de éxito con acceso a detalle/Mis fotos.
- Autocompletado conectado al proxy privado Places; dirección manual si falla/no está configurado. La clave de servidor y prueba real de Google siguen pendientes.
- [Circuito, configuración y verificación](subir-fotos-0.7.0.md). 45 pruebas Jest, TypeScript/lint y subida HTTP de fixtures.

## 0.6.0 — 2026-10-07

- Bandera en el cabezal derecho del detalle de una publicación, con confirmación antes de denunciar.
- Requiere sesión activa; sin sesión invita a ingresar. Envía el ID de publicación con el token seguro y muestra el resultado real del servidor.
- No despublica automáticamente. 41 pruebas Jest, TypeScript y lint.

## 0.5.2 — 2026-10-07

- Avatar circular del autor junto al nombre en tarjetas de fotos de la comunidad (Inicio/Fotos) y detalle.
- Sin avatar o si falla su imagen, icono de persona consistente con Mi cuenta. Una URL de reemplazo vuelve a cargar normalmente.
- Usa `author.avatar` de la API existente, sin consultas adicionales ni cambios de identidad. 40 pruebas Jest; QA visual con avatar nuevo de Santi y autor sin foto.

## 0.5.1 — 2026-10-07

- Corrige JPEG de iOS reportados por el selector como `image/jpg`: se normalizan a `image/jpeg` antes de validar y subir. La validación anterior rechazaba fotos válidas antes de crear la vista previa.
- Mensajes de guardado distinguen nombres solamente de perfil con foto. Regresión con el MIME real del selector y comprobación nativa de vista previa.

## 0.5.0 — 2026-10-07

- Foto de perfil seleccionable desde Editar perfil, preview local y subida al guardar; límite de peso con mensajes simples.
- Mi cuenta con avatar circular a la derecha (20% del ancho del teléfono, con límite para tablets), nombre a 20 y Mis fotos a 17 puntos.
- Selector nativo de biblioteca, configuración de privacidad iOS y recompilación; sin pedir cámara ni permisos Android de almacenamiento general.

## 0.4.0 — 2026-10-07

- Mi cuenta autenticada muestra nombre, avatar propio si existe y Mis fotos en grilla cuadrada de dos columnas, paginación y acceso al detalle.
- Engranaje arriba a la derecha para Configuración: editar nombre/apellido, email de solo lectura y Cerrar sesión. No se cambia email ni foto de perfil todavía.
- Espera de restauración de sesión sin mostrar transitoriamente el formulario de ingreso; revalidación al volver de Configuración.
- Consultas por autor WordPress; solo publicaciones visibles, sin exponer IDs antiguos ni volver a importar datos.

## 0.3.0 — 2026-10-07

- Mi cuenta con ingreso nativo por email/contraseña arriba, sin botón al sitio; registro, reactivación y recuperación conservados.
- Sesión persistente en Keychain/Keystore, contraseña no guardada, validación al restaurar y cierre con revocación.
- Inicio como ruta inicial explícita. Face ID/huella pendientes de pruebas nativas; subida de fotos y edición de perfil aún no implementadas.

## 0.2.0 — 2026-10-07

- Formularios nativos Reactivar cuenta, Registrarme y Recordar contraseña conectados a las APIs existentes del plugin; ya no abren la web para solicitar el correo.
- Nombre/email sin contraseña ni IDs históricos enviados por el cliente; validaciones, estado de envío, protección de doble solicitud, cancelación al salir, errores y confirmación genérica.
- Elección de contraseña continúa en el enlace del correo; ingreso/sesión móvil y perfil/subida siguen pendientes. Captura de correos local explícita en builds de desarrollo.
- 23 pruebas de cliente y 24 de cuentas del backend con fixtures limpiados. Revisión inicial del formulario de reactivación en iPhone 17.
- Verificación Android retomada: manifiesto Debug generado correctamente con `org.useful_media_app.foodtruck.dev`, 40201/4.2.1-dev y JDK 17. No equivale a APK compilado, firmado ni publicación; bundle productivo iOS sigue pendiente.

## 0.1.1 — 2026-10-07

- Objetivo confirmado: actualizar las apps existentes, no crear fichas nuevas en tiendas.
- Android applicationId histórico verificado en el APK firmado: `org.useful_media_app.foodtruck`. Debug separado con sufijo `.dev`; namespace Kotlin interno independiente del ID publicado.
- APK de referencia 4.2.0/40200; valores locales 4.2.1-dev/40201 no confirman la numeración actual de Play. Tareas release exigen versión/código explícitos, pendientes de comprobar en consola, y firma aún sin configurar.
- Auditoría iOS identifica discrepancia entre Firebase (guiones) y config/enlaces (guiones bajos). Bundle de producción pendiente de confirmación en App Store Connect; no sustituido por una suposición.
- Keystore y Firebase históricos intactos/fuera de Git. Ver [continuidad de tiendas](continuidad-tiendas.md).

## 0.1.0 — 2026-10-07

- Base React Native/TypeScript independiente, adaptada de la estructura, cabecera y tarjetas de BuenCafé. Proyectos Android/iOS nuevos, identidad provisional y sin copiar claves/Firebase.
- Inicio combinado, eventos próximos/pasados, directorio de foodtrucks y fotos con paginación y detalles, todos consultados en la API del plugin.
- Fechas históricas visibles, estados de vacío/error/reintento, cancelación y revalidación al enfocar detalles. No se inventa contenido para llenar pantallas.
- Cuenta con reactivación destacada y enlaces explícitos al sitio; sesión/subida nativas aún pendientes.
- 14 pruebas, TypeScript/lint y 19 comprobaciones reales de API de lectura. Compilación y revisión inicial iOS Simulator; Android/dispositivos pendientes.
- No apta para tiendas todavía: configuración productiva, firma, branding y revisión de alertas de dependencias pendientes. Plugin sin cambios, conserva 0.13.0.
