# Estado de la tarea

## Objetivo
Implementar acceso y registro con Google, manteniendo roles y matrícula. Producción aún en **4cd717d**. Preguntado al usuario por aprobación administrativa de nuevas solicitudes y disponibilidad de cliente OAuth. Propuesta: solicitud sin acceso hasta asignar rol/clase; vinculación de cuentas existentes mediante contraseña una vez. Sin dependencias nuevas; OAuth con cliente HTTP de Laravel. Actualizado 2026-09-09. Reanudar leyendo este archivo, git status y git log; no repetir auditoría ni pruebas previas.

## Completado
- Google implementado (todavía no desplegado): OAuth servidor con state/PKCE, correo verificado, vinculación inicial con contraseña y acceso posterior por identificador Google. Nuevas solicitudes sin acceso hasta aprobación administrativa; estudiantes requieren clase, profesores sin permisos iniciales. No se guardan tokens; Nginx propio omite parámetros de consulta de sus logs. Sin dependencias nuevas.
- Verificación Google: **55 pruebas / 437 aserciones** de Google/acceso/organización/matrícula/demo correctas en SQLite y PostgreSQL; **una prueba Playwright** correcta (errores de login, revisión, cambio de rol, clase obligatoria y aprobación). Compilación TypeScript/Vue y Pint correctos. No confundir con la suite completa; Google externo está simulado.
- Crear retos: errores de curso/módulos/rúbricas ahora aparecen en el formulario en español y conservan los datos. Selector deshabilita rúbricas incompatibles y limpia selecciones que dejan de ser válidas. No se conoce la selección exacta que provocó el error del usuario; se cubren todas las ramas que devolvían la pantalla 422.
- Corrección `4cd717d`: **7 pruebas nuevas / 47 aserciones** correctas tanto en SQLite como PostgreSQL; **una prueba nueva Playwright** verifica error conservando formulario y creación posterior válida. Compilación y Pint correctos.
- Producción: app/web **4cd717d**, web y PostgreSQL saludables, mantenimiento retirado, HTTPS login 200 y API vecina 200. Sin migraciones nuevas; despliegue conserva cuentas manuales y mantiene **40 estudiantes / 10 profesores ficticios activos**. Copia privada previa: `backups/production-20260909-4cd717d`.
- Correcciones anteriores publicadas: contraseña mínima 10; clase actual única y obligatoria al dar de alta/importar estudiantes; pestañas Profesor/Estudiante con clase; renombrado del curso; recuperación de participantes en retos vacíos y errores de equipos en español; CSRF correcto tras login.
- Base `504db0b` ya verificada: **54 pruebas / 420 aserciones** en SQLite y PostgreSQL, **5 pruebas Playwright**, recursos HTTPS y cookies Secure/HttpOnly. Son comprobaciones de la entrega anterior, no una repetición completa de la suite actual.
- Entorno temporal `erronk2d-test` retirado al acabar esta corrección. Imágenes y copias anteriores conservadas; vista previa SQLite detenida y conservada.
- Resend: dominio verificado y remitente definitivo configurado; envío anterior con remitente provisional entregado. No repetir correos al reanudar.
- Restauración completa de `production-20260909-4cd717d` verificada en `compose.restore.yml`: versión de la copia **504db0b** y clave original, PostgreSQL restaurado sin errores, **70 archivos SHA-256 idénticos**, cinco migraciones aplicadas, login y dos recursos HTTP 200. Cálculos de dos retos y tres informes de clase correctos en transacción de solo lectura. Copia con tres valoraciones, una nota de módulo y sin publicaciones (no valida publicaciones reales ausentes).
- Ensayo `erronk2d-restore` retirado: contenedores/red y datos en tmpfs eliminados; copia original intacta. HTTPS y API vecina 200 al acabar. Procedimiento reutilizable en `docs/despliegue.md`; configuración privada y manifiesto bajo `backups/restore-check-20260909-4cd717d/`, no imprimir ni versionar.

## Pendiente
1. Usuario: volver a crear el reto tras recargar la página; seleccionar módulos antes de elegir rúbrica. Si hay incompatibilidad, resolver el mensaje del formulario.
2. Acordar destino externo cifrado, frecuencia y retención de copias. Pregunta asíncrona enviada al usuario; no se ha elegido destino ni configurado transferencias. La restauración completa ya está verificada.
3. Medir carga concurrente y concretar supervisión de TLS/espacio. Verificar entrega del remitente definitivo en la siguiente recuperación solicitada, sin envíos adicionales por iniciativa propia.
4. Estudiantes antiguos sin clase: asignación expresa en Organización → Estudiante. Reto vacío: Gestionar equipos → Incorporar estudiantes de la clase. No inventar matrículas.
5. Exportación XLSX/PDF generado en servidor son ampliaciones futuras. Limpiar cuentas ficticias solo cuando lo solicite el usuario.

## Archivos relevantes y modificados
- Google: controladores GoogleAuth/Registration/Auth/Setup; modelos User/GoogleRegistration; migración `2026_09_09_201254_add_google_authentication.php`; factoría GoogleRegistration y seeder local; Login.vue, Setup.vue y RegistrationRequests.vue; rutas, config/services.php, .env.example, ops/nginx.conf; pruebas GoogleAuth/GoogleRegistration y Browser/workflows; documentación de despliegue/decisiones/estado y PROGRESS.
- Corrección: `app/Http/Controllers/ChallengeController.php`, `resources/js/pages/Dashboard.vue`, `tests/Feature/ChallengeCreationTest.php`, `tests/Browser/workflows.spec.ts`; commit `4cd717d`.
- Modificados en este bloque: nuevo `compose.restore.yml`, `PROGRESS.md`, `docs/despliegue.md`, `docs/estado-proyecto.md`. Continuidad de la corrección publicada: `1cc29ca`. Existe eliminación ajena **sin incluir en nuestros commits** de `docs/PROGRESS.md`; no recrearlo. Último commit: consultar git log.
- Operación: `ops/{deploy,backup,Dockerfile.production}`, `compose.production.yml`, `compose.test.yml`; procedimiento de pruebas en README.
- Dominio: `app/Domain/Grades/{ChallengeWriter,Gradebook}.php`; controladores Setup/Challenge/Import/Auth; modelos User/Classroom; comando `EnsureDemoData.php`.
- Matrícula/demo: migración `2026_09_08_204238_add_current_classroom_and_demo_identifiers.php`; FK nullable para legado y demo_key únicos.
- Fuentes funcionales: `prompt.md`, `docs/decisiones.md`, `docs/plan-correcciones.md`; leer solo puntos relevantes cuando haya dudas.
- Privados/ignorados: `.env.production`, `ops/production-credentials`, `backups/`, `test-results/`. Nunca imprimir ni versionar secretos o datos personales.

## Decisiones
- Una clase actual por estudiante; conservar participantes/notas históricos. No sincronizar automáticamente retos evaluados/publicados.
- 40/10 son cuentas FICTICIAS adicionales a manuales/administradoras. `ops/deploy` ejecuta `erronk2d:demo` idempotente: conserva contraseñas/asignaciones/datos editados. El administrador puede cambiar sus contraseñas; sin correos a direcciones .test.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales docentes por estudiante/criterio/reto, sin módulo; auto/coevaluación activas. Retos ponderados por Evaluación y media de Evaluaciones para el curso.
- VPS compartido: no modificar gateway, paquetes, Docker global, firewall ni servicios ajenos. Web solo 127.0.0.1:8082; app/web backend + outbound y DB privada. API athletes 127.0.0.1:8000; gateway `web-gateway-caddy-1`.
- Navegador exclusivamente `http://browser-app:8083`, red interna y marca testing; nunca producción. PHP/Composer en Docker; Git local sin remoto; `.ai/rules` no existe.
- Restauración: proyecto `erronk2d-restore`, red interna sin puertos, datos en tmpfs y correo array; usar versión/clave del archivo privado de la copia, no el nombre del directorio. Tinker requiere `-e XDG_CONFIG_HOME=/tmp/erronk2d-psysh` en esta imagen; no cambiar permisos globales.
- Mantener este archivo actualizado durante el trabajo y en commits de avance; prevalece la petición reciente de actualización continua.

## Último error / errores pendientes
Pantalla 422 corregida y publicada; falta confirmar el caso concreto del usuario. Ensayo de restauración sin errores pendientes. Aviso de permisos de Tinker resuelto con directorio temporal interno; no requiere modificar la imagen ni el VPS.

## Siguiente acción concreta
Guardar el commit Google verificado, construir imágenes y desplegar con copia nueva, manteniendo Google desactivado hasta configurar credenciales privadas. Después comprobar HTTPS y retirar el entorno de pruebas. Activación y prueba real requieren GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET; retorno /auth/google/callback. Consulta de política de registro y credenciales aún sin respuesta; propuesta aplicada: aprobación administrativa.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker compose --env-file .env.production -f compose.production.yml ps
docker compose --env-file backups/restore-check-20260909-4cd717d/runtime.env -f compose.restore.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si nuevos cambios justifican repetir pruebas:
bash ops/php vendor/bin/phpunit tests/Feature/ChallengeCreationTest.php
docker compose -f compose.test.yml run --rm tests php vendor/bin/phpunit tests/Feature/ChallengeCreationTest.php
docker compose -f compose.test.yml --profile browser --profile runtime down
```
No repetir bootstrap, migraciones, carga ficticia o envíos para recuperar contexto. Nuevas entregas: imágenes versionadas verificadas y `bash ops/deploy VERSION backups/FECHA-VERSION` con directorio nuevo.
