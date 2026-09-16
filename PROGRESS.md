# Estado de la tarea

## Objetivo
Cargar el catálogo FP preparado y desplegar en producción, autorizado el 16/09/2026. El usuario permite omitir pruebas no necesarias porque aún no hay usuarios reales.

## Completado
- Catálogo revisado: 181 ciclos (28 básicos, 62 medios, 91 superiores) y 2.691 módulos; currículo ministerial, distribución por cursos distinta de Euskadi. Alternativa ministerial autorizada previamente.
- Importador transaccional con previsualización, filtro por ciclo, bloqueo concurrente, conservación de registros existentes y rechazo de coincidencias ambiguas. No añade módulos a grupos existentes.
- Siete pruebas / 36 aserciones correctas en SQLite y PostgreSQL: carga completa, repetición sin duplicados, previsualización, conservación, cancelación de cargas ambiguas y bloqueo concurrente. Pint aplicado a ambos PHP.
- Grupos y evaluaciones propias publicados y verificados en 5a66325 el 15/09/2026. Copia previa privada: backups/production-20260915-5a66325. No repetir pruebas de Grupos ni reinicio académico.

## Pendiente
- Construir imágenes desde el commit, previsualizar catálogo en producción, desplegar mediante ops/deploy con copia previa y ejecutar erronk2d:catalog --execute; verificar recuentos, repetición y HTTPS.
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
- Sin fallo funcional pendiente. Corregida comparación de prueba para leer ambos estados desde BD (orden de claves).
- Pint --dirty no disponible por falta de Git en la imagen; aplicada lista explícita. Laravel 13.30.1, Inertia 3.3.3, PHPUnit 12.5.34. .ai/rules no existe; guía testing leída en vendor/laravel/boost.
