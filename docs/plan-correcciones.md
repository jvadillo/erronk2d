# Plan de correcciones y próximos pasos

Actualizado el 8 de septiembre de 2026. Estas mejoras están **planificadas, no implementadas**. Complementan `prompt.md` y las aclaraciones del usuario; no cambian los cálculos académicos.

## Diagnóstico confirmado

- El alta de personas y su formulario exigen actualmente 12 caracteres.
- La consulta de producción, limitada a recuentos, encontró una clase sin matrícula y un reto sin participantes ni equipos. El selector de integrantes usa los participantes del reto: por eso aparece vacío. El alta de estudiante no lo matricula, el reto copia la matrícula al crearse y la edición de matrícula queda bloqueada si existen retos.
- Guardar equipos vacíos dispara `teams.*.students` con `required|array|min:2|max:5`; falta explicar el problema en español y junto al equipo afectado. El mensaje sobre cambios pendientes no distingue validación de conflicto de revisión.
- El bloqueo de Evaluaciones se aplica a toda la edición del curso, incluido su nombre. El guardado actual también elimina y recrea Evaluaciones, algo que no debe suceder al renombrar.
- La relación actual entre personas y clases admite varias clases por estudiante. Importación y alta permiten estudiantes sin clase. La pestaña Personas mezcla todos los roles.

## 1. Matrícula única y reparación del flujo de equipos — prioridad alta

1. Exigir una clase al crear estudiantes, también en CSV/XLSX; mostrar su selección en la previsualización de importación. Si no existen clases, explicar cómo crear una antes del alta.
2. Propuesta de modelo: una referencia a la clase actual por estudiante, con clave foránea; mantener las relaciones múltiples del profesorado y los participantes históricos de cada reto. Evitar dos fuentes de verdad para la matrícula estudiantil.
3. Antes de migrar, detectar estudiantes sin clase o con varias. Conservar las asociaciones inequívocas y presentar los casos ambiguos para su asignación expresa; no inventar matrículas ni eliminar históricos. El estudiante existente sin clase deberá asignarse desde el flujo de corrección.
4. Permitir actualizar la matrícula actual de una clase aunque tenga retos. Un cambio de clase no trasladará automáticamente notas ni participantes de retos anteriores. Se entiende «una clase» como una clase actual; el historial se conserva en los retos.
5. Para el reto vacío existente, ofrecer una acción explícita y auditada que incorpore estudiantes de su clase mientras no haya evaluaciones, repartos ni publicaciones. No sincronizar silenciosamente retos ya evaluados. Verificar estas condiciones dentro de la transacción y conservar el control de revisión.
6. Mostrar un estado vacío útil en Equipos y desactivar el guardado cuando falten integrantes. Mantener equipos de 2–5 estudiantes y exclusividad por reto; presentar errores por equipo en español, conservando las selecciones introducidas. Diferenciar estos errores de una revisión desactualizada.

**Aceptación:** alta con clase obligatoria; rechazo de matrícula doble y clase inexistente; importación atómica; traslado conserva historial; reparación explícita de reto vacío; alta de dos equipos válidos; rechazo de duplicados, ajenos al reto, 0/1/6 integrantes y cambios después de evaluar. Reproducir el caso original en una base aislada antes de corregirlo.

## 2. Contraseñas de 10 caracteres y nombre del curso

- Unificar el mínimo en 10 caracteres en altas, cambios y recuperación de contraseñas, interfaz y comando de primera cuenta administradora. Mantener el máximo actual y permitir dejar vacía una contraseña al editar para conservarla. No alterar contraseñas existentes ni reducir las aleatorias de prueba.
- Separar renombrado del curso y modificación de sus Evaluaciones. Guardar el nombre sin borrar/recrear periodos, conservando identificadores, orden, retos y pesos. Mostrar las Evaluaciones bloqueadas cuando tengan retos y rechazar cambios estructurales también en el servidor.

**Aceptación:** contraseña de 9 rechazada, 10 y 11 aceptadas, contraseña vacía de edición conservada; renombrado con retos correcto, mismos identificadores de Evaluación e histórico; nombre duplicado y modificación de periodos bloqueados sin cambios parciales.

## 3. Pestañas Profesor y Estudiante

- Sustituir Personas por **Profesor** y **Estudiante**, con sus botones de alta y permisos existentes.
- Mostrar la clase actual en la tabla de estudiantes y en su edición; permitir resolver matrículas pendientes sin ocultarlas.
- Conservar los permisos del profesorado y su capacidad de trabajar en varias clases. La cuenta administradora no se contará como profesor ficticio; conservar su identificación en el acceso/perfil.

**Aceptación:** cada pestaña muestra el rol correcto; la clase se actualiza al guardar; las restricciones de acceso se aplican tanto en interfaz como en servidor; revisión en móvil.

## 4. Datos ficticios persistentes en producción

- Crear un comando específico, repetible y limitado a datos identificados explícitamente como prueba. **40 estudiantes de prueba y 10 profesores de prueba**, además de las cuentas creadas manualmente y la administradora.
- Propuesta inicial: dos clases ficticias de 20 estudiantes, módulos y responsables suficientes para probar equipos y evaluaciones. Usar identificadores reservados y cuentas claramente ficticias; no enviar invitaciones ni correos automáticamente.
- Ejecutarlo después de las migraciones de cada despliegue durante esta etapa. Repetirlo no duplicará personas, reiniciará contraseñas ni sobrescribirá notas o asignaciones de prueba ya editadas. Si se eliminan cuentas ficticias, reponer únicamente las necesarias para alcanzar los recuentos acordados.
- No utilizar `migrate:fresh` ni el seeder actual: este requiere una base vacía local/de pruebas. Evitar mezclar estos datos con la clase y el reto creados manualmente.
- Preparar identificación suficiente para una limpieza futura revisable; ejecutarla solo cuando el usuario la solicite, teniendo en cuenta referencias e histórico.

**Aceptación:** 40/10 cuentas ficticias activas tras primera y segunda ejecución; clase única de cada estudiante; conservación de cuentas manuales, contraseñas, notas y publicaciones; reposición sin duplicados y rechazo de colisiones con cuentas ajenas al conjunto ficticio.

## 5. Verificación y publicación de las correcciones

1. Pruebas de regresión por bloque en bases SQLite/PostgreSQL aisladas; formato PHP y compilación Vue cuando cambien esos archivos. Commits pequeños con comportamiento y validación descritos.
2. Adaptar el entorno de navegador para que no pueda apuntar a producción por accidente: la suite actual usa 8082, ahora ocupado por producción. Usar una instancia y un puerto de pruebas propios con comprobación explícita del entorno ficticio antes de realizar escrituras.
3. Revisar en navegador alta con clase, pestañas, renombrado y equipos. Los tests no deben modificar datos de producción.
4. Construir imágenes con etiqueta de commit, copiar los datos propios y ensayar las migraciones. Desplegar únicamente Erronk2D, ejecutar la carga ficticia repetible y verificar 40/10, acceso HTTPS y salud de la API vecina mediante lecturas.
5. Conservar imágenes y copia anteriores. Una restauración sobre producción exige autorización específica porque perdería cambios posteriores; no automatizarla ante cualquier error.

## Pendientes operativos anteriores

- **Completado:** aplicación pública por HTTPS; Caddy específico; PostgreSQL aislado; administrador inicial; API Resend; correo previo confirmado como entregado. Dominio Resend verificado y remitente definitivo aplicado. Copia local de producción creada.
- **Pendiente:** comprobar una entrega desde el remitente definitivo en la siguiente recuperación solicitada; el envío confirmado anterior utilizó el remitente provisional. No repetir envíos solo para reanudar una sesión.
- Ensayar restauración en una base aislada y acordar destino externo cifrado, retención y frecuencia de copias. La copia local no protege contra pérdida del VPS.
- Medir carga concurrente, vigilar espacio/crecimiento de auditoría y renovación TLS. No instalar ni cambiar servicios globales como parte de estas correcciones.
- Exportación XLSX y PDF generado en servidor permanecen como ampliaciones futuras; actualmente hay CSV e impresión PDF.

## Siguiente acción concreta

Reproducir en un test aislado el flujo «estudiante sin matrícula → reto vacío → equipos sin integrantes», y preparar la migración a clase actual única junto con la reparación explícita del reto vacío. Leer primero `PROGRESS.md` y las reglas aplicables; no repetir la auditoría del VPS.
