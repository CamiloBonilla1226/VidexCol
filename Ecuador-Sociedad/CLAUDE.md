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
## TABLA: usuario
============================================================

Tabla de usuarios del sistema. Se conecta con `sat_reportes` a través de los
campos `idUsuario`, `creacionUsuario` y `modificacionUsuario`, que hacen
referencia al `id` del usuario que generó, creó o modificó un reporte.

| Campo                 | Tipo         | Null | Key | Default | Extra          |
|-----------------------|--------------|------|-----|---------|----------------|
| `id`                  | int(11)      | NO   | PRI | NULL    | auto_increment |
| `tipo`                | int(11)      | NO   |     | NULL    |                |
| `tipo_user_cli`       | int(11)      | NO   |     | 0       |                |
| `nombre`              | varchar(255) | NO   |     | NULL    |                |
| `identificacion`      | varchar(50)  | NO   |     | NULL    |                |
| `tipoIdentificacion`  | int(11)      | NO   |     | NULL    |                |
| `direccion`           | varchar(255) | NO   |     | NULL    |                |
| `telefono1`           | varchar(50)  | NO   |     | NULL    |                |
| `celular`             | varchar(50)  | NO   |     | NULL    |                |
| `email`               | varchar(255) | NO   |     | NULL    |                |
| `url`                 | varchar(255) | NO   |     | NULL    |                |
| `url2`                | text         | NO   |     | NULL    |                |
| `observaciones`       | text         | NO   |     | NULL    |                |
| `login`               | varchar(50)  | YES  |     | NULL    |                |
| `password`            | varchar(255) | NO   |     | NULL    |                |
| `superusuario`        | tinyint(4)   | YES  |     | NULL    |                |
| `acceso`              | tinyint(1)   | NO   |     | 1       |                |
| `acceso_graphs`       | tinyint(4)   | NO   |     | NULL    |                |
| `creacionUsuario`     | int(11)      | NO   |     | NULL    |                |
| `creacionFecha`       | varchar(25)  | NO   |     | NULL    |                |
| `modUsuario`          | int(11)      | YES  |     | NULL    |                |
| `modFecha`            | date         | YES  |     | NULL    |                |
| `usua_muni`           | int(11)      | YES  | MUL | NULL    |                |
| `usua_pais`           | varchar(50)  | YES  |     | NULL    |                |
| `excluido_reportes`   | tinyint(1)   | YES  |     | 0       |                |

### Relación con sat_reportes

- `usuario.id` es referenciado por:
  - `sat_reportes.idUsuario` — usuario que generó/reportó el registro.
  - `sat_reportes.creacionUsuario` — usuario que creó el reporte.
  - `sat_reportes.modificacionUsuario` — usuario que modificó el reporte
    por última vez.

### Campos relevantes

- `tipo` / `tipo_user_cli` — clasifican el tipo de usuario (interno,
  cliente, etc.).
- `acceso` — indica si el usuario tiene acceso activo al sistema (1 = sí).
- `superusuario` — bandera de privilegios elevados.
- `acceso_graphs` — controla si el usuario puede ver gráficos/reportes
  estadísticos.
- `usua_muni` — municipio asociado al usuario (índice `MUL`, probable FK a
  tabla de municipios).
- `usua_pais` — país del usuario.
- `excluido_reportes` — indica si el usuario debe excluirse de ciertos
  reportes/estadísticas (1 = excluido).
- `creacionFecha` — fecha de creación del usuario (nota: almacenada como
  `varchar(25)` en lugar de `datetime`/`date`).

============================================================
## TABLA: usuario_empresa
============================================================

Tabla de **información organizacional** del usuario. Se diligencia desde la
pestaña "Información Organizacional" del formulario de usuario
(`usuario.php`, tab `#empresa`). Se relaciona con `usuario` mediante
`idUsuario`.

### Campo "Programa al que pertenece" (`empresa_proceso`)

En el formulario, el select "Programa al que pertenece" se llena con un
catálogo de `categorias`, pero usando una sección distinta a la de
`sat_reportes.rep_tip`:

```sql
SELECT * FROM categorias WHERE idSec = 38 ORDER BY descripcion asc
```

- El `<option value>` es el `categorias.id` y el texto mostrado es
  `categorias.descripcion`.
- ⚠️ **No confundir con `sat_reportes.rep_tip`**: ese usa `categorias.idSec = 305`
  (programas de Ecuador) o `idSec = 83` (programas de policía). El campo
  `empresa_proceso` del usuario usa `idSec = 38`, un agrupador distinto dentro
  de la misma tabla `categorias` (catálogo de "procesos/programas" a nivel de
  usuario, no de reporte).

### Flujo de guardado (`usuario.php`)

1. El valor seleccionado llega como `$_POST["empresa_proceso"]` y se
   sanitiza con `eliminarInvalidos()`.
2. **Usuario nuevo** → `INSERT INTO usuario_empresa (... empresa_proceso ...)`
   junto con el resto de campos organizacionales, ligado a `idUsuario`
   (`$ultimoId`).
3. **Edición de usuario** → `UPDATE usuario_empresa SET ... empresa_proceso = "..."
   WHERE idUsuario = "$idUsuarioActual"`.
4. Al recargar el formulario de edición, se lee de vuelta con
   `$empresa_proceso = $PSN1->f("empresa_proceso")` para marcar la opción
   `selected` en el combo.

### Otros campos de `usuario_empresa` guardados junto con `empresa_proceso`

- `empresa_tipo` — tipo de ministerio (`categorias.idSec = 15`).
- `empresa_representante`, `empresa_contacto` — datos de contacto.
- `empresa_direccion`, `empresa_url` — dirección y página web.
- `empresa_paisid`, `empresa_pais` — país (fijo: Ecuador, id `282`).
- `empresa_sitio_cor` — zona (`categorias.idSec = 85`).
- `empresa_socio`, `empresa_pd`, `empresa_sitio`, `empresa_rm`,
  `empresa_circuito` — otros datos organizacionales/financieros.
- ⚠️ **Mismo riesgo de seguridad** que en `tbl_adjuntos`: el `INSERT`/`UPDATE`
  concatena directamente las variables (ya pasadas por `eliminarInvalidos()`)
  dentro del SQL en vez de usar sentencias preparadas.

============================================================
## FORMULARIO: gestionar-sub-programa-evangelistas.php
============================================================

Formulario de reportes del programa **Evangelistas** (`categorias.id = 318`).
Guarda principalmente en `sat_reportes`, con `rep_tip = 318` **fijo en el
código** (no es una selección del usuario).

### Mapa de campos visibles del formulario → columna real

| Campo mostrado al usuario | Columna en `sat_reportes` | Input HTML |
|---|---|---|
| Fecha del informe | `fechaReporte` | `fechaReporte` |
| Nombre del evangelista | `idUsuario` | `usua_id` (hidden, autocompletado vía JS) |
| Total población que hay en la prisión | `asistencia_total` | `asistencia_total` |
| Número de prisioneros invitados | `asistencia_hom` | `asistencia_hom` |
| Número de prisioneros que iniciaron el curso | `asistencia_muj` | `asistencia_muj` |
| Cárcel ubicación | *(no va a `sat_reportes`)* | `car_id[]` → tabla `tbl_adjuntos` |
| Actividades (checkboxes de mapeo) | `mapeo_oracion`, `mapeo_companerismo`, `mapeo_adoracion`, `mapeo_biblia`, `mapeo_evangelizar`, `mapeo_cena`, `mapeo_dar`, `mapeo_bautizar`, `mapeo_trabajadores` | mismos nombres |
| Total de creyentes que asistieron a los grupos en el mes | `asistencia_jov` | `asistencia_jov` |
| Número de bautizos en el mes | `bautizados` | `bautizados` |
| Número de voluntarios internos activos | `discipulado` | `discipulado` |
| Número de voluntarios externos activos | `desiciones` | `desiciones` |
| Número de pospenados que está acompañando | `preparandose` | `preparandose` |
| Testimonio 1 (impacto positivo PPL) | `comentario` | `rep_text1` |
| Testimonio 2 (superación pospenado) | `rep_text2` | `rep_text2` |
| Testimonio 3 (autoridad carcelaria) | `rep_text3` | `rep_text3` |
| Observaciones/obstáculos | `rep_text4` | `rep_text4` |
| Foto | `ext1` (extensión) | `archivo1` → archivo físico en `archivos/evi_{id}_1.{ext}` |

⚠️ **Las etiquetas del formulario NO coinciden con el nombre técnico de la
columna** (p. ej. "Total población en la prisión" se guarda en
`asistencia_total`, que en otros programas significa "asistencia total").
Este formulario es evidentemente una copia/adaptación de otro programa
(grupos/plantación de iglesias) al que solo se le cambiaron las etiquetas
visibles, no los nombres internos de los campos.

### Cárceles seleccionadas → `tbl_adjuntos`

El select `car_id[]` no guarda en `sat_reportes`, sino en `tbl_adjuntos`:

```php
INSERT INTO tbl_adjuntos (adj_nom, adj_url, adj_fec, adj_tip, adj_rep_fk)
VALUES ('{id_carcel}', '{id_carcel}', '{fecha}', 4, {idReporte})
```

`adj_tip = 4` identifica este bloque como **cárceles** (agregar a la lista de
valores conocidos de `adj_tip`: `1` = graduados, `4` = cárceles). `adj_nom` y
`adj_url` guardan el mismo `id` de `tbl_regional_ubicacion` en ambas
columnas (parece redundante/copiado del bloque de graduados, donde esas dos
columnas sí tenían usos distintos).

### Datos que se guardan pero NO aparecen en el formulario de inserción

1. **`rep_tip = 318`** — fijo en el código, no es una opción del usuario.
2. **`iglesias_reconocidas = 0`** — siempre se guarda en cero.
3. **`creacionFecha = NOW()`, `creacionUsuario = $_SESSION["id"]`** —
   auditoría automática.
4. **Columnas que el código intenta leer de `$_REQUEST` pero que NO tienen
   ningún `<input>` en este formulario** (quedan vacías/0 porque el índice
   no existe en el POST): `sitioReunion`, `grupoMadre_txt`,
   `nombreGrupo_txt`, `pabellon`, `direccion`, `ciudad` (municipio),
   `capacitacion_txt`, `generacionNumero`, `idGrupoMadre` (tiene un hidden
   pero nunca se le asigna valor), `rep_ndis`, `mapeo_fecha`,
   `mapeo_comprometido`, `mapeo_cuarto`, `mapeo_anho`.
5. **`plantador` y `rep_entr` (entrenador)** — se usan en el `INSERT` pero
   `$plantador`/`$entrenador` **nunca se definen** en la rama de inserción
   de este archivo → variable indefinida en PHP, se guarda cadena vacía.
   ⚠️ Posible bug a corregir.
6. **`number_person_without_freedom` y `number_person_post_penalties`** —
   el código sí los procesa y guarda, pero sus `<input>` están **comentados**
   en el HTML del formulario de inserción. La columna existe y se intenta
   llenar, pero actualmente no hay forma de que el usuario la diligencie
   desde esta pantalla — siempre se guarda vacío/0.
7. **`asistencia_nin`** — mismo caso: su `<input>` está comentado en el
   formulario de inserción, aunque en el modo de edición/visualización sí
   aparece poblado (reportes antiguos donde este campo sí se llenaba).
8. **`archivo2` / `archivo3`** — el código procesa hasta 3 archivos (`ext1`,
   `ext2`, `ext3`), pero el formulario de inserción solo tiene el input
   `archivo1`; no hay manera de cargar una segunda o tercera foto desde esta
   pantalla.

### Resumen rápido

- **Tabla principal:** `sat_reportes` (`rep_tip = 318` fijo).
- **Tabla secundaria:** `tbl_adjuntos` (`adj_tip = 4`) para las cárceles
  seleccionadas, ligada por `adj_rep_fk = sat_reportes.id`.
- **Archivo físico:** la foto se guarda en disco
  (`archivos/evi_{id}_1.{ext}`); solo su extensión queda en `ext1`.
- Hay código heredado (variables no definidas, inputs comentados) que hace
  que varias columnas de `sat_reportes` siempre queden vacías en este
  formulario específico, aunque la tabla sí tiene esos campos.

============================================================
## Relación entre las tablas (resumen)

```
categorias (id) ──────< sat_reportes (rep_tip, idSec=305/83)
                              │      ▲
                              │ id   │ idUsuario / creacionUsuario /
                              ▼      │ modificacionUsuario
                      tbl_adjuntos   │
                      (adj_rep_fk)   │
                                     │
                              usuario (id) ──────< usuario_empresa (idUsuario)
                                                          │
                                                          ▼
                                            categorias (id, idSec=38)
                                            "empresa_proceso" (programa del usuario)
```

- `categorias.id` identifica el **programa** (para reportes, `idSec = 305`/`83`).
- `sat_reportes.rep_tip` indica a qué programa pertenece cada reporte.
- `sat_reportes.id` es referenciado por `tbl_adjuntos.adj_rep_fk` para
  asociar adjuntos (por ejemplo, graduados) a un reporte específico.
- `usuario.id` es referenciado por `sat_reportes.idUsuario`,
  `sat_reportes.creacionUsuario` y `sat_reportes.modificacionUsuario` para
  identificar al usuario que reportó, creó o modificó cada registro.
- `usuario.id` también es referenciado por `usuario_empresa.idUsuario` para
  guardar la información organizacional del usuario, incluyendo
  `empresa_proceso` (el "programa al que pertenece" el usuario, distinto del
  programa del reporte: usa `categorias.idSec = 38`).