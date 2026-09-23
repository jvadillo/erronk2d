# Estado de la tarea

## Objetivo
Implementar la edición de ambas rúbricas del reto por cualquier profesor del grupo y administración, con acceso desde Evaluación y aviso previo ante pérdida de valoraciones.

## Completado
- Backend: editor de copia del reto, previsualización sin escrituras, confirmación firmada ligada a usuario/contenido/revisión y guardado transaccional mediante ChallengeWriter.
- Borrado selectivo y remapeo de niveles por origen en la revisión; conservación del resto, reparto, biblioteca, otros retos y publicaciones. Transversales cubre teacher/self/peer.
- Permisos del grupo, curso cerrado, reapertura con motivo, concurrencia y auditoría antes/después. Sin migraciones ni dependencias nuevas.
- 49 pruebas / 479 aserciones correctas tanto en SQLite como PostgreSQL (edición nueva, editor de biblioteca y Gradebook). Pint correcto con archivos explícitos.
- Interfaz implementada: botón a la derecha de Anterior/Siguiente, editor contextual, revisión del impacto y vuelta al mismo sujeto. TypeScript/Vite correctos. Cuatro flujos de navegador correctos: dos de biblioteca y edición de ambas rúbricas (avisos, cancelación, conservación, borrado, recarga y móvil). Capturas revisadas.
- Corregida actualización de la copia local de evaluación al recargar tras un conflicto. Flujos de dos pestañas verificados para equipo y transversales.
- Backend registrado en 7a7c34e; interfaz y pruebas registradas en el siguiente commit.
- Producción sigue en 6371599; copia previa validada backups/production-20260922-6371599. Esta petición no se ha desplegado.

## Pendiente
- Implementación y comprobaciones completadas. Despliegue no solicitado en esta petición; producción conserva la versión anterior.

## Archivos relevantes
- resources/js/pages/{Challenge,RubricEditor}.vue, resources/js/rubrics.ts, resources/js/components/RubricAssessment.vue.
- app/Http/Controllers/{ChallengeController,ChallengeRubricController}.php, app/Domain/Grades/{ChallengeRubricEditor,ChallengeWriter,Gradebook}.php.
- app/Models/{User,Classroom,Challenge,Assessment,Publication,AuditEvent}.php, tests/Feature/{ChallengeRubricEditingTest,RubricEditorTest,GradebookTest}.php, tests/Browser/rubrics.spec.ts.
- prompt.md: preservación de rúbricas históricas (378–380, 978). routes/web.php.

## Decisiones
- Usuario confirmó ambas rúbricas, cualquier profesor del grupo y administración; borrar solo valoraciones afectadas y conservar evidencia histórica.
- .ai/rules no existe. Preservar cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento.
- Privados: .env.production, ops/*credentials, backups/ y test-results/. No mostrar ni versionar.
- VPS compartido: solo recursos propios y despliegues con copia previa; no repetir reinicio académico ni carga del catálogo.
- Google aplazado; copias externas/carga/supervisión fuera de alcance.

## Último error
- Sin errores pendientes. Corregida espera de navegación de Playwright. PHP solo disponible en contenedor; Pint --dirty requiere Git ausente en él (usar archivos explícitos).
