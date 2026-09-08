# Estado del proyecto y siguiente fase

Fecha de revisión: 8 de septiembre de 2026. `prompt.md` se conserva sin modificaciones y se interpreta junto con las aclaraciones recogidas en `decisiones.md`.

## Punto de partida y trabajo retomado

La inspección inicial encontró la especificación, sin aplicación ni repositorio Git. Antes de la interrupción ya estaban creados el dominio académico, la interfaz Laravel/Inertia/Vue, las migraciones, datos ficticios y 17 pruebas PHP. La revisión retomada comprobó ese estado antes de continuar.

Se completaron la preparación de Laravel Boost requerida por `AGENTS.md`, pruebas con PHP 8.4/PostgreSQL, pruebas de navegador, imágenes de producción, configuración aislada y documentación. Se corrigieron precisión sin reparto, guardados consecutivos, validación de notas ausentes, signos positivos de defensas, formularios de organización y errores de importación Excel. El repositorio Git local permite revisar todos los archivos; no se ha publicado código ni configurado un remoto.

## Correspondencia con la especificación

| Área | Resultado implementado | Aclaración o alcance |
| --- | --- | --- |
| Cursos, clases y módulos | Gestión de cursos con Evaluaciones configurables, clases/matrículas y varios responsables por módulo | Los retos conservan participantes propios. Edición estructural de cursos y clases con retos restringida |
| Personas y acceso | Administrador, profesorado y alumnado; permisos de escritura; recuperación; cuentas activas/inactivas | Sin registro público. Primera cuenta por comando interactivo; SMTP pendiente |
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
| Despliegue | Imágenes propias, Compose aislado, sitio Caddy propuesto y copias | Sin activación pública, DNS ni modificaciones del gateway |

## Verificación

Resultado final: **34 pruebas PHP, 273 comprobaciones**, tanto en SQLite como en PostgreSQL; **3 pruebas de navegador** correctas. Compilación de la interfaz y configuración de imágenes verificadas.

Las pruebas PHP cubren cálculo exacto, ausencia frente a cero, reparto atómico, agregación de defensas en todos los módulos, permisos, consenso transversal, acceso del alumnado, publicación/reapertura, histórico de rúbricas/equipos, conflictos de edición, Evaluaciones ponderadas, acceso/recuperación, configuración, creación de administrador e importación CSV/XLSX.

Las tres pruebas de navegador cubren matriz y teclado con guardados consecutivos, acceso del alumnado y móvil, y edición/cambio de formularios de organización. Los datos utilizados son ficticios. No se ha realizado una prueba de carga ni un ensayo con un centro usando datos reales.

También se verifican compilación TypeScript/Vue, formato PHP, configuración Compose y sintaxis de Nginx/PHP-FPM. La prueba integrada de las imágenes de producción ha verificado el acceso HTTP 200, la conexión a PostgreSQL, cookies Secure/HttpOnly, recursos con URLs HTTPS y bloqueo de archivos ocultos. Ha utilizado red interna y PostgreSQL temporal, sin publicar puertos. Se ha retirado ese entorno al terminar; se conserva la demostración privada.

## Fases realizadas y siguientes

1. **Auditoría y decisiones:** realizadas; infraestructura y reglas funcionales documentadas.
2. **Base y dominio académico:** realizados; migraciones, permisos y cálculos con precisión exacta.
3. **Flujos docentes y alumnado:** implementados y probados en la demostración.
4. **Publicación, informes e importación:** implementados dentro del alcance indicado.
5. **Preparación operativa:** imágenes, configuración, procedimiento de copias y reversión preparados; activación y prueba externa de copias pendientes.
6. **Validación del centro:** revisar la demostración con el profesorado y acordar el primer curso real. Confirmar SMTP y cuenta administradora.
7. **Despliegue público autorizado:** sustituir vista previa por producción propia, migrar base vacía, crear cuenta inicial, añadir DNS/sitio Caddy, validar HTTPS y comprobar de nuevo la aplicación existente.

## Riesgos y límites pendientes

- VPS compartido, sin swap. Los límites de memoria reducen riesgo, pero todavía hace falta medir carga concurrente real. Las auditorías conservan estados completos de las notas y aumentan el tamaño de la base; observar crecimiento antes de decidir retención.
- Cada guardado exige la revisión actual. Los cambios concurrentes de otras personas se rechazan para evitar pérdidas; hay que actualizar y volver a introducir el cambio. La interfaz conserva las ediciones locales pendientes mientras muestra el error.
- La corrección estructural de matrículas/cursos o equipos ya evaluados está restringida. Si se necesita, debe diseñarse como una operación explícita que preserve snapshots y publicaciones.
- SMTP, política de copias externas, restauración operativa, supervisión TLS y datos de la cuenta inicial no están configurados. El seeder y sus cuentas no son adecuados para producción.
- Las instantáneas publicadas permanecen conservadas. Los informes generales de curso usan los datos actuales como seguimiento, por lo que una corrección autorizada puede cambiar el resultado provisional hasta una nueva publicación.
- No se ha instalado un gestor de procesos o scheduler global; la ejecución permanente propuesta utiliza exclusivamente reinicio automático de los contenedores Erronk2D.

El plan de activación y las autorizaciones concretas están en [despliegue.md](despliegue.md).
