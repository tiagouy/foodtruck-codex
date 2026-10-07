# Solicitudes de cuenta nativas — 0.2.0

Mi cuenta mantiene Reactivar cuenta destacado y ahora abre pantallas propias para solicitar registro, reactivación o recuperación. No pide contraseñas por email ni copia la sesión del navegador. Ingresar en el sitio sigue siendo un acceso web explícito hasta implementar el login/sesión móvil.

## Contrato compartido

POST JSON a `/wp-json/foodtrucks-uy/v1/accounts/register`, `/accounts/reactivate` y `/accounts/forgot-password`. Registro: `name` y `email`; las otras acciones: solo `email`. Email normalizado/nombre recortado; nunca envía rol, ID histórico, password ni token de reactivación. La API conserva la validación definitiva, límites, autoría e identidad WordPress ya probadas. Plugin sin cambios, conserva versión 0.13.0.

Respuesta 202 con mensaje genérico: no confirma existencia de cuentas. Errores de campos, registro deshabilitado, límite 429 o correo no disponible se presentan como error, no como éxito. Timeout/cancelación y guardia de doble envío; datos del formulario solo en memoria, sin logs ni persistencia.

Confirmación del email y elección de contraseña mediante el enlace del correo en la web. La app no presupone sesión después de enviarlo, ni inicia sesión por el simple conocimiento del email. Pendientes: endpoint de login/sesión revocable y almacenamiento seguro en dispositivo, perfil/avatar, carga/edición/denuncias y retorno a la app desde enlaces. La identidad antigua sigue preservada en el backend.

Desarrollo: los correos quedan en WordPress → Usuarios → Correos de cuentas, no se envían reales; aviso visible únicamente en `__DEV__`. En producción serán correos reales cuando el backend y HTTPS estén configurados. La app por ahora no tiene URL de producción habilitada.

## Verificación

- TypeScript, lint y 23 pruebas de API/pantallas: POST/métodos/campos, normalización, validaciones previas, errores 429, respuesta inesperada, formulario sin password, confirmación y recuperación tras error, además de las regresiones públicas.
- 24 pruebas existentes de cuentas en WordPress local, con identidades ficticias y limpieza final: email duplicado no cambia identidad/nombre/clave, permisos no escalables, reactivación, confirmación y enlace de un solo uso.
- Revisión nativa en iPhone 17/iOS 26.2: las tres pantallas, validación de registro vacío y Reactivar cuenta con teclado de email, envío y confirmación genérica usando un email ficticio inexistente. Esa comprobación no crea una cuenta ni envía correo a una persona.
- Android: `processDebugMainManifest` completado tras liberar espacio. QA de formularios en Android/dispositivos reales y sesión móvil pendientes.

Código del cliente 0.2.0 independiente de las versiones de tiendas; no se eligieron nuevos números productivos ni se modificó el bundle iOS provisional. No se publicó en tiendas.
