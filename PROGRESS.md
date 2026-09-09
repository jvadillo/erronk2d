# Estado de la tarea

## Objetivo
Publicar las seis correcciones ya implementadas y verificadas, manteniendo 40 estudiantes y 10 profesores ficticios. Actualizado al reanudar el 2026-09-09, antes de continuar. Leer este archivo, `git status --short` y `git log -5 --oneline`; no repetir auditoría, especificación completa ni pruebas sin cambios.

## Completado
- Código en `bb10242` y `504db0b`: mínimo 10 caracteres en contraseñas; clase actual única y obligatoria en alta/importación; pestañas Profesor/Estudiante con clase; renombrado sin recrear Evaluaciones; recuperación explícita de participantes de retos vacíos y errores de equipos en español.
- Informes conservan participantes históricos tras cambios de clase. Corregido CSRF tras login usando la cookie vigente. Fórmulas académicas conservadas.
- `erronk2d:demo` mantiene 40 estudiantes y 10 profesores ficticios, dos clases iniciales, módulos y rúbricas. Identificadores propios, transacción y bloqueo; conserva cuentas manuales, contraseñas y datos editados. `ops/deploy` lo ejecuta en cada entrega.
- Validación final: **54 pruebas / 420 aserciones en SQLite y también PostgreSQL; 5 pruebas Playwright correctas**. Compilación Vue/TypeScript, Pint y sintaxis de despliegue correctos. No repetir al reanudar.
- Navegador aislado en `compose.test.yml`, destino exclusivo `http://browser-app:8083`, sin puertos públicos; exige marca testing antes de escribir. Su servidor temporal quedó detenido después de las pruebas.
- Imágenes `erronk2d-app:504db0b` y `erronk2d-web:504db0b` terminadas; web construida al reanudar reutilizando exactamente las capas de la aplicación verificada.
- Copia PostgreSQL restaurada en `erronk2d_restore_20260909`, dentro del contenedor temporal de pruebas. Migración nueva aplicada correctamente; conservados 2 usuarios, 1 curso, 1 clase y 1 reto. Archivo de almacenamiento legible; restauración completa de ficheros/aplicación no ensayada.
- Producción sigue en **f249689**, app/web/DB activos y web/DB saludables: las correcciones y las 40/10 cuentas todavía NO están desplegadas. HTTPS/Caddy propio/PostgreSQL aislado ya operativos.
- Resend: dominio verificado, remitente definitivo configurado; envío anterior con remitente provisional entregado. No repetir correos al reanudar.

## Pendiente
1. Imágenes y ensayo de restauración/migración terminados. Continuar por el despliegue siguiente; no repetirlos.
2. Publicar con `bash ops/deploy 504db0b backups/production-20260909-504db0b`: mantenimiento exclusivo de Erronk2D, copia nueva, migración propia, carga ficticia, sustitución app/web y reapertura. Preservar imágenes anteriores.
3. Verificar HTTPS, assets, contenedores, ausencia de mantenimiento y recuentos 40/10 ficticios mediante lecturas; salud de API vecina. Retirar solo el entorno temporal `erronk2d-test`.
4. Actualizar documentación y este progreso; commit final. El usuario ahora pide actualizar PROGRESS durante el trabajo: sustituye la antigua restricción de no hacer cambios después de actualizarlo.
5. Después: destino externo cifrado/frecuencia/retención de copias, medición de carga y supervisión TLS/espacio. Entrega con remitente definitivo pendiente de la siguiente recuperación solicitada. XLSX/PDF de servidor son ampliaciones futuras.

## Archivos relevantes y modificados
- Commits de implementación: `bb10242`, `504db0b`. Al reanudar no había cambios sin commit. Modificado ahora: `PROGRESS.md`.
- `ops/deploy`, `ops/backup`, `ops/Dockerfile.production`, `compose.production.yml`, `compose.test.yml`: próximos pasos operativos.
- `database/migrations/2026_09_08_204238_add_current_classroom_and_demo_identifiers.php`: nueva FK nullable para legado, migración de asociaciones inequívocas y `demo_key` únicos. Vínculos ambiguos conservados para resolución expresa.
- `app/Console/Commands/EnsureDemoData.php`; controladores Setup/Challenge/Import/Auth; `app/Domain/Grades/{ChallengeWriter,Gradebook}.php`; modelos User/Classroom.
- `resources/js/pages/{Setup,Challenge,Login}.vue`, `resources/js/lib.ts`; pruebas Feature Enrollment/DemoData/Access/Gradebook/Import; `tests/Browser/workflows.spec.ts`.
- `docs/plan-correcciones.md`, `docs/despliegue.md`, `README.md` actualizados con implementación/procedimiento; `docs/estado-proyecto.md` aún necesita reflejar la entrega.
- Privados/ignorados: `.env.production`, `ops/production-credentials`, `backups/`, `test-results/`. No imprimir secretos. Copias existentes: `backups/production-20260908-resend` y `backups/gateway-20260908T112052Z.tar.gz`.

## Decisiones
- Una clase actual por estudiante; conservar participantes/notas históricos. Estudiantes existentes sin clase requieren asignación expresa, sin adivinar su matrícula.
- Reto vacío: Organización → Estudiante → asignar clase; Gestionar equipos → Incorporar estudiantes de la clase. No sincronizar automáticamente retos evaluados/publicados.
- 40/10 son cuentas FICTICIAS adicionales a las manuales/administradoras. Contraseñas aleatorias que el administrador puede cambiar para probar accesos. Sin correos a direcciones `.test`; limpieza solo a petición del usuario.
- Nota equipo → reparto opcional → TODAS las defensas → UNA nota final de reto usada como 40% en TODOS los módulos. Transversales del profesor por estudiante/criterio/reto sin módulo; auto/coevaluación activas. Retos ponderados por Evaluación y media de Evaluaciones para el curso.
- VPS compartido: no modificar gateway, paquetes, Docker global, firewall ni servicios ajenos. Web Erronk2D solo 127.0.0.1:8082; app/web en backend + outbound, DB privada. API athletes en 127.0.0.1:8000. Demo antigua detenida y conservada.
- PHP/Composer solo en Docker; `.ai/rules` no existía. Git local sin remoto. Consultar AGENTS y versiones instaladas si vuelve a cambiar código.

## Último error / errores pendientes
No hay fallos de código/pruebas pendientes conocidos. Las correcciones todavía no están publicadas. Los estudiantes del reto vacío requieren asignación expresa de clase.

## Siguiente acción concreta
Ejecutar `bash ops/deploy 504db0b backups/production-20260909-504db0b`, después comprobar HTTPS, recuentos ficticios y API vecina. No recrear ni volver a probar la base funcional ya validada.

## Comandos para verificarlo
```sh
git status --short
git log -5 --oneline
docker image inspect erronk2d-app:504db0b erronk2d-web:504db0b --format '{{.RepoTags}} {{.Created}}'
docker compose --env-file .env.production -f compose.production.yml ps
curl -fsS --max-time 15 -o /dev/null -w 'HTTPS %{http_code}\n' https://erronk2d.jonvadillo.com/login
# Solo si nuevos cambios justifican repetir pruebas:
bash ops/php vendor/bin/phpunit
docker compose -f compose.test.yml run --rm tests
```
