# Estado de la tarea

## Objetivo
Eliminar el eyebrow «ORGANIZACIÓN DEL CENTRO» de las páginas de Organización.

## Completado
- Quitada la única aparición de la plantilla compartida por las secciones de Organización.
- Añadida comprobación a la prueba de navegador; compilación y prueba enfocada correctas.
- La mejora anterior del editor de rúbricas está desplegada en producción (`47c80d6`).

## Pendiente
- Ninguno.

## Archivos relevantes
- `resources/js/pages/Setup.vue`, `tests/Browser/rubrics.spec.ts`.

## Decisiones
- La página `Setup.vue` comparte encabezado entre secciones; una eliminación cubre todas.

## Último error
- La prueba amplia de navegación no encontró el enlace «Profesor» antes de llegar a la página; la prueba enfocada con acceso directo pasó.
