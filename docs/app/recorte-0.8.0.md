# Encuadre de fotos de publicaciones — 0.8.0

Al elegir una imagen desde Subir foto, se abre un editor nativo con marco cuadrado fijo. Permite mover/zoom y confirmar con **Usar foto** o cancelar. Cancelar cualquiera de los dos pasos conserva la foto anterior y el texto/dirección del formulario. No se publica nada hasta tocar Publicar foto.

Se mantiene `react-native-image-picker` para la biblioteca del sistema. La dependencia nueva `react-native-image-crop-picker` **0.52.0 exacta** se usa únicamente con `openCropper`, no para abrir la biblioteca ni cámara. [Documentación del proyecto](https://github.com/ivpusic/react-native-image-crop-picker#crop-picture). Requiere recompilar los binarios; actualizar solo JavaScript sobre un binario anterior no alcanza.

Salida interna cuadrada de 900 × 900, JPEG solicitado, sin base64/EXIF en la respuesta. Se valida el archivo local, dimensiones cuadradas, MIME y tamaño antes de aceptar la nueva selección. No se sustituye por la original si falla el recorte. Preview cuadrado adaptable al ancho, limitado en tablets, sin franjas laterales.

El servidor conserva el comportamiento existente: optimiza el archivo enviado hasta 900 px y 300 KB, sin aplicar otro recorte ni cambiar encuadres históricos. Tampoco se cambia el avatar de perfil, la API ni los datos migrados. Temporales del recortador viven en la caché local de la app, no se sube el original. La limpieza de caché queda a cargo de la librería/sistema; no se borra un archivo mientras una subida/reintento puede necesitarlo.

No se agregan permisos de cámara, micrófono ni acceso general a la galería. Android elimina explícitamente el permiso heredado `WRITE_EXTERNAL_STORAGE` de la dependencia: el editor utiliza el archivo que seleccionó la persona. Se conservan las descripciones de privacidad iOS existentes.

Pruebas automatizadas: 47 Jest, TypeScript y lint. Cobertura de archivo realmente recortado, opciones cuadradas, cancelación, resultado inválido/error nativo y conservación de preview anterior. iOS compilado/instalado en iPhone 17: selección de cascada de ejemplo → editor → Usar foto → preview cuadrado, sin publicar la muestra. El JPEG temporal resultó realmente de 900 × 900 y 282.762 bytes. Android `processDebugMainManifest` exitoso y manifiesto final sin WRITE_EXTERNAL_STORAGE; falta QA de gestos en dispositivo Android. Se reutilizó DerivedData para no duplicar las compilaciones.
