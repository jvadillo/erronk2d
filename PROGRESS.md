# Estado de la tarea

## Objetivo
Sustituir las pestañas del reto por una página de inicio con cuatro cajas (Estudiantes y Equipos, Evaluación, Evidencias, Configuración) con accesos directos, y quitar la cabecera del reto en cada sección para ganar espacio vertical.

## Completado
- Rama `reto-inicio` (sin fusionar ni desplegar).
- `resources/js/components/ChallengeHub.vue`: inicio del reto con las cuatro cajas; Evaluación destacada en morado con Tabla general, Ev. técnica, Ev. transversales, Introducción masiva y Exportar CSV; Equipos con integrantes; Evidencias con las tres últimas anotaciones; Configuración con reparto de pesos y edición de rúbricas.
- `Challenge.vue`: `/challenges/{id}` sin `tab` muestra el inicio; `?tab=…` y `?evaluation=…` abren la sección. En las secciones solo hay una barra fija con regreso al inicio y selector compacto (`tablist` «Vistas del reto», solo iconos por debajo de 1250 px).
- Tests de navegador adaptados y nuevo test «inicio del reto». `evidence.spec.ts` completo correcto (7/7) en el entorno de pruebas del VPS.
- Paleta morada desplegada antes en producción (`1c60762`).

## Pendiente
- Revisión del usuario; después fusionar en `main` y desplegar con `ops/deploy`.
- Fallos de navegador previos, también presentes en `main`: `rubrics.spec.ts` (botones antiguos «Rúbrica del equipo» y combo «Tipo» del editor) y cinco de `workflows.spec.ts` (enlaces «Profesor»/«Estudiante» de Organización).

## Archivos relevantes
- `resources/js/components/ChallengeHub.vue`, `resources/js/pages/Challenge.vue`.
- `tests/Browser/evidence.spec.ts`, `rubrics.spec.ts`, `workflows.spec.ts`, `empty-rubrics.spec.ts`.

## Decisiones
- La navegación entre secciones sigue siendo cliente (`router.push` con `preserveState`) para conservar borradores.
- Publicar/Reabrir solo en el inicio del reto; el selector de estado sigue en Evaluación.
- Pruebas de navegador: el VPS cambia temporalmente a la rama, `npm run build`, `migrate:fresh --seed` en `erronk2d-test` y vuelve a `main`.

## Último error
- Dos carreras en la prueba de pestañas (volver atrás/recargar antes de completar la visita); corregidas esperando la URL.
