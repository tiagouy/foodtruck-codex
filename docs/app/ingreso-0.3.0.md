# Ingreso nativo — app 0.3.0 / plugin 0.14.0

Mi cuenta muestra primero email y contraseña, ingreso y recuperación. Conserva la reactivación destacada y el acceso al registro. Se elimina el botón de ingresar en el sitio. La app sigue abriendo en Inicio y permite consultar contenido sin cuenta.

Se autentica con WordPress mediante la nueva API del plugin. Cuentas pendientes deben confirmar/reactivar antes de ingresar; las administrativas no reciben sesiones móviles. Token aleatorio de 256 bits, duración de 30 días, hasta cinco sesiones por usuario. El servidor almacena solo su hash y una firma ligada a la contraseña; resetear/cambiar contraseña invalida sesiones anteriores. Cerrar sesión revoca el token y borra el almacenamiento seguro del dispositivo. Un fallo de conexión al cerrar se muestra y permite reintentar.

El token se guarda en Keychain/Keystore, nunca la contraseña. El campo contraseña se vacía tras cada intento. Al abrir Mi cuenta se valida la sesión guardada; errores de red no borran el token, pero tampoco presentan un perfil no verificado. La sesión no habilita todavía edición de perfil ni subida de fotos: esas funciones siguen pendientes. Face ID/huella se pospone hasta validar este circuito y el desbloqueo en dispositivos reales; no hay un botón de biometría ficticio.

Rutas: POST `accounts/login`, GET `accounts/session`, POST `accounts/logout`. La autorización es `Bearer`; no se usan cookies WordPress ni se expone el ID histórico. Respuestas de cuentas `no-store, private`; producción requiere HTTPS (HTTP solo en entorno local). Rate limit por IP/email. Servidor web de producción deberá reenviar Authorization al PHP; probar antes de liberar.

Keychain 10.0.0 requiere recompilar: no basta Fast Refresh sobre el binario anterior. iOS debug mantiene bundle de desarrollo hasta confirmar el de App Store Connect. Android requiere recompilar y QA; no es una versión de tienda lista para publicar.

Keychain en iOS Simulator requiere firma ad hoc y entitlement de aplicación. `Simulator.entitlements` se aplica exclusivamente a Debug con SDK `iphonesimulator`; no contiene un Team ID ni permisos de producción. No compilar este circuito con `CODE_SIGNING_ALLOWED=NO`. La firma/distribución del iPhone real deberá configurarse con la identidad confirmada de tienda.

Pruebas: 29 Jest (incluye persistencia segura, revocación y errores de conexión), TypeScript/lint, 13 comprobaciones de sesión, 5 de HTTP real, 24 de cuentas, 19 de API pública y 12 de identidad/configuración. Fixtures de autenticación aislados del bucket de Loginizer y retirados al finalizar; el plugin de seguridad permanece activo. Usuarios reales y 51 publicaciones conservados.

Compilación iOS Debug con firma ad hoc y permisos de simulador exitosa. Pantalla revisada en iPhone 17/iOS 26.2: campos correctos, sin botón al sitio, lectura del Keychain vacío sin error y validación de campos vacíos. El circuito completo con una cuenta real y persistencia tras reiniciar queda para la prueba manual del usuario; no se ingresaron sus contraseñas por UI.

Referencias: [almacenamiento seguro](https://oblador.github.io/react-native-keychain/docs/usage/).
