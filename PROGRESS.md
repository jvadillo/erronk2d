# Estado de la tarea

## Objetivo
Compactar las descripciones de evaluación y simplificar la página del reto y la gestión de equipos, según la petición del 22/09/2026.

## Completado
- Descripciones generales de criterios y de niveles limitadas a cuatro líneas, expandibles por clic o teclado. Leer un nivel no cambia la nota y funciona también en solo lectura.
- Eliminados `formula-note`, `evaluation-progress` y su cálculo/estilos; botón Histórico oculto temporalmente, conservando su funcionalidad interna.
- Gestión de equipos: inicialmente solo miembros actuales, eliminación directa y botón Añadir estudiantes que despliega casillas en columnas adaptables. Conservados validación de 2–5 miembros, exclusividad entre equipos y guardado explícito.
- TypeScript/Vite correctos. Pruebas específicas de evaluación docente y equipos correctas (2/2), con persistencia, permisos, teclado y móvil. Capturas revisadas.
- Flujos compartidos verificados: 11/12 workflows correctos en ejecución completa; el restante se corrigió por expectativas antiguas (progreso eliminado y botón Atrás) y pasó al repetirlo. Incluye autoevaluación y cursos cerrados. Total: 13 flujos distintos correctos contando evaluación docente.
- Entrega anterior: editor de rúbricas en página propia, pesos porcentuales y evaluación en tabla. Producción publicada en 0298155; detalle y copias previas en `docs/despliegue.md`.
- Catálogo FP publicado previamente en 3bfe097. No repetir carga de catálogo ni reinicio académico.

## Pendiente
- Implementación terminada; cambios todavía no desplegados.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- `resources/js/pages/Challenge.vue`, `resources/js/components/RubricAssessment.vue`, `resources/css/{app,rubrics}.css`.
- `tests/Browser/{rubrics,workflows}.spec.ts`; capturas privadas en `test-results/`.
- `compose.test.yml`: pruebas solo en http://browser-app:8083; PHP/Composer mediante `ops/php`.
- Fuente funcional: `prompt.md` y aclaraciones del usuario. `ops/deploy`, `ops/backup`, `ops/Dockerfile.production`, `docs/despliegue.md` para próximas entregas.
- Cambios ajenos: `CLAUDE.md` y `docs/PROGRESS.md` eliminados; `LARAVEL_BOOST_GUIDELINES.md` sin seguimiento. No incluirlos.
- Privados: `.env.production`, `ops/production-credentials`, `backups/`, `test-results/`. Nunca mostrarlos ni versionarlos.

## Decisiones
- Cursos cerrados solo lectura; cada reto conserva copia de su rúbrica. Cambiar plantilla no cambia evaluaciones históricas.
- VPS compartido: solo recursos propios. Producción con copia previa y pruebas de escritura aisladas. Construir desde git archive para excluir cambios ajenos.
- Sin cambios de PHP, dependencias o esquema. `.ai/rules` no existe. Guías locales testing/Inertia leídas; documentación Inertia v3 consultada mediante Boost con red autorizada.

## Último error
- Sin errores pendientes. Prueba antigua exigía el bloque de progreso eliminado: ajustada y repetida correctamente.
