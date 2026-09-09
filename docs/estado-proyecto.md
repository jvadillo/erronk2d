# Estado del proyecto y siguiente fase

Fecha de revisión: 9 de septiembre de 2026. `prompt.md` se conserva sin modificaciones y se interpreta junto con las aclaraciones recogidas en `decisiones.md`.

## Punto de partida y trabajo retomado

La inspección inicial encontró la especificación, sin aplicación ni repositorio Git. Antes de la interrupción ya estaban creados el dominio académico, la interfaz Laravel/Inertia/Vue, las migraciones, datos ficticios y 17 pruebas PHP. La revisión retomada comprobó ese estado antes de continuar.

Se completaron la preparación de Laravel Boost requerida por `AGENTS.md`, pruebas con PHP 8.4/PostgreSQL, pruebas de navegador, imágenes de producción, configuración aislada y documentación. Se corrigieron precisión sin reparto, guardados consecutivos, validación de notas ausentes, signos positivos de defensas, formularios de organización y errores de importación Excel. El repositorio Git local permite revisar todos los archivos; no se ha publicado código ni configurado un remoto.

## Correspondencia con la especificación

| Área | Resultado implementado | Aclaración o alcance |
| --- | --- | --- |
| Cursos, clases y módulos | Cursos con Evaluaciones, clase actual única por estudiante y varios responsables por módulo | Renombrado de cursos sin recrear periodos. Cambiar matrícula actual conserva los participantes históricos de los retos |
| Personas y acceso | Administrador, profesorado y alumnado; permisos de escritura; recuperación; cuentas activas/inactivas | Sin registro público. Primera cuenta creada; API Resend configurada, dominio verificado |
| Retos | Clase/Evaluación, módulos, peso, fechas, estados, opciones y rúbricas copiadas | Las rúbricas y módulos se eligen al crear; cambios posteriores de esas vinculaciones no incluidos |
| Equipos | 2–5 integrantes, exclusividad por reto, composición independiente | Reorganización bloqueada después de empezar evaluaciones |
| Rúbricas | Criterios, pesos, niveles, GENERAL/módulo para equipo; edición, ordenación y duplicado de plantillas | Cada reto conserva su copia; transversales sin módulo |
| Nota de equipo y reparto | Cálculo ponderado, presupuesto exacto, validación atómica del reparto | Reparto opcional introducido por el profesor; alumnado no reparte en la app |
| Defensas | Una por estudiante/módulo/reto, opcional, fecha, responsable, notas | Todas se suman/restan sobre la misma nota individual; no existen notas de reto por módulo |
| Exámenes | Una nota por estudiante/módulo/reto, edición individual o masiva | Sustituye las múltiples actividades/exámenes del texto inicial, por decisión del usuario |
| Transversales | Autoevaluación, compañeros y consenso del profesorado | Profesorado: un registro por estudiante/criterio/reto, sin promedio entre módulos o docentes |
| Matriz | Desglose, filtros, orden, columnas fijas/ocultables, teclado y lotes | Las defensas incluyen diálogo de detalle; las notas de examen se editan en línea |
| Resumen y pendientes | Estudiantes, equipos, módulos, profesores, progreso y errores | Datos ausentes permanecen pendientes; cero es una calificación real |
| Publicación e histórico | Instantáneas inmutables, motivo para reabrir, revisión contra sobreescrituras y auditoría | El alumnado ve su resultado publicado. Tras reapertura debe publicarse la corrección |
| Evaluaciones y curso | Retos ponderados dentro de cada Evaluación, media aritmética de Evaluaciones | Cálculos por módulo; no se omiten periodos incompletos |
| Informes | Reto/estudiante/equipo mediante matriz y filtros; módulo, Evaluación y curso con detalle | Vista de seguimiento incluye notas en elaboración; histórico publicado se consulta por reto |
| Intercambio | Importación CSV/XLSX con previsualización; CSV y PDF mediante impresión | Exportación XLSX y PDF generado en servidor quedan como ampliaciones; el texto las pide como preparación futura |
| Despliegue | Imágenes propias, Compose aislado, Caddy específico y copia local | HTTPS público activo; API vecina comprobada |

## Verificación

Versión `504db0b` verificada: **54 pruebas PHP, 420 comprobaciones**, tanto en SQLite como en PostgreSQL; **5 pruebas de navegador** correctas. Compilación de la interfaz y formato PHP verificados.

Las pruebas PHP cubren cálculo exacto, ausencia frente a cero, reparto atómico, agregación de defensas en todos los módulos, permisos, consenso transversal, acceso del alumnado, publicación/reapertura, histórico de rúbricas/equipos, conflictos de edición, Evaluaciones ponderadas, acceso/recuperación, configuración, creación de administrador e importación CSV/XLSX.

Las cinco pruebas de navegador cubren matriz y teclado con guardados consecutivos, acceso del alumnado y móvil, edición/cambio de formularios, alta con contraseña de 10 caracteres y clase, renombrado de cursos, pestañas separadas y recuperación de participantes para guardar equipos. El navegador usa exclusivamente una instancia interna identificada como testing. Los datos son ficticios; no se ha realizado una prueba de carga.

También se verifican compilación TypeScript/Vue, formato PHP, configuración Compose y sintaxis de Nginx/PHP-FPM. La prueba integrada de las imágenes de producción ha verificado el acceso HTTP 200, la conexión a PostgreSQL, cookies Secure/HttpOnly, recursos con URLs HTTPS y bloqueo de archivos ocultos. Ha utilizado red interna y PostgreSQL temporal, sin publicar puertos. Se ha retirado ese entorno al terminar; se conserva la demostración privada detenida.

## Fases realizadas y siguientes

1. **Auditoría y decisiones:** realizadas; infraestructura y reglas funcionales documentadas.
2. **Base y dominio académico:** realizados; migraciones, permisos y cálculos con precisión exacta.
3. **Flujos docentes y alumnado:** implementados y probados en la demostración.
4. **Publicación, informes e importación:** implementados dentro del alcance indicado.
5. **Producción:** actualizada el 9 de septiembre de 2026 en https://erronk2d.jonvadillo.com con las imágenes `504db0b`. PostgreSQL propio, migración aplicada y mantenimiento retirado. HTTPS, recursos y cookies Secure/HttpOnly comprobados; API existente HTTP 200. No repetir bootstrap.
6. **Correo:** SDK Resend 1.13.0 integrado, recuperación en español y manejo de fallos; 7 pruebas específicas de correo/acceso y 57 aserciones correctas. Envío anterior confirmado como entregado; dominio verificado y remitente definitivo aplicado. La entrega con ese nuevo remitente queda por verificar en la próxima recuperación solicitada.
7. **Operación:** copia previa a esta entrega en `backups/production-20260909-504db0b`. La copia PostgreSQL anterior se restauró en una base temporal y admitió la migración nueva conservando sus registros. Archivo de almacenamiento legible; restauración completa de ficheros/aplicación y política de copias externas pendientes.
8. **Correcciones solicitadas:** implementadas y publicadas; criterios en [plan-correcciones.md](plan-correcciones.md). Recuentos verificados: 40 estudiantes y 10 profesores ficticios activos, adicionales a las cuentas manuales; ningún estudiante ficticio sin clase. Corregido también el token CSRF obsoleto tras iniciar sesión.

## Riesgos y límites pendientes

- VPS compartido, sin swap. Los límites de memoria reducen riesgo, pero todavía hace falta medir carga concurrente real. Las auditorías conservan estados completos de las notas y aumentan el tamaño de la base; observar crecimiento antes de decidir retención.
- Cada guardado exige la revisión actual. Los cambios concurrentes de otras personas se rechazan para evitar pérdidas; hay que actualizar y volver a introducir el cambio. La interfaz conserva las ediciones locales pendientes mientras muestra el error.
- Los estudiantes antiguos sin matrícula requieren asignación expresa. Los cambios de clase conservan los retos previos; la incorporación de participantes solo se permite en retos vacíos sin evaluaciones ni publicaciones. Equipos ya evaluados conservan su composición.
- Política de copias externas, restauración completa y supervisión TLS pendientes. El seeder original está limitado a local/testing; producción utiliza exclusivamente `erronk2d:demo` mediante `ops/deploy` para mantener sus cuentas ficticias.
- Las instantáneas publicadas permanecen conservadas. Los informes generales de curso usan los datos actuales como seguimiento, por lo que una corrección autorizada puede cambiar el resultado provisional hasta una nueva publicación.
- No se ha instalado un gestor de procesos o scheduler global; la ejecución permanente propuesta utiliza exclusivamente reinicio automático de los contenedores Erronk2D.

El procedimiento de despliegue y reversión está en [despliegue.md](despliegue.md).
