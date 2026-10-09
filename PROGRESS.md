# Estado de la tarea

## Objetivo
Importar estudiantes con las cabeceras `nombre` y `email` y guardar en la biblioteca las rúbricas creadas desde un reto.

## Completado
- Importación de estudiantes: cabeceras `nombre, email` sin distinguir mayúsculas; `name` ya no se acepta para estudiantes. Profesorado (`name, email`) y módulos (`name, code`) sin cambios. Mensaje de error: «La primera fila debe contener las cabeceras: …».
- Ventana de importación (`Setup.vue`): bloque «Estructura del archivo» con las cabeceras y una fila de ejemplo según el tipo.
- Rúbricas: migración `2026_10_09_120000_link_challenge_rubrics_to_library` (`team_rubric_id`, `transversal_rubric_id` en `challenges`, `nullOnDelete`). Al guardar una rúbrica que nació vacía en el reto se crea una rúbrica en la biblioteca de quien la edita (ciclo/nivel del grupo); los guardados siguientes la actualizan si quien edita es su propietario o admin. Las rúbricas copiadas de la biblioteca siguen independientes.
- Nombre por defecto («Rúbrica técnica»/«Rúbrica transversal») se guarda en la biblioteca como «… · nombre del reto». El editor avisa en el pie cuando el guardado también actualiza la biblioteca (`savesToLibrary`).
- Pruebas: `ImportTest` (cabeceras nuevas y rechazo de `name`), `AcademicWorkflowTest`, `ChallengeRubricEditingTest` (creación, actualización, otra persona no altera la biblioteca, plantilla no se duplica).
- Validado en local: `vue-tsc`, `vite build` y `php -l` correctos.

## Pendiente
- Ejecutar PHPUnit, Pint y Playwright en el VPS (en local no hay `vendor`; PHP local 8.1). Incluye lo pendiente del bloque anterior (estados del reto).
- Desplegar con copia previa; hay dos migraciones nuevas (estados del reto y enlace de rúbricas).
- Las rúbricas creadas desde retos antes de este cambio no tienen enlace y no aparecen en la biblioteca.

## Archivos relevantes
- `app/Http/Controllers/ImportController.php`, `resources/js/pages/Setup.vue`, `resources/css/app.css`.
- `app/Domain/Grades/ChallengeRubricEditor.php`, `app/Http/Controllers/ChallengeRubricController.php`, `app/Http/Controllers/ChallengeController.php`, `resources/js/pages/RubricEditor.vue`.
- `tests/Feature/ImportTest.php`, `tests/Feature/AcademicWorkflowTest.php`, `tests/Feature/ChallengeRubricEditingTest.php`.

## Decisiones
- Solo las rúbricas nacidas vacías en el reto se enlazan a la biblioteca, para no modificar plantillas compartidas.
- Si se borra la rúbrica de la biblioteca, el reto conserva su copia y no se vuelve a crear.

## Último error
- Ninguno.
