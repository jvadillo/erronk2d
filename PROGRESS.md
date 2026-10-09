# Estado de la tarea

## Objetivo
Simplificar los estados del reto, volver al inicio del reto tras guardar equipos con un aviso y renombrar «Revisar cambios» a «Guardar cambios» en el editor de rúbricas del reto.

## Completado
- Estados: se elimina `draft`. Un reto nuevo nace `active` («En curso»): el profesorado edita y evalúa, el alumnado no se evalúa. `evaluating` («En evaluación») abre la autoevaluación y la coevaluación. Finalizado y Publicado sin cambios.
- Migración `2026_10_09_100000_simplify_challenge_statuses`: `active`→`evaluating` (conserva el acceso del alumnado), `draft`→`active`, valor por defecto `active`.
- `ChallengeWriter`: estados permitidos `active,evaluating,finished`; el alumnado solo evalúa en `evaluating`. Seeder adaptado.
- `Challenge.vue`: botones «Pasar a evaluación» / «Volver a En curso» en la cabecera (permiso `manage_challenges`) con aviso; «Guardar equipos» vuelve al inicio del reto y muestra un aviso de éxito.
- `RubricEditor.vue`: «Guardar cambios» en contexto de reto. `Student.vue` y `lib.ts` adaptados.
- Pruebas: `GradebookTest` (evaluación del alumnado según estado, `draft` rechazado), `ChallengeCreationTest` (nace `active`), navegador `empty-rubrics` y `workflows` actualizadas.
- Validado en local: `vue-tsc` y `vite build` correctos; `php -l` correcto.

## Pendiente
- Ejecutar PHPUnit, Pint y Playwright en el VPS (en local no hay `vendor` ni Docker activo; PHP local 8.1).
- Desplegar con copia previa; la migración cambia estados de retos existentes.

## Archivos relevantes
- `database/migrations/2026_10_09_100000_simplify_challenge_statuses.php`, `app/Domain/Grades/ChallengeWriter.php`, `database/seeders/DatabaseSeeder.php`.
- `resources/js/pages/Challenge.vue`, `resources/js/pages/RubricEditor.vue`, `resources/js/pages/Student.vue`, `resources/js/lib.ts`.
- `tests/Feature/GradebookTest.php`, `tests/Feature/ChallengeCreationTest.php`, `tests/Browser/empty-rubrics.spec.ts`, `tests/Browser/workflows.spec.ts`.

## Decisiones
- Los retos `active` existentes pasan a `evaluating` para no cerrar la evaluación del alumnado ya abierta.
- Reabrir un reto lo deja en `evaluating` (sin cambios).
- Pruebas de navegador: `migrate:fresh --seed` en `erronk2d-test` antes de cada ejecución.

## Último error
- Ninguno.
