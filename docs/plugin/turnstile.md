# Turnstile para solicitudes de cuentas

Estado: preparado en Core 0.22.13, no desplegado ni activado en producción. Widget Managed creado por Santi. No guardar la Secret key en Git, chat, historial de comandos, capturas ni respuestas de WP-CLI.

## Alcance y activación gradual

- Protege `/registro/`, `/recordar-contrasena/`, `/reactivar-cuenta/` y sus tres rutas REST compartidas. La validación ocurre antes de consultar/crear usuarios o generar claves y correos.
- Conserva nonces web y límites existentes por IP/email. Un visitante sin token puede consumir su cupo IP, pero no el cupo del email ni crear cuentas ni enviar correos.
- No protege automáticamente Contact Form 7, wp-login.php, plugins ajenos o cualquier otro envío de WordPress: auditar esos flujos si continúa el abuso.
- No toca login web/app, sesiones, perfiles ni elección de contraseña con enlace válido. No elimina usuarios ni silencia avisos administrativos.
- **Los binarios móviles actuales no envían token.** Al activar, sus solicitudes de registro, recuperación y reactivación recibirán 403 con mensaje y URL del formulario web correspondiente. No hay bypass por plataforma/User-Agent/Origin/cabecera ni por una clave compartida en el cliente. Acordar esta transición antes de activar; preparar en otra entrega la apertura del formulario protegido desde la app si se desea una experiencia directa.
- Hasta activar `ftuy_turnstile_enabled`, no cambia el comportamiento. No dar por protegido el sitio por cargar las claves o desplegar el plugin.

## Configuración privada

El código lee constantes `FTUY_TURNSTILE_SITE_KEY` (pública) y `FTUY_TURNSTILE_SECRET_KEY` (secreta). La secreta debe proceder de un archivo privado fuera de public_html, por ejemplo `/home/usefsgir/foodtruck-secrets/turnstile.ini`, directorio 700 y archivo 600, sin imprimir el contenido. No usar `wp config set` pasando la clave real: muestra el valor en la salida.

Formato del archivo privado (solo ejemplo, NO una clave real):

```ini
secret_key="REEMPLAZAR_EN_EL_SERVIDOR"
```

Definir en wp-config antes de wp-settings, manteniendo el resto de configuración:

```php
define( 'FTUY_TURNSTILE_SITE_KEY', 'SITE_KEY_PUBLICA_DEL_WIDGET' );
define( 'FTUY_TURNSTILE_SECRET_KEY', is_readable( '/home/usefsgir/foodtruck-secrets/turnstile.ini' ) ? ( ( parse_ini_file( '/home/usefsgir/foodtruck-secrets/turnstile.ini', false, INI_SCANNER_RAW ) ?: array() )['secret_key'] ?? '' ) : '' );
```

No activar hasta que existan claves de producción y widget autorizado para el hostname de `home_url`. Validación exige el hostname canónico exacto, acción `ftuy_register`, `ftuy_forgot_password` o `ftuy_reactivate` y respuesta booleana exitosa. Tokens vencidos/reutilizados se rechazan por Siteverify; no se cachean éxitos. Error de red/configuración implica rechazo cerrado, no envío sin verificar.

## Verificación y despliegue pendiente

1. Ejecutar `php tests/turnstile-unit.php` (simulación sin red, DB ni correo) y lint de PHP.
2. Probar en un WordPress de pruebas las rutas web y REST con claves oficiales de prueba y servicios simulados. No usar claves de prueba en producción. Verificar que las cuentas sigan siendo suscriptores y confirmación/recuperación funcionen.
3. Desplegar mediante el flujo habitual de Git con respaldo. Aún no se ha creado ni subido el tag de esta versión.
4. Instalar las constantes privadas, verificar presencia sin imprimir valores y acordar el tratamiento de clientes móviles antiguos.
5. Activar con `wp option update ftuy_turnstile_enabled 1` usando PHP 8.3 del servidor.
6. Verificar widget en los tres formularios; un único envío real con una cuenta propia. Sin token, JSON sin token y validación rechazada deben impedir creación/envío. Probar login web/app existente. Revisar tanto logs del proveedor de correo como solicitudes HTTP antes de atribuir todos los rebotes al registro.
7. Documentar el uso de Cloudflare Turnstile en privacidad. Seguir midiendo volumen: un CAPTCHA reduce abuso, no garantiza ausencia de bots ni resuelve por sí solo correo falsificado/backscatter.

Rollback: restaurar la versión anterior o desactivar la opción restaura el flujo sin CAPTCHA, **reabre esa vía de abuso** y no debe hacerse sin decisión explícita. No cambiar DNS ni desactivar SMTP como parte de esta integración.

## Referencias oficiales

- https://developers.cloudflare.com/turnstile/get-started/widget-management/dashboard/
- https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
