# Estado de la tarea

## Objetivo actual
Ordenar el menú Organización como Cursos académicos, Ciclos, Módulos, Grupos, Profesores, Estudiantes, Rúbricas y Solicitudes; publicar el cambio en producción.

## Completado en esta petición
- Reordenada la navegación y actualizadas sus etiquetas plurales. Los títulos propios de las páginas siguen iguales.
- Prueba de navegación: 20 pruebas / 305 aserciones correctas. TypeScript/Vite y Pint correctos.
- Commit `6371599`; imágenes de producción construidas desde ese commit. App/web saludables y release activo `6371599`; HTTPS `/up` y `/login` correctos.
- Despliegue con copia previa `backups/production-20260922-6371599`, permisos 700; índice PostgreSQL y gzip de almacenamiento correctos. Sin migraciones pendientes ni reinicio académico.

## Pendiente
- No quedan tareas de esta petición.

## Archivos relevantes
- `app/Http/Controllers/SetupController.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `tests/Feature/OrganizationNavigationTest.php`, `ops/deploy`.

## Decisiones
- Mantener los títulos de página actuales y usar etiquetas de menú específicas.
- Desplegar sin reinicio académico ni cambios de datos.

## Último error
- PHP no está instalado en el host y Pint `--dirty` requiere Git dentro del contenedor. Ejecutados Pint sobre archivos explícitos y PHPUnit en el contenedor aislado; ambos correctos.

## Objetivo
Simplificar evaluación y retos; mostrar módulos y ciclos en tablas, filtrar módulos y gestionar su asociación desde ciclos. Desplegar a producción.

## Completado
- Reto: título «Evaluación». Rúbrica: eliminado `rubric-choice-state`, conservando selección accesible y resaltado verde.
- Retos: título «Tus retos.» y retirado `overview-grid`.
- Módulos: tabla ordenable por nombre A–Z/Z–A y filtros por ciclo (incluido sin asignar) y curso/nivel.
- Ciclos: tabla ordenable y popup Ver módulos para quitar, buscar por nombre sin distinguir tildes y añadir módulos sin ciclo.
- Alta/edición de módulos sin ciclo disponible. Validación de permisos, pertenencia, duplicados y curso; bloqueo transaccional de asociación.
- 28 pruebas PHP correctas (185 aserciones): CycleModulesTest, SetupTest y GroupManagementTest. TypeScript/Vite y Pint correctos.
- Producción actualizada a `cb7206a`; copia previa `backups/production-20260922-cb7206a` validada. Sin migraciones pendientes. Servicios iniciados y HTTPS `/up`, `/login` y nuevo recurso Setup JS responden 200.
- Dos flujos Playwright correctos: evaluación docente y catálogo (orden, filtros, altas/bajas, recarga y móvil). Capturas revisadas.

## Pendiente
- Entrega completada; no quedan tareas de esta petición.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- `resources/js/pages/{Setup,Dashboard,Challenge}.vue`, `resources/js/components/RubricAssessment.vue`, `resources/css/rubrics.css`.
- `app/Http/Controllers/SetupController.php`, `tests/Feature/CycleModulesTest.php`, `tests/Browser/{rubrics,workflows}.spec.ts`.
- `ops/deploy`, `ops/backup`, `ops/Dockerfile.production`, `compose.test.yml`, `docs/despliegue.md`.
- Producción previa: 8df6f23, copia `backups/production-20260922-8df6f23`.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No incluirlos.
- Privados: .env.production, ops/demo-credentials, ops/production-credentials, backups/ y test-results/. No mostrar ni versionar.

## Decisiones
- Aclaración del usuario: cada módulo pertenece como máximo a un ciclo y curso. Solo se añaden módulos sin ciclo; no se copian ni comparten. Quitar deja cycle_id nulo y conserva asociaciones de grupos y notas.
- Curso del filtro significa nivel (1.º–4.º). Sin cambios de esquema ni dependencias.
- VPS compartido: solo recursos propios, copia previa, pruebas de escritura aisladas y construcción desde git archive. No repetir carga de catálogo ni reinicio académico.
- .ai/rules no existe. Guías locales Inertia/Vue y testing leídas; documentación Inertia v3 consultada con Boost.

## Último error
- Sin errores pendientes. Ajustados selectores Playwright y expectativa de Leer más para descripciones cortas. Pint --dirty no funciona por ausencia de Git en el contenedor; aplicado Pint a los dos PHP modificados.
