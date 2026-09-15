# Estado de la tarea

## Objetivo
**Plan académico terminado y publicado en 878840e** en https://erronk2d.jonvadillo.com. Reinicio autorizado ejecutado y verificado; cierre de continuidad el 15/09/2026. No volver a ejecutar la limpieza ni repetir comprobaciones ya resueltas.

## Completado
- Base anual/contexto persistente en commit 22653e5. Controladores, permisos de clase/módulo, matrícula múltiple, catálogos estables y rúbricas privadas/compartidas publicados en 8d78888/878840e.
- Selector visible de curso, aislamiento transversal, bloqueo de escrituras de años cerrados y peticiones de pestañas con contexto antiguo. Formularios de ciclos, clases, responsables y matrícula por correo; nombres históricos congelados.
- «No matriculado» explícito, separado de pendiente/cero; no borra notas existentes y se excluye de medias. Publicaciones conservan la marca.
- Reinicio único ejecutado: vista previa, copia confirmada, mantenimiento en producción, validación de 50 identidades demo + administrador y transacción. La siembra de producción mantiene únicamente las 40/10 cuentas, sin recrear actividad académica.
- **138 pruebas / 1.179 aserciones correctas en SQLite**. Incluyen regresión completa, importación de correo con mayúsculas, histórico, permisos y reinicio con credenciales intactas. Las mismas 138 pruebas pasan en PostgreSQL (corregida únicamente la ordenación de la comparación de hashes en la prueba de reinicio). **11 escenarios Playwright correctos** (10 iniciales + repetición del alta corregida y móvil). Capturas revisadas: selector móvil sin solapamiento. Vue/TypeScript y Pint correctos.

- Producción en **878840e**: 1 administrador, 40 estudiantes y 10 profesores activos; 0 años, ciclos, clases, módulos, matrículas, rúbricas y retos. Una única marca de reinicio. Comparación antes/después confirma datos y credenciales de cuentas conservadas idénticos. Se retiraron los 2 estudiantes no ficticios.
- Copia previa privada `backups/production-20260914-878840e` de versión **38e25ce**, con base, almacenamiento y entorno. Índice PostgreSQL e integridad gzip comprobados; no confundir versión de la copia con etiqueta del directorio.
- Ensayo del reinicio con imagen de producción correcto; segunda ejecución inocua. Despliegue sustituye solo app/web; mantenimiento retirado. HTTPS login/CSS/JS 200, clases/ciclos/informes protegidos 302, API vecina 200. Entorno erronk2d-test retirado por completo; copias e imágenes anteriores conservadas.

## Pendiente
- **Sin tareas pendientes del plan académico.** El administrador ya puede configurar el primer curso académico, ciclos y módulos; después cada profesor crea sus clases, asigna responsables y matricula estudiantes. No crear datos académicos por iniciativa propia tras el reinicio.
- Google aplazado expresamente (`redirect_uri_mismatch` externo). Copias externas cifradas, carga y supervisión quedan para futuras tareas; no retomarlas sin petición.

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
- Matrícula de clase abarca todos sus módulos; no gestionar matrícula parcial. Usuario permite nota vacía o «No matriculado»: marca explícita no numérica implementada y verificada en cálculo/publicación, distinta de pendiente/cero, nunca inferida por ausencia de notas.
- Reinicio inicial final: borrar actividad académica, años, módulos, rúbricas y cuentas docentes/estudiantiles no ficticias; conservar profesores/estudiantes de ejemplo con credenciales y acceso administrador. Sustituye la conservación inicial de todas las cuentas. No borrar configuración, servicios ni copias. Limpieza ya ejecutada y protegida frente a repetición.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales docentes sin módulo; auto/coevaluación activas; retos ponderados por Evaluación y media de Evaluaciones por curso.
- Producción conserva 40/10 ficticios y acceso administrador; ops/deploy mantiene solo cuentas demo, sin recrear actividad académica.
- VPS compartido: solo recursos propios. Web 127.0.0.1:8082, app/web backend + outbound, DB privada; API vecina 127.0.0.1:8000. No modificar gateway, paquetes, firewall ni Docker global.
- Navegador exclusivamente http://browser-app:8083 con marca testing; nunca pruebas de escritura en producción. Preview SQLite detenido: no arrancarlo en 8082.
- PHP/Composer en Docker; Git local sin remoto; .ai/rules no existe. Restauraciones en red interna y tmpfs sin correo, con versión/clave de la copia; Tinker requiere XDG_CONFIG_HOME temporal, no permisos globales.

## Último error / errores pendientes
Sin fallos pendientes en la entrega académica. Pint --dirty no funciona porque la imagen PHP carece de Git: usar lista explícita de PHP modificados. Skills de testing e Inertia leídas en plantillas vendor/laravel/boost; documentación Inertia v3 consultada por boost:execute-tool SearchDocs. .ai/rules no existe. Laravel 13.30.1, Inertia Laravel 3.3.3, PHPUnit 12.5.34, PHP 8.4.25.

## Siguiente acción concreta
Plan terminado. Al recibir una nueva petición, consultar este estado y el archivo concreto afectado. No repetir auditorías del VPS, prompt, bootstrap, reinicio, correos ni OAuth. Mantener los cambios ajenos sin incluirlos en commits. PHP/Composer mediante ops/php; pruebas solo en entornos ficticios aislados.
