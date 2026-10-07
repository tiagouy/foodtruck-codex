# Changelog de la app

## 0.1.1 — 2026-10-07

- Objetivo confirmado: actualizar las apps existentes, no crear fichas nuevas en tiendas.
- Android applicationId histórico verificado en el APK firmado: `org.useful_media_app.foodtruck`. Debug separado con sufijo `.dev`; namespace Kotlin interno independiente del ID publicado.
- APK de referencia 4.2.0/40200; valores locales 4.2.1-dev/40201 no confirman la numeración actual de Play. Tareas release exigen versión/código explícitos, pendientes de comprobar en consola, y firma aún sin configurar.
- Auditoría iOS identifica discrepancia entre Firebase (guiones) y config/enlaces (guiones bajos). Bundle de producción pendiente de confirmación en App Store Connect; no sustituido por una suposición.
- Keystore y Firebase históricos intactos/fuera de Git. Ver [continuidad de tiendas](continuidad-tiendas.md).

## 0.1.0 — 2026-10-07

- Base React Native/TypeScript independiente, adaptada de la estructura, cabecera y tarjetas de BuenCafé. Proyectos Android/iOS nuevos, identidad provisional y sin copiar claves/Firebase.
- Inicio combinado, eventos próximos/pasados, directorio de foodtrucks y fotos con paginación y detalles, todos consultados en la API del plugin.
- Fechas históricas visibles, estados de vacío/error/reintento, cancelación y revalidación al enfocar detalles. No se inventa contenido para llenar pantallas.
- Cuenta con reactivación destacada y enlaces explícitos al sitio; sesión/subida nativas aún pendientes.
- 14 pruebas, TypeScript/lint y 19 comprobaciones reales de API de lectura. Compilación y revisión inicial iOS Simulator; Android/dispositivos pendientes.
- No apta para tiendas todavía: configuración productiva, firma, branding y revisión de alertas de dependencias pendientes. Plugin sin cambios, conserva 0.13.0.
