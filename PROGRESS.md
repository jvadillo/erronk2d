# Estado de la tarea

## Objetivo
Cambiar la paleta de la UI de verde a morado, siguiendo `docs/retos_morado.png`.

## Completado
- Color principal `#82005e` (`--green` en `resources/css/app.css`, lateral, favicon, `theme-color`, barra de progreso de Inertia).
- Morado oscuro `#6f0050` en botón principal y hover del lateral; tintes rosas claros (`#f7e4f0`) para elemento activo, marca y estado "En evaluación"; "En curso" en morado sólido.
- Grises verdosos convertidos a grises neutros con leve tinte morado; fondo `#f6f4f5`, texto `#2b1f28`.
- Se mantienen verdes/azules semánticos: `.notice.success`, `.badge.published`, `.badge.finished`.
- Revisado visualmente con una vista previa estática del dashboard.

- Desplegado en producción (`1c60762`) el 7/10/2026 con `ops/deploy`; copia `backups/production-20261007-1c60762`; HTTPS verificado. Detalle en `docs/despliegue.md`.

## Pendiente
- Nada.

## Archivos relevantes
- `resources/css/app.css`, `resources/css/sidebar.css`, `resources/css/rubrics.css`.
- `resources/js/components/EvidenceWorkspace.vue`, `resources/js/pages/Challenge.vue`, `resources/js/app.ts`.
- `resources/views/app.blade.php`, `public/favicon.svg`.

## Decisiones
- El nombre de variable `--green` se conserva para no tocar todas las referencias; ahora contiene el morado principal.
- Conversión de tono automática (verde → morado, HSL) con ajustes manuales en lateral y estados.

## Último error
- Ninguno.
