# Despliegue de Erronk2D en este VPS

**Plan preparado; publicación pública no ejecutada.** Trabajar desde `/home/deploy/projects/erronk2d`. No cambiar el gateway ni DNS hasta tener autorización para la activación.

## Arquitectura preparada

```text
Internet → Caddy compartido (HTTPS erronk2d.jonvadillo.com)
         → 127.0.0.1:8082 → Nginx Erronk2D → PHP-FPM Erronk2D
                                            → PostgreSQL Erronk2D (red privada)
```

`compose.production.yml` define proyecto `erronk2d`: imágenes app/web, red privada `backend`, red propia outbound para correo y publicación local del servicio web, volúmenes `database` y `storage`, logs rotados y límites de recursos. PHP y PostgreSQL no publican puertos. El servicio web también pertenece a la red propia outbound: Docker de este VPS no materializa PortBindings si el contenedor solo está conectado a una red internal. La publicación sigue limitada a 127.0.0.1:8082. Nginx y PHP ejecutan con UID 1000 y sin capacidades añadidas. El presupuesto máximo configurado de RAM es 704 MiB entre los tres servicios; no es una medida de consumo real ni sustituye una prueba de carga.

`TRUSTED_PROXIES=REMOTE_ADDR` confía en el Nginx interno inmediato para la cadena de cabeceras de Caddy. Es correcto únicamente mientras PHP-FPM no sea expuesto y el puerto web siga limitado a loopback. Verificar cabeceras HTTPS, cookies y dirección de cliente antes de abrir el servicio.

## Decisiones necesarias para activar

1. Autorizar el alta del subdominio y la incorporación/recarga de **un único sitio** del Caddy compartido.
2. Confirmar quién administrará la aplicación: nombre y correo de la cuenta inicial. El comando solicitará su contraseña de forma oculta.
3. Proporcionar servidor SMTP, credenciales y remitente autorizado. No se deben pegar secretos en el repositorio.
4. Confirmar destino externo cifrado, retención y frecuencia de copias. Propuesta inicial: diaria, antes de cada actualización y prueba de restauración periódica. No se ha programado ninguna tarea global.

Los criterios funcionales ya aceptados están en `decisiones.md`; no requieren otra aprobación.

## Preparación y comprobaciones

Revisar espacio, RAM, contenedores y `ss -ltn`. Verificar la API existente con una consulta GET a su healthcheck, sin ejecutar scripts que generen datos de otra aplicación.

Construir y probar imágenes propias:

```sh
docker build -t erronk2d-php:local -f ops/Dockerfile.php .
docker build -t erronk2d-app:local --target application -f ops/Dockerfile.production .
docker build -t erronk2d-web:local --target web -f ops/Dockerfile.production .
docker compose -f compose.production.yml config --no-interpolate --no-env-resolution --quiet
```

Antes de una entrega pública, usar una etiqueta de versión identificable para las dos imágenes y registrar sus digests. Composer y npm tienen lockfiles. Las etiquetas base deben fijarse a los digests efectivamente validados antes de establecer una política de actualizaciones; no usar actualizaciones automáticas de imágenes en producción.

Crear `.env.production` a partir del ejemplo **si no existe**, permisos 600. Generar `APP_KEY` y una contraseña aleatoria exclusiva de PostgreSQL; rellenar correo. Conservar una copia protegida de `APP_KEY`: perderla impide recuperar datos cifrados/sesiones. No reutilizar `.env`, SQLite ni las contraseñas de demostración.

En todos los comandos de producción usar explícitamente:

```sh
docker compose --env-file .env.production -f compose.production.yml config --quiet
```

## Ensayo privado de las imágenes

El perfil `runtime` de `compose.test.yml` levanta las imágenes app/web dentro de la red interna de pruebas, sin puertos publicados y con la base temporal. Se ha verificado su funcionamiento por HTTP interno, cookies Secure/HttpOnly, assets HTTPS y denegación de archivos ocultos. Esto valida el empaquetado; no sustituye las comprobaciones de DNS, TLS público y SMTP de la activación.

```sh
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml --profile runtime up -d app web
# Comprobaciones internas; web escucha en http://web:8080 dentro de esa red.
docker compose -f compose.test.yml --profile runtime down
```

No ejecutar las pruebas PHP mientras esté abierto el ensayo HTTP contra su misma base temporal. El perfil utiliza una clave fija exclusivamente de pruebas, que nunca debe copiarse al entorno real.

## Primera activación, después de autorizarla

1. Detener y retirar exclusivamente `erronk2d-preview` si se va a reutilizar 8082. Su base SQLite permanece dentro del proyecto, separada de producción.
2. Arrancar la base propia y ejecutar las migraciones. No usar `migrate:fresh` en producción.

```sh
docker compose --env-file .env.production -f compose.production.yml up -d postgres
docker compose --env-file .env.production -f compose.production.yml run --rm app php artisan migrate --force --no-interaction
docker compose --env-file .env.production -f compose.production.yml run --rm app php artisan erronk2d:admin administrador@tu-centro.eus --name="Nombre del administrador"
docker compose --env-file .env.production -f compose.production.yml up -d app web
```

El correo/nombre del ejemplo deben sustituirse por los autorizados. `erronk2d:admin` solo crea la primera cuenta y rechaza sobrescribir o añadir otra cuando ya existe un administrador. No ejecutar el seeder de demostración en producción: está bloqueado por entorno.

3. Comprobar `/up` y `/login` por loopback, imágenes, logs, conexión a la base y assets. Para simular HTTPS a través del proxy, enviar `Host: erronk2d.jonvadillo.com` y `X-Forwarded-Proto: https`. Las cookies de producción requieren HTTPS; las pruebas de acceso final se harán a través del dominio.
4. Crear registro **A** de `erronk2d` hacia `2.28.118.113`. No tocar el registro raíz. No crear AAAA sin verificar IPv6 extremo a extremo. Comprobar DNS autoritativo, propagación y CAA antes de solicitar el certificado.
5. Respaldar la configuración actual del gateway. Incorporar solo `ops/erronk2d.caddy` como `sites/erronk2d.caddy`. Validar el Caddyfile completo con el binario existente, que incluye el sitio de athletes, antes de recargar. Si falla la validación, retirar exclusivamente el archivo recién añadido y conservar el servicio activo.
6. Recargar Caddy con su comando de recarga, sin reiniciar Docker ni sustituir certificados/volúmenes. No ejecutar `compose down` sobre el gateway.
7. Comprobar redirección HTTP→HTTPS, hostname/cadena/vigencia del certificado, cookies Secure/HttpOnly, acceso de administrador/profesor/alumno, recuperación por SMTP e importación. Volver a comprobar la API existente.

## Copias y restauración

`bash ops/backup backups/FECHA-VERSION` crea un directorio nuevo, privado, con un `pg_dump` de formato custom, el almacenamiento propio y `.env.production`. Rechaza reutilizar un directorio existente. Preparar previamente el directorio padre `backups/`; nunca subirlo a Git. La copia contiene información sensible y debe cifrarse antes de enviarse fuera del VPS. Un fallo intermedio deja una copia incompleta que no debe darse por válida.

Para una copia coherente antes de actualizar: poner **solo Erronk2D** en mantenimiento y esperar a que terminen las peticiones en curso, realizar la copia, después salir de mantenimiento. No parar servicios ajenos. Una copia en vivo de PostgreSQL es consistente para la base; la coincidencia temporal con ficheros requiere mantenimiento si en el futuro se añaden cargas de documentos.

Validar primero la restauración en un proyecto Compose separado, con otra base/volúmenes y sin ruta pública: `pg_restore --exit-on-error --no-owner --no-acl` sobre una base vacía, restaurar almacenamiento, ajustar propietario UID 1000, aplicar la clave original y arrancar la misma versión de imágenes. Comparar usuarios, retos, publicaciones y notas conocidas. No restaurar encima de producción para hacer una prueba.

La herramienta de copia está preparada, pero aún no se ha probado con datos de producción ni se ha configurado almacenamiento externo o retención automática.

## Actualizaciones y reversión

Construir imágenes antes de la ventana de mantenimiento; ejecutar pruebas con SQLite/PostgreSQL y navegador. Etiquetar la entrega y conservar las imágenes anteriores. Hacer copia, poner Erronk2D en mantenimiento, migrar exclusivamente su base, sustituir solo app/web y verificar antes de reabrir.

Si falla una primera activación, retirar solo el sitio Erronk2D y validar/recargar Caddy. Detener únicamente el Compose `erronk2d`, conservando sus volúmenes. Si falla una actualización, volver a imágenes anteriores solo cuando el esquema sea compatible; si no lo es, restaurar una copia verificada con una ventana de mantenimiento explícita. La restauración puede perder cambios posteriores a la copia y requiere autorización específica. No ejecutar `down -v`, `migrate:rollback` ni limpiezas globales como mecanismo automático de reversión.
