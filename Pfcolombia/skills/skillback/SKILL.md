---
name: skillback-skill
description: Guía de buenas prácticas de backend para el proyecto PFCOLOMBIA (PHP 7.4 + MySQL, clase DBbase_Sql, routing index.php?doc=, auth por sesión). Úsala siempre que se escriban o modifiquen consultas SQL, se cree o edite lógica de formularios (LPP, CM, Instituto Bíblico, Evangelistas, Proyecto Felipe, ECOP), se trabaje con reporte_lpp/reporte_cm/sat_reportes y sus tablas relacionadas, o se toque cualquier archivo .php del lado servidor. Aplícala también ante pedidos de "optimizar consultas", "mejorar el rendimiento", "que cargue más rápido" o "evitar errores", aunque no se mencione SQL explícitamente.
---

# Backend — PFCOLOMBIA

Skill de referencia para cualquier trabajo de servidor en PFCOLOMBIA: PHP 7.4, MySQL vía la clase `DBbase_Sql`, autenticación por sesión y routing `index.php?doc=`. El objetivo es que las consultas sean **óptimas, seguras y sin errores**, para que la aplicación cargue rápido y de forma confiable.

## 0. Contexto del modelo de datos

Antes de escribir cualquier consulta, revisar el `CLAUDE.md` del proyecto para saber en qué tabla vive cada programa:

- **307 (LPP):** `reporte_lpp` + `reporte_interno_lpp` + `reporte_externo_lpp` + `reporte_graduado_lpp`
- **308 (CM/ECC):** `reporte_cm` + `reporte_graduado_cm`
- **317, 318, 319, 347** (Instituto Bíblico, Evangelistas, Proyecto Felipe, ECOP): `sat_reportes` (filtrando por `rep_tip`) + `tbl_adjuntos`

No mezclar consultas entre el modelo nuevo (`reporte_lpp`/`reporte_cm`) y el legacy (`sat_reportes`) para un mismo programa.

## 1. Seguridad de consultas (prioridad máxima)

- **Nunca concatenar variables directamente en el SQL.** Usar siempre consultas parametrizadas/prepared statements a través de `DBbase_Sql` (o `mysqli`/`PDO` con bind de parámetros si `DBbase_Sql` lo soporta).
- Revisar el archivo que se está editando en busca de concatenación directa de `$_POST`/`$_GET` en el SQL (patrón de riesgo ya detectado antes en el proyecto) y corregirlo a prepared statements si se toca ese código, aunque no sea el foco explícito de la tarea, avisando al usuario del cambio.
- Sanitizar/validar toda entrada del usuario antes de usarla en filtros, `ORDER BY` o nombres de columna dinámicos (estos no se pueden parametrizar igual que los valores, así que deben validarse contra una lista blanca).
- No exponer mensajes de error de MySQL crudos al usuario final; loguearlos y mostrar un mensaje genérico.

## 2. Consultas óptimas (rendimiento)

- Evitar `SELECT *`; seleccionar solo las columnas que realmente se necesitan, especialmente en listados largos (ej. listados de reportes, graduados).
- Usar los índices ya definidos en el esquema (`usuario_id`, `carcel_id`, `fecha_reporte`, `municipio_id`, `rep_tip`, etc.) al filtrar/ordenar; si se agrega un filtro nuevo muy usado, sugerir un índice si no existe.
- Preferir `JOIN` sobre múltiples consultas separadas en bucle (evitar el patrón N+1: por ejemplo, no traer todos los `reporte_cm` y luego hacer una consulta por cada uno para traer sus graduados; usar un solo `JOIN` o una consulta `IN (...)` agrupada).
- Paginar listados largos (`LIMIT`/`OFFSET` o keyset pagination) en vez de traer todos los registros de una tabla grande de una vez.
- Al insertar un reporte con sus tablas hijas (ej. `reporte_lpp` + internos/externos/graduados, o `reporte_cm` + `reporte_graduado_cm`), usar transacciones (`BEGIN`/`COMMIT`/`ROLLBACK`) para garantizar consistencia si falla alguna inserción intermedia.
- Cachear o evitar recalcular en cada request datos que cambian poco (ej. catálogos de `categorias`, cárceles, municipios) si el patrón del proyecto ya lo permite.

## 3. Validaciones y reglas de negocio

- Las validaciones de negocio deben **fallar de forma explícita y visible** (mensaje de error claro devuelto al frontend), nunca corregir el dato silenciosamente en el backend sin informar al usuario. Esta es una convención ya establecida en el proyecto (ej. módulo LPP) y debe mantenerse en cualquier lógica nueva.
- Verificar los límites reales de las columnas antes de escribir/migrar datos (ej. columnas `varchar` con longitud limitada); truncaciones silenciosas ya han causado corrupción de datos en el proyecto (caso conocido con un campo `VARCHAR(100)`). Si un valor puede exceder el límite de la columna, validar y avisar en vez de dejar que MySQL trunque.
- Revisar duplicidad de campos o lógica repetida al modificar archivos grandes/monolíticos; si se detecta, señalarlo aunque no se pida arreglar de inmediato.
- Verificar sesión/autenticación en cada endpoint que maneje datos sensibles, siguiendo el patrón de auth por sesión ya usado (no crear un mecanismo de auth paralelo).

## 4. Manejo de archivos adjuntos

- Seguir el patrón ya usado para adjuntos: extensión guardada aparte (`archivo_foto`, `archivo_testimonio`, `*_ext`, o registro en `tbl_adjuntos` con `adj_rep_fk`), no inventar un esquema de almacenamiento nuevo sin necesidad.
- Verificar el tipo MIME real del archivo subido (no solo la extensión) antes de guardarlo o procesarlo, para evitar bugs de conversión (ej. caso conocido de bug PNG→JPEG) y riesgos de seguridad.
- Para archivos grandes (videos, fotos de alta resolución), seguir el patrón ya usado de subida progresiva/streaming si el módulo lo requiere.

## 5. Checklist antes de entregar un cambio de backend

1. ¿La consulta usa parámetros/prepared statements en vez de concatenar strings?
2. ¿Evité `SELECT *` y traje solo las columnas necesarias?
3. ¿Evité el patrón N+1 (consultas dentro de un bucle)?
4. ¿Usé transacciones si el cambio implica insertar/actualizar en tabla principal + tablas hijas?
5. ¿Los errores de validación son explícitos y visibles, no corregidos en silencio?
6. ¿Respeté los límites de longitud de las columnas para evitar truncación de datos?
7. ¿La consulta apunta al modelo de datos correcto según el programa (307/308 → tablas nuevas; 317/318/319/347 → `sat_reportes`)?