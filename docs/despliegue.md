# Despliegue de Erronk2D en este VPS

**Producción en fdd6a55; desplegada el 8 de octubre de 2026.** La lista de estudiantes de Evidencias muestra un recuento por valoración (verde/gris/rojo) en lugar del total y sin avatares. `evidence.spec.ts` 5/5 antes de desplegar. Imágenes desde `git archive fdd6a55`; `ops/deploy` con copia privada `backups/production-20261008-fdd6a55` de `3f780ff`; sin migraciones. App/web ejecutan fdd6a55, saludables; `/up` y `/login` HTTP 200 por HTTPS.

- `erronk2d-app:fdd6a55`: `sha256:5334c59daee600d994d7d8a50f25bc24f74c51ea2cbfe125ddf919cc04f230f1`.
- `erronk2d-web:fdd6a55`: `sha256:ba3bd063847fa7e1dd704ea71b713cd8479b1a25f14eb851746e8ebb61faa7ac`.

**Producción anterior en 3f780ff; desplegada el 8 de octubre de 2026.** Las anotaciones de evidencias tienen valoración positiva, neutra o negativa (selector de caras junto a «Guardar anotación», neutra por defecto). Migración `add_sentiment_to_challenge_evidences` aplicada: las 3 anotaciones existentes quedan como neutras.

Validación previa: 218 pruebas PHP / 1.941 aserciones en PostgreSQL y 16 pruebas de navegador correctas tras eliminar las obsoletas. Imágenes construidas desde `git archive 3f780ff`; `ops/deploy` completado con copia privada `backups/production-20261008-3f780ff` de `f3f2a9e`. App/web ejecutan 3f780ff, web y PostgreSQL saludables, mantenimiento retirado; `/up` y `/login` HTTP 200 por HTTPS.

- `erronk2d-app:3f780ff`: `sha256:b0730e1c84636cec2c3ec1a76185ca0737f6a619c6c4a711c136a96a0f8698e2`.
- `erronk2d-web:3f780ff`: `sha256:661f6c9c3b9ff0411218fa5a1bf5f4236dbafd5f1fab6ae262d820f9ab14ed44`.

**Producción anterior en f3f2a9e; desplegada el 8 de octubre de 2026, sin ejecutar tests por indicación del usuario.** Inicio del reto con cuatro cajas, avisos flotantes (toasts), tablas de evaluación y rúbricas a pantalla completa, títulos h1 unificados (`clamp(24px,2vw,32px)`) sin etiqueta superior y sin nota del lateral.

Imágenes construidas en el VPS desde `git archive f3f2a9e`; build TypeScript/Vite correcto. `ops/deploy` completado con copia privada `backups/production-20261008-f3f2a9e` de `1c60762`; sin migraciones pendientes; datos de demo preparados. App/web ejecutan f3f2a9e, web y PostgreSQL saludables, mantenimiento retirado. `/up` y `/login` HTTP 200 por HTTPS; CSS servido con el nuevo tamaño de título.

- `erronk2d-app:f3f2a9e`: `sha256:1ad23a3d19c032a0f35691a42a31ec01f1323089769b90828939c4b8a203b51f`.
- `erronk2d-web:f3f2a9e`: `sha256:da411641cebec0ef76c79d87b6449226cd615a396c4587ef6542c03cafc57d6e`.

**Producción anterior en 1c60762; desplegada el 7 de octubre de 2026.** Nueva paleta morada de la interfaz (`#82005e`, `#6f0050`, rosas claros y grises neutros).

Imágenes construidas en el VPS desde `git archive 1c60762`; build TypeScript/Vite correcto. `ops/deploy` completado con copia privada `backups/production-20261007-1c60762` (700) de `b175c40-local-20261003`; sin migraciones pendientes; datos de demo preparados. App/web ejecutan 1c60762, web y PostgreSQL saludables, mantenimiento retirado. `/up` y `/login` HTTP 200 por HTTPS; el CSS servido y `theme-color` usan el morado.

- `erronk2d-app:1c60762`: `sha256:6dcf8691bf13fda66d350d93950ec60c0cb7a8ba0abbfa07b65dbc0b7fd48a4b`.
- `erronk2d-web:1c60762`: `sha256:7ba1e8c92ab86e3ef4ba7a12297a99ee8cf60978a5ed7725d8b98dcb6d6dc66d`.

**Producción anterior en 8df6f23; verificación cerrada el 22 de septiembre de 2026.** La evaluación de rúbricas permite seleccionar el nivel desde toda la celda y expandir el texto solo con «+ Leer más».

Imágenes construidas desde `git archive 8df6f23`, excluyendo cambios ajenos. `npm run build` (TypeScript/Vite) correcto. `ops/deploy` completado con copia privada `backups/production-20260922-8df6f23`; índice PostgreSQL y gzip del almacenamiento comprobados, sin migraciones pendientes. Se prepararon los datos de demo prescritos. App/web usan `8df6f23`, app/web/PostgreSQL activos y web saludable; mantenimiento retirado. `/up` y `/login` verificados por HTTPS; login HTTP 200. No se repitieron pruebas funcionales ni escrituras académicas.

**Producción anterior en 7bca158.** Publicados el editor de rúbricas en página propia, pesos porcentuales que suman 100 %, niveles comunes por columna y evaluación docente/auto/coevaluación en tabla con guardado automático. Las copias históricas de las rúbricas conservan sus criterios, notas y selecciones.

Imágenes construidas desde `git archive 7bca158`, excluyendo cambios ajenos. TypeScript/Vite y rutas del editor en la imagen correctos. Identificadores publicados:

- `erronk2d-app:7bca158`: `sha256:5c04e7d60a48ae13f4e7e842539cd1e56e8b830ab0b734008dda74788c73f1a1`.
- `erronk2d-web:7bca158`: `sha256:2c6ec4fe6bf398f5207442586d6a68c84aa430616603a6ca9a60dad9b0ba2927`.

`ops/deploy` completado con copia privada `backups/production-20260922-7bca158` de **3bfe097**. Índice del volcado PostgreSQL, integridad gzip y permisos 700/600 comprobados. No había migraciones pendientes. No se ejecutó reinicio académico ni recarga del catálogo. App/web ejecutan 7bca158, web y PostgreSQL saludables, mantenimiento retirado.

Validación funcional previa: 52 pruebas / 428 aserciones tanto en SQLite como PostgreSQL; tres pruebas nuevas de navegador y doce workflows existentes correctos, con capturas de escritorio/móvil revisadas. Tras desplegar: acceso administrativo, biblioteca, nuevo editor, apertura de una rúbrica existente y página del reto HTTP 200; enlaces y recursos de editor/evaluación por HTTPS y cookies Secure/HttpOnly correctos; sesión cerrada. La comprobación de producción fue de solo lectura académica; no se repitieron guardados ni la batería funcional ya verificada en aislamiento.

**Catálogo FP publicado previamente en 3bfe097, el 16 de septiembre de 2026.** Cargado mediante `php artisan erronk2d:catalog --execute --no-interaction`: 179 ciclos y 2.687 módulos nuevos; total 181 ciclos y 2.691 módulos. Currículo ministerial incluido en `database/seeders/official-catalog.json`, cuya distribución por cursos no equivale a la de Euskadi. Los dos ciclos y cuatro módulos anteriores se conservan, junto con sus vínculos de grupo, comprobados mediante huellas antes/después. La previsualización posterior indica cero registros pendientes. Los módulos nuevos se incorporan a grupos existentes desde Grupos.

Despliegue mediante `ops/deploy`, sin reinicio académico, con copia privada `backups/production-20260916-3bfe097` de la versión **5a66325**. Índice del volcado PostgreSQL, integridad gzip y permisos 700/600 comprobados. Sin migraciones de esquema pendientes; mantenimiento retirado.

Las imágenes se construyeron desde `git archive 3bfe097`, excluyendo cambios ajenos. Identificadores publicados:

- `erronk2d-app:3bfe097`: `sha256:a3fcced078041933598383f8036696478f1ca23a481a275dcc2d643304284684`.
- `erronk2d-web:3bfe097`: `sha256:43fb114cae3de283b137e5f000c6118aec32c57ebd9ec47230b733cab5959185`.

Validación: siete pruebas del importador / 36 aserciones correctas en SQLite y PostgreSQL (previsualización, carga completa, repetición sin duplicados, conservación, ambigüedades y concurrencia). Pint, TypeScript y Vite correctos. En producción, acceso administrativo y páginas Ciclos/Módulos HTTP 200 con los totales esperados, recursos HTTPS y cookies Secure/HttpOnly correctos; sesión cerrada. App/web ejecutan 3bfe097, web y PostgreSQL saludables. Entorno de pruebas retirado. Se omitió repetir la batería de navegador y regresión de Grupos ya verificada, conforme a la autorización del usuario.

Grupos, evaluaciones propias y gestión rápida publicados previamente en **5a66325**, con copia `backups/production-20260915-5a66325` de **878840e** y comprobación completa terminada el 15/09/2026.

El reinicio inicial se ejecutó únicamente en **878840e**, con copia `backups/production-20260914-878840e` de **38e25ce**. **No repetir la limpieza:** producción ya contiene actividad académica. La migración de evaluaciones a grupos no admite rollback automático; una reversión exige restaurar la copia previa con autorización específica. La antigua vista previa sigue detenida y conservada.

## Arquitectura preparada

### Acceso y registro con Google

**Trabajo aplazado expresamente por el usuario.** Conservar esta referencia para cuando solicite retomarlo; no repetir comprobaciones OAuth ni pedir cambios en Google Cloud mientras siga aplazado.

La integración está publicada y **habilitada con las credenciales privadas proporcionadas por el usuario**. La última comprobación real de redirección devolvió **redirect_uri_mismatch** de Google: falta añadir la URI exacta de retorno al cliente en Google Cloud. No utiliza SMTP ni cambia la configuración de Resend. Mantiene el acceso con contraseña.

1. En Google Cloud / Google Auth Platform, crear un cliente OAuth de tipo **Aplicación web** y configurar la pantalla de consentimiento de Erronk2D. Elegir la audiencia adecuada para el centro; si se mantiene en pruebas, añadir las cuentas de prueba en Google. Solo se solicitan `openid`, `email` y `profile`.
2. Registrar exactamente esta URI de retorno: `https://erronk2d.jonvadillo.com/auth/google/callback`. El flujo es de servidor y no necesita una biblioteca JavaScript de Google ni un origen JavaScript autorizado.
3. Configurar de forma privada en `.env.production`: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` con la URI anterior y `GOOGLE_ENABLED=true`. Mantener permisos 600 y no mostrar ni versionar los valores. No reutilizar la clave de Resend. El Compose propio ya carga el archivo de entorno.
4. Recrear únicamente el servicio `app` de Erronk2D para recoger las variables; comprobar primero la configuración sin imprimir secretos. No hace falta modificar Caddy, DNS, certificados o firewall. Para desactivar, poner `GOOGLE_ENABLED=false` y recrear solo `app`.
5. Verificar con una cuenta real autorizada la ida/vuelta de Google, la vinculación inicial, el acceso posterior y una solicitud de registro. Las pruebas automatizadas simulan las respuestas de Google; no sustituyen esta comprobación con el cliente real.

Las cuentas existentes se vinculan confirmando una vez su contraseña de Erronk2D; después se identifican por el identificador estable de Google. No se cambian su rol, permisos, correo ni historial. Cambiar el correo desde Organización elimina la vinculación anterior. Las cuentas desactivadas siguen bloqueadas.

Las nuevas cuentas Google generan una solicitud sin acceso académico. Administración → Organización → Solicitudes permite aprobar o rechazar: al aprobar se elige Profesor o Estudiante; para estudiantes la clase es obligatoria. Los profesores activos pueden crear clases en cualquier curso abierto; adquieren acceso a las clases que crean o a las que les añade su propietario. No hay permisos individuales. No se envían correos automáticamente. Una solicitud rechazada no se reabre mediante otro intento de acceso.

Se usan `state` de un solo uso, caducidad de diez minutos y PKCE S256; el perfil se obtiene desde Google con un token intercambiado exclusivamente en el servidor y exige correo verificado. No se persisten tokens OAuth. El Nginx propio omite parámetros de consulta del registro de acceso para no guardar códigos de autorización. Las credenciales OAuth y los detalles de errores de Google no se registran.

Referencia de configuración: [OAuth para aplicaciones web de Google](https://developers.google.com/identity/protocols/oauth2/web-server).

```text
Internet → Caddy compartido (HTTPS erronk2d.jonvadillo.com)
         → 127.0.0.1:8082 → Nginx Erronk2D → PHP-FPM Erronk2D
                                            → PostgreSQL Erronk2D (red privada)
```

`compose.production.yml` define proyecto `erronk2d`: imágenes app/web, red privada `backend`, red propia outbound para correo y publicación local del servicio web, volúmenes `database` y `storage`, logs rotados y límites de recursos. PHP y PostgreSQL no publican puertos. El servicio web también pertenece a la red propia outbound: Docker de este VPS no materializa PortBindings si el contenedor solo está conectado a una red internal. La publicación sigue limitada a 127.0.0.1:8082. Nginx y PHP ejecutan con UID 1000 y sin capacidades añadidas. El presupuesto máximo configurado de RAM es 704 MiB entre los tres servicios; no es una medida de consumo real ni sustituye una prueba de carga.

`TRUSTED_PROXIES=REMOTE_ADDR` confía en el Nginx interno inmediato para la cadena de cabeceras de Caddy. Es correcto únicamente mientras PHP-FPM no sea expuesto y el puerto web siga limitado a loopback. Verificar cabeceras HTTPS, cookies y dirección de cliente antes de abrir el servicio.

## Estado y decisiones pendientes

- Activación autorizada y completada: registro A existente, sitio específico `sites/erronk2d.caddy` y recarga validada del Caddy compartido, sin reiniciarlo. Copia previa en `backups/gateway-20260908T112052Z.tar.gz`.
- Administrador inicial creado; credenciales únicamente en `ops/production-credentials` (600, ignorado). No repetir su creación ni regenerar APP_KEY.
- API Resend configurada, dominio verificado y remitente definitivo aplicado. DNS y procedimiento en [resend.md](resend.md). No se necesita SMTP.
- Pendiente acordar destino externo cifrado, retención y frecuencia de copias. Propuesta: diaria, antes de cada actualización y restauración de prueba periódica. No se ha programado ninguna tarea global.

Las correcciones funcionales y su siguiente despliegue están en [plan-correcciones.md](plan-correcciones.md).

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

El perfil `runtime` de `compose.test.yml` levanta las imágenes app/web dentro de la red interna de pruebas, sin puertos publicados y con la base temporal. Se ha verificado su funcionamiento por HTTP interno, cookies Secure/HttpOnly, assets HTTPS y denegación de archivos ocultos. Esto valida el empaquetado; no sustituye las comprobaciones de DNS, TLS público y correo real de la activación.

```sh
docker compose -f compose.test.yml run --rm tests
docker compose -f compose.test.yml --profile runtime up -d app web
# Comprobaciones internas; web escucha en http://web:8080 dentro de esa red.
docker compose -f compose.test.yml --profile runtime down
```

No ejecutar las pruebas PHP mientras esté abierto el ensayo HTTP contra su misma base temporal. El perfil utiliza una clave fija exclusivamente de pruebas, que nunca debe copiarse al entorno real.

## Primera activación — referencia histórica, ya ejecutada

No repetir este bloque sobre la producción existente.

1. Detener exclusivamente `erronk2d-preview` si se va a reutilizar 8082; conservar el contenedor. Su base SQLite permanece dentro del proyecto, separada de producción.
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
7. Comprobar redirección HTTP→HTTPS, hostname/cadena/vigencia del certificado, cookies Secure/HttpOnly, acceso de administrador/profesor/alumno, recuperación por Resend e importación. Volver a comprobar la API existente.

## Copias y restauración

`bash ops/backup backups/FECHA-VERSION` crea un directorio nuevo, privado, con un `pg_dump` de formato custom, el almacenamiento propio y `.env.production`. Rechaza reutilizar un directorio existente. Preparar previamente el directorio padre `backups/`; nunca subirlo a Git. La copia contiene información sensible y debe cifrarse antes de enviarse fuera del VPS. Un fallo intermedio deja una copia incompleta que no debe darse por válida.

Para una copia coherente antes de actualizar: poner **solo Erronk2D** en mantenimiento y esperar a que terminen las peticiones en curso, realizar la copia, después salir de mantenimiento. No parar servicios ajenos. Una copia en vivo de PostgreSQL es consistente para la base; la coincidencia temporal con ficheros requiere mantenimiento si en el futuro se añaden cargas de documentos.

Validar primero la restauración en un proyecto Compose separado, con otra base/volúmenes y sin ruta pública: `pg_restore --exit-on-error --no-owner --no-acl` sobre una base vacía, restaurar almacenamiento, ajustar propietario UID 1000, aplicar la clave original y arrancar la misma versión de imágenes. Comparar usuarios, retos, publicaciones y notas conocidas. No restaurar encima de producción para hacer una prueba.

Última copia previa a la navegación en `backups/production-20260910-38e25ce`, correspondiente a la versión anterior `736925b`; base, almacenamiento y entorno privado guardados antes de actualizar. Primera copia local en `backups/production-20260908-resend`; copia previa a Google en `backups/production-20260909-736925b`, con el esquema anterior y la configuración privada OAuth. Se conservan las de `4cd717d` y `504db0b`. La primera base se restauró correctamente en PostgreSQL temporal y se ensayó la migración nueva conservando sus registros. La copia `4cd717d` también superó el ensayo completo descrito a continuación. Quedan pendientes el almacenamiento externo y la retención automática.

### Ensayo de restauración completado el 9 de septiembre de 2026

`compose.restore.yml` define exclusivamente el proyecto `erronk2d-restore`: red interna sin puertos publicados, PostgreSQL y almacenamiento en tmpfs, recursos limitados, correo `array` y sin credenciales de Resend. No comparte volúmenes ni redes con producción. El ensayo no modifica la copia original.

La copia `production-20260909-4cd717d` se obtuvo antes de actualizar y corresponde a imágenes **504db0b**. Se utilizó esa versión y su clave original, leídas del archivo privado `environment`; no se debe deducir la versión por el nombre del directorio. El entorno de ejecución privado `backups/restore-check-20260909-4cd717d/runtime.env` contiene únicamente `ERRONK2D_RESTORE_RELEASE` y `ERRONK2D_RESTORE_KEY`, con permisos 600 en directorio 700. No imprimirlo, versionarlo ni copiar el resto de variables de producción al ensayo.

Procedimiento verificado:

1. Preparar el archivo privado anterior; validar el Compose sin mostrar su configuración completa (incluye la clave). Comprobar que no hay puertos ni volúmenes compartidos y que la única red es interna.
2. Arrancar solo `postgres`; restaurar `database.dump` con `pg_restore --exit-on-error --no-owner --no-acl -U erronk2d_restore -d erronk2d_restore`.
3. Arrancar `app`. Validar previamente que el tar solo contiene rutas relativas, ficheros y directorios, sin enlaces ni recorridos `..`; extraerlo en `/app/storage` del contenedor temporal. Comparar SHA-256 de cada fichero con el archivo original antes de ejecutar Artisan o HTTP.
4. Consultar `artisan migrate:status` con la versión original. La copia actual tiene todas las migraciones aplicadas; no ejecutar migraciones ni seeders por rutina. Ejecutar `artisan up` únicamente en el proyecto temporal: la copia conserva el mantenimiento previo al despliegue.
5. Arrancar `web`; consultar internamente `http://web:8080/login` y sus recursos. Leer los cálculos de `Gradebook::challenge` y `Gradebook::report` en una transacción PostgreSQL de solo lectura. Para Tinker en la imagen, usar `exec -e XDG_CONFIG_HOME=/tmp/erronk2d-psysh` por los permisos del usuario 1000.
6. Retirar el proyecto con `down`, sin opciones de borrado de volúmenes. Al quitar los contenedores desaparecen sus datos temporales en memoria; se conservan la copia y el manifiesto privado de comprobación.

Resultado: restauración PostgreSQL sin errores; **70 archivos coincidentes por SHA-256**, cinco migraciones aplicadas, login y dos recursos HTTP 200, lectura correcta de dos retos y tres informes de clase. La copia contenía tres valoraciones, una nota de módulo y ninguna publicación; este ensayo no demuestra restauración de publicaciones reales ausentes en la copia. Producción y la API vecina seguían respondiendo HTTP 200 al terminar.

Todos los comandos del ensayo deben usar este prefijo, nunca el Compose de producción:

```sh
docker compose --env-file backups/restore-check-20260909-4cd717d/runtime.env -f compose.restore.yml ps
# Retirar únicamente el ensayo cuando haya terminado:
docker compose --env-file backups/restore-check-20260909-4cd717d/runtime.env -f compose.restore.yml down
```

Para repetirlo con otra copia, crear un directorio privado nuevo y seleccionar las imágenes y clave de esa copia. No reutilizar una base temporal que ya contenga datos.

## Actualizaciones y reversión

Construir imágenes antes de la ventana de mantenimiento; ejecutar pruebas con SQLite/PostgreSQL y navegador. Etiquetar la entrega y conservar las imágenes anteriores. Hacer copia, poner Erronk2D en mantenimiento, migrar exclusivamente su base, sustituir solo app/web y verificar antes de reabrir.

Si falla una primera activación, retirar solo el sitio Erronk2D y validar/recargar Caddy. Detener únicamente el Compose `erronk2d`, conservando sus volúmenes. Si falla una actualización, volver a imágenes anteriores solo cuando el esquema sea compatible; si no lo es, restaurar una copia verificada con una ventana de mantenimiento explícita. La restauración puede perder cambios posteriores a la copia y requiere autorización específica. No ejecutar `down -v`, `migrate:rollback` ni limpiezas globales como mecanismo automático de reversión.

## Entregas durante la fase de pruebas del centro

Una vez construidas y verificadas las imágenes `erronk2d-app:VERSION` y `erronk2d-web:VERSION`, ejecutar:

```sh
bash ops/deploy VERSION backups/FECHA-VERSION
```

El procedimiento rechaza un destino de copia existente y una aplicación que ya esté en mantenimiento. Pone solo Erronk2D en mantenimiento, copia su base/almacenamiento/configuración, migra exclusivamente su PostgreSQL, ejecuta `erronk2d:demo`, sustituye app/web y registra la versión en el archivo privado. Si falla antes de iniciar migraciones, recupera el servicio anterior. Si falla después, conserva el mantenimiento para evitar exponer una versión incompatible; no restaura datos automáticamente. Revisar el resultado y HTTPS después.

El comando de datos de prueba usa identificadores `demo_key` y una transacción con bloqueo: mantiene únicamente 40 estudiantes y 10 profesores activos, sin crear cursos, clases, módulos ni rúbricas, sin contar cuentas manuales ni administradores. Conserva nombres y contraseñas editadas; repone cuentas eliminadas y reactiva las ficticias desactivadas. Sus contraseñas iniciales son aleatorias y no se muestran ni envían: el administrador puede asignar una contraseña desde Organización para probar otro rol. Las direcciones `.test` no reciben correo.

Los estudiantes antiguos sin clase requieren asignación expresa. Para reparar un reto vacío: asignar estudiantes a su clase en Organización → Estudiante y usar Gestionar equipos → Incorporar estudiantes de la clase. Los participantes históricos no se sincronizan automáticamente al cambiar matrículas.

No ejecutar manualmente el seeder local en producción. Retirar la carga ficticia del procedimiento y limpiar sus registros solo cuando el usuario lo solicite.


### Transición a cursos independientes

**Transición ya ejecutada en 878840e.** Las instrucciones siguientes documentan la operación realizada; los despliegues futuros omiten la opción de reinicio.

La entrega académica requiere una única limpieza autorizada, separada de `migrate`. Después de validar las imágenes, ejecutar `bash ops/deploy VERSION backups/DIRECTORIO-NUEVO --reset-academics`. El script pone Erronk2D en mantenimiento, crea la copia y comprueba el índice del volcado PostgreSQL y la integridad gzip del almacenamiento antes de migrar y limpiar. Conservar también la versión y configuración anteriores para una restauración coherente.

`erronk2d:reset-academics` sin opciones solo muestra recuentos. La ejecución requiere `--execute --backup-confirmed`, mantenimiento en producción, un administrador activo y las 50 identidades de ejemplo esperadas. Conserva sus credenciales; elimina toda actividad y catálogos académicos, matrículas, rúbricas, cuentas docentes/estudiantiles restantes y sesiones antiguas. Una marca de auditoría impide repetir el borrado en despliegues posteriores. No elimina configuración, archivos de servicio ni copias. Los siguientes despliegues omiten `--reset-academics`.

Tras el reinicio, administración crea el primer curso académico, los ciclos y los módulos. Cada profesor crea sus clases, añade compañeros y asigna responsables; matricula estudiantes por correo exacto o crea cuentas nuevas. El alumnado conserva acceso al histórico de sus matrículas y los años cerrados bloquean toda edición académica.
