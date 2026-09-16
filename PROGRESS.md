# Estado de la tarea

## Objetivo
Implementar rúbricas en página propia y tabla, y evaluación coherente dentro del reto. Catálogo FP publicado en 3bfe097; este cambio aún no desplegado.

## Completado
- Rúbricas: implementado editor de página propia y tabla común de evaluación; servidor exige columnas/notas comunes y pesos porcentuales con dos decimales que sumen 100 %. Rutas protegidas por propiedad/rol; copia histórica de retos conservada.
- Primera verificación del cambio: 52 pruebas / 428 aserciones correctas tanto en SQLite como PostgreSQL; TypeScript/Vite correctos; Pint aplicado. Falta completar navegador y revisar capturas antes del commit funcional.
- Producción app/web en 3bfe097, construidas desde git archive; TypeScript/Vite correctos. Despliegue mediante ops/deploy y copia previa privada backups/production-20260916-3bfe097 de 5a66325: índice PostgreSQL, gzip y permisos 700/600 verificados. Mantenimiento retirado; sin migraciones de esquema pendientes.
- Carga ejecutada: 179 ciclos y 2.687 módulos nuevos; total 181/2.691. Previsualización posterior indica cero pendientes. Huellas de ciclos/módulos anteriores y vínculos de grupos coinciden exactamente.
- HTTPS: acceso administrativo, Ciclos y Módulos con totales correctos, recursos y cookie Secure/HttpOnly verificados; sesión cerrada. Web/PostgreSQL saludables. Entorno de pruebas retirado.
- Catálogo revisado: 181 ciclos (28 básicos, 62 medios, 91 superiores) y 2.691 módulos; currículo ministerial, distribución por cursos distinta de Euskadi. Alternativa ministerial autorizada previamente.
- Importador transaccional con previsualización, filtro por ciclo, bloqueo concurrente, conservación de registros existentes y rechazo de coincidencias ambiguas. No añade módulos a grupos existentes.
- Siete pruebas / 36 aserciones correctas en SQLite y PostgreSQL: carga completa, repetición sin duplicados, previsualización, conservación, cancelación de cargas ambiguas y bloqueo concurrente. Pint aplicado a ambos PHP.
- Grupos y evaluaciones propias publicados y verificados en 5a66325 el 15/09/2026. Copia previa privada: backups/production-20260915-5a66325. No repetir pruebas de Grupos ni reinicio académico.

## Pendiente
- Implementado, pendiente revisar en navegador: editor crear/editar en página propia; criterios por fila y niveles comunes por columna, cuatro iniciales ampliables. Módulo solo en tipo reto; descripción opcional dentro de Nombre sin romper alineación.
- Última aclaración del usuario: pesos en PORCENTAJES, suma obligatoria 100 % (sustituye recomendación inicial de pesos relativos). Nota común por columna, descripción por celda.
- Implementada evaluación docente en tabla en zona principal del reto con selector de equipo/estudiante y vuelta a matriz; auto/coevaluación con el mismo componente. No exige páginas propias de evaluación.
- En curso: PostgreSQL, navegador con tests/Browser/rubrics.spec.ts y workflows afectados, capturas escritorio/móvil. Tras validar, commit funcional; no se ha desplegado el nuevo editor.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- Nuevos: resources/js/pages/RubricEditor.vue, resources/js/components/RubricAssessment.vue, resources/js/rubrics.ts, resources/css/rubrics.css; tests/Feature/RubricEditorTest.php y tests/Browser/rubrics.spec.ts.
- Rúbricas: resources/js/pages/Setup.vue (modal actual), Challenge.vue (evaluación docente), Student.vue (auto/coevaluación); app/Http/Controllers/SetupController.php, ChallengeController.php; app/Models/Rubric.php; routes/web.php; resources/css/app.css.
- Pruebas relacionadas: SetupTest, ChallengeCreationTest y tests/Browser/workflows.spec.ts. prompt.md secciones de rúbricas consultadas; cada reto conserva copia independiente.
- app/Console/Commands/ImportOfficialCatalog.php; database/seeders/official-catalog.json; tests/Feature/ImportOfficialCatalogTest.php.
- ops/deploy, ops/backup, ops/php, ops/Dockerfile.production, compose.production.yml, compose.test.yml; docs/despliegue.md.
- Fuente funcional: prompt.md y aclaraciones del usuario; docs/decisiones.md conserva el plan académico anterior.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- Implementación autorizada tras aclaraciones. Se permite borrar datos de rúbricas/evaluaciones incompatibles, pero se prevé conservarlos: porcentajes equivalentes al editar y tablas compatibles para copias históricas. No tocar catálogo FP ni otras áreas.
- Carga aditiva, sin limpieza, sin sobrescribir nombres/códigos ni modificar grupos existentes. Nuevos módulos se añaden desde Grupos.
- VPS compartido: solo recursos propios, web en 127.0.0.1:8082. Producción con copia previa; pruebas de escritura aisladas.
- Cursos cerrados solo lectura; evaluaciones propias de grupo; retirada de módulos conserva histórico. Migración de evaluaciones sin rollback automático.
- Construir desde git archive para excluir cambios ajenos. Mantener cuentas demo y administrador; no repetir reinicio académico.

## Último error
- Sin fallos pendientes. Restricción de red en comprobación HTTPS resuelta con ejecución autorizada.
- Pint --dirty no disponible por falta de Git en la imagen; aplicada lista explícita. Laravel 13.30.1, Inertia 3.3.3, PHPUnit 12.5.34. .ai/rules no existe; guía testing leída en vendor/laravel/boost.
