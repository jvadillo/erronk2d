# Estado de la tarea

## Objetivo
Renombrar Clase a Grupo, trasladar las evaluaciones del curso académico al grupo y ofrecer gestión rápida de matrículas y módulos desde Grupos. Implementación local; producción sigue en 878840e.

## Completado
- Terminología Grupo en navegación, formularios, mensajes, retos e informes. Se conservan identificadores internos y rutas `classroom` por compatibilidad.
- Evaluaciones propias de cada grupo (1–12; 3 por defecto). Migración copia las antiguas evaluaciones anuales a sus grupos, reasigna retos y conserva publicaciones. Cursos ya no configuran evaluaciones; creación de retos e informes usan las del grupo.
- Gestión rápida desde tarjetas de grupo: matrícula por correo exacto, baja conservando histórico, añadir/retirar módulos y configurar evaluaciones.
- Corregido orden de evaluaciones al guardar: se recargan posiciones tras el desplazamiento temporal para que Eloquent guarde también las que mantienen su orden. Regresión reproduce el fallo previo; 16 pruebas / 141 aserciones correctas en SQLite y PostgreSQL. Pint correcto en ambos archivos PHP modificados.
- Retirar un módulo mantiene su vínculo histórico, responsables, retos y notas; excluido de nuevos retos, restaurable sin duplicados. Nuevos módulos de catálogo/importación se añaden manualmente a grupos existentes.
- Regresión SQLite: 155 pruebas correctas y una expectativa de texto Clase→Grupo corregida; sus 10 pruebas repetidas pasan. PostgreSQL: 156 pruebas / 1.347 aserciones correctas. Pruebas focalizadas correctas: migración desde esquema anterior, aislamiento, permisos, edición de evaluaciones, retirada/restauración y conservación/corrección de notas publicadas. TypeScript/Vite correctos; Pint aplicado a PHP modificados.
- Entrega académica anterior publicada y reinicio único ya terminado: no repetir limpieza ni comprobaciones previas. Copia privada previa `backups/production-20260914-878840e` de versión 38e25ce; producción conserva administrador y cuentas demo 40/10.

## Pendiente
- Terminar prueba de navegador de Grupos en escritorio/móvil y commit de verificación. Sin despliegue solicitado en esta tarea.
- Catálogo oficial aplazado: mantener `app/Console/Commands/ImportOfficialCatalog.php` y `tests/Feature/ImportOfficialCatalogTest.php` ajenos sin incluir en commits. Extracción /tmp/erronk2d-* con 233 fichas estatales y 224 correspondencias IVAC; currículo vasco prioritario, ministerial autorizado como alternativa. No limpiar ni cargar producción.
- Google aplazado (`redirect_uri_mismatch` externo), copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- `app/Http/Controllers/{Setup,Challenge,Import}Controller.php`; `app/Models/{AcademicYear,Classroom,Period}.php`; `app/Domain/Grades/Gradebook.php`.
- `resources/js/components/GroupCard.vue`, `resources/js/pages/{Setup,Dashboard}.vue`; textos de organización/retos/informes.
- Migración `2026_09_15_111938_move_periods_to_classrooms.php`; factory Challenge, DatabaseSeeder y orden de borrado de ResetAcademicData.
- Pruebas `GroupManagementTest`, `GroupMigrationTest`, Setup/Enrollment/ChallengeCreation/Gradebook/OrganizationNavigation; `tests/Browser/workflows.spec.ts`.
- Fuente funcional: `prompt.md`, aclaraciones del usuario y `docs/decisiones.md` (plan académico anterior). PHP/Composer mediante `ops/php`; navegador solo compose.test.yml, destino http://browser-app:8083.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md y archivos de catálogo sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- Aclaraciones del usuario: módulos únicamente del mismo ciclo/nivel; retirada permitida conservando histórico; todo el profesorado miembro y administración gestiona módulos/evaluaciones; se pueden añadir evaluaciones y quitar solo las que no tengan retos.
- Evaluaciones con retos conservan nombre e ID; las vacías pueden renombrarse/eliminarse. Propiedad, profesorado y responsables mantienen sus permisos previos (propietario/admin); matrícula sigue disponible a miembros.
- Migración de evaluaciones sin reversión automática: volver a la arquitectura anual exige restaurar copia previa. No ejecutar rollback de esta migración.
- Años cerrados solo lectura; contexto persistente y rechazo de pestañas antiguas. Matrícula de grupo cubre todos sus módulos; bajas no borran notas ni otras matrículas. No arrastrar ni sincronizar automáticamente retos ya evaluados/publicados.
- VPS compartido: solo recursos propios, app web en 127.0.0.1:8082; no tocar gateway/API vecina/Docker global. Producción solo mediante despliegue con copia; pruebas de escritura exclusivamente aisladas.

## Último error
- Navegador detectó evaluaciones nuevas antes de las anteriores; corrección y regresión PHP terminadas. Pendiente completar navegador con datos aislados recién preparados.
- Pint --dirty falla por ausencia de Git en imagen PHP; formato aplicado con lista explícita de archivos. Documentación Inertia v3 consultada por Boost con red autorizada; guías testing/Inertia leídas en vendor/laravel/boost. .ai/rules no existe.
- Laravel 13.30.1, Inertia Laravel 3.3.3, PHPUnit 12.5.34, PHP 8.4.25. No repetir auditoría VPS, bootstrap, limpieza, correos ni OAuth.
