# Estado de la tarea

## Objetivo
**Navegación terminada y publicada** en https://erronk2d.jonvadillo.com, imágenes app/web **38e25ce**, actualizado 2026-09-10. Google **aplazado expresamente por el usuario**. Reanudar con este archivo, git status y git log; no repetir auditoría, prompt completo ni pruebas ya verificadas.

## Completado
- Lateral plegable de 252 a 80 px, iconos y preferencia persistente en el navegador. Organización tiene submenú y páginas propias: Cursos académicos, Clases, Profesor, Estudiante, Módulos, Biblioteca de rúbricas y Solicitudes (solo administración). URL, título e historial propios; móvil y teclado verificados.
- Código en **38e25ce**. 18 pruebas nuevas / 216 aserciones correctas en SQLite y PostgreSQL; 29 pruebas existentes afectadas también correctas en ambos. **9 pruebas Playwright correctas**, capturas revisadas, TypeScript/Vue y Pint correctos. Entorno erronk2d-test retirado.
- Despliegue terminado: copia privada `backups/production-20260910-38e25ce` (versión anterior 736925b); sin nuevas migraciones, mantenimiento retirado. HTTPS login y ambos recursos compilados 200; siete páginas protegidas redirigen al login; API vecina 200. Solo contenedores propios app/web actualizados.
- Conservados **40 estudiantes y 10 profesores ficticios activos**, además de cuentas manuales. Correcciones anteriores publicadas: contraseña mínima 10, clase única obligatoria, renombrar curso, recuperación de participantes/equipos, errores de creación de retos en formulario y CSRF tras login.
- Google implementado en 736925b: vinculación confirmando contraseña, registro con aprobación administrativa y clase obligatoria para estudiantes, state de un uso y PKCE, sin guardar tokens. Configuración privada conservada y habilitada; validación real pendiente y aplazada. Pruebas históricas y detalles en docs/estado-proyecto.md.
- Resend configurado, dominio verificado y remitente definitivo aplicado; envío anterior entregado. No repetir correos para recuperar contexto.
- Restauración completa de copia 4cd717d ensayada con versión original 504db0b: PostgreSQL, 70 archivos SHA-256, login/recursos y cálculos. No contenía publicaciones reales. Ensayo retirado; copias e imágenes anteriores conservadas.

## Pendiente
1. **Google aplazado**: solo cuando el usuario lo retome, autorizar `https://erronk2d.jonvadillo.com/auth/google/callback` en Google Cloud y completar acceso real. No repetir preguntas ni llamadas OAuth ahora. Política conservadora actual: aprobación administrativa, sin elección pública de rol/clase.
2. Elegir con el usuario destino externo cifrado, frecuencia y retención de copias; restauración ya ensayada.
3. Medir carga concurrente en entorno ficticio aislado y concretar supervisión de TLS/espacio. Comprobar remitente definitivo en la próxima recuperación solicitada, sin envíos adicionales.
4. Estudiantes antiguos sin clase requieren asignación expresa. XLSX/PDF generado en servidor son ampliaciones futuras; limpieza ficticia solo por petición.

## Archivos relevantes y modificados
- Navegación 38e25ce: app/Http/Controllers/SetupController.php, app/Http/Middleware/HandleInertiaRequests.php, routes/web.php, resources/js/components/Layout.vue, resources/js/pages/{Setup,Challenge}.vue, resources/js/app.ts, resources/css/{app,sidebar}.css.
- Pruebas: tests/Feature/OrganizationNavigationTest.php, GoogleRegistrationTest.php (rutas), tests/Browser/workflows.spec.ts. Capturas privadas en test-results/sidebar-{expanded,collapsed,mobile}.png.
- Documentación actualizada: PROGRESS.md, README.md, docs/{despliegue,decisiones,estado-proyecto}.md. Eliminación ajena **docs/PROGRESS.md** conservada sin recrear ni incluir en commits.
- Google: controladores GoogleAuth/Registration/Auth, modelos User/GoogleRegistration, config/services.php, migración 2026_09_09_201254_add_google_authentication.php, Login.vue y RegistrationRequests.vue; tests/Feature/GoogleAuthTest.php.
- Dominio: app/Domain/Grades/{ChallengeWriter,Gradebook}.php; fuentes prompt.md y docs/{decisiones,plan-correcciones}.md, leer solo puntos pertinentes.
- Operación: ops/{deploy,backup,Dockerfile.production}, compose.production.yml, compose.test.yml, compose.restore.yml.
- Privados/ignorados: .env.production (600), ops/production-credentials, backups/, test-results/. Nunca mostrar o versionar secretos, tokens ni datos personales.

## Decisiones
- Una clase actual por estudiante; participantes/notas históricos conservados. No sincronizar retos evaluados/publicados.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales docentes sin módulo; auto/coevaluación activas; retos ponderados por Evaluación y media de Evaluaciones por curso.
- 40/10 ficticios adicionales a manuales; ops/deploy mantiene datos demo de forma idempotente sin sobrescribir contraseñas/notas ni enviar correos a .test.
- VPS compartido: solo recursos propios. Web 127.0.0.1:8082, app/web backend + outbound, DB privada; API vecina 127.0.0.1:8000. No modificar gateway, paquetes, firewall ni Docker global.
- Navegador exclusivamente http://browser-app:8083 con marca testing; nunca pruebas de escritura en producción. Preview SQLite detenido: no arrancarlo en 8082.
- PHP/Composer en Docker; Git local sin remoto; .ai/rules no existe. Restauraciones en red interna y tmpfs sin correo, con versión/clave de la copia; Tinker requiere XDG_CONFIG_HOME temporal, no permisos globales.

## Último error / errores pendientes
Sin errores pendientes conocidos en la navegación entregada. Google conserva **redirect_uri_mismatch**: falta registrar el retorno en el cliente externo; Client ID/Secret no permiten modificarlo. Aplazado por petición. Acceso con contraseña operativo.

## Siguiente acción concreta
La petición de navegación está cerrada. Revisar comentarios del usuario sobre la interfaz publicada; si pide continuar con operación, preparar medición de carga aislada y acordar destino/retención de copias externas. No retomar Google hasta petición expresa ni repetir el despliegue 38e25ce.

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
