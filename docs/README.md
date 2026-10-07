# Foodtrucks Uruguay — reconstrucción

Este repositorio separa la reconstrucción del producto de los materiales históricos que sirven de referencia.

| Área | Ubicación | Propósito |
| --- | --- | --- |
| Documentación del proyecto | `docs/` | Decisiones, modelo de datos, migración y evolución. |
| Aplicación móvil nueva | `apps/mobile/` | Base React Native/TypeScript, adaptada de BuenCafé y conectada a la API pública del plugin. |
| Plugin WordPress | `plugins/foodtrucks-uy-core/` | Dominio, panel administrativo, cuentas, API y migración. |
| Material histórico | `Cosas viejas/`, `foodtruckuruguay.com/`, `Bds/` | Solo consulta: no continuar desarrollo allí. |
| Guía de referencia BuenCafé | `docs/referencias/BUENCAFE_APP_NUEVA.md` | Patrón técnico para una reconstrucción moderna. |

## Regla de trabajo

El código nuevo se crea únicamente en `apps/` y `plugins/`. La copia completa de hosting y las bases SQL permanecen intactas mientras se analizan y se migran de forma controlada.

## Punto de partida

El checklist por etapas está en [plan de trabajo](04-plan-de-trabajo.md).

El primer módulo en definición es [Eventos](05-eventos.md), con carga comunitaria, revisión y listado compartido con la app.

La arquitectura propuesta y el alcance están en [visión y arquitectura](01-vision-y-arquitectura.md). El plan de los datos históricos está en [migración](02-migracion-de-datos.md) y las reglas de releases en [versionado y operación](03-versionado-y-operacion.md).

La documentación específica está en [app móvil](app/README.md), [plugin](plugin/README.md), [changelog del plugin](plugin/CHANGELOG.md) y [material histórico](legacy/README.md).
