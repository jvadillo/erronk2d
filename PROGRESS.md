# Estado de la tarea

## Objetivo
Implementar y publicar menú lateral plegable a iconos y páginas independientes de Organización con submenú. El usuario **aplaza Google expresamente**: conservar integración y error de URI para retomarlos, sin seguir verificando OAuth ahora. Producción en 736925b. Actualizado 2026-09-10. Rutas y sección por URL en implementación; falta lateral, pruebas y despliegue. Reanudar con este archivo, git status y git log; no repetir auditoría ni pruebas previas.

## Completado
- Navegación en desarrollo: lateral plegable con preferencia local, submenú e iconos; rutas /setup/{courses,classrooms,teachers,students,modules,rubrics,registrations}, permisos conservados y /setup redirige a cursos. Cursos y Clases separados; páginas conservan URL y título. Google sigue aplazado.
- Comprobación actual: 29 pruebas existentes afectadas correctas en SQLite/PostgreSQL; 18 nuevas pruebas de navegación / 216 aserciones correctas en ambos tras corregir 405 por 404 en URL desconocida. Compilación correcta. Navegador completo en ejecución; no desplegado aún.
- Producción: imágenes app/web **736925b**, migración Google aplicada, mantenimiento retirado, HTTPS login 200 y API vecina 200. Copia previa privada: `backups/production-20260909-736925b`. Mantiene **40 estudiantes / 10 profesores ficticios activos** y conserva cuentas manuales.
- Google: OAuth de servidor con state de un solo uso, caducidad y PKCE S256; correo verificado. Cuentas existentes confirman una vez la contraseña local; posteriores accesos por identificador Google. No modifica roles, permisos, correo ni historial; cuentas desactivadas bloqueadas.
- Registro Google: solicitud sin acceso académico; administrador revisa en Organización → Solicitudes. Aprueba Profesor o Estudiante (clase obligatoria); profesores sin permisos iniciales de edición. Rechazos no se reabren automáticamente. Cambiar correo desde Organización elimina la vinculación Google anterior.
- Credenciales OAuth proporcionadas por el usuario, guardadas solo en .env.production (600), GOOGLE_ENABLED=true. No imprimirlas. Nginx propio omite parámetros de consulta en access logs; no se guardan tokens OAuth. Sin dependencias nuevas ni cambios en Caddy/servicios ajenos.
- **55 pruebas / 437 aserciones** de Google/acceso/organización/matrícula/demo correctas en SQLite y PostgreSQL; **una prueba Playwright** correcta (errores de login, revisión, rol, clase y aprobación). Compilación, Pint, rutas y sintaxis de Nginx correctos. Google simulado en pruebas; no repetirlas sin motivo.
- Comprobación pública: props de login habilitan Google; redirección 302 a accounts.google.com con retorno correcto y PKCE; Google devuelve **redirect_uri_mismatch**. Sesión de comprobación cancelada sin crear cuentas/solicitudes. Falta autorizar URI en Google Cloud y prueba real con usuario.
- Entorno erronk2d-test retirado después de probar Google. Copias e imágenes anteriores conservadas.
- Correcciones anteriores publicadas: 422 al crear retos → errores en formulario y rúbricas compatibles; contraseña mínima 10; clase actual única y obligatoria; pestañas Profesor/Estudiante; renombrado de curso; recuperación de participantes en retos vacíos y equipos; CSRF tras login. Validaciones históricas: 4cd717d (7/47 + navegador) y 504db0b (54/420 + 5 navegador), no confundir con suite actual.
- Restauración completa de copia `production-20260909-4cd717d` verificada con versión original 504db0b: base, 70 archivos SHA-256 idénticos, login/recursos y cálculos. Sin publicaciones en esa copia. Ensayo erronk2d-restore retirado; procedimiento en docs/despliegue.md.
- Resend: dominio verificado y remitente definitivo configurado; envío anterior con remitente provisional entregado. No repetir correos al reanudar.

## Pendiente
1. **Google aplazado por el usuario**. Al retomarlo: autorizar `https://erronk2d.jonvadillo.com/auth/google/callback` en Google Cloud, comprobar redirección y acceso real. Credenciales privadas conservadas; no repetir preguntas ni llamadas OAuth mientras siga aplazado.
2. Política conservadora de registro aplicada mientras se espera respuesta: aprobación administrativa; no acceso directo ni elección pública de rol/clase.
3. Destino externo cifrado, frecuencia y retención de copias pendientes de elección del usuario; restauración ya ensayada.
4. Medir carga concurrente en entorno ficticio aislado y concretar supervisión de TLS/espacio. Verificar remitente definitivo en próxima recuperación solicitada, sin envíos adicionales.
5. Estudiantes antiguos sin clase requieren asignación expresa; no inventar matrículas. Exportación XLSX/PDF en servidor son ampliaciones futuras; limpieza ficticia solo por petición.

## Archivos relevantes y modificados
- Navegación actual: SetupController, HandleInertiaRequests, routes/web.php, Layout.vue, Setup.vue, Challenge.vue, app.ts, resources/css/{app,sidebar}.css; OrganizationNavigationTest, GoogleRegistrationTest (solo rutas), Browser/workflows y PROGRESS.
- Google (commit **736925b**): controladores GoogleAuth/Registration/Auth/Setup, modelos User/GoogleRegistration, migración `2026_09_09_201254_add_google_authentication.php`, factoría GoogleRegistration y seeder local, config/services.php, .env.example, routes/web.php, ops/nginx.conf.
- Interfaz: `resources/js/pages/{Login,Setup}.vue`, `resources/js/components/RegistrationRequests.vue`. Pruebas: `tests/Feature/{GoogleAuth,GoogleRegistration}Test.php` y Browser/workflows.spec.ts.
- Documentación modificada: PROGRESS.md, README.md, docs/{despliegue,decisiones,estado-proyecto}.md. Eliminación ajena **docs/PROGRESS.md** conservada sin recrearla ni incluirla en commits. Último commit documental: git log.
- Operación: ops/{deploy,backup,Dockerfile.production}, compose.production.yml, compose.test.yml, compose.restore.yml.
- Dominio: app/Domain/Grades/{ChallengeWriter,Gradebook}.php; fuentes prompt.md y docs/{decisiones,plan-correcciones}.md, leer solo puntos relevantes.
- Privados/ignorados: .env.production, ops/production-credentials, backups/, test-results/. Nunca mostrar o versionar secretos, tokens ni datos personales.

## Decisiones
- Una clase actual por estudiante y participantes/notas históricos conservados; no sincronizar retos evaluados/publicados.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales docentes sin módulo, auto/coevaluación activas; retos ponderados por Evaluación y media de Evaluaciones por curso.
- 40/10 ficticios adicionales a manuales; ops/deploy mantiene datos demo de forma idempotente, sin sobrescribir contraseñas/notas ni enviar correos a .test.
- VPS compartido: solo recursos propios. Web 127.0.0.1:8082, app/web backend + outbound, DB privada; API vecina 127.0.0.1:8000. No modificar gateway, paquetes, firewall ni Docker global.
- Navegador exclusivamente http://browser-app:8083 y marca testing; nunca credenciales/pruebas de escritura en producción. PHP/Composer en Docker, Git local sin remoto, .ai/rules no existe.
- Restauración en red interna y tmpfs sin correo; versión/clave de la copia. Tinker requiere directorio temporal XDG_CONFIG_HOME en esta imagen; no cambiar permisos globales.
- Mantener PROGRESS actualizado durante el trabajo y en commits; preservar las decisiones y tareas anteriores.

## Último error / errores pendientes
**redirect_uri_mismatch** confirmado al abrir el consentimiento de Google con el cliente proporcionado. La app envía la URI correcta; falta registrarla en Google Cloud. No se puede corregir la configuración del cliente externo usando únicamente su Client ID/Secret. Acceso con contraseña operativo.

## Siguiente acción concreta
Terminar navegador completo sobre datos ficticios y revisar capturas de lateral expandido/plegado/móvil. Corregir cualquier fallo, guardar bloque, construir imágenes y desplegar con copia. No retomar Google; no repetir pruebas PHP ya correctas salvo cambios relacionados.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si cambian los flujos Google:
bash ops/php vendor/bin/phpunit tests/Feature/GoogleAuthTest.php tests/Feature/GoogleRegistrationTest.php
docker compose -f compose.test.yml run --rm tests php vendor/bin/phpunit tests/Feature/GoogleAuthTest.php tests/Feature/GoogleRegistrationTest.php
# Retirar solo entorno de pruebas al acabar:
docker compose -f compose.test.yml --profile browser --profile runtime down
```
No imprimir cabeceras completas de /auth/google (contienen state). No repetir bootstrap/migraciones/envíos para recuperar contexto. Entregas nuevas: imágenes versionadas, `bash ops/deploy VERSION backups/FECHA-VERSION`, directorio de copia nuevo.
