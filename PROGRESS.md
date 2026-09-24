# Estado de la tarea

## Objetivo
Rehacer la UI de Evidencias para consultar anotaciones por estudiante con agilidad y desplegarla en producción.

## Completado
- Vista con lista alfabética, búsqueda sin tildes por nombre/equipo, contadores y filtro «Con anotaciones».
- Ficha individual con historial, autor y fecha; formulario contextual y borradores independientes por estudiante durante la visita.
- Diseño adaptado a escritorio, tablet y móvil; navegación móvil entre lista y ficha con foco accesible.
- Las evidencias de antiguos participantes siguen accesibles en modo lectura.
- `npm run build` y tres pruebas Playwright específicas pasan: consulta/guardado persistente, borradores, errores de validación, solo lectura y denegación al alumnado.
- Capturas revisadas y ausencia de desbordamiento comprobada en 390, 768, 820 y 1024 px.
- Editor de equipos mejorado previamente en `99ecaea`; producción anterior `03a73f0`.
- Botones de evaluación de la página del reto renombrados a «Ev. técnica» y «Ev. transversales».
- Resumen de estudiantes/equipos retirado del popup «Organizar equipos».
- Espaciado interior del buscador y de la lista de estudiantes en «Organizar equipos» ampliado.
- Producción desplegada desde `10c99a6`; copia previa íntegra en `backups/production-20260924-10c99a6` y HTTPS verificado (`/up` correcto, `/login` HTTP 200).

## Pendiente
- Sin pasos pendientes.

## Archivos relevantes
- `resources/js/pages/Evidence.vue`, `resources/js/pages/Challenge.vue`, `resources/css/app.css`, `tests/Browser/evidence.spec.ts`.
- `app/Http/Controllers/ChallengeEvidenceController.php`, `routes/web.php`.
- `ops/deploy`, `ops/Dockerfile.production`, `compose.production.yml`.

## Decisiones
- Seleccionar un estudiante muestra solo su historial; el formulario guarda para esa misma persona.
- Borradores en memoria, sin guardar anotaciones privadas en el almacenamiento del navegador.
- Despliegue desde un archivo Git del commit para excluir cambios locales ajenos en rúbricas y documentación.

## Último error
La primera comprobación HTTPS no resolvió DNS en el entorno aislado; se repitió con acceso de red y `/up` y `/login` respondieron correctamente.
