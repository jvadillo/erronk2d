# Estado de la tarea

## Objetivo
Limitar a dos decimales todas las notas, equilibrar los repartos iniciales al céntimo y mostrar errores de reparto en español.

## Completado
- Validación a dos decimales para rúbricas, exámenes, defensas y reparto; pesos y porcentajes conservan su precisión configurada.
- Las notas visibles de evaluación se muestran con un máximo de dos decimales.
- El error del reparto indica los puntos totales y la diferencia, con singular/plural.
- PHPUnit: 42 pruebas / 434 aserciones correctas. Pint y `npm run build` correctos.
- Producción desplegada en `2aea63a`; migraciones sin cambios. HTTPS de `/up` y `/login` responde 200.
- Copia previa verificada en `backups/production-20260923-2aea63a`.
- Campos del reparto limitados a dos decimales; reparto inicial equitativo con céntimos sobrantes asignados desde el final.
- Mensaje localizado para rechazar entradas antiguas con más de dos decimales; presupuesto total del reparto redondeado a céntimos.
- PHPUnit afectado: 27 pruebas / 313 aserciones correctas. Pint y `npm run build` correctos.
- Corrección desplegada en `51bb0ec`; HTTPS `/up` y `/login` responde 200. Copia previa verificada en `backups/production-20260923-51bb0ec`.

## Pendiente
- Ninguno.

## Archivos relevantes
- app/Domain/Grades/ChallengeWriter.php, ChallengeRubricEditor.php, app/Http/Controllers/SetupController.php.
- resources/js/pages/Challenge.vue, tests/Feature/{GradebookTest,ChallengeRubricEditingTest}.php.

## Decisiones
- Las operaciones mantienen precisión interna; la restricción de dos decimales se aplica a la entrada y presentación de notas, sin alterar pesos.
- Despliegue sin reinicio académico; se usó el procedimiento `ops/deploy` con copia previa.
- El presupuesto distribuible se redondea a dos decimales y los céntimos restantes se asignan de uno en uno desde el último integrante.
- Se conservan cambios ajenos del espacio de trabajo: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento.

## Último error
- Ninguno pendiente.
