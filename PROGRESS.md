# Estado de la tarea

## Objetivo
Dar a cada grupo su propia página de configuración y simplificar las tarjetas de la lista de Grupos.

## Completado
- Ruta `groups.show` (`/setup/classrooms/{classroom}`) → `SetupController::group`, página `Group.vue` con pestañas (`?tab=`): Configuración (datos del grupo y evaluaciones), Módulos (añadir/quitar, responsables), Profesores (solo propietario o admin pueden cambiarlos) y Estudiantes (matricular/desmatricular). Reutiliza los endpoints existentes de `/setup/*`; tras guardar se vuelve a la misma pestaña.
- Lista de Grupos: `GroupCard.vue` muestra nombre, ciclo, curso y recuentos de estudiantes, profesores y evaluaciones con enlace a la página. El listado ya no carga módulos ni estudiantes. Crear un grupo abre su página.
- `Setup.vue`: retirados los modales de editar grupo, evaluaciones y responsables. `Layout.vue`: «Grupos» queda activo en subpáginas.
- Pruebas: `GroupManagementTest` (resumen de la lista, página del grupo, acceso), `AcademicWorkflowTest` (redirección al crear), `workflows.spec.ts` (página del grupo, creación y curso cerrado).
- Validado en el VPS: Pint, 224 pruebas PHP en PostgreSQL y 16 pruebas Playwright correctas; capturas de escritorio y móvil revisadas.
- Bloques anteriores ya en producción: importación con `nombre, email`, rúbricas enlazadas a la biblioteca (464ecf2) y menú lateral de 216px (fe0fbab).

## Pendiente
- Desplegar la página del grupo (`9f4941e` y siguientes) cuando el usuario lo pida.
- Las rúbricas creadas desde retos antes del enlace con la biblioteca no aparecen en ella (recuperación puntual solo si se pide).

## Archivos relevantes
- `app/Http/Controllers/SetupController.php`, `routes/web.php`.
- `resources/js/pages/Group.vue`, `resources/js/components/GroupCard.vue`, `resources/js/pages/Setup.vue`, `resources/js/components/Layout.vue`.
- `tests/Feature/GroupManagementTest.php`, `tests/Feature/AcademicWorkflowTest.php`, `tests/Browser/workflows.spec.ts`.

## Decisiones
- Cuatro pestañas (el usuario enumeró cuatro aunque escribió «tres»). Las evaluaciones están en Configuración.
- Al pasar de escritorio a móvil sin navegar, el submenú de Organización sigue abierto (comportamiento previo); la prueba lo cierra igual que antes.

## Último error
- Ninguno.
