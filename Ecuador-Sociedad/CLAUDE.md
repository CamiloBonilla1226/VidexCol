# Documentación de Base de Datos — Reportes de Programas

Este documento describe la estructura y relación de las tablas involucradas en el
sistema de reportes de programas (`sat_reportes`), su catálogo de programas
(`categorias`) y la tabla de adjuntos asociada (`tbl_adjuntos`).

============================================================
## TABLA: sat_reportes
============================================================

Tabla principal que almacena los reportes enviados por los diferentes
**programas**. Cada reporte contiene información de asistencia, bautizos,
discipulado, indicadores de mapeo (oración, biblia, evangelización, etc.),
y datos administrativos de creación/modificación.

### Identificación del programa

Cada reporte pertenece a un programa específico, identificado mediante el
campo:

- **`rep_tip`** (int) → Hace *match* con el campo **`id`** de la tabla
  `categorias`. Este es el vínculo que determina a qué programa pertenece
  cada reporte.

```sql
SELECT DISTINCT r.rep_tip, c.id, c.idSec, c.descripcion, c.detalle
FROM sat_reportes r
INNER JOIN categorias c ON c.id = r.rep_tip
ORDER BY r.rep_tip;
```

### Otros campos relevantes

- `idUsuario` — usuario que generó el reporte.
- `idGrupoMadre`, `grupoMadre_txt`, `nombreGrupo_txt` — identificación del
  grupo/célula que reporta.
- `fechaReporte`, `fechaInicio` — fechas del reporte.
- `asistencia_total`, `asistencia_hom`, `asistencia_muj`, `asistencia_jov`,
  `asistencia_nin` — desglose de asistencia.
- `bautizados`, `discipulado`, `desiciones`, `preparandose`,
  `bautizadosPeriodo`, `graduados`, `graduadosPeriodo`,
  `iglesias_reconocidas` — indicadores de crecimiento espiritual.
- `mapeo_*` (anho, cuarto, fecha, comprometido, oracion, companerismo,
  adoracion, biblia, evangelizar, cena, dar, bautizar, trabajadores) —
  indicadores de mapeo/seguimiento del grupo.
- `creacionFecha`, `creacionUsuario`, `modificacionFecha`,
  `modificacionUsuario` — auditoría del registro.
- `number_person_without_freedom`, `number_person_post_penalties` — campos
  específicos de programas relacionados con población privada de libertad
  (ver programa "Estación de Policía UPPL Manizales").

============================================================
## TABLA: categorias
============================================================

Catálogo de programas. Funciona como tabla maestra a la que apunta
`sat_reportes.rep_tip`.

| Campo         | Tipo         | Descripción                                   |
|---------------|--------------|------------------------------------------------|
| `id`          | int(11) PK   | Identificador del programa (match con `rep_tip`) |
| `idSec`       | int(11) MUL  | Identificador de sección/categoría agrupadora |
| `descripcion` | varchar(255) | Nombre/descripción del programa               |
| `detalle`     | varchar(255) | Detalle adicional del programa (normalmente igual a `descripcion`) |

### Programas actualmente registrados

| id  | idSec | descripcion / detalle                              |
|-----|-------|-----------------------------------------------------|
| 308 | 305   | Ecuador ECC Cada Comunidad para Cristo               |
| 317 | 305   | Centros de Capacitación Ecuador (Proyecto Felipe)    |
| 318 | 305   | Evangelistas                                         |
| 327 | 83    | Estación de Policía UPPL Manizales                   |

> **Nota:** el campo `idSec` agrupa programas por sección. Por ejemplo, los
> programas `308`, `317` y `318` pertenecen a la sección `305` (Ecuador),
> mientras que `327` pertenece a la sección `83` (programas de policía /
> población privada de libertad).

============================================================
## TABLA: tbl_adjuntos
============================================================

Tabla **genérica de adjuntos**, reutilizada para varios bloques del
formulario de reportes (graduados, vinculados/bautizados, etc.). Se conecta
con `sat_reportes` a través de `adj_rep_fk`.

| Campo        | Tipo         | Null | Key | Default | Extra          |
|--------------|--------------|------|-----|---------|----------------|
| `adj_id`     | int(11)      | NO   | PRI | NULL    | auto_increment |
| `adj_nom`    | varchar(100) | NO   |     | NULL    |                |
| `adj_url`    | varchar(50)  | NO   |     | NULL    |                |
| `adj_fec`    | date         | NO   |     | NULL    |                |
| `adj_can`    | varchar(30)  | YES  |     | NULL    |                |
| `adj_tip`    | int(11)      | YES  |     | NULL    |                |
| `adj_rep_fk` | int(11)      | NO   |     | NULL    |                |

### Mapeo de campos del formulario → columnas de la tabla

- Nombre completo del graduado → `adj_nom`
- Tarjeta dactilar / N° identificación → `adj_url`
- Fecha de registro (fecha actual) → `adj_fec`
- Tipo de adjunto (1 = graduados) → `adj_tip`
- Llave foránea al reporte → `adj_rep_fk`
- Identificador del registro (edición) → `adj_id`

### Observaciones

- `tbl_adjuntos` es una tabla genérica de "adjuntos" reutilizada para
  varios bloques del formulario (graduados, vinculados/bautizados, etc.).
- La diferenciación entre tipos de registro se hace mediante la columna
  `adj_tip` (valor `1` corresponde específicamente a **GRADUADOS**).
- La relación con el reporte principal se hace a través de `adj_rep_fk`,
  que apunta al `id` del reporte principal (tabla `sat_reportes`, variable
  `$ultimoId` / `$idReporteActual` en el código PHP).
- ⚠️ **Riesgo de seguridad:** las consultas SQL se construyen concatenando
  directamente los valores de `$_REQUEST` sin sanitización visible
  (posible inyección SQL). Esto queda fuera del alcance de esta
  documentación, pero se recomienda revisarlo y migrar a consultas
  preparadas (prepared statements / PDO / mysqli con bind_param).

============================================================
## Relación entre las tablas (resumen)

```
categorias (id) ──────< sat_reportes (rep_tip)
                              │
                              │ id
                              ▼
                      tbl_adjuntos (adj_rep_fk)
```

- `categorias.id` identifica el **programa**.
- `sat_reportes.rep_tip` indica a qué programa pertenece cada reporte.
- `sat_reportes.id` es referenciado por `tbl_adjuntos.adj_rep_fk` para
  asociar adjuntos (por ejemplo, graduados) a un reporte específico.