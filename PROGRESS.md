# Estado de la tarea

## Objetivo
Reorganizar la página del reto en cuatro pestañas y desplegarla en producción.

## Completado
- Pestañas Estudiantes y Equipos, Evaluación, Evidencias y Configuración con URL propia y navegación por teclado.
- Equipos y configuración integrados en la página; evidencias extraídas a un componente reutilizable; enlace antiguo redirigido a su pestaña.
- Borradores conservados entre pestañas; formularios deshabilitados según permisos y cierre del reto/curso.
- Compilación frontend correcta y cuatro pruebas PHP (49 aserciones) sobre evidencias, redirecciones y privacidad.
- Seis pruebas Playwright afectadas pasan: evidencias, pestañas/borradores/configuración, matriz y equipos. Revisión visual de escritorio/tablet y ausencia de desbordamiento en móvil/tablet.
- Imágenes de aplicación y web construidas desde el commit `219d3b8`.
- Pint aplicado a los archivos PHP cambiados; el contenedor no permite `--dirty` por no disponer de Git.

## Pendiente
- Copia previa, despliegue de `219d3b8` y comprobación HTTPS.

## Archivos relevantes
- `resources/js/pages/Challenge.vue`, `resources/js/components/EvidenceWorkspace.vue`.
- `app/Http/Controllers/ChallengeController.php`, `app/Http/Controllers/ChallengeEvidenceController.php`.
- `tests/Feature/ChallengeTabsTest.php`, `tests/Browser/evidence.spec.ts`, `tests/Browser/workflows.spec.ts`.
- `ops/deploy`, `ops/Dockerfile.production`, `compose.production.yml`.

## Decisiones
- Evaluación sigue siendo la pestaña inicial; las cuatro pestañas permanecen visibles al evaluar rúbricas.
- Despliegue autorizado por el usuario; producción actual `10c99a6`.
- Excluir cambios locales previos en RubricAssessment, su prueba y documentación.
- No están instaladas las skills adicionales de Vue/pruebas mencionadas por AGENTS; se usan las convenciones existentes y documentación oficial de Inertia 3.

## Último error
La prueba antigua de matriz tenía datos de cuatro decimales y un enlace con nombre obsoleto. Adaptada al límite actual de dos decimales y al enlace «Rúbricas»; pasa. La prueba de equipos se repitió con la base ficticia reinicializada y pasa.
