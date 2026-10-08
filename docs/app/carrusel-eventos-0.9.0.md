# Carrusel de eventos — 0.9.0

Home y Eventos comparten una presentación nueva sin dependencias adicionales: Animated.FlatList horizontal, snap de una tarjeta y escala interpolada en el hilo nativo. La tarjeta central ocupa el 80% del ancho (máximo 360 px); sus vecinos bajan al 90% de escala. Imagen cuadrada, fecha, título y lugar, con indicación de entrada libre/cancelado cuando los datos lo permiten.

No es un carrusel infinito ni un límite de tres registros: se ve el centro y parte de sus vecinos, conservando cada evento una sola vez. Home consulta hasta tres próximos. Agenda conserva todos los resultados paginados y permite cargar más; no mezcla pasados con próximos.

Controles anterior/siguiente con área de 44 px, contador y tarjetas accesibles; Reduce Motion evita escala y animación por flechas. Mantiene estados de carga, vacío, error y recarga. Sin cambiar fotos de la comunidad, foodtrucks ni API.

Verificado: 53 pruebas, TypeScript/lint e iPhone 17 con cuatro eventos históricos; avance por flecha, segunda tarjeta centrada con vecinos y apertura de detalle. Pendiente confirmar manualmente gesto de deslizar (el arrastre automatizado actuó como toque) y probar Android. No implica build de tiendas ni publicación.
