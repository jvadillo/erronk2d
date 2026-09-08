# Estado de la tarea

## Objetivo
Implementar las seis correcciones planificadas en `docs/plan-correcciones.md`, preservando los datos y el aislamiento del VPS. En esta sesión se pidió PLANIFICAR: las correcciones aún no están implementadas. Reanudar con este archivo, `git status --short` y `git log -5 --oneline`; leer después solo los archivos del siguiente paso.

## Completado
- Producción accesible en https://erronk2d.jonvadillo.com. Última comprobación 2026-09-08: login HTTPS 200 y API vecina 200. PostgreSQL propio; administrador inicial creado, no repetir bootstrap.
- Laravel 13.30.1/PHP 8.4, Inertia 3/Vue 3. Imágenes app/web `f249689`; configuración de red corregida en `9625eee`. Demo SQLite detenida y conservada; 8082 ahora pertenece a producción.
- Resend SDK 1.13.0 integrado, recuperación en español y errores controlados. Dominio y tres registros DNS verificados; remitente definitivo `no-reply@erronk2d.jonvadillo.com` aplicado recreando solo app. Envío anterior con remitente provisional confirmado `delivered`; no repetir envíos al reanudar.
- Copia local privada de producción: `backups/production-20260908-resend`. Copia anterior del gateway: `backups/gateway-20260908T112052Z.tar.gz`. Restauración todavía no ensayada.
- Diagnóstico de equipos confirmado mediante código y recuentos: clase sin matrícula, reto sin participantes ni equipos. El alta no asigna clase; el reto copia la matrícula al crearse; después la edición de matrícula queda bloqueada.
- Plan con prioridades, migración segura y criterios de aceptación guardado en commit `c4c8a80`. Documentación de producción actualizada y `git diff --cached --check` correcto. No hubo cambios PHP/Vue ni se repitieron sus pruebas.
- Validación anterior: 34 pruebas PHP/273 aserciones en SQLite y PostgreSQL, 3 pruebas de navegador sobre demo. Después, 7 pruebas de correo/acceso/57 aserciones correctas; no confundir con una ejecución nueva de toda la suite.

## Pendiente
1. Clase obligatoria y única del estudiante en alta, edición e importación; migrar asociaciones existentes sin inventar matrículas ni perder histórico.
2. Reparar explícitamente participantes de retos vacíos sin evaluaciones/publicaciones; permitir corregir matrícula actual; errores de equipos claros en español, validación 2–5 y conservación de selecciones.
3. Contraseñas mínimas de 10 caracteres; renombrar curso conservando Evaluaciones e identificadores cuando estén bloqueadas.
4. Sustituir Personas por Profesor y Estudiante; mostrar y editar clase del estudiante.
5. Comando repetible para mantener 40 estudiantes y 10 profesores FICTICIOS activos tras cada despliegue, además de las cuentas manuales. No están cargados aún. No ejecutar el seeder actual en producción.
6. Aislar navegador de producción antes de ejecutar su suite: actualmente apunta a 8082. Pruebas, imágenes versionadas, copia, migración propia y despliegue por bloques.
7. Comprobar entrega con remitente definitivo en la siguiente recuperación solicitada; ensayar restauración aislada; acordar copias externas cifradas/retención; medir carga y supervisar TLS/espacio. Exportación XLSX/PDF de servidor quedan como ampliaciones.

## Archivos relevantes y modificados
- Modificados en esta sesión: `README.md`, `docs/despliegue.md`, `docs/estado-proyecto.md`, `docs/resend.md`; creado `docs/plan-correcciones.md`. Commit `c4c8a80`.
- Modificado privado/ignorado: `.env.production`, solo remitente; copia nueva en `backups/`. No incluir secretos ni copias en Git.
- `PROGRESS.md`: esta actualización es el último cambio solicitado; queda sin commit para no efectuar modificaciones posteriores. Incluirla en el siguiente avance autorizado.
- Siguiente bloque: `app/Http/Controllers/{SetupController,ChallengeController,ImportController}.php`, `app/Domain/Grades/ChallengeWriter.php`, `app/Models/{User,Classroom,Challenge}.php`, `database/migrations/2026_09_07_000001_create_academic_domain.php` (referencia; crear migración nueva).
- Interfaz: `resources/js/pages/{Setup,Challenge}.vue`; pruebas: `tests/Feature/{SetupTest,ImportTest,GradebookTest,AccessTest,ResendMailTest}.php`, `tests/Browser/workflows.spec.ts`.
- Operación: `compose.production.yml`, `compose.test.yml`, `ops/{php,backup,Dockerfile.production,erronk2d.caddy}`. Credenciales iniciales solo en `ops/production-credentials` (600/ignorado), no imprimir.
- Fuente funcional: `prompt.md` y `docs/decisiones.md`; nuevas aclaraciones/plan en `docs/plan-correcciones.md`. `.ai/rules` no existe en esta revisión; comprobar instrucciones aplicables antes de editar código. PHP/Composer se ejecutan en Docker, no en el host.

## Decisiones
- Una clase ACTUAL por estudiante; propuesta: referencia única con clave foránea, conservando participantes históricos de retos. Profesorado puede pertenecer a varias clases. Resolver matrículas ambiguas expresamente.
- Reponer solo cuentas ficticias identificadas, sin duplicarlas, reiniciar contraseñas ni sobrescribir notas. Propuesta: dos clases ficticias de 20; administradores y cuentas manuales no cuentan en 40/10. Limpieza solo cuando el usuario la solicite.
- Unificar mínimo de 10 también en cambio/recuperación de contraseña para mantener coherencia. Renombrar curso no debe recrear periodos.
- Nota equipo → reparto opcional registrado por profesor → TODAS las defensas → ÚNICA nota final del reto usada en el 40% de TODOS los módulos. Un examen/defensa por estudiante/módulo/reto; transversales del profesor por estudiante/criterio/reto SIN módulo; auto/coevaluación activas.
- Retos ponderados por Evaluación, curso media de Evaluaciones; ausencias pendientes, no cero; publicaciones inmutables y reapertura motivada.
- VPS compartido: no tocar servicios ajenos, firewall ni paquetes globales. Gateway compartido `/home/deploy/projects/gateway`, contenedor `web-gateway-caddy-1`; API athletes en 127.0.0.1:8000. Erronk2D web solo 127.0.0.1:8082, DB privada, redes/volúmenes propios.
- Web necesita redes backend + outbound para publicar loopback en este Docker; problema ya resuelto, no repetir auditoría ni cambiar Docker global. Git local sin remoto.

## Último error / errores pendientes
`The teams.0.students field is required` (y equipo 2): listas vacías porque el reto no tiene participantes; falta corregir matrícula y proporcionar recuperación explícita del reto vacío. No se ha corregido aún. Sigue bloqueado el renombrado de cursos con retos y siguen vigentes las demás limitaciones enumeradas en Pendiente.

## Siguiente acción concreta
Reproducir en una prueba aislada el flujo «alta sin matrícula → reto vacío → equipos sin integrantes». Después implementar clase actual única y la reparación explícita del reto vacío, con las protecciones históricas del plan. No ejecutar escrituras de prueba sobre producción.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
git diff --check
# Regresión del próximo bloque; estas pruebas existentes aún no cubren todas las correcciones:
bash ops/php vendor/bin/phpunit tests/Feature/SetupTest.php tests/Feature/ImportTest.php tests/Feature/GradebookTest.php
# PostgreSQL separado; ejecutar cuando corresponda validar la migración:
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml --profile runtime down
# Lecturas de producción:
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
```
No ejecutar Playwright hasta separar su destino del puerto 8082 de producción. No repetir bootstrap, seeder local, envío de correo, migraciones ni pruebas ya verificadas solo para recuperar contexto.
