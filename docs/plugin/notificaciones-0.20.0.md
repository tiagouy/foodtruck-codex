# Notificaciones desde WordPress — 0.20.0

Foodtrucks UY → Notificaciones. Solo `manage_options`; formulario POST con nonce.
Envío manual mediante PHP a pushv3, app 12. No altera el circuito móvil.

## Configuración por entorno

Pendiente para el despliegue a producción (acordado el 2026-10-10): configurar las
variables en el servicio PHP y comprobar un envío de prueba autorizado. No se
configuran por ahora en MAMP ni se almacenan valores nuevos. El panel permanece
sin envío habilitado mientras falte el entorno.

El proceso que ejecuta PHP (Apache/PHP-FPM/MAMP) debe recibir:

```text
PUSH_API_KEY=<clave privada>
FTUY_PUSH_TOKEN_APP=<token de Foodtrucks>
```

No basta exportarlas en una terminal si PHP web corre en otro proceso. Configurar
en el servicio/hosting fuera de la carpeta pública y reiniciar el servicio cuando
corresponda. En PHP-FPM puede requerirse permitir estas dos variables explícitamente
en el pool. No usar phpinfo público para comprobar valores. El panel solo muestra
disponible/no disponible. No guarda secretos en options, formularios, JS, logs ni Git.
No se necesita service account de Firebase: queda exclusivamente en pushv3.

Fuente: `/Users/Santi/CLIENTES/_repo-git/pushv3/docs/API.md`, leído completo.
`POST https://useful-media-push.org/pushv3/api/` por formulario, `metodo=send`,
`titulo`, `mensaje`, `key`, `tokenApp`, `idapp=12`. Sin redirecciones; TLS validado.

## Uso

Destino inicial usuarios específicos: IDs WordPress separados por comas. Para
probar con Santiago, confirmar su ID (históricamente 100) y sesión móvil activa.
Puede notificar todos los dispositivos asociados a ese usuario, no solo un teléfono.
No permite ID 0 ni vacío en esta modalidad. Envío general exige casilla explícita;
incluye tokens activos de usuarios sin login. No hay envíos automáticos al instalar.

Historial local en tabla propia `ft_push_sends`: autor, título, texto, audiencia,
IDs de destino, fecha UTC y conteos. No conserva tokens FCM, claves ni respuestas
crudas. Resultado aceptado significa aceptado por FCM, no recibido/leído. Un UUID
único reclama el envío antes del HTTP: doble clic/reenvío del formulario no duplica.
Timeout, 5xx o respuesta no interpretable: resultado incierto, sin reintentos
automáticos. Verificar historial de pushv3 antes de crear un nuevo envío.

Sin programación, buzón ni navegación profunda en esta etapa. Sin tocar credenciales
de la app por decisión del usuario; separación/rotación de permisos pendiente.

## Validación

`wp eval-file tests/push-admin.php`: credenciales sintéticas y HTTP interceptado,
sin llamadas reales. Verifica permisos, confirmación general, entradas inválidas,
configuración ausente, resultado FCM/parcial/rechazo/timeout, idempotencia y ausencia
de claves en HTML/historial. Limpia exclusivamente sus propios registros sintéticos.
Prueba real necesita entorno configurado y autorización específica del destinatario.
