# Subida de fotos desde la app — 0.7.0

Acceso en Fotos y Mi cuenta → Subir foto. Sin sesión invita a ingresar/registrarse; con sesión permite elegir una foto, ver su preview, escribir texto y dirección. No hay puntaje, selección de autor ni estado editable. La foto se publica directamente, sin moderación previa.

El selector usa la biblioteca nativa (una imagen). Cancelar no modifica la selección. JPEG de iOS `image/jpg` se normaliza; se permite JPG/PNG/WebP y se muestra un error simple si supera 5 MB. Las medidas/calidad son internas, no instrucciones para la persona.

## Publicación y reintentos

`POST /foodtrucks-uy/v1/publications/upload` con bearer y multipart (`photo`, `caption`, `address`, `request_id`, coordenadas opcionales). Token solo en Keychain/cabecera, nunca en parámetros de navegación. El servidor asigna autor y estado publicada; ignora puntaje y campos manipulados.

La imagen se optimiza conservando su proporción, máximo 900 px en el lado mayor y 300 KB, JPEG con fondo blanco y metadatos de 72 dpi. Solo la copia optimizada se guarda en `/media/publicaciones/`. No se amplían imágenes pequeñas ni se recortan. Entrada máxima 5 MB/40 MP.

Se guarda un recibo privado en `ft_publication_uploads`, junto a publicación e historial dentro de una transacción. Una misma cuenta y `request_id` devuelven el ID creado, sin generar otra foto. La app conserva ese ID durante los reintentos en la pantalla. Ante un resultado incierto bloquea la edición e invita a reintentar. **No es un borrador persistente:** cerrar la app o abandonar esta pantalla puede perder este identificador; no garantiza deduplicación entre borradores nuevos.

Límite diez intentos procesados por cuenta/hora y exclusión de subida simultánea por cuenta. Un bloqueo abandonado se recupera tras cinco minutos. Fallar antes de completar elimina únicamente el medio temporal recién creado, no fotos previas.

Al completar ofrece Ver mi foto / Ir a Mis fotos. Las listas se revalidan al volver. El administrador puede editar, denunciar o despublicar desde el plugin. Despublicar oculta detalle/listas/API; un reintento antiguo no republica una foto retirada.

## Autocompletado

La app pide sugerencias después de tres caracteres y 450 ms de pausa. Las solicitudes anteriores se cancelan y se descartan resultados obsoletos. Seleccionar consulta dirección/coordenadas; escribir encima elimina la ubicación anterior. Si Google falla o no está configurado, se permite dirección manual, sin inventar coordenadas.

El plugin hace de proxy autenticado para Places API New (`places/autocomplete`, `places/details`). No distribuye claves a la app. Solo Uruguay/español; máximo cinco sugerencias; campos mínimos. El detalle debe corresponder a una sugerencia reciente de esa cuenta/sesión. IDs temporales durante cinco minutos; límite 120 consultas por cuenta/hora.

App 0.8.2/plugin 0.19.0: seleccionar mantiene el nombre principal de la sugerencia en Dónde fue (por ejemplo Expo Café Uruguay). La dirección postal devuelta por Google va a `street_address`, un campo independiente en la tabla/admin. `address` conserva el nombre visible para lista/detalle web y app, junto con LAT/LONG. Cambiar el texto manualmente descarta la selección y no envía calle/coordenadas antiguas. Sin selección sigue siendo dirección manual. No se reescriben fotos históricas.

**Actualización plugin 0.18.1:** en local se reutiliza la clave web existente cuando no está definida la constante privada de servidor. Verificada contra Places New sin cambiar restricciones. La clave nunca se entrega a la app. En producción definir `FTUY_GOOGLE_PLACES_SERVER_KEY` en el entorno privado de WordPress (`wp-config.php`, fuera del repo), destinada a servidor, restringida a Places API New y a IP del servidor cuando sea posible, con cuotas/alertas. No quitar restricciones a la clave web por comodidad; no pegar claves en chat ni subirlas a Git.

Documentación oficial: [Autocomplete New](https://developers.google.com/maps/documentation/places/web-service/place-autocomplete), [Place Details](https://developers.google.com/maps/documentation/places/web-service/place-details), [sesiones](https://developers.google.com/maps/documentation/places/web-service/using-session-tokens), [políticas y atribuciones](https://developers.google.com/maps/documentation/places/web-service/policies). La lista compacta muestra atribución Google Maps. Antes de publicar en tiendas: validar clave, cuotas, políticas de conservación de datos/ubicaciones y avisos públicos de privacidad/términos. No se almacena caché de respuestas de Google.

## Verificación

- 45 pruebas Jest de app, TypeScript y lint.
- 14 comprobaciones HTTP reales de subida contra WordPress local con usuario e imágenes sintéticos, limpiados al terminar: autenticación, campos, optimización, autor, publicación inmediata, reintentos y despublicación.
- 10 comprobaciones del proxy Places con respuestas simuladas: sin consultas externas/gasto. En plugin 0.18.1 también se verificó Google real con la clave web existente desde local: sugerencias/dirección/latitud/longitud para Plaza Villa Biarritz usando cuenta sintética limpiada. Sugerencias visibles en la app para la búsqueda Villa d, conservando borrador real sin publicar.
- QA en iPhone 17: acceso, formulario, selector nativo y cancelación. Sin publicar fotos nuevas con la cuenta real del usuario. Android y flujo real completo desde teléfono pendientes.
