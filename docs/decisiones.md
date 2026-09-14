# Decisiones de Erronk2D

Fuente funcional: `prompt.md`, junto con las aclaraciones del usuario de 2026-09-07. Las aclaraciones posteriores prevalecen donde simplifican el documento original.

## Aclaraciones aceptadas

- Un curso académico tiene un número configurable de **Evaluaciones** (por ejemplo, dos o tres).
- Cada reto pertenece a una Evaluación y tiene un peso positivo. La nota de la Evaluación por módulo es la media ponderada de los retos participantes en ese módulo. Los pesos son relativos: se dividen por su suma, sin exigir que sumen 100. Los retos ajenos al módulo no cuentan como cero.
- La nota del curso por módulo es la media aritmética de sus Evaluaciones. Una Evaluación incompleta mantiene pendiente la nota del curso; no se omite silenciosamente.
- Un único examen por estudiante, módulo y reto. No se implementa un catálogo de actividades múltiples.
- Una única defensa por estudiante, módulo y reto. Cada módulo tiene defensa activada por defecto, configurable para ese reto. Una defensa pendiente es distinta de una defensa de cero.
- Los profesores responsables de un módulo en una clase comparten las calificaciones. No se promedian varias notas de profesores para un mismo registro. Se conserva quién lo modificó y cuándo. La decisión del 14 de septiembre sustituye los permisos individuales por pertenencia a clase y responsabilidad de módulo.
- Cada profesor solo consulta clases donde es propietario o miembro, con sus estudiantes, retos y notas, dentro del curso académico seleccionado. La decisión del 14 de septiembre sustituye la anterior visibilidad docente global.
- El reparto es opcional por reto. Si está desactivado, cada estudiante parte de la nota del equipo. Si está activado, el profesor registra conjuntamente el reparto acordado presencialmente y se valida la suma antes de guardar.
- Todas las defensas se acumulan sobre esa base individual para obtener **una única nota final por estudiante y reto**, usada en todos los módulos.
- La nota final del reto se limita por defecto a 0–10; el límite es configurable y se conserva el resultado bruto.
- No se convierten datos ausentes en cero ni se renormalizan componentes pendientes.
- La publicación conserva una instantánea; una corrección exige reapertura autorizada y motivo. No modifica publicaciones históricas.

## Precisión

Las notas introducidas admiten hasta cuatro decimales; la presentación ordinaria utiliza dos. Los cálculos de dominio utilizarán números racionales exactos, y se redondeará únicamente al presentar o materializar un resultado. La nota de equipo utilizada como presupuesto de reparto se cuantiza explícitamente a cuatro decimales, conservando también el cálculo de rúbrica exacto. La suma del reparto se compara exactamente contra ese presupuesto. Se muestra la precisión completa al repartir.

## Infraestructura y alcance autorizado

Desarrollo autorizado en `/home/deploy/projects/erronk2d`. Las dependencias de PHP se ejecutarán dentro de contenedores propios. No se cambian paquetes globales, servicios, firewall ni aplicaciones existentes.

El despliegue público fue autorizado posteriormente y está activo en erronk2d.jonvadillo.com. Las actualizaciones se limitan al Compose de Erronk2D, con copia previa; no autorizan cambios globales en el VPS.

La auditoría encontró Caddy compartido en 80/443, FastAPI en 127.0.0.1:8000 y PostgreSQL privado de athletes-pair-match. Erronk2D usará recursos propios y un puerto local separado. No se ejecutarán limpiezas globales de Docker.

## Aclaraciones adicionales confirmadas

- Los estudiantes mantienen su acceso para autoevaluación y coevaluación. No intervienen en el registro del reparto.
- La evaluación transversal del profesorado es **única por estudiante, criterio y reto**, sin módulo. Cualquier profesor autorizado puede completarla. Los docentes consensúan los valores fuera de la aplicación. No se promedian notas entre profesores ni entre módulos.

## Acceso con Google solicitado el 9 de septiembre de 2026

- **Trabajo aplazado expresamente por el usuario el 10 de septiembre.** Conservar la integración y configuración privada; no continuar verificaciones OAuth ni solicitar cambios en Google Cloud hasta que indique retomarlo. El error conocido es `redirect_uri_mismatch`.
- El usuario solicita inicio de sesión y registro mediante Google, manteniendo el acceso con contraseña.
- Criterio aplicado mientras se espera respuesta a la consulta: los registros nuevos son solicitudes pendientes; solo el administrador puede aprobarlas y asignar rol/clase. No se conceden permisos por el dominio del correo ni por datos enviados desde el navegador.
- Vincular una cuenta existente requiere confirmar una vez su contraseña local. No se fusionan cuentas ni se cambia su historial. Una cuenta desactivada no obtiene acceso mediante Google.
- Sin dependencias adicionales ni modificaciones de infraestructura compartida. Credenciales OAuth proporcionadas por el usuario y aplicadas de forma privada; integración habilitada. Falta autorizar la URI de retorno en Google Cloud y completar una prueba real con una cuenta; instrucciones en despliegue.md.

## Navegación solicitada el 10 de septiembre de 2026

- El lateral se contrae parcialmente a iconos; el navegador conserva la preferencia. Los enlaces siguen teniendo nombres accesibles y ayudas al pasar el cursor.
- Organización agrupa un submenú de páginas con URL, título e historial propios: Cursos académicos, Clases, Profesor, Estudiante, Módulos, Biblioteca de rúbricas y Solicitudes. Comparten formularios sin duplicarlos; conservan los permisos existentes y Solicitudes solo es accesible para administración.
- En móvil se mantiene la barra inferior y Organización abre su submenú. La navegación por teclado permite cerrar el submenú con Escape.

## Arquitectura académica confirmada el 14 de septiembre de 2026

Estado: aclaraciones funcionales cerradas el 14 de septiembre; plan de implementación al final de esta sección. No implementado ni aplicado a producción.

- **Curso académico** (2025-26) y **curso/nivel** (1.º, 2.º) son conceptos distintos. Clases, matrículas, retos y su actividad pertenecen a un curso académico; cada año empieza desde cero sin traslado automático. Cuentas, ciclos, módulos y biblioteca de rúbricas son estables entre años.
- Solo administración crea cursos académicos, los cierra y los reabre. Todos los profesores activos pueden acceder a cualquier curso abierto y crear clases sin asignación previa. Los cursos cerrados permiten consulta del histórico y bloquean creación/edición académica; corregir requiere reapertura administrativa.
- Primer acceso docente: seleccionar automáticamente el curso abierto creado más recientemente. Accesos posteriores: recuperar el último utilizado, persistido en el usuario. Selector siempre visible en la parte superior del lateral para cambiar sin cerrar sesión. Si no hay ningún curso disponible, mostrar aviso de espera de asignación administrativa; no exigir asignación docente cuando sí existe un curso abierto.
- El curso seleccionado delimita transversalmente consultas y formularios. Administración puede gestionar cuentas, cursos y catálogos globales sin selección; para clases/notas necesita curso. Estudiantes trabajan con un curso seleccionado de entre aquellos donde tienen matrícula, incluidos los cerrados para consultar resultados.
- Cada clase anual pertenece a un único ciclo y nivel y tiene un profesor propietario. Un profesor solo ve clases propias o a las que se le ha añadido. Administración puede transferir la propiedad. Retirar a un docente revoca su acceso sin borrar autoría ni calificaciones anteriores.
- Propietario y administrador gestionan docentes y datos de la clase. Todos los docentes de la clase pueden matricular estudiantes, crear/editar retos, gestionar equipos y publicar resultados. Se eliminan los permisos individuales.
- Ciclos y módulos son catálogos estables administrados solo por administración. Un módulo pertenece a un ciclo y nivel; todas las clases de ese ciclo/nivel disponen de sus módulos automáticamente. Cada reto pertenece a una clase y selecciona un subconjunto de esos módulos; no combina clases, ciclos ni niveles.
- Los profesores responsables de módulos se asignan en el contexto anual de la clase. Solo los responsables evalúan exámenes, defensas y criterios de rúbrica de su módulo. Todos los docentes de la clase pueden evaluar criterios generales y transversales.
- Un estudiante puede tener varias matrículas en distintas clases del mismo curso académico. Incorporarlo a otra clase no arrastra notas; el histórico de la clase de origen se conserva. Por defecto cursa todos los módulos de cada clase: no se gestionan matrículas parciales por módulo. El usuario admite dejar la nota vacía si no cursa un módulo o señalarla como «No matriculado». Criterio propuesto para la implementación: marca explícita no numérica en la evaluación, diferenciada de pendiente y de cero; nunca inferirla automáticamente por faltar notas.
- El profesor puede crear cuentas nuevas de estudiante y matricular cuentas existentes mediante búsqueda por correo exacto, sin listado global. Administración conserva gestión y edición de cualquier cuenta.
- Las rúbricas docentes son privadas y se pueden compartir con otros profesores para usar y copiar, sin conceder edición del original. Sus criterios pueden ser generales o corresponder a módulos del ciclo y nivel de la clase del reto. Los docentes de la clase ven la copia utilizada durante el reto. Editar la biblioteca no altera las copias congeladas de los retos existentes.
- Evaluaciones comunes por curso académico. El histórico conserva los nombres originales de ciclos y módulos aunque el catálogo cambie después.
- Transición final solicitada: «Borra todo de cero excepto estudiantes y profesores de ejemplo». Sustituye la conservación inicial de todas las cuentas y catálogos: reiniciar cursos académicos, Evaluaciones, clases, módulos, rúbricas y toda la actividad asociada, retirando también las cuentas docentes/estudiantiles que no sean de ejemplo. Conservar las cuentas de ejemplo con sus credenciales; preservar el acceso administrador para poder configurar la aplicación vacía. La limpieza se prepara como operación inicial expresa, con copia previa, sin afectar configuración de servicios ni copias privadas.

### Plan de implementación

1. **Modelo de datos.** Separar catálogo estable de ciclos y módulos (con nivel) de clases anuales. Añadir propietario de clase, docentes miembros y responsabilidades de módulo por clase. Sustituir users.classroom_id por matrículas múltiples con baja conservada para consulta histórica. Añadir estado abierto/cerrado de año y último año usado en usuario. Rúbricas con propietario y permisos de uso/copia; referencias estables y copias de nombres académicos para proteger el histórico.
2. **Contexto y autorización.** Resolver el año al entrar y al cambiarlo, revalidando disponibilidad; recuperar el último permitido o usar el abierto creado más recientemente. Años cerrados accesibles solo en consulta. Aplicar el ámbito y las políticas de clase/módulo a todas las lecturas y escrituras, identificadores de URL/formularios, informes, exportaciones e importaciones. Separar rutas globales de administración. Retirar permisos individuales; revocar acceso al retirar docentes. Una pestaña con contexto antiguo no debe guardar datos bajo otro año tras cambiar el selector.
3. **Organización y matrícula.** Administración mantiene cuentas, años, Evaluaciones, ciclos y módulos. Profesor crea clase desde el año seleccionado e indica ciclo/nivel; módulos disponibles automáticamente. Propietario/admin editan clase y docentes; responsabilidades de módulos se asignan entre miembros. Profesor crea estudiantes o busca por correo exacto para matricularlos; adaptar también importaciones para reutilizar cuentas existentes y admitir varias clases, sin revelar listados globales ni trasladar notas.
4. **Retos, rúbricas y evaluación.** Retos limitados a una clase y sus módulos, participantes procedentes de su matrícula y conservación de históricos tras las bajas. Biblioteca privada y rúbricas compartidas para uso/copia con filtrado de criterios compatibles. Evaluación por responsables de módulo; generales/transversales por cualquier miembro. Copias de rúbrica y nombres inmutables respecto a cambios posteriores de catálogo. Nota vacía sigue siendo pendiente; tratamiento explícito de «No matriculado» sin gestionarlo como matrícula parcial ni convertirlo en cero. Cubrir su interacción con cálculos, publicación e informes antes de dar por terminada la evaluación.
5. **Interfaz.** Selector persistente arriba del lateral, visible también en móvil/lateral plegado; indicar año cerrado. Navegación contextual a clases, estudiantes, retos y resultados; páginas globales administrativas para cuentas/catálogos. Formularios heredan el año activo, evitan opciones ajenas y conservan errores y cambios pendientes. Estados vacíos con acciones según rol: administrador configura el primer año/catálogo, docente espera si no hay año disponible y estudiante espera matrícula.
6. **Verificación por bloques.** Pruebas de aislamiento entre dos años, dos clases y varias cuentas, incluidos accesos directos, escrituras manipuladas, importaciones y exportaciones. Comprobar cierre/reapertura, cambio de año con pestañas antiguas, propiedad/revocación, matrícula múltiple/bajas, responsabilidad de módulo, privacidad/copia de rúbricas y congelación del histórico. Regresión de cálculos y «No matriculado», publicación y autenticación. SQLite/PostgreSQL y navegador ficticio aislado; Pint y compilación Vue/TypeScript para cambios correspondientes. Leer las reglas/skills aplicables antes de editar código o pruebas.
7. **Reinicio y entrega.** Preparar una operación inicial de limpieza independiente de las migraciones ordinarias, limitada a Erronk2D y verificable por recuentos. Conservar administrador y cuentas marcadas como ejemplo sin cambiar credenciales, borrar datos académicos y cuentas docentes/estudiantiles restantes, y limpiar referencias/sesiones antiguas. Revisar ops/deploy y erronk2d:demo: ahora recrean curso/clases/módulos/rúbricas; deben dejar de reponer actividad académica. Ensayar transición con datos ficticios, guardar copia privada previa, publicar imágenes versionadas y comprobar acceso y aplicación vacía. No ejecutar limpieza antes de que la nueva versión esté preparada y verificada.

Orden de entrega: modelo/contexto → permisos/organización → retos/rúbricas/notas → interfaz y regresión → reinicio/despliegue. Commits revisables con progreso actualizado. La integración externa de Google sigue aplazada; solo adaptar los puntos internos afectados por matrícula/contexto sin retomar pruebas OAuth reales.
