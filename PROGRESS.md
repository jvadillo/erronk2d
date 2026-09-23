# Estado de la tarea

## Objetivo
Limitar a dos decimales todas las notas y aclarar cuánto sobra o falta al guardar un reparto inválido.

## Completado
- Validación a dos decimales para rúbricas, exámenes, defensas y reparto; pesos y porcentajes conservan su precisión configurada.
- Las notas visibles de evaluación se muestran con un máximo de dos decimales.
- El error del reparto indica los puntos totales y la diferencia, con singular/plural.
- PHPUnit: 42 pruebas / 434 aserciones correctas. Pint y `npm run build` correctos.

## Pendiente
- Ninguno. Despliegue en producción no solicitado.

## Archivos relevantes
- app/Domain/Grades/ChallengeWriter.php, ChallengeRubricEditor.php, app/Http/Controllers/SetupController.php.
- resources/js/pages/Challenge.vue, tests/Feature/{GradebookTest,ChallengeRubricEditingTest}.php.

## Decisiones
- Las operaciones mantienen precisión interna; la restricción de dos decimales se aplica a la entrada y presentación de notas, sin alterar pesos.
- Se conservan cambios ajenos del espacio de trabajo: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento.

## Último error
- Ninguno pendiente. Aserciones adaptadas a las claves de error que devuelve Laravel para validaciones anidadas.
