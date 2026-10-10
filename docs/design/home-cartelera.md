# Home: cartelera elegida

2026-10-10 · Core 0.22.0 · Implementación local.

El usuario eligió el boceto «Cartelera de sabores», sin rayitas naranjas junto a los títulos ni tipografía inclinada. No es la variante editorial anterior.

- Conserva slider y cuatro fotografías originales.
- Hero compacto y bloque azul de agenda.
- Afiches completos 4:5 (`object-fit: contain`), sin modificar originales.
- Fecha, nombre y lugar son datos del evento fuera de la imagen, no parte del afiche.
- Cuatro columnas desktop; carrusel nativo con parte de la siguiente tarjeta en mobile.
- Fechas múltiples conservan rango completo debajo del lugar.
- Filtros servidor Hoy / Este finde / Próximos; enlace al mapa existente.
- Hoy se ofrece solo si hay un evento publicado no cancelado que coincide con la fecha local y aún no finalizó.
- Este finde corresponde al viernes–domingo próximo, o a los días restantes si ya es fin de semana. Si no hay eventos para ese período, la apertura predeterminada muestra Próximos.
- Hasta ocho eventos por selección, orden cronológico; enlace a la agenda completa.
- Tipografía condensada del sistema, sin peticiones a fuentes externas ni dependencia de Elementor Pro.

Sin cambios en fechas, usuarios, API, aplicación ni sitio de producción. Los datos reales de prueba pueden tener fechas diferentes de las que figuran impresas en sus afiches; corregirlos desde el administrador si corresponde, no desde el diseño.

Verificación: lint PHP, pruebas de integración locales, filtro Próximos, escritorio y mobile de 390 px, proporción 4:5 y ausencia de desbordamiento horizontal.
