# Estado de la tarea

## Objetivo
Incidencia de creación de retos corregida y publicada en **4cd717d** el 2026-09-09. Continuar con la validación del usuario y tareas operativas pendientes. Reanudar leyendo este archivo, `git status --short` y `git log -5 --oneline`; no repetir auditoría, especificación ni pruebas ya verificadas sin motivo.

## Completado
- Crear retos: errores de curso/módulos/rúbricas ahora aparecen en el formulario en español y conservan los datos. Selector deshabilita rúbricas incompatibles y limpia selecciones que dejan de ser válidas. No se conoce la selección exacta que provocó el error del usuario; se cubren todas las ramas que devolvían la pantalla 422.
- Corrección `4cd717d`: **7 pruebas nuevas / 47 aserciones** correctas tanto en SQLite como PostgreSQL; **una prueba nueva Playwright** verifica error conservando formulario y creación posterior válida. Compilación y Pint correctos.
- Producción: app/web **4cd717d**, web y PostgreSQL saludables, mantenimiento retirado, HTTPS login 200 y API vecina 200. Sin migraciones nuevas; despliegue conserva cuentas manuales y mantiene **40 estudiantes / 10 profesores ficticios activos**. Copia privada previa: `backups/production-20260909-4cd717d`.
- Correcciones anteriores publicadas: contraseña mínima 10; clase actual única y obligatoria al dar de alta/importar estudiantes; pestañas Profesor/Estudiante con clase; renombrado del curso; recuperación de participantes en retos vacíos y errores de equipos en español; CSRF correcto tras login.
- Base `504db0b` ya verificada: **54 pruebas / 420 aserciones** en SQLite y PostgreSQL, **5 pruebas Playwright**, recursos HTTPS y cookies Secure/HttpOnly. Son comprobaciones de la entrega anterior, no una repetición completa de la suite actual.
- Entorno temporal `erronk2d-test` retirado al acabar esta corrección. Imágenes y copias anteriores conservadas; vista previa SQLite detenida y conservada.
- Resend: dominio verificado y remitente definitivo configurado; envío anterior con remitente provisional entregado. No repetir correos al reanudar.
- Copia PostgreSQL anterior restaurada en base temporal y migración ensayada conservando registros; almacenamiento legible. Restauración completa de aplicación/ficheros aún pendiente.

## Pendiente
1. Usuario: volver a crear el reto tras recargar la página; seleccionar módulos antes de elegir rúbrica. Si hay incompatibilidad, resolver el mensaje del formulario.
2. Operación: ensayar restauración completa de ficheros/aplicación en entorno separado; acordar destino externo cifrado, frecuencia y retención de copias.
3. Medir carga concurrente y concretar supervisión de TLS/espacio. Verificar entrega del remitente definitivo en la siguiente recuperación solicitada, sin envíos adicionales por iniciativa propia.
4. Estudiantes antiguos sin clase: asignación expresa en Organización → Estudiante. Reto vacío: Gestionar equipos → Incorporar estudiantes de la clase. No inventar matrículas.
5. Exportación XLSX/PDF generado en servidor son ampliaciones futuras. Limpiar cuentas ficticias solo cuando lo solicite el usuario.

## Archivos relevantes y modificados
- Corrección: `app/Http/Controllers/ChallengeController.php`, `resources/js/pages/Dashboard.vue`, `tests/Feature/ChallengeCreationTest.php`, `tests/Browser/workflows.spec.ts`; commit `4cd717d`.
- Continuidad de esta entrega: `PROGRESS.md`, `docs/despliegue.md`, `docs/estado-proyecto.md`. Existe eliminación ajena **sin incluir en nuestros commits** de `docs/PROGRESS.md`; no recrearlo. Último commit documental: consultar git log.
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
- Mantener este archivo actualizado durante el trabajo y en commits de avance; prevalece la petición reciente de actualización continua.

## Último error / errores pendientes
Pantalla 422 corregida y publicada: `abort(422)` en validaciones de dominio sustituido por errores de campo. No hay fallos conocidos pendientes de esta corrección. Falta confirmar que el usuario completa su caso concreto.

## Siguiente acción concreta
Ante nueva incidencia, revisar solo el formulario/controlador y prueba afectados. Si se continúa con operación, preparar ensayo aislado de restauración completa usando la copia reciente, sin sobrescribir producción ni publicar puertos.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si nuevos cambios justifican repetir pruebas:
bash ops/php vendor/bin/phpunit tests/Feature/ChallengeCreationTest.php
docker compose -f compose.test.yml run --rm tests php vendor/bin/phpunit tests/Feature/ChallengeCreationTest.php
docker compose -f compose.test.yml --profile browser --profile runtime down
```
No repetir bootstrap, migraciones, carga ficticia o envíos para recuperar contexto. Nuevas entregas: imágenes versionadas verificadas y `bash ops/deploy VERSION backups/FECHA-VERSION` con directorio nuevo.
