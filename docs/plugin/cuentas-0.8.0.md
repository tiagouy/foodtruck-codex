# Cuentas · 0.8.0

## Identidad acordada

Una cuenta de WordPress con rol **suscriptor** sirve de identidad para web y app. El usuario no recibe permisos de administrador ni editor. La app solicita el registro al servidor: WordPress genera el ID nuevo; el dispositivo no fabrica IDs.

El ID antiguo de `users` de la app se guarda como `ftuy_legacy_user_id` en `usermeta`, solo para cuentas migradas. No se expone en formularios o REST ni se acepta del cliente. `FTUY_Accounts::set_legacy_id()` requiere administrador, es idempotente y rechaza reasignaciones y duplicados; una reserva privada en options impide que dos importaciones concurrentes asignen el mismo ID a dos cuentas. El perfil administrativo muestra el dato solo para lectura. No se migraron cuentas reales ni se tocaron fotos históricas. Faltan auditoría/importador y pruebas de correspondencia con fotos.

No hay relación evento–foodtruck: fue descartada por el usuario. Las publicaciones de fotos de comunidad siguen siendo una función de la app, no un formulario de la web.

## Pantallas

- `/registro/`: nombre y email; cuenta suscriptor pendiente, con contraseña aleatoria no entregada. Correo con enlace para confirmar email y elegir contraseña.
- `/ingresar/`: email y contraseña, opción mantener sesión y retorno a una pantalla propia autorizada.
- `/recordar-contrasena/`: solicita un enlace por email.
- `/reactivar-cuenta/`: solicita enlace únicamente para cuentas migradas con ID histórico y estado `legacy_pending`. Las cuentas de la app vieja todavía no se importaron; el mensaje público es genérico.
- `/elegir-contrasena/?key=…&login=…`: usa las claves nativas de WordPress, con vencimiento y un solo uso; contraseña de al menos 12 caracteres y confirmación. No se modifica la contraseña sin comprobar la clave. Una confirmación válida activa `email_pending`/`legacy_pending`.
- `/mi-cuenta/`: sesión requerida para mostrar email y editar nombre propio; enlaces a Mis eventos/Mis foodtrucks. Cambio de email y eliminación de cuenta quedan pendientes.
- `/salir/`: cierre con nonce y retorno al login propio.

La cabecera y las invitaciones de eventos/foodtrucks enlazan a estas pantallas, sin mostrar wp-login. Suscriptores sin barra administrativa y redirigidos desde wp-admin; administradores existentes mantienen su acceso. Las cuentas pendientes no pueden autenticarse ni usar contraseñas de aplicación de WordPress.

## API inicial

`POST /wp-json/foodtrucks-uy/v1/accounts/register`: `name`, `email`.

`POST /wp-json/foodtrucks-uy/v1/accounts/forgot-password` y `/accounts/reactivate`: `email`.

Respuestas normales 202 con mensaje genérico, sin informar si el email existe ni exponer ID/clave/contraseña. Validación 400, límite IP 429, problemas de servicio 500/503. Siempre suscriptor, ignorando roles/IDs enviados por el cliente. Es el mismo servicio usado por las pantallas web. Autenticación móvil, tokens y endpoints autenticados de perfil/subida de fotos quedan para la integración de app: no se crearon JWT ni contraseñas de aplicación como alternativa.

## Correo y habilitación

En localhost o entorno WordPress `local`, los mensajes se capturan sin salida real. **Usuarios → Correos de cuentas** (`manage_options`) permite leer los enlaces de prueba. También se intercepta `pre_wp_mail` localmente para capturar notificaciones nativas de cambios de contraseña u otros correos de la copia, nunca enviarlos a usuarios reales. La bandeja conserva los últimos cien mensajes en una opción no autoload; incluye enlaces sensibles y solo está disponible en local para administradores.

El registro del plugin se habilita por defecto solo en local. En producción requiere `ftuy_registration_enabled=true`, decisión explícita después de configurar HTTPS, correo y condiciones/privacidad. No se cambió `users_can_register` ni el rol predeterminado del WordPress histórico. Confirmar transporte y entrega antes de activar producción. La captura de correos locales no debe copiarse a producción.

## Seguridad y pendientes

Nonces de formularios anónimos ligados a cookie aleatoria HttpOnly/SameSite=Lax (Secure en HTTPS), límites por IP y email, redirecciones limitadas a rutas propias del mismo origen. Contraseñas no se repueblan ni se guardan en texto plano. Páginas privadas/noindex/no-cache y enlaces de confirmación con Referrer-Policy no-referrer. El registro no publica eventos ni foodtrucks automáticamente.

Pruebas: `wp eval-file tests/accounts-integration.php` y `wp eval-file tests/accounts-http.php`, solo en local. Cuentas, equivalencias y mensajes de prueba se limpian, conservando usuarios reales y correos ajenos. El resto de módulos se verifica con sus suites existentes.

Pendientes: migración de usuarios (incluidos emails duplicados/inválidos), política de cuentas eliminadas y equivalencias históricas, sesiones móviles, cambio de email verificado, pruebas de correo real, recuperación ante fallos, endurecimiento/medición de límites para producción y textos legales. No habilitar ni migrar producción desde esta etapa.
