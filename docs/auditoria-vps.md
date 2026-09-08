# Auditoría del VPS

Inspección inicial: 7 de septiembre de 2026. Desarrollo y revisión: 8 de septiembre de 2026. Se inspeccionó el VPS donde se ejecuta el proyecto; no se preparó otro servidor.

## Sistema y recursos

- Ubuntu 26.04.1, 2 CPU, aproximadamente 3,7 GiB de RAM y 38 GB de disco. Sin swap.
- Docker 29.8 y Compose 5.5. Las bases y aplicaciones existentes se ejecutan en contenedores.
- PHP y Composer no estaban instalados en el host. Se han proporcionado dentro de la imagen propia `erronk2d-php:local`: PHP 8.4.25 y Composer. No se instalaron paquetes globales.
- Usuario de trabajo `deploy`, con acceso a Docker; proyecto `/home/deploy/projects/erronk2d`. No se cambian propietarios ni permisos de otras aplicaciones.
- Servicios generales observados incluyen Docker/containerd y SSH. La pertenencia al grupo Docker permite operaciones privilegiadas: las instrucciones de este proyecto limitan su uso a recursos propios.
- No se pudo verificar completamente configuración reservada a root, firewall ni tareas cron de root sin credenciales sudo. No se presume que no existan reglas o tareas adicionales.

## Aplicaciones y despliegue existente

| Aplicación o servicio | Despliegue | Acceso y datos |
| --- | --- | --- |
| Gateway Caddy 2.11.4 | Compose `web-gateway`, `/home/deploy/projects/gateway` | Red del host, 80/443; volúmenes propios para certificados y configuración |
| API athletes-pair-match | Compose en `/home/deploy/projects/athletes-pair-match`, contenedor `athletes-pair-match-api-1` | Python 3.11/FastAPI, `127.0.0.1:8000`; publicado mediante el gateway |
| PostgreSQL existente | Contenedor `athletes-pair-match-postgres-1`, PostgreSQL 16 | Red privada y volumen exclusivo de la aplicación existente |

El Caddyfile importa `/etc/caddy/sites/*.caddy`. El único sitio observado era `athletes.caddy`, para `https://2.28.118.113`, con proxy a `127.0.0.1:8000`. Su límite de cuerpo de 16 KB pertenece únicamente a ese sitio; no se debe copiar a Erronk2D, que admite importaciones de 2 MB.

Caddy administra automáticamente TLS. El sitio por IP utiliza el perfil ACME de corta duración de Let's Encrypt; en la inspección se observó un certificado con vencimiento el 13 de septiembre de 2026. No se copiaron ni expusieron claves privadas. Mantener intactos los volúmenes y la renovación del gateway.

Los despliegues existentes se realizan mediante Docker Compose y reinicio automático `unless-stopped`. No se encontró una integración continua que autorice modificar automáticamente el gateway. No se ha acreditado una política de copias periódicas probada para todos los datos existentes; eso requiere una revisión específica de sus responsables.

## Dominio y conflictos

`erronk2d.jonvadillo.com` no aparecía en la configuración local inspeccionada. La resolución inicial devolvió NXDOMAIN. El dominio raíz existe y apunta a otro destino: no debe modificarse.

No se encontró conflicto con el puerto 8082 antes de iniciar la vista previa. Actualmente está ocupado por **la propia demostración de Erronk2D**, en loopback. Producción deberá sustituir esa vista previa o usar otro puerto local y ajustar solo su nuevo sitio Caddy.

## Impacto de cambios globales

| Cambio | Qué podría verse afectado | Alternativa específica |
| --- | --- | --- |
| Cambiar/reiniciar Caddy | HTTPS y API de athletes-pair-match | Añadir un único sitio Erronk2D, validar configuración completa y recargar solo tras autorización |
| PHP o paquetes globales | Dependencias y futuros procesos del host | Imagen PHP propia, sin instalación ni actualización global |
| PostgreSQL compartido | Datos, cuentas y disponibilidad de la API existente | PostgreSQL Erronk2D con credenciales, red y volumen propios |
| Docker daemon, redes o limpieza global | Todos los contenedores y volúmenes | Compose con nombre exclusivo; nunca ejecutar `docker system prune` ni borrar recursos ajenos |
| Certificados o volúmenes Caddy | Certificado actual y renovación automática | Caddy emitirá otro certificado para el nuevo hostname |
| Firewall o puertos 80/443/8000 | Acceso web, API o SSH | Conservarlos; publicar Erronk2D únicamente en `127.0.0.1:8082` detrás del gateway |
| Actualizar versiones del sistema | Compatibilidad y reinicios de todos los servicios | Versionar dependencias e imágenes propias y probar antes de sustituirlas |
| Exceso de RAM/disco/CPU | Degradación de todas las aplicaciones | Límites de contenedores, compilaciones controladas, logs rotados y seguimiento de capacidad |

Los contenedores comparten kernel y recursos físicos: el aislamiento reduce el alcance de cambios, pero no elimina el riesgo de agotamiento de recursos. Las copias del propio proyecto no respaldan aplicaciones ajenas.

## Cambios propios autorizados durante desarrollo

Se creó el proyecto Laravel/Vue, dependencias locales y control de versiones Git. Se construyeron imágenes con nombres `erronk2d-*`, una vista previa privada con datos ficticios y un entorno PostgreSQL temporal de pruebas. Se descargó una imagen de navegador para verificar la interfaz. No se cambiaron DNS, firewall, gateway, certificados, datos ajenos ni servicios globales.

El despliegue propuesto se describe en [despliegue.md](despliegue.md). Añadir el sitio al gateway compartido sigue requiriendo autorización porque una configuración inválida podría afectar al servicio existente.
