# Estado de la tarea

## Objetivo
Mejorar la creación de equipos en los retos y mantener el seguimiento de evidencias individuales.

## Completado
- Añadido el botón «Evidencias» junto a la rúbrica y una página con búsqueda y anotaciones por estudiante.
- Persistencia con estudiante, autor y fecha; solo profesorado puede consultar o guardar.
- La pantalla queda en modo lectura cuando el curso o el reto están cerrados.
- Compilación del frontend y Pint dirigido a los archivos PHP modificados correctos.
- Desplegado `03a73f0` en producción; migración aplicada y app/web saludables.
- HTTPS `/up` correcto y `/login` HTTP 200. Copia previa en `backups/production-20260923-03a73f0` validada por `ops/deploy`.
- Editor de equipos renovado con tarjetas, integrantes visibles y selector desplegable con búsqueda por nombre.
- La lista ofrece solo estudiantes sin equipo, ordenados alfabéticamente; la búsqueda ignora tildes.
- `npm run build` y el flujo Playwright de equipos pasan.

## Pendiente
- Verificación funcional específica de permisos, guardado y modo lectura de evidencias.

## Archivos relevantes
- `resources/js/pages/Challenge.vue`, `resources/js/pages/Evidence.vue`, `routes/web.php`.
- `app/Http/Controllers/ChallengeEvidenceController.php`, `app/Models/ChallengeEvidence.php`, `database/migrations/2026_09_23_160418_create_challenge_evidence_table.php`.
- `resources/css/app.css`, `tests/Browser/workflows.spec.ts`.

## Decisiones
- Las evidencias se guardan como anotaciones acumulativas y muestran autor y fecha.
- El acceso sigue el contexto académico del reto; estudiantes no pueden abrir esta página.
- Para cambiar un estudiante de equipo, se quita de su equipo actual y vuelve a aparecer entre los disponibles.

## Último error
La primera expectativa del test de equipos asumía un orden alfabético incorrecto; se corrigió y la ejecución enfocada pasó.
