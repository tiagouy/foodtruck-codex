# Prueba nativa iOS · 2026-10-13

- Compilación Debug del workspace FoodtrucksUY: BUILD SUCCEEDED, Xcode SDK iOS Simulator 26.5, destino iPhone XR / iOS 18.1.
- Build instalado y abierto, identidad `org.useful-media-app.foodtruck`, versión 5.0, build 1. Firma ad hoc solo para simulador; no archive/export/upload a Apple.
- Inicio cargó desde el sitio público Asado Celeste (17/10/2026), Robin/Route/Scaronne y las fotos de la comunidad. Se abrió el detalle de Asado Celeste con afiche, fecha, lugar, descripción y enlaces; luego se abrió la agenda desde el botón Ver eventos.
- Aparece aviso de desarrollo “Open debugger to view warnings” sobre la zona inferior. No se investigó su causa ni se da por validado el menú inferior: los clics automatizados en tabs no produjeron navegación. Queda revisión manual/dismiss del aviso y prueba de tabs antes de distribución.
- No se ingresaron credenciales, no se subieron fotos, no se enviaron correos ni se cambiaron cuentas. Login/perfil/uploads pendientes de prueba con el usuario.
- Metro en 8081. DerivedData regenerable `/private/tmp/ftuy-ios-online-0.12.0`; log privado `/private/tmp/ftuy-ios-online-0.12.0-build.log`.
- La consulta de Keychain encontró un certificado Apple Distribution del equipo 3Y7GC99X3J y certificados de desarrollo. Solo lectura; todavía falta configurar/verificar equipo y perfil de distribución en el proyecto, así como enlaces asociados y push iOS real.

No sustituye pruebas en iPhone físico, Release ni TestFlight.
