# Estado de la tarea

## Objetivo
Sustituir las pestañas del reto por una página de inicio con cuatro cajas (Estudiantes y Equipos, Evaluación, Evidencias, Configuración) con accesos directos, y quitar la cabecera del reto en cada sección para ganar espacio vertical.

## Completado
- Rama `reto-inicio` (sin fusionar ni desplegar).
- `resources/js/components/ChallengeHub.vue`: inicio del reto con las cuatro cajas; Evaluación destacada en morado con Tabla general, Ev. técnica y Ev. transversales; Equipos con integrantes; Evidencias con las dos últimas anotaciones; Configuración con reparto de pesos y edición de rúbricas.
- `Challenge.vue`: `/challenges/{id}` sin `tab` muestra el inicio; `?tab=…` y `?evaluation=…` abren la sección. En las secciones solo hay una barra fija con regreso al inicio y selector compacto (`tablist` «Vistas del reto», solo iconos por debajo de 1250 px).
- Tests de navegador adaptados y nuevo test «inicio del reto». `evidence.spec.ts` completo correcto (7/7) en el entorno de pruebas del VPS.
- Avisos flotantes (`Toasts.vue`, `notify()` en `lib.ts`): arriba a la derecha, X para cerrar, se desvanecen a los `TOAST_DURATION_MS` (5000 ms, configurable en `lib.ts` o por llamada; `duration: null` los mantiene). Pausa al pasar el ratón. Flash de éxito, error de contexto académico y errores de guardado del reto ya no ocupan espacio.
- Eliminado «Datos actualizados»; los errores del reto se muestran como toast con «Actualizar datos» (dentro de los modales siguen en línea).
- Inicio del reto compacto: sin Introducción masiva ni Exportar CSV, equipos con desvanecido final, 2 evidencias, modo compacto con altura ≤ 860 px. Verificado sin scroll a 1440×900 y 1280×800.
- Pantalla completa en la tabla general (icono junto a columnas/CSV) y en la rúbrica (icono en su barra); salida con botón o Esc.
- Disponible en el entorno de pruebas del VPS (checkout en `reto-inicio`). Tests de navegador sin actualizar ni ejecutar tras estos cambios, por indicación del usuario.
- Paleta morada desplegada antes en producción (`1c60762`).

## Pendiente
- Actualizar y pasar los tests de navegador (los avisos ya no están en línea: `tabpanel … getByRole('status')`/`notice error` pueden cambiar).
- Revisión del usuario; después fusionar en `main` y desplegar con `ops/deploy`.
- Fallos de navegador previos, también presentes en `main`: `rubrics.spec.ts` (botones antiguos «Rúbrica del equipo» y combo «Tipo» del editor) y cinco de `workflows.spec.ts` (enlaces «Profesor»/«Estudiante» de Organización).

## Archivos relevantes
- `resources/js/components/ChallengeHub.vue`, `resources/js/pages/Challenge.vue`, `resources/js/components/Toasts.vue`, `resources/js/lib.ts`, `resources/js/components/Layout.vue`.
- `tests/Browser/evidence.spec.ts`, `rubrics.spec.ts`, `workflows.spec.ts`, `empty-rubrics.spec.ts`.

## Decisiones
- La navegación entre secciones sigue siendo cliente (`router.push` con `preserveState`) para conservar borradores.
- Publicar/Reabrir solo en el inicio del reto; el selector de estado sigue en Evaluación.
- Pruebas de navegador: el VPS cambia temporalmente a la rama, `npm run build`, `migrate:fresh --seed` en `erronk2d-test` y vuelve a `main`.

## Último error
- Dos carreras en la prueba de pestañas (volver atrás/recargar antes de completar la visita); corregidas esperando la URL.
