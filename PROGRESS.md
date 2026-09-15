# Estado de la tarea

## Objetivo
Gestión de Grupos, evaluaciones propias y matrícula/módulos rápidos publicada en `5a66325`; comprobación de producción terminada el 15/09/2026.

## Completado
- Imágenes `5a66325` construidas desde `git archive`, sin el catálogo ajeno. TypeScript/Vite correctos; arranque aislado, esquema de Grupos, login/salud, rutas protegidas y 17 recursos HTTP correctos. Recuentos y huellas previos obtenidos mediante consultas de solo lectura para contrastar tras migrar.
- Despliegue autorizado mediante `ops/deploy`, sin reinicio académico. Copia privada `backups/production-20260915-5a66325` de versión `878840e`, índice PostgreSQL/gzip validados y permisos 700/600. Migración aplicada; app/web en `5a66325`, mantenimiento retirado.
- Producción: recuentos y huellas de cuentas, grupos, matrículas, módulos, retos y notas coinciden (excluidos campos técnicos modificados). Ningún reto con evaluación ajena ni grupo sin evaluaciones. Acceso administrativo, Grupos/cursos/informes autenticados, recursos HTTPS y cookies Secure/HttpOnly correctos. Sesión de comprobación cerrada; API vecina `/api/health` HTTP 200 y entorno aislado retirado.
- Terminología Grupo en navegación, formularios, mensajes, retos e informes. Se conservan identificadores internos y rutas `classroom` por compatibilidad.
- Evaluaciones propias de cada grupo (1–12; 3 por defecto). Migración copia las antiguas evaluaciones anuales a sus grupos, reasigna retos y conserva publicaciones. Cursos ya no configuran evaluaciones; creación de retos e informes usan las del grupo.
- Gestión rápida desde tarjetas de grupo: matrícula por correo exacto, baja conservando histórico, añadir/retirar módulos y configurar evaluaciones.
- Corregido orden de evaluaciones al guardar: se recargan posiciones tras el desplazamiento temporal para que Eloquent guarde también las que mantienen su orden. Regresión reproduce el fallo previo; 16 pruebas / 141 aserciones correctas en SQLite y PostgreSQL. Pint correcto en ambos archivos PHP modificados.
- Navegador: 12/12 pruebas correctas (navegación, retos, notas, matrículas, equipos, revisión de solicitudes, Grupos, contexto y cursos cerrados). Ajustada anchura de nombres de evaluación en móvil; TypeScript/Vite y repetición focalizada de Grupos correctos. Capturas de tarjeta en escritorio/móvil y diálogo móvil revisadas; sin desbordamiento horizontal ni errores JavaScript en el flujo de Grupos.
- Retirar un módulo mantiene su vínculo histórico, responsables, retos y notas; excluido de nuevos retos, restaurable sin duplicados. Nuevos módulos de catálogo/importación se añaden manualmente a grupos existentes.
- Regresión SQLite: 155 pruebas correctas y una expectativa de texto Clase→Grupo corregida; sus 10 pruebas repetidas pasan. PostgreSQL: 156 pruebas / 1.347 aserciones correctas. Pruebas focalizadas correctas: migración desde esquema anterior, aislamiento, permisos, edición de evaluaciones, retirada/restauración y conservación/corrección de notas publicadas. TypeScript/Vite correctos; Pint aplicado a PHP modificados.
- Entrega académica anterior publicada y reinicio único ya terminado: no repetir limpieza ni comprobaciones previas. Copia privada previa `backups/production-20260914-878840e` de versión 38e25ce; producción conserva administrador y cuentas demo 40/10.

## Pendiente
- Sin tareas pendientes de Grupos ni de su despliegue. No repetir el reinicio académico ni las comprobaciones terminadas salvo nuevos cambios o errores.
- Catálogo oficial aplazado: mantener `app/Console/Commands/ImportOfficialCatalog.php` y `tests/Feature/ImportOfficialCatalogTest.php` ajenos sin incluir en commits. Extracción /tmp/erronk2d-* con 233 fichas estatales y 224 correspondencias IVAC; currículo vasco prioritario, ministerial autorizado como alternativa. No limpiar ni cargar producción.
- Google aplazado (`redirect_uri_mismatch` externo), copias externas/carga/supervisión fuera de alcance.

## Archivos relevantes
- `app/Http/Controllers/{Setup,Challenge,Import}Controller.php`; `app/Models/{AcademicYear,Classroom,Period}.php`; `app/Domain/Grades/Gradebook.php`.
- `resources/js/components/GroupCard.vue`, `resources/js/pages/{Setup,Dashboard}.vue`; textos de organización/retos/informes.
- Migración `2026_09_15_111938_move_periods_to_classrooms.php`; factory Challenge, DatabaseSeeder y orden de borrado de ResetAcademicData.
- Pruebas `GroupManagementTest`, `GroupMigrationTest`, Setup/Enrollment/ChallengeCreation/Gradebook/OrganizationNavigation; `tests/Browser/workflows.spec.ts`.
- Fuente funcional: `prompt.md`, aclaraciones del usuario y `docs/decisiones.md` (plan académico anterior). PHP/Composer mediante `ops/php`; navegador solo compose.test.yml, destino http://browser-app:8083.
- `docs/despliegue.md`: versión e identificadores de imágenes publicados, copia previa y comprobaciones. Construir futuras imágenes desde el commit para excluir archivos ajenos sin seguimiento.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md y archivos de catálogo sin seguimiento. No incluirlos.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.

## Decisiones
- Aclaraciones del usuario: módulos únicamente del mismo ciclo/nivel; retirada permitida conservando histórico; todo el profesorado miembro y administración gestiona módulos/evaluaciones; se pueden añadir evaluaciones y quitar solo las que no tengan retos.
- Evaluaciones con retos conservan nombre e ID; las vacías pueden renombrarse/eliminarse. Propiedad, profesorado y responsables mantienen sus permisos previos (propietario/admin); matrícula sigue disponible a miembros.
- Migración de evaluaciones sin reversión automática: volver a la arquitectura anual exige restaurar copia previa. No ejecutar rollback de esta migración.
- Años cerrados solo lectura; contexto persistente y rechazo de pestañas antiguas. Matrícula de grupo cubre todos sus módulos; bajas no borran notas ni otras matrículas. No arrastrar ni sincronizar automáticamente retos ya evaluados/publicados.
- VPS compartido: solo recursos propios, app web en 127.0.0.1:8082; no tocar gateway/API vecina/Docker global. Producción solo mediante despliegue con copia; pruebas de escritura exclusivamente aisladas.

## Último error
- Sin fallos pendientes conocidos. Orden de evaluaciones corregido en `11a2ee1`; regresión, navegador y comprobación de producción terminados.
- Pint --dirty falla por ausencia de Git en imagen PHP; formato aplicado con lista explícita de archivos. Documentación Inertia v3 consultada por Boost con red autorizada; guías testing/Inertia leídas en vendor/laravel/boost. .ai/rules no existe.
- Laravel 13.30.1, Inertia Laravel 3.3.3, PHPUnit 12.5.34, PHP 8.4.25. No repetir auditoría VPS, bootstrap, limpieza, correos ni OAuth.
