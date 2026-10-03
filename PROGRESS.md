# Estado de la tarea

## Objetivo
Permitir crear retos con rúbricas nuevas vacías, orientar su edición y mantener coherentes evaluaciones y resultados.

## Completado
- Opción «Crear nueva rúbrica» independiente para técnica y transversal; crea copias propias del reto sin añadir plantillas a la biblioteca.
- Aviso de rúbrica vacía y botón de edición para docentes; aviso sin edición para alumnado. API rechaza valoraciones sin criterios.
- Editor muestra tipo, ciclo y curso/nivel bloqueados, usando el contexto histórico del reto. Rúbricas vacías ya no se interpretan como pesos antiguos.
- Aviso cerrable de módulos sin criterios específicos en edición y evaluación técnica; se actualiza al cambiar la cobertura.
- Selecciones incompatibles de ambas rúbricas se limpian al cambiar grupo/módulos; error de compatibilidad asociado al campo correcto.
- Selección de equipo reactiva: tras crear los primeros equipos, volver a Evaluación selecciona un equipo válido sin recargar.
- 75 pruebas PHP / 732 aserciones correctas: creación, edición, pestañas, cálculos, reparto, publicación e historial. TypeScript/Vite correctos. Pint correcto con rutas explícitas.
- 2 pruebas Playwright correctas: recorrido de rúbricas vacías, contexto bloqueado, aviso cerrable y actualización de módulos, primeras evaluaciones, persistencia y aviso del alumnado; regresión de creación con plantillas.
- Servidor y pruebas registrados en `184f8e9`; interfaz y prueba de navegador en `062956e`; despliegue y copia registrados en esta actualización.

## Pendiente
- Implementación, verificaciones y despliegue completados.

## Archivos relevantes
- `app/Http/Controllers/ChallengeController.php`, `ChallengeRubricController.php`; `app/Domain/Grades/ChallengeWriter.php`.
- `resources/js/pages/Dashboard.vue`, `Challenge.vue`, `RubricEditor.vue`, `Student.vue`; `resources/js/components/ModuleCriteriaNotice.vue`.
- `tests/Feature/ChallengeCreationTest.php`, `ChallengeRubricEditingTest.php`; `tests/Browser/empty-rubrics.spec.ts`.

## Decisiones
- Una rúbrica vacía mantiene notas pendientes y bloquea finalizar/publicar; no permite reparto ni valoraciones ficticias. Edición conserva el mecanismo de revisión, concurrencia e historial existente.
- Falta de criterios específicos por módulo es informativa: los criterios GENERAL no cuentan como específicos y el aviso no bloquea evaluar.
- Conservar cambios locales previos en `CLAUDE.md`, `docs/PROGRESS.md`, `LARAVEL_BOOST_GUIDELINES.md`, `RubricAssessment.vue` y `tests/Browser/rubrics.spec.ts` fuera de los commits de esta tarea.
- La imagen mantiene el ajuste previo de anchura uniforme en `RubricAssessment.vue`, que ya estaba incluido en la versión anterior de producción.

## Último error
- Ninguno pendiente. El healthcheck `/up` devuelve la página HTML normal de Laravel con HTTP 200; se comprobó el servicio público por HTTPS.
