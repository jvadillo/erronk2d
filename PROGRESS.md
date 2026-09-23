# Estado de la tarea

## Objetivo
Permitir al profesorado consultar y registrar evidencias individuales durante un reto.

## Completado
- Añadido el botón «Evidencias» junto a la rúbrica y una página con búsqueda y anotaciones por estudiante.
- Persistencia con estudiante, autor y fecha; solo profesorado puede consultar o guardar.
- La pantalla queda en modo lectura cuando el curso o el reto están cerrados.
- Compilación del frontend y Pint dirigido a los archivos PHP modificados correctos.

## Pendiente
- Verificación funcional de permisos, guardado y modo lectura.

## Archivos relevantes
- `resources/js/pages/Challenge.vue`, `resources/js/pages/Evidence.vue`, `routes/web.php`.
- `app/Http/Controllers/ChallengeEvidenceController.php`, `app/Models/ChallengeEvidence.php`, `database/migrations/2026_09_23_160418_create_challenge_evidence_table.php`.

## Decisiones
- Las evidencias se guardan como anotaciones acumulativas y muestran autor y fecha.
- El acceso sigue el contexto académico del reto; estudiantes no pueden abrir esta página.

## Último error
`pint --dirty` requiere Git, ausente en la imagen del contenedor; Pint pasó al ejecutarse sobre las rutas PHP modificadas.
