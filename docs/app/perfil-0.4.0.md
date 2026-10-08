# Mi cuenta y Configuración — app 0.4.0 / plugin 0.15.0

Mi cuenta deja de ser una pantalla de bienvenida con logout: muestra el nombre público, avatar propio si existe y Mis fotos. Grilla de dos columnas con miniaturas cuadradas, carga por lotes de 12 y acceso al detalle existente. El recorte es visual, no modifica los archivos conservados.

Se consulta la API pública de publicaciones por ID WordPress del usuario autenticado. La relación con IDs históricos ya migrados conserva las fotos antiguas. Solo aparecen publicaciones `published`; no se vuelve a importar ni se recuperan fotos despublicadas. Las respuestas con otro autor se rechazan, y salir de la vista cancela la consulta. Estados de carga, error, reintento y vacío separados.

Engranaje accesible arriba a la derecha → Configuración. Nombre y apellido editables, email de solo lectura, Guardar cambios y Cerrar sesión. POST `accounts/profile` requiere token válido y usa exclusivamente su propietario; ignora cualquier ID/rol/email/contraseña recibido. Cambiar el nombre no modifica los vínculos históricos, la sesión ni la autoría de publicaciones. Se actualiza `display_name` como nombre + apellido.

El cambio de foto de perfil y la subida de fotos siguen pendientes. No se añadieron puntuaciones ni aprobación previa. Las futuras publicaciones se publicarán directamente, con despublicación posterior por el administrador cuando corresponda.

Se evita mostrar login mientras se valida la sesión al abrir Mi cuenta. Al volver desde Configuración se revalida para reflejar el nombre nuevo o el cierre de sesión.

QA: 33 pruebas Jest, TypeScript/lint, 17 comprobaciones de sesión/perfil. En el iPhone 17/iOS 26.2 se verificó la sesión existente de Santi y sus nueve fotos, miniaturas cuadradas en dos columnas y acceso a Configuración. No se editó su nombre ni se cerró su sesión para probar: las escrituras se comprobaron con fixtures que se eliminan al finalizar.
