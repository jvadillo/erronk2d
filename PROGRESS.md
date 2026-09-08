# Estado de la tarea

## Objetivo
Publicar Erronk2D en https://erronk2d.jonvadillo.com en este VPS compartido, usando Resend API para el correo. Reanudar leyendo este archivo, `git status --short` y `git log -5 --oneline`; abrir solo los archivos necesarios para el siguiente paso. Actualizar este documento y hacer commits por avances verificables.

## Completado
- Aplicación Laravel 13.30.1/PHP 8.4, Inertia 3/Vue 3 implementada y demostración privada en 127.0.0.1:8082 (`erronk2d-preview`, SQLite ficticia).
- 34 pruebas PHP/273 aserciones en SQLite y PostgreSQL; 3 pruebas Playwright correctas. Imágenes app/web probadas con PostgreSQL aislado; entorno `erronk2d-test` retirado.
- Primer commit `7842bb4`; instrucciones de reanudación en AGENTS.md y este archivo.
- SDK Resend 1.13.0 instalado; `.env.production` privada creada con APP_KEY y contraseña DB nuevas. Dominio Resend creado (sin verificar): id `8ecf5fd6-892f-4fb5-89f1-0798c6b783bf`, región eu-west-1. Usuario avisado por pregunta asíncrona para añadir DNS de `docs/resend.md`; no hay acceso al panel GoDaddy desde aquí.
- Preparados Compose de producción, Nginx/PHP-FPM propios, sitio Caddy y copia de seguridad. No había commits; se crea ahora una base revisable.
- DNS comprobado 2026-09-08 11:07 UTC: A del subdominio = 2.28.118.113, sin AAAA ni CAA restrictivo. DNS administrado en domaincontrol.com. No hace falta cambiar A.

## Pendiente
1. Pruebas Resend/acceso: 7 pruebas correctas, HTTP simulado; correos en español, errores del proveedor controlados. SDK listo para desplegar. Esperar DNS de correo/verificar dominio; mientras tanto envío de prueba solo a jvadillo@egibide.org con onboarding@resend.dev. Clave únicamente en `.env.production`.
2. Producción creada: PostgreSQL `erronk2d-postgres-1`, migraciones aplicadas, volúmenes propios. Imágenes `erronk2d-app:f249689` y `erronk2d-web:f249689` construidas. Cuenta administradora jvadillo@egibide.org ya creada; no repetir bootstrap. Credenciales solo en `ops/production-credentials` (600/ignorado).
3. Demo `erronk2d-preview` detenida (conservada). Producción app/web/DB arrancada. Docker no publica puertos de contenedores conectados solo a red interna: corregido web a [backend, outbound], recreado y comprobado HTTP local 200.
4. Copia gateway ya creada: `backups/gateway-20260908T112052Z.tar.gz`. Candidato completo validado sin cambios en gateway activo. Añadido `sites/erronk2d.caddy`; Caddy validado y recargado sin reinicio; comprobar ahora HTTPS público, login y API existente y verificar HTTPS público y API existente.
5. Verificar correo real autorizado, dejar credenciales iniciales privadas, actualizar documentos/PROGRESS y hacer commit final.

## Archivos relevantes
- `prompt.md`: especificación original; `docs/decisiones.md`: aclaraciones funcionales aceptadas. No releer todo en cada sesión salvo cambio de alcance.
- `app/Domain/Grades/{Calculator,Gradebook,ChallengeWriter}.php`: cálculo, matriz y escrituras auditadas.
- `config/mail.php`, `config/services.php`, `app/Http/Controllers/AuthController.php`: correo y recuperación.
- `compose.production.yml`, `.env.production.example`, `ops/Dockerfile.production`, `ops/erronk2d.caddy`, `ops/backup`.
- `tests/Feature/AccessTest.php`, `tests/Feature/ImportTest.php`, `tests/Feature/GradebookTest.php`; `tests/Browser/workflows.spec.ts` (solo demo local).
- `docs/despliegue.md`, `docs/auditoria-vps.md`, `docs/estado-proyecto.md`, `README.md`.
- PHP/Composer SOLO en Docker: imagen `erronk2d-php:local`; wrapper `bash ops/php`. Pint `--dirty` necesita imagen `composer:2`, que sí contiene Git.
- Pruebas PostgreSQL: `docker compose -f compose.test.yml run --rm tests`; limpieza exclusivamente `docker compose -f compose.test.yml --profile runtime down`.

## Decisiones
- Autorizado por usuario el 2026-09-08: integrar Resend, guardar progreso, commits frecuentes y continuar hasta acceso público por subdominio. El DNS y la incorporación/recarga específica de Caddy están ahora autorizados dentro de ese despliegue.
- VPS compartido: no instalar/actualizar paquetes del host, no reiniciar Docker ni servicios ajenos, no tocar datos existentes/firewall ni limpiar recursos globalmente.
- Gateway `/home/deploy/projects/gateway`, Compose `web-gateway`, contenedor `web-gateway-caddy-1`, Caddy 2.11.4 red host 80/443. Importa `/etc/caddy/sites/*.caddy`. Sitio actual `athletes.caddy`: https://2.28.118.113 → 127.0.0.1:8000. API y PostgreSQL athletes deben seguir saludables.
- Producción Erronk2D: imágenes app/web, PostgreSQL propio, redes/volúmenes propios, 127.0.0.1:8082. Puerto ocupado por demo hasta sustitución; conservar su SQLite.
- Nota equipo → reparto opcional registrado por profesor → TODAS las defensas → ÚNICA nota final de reto compartida en el 40% de todos los módulos. Un examen y una defensa por estudiante/módulo/reto. Transversales del profesor por estudiante/criterio/reto SIN módulo; auto/coevaluación del alumnado siguen activas.
- Retos ponderados por Evaluación; media de Evaluaciones para curso; ausencias pendientes, no cero; publicaciones inmutables y reapertura motivada.
- Secretos/datos excluidos de Git y contexto: `.env`, `.env.production`, `ops/demo-credentials`, SQLite, `test-results/`. Commits locales; no remoto configurado.

## Último error
```text
Docker 29 no materializó PortBindings de web al tener solo red internal:true (NetworkSettings.Ports era null). RESUELTO: web conectado a backend + outbound, publicación exclusivamente en 127.0.0.1:8082 verificada HTTP 200. No modificar firewall ni Docker global.
```
