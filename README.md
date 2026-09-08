# Erronk2D

Aplicación de evaluación de retos con Laravel 13, PHP 8.4, Inertia 3 y Vue 3. La especificación original está en [prompt.md](prompt.md); las aclaraciones posteriores se conservan en [docs/decisiones.md](docs/decisiones.md).

La cadena de cálculo es: **rúbrica del equipo → reparto opcional registrado por el profesor → suma de todas las defensas → una única nota final del reto por estudiante**. Esa misma nota participa en todos los módulos. Las transversales del profesorado se registran por estudiante, criterio y reto, sin módulo.

## Estado

Implementados: acceso y recuperación de contraseña; roles y permisos; cursos con Evaluaciones, clases, personas y módulos; rúbricas reutilizables con copias históricas; retos y equipos; matriz editable, reparto, defensas y examen único por módulo; autoevaluación y coevaluación; transversales compartidas; publicación y reapertura; informes ponderados de Evaluaciones y media de curso; importación CSV/XLSX y exportación CSV/impresión PDF.

La demostración está escuchando **solo en `127.0.0.1:8082` del VPS**. Contiene datos ficticios. Para verla desde tu ordenador:

```sh
ssh -N -L 8082:127.0.0.1:8082 deploy@2.28.118.113
```

Abre `http://localhost:8082`. Cuentas: `admin@erronk2d.test`, `profesor1@erronk2d.test` y `alumno1@erronk2d.test`. La contraseña está en el archivo local protegido `ops/demo-credentials`; no se incluye en Git ni en este documento. La demostración utiliza SQLite; producción utilizará PostgreSQL propio.

El dominio público y SMTP están pendientes de configuración autorizada. La recuperación de contraseña local escribe al log y no envía correo real.

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

Para iniciar la vista previa si no existe el contenedor:

```sh
docker run -d --name erronk2d-preview --cpus=.75 --memory=384m --pids-limit=100 \
  --user 1000:1000 --cap-drop ALL --security-opt no-new-privileges:true \
  -p 127.0.0.1:8082:8082 -v "$PWD:/app" -w /app erronk2d-php:local \
  php artisan serve --host=0.0.0.0 --port=8082 --no-reload
```

El puerto se comparte de forma alternativa con el futuro servicio de producción; nunca se deben arrancar ambos sobre 8082.

## Verificación

```sh
bash ops/php vendor/bin/phpunit
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml down
npm run build
```

PostgreSQL de pruebas usa red privada, almacenamiento temporal y ningún puerto público. El `down` anterior solo corresponde al proyecto `erronk2d-test`. No usar limpiezas globales de Docker.

Las pruebas de navegador usan datos ficticios locales y restauran las notas que modifican; generan eventos de auditoría de demostración. Requieren la vista previa y su contraseña local:

```sh
docker run --rm --name erronk2d-browser --network host --cpus=1 --memory=1g \
  --pids-limit=200 --shm-size=128m --user 1000:1000 -v "$PWD:/app" -w /app \
  mcr.microsoft.com/playwright:v1.63.0-noble npx playwright test
```

No ejecutar esa suite contra datos reales. Las capturas se generan en `test-results/`, fuera de Git. Las pruebas PHP utilizan bases separadas mediante `phpunit.xml`.

## Operación y alcance

- [Auditoría del VPS y riesgos](docs/auditoria-vps.md).
- [Despliegue, DNS, HTTPS, copias y reversión](docs/despliegue.md).
- [Correspondencia con la especificación y pendientes](docs/estado-proyecto.md).
- [Decisiones funcionales](docs/decisiones.md).

La biblioteca de rúbricas se puede modificar y duplicar sin alterar las copias de los retos. La composición de equipos se bloquea cuando empiezan sus evaluaciones. La edición estructural de cursos y matrículas con retos está restringida para conservar el histórico. Los informes de curso son de seguimiento y pueden contener resultados todavía no publicados; cada publicación conserva su instantánea independiente.
