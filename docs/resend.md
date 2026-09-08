# Correo mediante Resend

Se usa el transporte API nativo de Laravel y `resend/resend-php` 1.13.0. La clave se guarda solo en `.env.production` (600), ignorada por Git y por las imágenes Docker. No se necesita SMTP.

Dominio Resend: `erronk2d.jonvadillo.com`, región `eu-west-1`, id `8ecf5fd6-892f-4fb5-89f1-0798c6b783bf`.
Remitente definitivo: `Erronk2D <no-reply@erronk2d.jonvadillo.com>`.
Hasta verificar el dominio, el remitente de prueba `onboarding@resend.dev` solo permite enviar a la dirección propietaria de la cuenta Resend (`jvadillo@egibide.org`).

## DNS requerido

Añadir en la zona **jonvadillo.com** del proveedor DNS (GoDaddy). Los nombres siguientes son relativos a esa zona. No cambiar el registro A de la aplicación ni los MX del dominio raíz.

| Tipo | Nombre | Valor | Prioridad | TTL |
| --- | --- | --- | --- | --- |
| TXT | `resend._domainkey.erronk2d` | `p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDQM6EmpF0eRv0FkbaPay0ESraqeoAnhKcWaRtx/CJX2IQfldtMNBS2hZ8sEx6opXZFDyiuGu8HGm7T6kEgQVFtxHYFlqAPIxQcEzufrYj+kaPIO9/CtgneUxlnPC9D7AKNYD8hoIkGHzszas9Of1o41Zn1+M6fckzNiu57atRaRwIDAQAB` | — | 600 o predeterminado |
| MX | `send.erronk2d` | `feedback-smtp.eu-west-1.amazonses.com` | 10 | 600 o predeterminado |
| TXT | `send.erronk2d` | `v=spf1 include:amazonses.com ~all` | — | 600 o predeterminado |

La API devolvió estos valores exactos; también están en `docs/resend-dns.json`. La clave DKIM de la tabla es pública: no es la API key.

Tras crear los registros: solicitar verificación del dominio en Resend, consultar hasta estado `verified`, sustituir `MAIL_FROM_ADDRESS` en `.env.production` por el remitente definitivo y recrear únicamente el contenedor app de Erronk2D. Verificar una recuperación real desde la aplicación.

Referencias: [transporte Laravel](https://laravel.com/docs/13.x/mail#resend-driver), [verificación de dominio Resend](https://resend.com/docs/dashboard/domains/introduction).
