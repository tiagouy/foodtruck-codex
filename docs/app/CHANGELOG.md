# Changelog de la app

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
