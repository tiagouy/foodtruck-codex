# Concordancia visual web/app · 0.11.0

2026-10-10.

Home: logo transparente aprobado, marco proporcional al 90% del contenido y
sección de eventos en azul #05204B. Agenda: fondo azul, texto blanco y pestañas
rectas Próximos/Pasados con selección naranja #C94308.

Carrusel compartido: afiches completos 4:5, radios de 10, calendario de inicio
con día de semana/número/mes fuera del arte, título, lugar y rango completo de
fechas. Fechas calculadas como fechas de calendario, no instantes dependientes
de la zona del dispositivo. Tipografía nativa, sin adornos ni inclinaciones.
Se retira la etiqueta genérica sobre el afiche; Cancelado se conserva.

Se mantienen navegación inferior, API, filtros de agenda existentes,
paginación, sesión, push, fotos, iconos y splash. Los filtros Hoy/Este finde de
la web NO se agregaron a la app en esta iteración.

Validación: TypeScript, ESLint y 71 pruebas Jest. Android arm64 Debug compilado
e instalado con `adb install -r` en el emulador existente (sin borrar datos).
Captura nativa de Home revisada: logo proporcionado, banda azul, afiche entero
y tarjeta de metadatos. Se corrigió el ancho del día de dos cifras observado en
esa revisión. La navegación manual a la agenda y QA visual en iOS quedan para
la siguiente revisión; no se afirma prueba visual de ambas plataformas.

El emulador mostró un aviso de desarrollo LogBox, no una pantalla de error;
esta comprobación no equivale a validar una build de producción.
