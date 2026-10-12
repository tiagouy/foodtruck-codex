# Conexión al sitio publicado · 2026-10-12

App interna 0.12.0, versión de tiendas 5.0. Backend Debug y Release: `https://foodtruckuruguay.com`. Todas las peticiones siguen usando `/wp-json/foodtrucks-uy/v1/`. MAMP se habilita explícitamente mediante `USE_LOCAL_BACKEND`, solo en Debug.

## Verificado

- 10 suites, 72 pruebas Jest correctas; TypeScript y ESLint correctos.
- El transporte/parser real de la app pasó 19 comprobaciones de lectura contra producción: agenda próxima/pasada, detalle de evento, catálogo foodtrucks, paginación y detalle de fotos, ausencia de campos privados y 404 de publicación inexistente.
- El servidor devolvió 50 publicaciones públicas en esta comprobación. No se crearon, modificaron ni borraron cuentas/publicaciones ni se enviaron correos.
- Android versionName 5.0/code 50000; iOS marketing version 5.0/build 1, con bundles confirmados conservados.

## Pendiente

No había emuladores ni simuladores abiertos al verificar. Estas pruebas de Node no sustituyen pruebas de red/renderización nativa. Probar login con contraseña vigente del sitio publicado, cargar una foto identificada como prueba, revisar perfil/fotos/eventos/foodtrucks y denunciar/despublicar cuando corresponda.

Las sesiones guardadas en local se revalidan contra el servidor público; pueden no ser válidas y requerir ingreso nuevamente. No se transfirieron credenciales manualmente. Los datos de prueba que se publiquen ahora serán visibles en producción.

Antes de distribuir: enlaces asociados del dominio, firma y equipo Apple, APNs/iOS y backend push configurado en servidor. No se archivó ni subió a TestFlight. No se considera el cambio de URL una validación completa de distribución.
