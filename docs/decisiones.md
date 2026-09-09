# Decisiones de Erronk2D

Fuente funcional: `prompt.md`, junto con las aclaraciones del usuario de 2026-09-07. Las aclaraciones posteriores prevalecen donde simplifican el documento original.

## Aclaraciones aceptadas

- Un curso académico tiene un número configurable de **Evaluaciones** (por ejemplo, dos o tres).
- Cada reto pertenece a una Evaluación y tiene un peso positivo. La nota de la Evaluación por módulo es la media ponderada de los retos participantes en ese módulo. Los pesos son relativos: se dividen por su suma, sin exigir que sumen 100. Los retos ajenos al módulo no cuentan como cero.
- La nota del curso por módulo es la media aritmética de sus Evaluaciones. Una Evaluación incompleta mantiene pendiente la nota del curso; no se omite silenciosamente.
- Un único examen por estudiante, módulo y reto. No se implementa un catálogo de actividades múltiples.
- Una única defensa por estudiante, módulo y reto. Cada módulo tiene defensa activada por defecto, configurable para ese reto. Una defensa pendiente es distinta de una defensa de cero.
- Los profesores de un módulo comparten las calificaciones: cualquiera con permiso puede introducirlas. No se promedian varias notas de profesores para un mismo registro. Se conserva quién lo modificó y cuándo.
- Todos los profesores pueden consultar todas las notas. La autorización de escritura sigue dependiendo de permisos y responsabilidad docente.
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

- El usuario solicita inicio de sesión y registro mediante Google, manteniendo el acceso con contraseña.
- Criterio aplicado mientras se espera respuesta a la consulta: los registros nuevos son solicitudes pendientes; solo el administrador puede aprobarlas y asignar rol/clase. No se conceden permisos por el dominio del correo ni por datos enviados desde el navegador.
- Vincular una cuenta existente requiere confirmar una vez su contraseña local. No se fusionan cuentas ni se cambia su historial. Una cuenta desactivada no obtiene acceso mediante Google.
- Sin dependencias adicionales ni modificaciones de infraestructura compartida. Activación pendiente de las credenciales OAuth privadas y prueba real del retorno de Google; instrucciones en despliegue.md.
