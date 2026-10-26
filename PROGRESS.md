# Estado de la tarea

## Objetivo
Preparación docente con menos dependencia de administración (ciclos, módulos, grupos, duplicación), profesores participantes por reto y vaciado del VPS conservando cuentas, ciclos, módulos y cursos académicos.

## Completado
- Pendiente de desplegar y de validar en el VPS (ver «Último error»). Sin commit de despliegue.
- 26-10-2026, por indicación del usuario: solo administración crea cursos académicos (sección `courses` vuelve a ser administrativa; `GroupForm` ya no los crea).
- Profesores participantes por reto (`challenge_teacher`, migración `2026_10_26_100000` con los docentes activos del grupo para retos existentes): se eligen al crear el reto (`teacher_ids`, el creador entra si es del grupo); solo participantes y admin ven y operan el reto (tampoco el propietario si no participa); acciones `teachers` (añadir; retirar solo admin) y `modules` (añadir módulos activos del grupo; quitar solo sin notas ni criterios de rúbrica) en `ChallengeWriter`; modales en `Challenge.vue`; `/reports` solo suma los retos del docente y avisa (`report.partial`).
- `erronk2d:reset-activity` y `ops/deploy VERSION DIR --reset-activity`: borran grupos, matrículas, retos, equipos, notas, evidencias, publicaciones, rúbricas, solicitudes y auditoría; conservan cuentas, ciclos, módulos y cursos académicos (huella comparada antes/después). Pruebas `ChallengeParticipantsTest` y `ResetActivityDataTest`.
- Catálogo común (`app/Domain/Catalog.php`): claves normalizadas `name_key`/`code_key` (mayúsculas y espacios irrelevantes) con índices únicos; duplicados rechazados con mensaje y altas simultáneas convertidas en error de validación (savepoint). Módulos únicos por nombre y código dentro del ciclo, en cualquier curso / nivel.
- Profesorado: crea ciclos y módulos (`/setup/cycle|module`, JSON si `wantsJson`). Editar y reorganizar sigue siendo de administración. Organización muestra a docentes Ciclos y Módulos.
- Creación de grupo (`GroupForm.vue` + `CatalogQuickAdd.vue`): elige curso académico abierto y elige o crea ciclo y módulos sin salir del formulario; nivel 1.º–4.º; evaluaciones (3 por defecto); profesores; `module_ids` opcional en `/setup/classroom` y `academic_year_id` explícito (curso abierto). Tras crear, el contexto pasa al curso del grupo. `?crear=grupo` abre el formulario.
- Permisos por grupo: propietario/admin cambian datos, evaluaciones, módulos y profesores (`group-module` y `group-periods` ahora exigen `canManageClassroom`); miembros gestionan matrículas, retos, equipos, rúbricas, notas y publicación.
- Responsables de módulo eliminados: `User::teaches`, `Module::teachersFor`, `/setup/responsibility`, filtro de `Challenge::visibleTo`, bloqueos de `ChallengeWriter`, requisito al crear retos, incidencia «sin profesor responsable», controles de `Group.vue`/`Challenge.vue`/`RubricAssessment.vue`. El libro expone `teachers` (profesorado activo del grupo). Migración `2026_10_25_100000_open_catalog_and_retire_module_responsibilities` archiva las filas en `audit_events` (`system.module_responsibilities_retired`) y elimina la tabla; `down()` la restaura.
- Duplicar grupo (`POST /setup/classrooms/{id}/duplicate`, `groups.duplicate`): curso destino abierto, nombre, profesorado activo revisable, alumnado activo opcional; copia ciclo, nivel, módulos activos y evaluaciones; nunca actividad; transaccional; quien duplica es propietario.
- Textos: «Curso académico» frente a «Curso / nivel», estados vacíos con acciones disponibles, sin mensajes de espera a administración. Importaciones de módulos y `erronk2d:catalog` usan la regla de duplicados por ciclo.
- Pruebas nuevas: `GroupPreparationTest` (recorrido sin estructura previa, duplicados, concurrencia, permisos, ajenos, duplicación con/sin matrículas, curso cerrado) y `CatalogMigrationTest`; adaptadas las de responsables, navegación, catálogo oficial y `workflows.spec.ts`.
- Validado localmente: `npm run build` (vue-tsc) y `php -l` de los archivos PHP modificados.
- Anterior, ya en producción: tarjetas de retos con color y forma propios (`52b626f`, copia `backups/production-20261024-52b626f`), rúbricas sin compartir, cabecera de equipos, confirmaciones de estado, página de grupo con pestañas y buscador de matrícula (detalle en el historial de Git).

## Pendiente
- Vaciar el VPS cuando el usuario facilite el acceso SSH: copia y despliegue con `bash ops/deploy VERSION backups/DIRECTORIO-NUEVO --reset-activity` tras validar las pruebas.
- Ejecutar en el VPS (o con Docker operativo): `vendor/bin/pint --dirty --format agent`, la suite PHP en PostgreSQL y Playwright (`workflows.spec.ts` cambia: grupo como miembro sin cambios estructurales, alta de curso académico con duplicado, creación de grupo con alta de módulo, duplicación desde curso cerrado, selectores «Curso / nivel»).
- Desplegar tras la validación (la migración retira `classroom_module_user`; las filas quedan archivadas en `audit_events`).
- Las rúbricas creadas desde retos antes del enlace con la biblioteca no aparecen en ella (recuperación puntual solo si se pide).

## Archivos relevantes
- `app/Domain/Catalog.php`, `database/migrations/2026_10_25_100000_open_catalog_and_retire_module_responsibilities.php`, `app/Models/{AcademicYear,Cycle,Module,User,Challenge}.php`.
- `app/Http/Controllers/SetupController.php` (`store`, `classroom`, `duplicate`, `targetYear`), `routes/web.php`, `app/Domain/Grades/{Gradebook,ChallengeWriter}.php`, `app/Http/Controllers/{ChallengeController,ImportController}.php`, `app/Console/Commands/ImportOfficialCatalog.php`.
- `resources/js/components/{GroupForm,CatalogQuickAdd,Layout,GroupCard,RubricAssessment}.vue`, `resources/js/pages/{Setup,Group,Dashboard,Challenge,Reports}.vue`.
- `tests/Feature/{GroupPreparationTest,CatalogMigrationTest,GroupManagementTest,GradebookTest,AcademicWorkflowTest,OrganizationNavigationTest}.php`, `tests/Browser/workflows.spec.ts`.
- Especificación: `prompt.md` (secciones 2, 3 y 29), `docs/decisiones.md`, `README.md`.

## Decisiones
- Duplicados de módulo por ciclo (cualquier nivel), no por ciclo y nivel: el catálogo oficial no tiene repetidos dentro de un ciclo. Las colisiones heredadas reciben una clave con sufijo `#id` en la migración, sin fusionar ni borrar datos.
- Si `/setup/classroom` (alta) no recibe `academic_year_id`, usa el curso activo con la comprobación de pestaña caducada (409); si lo recibe, exige un curso abierto disponible.
- La duplicación solo reutiliza módulos activos que siguen en el mismo ciclo y nivel del catálogo; el nombre del ciclo se toma del catálogo actual.
- Sin uso compartido de rúbricas: cada docente reutiliza las suyas; la pertenencia al grupo no comparte bibliotecas.
- `/reports` no filtra por módulos: la pertenencia al grupo da acceso a todo su seguimiento.

## Último error
- Docker Desktop no arranca en esta máquina (la distribución WSL `docker-desktop` aparece «Uninstalling») y el PHP local es 8.1 (el proyecto exige 8.4): no se han podido ejecutar Pint, PHPUnit ni Playwright en esta sesión.
