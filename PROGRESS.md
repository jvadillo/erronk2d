# Estado de la tarea

## Objetivo
Catálogo FP cargado y versión 3bfe097 desplegada y verificada en producción el 16/09/2026.

## Completado
- Producción app/web en 3bfe097, construidas desde git archive; TypeScript/Vite correctos. Despliegue mediante ops/deploy y copia previa privada backups/production-20260916-3bfe097 de 5a66325: índice PostgreSQL, gzip y permisos 700/600 verificados. Mantenimiento retirado; sin migraciones de esquema pendientes.
- Carga ejecutada: 179 ciclos y 2.687 módulos nuevos; total 181/2.691. Previsualización posterior indica cero pendientes. Huellas de ciclos/módulos anteriores y vínculos de grupos coinciden exactamente.
- HTTPS: acceso administrativo, Ciclos y Módulos con totales correctos, recursos y cookie Secure/HttpOnly verificados; sesión cerrada. Web/PostgreSQL saludables. Entorno de pruebas retirado.
- Catálogo revisado: 181 ciclos (28 básicos, 62 medios, 91 superiores) y 2.691 módulos; currículo ministerial, distribución por cursos distinta de Euskadi. Alternativa ministerial autorizada previamente.
- Importador transaccional con previsualización, filtro por ciclo, bloqueo concurrente, conservación de registros existentes y rechazo de coincidencias ambiguas. No añade módulos a grupos existentes.
- Siete pruebas / 36 aserciones correctas en SQLite y PostgreSQL: carga completa, repetición sin duplicados, previsualización, conservación, cancelación de cargas ambiguas y bloqueo concurrente. Pint aplicado a ambos PHP.
- Grupos y evaluaciones propias publicados y verificados en 5a66325 el 15/09/2026. Copia previa privada: backups/production-20260915-5a66325. No repetir pruebas de Grupos ni reinicio académico.

## Pendiente
- Sin tareas pendientes de esta carga y despliegue. No repetir pruebas ya verificadas salvo nuevos cambios o fallos.
- Google aplazado (redirect_uri_mismatch externo). Copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- app/Console/Commands/ImportOfficialCatalog.php; database/seeders/official-catalog.json; tests/Feature/ImportOfficialCatalogTest.php.
- ops/deploy, ops/backup, ops/php, ops/Dockerfile.production, compose.production.yml, compose.test.yml; docs/despliegue.md.
- Fuente funcional: prompt.md y aclaraciones del usuario; docs/decisiones.md conserva el plan académico anterior.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- Carga aditiva, sin limpieza, sin sobrescribir nombres/códigos ni modificar grupos existentes. Nuevos módulos se añaden desde Grupos.
- VPS compartido: solo recursos propios, web en 127.0.0.1:8082. Producción con copia previa; pruebas de escritura aisladas.
- Cursos cerrados solo lectura; evaluaciones propias de grupo; retirada de módulos conserva histórico. Migración de evaluaciones sin rollback automático.
- Construir desde git archive para excluir cambios ajenos. Mantener cuentas demo y administrador; no repetir reinicio académico.

## Último error
- Sin fallos pendientes. Restricción de red en comprobación HTTPS resuelta con ejecución autorizada.
- Pint --dirty no disponible por falta de Git en la imagen; aplicada lista explícita. Laravel 13.30.1, Inertia 3.3.3, PHPUnit 12.5.34. .ai/rules no existe; guía testing leída en vendor/laravel/boost.
