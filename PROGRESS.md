# Estado de la tarea

## Objetivo
Completar y publicar la arquitectura de cursos académicos independientes aprobada el 14/09/2026. Implementación y reinicio autorizados; **producción todavía en 38e25ce, sin cambios ni borrados**.

## Completado
- Base anual/contexto persistente en commit 22653e5. Controladores, permisos de clase/módulo, matrícula múltiple, catálogos estables y rúbricas privadas/compartidas implementados en el árbol de trabajo.
- Selector visible de curso, aislamiento transversal, bloqueo de escrituras de años cerrados y peticiones de pestañas con contexto antiguo. Formularios de ciclos, clases, responsables y matrícula por correo; nombres históricos congelados.
- «No matriculado» explícito, separado de pendiente/cero; no borra notas existentes y se excluye de medias. Publicaciones conservan la marca.
- Reinicio único preparado: vista previa, copia confirmada, mantenimiento en producción, validación de 50 identidades demo + administrador y transacción. La siembra de producción mantiene únicamente las 40/10 cuentas, sin recrear actividad académica.
- **138 pruebas / 1.179 aserciones correctas en SQLite**. Incluyen regresión completa, importación de correo con mayúsculas, histórico, permisos y reinicio con credenciales intactas. Las mismas 138 pruebas pasan en PostgreSQL (corregida únicamente la ordenación de la comparación de hashes en la prueba de reinicio). **11 escenarios Playwright correctos** (10 iniciales + repetición del alta corregida y móvil). Capturas revisadas: selector móvil sin solapamiento. Vue/TypeScript y Pint correctos.

## Pendiente
1. Código e interfaz verificados. Imágenes app/web construidas; falta ensayo del reinicio con imagen de producción en la base ficticia.
2. ops/deploy adaptado: copia comprobada, comparación de cuentas/credenciales antes/después y mantenimiento conservado ante fallo posterior a migraciones. Ensayar y publicar con limpieza autorizada.
3. Actualizar documentación existente y continuidad, retirar solo entorno propio de pruebas al acabar.
4. Google aplazado expresamente (redirect_uri_mismatch externo). Copias externas, carga y supervisión quedan para futuras tareas; no retomarlas ahora.

## Archivos relevantes
- Contexto: app/Domain/AcademicContext.php, middleware ResolveAcademicContext, AcademicContextController, routes/web.php.
- Backend: Setup/Challenge/Report/Import/RegistrationController; modelos Classroom/Module/User/Rubric/Enrollment/Cycle; Domain/Grades/{ChallengeWriter,Gradebook}.
- UI: Layout.vue, Setup/Dashboard/Challenge/Reports/Student.vue, lib.ts, app.ts, sidebar.css.
- Reinicio: app/Console/Commands/{ResetAcademicData,EnsureDemoData}.php; database/seeders/DatabaseSeeder.php; ops/{deploy,backup}.
- Pruebas: AcademicContext/AcademicWorkflow/ResetAcademicData/Gradebook y regresión de Setup/Enrollment/Import/GoogleRegistration; tests/Browser/workflows.spec.ts.
- Migraciones 2026_09_14_143913 y 145442; factories Classroom/Module/Challenge/Cycle/Enrollment. docs/decisiones.md contiene plan aprobado.
- Privados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrarlos ni versionarlos.
- Cambios ajenos: CLAUDE.md y docs/PROGRESS.md eliminados; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. Conservar sin incluir.

## Decisiones
- Arquitectura acordada: administrador crea/cierra/reabre años; docentes activos acceden a cualquier abierto. Primer acceso selecciona el abierto creado más recientemente; siguientes recuperan último año del usuario. Selector visible; histórico cerrado en solo lectura. Administración sin año para tareas globales; estudiantes acceden a años con matrícula.
- Clases anuales de un ciclo/nivel, propietario transferible por administrador; solo propietario/miembros ven sus datos. Sin permisos individuales; propietario/admin gestionan docentes y clase, miembros gestionan actividad. Responsables por módulo de la clase evalúan sus notas; generales/transversales compartidas. Varias matrículas por estudiante/año sustituyen la clase única. Sin arrastre de notas entre clases ni sincronización de retos evaluados/publicados.
- Catálogos ciclo/módulo solo administrativos; cuentas persistentes entre años, docentes crean estudiantes y buscan existentes por correo exacto. Rúbricas privadas compartidas solo para usar/copiar; copia congelada en retos. Nombres de ciclos/módulos congelados en histórico; Evaluaciones comunes por año.
- Matrícula de clase abarca todos sus módulos; no gestionar matrícula parcial. Usuario permite nota vacía o «No matriculado»: propuesta de marca explícita no numérica en evaluación, distinta de pendiente/cero, nunca inferida por ausencia de notas. Verificar consecuencias en cálculo/publicación.
- Reinicio inicial final: borrar actividad académica, años, módulos, rúbricas y cuentas docentes/estudiantiles no ficticias; conservar profesores/estudiantes de ejemplo con credenciales y acceso administrador. Sustituye la conservación inicial de todas las cuentas. No borrar configuración, servicios ni copias. Limpieza aún no ejecutada.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales docentes sin módulo; auto/coevaluación activas; retos ponderados por Evaluación y media de Evaluaciones por curso.
- Producción actual conserva 40/10 ficticios adicionales a manuales; ops/deploy mantiene datos demo idempotentes. El futuro plan debe revisar esa siembra para no recrear actividad académica tras el reinicio solicitado.
- VPS compartido: solo recursos propios. Web 127.0.0.1:8082, app/web backend + outbound, DB privada; API vecina 127.0.0.1:8000. No modificar gateway, paquetes, firewall ni Docker global.
- Navegador exclusivamente http://browser-app:8083 con marca testing; nunca pruebas de escritura en producción. Preview SQLite detenido: no arrancarlo en 8082.
- PHP/Composer en Docker; Git local sin remoto; .ai/rules no existe. Restauraciones en red interna y tmpfs sin correo, con versión/clave de la copia; Tinker requiere XDG_CONFIG_HOME temporal, no permisos globales.

## Último error / errores pendientes
Sin fallos pendientes en PHPUnit SQLite. Pint --dirty no funciona porque la imagen PHP carece de Git: usar lista explícita de PHP modificados. Skills de testing e Inertia leídas en plantillas vendor/laravel/boost; documentación Inertia v3 consultada por boost:execute-tool SearchDocs. .ai/rules no existe. Laravel 13.30.1, Inertia Laravel 3.3.3, PHPUnit 12.5.34, PHP 8.4.25.

## Siguiente acción concreta
Ensayar reinicio con imágenes locales en compose.test.yml (runtime), comprobar HTTP interno, etiquetar y desplegar con copia previa. No repetir auditorías del VPS, prompt ni pruebas sin cambios. Producción intacta: prevalidación de recuentos confirma 1 administrador, 40 estudiantes demo y 10 profesores demo, más 2 estudiantes no demo que se borrarán. PostgreSQL y navegador de pruebas activos. Actualizar progreso y hacer commits antes de publicar.
