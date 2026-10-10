# Transición web: Astra y Elementor

Decisión del usuario: 2026-10-10. Sustituir Eventchamp por Astra gratuito y preferir
Elementor para la Home. Astra 4.14.0 y Elementor 4.3.4 activos en local mediante
tema hijo versionado `themes/foodtrucks-astra` 0.1.0. Nueva Home 4542 (o ID guardado
en `ftuy_astra_home_id` al repetir instalación). Plugin Core 0.21.0.

## Cambio realizado

- Se conserva la Home 1321 completa como borrador. Backup SQL privado local:
  `/private/tmp/foodtruck-astra-backup-20261010/food-before-astra.sql` (0600).
  Es temporal: trasladar a backup privado duradero antes de cualquier despliegue.
- WPBakery, Theme Event Champ Elements, Revolution Slider, WP Events Importer y
  Envato Market desactivados; archivos conservados, Eventchamp no eliminado.
- Nueve demos auxiliares (112,113,132,338,1092,1150,1169,1183,1221) retiradas de
  publicación como borradores, sin borrado. Home antigua también como borrador.
- Ocho secciones independientes como widgets Shortcode de Elementor: hero,
  eventos, foodtrucks, app, novedades, amigos, contacto y mapa. Se pueden reordenar
  o sustituir en Elementor; datos dinámicos y presentación de cada bloque viven en
  el plugin, no en Eventchamp. No son ocho diseños enteramente maquetados a mano.
- Cabecera/menú y pie compartidos entre tema hijo y rutas del plugin. Menú desktop
  y desplegable mobile; cuenta y formularios mantienen permisos y nonces.
- Fotos originales intactas; slider nativo con flechas, desplazamiento horizontal,
  sin autoplay ni librería extra; respeta Reduce Motion.
- Pie de tres columnas: contacto/redes, novedades y descarga de app a la derecha.
  Descargas deshabilitadas hasta republicar tiendas; activar `ftuy_store_links`
  (`enabled`, `ios`, `android`) con URLs verificadas. No se anuncia app disponible.
- Google Maps carga al acercarse a pantalla o pulsar botón. Solo eventos publicados,
  no cancelados, en curso/próximos, con coordenadas válidas (máximo 200). Links
  alternativos disponibles sin mapa. Key web preservada sin imprimir. Map ID demo
  en local; configurar `ftuy_google_map_id` propio en producción y restricciones
  de la clave de navegador. No usar la clave privada del proxy Places.

Validación: 44 comprobaciones de rutas/HTML/API; slider verificado avanzando una
foto, menú mobile y desktop, cuatro originales, mapa con marcador pop up. Clic
automatizado del marcador no validado por shadow root cerrado del navegador;
queda prueba manual, aunque el enlace alternativo está disponible.

No se midió una mejora porcentual de velocidad: se confirmó que la Home no carga
scripts Eventchamp/WPBakery/RevSlider. No activar caché persistente global sin
excluir cuentas/APIs privadas. reCAPTCHA existente rechaza localhost; pendiente
configurar dominio de pruebas/clave compatible sin debilitar protección. No se
enviaron formularios ni correos de prueba.

Las páginas institucionales antiguas con WPBakery siguen conservadas; revisar y
reconstruir las que sean útiles. No se modificaron producción, usuarios ni fotos.

Restauración: opciones anteriores en `ftuy_home_before_astra`; restaurar tema,
Home y plugins después de revisar dependencias. SQL conserva estados originales.
Script inicial reproducible: `wp eval-file tools/setup-astra-local.php`, solo local,
con Elementor activo y tema/plugin copiados. No sobreescribe una Home ya existente.

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

- [x] Preparar una nueva Home conservando página y datos originales.
- [x] Reemplazar slider y reutilizar los cuatro adjuntos arriba.
- [x] Incorporar listados del plugin con sus próximos reales, no el catálogo antiguo.
- [x] Unificar encabezado/pie del plugin con Astra, sin romper rutas ni cuentas/API.
- [ ] Reconstruir páginas útiles; revisar demos antes de retirar cualquier contenido.
- [x] Probar móvil/escritorio, activar Astra y retirar dependencias antiguas.

No se importó ninguna demo comercial ni se instalaron paquetes adicionales de widgets.
