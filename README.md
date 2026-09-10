# Erronk2D

Aplicación de evaluación de retos con Laravel 13, PHP 8.4, Inertia 3 y Vue 3. La especificación original está en [prompt.md](prompt.md); las aclaraciones posteriores se conservan en [docs/decisiones.md](docs/decisiones.md).

La cadena de cálculo es: **rúbrica del equipo → reparto opcional registrado por el profesor → suma de todas las defensas → una única nota final del reto por estudiante**. Esa misma nota participa en todos los módulos. Las transversales del profesorado se registran por estudiante, criterio y reto, sin módulo.

## Estado

Implementados: acceso y recuperación de contraseña; roles y permisos; cursos con Evaluaciones, clases, personas y módulos; rúbricas reutilizables con copias históricas; retos y equipos; matriz editable, reparto, defensas y examen único por módulo; autoevaluación y coevaluación; transversales compartidas; publicación y reapertura; informes ponderados de Evaluaciones y media de curso; importación CSV/XLSX y exportación CSV/impresión PDF.

Producción está disponible en **https://erronk2d.jonvadillo.com**, con PostgreSQL propio y correo por la API de Resend. El dominio de correo está verificado y el remitente definitivo configurado. Las credenciales iniciales se conservan únicamente en `ops/production-credentials`, archivo privado e ignorado por Git.

Acceso Google y solicitudes de registro implementados, con su puesta en funcionamiento **aplazada por el usuario**: las cuentas existentes confirman una vez su contraseña para vincularse; el administrador aprueba cuentas nuevas y asigna rol/clase. Configuración y estado de la activación en [docs/despliegue.md](docs/despliegue.md). Los secretos OAuth permanecen exclusivamente en el entorno privado.

El menú lateral puede contraerse a una columna de iconos y recuerda la preferencia en el navegador. Organización tiene páginas independientes para Cursos académicos, Clases, Profesor, Estudiante, Módulos, Biblioteca de rúbricas y Solicitudes (solo administración). En móvil se accede mediante el submenú de la barra inferior.

La antigua demostración SQLite está detenida y conservada. El puerto `127.0.0.1:8082` pertenece ahora a producción: no arrancar allí la vista previa ni ejecutar la suite de navegador contra él.

Implementadas las correcciones de [el plan](docs/plan-correcciones.md): clase actual única y obligatoria en altas/importación, recuperación explícita de retos vacíos, contraseñas de 10 caracteres, renombrado sin alterar Evaluaciones y páginas Profesor/Estudiante. `erronk2d:demo` mantiene 40 estudiantes y 10 profesores ficticios además de las cuentas manuales. Consultar `PROGRESS.md` para la versión efectivamente desplegada.

## Desarrollo aislado

PHP y Composer se ejecutan en contenedores exclusivos. No instales paquetes PHP ni modifiques servicios globales de este VPS.

```sh
docker build -t erronk2d-php:local -f ops/Dockerfile.php .
```

En una copia nueva del proyecto: instalar las dependencias con Composer dentro de esa imagen (usuario 1000:1000 y `/app` como directorio), ejecutar `npm ci --ignore-scripts`, copiar `.env.example` a `.env` **solo si no existe**, generar la clave con `bash ops/php artisan key:generate`, crear `database/development.sqlite` y ejecutar `bash ops/php artisan migrate`. No ejecutar este procedimiento sobre la demostración ya existente.

El seeder es opcional: requiere base vacía, entorno `local` o `testing`, y una contraseña aleatoria de al menos 12 caracteres en `ERRONK2D_DEMO_PASSWORD`. Rechaza bases que ya tengan usuarios o cursos.

```sh
bash ops/php artisan db:seed
npm run build
```

Para reactivar una demostración, preparar un puerto propio libre y adaptar la configuración de navegador antes de iniciarla. No reutilizar el puerto 8082 de producción.

## Verificación

```sh
bash ops/php vendor/bin/phpunit
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml down
npm run build
```

PostgreSQL de pruebas usa red privada, almacenamiento temporal y ningún puerto público. El `down` anterior solo corresponde al proyecto `erronk2d-test`. No usar limpiezas globales de Docker.

La suite de navegador usa exclusivamente `http://browser-app:8083` en la red interna de pruebas, sin publicar puertos. Antes de enviar credenciales o escribir exige la marca del entorno `testing`; producción no la proporciona. No ejecutar pruebas PHP contra PostgreSQL mientras la instancia de navegador usa esa misma base temporal.

```sh
# Solo el proyecto erronk2d-test; reinicializa su base ficticia:
docker compose -f compose.test.yml stop browser-app
docker compose -f compose.test.yml run --rm browser-app php artisan migrate:fresh --seed --force --no-interaction
docker compose -f compose.test.yml run --rm browser
docker compose -f compose.test.yml --profile browser --profile runtime down
```

Capturas en `test-results/`, fuera de Git. Las pruebas PHP usan bases separadas mediante `phpunit.xml`.

## Operación y alcance

- [Auditoría del VPS y riesgos](docs/auditoria-vps.md).
- [Despliegue, DNS, HTTPS, copias y reversión](docs/despliegue.md).
- [Correspondencia con la especificación y pendientes](docs/estado-proyecto.md).
- [Decisiones funcionales](docs/decisiones.md).

La biblioteca de rúbricas se puede modificar y duplicar sin alterar las copias de los retos. La composición de equipos se bloquea cuando empiezan sus evaluaciones. Cambiar la clase actual de un estudiante conserva los participantes y las notas de retos anteriores; renombrar un curso conserva sus Evaluaciones. Los informes de curso son de seguimiento y pueden contener resultados todavía no publicados; cada publicación conserva su instantánea independiente.
