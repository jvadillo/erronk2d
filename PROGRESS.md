# Estado de la tarea

## Objetivo
Planificar el editor de rúbricas en página propia y tabla: criterios por fila; Módulo (solo tipo reto), Nombre, Peso y niveles por columnas. Catálogo FP publicado en 3bfe097.

## Completado
- Producción app/web en 3bfe097, construidas desde git archive; TypeScript/Vite correctos. Despliegue mediante ops/deploy y copia previa privada backups/production-20260916-3bfe097 de 5a66325: índice PostgreSQL, gzip y permisos 700/600 verificados. Mantenimiento retirado; sin migraciones de esquema pendientes.
- Carga ejecutada: 179 ciclos y 2.687 módulos nuevos; total 181/2.691. Previsualización posterior indica cero pendientes. Huellas de ciclos/módulos anteriores y vínculos de grupos coinciden exactamente.
- HTTPS: acceso administrativo, Ciclos y Módulos con totales correctos, recursos y cookie Secure/HttpOnly verificados; sesión cerrada. Web/PostgreSQL saludables. Entorno de pruebas retirado.
- Catálogo revisado: 181 ciclos (28 básicos, 62 medios, 91 superiores) y 2.691 módulos; currículo ministerial, distribución por cursos distinta de Euskadi. Alternativa ministerial autorizada previamente.
- Importador transaccional con previsualización, filtro por ciclo, bloqueo concurrente, conservación de registros existentes y rechazo de coincidencias ambiguas. No añade módulos a grupos existentes.
- Siete pruebas / 36 aserciones correctas en SQLite y PostgreSQL: carga completa, repetición sin duplicados, previsualización, conservación, cancelación de cargas ambiguas y bloqueo concurrente. Pint aplicado a ambos PHP.
- Grupos y evaluaciones propias publicados y verificados en 5a66325 el 15/09/2026. Copia previa privada: backups/production-20260915-5a66325. No repetir pruebas de Grupos ni reinicio académico.

## Pendiente
- Rúbricas: revisión inicial realizada; pendientes respuestas sobre alcance (editor/evaluación), notas comunes por columna o por celda, pesos relativos o porcentajes y descripción del criterio. No implementado ni desplegado este cambio.
- Propuesta base: crear/editar en página propia, número de niveles común configurable, cuatro iniciales, añadir criterio como fila, ordenar filas y conservar GENERAL para criterios de reto. Concretar plan tras las respuestas.
- Catálogo FP sin tareas pendientes. No repetir pruebas ya verificadas salvo nuevos cambios o fallos.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- Rúbricas: resources/js/pages/Setup.vue (modal actual), Challenge.vue (evaluación docente), Student.vue (auto/coevaluación); app/Http/Controllers/SetupController.php, ChallengeController.php; app/Models/Rubric.php; routes/web.php; resources/css/app.css.
- Pruebas relacionadas: SetupTest, ChallengeCreationTest y tests/Browser/workflows.spec.ts. prompt.md secciones de rúbricas consultadas; cada reto conserva copia independiente.
- app/Console/Commands/ImportOfficialCatalog.php; database/seeders/official-catalog.json; tests/Feature/ImportOfficialCatalogTest.php.
- ops/deploy, ops/backup, ops/php, ops/Dockerfile.production, compose.production.yml, compose.test.yml; docs/despliegue.md.
- Fuente funcional: prompt.md y aclaraciones del usuario; docs/decisiones.md conserva el plan académico anterior.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- El usuario solicita preguntas y plan antes de desarrollar el nuevo editor. Autoriza borrar rúbricas/evaluaciones anteriores si surge incompatibilidad; no hay usuarios reales. Por ahora parece viable conservar los datos, pendiente del diseño acordado.
- Carga aditiva, sin limpieza, sin sobrescribir nombres/códigos ni modificar grupos existentes. Nuevos módulos se añaden desde Grupos.
- VPS compartido: solo recursos propios, web en 127.0.0.1:8082. Producción con copia previa; pruebas de escritura aisladas.
- Cursos cerrados solo lectura; evaluaciones propias de grupo; retirada de módulos conserva histórico. Migración de evaluaciones sin rollback automático.
- Construir desde git archive para excluir cambios ajenos. Mantener cuentas demo y administrador; no repetir reinicio académico.

## Último error
- Sin fallos pendientes. Restricción de red en comprobación HTTPS resuelta con ejecución autorizada.
- Pint --dirty no disponible por falta de Git en la imagen; aplicada lista explícita. Laravel 13.30.1, Inertia 3.3.3, PHPUnit 12.5.34. .ai/rules no existe; guía testing leída en vendor/laravel/boost.
