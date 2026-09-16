# Estado de la tarea

## Objetivo
Editor de rúbricas en página propia y evaluación en tabla implementados y verificados. Producción permanece en 3bfe097 (catálogo FP); nuevo editor aún sin desplegar.

## Completado
- Implementación principal en 8270aed: crear/editar rúbricas en página propia; filas de criterios y columnas de niveles con nota común. Cuatro niveles iniciales, ampliables hasta 20; hasta 40 criterios ordenables. Módulo solo para tipo reto, con opción GENERAL; descripción opcional debajo del nombre.
- Última aclaración del usuario aplicada: pesos en PORCENTAJES (hasta dos decimales), suma obligatoria de 100 %, con total/faltante/exceso y reparto igualitario que ajusta el redondeo. Sustituye la propuesta inicial de pesos relativos.
- Evaluación docente en zona principal del reto, selector de equipo/estudiante, anterior/siguiente y vuelta a matriz. Auto/coevaluación usa la misma tabla. Guardado automático, permisos por módulo y bloqueo por curso/reto cerrado conservados.
- Compatibilidad sin borrar datos: pesos relativos antiguos se convierten en porcentajes al editar. Plantillas con niveles desiguales requieren unificación explícita en editor. Copias históricas conservan notas, criterios y selecciones originales; tabla admite sus diferencias sin recalcularlas.
- Verificación: 52 pruebas / 428 aserciones correctas tanto en SQLite como PostgreSQL (editor, validación, permisos, histórico, creación y organización). Pint correcto en los PHP modificados; TypeScript/Vite correctos.
- Navegador: 3 pruebas nuevas correctas (editor completo y persistencia, evaluación/permisos y conversión de pesos antiguos) más 12 workflows existentes correctos, incluidos alumnado e históricos cerrados. Capturas escritorio/móvil revisadas. Contenido largo contenido en celdas, cuatro niveles visibles en escritorio; sin desbordamiento horizontal de página. Prueba de editor repetida tras mejorar capturas: correcta.
- Catálogo FP publicado en 3bfe097 el 16/09/2026: total 181 ciclos y 2.691 módulos ministeriales. Copia previa privada backups/production-20260916-3bfe097, migración/carga y HTTPS verificados. Grupos publicados previamente en 5a66325. No repetir reinicio académico ni carga de catálogo.

## Pendiente
- Nuevo editor sin desplegar: siguiente entrega debe construir imágenes desde commit y usar ops/deploy con copia previa, sin limpiar datos ni volver a cargar catálogo. No hay migración de esquema nueva.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- resources/js/pages/RubricEditor.vue; resources/js/components/RubricAssessment.vue; resources/js/rubrics.ts; resources/css/rubrics.css (importado por app.css).
- resources/js/pages/{Setup,Challenge,Student}.vue; app/Http/Controllers/SetupController.php; routes/web.php.
- tests/Feature/RubricEditorTest.php, SetupTest, AcademicWorkflowTest, ChallengeCreationTest, OrganizationNavigationTest; tests/Browser/{rubrics,workflows}.spec.ts.
- Capturas privadas test-results/rubric-{editor,assessment}-{desktop,mobile}.png. Pruebas solo compose.test.yml, destino http://browser-app:8083. PHP/Composer mediante ops/php.
- Fuente funcional: prompt.md y aclaraciones del usuario. docs/despliegue.md registra producción actual. ops/deploy, ops/backup y ops/Dockerfile.production para próxima entrega.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- Usuario autorizó implementar y actualizar progreso durante el trabajo. Evaluación coherente sin exigir páginas propias. Permitió borrar datos incompatibles, pero no ha sido necesario.
- Cursos cerrados solo lectura; cada reto conserva copia de su rúbrica. Cambiar plantilla no cambia evaluaciones históricas.
- VPS compartido: solo recursos propios. Producción con copia previa y pruebas de escritura aisladas. Construir desde git archive para excluir cambios ajenos.

## Último error
- Sin fallos pendientes. Desbordamiento de etiquetas accesibles corregido al posicionar el contenedor de tabla. Pruebas esperan visitas Inertia antes de leer URL/recargar.
- Pint --dirty no disponible por falta de Git en imagen; aplicado formato a lista explícita. Laravel 13.30.1, Inertia 3.3.3, PHPUnit 12.5.34. .ai/rules no existe. Guías testing/Inertia leídas; documentación Inertia v3 consultada mediante Boost con red autorizada.
