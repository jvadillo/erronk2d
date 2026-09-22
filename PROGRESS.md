# Estado de la tarea

## Objetivo
Analizar y definir la edición de la rúbrica propia de un reto por su profesorado, con acceso desde Evaluación y aviso previo ante pérdida de valoraciones.

## Completado
- Revisados editor, copias de rúbricas de retos, permisos, guardado, cálculo de notas, reparto individual, publicación e historial.
- Cada reto conserva team_rubric y transversal_rubric independientes de la biblioteca. El editor actual solo edita plantillas; no basta enlazarlo.
- Las valoraciones identifican criterios por key y niveles por índice: borrar columnas exige remapear las valoraciones conservadas para evitar cambios silenciosos de nota.
- Cambiar pesos/notas recalcula resultados; añadir criterios deja evaluaciones pendientes; el reparto puede quedar inválido. Transversales afecta teacher/self/peer.
- ChallengeWriter ya usa transacción, bloqueo y revision, protege cursos cerrados y retos publicados/finalizados y registra antes/después. Publicaciones guardan snapshots.
- Preguntas enviadas: equipo o ambas rúbricas; profesores autorizados; borrar solo valoraciones afectadas o reiniciar esa rúbrica.
- Producción sigue en 6371599 (menú Organización). Copia validada backups/production-20260922-6371599; entrega anterior verificada con 20 pruebas / 305 aserciones, Pint, TypeScript/Vite y HTTPS.

## Pendiente
- Recibir las decisiones funcionales antes de implementar las operaciones que invalidan evaluaciones.
- Propuesta: editar copia del reto, conservar valoraciones válidas mediante identidad estable de niveles, previsualizar impacto en servidor y confirmar al guardar; conservar evidencia histórica y proteger concurrencia.
- Precisar cambios de significado de criterios/descripciones, puntuaciones/pesos, módulos y reparto; tratar rúbricas antiguas sin unificación silenciosa.
- Botón Editar rúbrica a la derecha de Anterior/Siguiente en Evaluación; volver al mismo reto/equipo tras guardar.
- No se ha cambiado código ni desplegado nada de esta petición.

## Archivos relevantes
- resources/js/pages/{Challenge,RubricEditor}.vue, resources/js/rubrics.ts, resources/js/components/RubricAssessment.vue.
- app/Http/Controllers/{ChallengeController,SetupController}.php, app/Domain/Grades/{ChallengeWriter,Gradebook,Calculator}.php.
- app/Models/{User,Classroom,Challenge,Assessment,Publication,AuditEvent}.php, tests/Feature/{RubricEditorTest,GradebookTest}.php.
- prompt.md: preservación de rúbricas históricas (378–380, 978). routes/web.php.

## Decisiones
- Pendientes de respuesta; las propuestas anteriores aún no son decisiones del usuario.
- .ai/rules no existe. Preservar cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento.
- Privados: .env.production, ops/*credentials, backups/ y test-results/. No mostrar ni versionar.
- VPS compartido: solo recursos propios y despliegues con copia previa; no repetir reinicio académico ni carga del catálogo.
- Google aplazado; copias externas/carga/supervisión fuera de alcance.

## Último error
- Ninguno en el análisis. PHP solo disponible en contenedor; Pint --dirty requiere Git ausente en él (usar archivos explícitos).
