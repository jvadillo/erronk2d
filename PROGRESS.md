# Estado de la tarea

## Objetivo
Ajustes de la página del reto: estado con desplegable, «Publicar notas» y tarjetas de equipo.

## Completado
- Cabecera del reto: desplegable «Estado del reto» (En curso, En evaluación, Finalizado; solo con `manage_challenges` y reto abierto) en lugar de «Pasar a evaluación»/«Volver a En curso», y «Publicar notas». Retirado el desplegable de estado de Evaluación; «Introducción masiva» con icono. Tarjeta de equipo a media anchura aunque sea la única; `team-roster-heading` sustituido por `.team-card-count` («Sin integrantes», «1 persona», «N personas»). 16 Playwright correctas en el VPS y capturas revisadas; desplegado `5cd379a` el 24 de octubre de 2026 con copia `backups/production-20261024-5cd379a` de `39161ad`.
- Tabla de evaluación a pantalla completa sin el `header.fullscreen-bar`: el botón de salir (icono, Esc sigue funcionando) ocupa el sitio del de entrar en `.matrix-toolbar`, y el estado de guardado se muestra en esa barra. Prueba nueva en `evidence.spec.ts`; 16 Playwright correctas en el VPS; desplegado `39161ad` el 24 de octubre de 2026 con copia `backups/production-20261024-39161ad` de `4cf919a`.
- Tarjeta «Evaluación» de `ChallengeHub.vue` igual que las demás: sin `hub-card-primary` ni `hub-progress` (progreso y avisos). 16 pruebas Playwright correctas en el VPS y capturas de escritorio/móvil revisadas; desplegado `4cf919a` el 23 de octubre de 2026 con copia `backups/production-20261023-4cf919a` de `b98e9b8` (PHP 8.4.27).
- Buscador para matricular (`StudentSearch.vue`, `GET /students/search`, throttle 60/min): desde 3 caracteres sugiere hasta 10 estudiantes activos no matriculados en el grupo por nombre o correo; combobox accesible con teclado. Sustituye la regla de correo exacto de la especificación por indicación del usuario. Alineados los botones «Añadir» y «Matricular» (`label` sin margen en `.group-inline-form`). 227 pruebas PHP y 16 Playwright correctas en el VPS; desplegado `1773e50` el 14 de octubre de 2026 con copia `backups/production-20261014-1773e50`.
- Ruta `groups.show` (`/setup/classrooms/{classroom}`) → `SetupController::group`, página `Group.vue` con pestañas (`?tab=`): Configuración (datos del grupo y evaluaciones), Módulos (añadir/quitar, responsables), Profesores (solo propietario o admin pueden cambiarlos) y Estudiantes (matricular/desmatricular). Reutiliza los endpoints existentes de `/setup/*`; tras guardar se vuelve a la misma pestaña.
- Lista de Grupos: `GroupCard.vue` muestra nombre, ciclo, curso y recuentos de estudiantes, profesores y evaluaciones con enlace a la página. El listado ya no carga módulos ni estudiantes. Crear un grupo abre su página.
- `Setup.vue`: retirados los modales de editar grupo, evaluaciones y responsables. `Layout.vue`: «Grupos» queda activo en subpáginas.
- Pruebas: `GroupManagementTest` (resumen de la lista, página del grupo, acceso), `AcademicWorkflowTest` (redirección al crear), `workflows.spec.ts` (página del grupo, creación y curso cerrado).
- Validado en el VPS: Pint, 224 pruebas PHP en PostgreSQL y 16 pruebas Playwright correctas; capturas de escritorio y móvil revisadas.
- Página del grupo desplegada en producción (`3c19863`) el 14 de octubre de 2026 con copia `backups/production-20261014-3c19863`.
- Visibilidad de retos por módulos responsables (`02c1505`, prueba corregida en `1e1e3ba`): 226 pruebas PHP y 16 Playwright correctas en el VPS; desplegado `1e1e3ba` el 14 de octubre de 2026 con copia `backups/production-20261014-1e1e3ba`. Solo dos cuentas ficticias dejan de ver retos.
- Bloques anteriores ya en producción: importación con `nombre, email`, rúbricas enlazadas a la biblioteca (464ecf2) y menú lateral de 216px (fe0fbab).
- Visibilidad de retos: el profesorado solo ve y opera los retos de sus grupos con algún módulo del que es responsable (`Challenge::visibleTo`, `User::canAccessChallenge`); crear un reto exige serlo de algún módulo elegido. Admin sin cambios. Pruebas adaptadas, sin ejecutar (sin `vendor/` local).

## Pendiente
- Revisar si `/reports` debe filtrar por módulos del profesor (pendiente de decidir; no cambiado).
- Las rúbricas creadas desde retos antes del enlace con la biblioteca no aparecen en ella (recuperación puntual solo si se pide).

## Archivos relevantes
- `resources/js/pages/Challenge.vue`, `resources/js/components/ChallengeHub.vue`, `resources/css/app.css`, `tests/Browser/evidence.spec.ts`, `tests/Browser/workflows.spec.ts`.
- `app/Http/Controllers/SetupController.php`, `routes/web.php`.
- `resources/js/pages/Group.vue`, `resources/js/components/GroupCard.vue`, `resources/js/pages/Setup.vue`, `resources/js/components/Layout.vue`.
- `tests/Feature/GroupManagementTest.php`, `tests/Feature/AcademicWorkflowTest.php`, `tests/Browser/workflows.spec.ts`.

## Decisiones
- Pasar a «Finalizado» desde el desplegable cierra el reto (el servidor exige evaluaciones completas); después solo se modifica con «Reabrir». Si el servidor rechaza el cambio, el desplegable vuelve al estado real.
- Cuatro pestañas (el usuario enumeró cuatro aunque escribió «tres»). Las evaluaciones están en Configuración.
- Al pasar de escritorio a móvil sin navegar, el submenú de Organización sigue abierto (comportamiento previo); la prueba lo cierra igual que antes.

## Último error
- Ninguno.
