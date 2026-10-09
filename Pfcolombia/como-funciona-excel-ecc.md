# Cómo funciona el Excel del Informe del Coordinador (C&M / ECC)

## 1. ¿De dónde salen los datos?

1. En `informe-coordinador-ecc.php` presionas **"Descargar para Excel"**. Tus filtros (fechas, regional, período) se envían a `generaExcelECC.php`.
2. `generaExcelECC.php` llama a `api-informe-coordinador-ecc.php`, que consulta la tabla `reporte_cm` con esos filtros.
3. La API devuelve **un renglón por cada reporte** (no agrupado por coordinador).
4. `generaExcelECC.php` arma el Excel: **una fila por reporte**.

Los datos se diligencian en el formulario `gestionar-sub-programa-ecc.php`.

## 2. Datos del grupo (columnas A a F)

| Col. | Encabezado | Pregunta en el formulario | Campo en BD |
|---|---|---|---|
| A | Nombre del líder | Nombre del usuario + "Entrenador" | `usuario.nombre` + `entrenador` |
| B | Nombre del grupo / iglesia | "Nombre grupo/iglesia" | `nombreGrupo_txt` |
| C | Fecha de inicio | "Fecha de inicio de la confraternidad" | `fechaInicio` |
| D | Generación | Botones 1, 2, 3, 4, 5... | `generacionNumero` |
| E | Ubicación | Cárcel/pabellón (INTRA) o departamento/municipio/dirección (EXTRA) | varios |
| F | Grupo madre / Iglesia | "Grupo madre" (fijo: Confraternidad carcelaria de Colombia) | `grupoMadre_txt` |

## 3. Indicadores (columnas G a K)

| Col. | Encabezado | Pregunta en el formulario | Campo en BD |
|---|---|---|---|
| G | Asistencia del grupo | Hombres + Mujeres + Jóvenes + Niños | `asistencia_total` |
| H | Total de creyentes en el grupo | Se calcula, no hay pregunta directa | `en_discipulado` |
| I | Nuevos creyentes en este período | "Decisiones para Cristo" | `decisiones_cristo` |
| J | Total de bautizados en el grupo | "Miembros Bautizados" (acumulado) | `miembros_bautizados` |
| **K** | **Nuevos bautizados en este período** | **"Bautizados este período"** | **`bautizados_periodo`** |

**La columna K es la que equivale a nuestra consulta SQL de bautizados.** La J es el acumulado, no la uses para esa suma.

## 4. Mapeo de madurez de la iglesia (columnas L a T y AC)

En el formulario se guarda un número del 1 al 4, y el Excel lo muestra tal cual:

- 1 = NO realizan la tarea
- 2 = La realizan acompañados del entrenador
- 3 = La realizan, pero este mes no lo hicieron
- 4 = La realizan autónomamente

| Col. | Encabezado | Campo en BD |
|---|---|---|
| L | Orar | `mapeo_oracion` |
| M | Compañerismo | `mapeo_companerismo` |
| N | Adorar | `mapeo_adoracion` |
| O | Aplicar la Biblia | `mapeo_biblia` |
| P | Evangelizar | `mapeo_evangelizar` |
| Q | Cena del Señor | `mapeo_cena` |
| R | Dar | `mapeo_dar` |
| S | Bautizar | `mapeo_bautizar` |
| T | Entrenar nuevos líderes | `mapeo_trabajadores` |
| AC | ¿Comprometido como iglesia? (3 = NO, 4 = SÍ) | `mapeo_comprometido` |

Las columnas **U a X** (1, 2, 3, 4) no corresponden a ninguna pregunta: siempre valen 0.

## 5. Datos del entrenador y cálculos (columnas Z a AI)

| Col. | Encabezado | Origen |
|---|---|---|
| Z | Ubicación del entrenador | Dirección y municipio del usuario que reporta |
| AA | Entrenador | Nombre del usuario que hizo el reporte |
| AB | Carnet de identidad | Identificación del usuario |
| AD | Suma | Suma de L a T |
| AE | Promedio | Suma ÷ 9 |
| AF | Desde | Inicio del trimestre filtrado |
| AG | Hasta | Fin del trimestre filtrado |
| AH | Reunido | "Fecha de mapeo" (`mapeo_fecha`) |
| AI | Nombre del socio | Regional de la cárcel (INTRA) o del usuario (EXTRA) |

## 6. El total

La **fila 10** de cada columna numérica (G10, H10, K10, etc.) tiene una fórmula `=SUMA(...)` que suma todas las filas de datos. Ese es el total que se compara contra la gráfica o contra una consulta SQL.

## 7. Para que el total de K coincida con la consulta SQL

Consulta de referencia:

```sql
SELECT COALESCE(SUM(bautizados_periodo), 0) AS total_bautizados
FROM reporte_cm
WHERE inactivo = 0
  AND fecha_reporte BETWEEN '2026-07-01' AND '2026-09-30';
```

Para que el Excel dé lo mismo, descárgalo con **Período = "Sin especificar"** y **Regional = "Todas"**. Si aún hay diferencia, puede deberse a:

- Reportes con `generacion = 0` (la API los omite y la consulta no).
- Reportes EXTRA, que se pierden si hay una regional seleccionada (no tienen cárcel).
- Usuarios con más de una fila en `usuario_empresa`, que duplican sus reportes.
- Cambios sin subir al servidor (`generaExcelECC.php` y `api-informe-coordinador-ecc.php`).
