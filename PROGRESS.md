# Estado de la tarea

## Objetivo
Limitar a dos decimales todas las notas, aclarar los repartos inválidos y desplegar la versión aprobada en producción.

## Completado
- Validación a dos decimales para rúbricas, exámenes, defensas y reparto; pesos y porcentajes conservan su precisión configurada.
- Las notas visibles de evaluación se muestran con un máximo de dos decimales.
- El error del reparto indica los puntos totales y la diferencia, con singular/plural.
- PHPUnit: 42 pruebas / 434 aserciones correctas. Pint y `npm run build` correctos.
- Producción desplegada en `2aea63a`; migraciones sin cambios. HTTPS de `/up` y `/login` responde 200.
- Copia previa verificada en `backups/production-20260923-2aea63a`.

## Pendiente
- Ninguno.

## Archivos relevantes
- app/Domain/Grades/ChallengeWriter.php, ChallengeRubricEditor.php, app/Http/Controllers/SetupController.php.
- resources/js/pages/Challenge.vue, tests/Feature/{GradebookTest,ChallengeRubricEditingTest}.php.

## Decisiones
- Las operaciones mantienen precisión interna; la restricción de dos decimales se aplica a la entrada y presentación de notas, sin alterar pesos.
- Despliegue sin reinicio académico; se usó el procedimiento `ops/deploy` con copia previa.
- Se conservan cambios ajenos del espacio de trabajo: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento.

## Último error
- El sandbox no resolvía el dominio en HTTPS; comprobación externa repetida con éxito (200).
