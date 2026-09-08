Quiero que desarrolles una aplicación web con Laravel para gestionar la metodología de retos colaborativos y su evaluación denominada ERRONK2D.

La aplicación debe sustituir la gestión que actualmente se realiza mediante hojas de cálculo.

Adjunto una captura de la hoja de cálculo que utilizamos actualmente como referencia. No es necesario copiar su diseño, pero sí entender qué información necesitamos gestionar y, sobre todo, mejorar radicalmente la experiencia de uso.

La aplicación debe estar pensada para que los profesores puedan gestionar y evaluar retos de forma rápida, visual y con el menor número de clics posible.

============================================================
1. CONTEXTO DE ERRONK2D
============================================================

Los estudiantes realizan retos de forma colaborativa.

En cada reto trabajan en equipos de entre 2 y 5 estudiantes para resolver un problema o proyecto propuesto por el profesorado.

Los equipos son específicos de cada reto y pueden cambiar entre retos.

Ejemplo:

RETO 1
Equipo 1:
- Alumno A
- Alumno B
- Alumno C

RETO 2
Equipo 1:
- Alumno A
- Alumno D

RETO 2
Equipo 2:
- Alumno B
- Alumno C
- Alumno E

La aplicación debe conservar el histórico de los equipos de cada reto.

============================================================
2. ESTRUCTURA ACADÉMICA
============================================================

La aplicación debe permitir gestionar:

- Cursos académicos.
- Clases/grupos.
- Estudiantes.
- Profesores.
- Módulos/asignaturas.
- Retos.

Una clase pertenece a un curso académico.

Una clase tiene estudiantes y profesores.

Los retos se realizan dentro de una clase.

En cada reto participan uno o varios módulos.

Cada módulo tiene uno o varios profesores responsables.

Un profesor puede ser responsable de varios módulos.

============================================================
3. USUARIOS Y PERMISOS
============================================================

Debe existir al menos:

- Administrador.
- Profesor.
- Estudiante.

El administrador debe poder gestionar toda la información.

Debe poder decidir qué profesores tienen permisos para determinadas tareas.

Por ejemplo:

- Gestionar estudiantes.
- Gestionar profesores.
- Gestionar módulos.
- Gestionar equipos.
- Crear y editar retos.
- Gestionar rúbricas.
- Evaluar retos.
- Evaluar competencias transversales.
- Introducir exámenes.
- Introducir defensas.
- Consultar notas.
- Modificar notas.
- Publicar resultados.

El sistema de permisos debe ser flexible y permitir añadir nuevos permisos en el futuro.

Los profesores no deben poder modificar información para la que no tengan autorización.

Los estudiantes tendrán acceso únicamente a la información y procesos que les correspondan.

============================================================
4. RETOS
============================================================

Los profesores deben poder crear y gestionar retos.

Un reto debe poder definir:

- Nombre.
- Descripción.
- Clase a la que pertenece.
- Curso académico.
- Fecha de inicio.
- Fecha de finalización.
- Estado.
- Módulos participantes.
- Equipos.
- Rúbricas de evaluación.
- Competencias transversales.
- Si existen defensas.
- Configuración de la evaluación.
- Observaciones.

El estado del reto puede evolucionar, por ejemplo:

- Borrador.
- En curso.
- En evaluación.
- Finalizado.
- Publicado.

La aplicación debe permitir consultar fácilmente todos los retos de una clase y acceder rápidamente a su estado de evaluación.

============================================================
5. EQUIPOS
============================================================

Los equipos se crean para cada reto.

Cada equipo debe tener entre 2 y 5 estudiantes.

Un estudiante no puede pertenecer a dos equipos diferentes dentro del mismo reto.

Los equipos pueden cambiar completamente entre retos.

Debe ser muy rápido:

- Crear equipos.
- Añadir estudiantes.
- Quitar estudiantes.
- Cambiar estudiantes.
- Ver los integrantes.
- Consultar el histórico.

La aplicación debe detectar y avisar de configuraciones incorrectas.

============================================================
6. SISTEMA GENERAL DE EVALUACIÓN
============================================================

Por defecto, la nota final de cada módulo se obtiene mediante:

- 30% competencias transversales.
- 40% valoración del reto.
- 30% examen y/o actividades del módulo.

Por tanto:

NOTA FINAL DEL MÓDULO =
    TRANSVERSALES × 30%
    +
    NOTA FINAL DEL RETO × 40%
    +
    EXAMEN/ACTIVIDADES × 30%

Estos porcentajes deben poder configurarse para adaptarse a posibles cambios de la metodología.

La configuración por defecto será 30 / 40 / 30.

============================================================
7. COMPETENCIAS TRANSVERSALES
============================================================

Las competencias transversales representan por defecto el 30% de la nota final.

Esta parte se divide en:

- 10% autoevaluación.
- 60% evaluación del equipo.
- 30% evaluación del profesorado.

Por tanto:

NOTA TRANSVERSALES =
    AUTOEVALUACIÓN × 10%
    +
    EVALUACIÓN EQUIPO × 60%
    +
    EVALUACIÓN PROFESORADO × 30%

Estos porcentajes deben ser configurables.

============================================================
8. RÚBRICAS TRANSVERSALES
============================================================

Debe existir un sistema completo para crear y gestionar las competencias/rúbricas transversales.

Ejemplos:

- Autonomía.
- Trabajo en equipo.
- Implicación.
- Responsabilidad.
- Comunicación.
- Organización.

Cada competencia debe tener:

- Nombre.
- Descripción.
- Peso.
- Diferentes niveles de consecución.
- Una nota asociada a cada nivel.
- Una descripción para cada nivel.

Ejemplo:

AUTONOMÍA

Nivel 1 → 4 puntos
Descripción: ...

Nivel 2 → 6 puntos
Descripción: ...

Nivel 3 → 8 puntos
Descripción: ...

Nivel 4 → 10 puntos
Descripción: ...

El profesor debe poder crear, editar, duplicar y reutilizar estas rúbricas.

============================================================
9. AUTOEVALUACIÓN
============================================================

Cada estudiante debe poder realizar su autoevaluación utilizando las competencias transversales configuradas para el reto.

Debe quedar registrado quién realizó la evaluación, a quién corresponde, cuándo se realizó y qué nivel seleccionó.

============================================================
10. EVALUACIÓN ENTRE COMPAÑEROS
============================================================

Cada estudiante debe evaluar al resto de integrantes de su equipo.

No debe poder evaluarse a sí mismo en esta parte.

Ejemplo:

Equipo:

Alumno A
Alumno B
Alumno C

Alumno A evalúa a B y C.

Alumno B evalúa a A y C.

Alumno C evalúa a A y B.

La aplicación debe calcular posteriormente la valoración recibida por cada estudiante.

Debe quedar registrado quién evaluó a quién.

============================================================
11. EVALUACIÓN DEL PROFESORADO
============================================================

El profesorado debe poder evaluar las competencias transversales de los estudiantes.

Esta evaluación debe poder realizarse de forma muy rápida.

No quiero obligar al profesor a abrir un formulario independiente para cada estudiante.

Debe existir una interfaz tipo matriz que permita evaluar varios estudiantes de manera simultánea.

Ejemplo conceptual:

                    Autonomía   Equipo   Implicación   Responsabilidad

Alumno A                8          9          8              7
Alumno B                9          8          9              8
Alumno C                7          7          8              9

============================================================
12. RÚBRICAS PARA LA VALORACIÓN DEL RETO
============================================================

La valoración del reto se realiza mediante rúbricas.

Debe existir un sistema completo para crear y reutilizar estas rúbricas.

Una rúbrica puede contener varios ítems.

Cada ítem puede pertenecer a:

- Un módulo concreto.
- GENERAL.

Ejemplo:

PROGRAMACIÓN
- Arquitectura.
- Calidad del código.
- Funcionalidad.

DWEC
- Interfaz.
- JavaScript.
- Usabilidad.

DWES
- Backend.
- API.
- Base de datos.

GENERAL
- Presentación.
- Documentación.

Cada ítem tendrá varios niveles.

Ejemplo:

CALIDAD DEL CÓDIGO

Nivel 1
Nota: 4
Descripción: ...

Nivel 2
Nota: 6
Descripción: ...

Nivel 3
Nota: 8
Descripción: ...

Nivel 4
Nota: 10
Descripción: ...

Debe ser posible:

- Crear rúbricas.
- Editarlas.
- Duplicarlas.
- Reutilizarlas.
- Crear ítems.
- Asociar ítems a módulos.
- Crear ítems generales.
- Crear niveles.
- Definir notas.
- Definir descripciones.
- Ordenar ítems.
- Configurar pesos si se considera necesario.

============================================================
13. RÚBRICAS REUTILIZABLES E HISTÓRICAS
============================================================

Las rúbricas deben poder reutilizarse en diferentes retos.

Es muy importante que una modificación posterior de una rúbrica no altere las evaluaciones históricas de retos que ya hayan sido evaluados o finalizados.

Cuando una rúbrica se utilice en un reto, debe quedar preservada la configuración utilizada en ese reto.

El sistema debe resolver correctamente este problema sin que el usuario tenga que preocuparse por ello.

============================================================
14. VALORACIÓN DEL RETO
============================================================

La valoración mediante rúbricas produce una nota del reto para el equipo.

Ejemplo:

Equipo de 3 estudiantes:

Nota obtenida mediante las rúbricas:
8/10

Inicialmente, los tres estudiantes tienen como referencia esa nota común.

Sin embargo, posteriormente se realiza un reparto individual.

============================================================
15. REPARTO DE LA NOTA DEL RETO
============================================================

Este es un concepto fundamental de Erronk2D.

La nota obtenida por el equipo debe convertirse en puntos totales que los integrantes tienen que repartirse.

Ejemplo:

Equipo de 3 estudiantes.

Nota del reto:
8/10

Puntos disponibles:

8 × 3 = 24 puntos

Los estudiantes pueden realizar el siguiente reparto:

Alumno A → 7
Alumno B → 8
Alumno C → 9

7 + 8 + 9 = 24

Por tanto:

Alumno A → 7
Alumno B → 8
Alumno C → 9

La aplicación debe permitir introducir este reparto de forma rápida.

Debe mostrar:

NOTA DEL EQUIPO: 8

ESTUDIANTES: 3

PUNTOS A REPARTIR: 24

Y después:

Alumno A [7]
Alumno B [8]
Alumno C [9]

TOTAL: 24
✓ REPARTO CORRECTO

Debe validar automáticamente que:

SUMA DE LOS REPARTOS =
NOTA DEL EQUIPO × NÚMERO DE ESTUDIANTES

Si no coincide, no debe permitir guardar el reparto.

Ejemplo:

7 + 8 + 8 = 23

Mostrar claramente:

"El reparto no es válido. Se deben repartir 24 puntos."

Debe permitir decimales.

============================================================
16. DEFENSAS
============================================================

Un reto puede tener configurado que existen defensas.

Una defensa es una prueba realizada por el profesorado a un estudiante.

Las defensas están asociadas a un módulo.

Por ejemplo:

Reto X

Alumno A:

Programación → +0,50
DWEC → -0,25
DWES → 0

Alumno B:

Programación → -0,50
DWEC → +0,50

Las defensas pueden SUMAR o RESTAR puntos.

============================================================
17. REGLA FUNDAMENTAL DE LAS DEFENSAS
============================================================

MUY IMPORTANTE:

Las defensas NO generan una nota de reto independiente para cada módulo.

Existe UNA ÚNICA NOTA FINAL DEL RETO PARA CADA ESTUDIANTE.

El proceso es:

1. Se obtiene una nota común para el equipo mediante las rúbricas del reto.
2. Se realiza el reparto de esa nota entre los integrantes.
3. Cada estudiante obtiene una nota individual de reto tras el reparto.
4. Se suman/restan TODAS las defensas obtenidas por ese estudiante en los diferentes módulos.
5. El resultado es la NOTA FINAL DEL RETO DEL ESTUDIANTE.
6. Esa única nota final del reto se utiliza como el 40% de la nota final de TODOS los módulos del reto.

Ejemplo completo:

NOTA DEL EQUIPO:
8

Equipo:
3 estudiantes

Puntos:
24

REPARTO:

Alumno A → 7
Alumno B → 8
Alumno C → 9

Después se realizan defensas:

Alumno A:
Programación → +0,50
DWEC → -0,25

Alumno B:
Programación → -0,50
DWEC → +0,50

Alumno C:
Programación → 0
DWEC → +0,25

Por tanto:

Alumno A:
7 + 0,50 - 0,25 = 7,25

Alumno B:
8 - 0,50 + 0,50 = 8,00

Alumno C:
9 + 0 + 0,25 = 9,25

Las notas finales del reto son:

Alumno A → 7,25
Alumno B → 8,00
Alumno C → 9,25

Y estas notas serán las utilizadas como "NOTA DEL RETO" en el cálculo de TODOS los módulos.

Por ejemplo, si Alumno A tiene:

Transversales = 8
Nota final del reto = 7,25
Examen Programación = 7

La nota de Programación será:

8 × 30%
+
7,25 × 40%
+
7 × 30%

Para DWEC, si el examen es 9:

8 × 30%
+
7,25 × 40%
+
9 × 30%

La parte de reto será siempre 7,25.

NO debe existir una "nota de reto de Programación" diferente de una "nota de reto de DWEC".

Existe una única nota final del reto por estudiante.

============================================================
18. DEFENSAS: INFORMACIÓN A REGISTRAR
============================================================

Cada defensa debe registrar:

- Estudiante.
- Reto.
- Módulo.
- Puntuación.
- Profesor que realiza la defensa.
- Fecha.
- Observaciones.

La puntuación puede ser:

- Positiva.
- Negativa.
- Cero.

Debe poder visualizarse claramente el total de defensas de cada estudiante.

Ejemplo:

Alumno A

Reparto: 7,00

Defensas:
Programación: +0,50
DWEC: -0,25

Total defensas: +0,25

NOTA FINAL RETO: 7,25

La nota del reparto original debe permanecer intacta.

============================================================
19. LÍMITES DE LAS DEFENSAS
============================================================

El sistema debe contemplar correctamente qué sucede si las defensas hacen que una nota sea inferior a 0 o superior a 10.

Si no existe una regla específica, utiliza una solución configurable y documentada, evitando decisiones irreversibles.

Por defecto, la nota final del reto debería poder mantenerse dentro del rango 0-10.

============================================================
20. EXÁMENES Y ACTIVIDADES DE LOS MÓDULOS
============================================================

Cada módulo participante en un reto puede tener:

- Un examen.
- Una actividad.
- Varias actividades.
- Una combinación de examen y actividades.

El profesor debe poder introducir las notas obtenidas por los estudiantes.

Ejemplo:

PROGRAMACIÓN

Alumno A → 8
Alumno B → 7
Alumno C → 9

DWEC

Alumno A → 7
Alumno B → 8
Alumno C → 8

La introducción debe ser rápida y preferentemente mediante una interfaz tabular.

============================================================
21. CÁLCULO FINAL DE CADA MÓDULO
============================================================

Para cada estudiante y cada módulo:

NOTA FINAL MÓDULO =
    NOTA TRANSVERSALES × 30%
    +
    NOTA FINAL DEL RETO × 40%
    +
    NOTA EXAMEN/ACTIVIDADES × 30%

IMPORTANTE:

La NOTA FINAL DEL RETO es única para cada estudiante.

No existe una nota de reto diferente por módulo.

Las defensas de los distintos módulos únicamente sirven para calcular esa nota final del reto.

Ejemplo:

Alumno A:

Transversales = 8,00

Reparto = 7,00

Defensas:
+0,50
-0,25

Nota final del reto:
7,25

Programación:
Examen = 7,00

DWEC:
Examen = 9,00

Entonces:

Programación =
8 × 30%
+
7,25 × 40%
+
7 × 30%

DWEC =
8 × 30%
+
7,25 × 40%
+
9 × 30%

============================================================
22. DESGLOSE DE NOTAS
============================================================

El usuario debe poder consultar de dónde sale cualquier nota.

Ejemplo:

ALUMNO A

TRANSVERSALES
8,00

RETO

Nota del equipo:
8,00

Reparto:
7,00

Defensas:
Programación +0,50
DWEC -0,25

Total defensas:
+0,25

NOTA FINAL DEL RETO:
7,25

MÓDULO: PROGRAMACIÓN

Transversales: 8,00
Reto: 7,25
Examen: 7,00

Nota final:
7,45

MÓDULO: DWEC

Transversales: 8,00
Reto: 7,25
Examen: 9,00

Nota final:
8,15

Los valores son únicamente ilustrativos.

============================================================
23. MATRIZ INTERACTIVA DE EVALUACIÓN
============================================================

La pantalla principal para el profesorado debe ser una MATRIZ INTERACTIVA DE EVALUACIÓN.

Debe sustituir la necesidad de trabajar con una hoja de cálculo.

La matriz debe permitir visualizar en una misma pantalla la información esencial.

Conceptualmente:

ESTUDIANTE
EQUIPO
TRANSVERSALES
NOTA EQUIPO
REPARTO
DEFENSAS
NOTA FINAL RETO
EXAMEN/ACTIVIDADES POR MÓDULO
NOTA FINAL POR MÓDULO

Por ejemplo:

Alumno | Equipo | Trans. | Reto equipo | Reparto | Defensas | Reto final | Prog. | Final Prog. | DWEC | Final DWEC | ...

La estructura debe adaptarse automáticamente al número de módulos del reto.

============================================================
24. MATRIZ: INFORMACIÓN VISUAL
============================================================

La matriz debe permitir distinguir claramente:

- Datos del estudiante.
- Equipo.
- Evaluación transversal.
- Nota del equipo.
- Reparto individual.
- Total de defensas.
- Nota final del reto.
- Notas de exámenes/actividades.
- Notas finales de cada módulo.

Debe ser evidente que:

REPARTO + DEFENSAS = NOTA FINAL RETO

Y que:

NOTA FINAL RETO

es común como componente del 40% de todos los módulos.

============================================================
25. MATRIZ: EDICIÓN RÁPIDA
============================================================

El profesor debe poder introducir y modificar información directamente desde la matriz cuando tenga permisos.

Priorizar:

- Edición inline.
- Navegación con teclado.
- Introducción de notas mediante teclado.
- Tablas grandes.
- Filtros.
- Búsqueda.
- Ordenación.
- Columnas congeladas.
- Cabeceras fijas.
- Mostrar/ocultar columnas.
- Acciones rápidas.
- Edición de varios estudiantes.
- Feedback inmediato al guardar.

No obligar a recargar la página después de cada modificación.

============================================================
26. MATRIZ: DESGLOSE
============================================================

Al hacer clic sobre una nota debe poder consultarse su desglose sin perder el contexto de la matriz.

Por ejemplo:

NOTA FINAL RETO 7,25

→ Nota equipo: 8
→ Reparto: 7
→ Defensa Programación: +0,50
→ Defensa DWEC: -0,25
→ Total defensas: +0,25
→ Resultado: 7,25

También debe poder abrirse el detalle de las competencias transversales y de las rúbricas.

============================================================
27. EVALUACIÓN MEDIANTE RÚBRICAS
============================================================

La evaluación con rúbricas debe ser rápida.

Al evaluar un ítem, mostrar sus niveles de forma clara.

Ejemplo conceptual:

CALIDAD DEL CÓDIGO

       4       6       8       10
       ○       ○       ●       ○

Debajo mostrar la descripción del nivel seleccionado.

Para evaluaciones masivas, debe existir una interfaz que reduzca al mínimo los clics.

============================================================
28. DASHBOARD DE CADA RETO
============================================================

Cada reto debe tener una vista general que permita saber inmediatamente cómo está la evaluación.

Debe mostrar como mínimo:

- Número de estudiantes.
- Número de equipos.
- Número de módulos.
- Profesores participantes.
- Estado de las evaluaciones.
- Evaluaciones pendientes.
- Exámenes pendientes.
- Defensas pendientes.
- Repartos pendientes.
- Errores de configuración.

Ejemplo:

RETO: Aplicación web

Estudiantes: 18
Equipos: 6
Módulos: 4

Transversales:
18/18 ✓

Evaluación del reto:
18/18 ✓

Repartos:
17/18 ⚠

Defensas:
15/18 ⚠

Exámenes:
16/18 ⚠

Notas finales:
15/18 ⚠

============================================================
29. DETECCIÓN DE ERRORES E INCONSISTENCIAS
============================================================

La aplicación debe detectar problemas automáticamente.

Ejemplos:

- Equipo con menos de 2 estudiantes.
- Equipo con más de 5 estudiantes.
- Estudiante en dos equipos del mismo reto.
- Reto sin módulos.
- Módulo sin profesor responsable.
- Rúbrica sin ítems.
- Ítem sin niveles.
- Pesos incorrectos.
- Evaluación transversal incompleta.
- Evaluaciones entre compañeros pendientes.
- Evaluación del profesorado pendiente.
- Reparto incorrecto.
- Examen pendiente.
- Defensa pendiente.
- Nota fuera de rango.
- Configuración incompleta.

Estos problemas deben ser visibles desde el dashboard.

============================================================
30. HISTÓRICO
============================================================

Los datos de los retos deben conservarse históricamente.

Modificar un reto posterior no debe alterar los anteriores.

Cambiar los equipos de un reto no debe modificar los equipos de otros retos.

Modificar posteriormente una rúbrica no debe cambiar las evaluaciones históricas que utilizaron una versión anterior.

Las modificaciones importantes de notas y evaluaciones deberían poder quedar registradas para saber quién realizó el cambio y cuándo.

============================================================
31. INFORMES
============================================================

Debe ser posible consultar:

- Notas de estudiantes.
- Notas por módulo.
- Notas por reto.
- Notas por equipo.
- Evolución de estudiantes.
- Resultados de competencias transversales.
- Resultados de rúbricas.
- Evaluaciones pendientes.

La aplicación debe quedar preparada para poder exportar posteriormente información a Excel, CSV y PDF.

============================================================
32. IMPORTACIÓN
============================================================

Debe contemplarse la posibilidad de importar datos iniciales mediante CSV/Excel.

Especialmente:

- Estudiantes.
- Profesores.
- Módulos.

La importación debe validar los datos y mostrar errores de forma comprensible.

============================================================
33. EXPERIENCIA DE USUARIO
============================================================

La prioridad absoluta de la aplicación es:

PRODUCTIVIDAD DEL PROFESOR.

No quiero una aplicación basada exclusivamente en formularios CRUD.

Los CRUD son necesarios para gestionar las entidades, pero el trabajo diario del profesor debe hacerse principalmente mediante:

- Matrices.
- Tablas.
- Edición inline.
- Rúbricas visuales.
- Filtros.
- Acciones rápidas.
- Dashboards.
- Acciones masivas.
- Navegación por teclado.
- Desgloses contextuales.

El profesor debe poder hacer muchas operaciones sin abandonar la pantalla principal del reto.

La aplicación debe transmitir una sensación de herramienta profesional de gestión de evaluación, no de una colección de formularios.

============================================================
34. DISEÑO RESPONSIVE
============================================================

La aplicación debe estar optimizada principalmente para ordenador y tablet, ya que la matriz de evaluación tendrá muchas columnas.

En pantallas pequeñas debe existir una estrategia adecuada para no hacer la interfaz inutilizable.

============================================================
35. DATOS DE DEMOSTRACIÓN
============================================================

Crear datos de demostración realistas.

Por ejemplo:

Curso:
2026-2027

Clase:
2DAW-A

20 estudiantes.

Varios profesores.

4 módulos.

Varios retos.

Equipos diferentes en cada reto.

Varias rúbricas.

Competencias transversales.

Evaluaciones completas.

Evaluaciones pendientes.

Exámenes.

Defensas positivas y negativas.

Repartos válidos e inválidos para poder probar las validaciones.

Los datos de demostración deben permitir probar todo el flujo de evaluación.

============================================================
36. TESTS
============================================================

Crear tests automatizados para comprobar especialmente las reglas de negocio.

Probar como mínimo:

- Equipos.
- Cambios de equipos entre retos.
- Rúbricas.
- Competencias transversales.
- Autoevaluación.
- Evaluación entre compañeros.
- Evaluación del profesorado.
- Nota del equipo.
- Reparto individual.
- Defensas.
- Exámenes.
- Actividades.
- Nota final.
- Permisos.
- Redondeos.
- Diferentes pesos.

CASO OBLIGATORIO 1:

Equipo de 3.

Nota de reto:
8

Puntos:
24

Reparto:
7 + 8 + 9 = 24

Debe ser válido.

CASO OBLIGATORIO 2:

Equipo de 3.

Nota de reto:
8

Reparto:
7 + 8 + 8 = 23

Debe ser inválido.

CASO OBLIGATORIO 3:

Equipo de 3.

Nota de reto:
8

Reparto:

A = 7
B = 8
C = 9

Defensas:

A:
Programación +0,5
DWEC -0,25

B:
Programación -0,5
DWEC +0,5

C:
0

Resultado:

A = 7,25
B = 8
C = 9

La nota final del reto de A debe ser 7,25.

Esta misma nota 7,25 debe utilizarse como componente del 40% tanto para Programación como para DWEC y cualquier otro módulo participante.

CASO OBLIGATORIO 4:

Modificar posteriormente una defensa.

Debe modificarse la nota final del reto correspondiente, pero NO el reparto original.

============================================================
37. REDONDEOS
============================================================

Evitar redondeos prematuros durante los cálculos.

Mantener suficiente precisión interna.

Definir claramente cómo se realiza el redondeo de:

- valores almacenados
- valores intermedios
- valores mostrados
- nota final

La solución debe evitar errores provocados por pequeños problemas de precisión decimal.

============================================================
38. CONFIGURABILIDAD
============================================================

Siempre que sea razonable, los elementos propios de la metodología deben ser configurables y no estar hardcodeados.

Especialmente:

- Pesos de evaluación.
- Pesos de competencias transversales.
- Niveles de rúbricas.
- Notas de los niveles.
- Ítems de rúbricas.
- Módulos participantes.
- Existencia de defensas.
- Límites de las defensas.
- Tipos de actividades/exámenes.

Sin embargo, no introducir complejidad innecesaria.

La aplicación debe mantener una experiencia sencilla para el profesor.

============================================================
39. ARQUITECTURA Y DECISIONES TÉCNICAS
============================================================

Utiliza Laravel y el stack que consideres más adecuado para conseguir los objetivos anteriores.

Tú debes decidir:

- Arquitectura.
- Modelo de datos.
- Estructura del proyecto.
- Tecnología frontend.
- Gestión del estado.
- Sistema de autenticación.
- Sistema de permisos.
- Componentes.
- Estrategia de persistencia.
- Optimización.

No es necesario que sigas literalmente ninguna estructura técnica propuesta por mí.

Prioriza:

- mantenibilidad
- seguridad
- claridad
- rendimiento
- facilidad de evolución
- buena experiencia de usuario

No introduzcas tecnologías innecesarias.

============================================================
40. DESARROLLO
============================================================

Desarrolla la aplicación de forma incremental.

Antes de implementar todo:

1. Analiza los requisitos.
2. Identifica las reglas de negocio.
3. Identifica las posibles ambigüedades importantes.
4. Propón una arquitectura.
5. Propón el modelo de dominio.
6. Explica el flujo de evaluación.
7. Explica cómo garantizarás que las notas históricas no cambien.
8. Explica cómo planteas la matriz interactiva.

Después comienza la implementación.

No es necesario que me preguntes por decisiones técnicas que puedas resolver razonablemente por tu cuenta.

Si encuentras una decisión funcional que pueda cambiar significativamente el comportamiento de la aplicación, indícala antes de implementarla.

============================================================
41. CRITERIO PRINCIPAL DE ÉXITO
============================================================

La aplicación será exitosa si un profesor puede realizar el flujo completo de un reto de forma rápida:

1. Crear el reto.
2. Seleccionar módulos.
3. Crear equipos.
4. Asignar estudiantes.
5. Seleccionar las rúbricas.
6. Evaluar el reto.
7. Obtener la nota del equipo.
8. Introducir el reparto individual.
9. Evaluar las competencias transversales.
10. Introducir las defensas.
11. Obtener automáticamente la nota final del reto de cada estudiante.
12. Introducir los exámenes/actividades de cada módulo.
13. Obtener automáticamente las notas finales de los módulos.
14. Consultar el desglose.
15. Detectar qué evaluaciones están pendientes.
16. Corregir posibles errores.
17. Publicar las notas.

Todo ello debe poder hacerse de forma mucho más ágil que en una hoja de cálculo.

============================================================
42. REGLA DE NEGOCIO MÁS IMPORTANTE
============================================================

Para evitar cualquier interpretación incorrecta:

Existe una única NOTA FINAL DEL RETO para cada estudiante.

El cálculo es:

NOTA EQUIPO
    ↓
REPARTO ENTRE ESTUDIANTES
    ↓
NOTA INDIVIDUAL DEL RETO
    ↓
SUMA/RESTA DE TODAS LAS DEFENSAS DEL ESTUDIANTE
    ↓
NOTA FINAL DEL RETO DEL ESTUDIANTE

Las defensas están asociadas a módulos únicamente para saber qué profesor/módulo realiza cada defensa y poder registrarlas y consultarlas.

Pero TODAS las defensas del estudiante se acumulan para obtener su única nota final del reto.

Después:

NOTA FINAL DE CADA MÓDULO =
    TRANSVERSALES
    +
    NOTA FINAL DEL RETO
    +
    EXAMEN/ACTIVIDADES DEL MÓDULO

Por defecto:

30% + 40% + 30%

Por tanto, si un estudiante tiene:

Transversales = 8
Nota final del reto = 7,25
Examen Programación = 7
Examen DWEC = 9

Entonces:

Programación:
8 × 30% + 7,25 × 40% + 7 × 30%

DWEC:
8 × 30% + 7,25 × 40% + 9 × 30%

La parte correspondiente al reto es SIEMPRE 7,25 en ambos módulos.

No crear una nota de reto independiente para cada módulo.

============================================================
43. COMIENZA
============================================================

Comienza analizando los requisitos y diseñando la solución.

Quiero que priorices especialmente dos aspectos:

1. Que las reglas de cálculo de Erronk2D sean correctas y estén claramente separadas de la interfaz.
2. Que la matriz de evaluación sea una herramienta extremadamente rápida y cómoda para el profesorado.

No sacrifiques la productividad del profesor por una arquitectura excesivamente compleja.
