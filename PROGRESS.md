# Estado de la tarea

## Objetivo
Corregir y publicar el error 422 al crear un reto, conservando los datos del formulario y explicando las incompatibilidades de rúbricas/módulos. Producción aún en `504db0b`. Actualizado 2026-09-09. Reanudar leyendo este archivo, `git status --short` y `git log -5 --oneline`; abrir solo archivos del siguiente paso, sin repetir auditoría/especificación/pruebas ya verificadas.

## Completado
- Corrección nueva verificada: las validaciones de curso/módulos/rúbricas devuelven errores del formulario; selector deshabilita rúbricas incompatibles y limpia selección obsoleta. 7 pruebas nuevas / 47 aserciones correctas en SQLite y PostgreSQL; nueva prueba Playwright correcta (error de servidor conserva formulario, posterior creación válida). Compilación y Pint correctos. Falta desplegar. No se conoce la selección exacta del usuario; se cubren todas las ramas que producían la pantalla 422.
- Producción en imágenes **erronk2d-app:504db0b / erronk2d-web:504db0b**, migración aplicada y mantenimiento retirado. HTTPS login 200, recursos públicos correctos, cookies Secure/HttpOnly; API vecina 200.
- Mínimo 10 caracteres en alta/cambio/recuperación/administrador; clase actual única y obligatoria en alta/importación; pestañas Profesor/Estudiante con clase; renombrado del curso sin recrear Evaluaciones.
- Recuperación explícita de participantes para retos vacíos, errores de equipos en español y selección validada. Cambio de clase conserva participantes e informes históricos. Corregido CSRF tras login usando la cookie vigente.
- `erronk2d:demo` ejecutado en producción: **40 estudiantes y 10 profesores ficticios activos**, además de las cuentas manuales; ningún estudiante ficticio sin clase. Crea dos clases iniciales, módulos y rúbricas; conserva contraseñas/asignaciones/datos editados y repone cuentas ficticias. `ops/deploy` lo ejecuta en cada entrega.
- Código en commits `bb10242` y `504db0b`. Validación final: **54 pruebas / 420 aserciones en SQLite y también PostgreSQL; 5 pruebas Playwright correctas**. Compilación Vue/TypeScript, Pint y sintaxis del despliegue correctos. No repetir solo para reanudar.
- Copia previa a esta entrega: `backups/production-20260909-504db0b`. Copia anterior PostgreSQL restaurada en base temporal y migración nueva ensayada conservando sus registros. Archivo de almacenamiento legible; ensayo completo de ficheros/aplicación aún pendiente.
- Entorno `erronk2d-test` retirado después de verificar: contenedores, red y bases temporales eliminados; copias privadas e imágenes anteriores conservadas. Demo antigua SQLite detenida y conservada.
- Resend: dominio verificado y remitente definitivo configurado. Envío anterior con remitente provisional entregado; no repetir correos al reanudar.
- Documentación de proyecto/despliegue actualizada. El usuario autorizó actualizar este progreso durante el trabajo; ya no aplica la antigua restricción de detener cambios después de escribirlo.

## Pendiente
Prioridad actual: construir imágenes del siguiente commit, desplegar con copia y comprobar HTTPS. No repetir las pruebas de creación ya verificadas.
1. Validación del usuario con la versión publicada. Los estudiantes antiguos sin clase requieren asignación expresa; no inventar matrículas. Para reparar su reto vacío: Organización → Estudiante → asignar clase; Gestionar equipos → Incorporar estudiantes de la clase.
2. Operación: ensayar restauración completa de ficheros/aplicación; acordar destino externo cifrado, frecuencia y retención de copias. La restauración de base y la migración ya se verificaron.
3. Medir carga concurrente y concretar supervisión de TLS/espacio. Verificar entrega con remitente definitivo en la siguiente recuperación solicitada, sin generar envíos adicionales por iniciativa propia.
4. Exportación XLSX/PDF generado en servidor son ampliaciones futuras. Limpieza de datos ficticios solo cuando el usuario la solicite.

## Archivos relevantes y modificados
- Incidencia actual: `app/Http/Controllers/ChallengeController.php`, `resources/js/pages/Dashboard.vue`, `tests/Feature/ChallengeCreationTest.php`, `tests/Browser/workflows.spec.ts`, `PROGRESS.md`. Existe una eliminación ajena de `docs/PROGRESS.md`: conservarla sin recrear el archivo ni incluirla en nuestros commits.
- Modificados en esta reanudación: `PROGRESS.md`, `README.md`, `docs/despliegue.md`, `docs/estado-proyecto.md`. Commit de continuidad previo: `c2493f3`; el commit final de documentación se identifica con `git log`.
- Operación: `ops/deploy`, `ops/backup`, `ops/Dockerfile.production`, `compose.production.yml`, `compose.test.yml`.
- Migración: `database/migrations/2026_09_08_204238_add_current_classroom_and_demo_identifiers.php`; FK nullable para legado, asociaciones inequívocas migradas y `demo_key` únicos. Vínculos antiguos ambiguos conservados para resolución expresa.
- Código: `app/Console/Commands/EnsureDemoData.php`; controladores Setup/Challenge/Import/Auth; `app/Domain/Grades/{ChallengeWriter,Gradebook}.php`; modelos User/Classroom.
- Interfaz: `resources/js/pages/{Setup,Challenge,Login}.vue`, `resources/js/lib.ts`. Pruebas Feature Enrollment/DemoData/Access/Gradebook/Import y `tests/Browser/workflows.spec.ts`.
- Fuentes funcionales: `prompt.md`, `docs/decisiones.md`, `docs/plan-correcciones.md`. No releerlas completas salvo cambios o dudas concretas.
- Privados/ignorados: `.env.production` (versión actualizada por despliegue), `ops/production-credentials`, `backups/`, `test-results/`. No imprimir secretos, claves ni datos personales.

## Decisiones
- Una clase actual por estudiante; conservar participantes/notas históricos. No sincronizar automáticamente retos evaluados/publicados.
- 40/10 son cuentas FICTICIAS adicionales a manuales/administradoras. Contraseñas aleatorias: el administrador puede cambiarlas para probar accesos. Sin correos a direcciones `.test`.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales del profesor por estudiante/criterio/reto sin módulo; auto/coevaluación activas. Retos ponderados por Evaluación y media de Evaluaciones para el curso.
- VPS compartido: no modificar gateway, paquetes, Docker global, firewall ni servicios ajenos. Web Erronk2D solo 127.0.0.1:8082; app/web en backend + outbound y DB privada. API athletes en 127.0.0.1:8000. Gateway `web-gateway-caddy-1`.
- Navegador exclusivamente `http://browser-app:8083`, red interna sin puertos públicos y comprobación de marca testing. Procedimiento en README; nunca apuntar las pruebas a producción.
- PHP/Composer solo en Docker; `.ai/rules` no existía. Git local sin remoto. Aplicar AGENTS y verificar versiones instaladas si vuelve a cambiar código.

## Último error / errores pendientes
Incidencia activa: creación de reto muestra 422 genérico. `abort(422)` en validaciones de dominio impide a Inertia mostrar errores de campo; una rúbrica actual tiene criterios de cuatro módulos y puede resultar incompatible con los elegidos. Sustituido por ValidationException y prevención en selector; pendiente de publicación.

## Siguiente acción concreta
Guardar el commit de corrección, construir sus imágenes app/web y publicar con `ops/deploy` y una copia nueva. Mantener las 40/10 cuentas ficticias y no modificar datos manuales.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si nuevos cambios justifican repetir pruebas:
bash ops/php vendor/bin/phpunit
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml --profile browser --profile runtime down
```
No repetir bootstrap, migraciones, carga ficticia, envíos o pruebas solo para recuperar contexto. Para nuevas entregas usar imágenes versionadas verificadas y `bash ops/deploy VERSION backups/FECHA-VERSION` con un directorio de copia nuevo.
