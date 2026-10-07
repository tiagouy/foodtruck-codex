# Aplicación móvil nueva

La nueva aplicación móvil está en `apps/mobile/`. Base `0.1.0` creada el 2026-10-07, tomando BuenCafé como referencia y adaptando su estructura de navegación, cabecera y tarjetas. React Native 0.84.0, React 19.2.3 y TypeScript, con proyectos nativos limpios. Ver [implementación y ejecución](base-0.1.0.md).

La aplicación consumirá exclusivamente la API versionada del plugin `Foodtrucks UY Core`; no accederá a MySQL ni a las APIs PHP históricas.

Estado 0.3.0: [ingreso nativo y sesión segura](ingreso-0.3.0.md), sin botón de ingreso al sitio. Conserva [registro, reactivación y recuperación](cuentas-0.2.0.md); elegir contraseña ocurre en el enlace del correo. Face ID, edición de perfil y subida de fotos pendientes. Sin artefacto publicable ni entrega productiva de correo validada.

Actualización 0.1.1: objetivo de tiendas confirmado como actualización de apps existentes. Android toma el applicationId del APK anterior, con debug separado; iOS presenta identificadores contradictorios y espera confirmación de App Store Connect. Ver [continuidad de tiendas y firma](continuidad-tiendas.md). No crear apps nuevas ni publicar la identidad de desarrollo.

## Inicio y navegación — propuesta 2026-10-07

La preocupación de Santi es que un inicio basado exclusivamente en fotos dependa de que la comunidad publique con frecuencia. La lectura pública sin sesión se mantiene; no implica que las fotos deban ser la pantalla inicial.

Propuesta de navegación: Inicio, Eventos, Foodtrucks, Fotos y Mi cuenta. Publicar foto será una acción dentro de Fotos, con ingreso/registro si no hay sesión, no una pestaña vacía para visitantes.

Inicio orientado a descubrir:

- Próximos eventos publicados, ordenados por fecha. Si no hay próximos, mensaje claro y acceso al histórico; nunca presentar eventos pasados como próximos.
- Foodtrucks publicados para explorar, con acceso al directorio. No mostrar las muestras pendientes ni inventar horarios o ubicación actual: departamento/localidad son su base.
- Fotos recientes de la comunidad como bloque complementario, con fecha visible y acceso a Fotos. El contenido histórico no se presenta como actividad nueva.
- Ocultar bloques sin contenido y mostrar alternativas útiles. Estados de carga, error y catálogo vacío distintos; no inventar actividad ni duplicar tarjetas para llenar espacio.

No se definen aún recomendaciones personalizadas, rotación aleatoria, geolocalización automática ni asociaciones evento–foodtruck. La jerarquía visual se validará antes de cerrar el inicio definitivo.

## Referencias y siguiente implementación

Leído `docs/referencias/BUENCAFE_APP_NUEVA.md` y confirmado el código de la app nueva en `/Users/Santi/CLIENTES/_repo-git/buencafe-codex2`. Sirve como referencia de organización React Native/TypeScript, pantallas, selección de imágenes y flujos de cuenta. BuenCafé permanece intacto. No copiar sus nombres legacy, backend, credenciales, claves de firma ni configuración Firebase.

Navegación, consultas públicas y sesiones móviles con cuentas WordPress implementadas. Siguiente: perfil y fotos propias, subida, edición y denuncias desde app y apertura de enlaces Android/iOS. Las escrituras de contenido móvil todavía requieren implementación del lado del plugin.

Santi autorizó reutilizar BuenCafé como base. Se generó un proyecto separado con identidad provisional `com.foodtrucksuy.dev`, no apta para actualizar directamente las apps existentes en tiendas. Antes de publicar hay que confirmar identificadores/firma históricos de Foodtrucks UY. Autenticación móvil, Firebase y enlaces asociados quedan para etapas propias.
