# Foto de perfil — app 0.5.0 / plugin 0.16.0

Corrección app 0.5.1: el selector iOS 8.2.1 devuelve JPEG como `image/jpg`; la validación inicial solo aceptaba `image/jpeg` y los rechazaba antes de subir. Se normaliza el alias, sin modificar el módulo nativo ni relajar la validación de contenido del servidor. Prueba con imagen de muestra del simulador hasta vista previa, sin guardar una foto en la cuenta de Santi. El aviso de guardado distingue nombres solamente de nombres y foto.

Editar perfil permite elegir una imagen de la biblioteca del dispositivo y previsualizarla en un círculo. Solo se sube al tocar Guardar cambios. Cancelar el selector no modifica el perfil. Una imagen demasiado pesada o no compatible muestra un mensaje sin subirla. Se guardan primero los nombres y luego la foto: si falla la foto, el mensaje informa el guardado parcial y permite reintentar.

Mi cuenta muestra nombre a 20 puntos y avatar circular arriba a la derecha. Diámetro aproximado de 20% del ancho del teléfono, máximo 128 puntos para tablets. Sin avatar aparece un icono de persona; tocarlo abre Configuración. Mis fotos pasa a 17 puntos. Se mantienen las nueve fotos históricas de Santi y la grilla de dos columnas.

Selector react-native-image-picker 8.2.1, como referencia de BuenCafé. Pide una sola foto y representación compatible, preprocesa para no transportar una imagen innecesariamente grande, sin base64 ni metadatos extra. [Documentación oficial](https://github.com/react-native-image-picker/react-native-image-picker). iOS incluye la descripción de acceso a fotos y requiere recompilación; Android usa el selector del sistema, sin permiso general de almacenamiento ni cámara.

POST `accounts/avatar` con Authorization Bearer y archivo multipart `photo`. El servidor no acepta ID de propietario desde el cliente: valida sesión y carga HTTP real. Procesa JPEG/PNG/WebP de hasta 5 MB y 40 megapíxeles: recorte central 500×500, JPEG fondo blanco, <=120 KB y metadata 72 dpi. Solo guarda el archivo procesado en `/media/perfiles/{UUID}/`; el origen no entra a la biblioteca.

El medio anterior se conserva, no se borra automáticamente. Una sustitución fallida no lo desvincula. Diez solicitudes por usuario/hora, bloqueo por usuario durante procesamiento, recuperación tras cinco minutos si una ejecución se interrumpe. Upload móvil hasta 45 segundos; respuestas privadas no cacheables. Avatar nuevo tiene URL distinta para evitar caché vieja.

Verificaciones: 37 pruebas Jest, TypeScript/lint y 17 regresiones de sesión/perfil; ocho comprobaciones HTTP de avatar con fixtures retirados. Optimización, propietario, preservación del original/medio previo, formato falso, >5 MB y ausencia de sesión. iOS Debug recompilado con firma ad hoc y permisos de simulador. Se verificaron diseño, apertura del selector privado de fotos y cancelación sin cambios. Android y dispositivos físicos aún requieren QA. No se sustituyó la foto personal de Santi durante pruebas.
