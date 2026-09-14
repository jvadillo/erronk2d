# Estado de la tarea

## Objetivo
**Nueva arquitectura en implementación, autorizada el 2026-09-14.** Primer bloque de esquema/contexto preparado; falta conectar el ámbito a operaciones e interfaz. Plan en docs/decisiones.md. No desplegado ni reiniciado.

**Navegación terminada y publicada** en https://erronk2d.jonvadillo.com, imágenes app/web **38e25ce**, actualizado 2026-09-10. Google **aplazado expresamente por el usuario**. Reanudar con este archivo, git status y git log; no repetir auditoría, prompt completo ni pruebas ya verificadas.

## Completado
- Base académica: migración aditiva de ciclos/matrículas, propietario de clase, módulos por clase/responsables, rúbricas compartidas y preferencia de año. AcademicContext resuelve disponibilidad, recuerda año y comprueba contexto de escritura; middleware y ruta de cambio registrados. **8 pruebas / 28 aserciones pasan en SQLite y PostgreSQL**. Esquema antiguo conservado temporalmente hasta adaptar controladores; todavía no hay aislamiento completo en la aplicación.
- Registradas las 16 respuestas y las 3 aclaraciones finales de arquitectura del 14 de septiembre; plan de 7 bloques en docs/decisiones.md. Sin cambios de aplicación, datos o producción; no se han repetido pruebas.
- Lateral plegable de 252 a 80 px, iconos y preferencia persistente en el navegador. Organización tiene submenú y páginas propias: Cursos académicos, Clases, Profesor, Estudiante, Módulos, Biblioteca de rúbricas y Solicitudes (solo administración). URL, título e historial propios; móvil y teclado verificados.
- Código en **38e25ce**. 18 pruebas nuevas / 216 aserciones correctas en SQLite y PostgreSQL; 29 pruebas existentes afectadas también correctas en ambos. **9 pruebas Playwright correctas**, capturas revisadas, TypeScript/Vue y Pint correctos. Entorno erronk2d-test retirado.
- Despliegue terminado: copia privada `backups/production-20260910-38e25ce` (versión anterior 736925b); sin nuevas migraciones, mantenimiento retirado. HTTPS login y ambos recursos compilados 200; siete páginas protegidas redirigen al login; API vecina 200. Solo contenedores propios app/web actualizados.
- Conservados **40 estudiantes y 10 profesores ficticios activos**, además de cuentas manuales. Correcciones anteriores publicadas: contraseña mínima 10, clase única obligatoria, renombrar curso, recuperación de participantes/equipos, errores de creación de retos en formulario y CSRF tras login.
- Google implementado en 736925b: vinculación confirmando contraseña, registro con aprobación administrativa y clase obligatoria para estudiantes, state de un uso y PKCE, sin guardar tokens. Configuración privada conservada y habilitada; validación real pendiente y aplazada. Pruebas históricas y detalles en docs/estado-proyecto.md.
- Resend configurado, dominio verificado y remitente definitivo aplicado; envío anterior entregado. No repetir correos para recuperar contexto.
- Restauración completa de copia 4cd717d ensayada con versión original 504db0b: PostgreSQL, 70 archivos SHA-256, login/recursos y cálculos. No contenía publicaciones reales. Ensayo retirado; copias e imágenes anteriores conservadas.

## Pendiente
0. Implementar el plan académico de docs/decisiones.md una vez concluida su presentación: modelo/contexto, permisos/organización, retos/rúbricas/notas, interfaz/regresión y reinicio/despliegue. Aclaraciones funcionales cerradas; no volver a preguntar lo ya acordado.
1. **Google aplazado**: solo cuando el usuario lo retome, autorizar `https://erronk2d.jonvadillo.com/auth/google/callback` en Google Cloud y completar acceso real. No repetir preguntas ni llamadas OAuth ahora. Política conservadora actual: aprobación administrativa, sin elección pública de rol/clase.
2. Elegir con el usuario destino externo cifrado, frecuencia y retención de copias; restauración ya ensayada.
3. Medir carga concurrente en entorno ficticio aislado y concretar supervisión de TLS/espacio. Comprobar remitente definitivo en la próxima recuperación solicitada, sin envíos adicionales.
4. XLSX/PDF generado en servidor son ampliaciones futuras. Reinicio completo autorizado como parte de la transición: conservar cuentas de ejemplo y acceso administrador; preparar/ensayar limpieza y copia previa con la nueva versión antes de ejecutarlo.

## Archivos relevantes y modificados
- Bloque académico: app/Domain/AcademicContext.php, app/Http/{Middleware/ResolveAcademicContext,Controllers/AcademicContextController}.php, modelos AcademicYear/Classroom/Cycle/Enrollment, factories correspondientes, migración 2026_09_14_143913_add_academic_context.php, tests/Feature/AcademicContextTest.php, bootstrap/app.php y routes/web.php.
- Navegación 38e25ce: app/Http/Controllers/SetupController.php, app/Http/Middleware/HandleInertiaRequests.php, routes/web.php, resources/js/components/Layout.vue, resources/js/pages/{Setup,Challenge}.vue, resources/js/app.ts, resources/css/{app,sidebar}.css.
- Pruebas: tests/Feature/OrganizationNavigationTest.php, GoogleRegistrationTest.php (rutas), tests/Browser/workflows.spec.ts. Capturas privadas en test-results/sidebar-{expanded,collapsed,mobile}.png.
- Documentación actualizada: PROGRESS.md, README.md, docs/{despliegue,decisiones,estado-proyecto}.md. Eliminación ajena **docs/PROGRESS.md** conservada sin recrear ni incluir en commits.
- Google: controladores GoogleAuth/Registration/Auth, modelos User/GoogleRegistration, config/services.php, migración 2026_09_09_201254_add_google_authentication.php, Login.vue y RegistrationRequests.vue; tests/Feature/GoogleAuthTest.php.
- Dominio: app/Domain/Grades/{ChallengeWriter,Gradebook}.php; fuentes prompt.md y docs/{decisiones,plan-correcciones}.md, leer solo puntos pertinentes.
- Operación: ops/{deploy,backup,Dockerfile.production}, compose.production.yml, compose.test.yml, compose.restore.yml.
- Privados/ignorados: .env.production (600), ops/production-credentials, backups/, test-results/. Nunca mostrar o versionar secretos, tokens ni datos personales.

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
Pint --dirty no funciona en imagen PHP sin Git; aplicar Pint a los archivos modificados explícitos (ya realizado en primer bloque). SearchDocs disponible vía Artisan boost:execute-tool; contenedor habitual sin red, consulta realizada correctamente en erronk2d-docs con red bridge. Versiones comprobadas: Laravel 13.30.1, Inertia Laravel 3.3.3, PHPUnit 12.5.34, PHP 8.4.25. Skills leídas en vendor/laravel/boost/.ai/laravel/skill/testing-best-practices y .ai/inertia-vue/2/skill/inertia-vue-development (plantillas); prevalece documentación v3 consultada.
Sin errores pendientes conocidos en la navegación entregada. Google conserva **redirect_uri_mismatch**: falta registrar el retorno en el cliente externo; Client ID/Secret no permiten modificarlo. Aplazado por petición. Acceso con contraseña operativo.

## Siguiente acción concreta
Continuar implementación autorizada: conectar relaciones nuevas y permisos de clase/módulo, adaptar Setup/retos/informes/importación y pruebas existentes; después Vue, evaluación No matriculado y reinicio. PostgreSQL erronk2d-test activo (solo pruebas ficticias), sin navegador aún. No repetir 8 pruebas iniciales salvo cambios. Cambios ajenos conservados: eliminados CLAUDE.md y docs/PROGRESS.md; LARAVEL_BOOST_GUIDELINES.md sin seguimiento. No retomar Google ni desplegar bloque incompleto.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si cambia la navegación:
bash ops/php vendor/bin/phpunit tests/Feature/OrganizationNavigationTest.php
docker compose -f compose.test.yml run --rm tests php vendor/bin/phpunit tests/Feature/OrganizationNavigationTest.php
# Retirar solo el entorno de pruebas al acabar:
docker compose -f compose.test.yml --profile browser --profile runtime down
```
No imprimir cabeceras completas de /auth/google (contienen state). Entregas nuevas: imágenes versionadas, `bash ops/deploy VERSION backups/FECHA-VERSION`, copia en directorio nuevo. No repetir bootstrap, migraciones ni envíos para recuperar contexto.
