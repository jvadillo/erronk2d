# Estado de la tarea

## Objetivo
Permitir crear retos con rúbricas nuevas vacías, orientar su edición y mantener coherentes evaluaciones y resultados.

## Completado
- Opción «Crear nueva rúbrica» independiente para técnica y transversal; crea copias propias del reto sin añadir plantillas a la biblioteca.
- Aviso de rúbrica vacía y botón de edición para docentes; aviso sin edición para alumnado. API rechaza valoraciones sin criterios.
- Editor muestra tipo, ciclo y curso/nivel bloqueados, usando el contexto histórico del reto. Rúbricas vacías ya no se interpretan como pesos antiguos.
- Aviso cerrable de módulos sin criterios específicos en edición y evaluación técnica; se actualiza al cambiar la cobertura.
- Selecciones incompatibles de ambas rúbricas se limpian al cambiar grupo/módulos; error de compatibilidad asociado al campo correcto.
- 75 pruebas PHP / 732 aserciones correctas: creación, edición, pestañas, cálculos, reparto, publicación e historial. TypeScript/Vite correctos. Pint correcto con rutas explícitas.

## Pendiente
- Terminar prueba Playwright del flujo nuevo y regresión de creación; revisar y hacer commit.
- Cambios aún sin desplegar.

## Archivos relevantes
- `app/Http/Controllers/ChallengeController.php`, `ChallengeRubricController.php`; `app/Domain/Grades/ChallengeWriter.php`.
- `resources/js/pages/Dashboard.vue`, `Challenge.vue`, `RubricEditor.vue`, `Student.vue`; `resources/js/components/ModuleCriteriaNotice.vue`.
- `tests/Feature/ChallengeCreationTest.php`, `ChallengeRubricEditingTest.php`; `tests/Browser/empty-rubrics.spec.ts`.

## Decisiones
- Una rúbrica vacía mantiene notas pendientes y bloquea finalizar/publicar; no permite reparto ni valoraciones ficticias. Edición conserva el mecanismo de revisión, concurrencia e historial existente.
- Falta de criterios específicos por módulo es informativa: los criterios GENERAL no cuentan como específicos y el aviso no bloquea evaluar.
- Conservar cambios locales previos en `CLAUDE.md`, `docs/PROGRESS.md`, `LARAVEL_BOOST_GUIDELINES.md`, `RubricAssessment.vue` y `tests/Browser/rubrics.spec.ts` fuera de los commits de esta tarea.
- Producción continúa en la versión anterior `495d95c-local-20260929`; no se han modificado datos de producción.

## Último error
- Pint no admite `--dirty` porque el contenedor PHP no dispone de Git; formato completado indicando únicamente los cinco archivos PHP modificados.
