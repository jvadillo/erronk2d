# Estado de la tarea

## Objetivo
Unificar las columnas de módulo y criterio al crear o editar una rúbrica y estrechar la columna del peso.

## Completado
- Selector de módulo, nombre y descripción opcional comparten la primera celda.
- Peso usa un campo numérico de hasta dos decimales con ancho compacto.
- Prueba de navegador actualizada y pasada; compilación frontend correcta.
- Desplegado en producción como release `47c80d6`; copia previa verificada en `backups/production-20260923-47c80d6`.

## Pendiente
- Ninguno.

## Archivos relevantes
- `resources/js/pages/RubricEditor.vue`, `resources/css/rubrics.css`, `tests/Browser/rubrics.spec.ts`.

## Decisiones
- La primera columna mantiene el selector arriba, seguido del nombre y la descripción.
- La columna de peso tiene 102 px y el campo ocupa 6,5 em.

## Último error
- La verificación DNS falló dentro del sandbox; `/up` y `/login` respondieron correctamente al reintentar fuera de él.
