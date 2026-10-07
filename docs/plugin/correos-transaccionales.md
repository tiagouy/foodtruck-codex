# Entrega de correos de cuentas

Diagnóstico del 7 de octubre de 2026, sin cambiar producción ni enviar mensajes reales:

- El WordPress local importado tiene WP Mail SMTP activo, mailer SMTP, remitente y host/autenticación configurados. Esto no comprueba que las credenciales sigan válidas ni que producción entregue bien.
- DNS público `foodtruckuruguay.com`: SPF publicado con el proveedor de hosting; la consulta TXT de `_dmarc.foodtruckuruguay.com` no devolvió registro. No se verificó DKIM: requiere conocer el selector del proveedor o una cabecera real.
- El desarrollo local sigue capturando mensajes en Usuarios → Correos de cuentas, sin envío externo.

Antes de habilitar registro en producción:

1. Confirmar remitente del dominio y proveedor de envío; decidir si mantener SMTP actual o pasar a un servicio transaccional. No contratar ni cambiar DNS sin elección de Santi.
2. Verificar SPF con el proveedor elegido, habilitar DKIM y publicar DMARC inicialmente con monitoreo; no imponer rechazo hasta comprobar todas las fuentes legítimas del dominio.
3. Usar envío autenticado cifrado. Secretos fuera de Git, respaldos privados; no copiar credenciales al código móvil.
4. Verificar From y alineación de dominio mediante cabeceras reales en Gmail/Outlook y otros destinos; revisar rebotes y logs sin registrar enlaces de recuperación ni tokens.
5. Probar registro, reactivación y recuperación reales, enlace único/caducidad, bandeja de entrada/spam y reintento. No enviar una campaña de reactivación masiva como parte de estas pruebas.

SMTP o SPF por sí solos no garantizan bandeja de entrada. [Google recomienda configurar SPF, DKIM y DMARC](https://support.google.com/mail/answer/81126?hl=en-en); también influyen reputación y contenido. Esta parte sigue pendiente de configuración/QA de producción.
