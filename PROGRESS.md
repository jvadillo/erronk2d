# Estado de la tarea

## Objetivo
Corregir la navegación de Evaluación para volver fácilmente a la tabla general desde las rúbricas técnica y transversal.

## Completado
- Botón «Volver a todas las evaluaciones» junto a la rúbrica; el botón superior «Atrás» utiliza el mismo retorno y enfoca el encabezado de la tabla.
- Pulsar «Evaluación», tanto en la propia pestaña como desde otra, restablece la tabla general conservando filtros y datos guardados.
- El retorno limpia los parámetros de rúbrica y estudiante de los enlaces directos para que la recarga mantenga la tabla general.
- Compilación TypeScript/Vue y Vite correcta; capturas de ordenador y móvil revisadas.
- Cuatro pruebas Playwright correctas: regreso desde ambas rúbricas en ordenador/móvil, teclado, filtros, enlaces directos y recarga; pestañas con borradores; creación de equipos con rúbricas nuevas.

## Pendiente
- Construir imágenes, aplicar la versión y comprobar HTTPS.

## Archivos relevantes
- `resources/js/pages/Challenge.vue`.
- `tests/Browser/evidence.spec.ts`, `tests/Browser/empty-rubrics.spec.ts`.
- `ops/Dockerfile.production`, `ops/deploy`, `compose.production.yml`.

## Decisiones
- Las pestañas siguen conservando los borradores de equipos, configuración y evidencias; entrar en Evaluación muestra su vista general.
- Conservar cambios locales previos en `CLAUDE.md`, `docs/PROGRESS.md`, `LARAVEL_BOOST_GUIDELINES.md`, `RubricAssessment.vue` y `tests/Browser/rubrics.spec.ts` fuera de los commits de esta tarea.
- Mantener en las imágenes el ajuste previo de anchura uniforme de `RubricAssessment.vue`, ya presente en producción.

## Último error
- Ninguno pendiente.
