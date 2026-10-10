# Transición web: Astra y Elementor

Decisión del usuario: 2026-10-10. Sustituir Eventchamp por Astra gratuito y preferir
Elementor para la Home. Astra 4.14.0 y Elementor 4.3.4 instalados en local, todavía
inactivos. Sin cambios de Home, tema activo o contenido original en esta etapa.

## Conservar las fotos del slider

El usuario quiere mantener las fotografías actuales usando otro componente, sin
dependencia de Eventchamp. Home original: página 1321, Inicio. Slider antiguo:
`eventchamp_event_counter_slider`, imágenes en este orden:

| Adjunto | Archivo en uploads/2021/09 |
| --- | --- |
| 2802 | soniarnocuestanada.jpg |
| 2803 | new-yorksiempre.jpg |
| 2804 | palmeritas.jpg |
| 2805 | playitaafull.jpg |

Los cuatro adjuntos y archivos existen en local. No borrar, sustituir ni redibujar.
Reutilizar originales en el nuevo slider; validar encuadre y altura en mobile.
El componente se decidirá al construir la nueva Home; no asumir Elementor Pro.

## Dependencias encontradas

21 páginas publicadas contienen shortcodes de WPBakery o Eventchamp, entre ellas
Inicio, Contacto, Novedades, Prensa, Reglamentación y páginas de demostración del
tema. La Home obtiene foodtrucks del CPT antiguo speaker y lista históricos como
próximos; debe pasar a consumir los datos del plugin. El enlace principal de
eventos apunta incorrectamente a /eventos/ sin el subdirectorio local /foodtruck/.

- [ ] Preparar una nueva Home conservando página y datos originales.
- [ ] Reemplazar slider y reutilizar los cuatro adjuntos arriba.
- [ ] Incorporar listados del plugin con sus próximos reales, no el catálogo antiguo.
- [ ] Unificar encabezado/pie del plugin con Astra, sin romper rutas ni cuentas/API.
- [ ] Reconstruir páginas útiles; revisar demos antes de retirar cualquier contenido.
- [ ] Probar móvil/escritorio antes de activar Astra y retirar dependencias antiguas.

No se importó ninguna demo ni se activaron/desactivaron plugins o temas.
