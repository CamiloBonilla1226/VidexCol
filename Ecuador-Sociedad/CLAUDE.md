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
## FORMULARIO: subcategoria-ecc.php
============================================================

Formulario de reportes del programa **ECC — Ecuador ECC Cada Comunidad para
Cristo** (`categorias.id = 308`). A pesar de sus ~6259 líneas, maneja un
**único sub-programa**: `rep_tip` está hardcodeado a `308`
(`subcategoria-ecc.php:310`, `638`) y las consultas de navegación
anterior/siguiente filtran explícitamente `rep_tip = 308`
(`subcategoria-ecc.php:1636`, `1672`). El tamaño se debe a mucho **código
muerto heredado**: variables de "generación" (`CERO`/`EVAN`/`GCEL`/`EXTRA`)
que ya no se usan — hoy `$generacionActual` está fijo en `"INTRA"`
(`subcategoria-ecc.php:83-84`) y `$preguntarGeneracion = 0`
(`subcategoria-ecc.php:81`), por lo que la rama que preguntaría la
generación (`subcategoria-ecc.php:3374`, `3562`) es inalcanzable.

Tiene **tres formularios** en el mismo archivo:

| Rango | Propósito | Se activa cuando |
|---|---|---|
| `subcategoria-ecc.php:1608`–`3168` | Edición/visualización de un reporte existente | `$idReporteActual > 0` |
| `subcategoria-ecc.php:3388`–`3426` | Selector de "generación" (INTRA/EXTRA) | `$preguntarGeneracion == 1` — **código muerto, nunca se ejecuta** |
| `subcategoria-ecc.php:3728`–`5592` | Alta de reporte nuevo (wizard multi-fieldset) | `$idReporteActual == 0` |

### Mapa de campos — modo INSERTAR (alta de reporte nuevo)

| Label visible | Input name | Columna en `sat_reportes` |
|---|---|---|
| (buscador de usuario/tag) | `usua_id` (hidden) | `idUsuario` |
| (hidden) | `fechaReporte` | `fechaReporte` |
| "Fecha de inicio del grupo/iglesia" | `fechaInicio` | `fechaInicio` |
| "Grupo madre" (fijo "ECUADOR", readonly) | `grupoMadre_txt` | `grupoMadre_txt` |
| "Nombre grupo/iglesia" (readonly) | `nombreGrupo_txt` | `nombreGrupo_txt` |
| Radios de generación (1-5) | `generacionNumero` | `generacionNumero` |
| "Municipio" (combo cargado por AJAX desde `datos_ubicacion.php`) | `municipio` | `ciudad` |
| "Dirección" | `direccion` | `direccion` |
| "Fecha de mapeo" (fija a hoy, readonly) | `mapeo_fecha` | `mapeo_fecha` |
| "¿Este grupo/iglesia está comprometido?" | `mapeo_comprometido` | `mapeo_comprometido` |
| Ítems del "Método de verificación" (Orar, Compañerismo, Adorar, Aplicar la biblia, Evangelizar, Cena del Señor, Dar, Bautizar, Entrenar nuevos líderes) | `mapeo_oracion`, `mapeo_companerismo`, `mapeo_adoracion`, `mapeo_biblia`, `mapeo_evangelizar`, `mapeo_cena`, `mapeo_dar`, `mapeo_bautizar`, `mapeo_trabajadores` | mismas columnas |
| "Foto + Fecha + Cantidad bautizados" (bloque repetible) | `act_bau_img[]`, `act_bau_fec[]`, `act_bau_can[]` | → **`tbl_adjuntos`**, no `sat_reportes` |
| "Foto 1/2/3" | `archivo1`, `archivo2`, `archivo3` | `ext1`, `ext2`, `ext3` |
| Hombres/Mujeres/Jóvenes/Niños | `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin` | mismas columnas |
| "Decisiones para cristo" (puenteado por JS al hidden `final_desiciones`) | `desiciones` → `final_desiciones` | `desiciones` |
| "Asistencia total" (calculado por JS, no editable) | `final_asistencia_total` | `asistencia_total` |
| "Miembros bautizados" (suma JS de `act_bau_can[]`) | `final_bautizados` | `bautizados` |
| "Bautizados este período" (mismo total JS) | `final_bautizadosPeriodo` | `bautizadosPeriodo` |
| "En discipulado" (= asistencia total, JS) | `final_discipulado` | `discipulado` |
| "Preparándose para bautismo" (= asistencia total − bautizadosPeriodo, JS) | `final_preparandose` | `preparandose` |

⚠️ **Todo el bloque `final_*` se recalcula en JavaScript** (`sumar()`,
`subcategoria-ecc.php:5835-5999`) justo antes del submit, y **el PHP del
servidor NO vuelve a validar/recalcular esos totales** — confía ciegamente
en lo que llega en los campos hidden. Un usuario con JS deshabilitado o que
manipule el POST puede enviar cualquier valor sin relación con los datos
reales.

### Mapa de campos — modo ACTUALIZAR (edición)

Usa un juego de nombres distinto (todos con prefijo `final_` para los
numéricos), pero consistente dentro de este modo: `usua_nombre/usua_id` →
`idUsuario`; `inactivo` → `inactivo`; `final_comentarios` → `comentario`;
`fechaInicio`, `grupoMadre_txt`, `nombreGrupo_txt`, `generacionNumero`,
`direccion` → columnas homónimas; `municipio` → `ciudad`;
`final_asistencia_hom/muj/jov/nin` → `asistencia_hom/muj/jov/nin`;
`final_bautizados` → `bautizados`; `final_discipulado` → `discipulado`;
`final_desiciones` → `desiciones`; `final_preparandose` →
`preparandose`; `final_bautizadosPeriodo` → `bautizadosPeriodo`;
`mapeo_fecha`, `mapeo_comprometido`, `mapeo_*` → columnas homónimas;
`archivo1/2/3` → `ext1/2/3` (solo si llega un archivo nuevo).

⚠️ **Corrección:** `rep_ndis` ("Número de discípulos LPP") también está
**comentado en el formulario de edición**
(`subcategoria-ecc.php:2402-2408`), igual que en el de alta — no hay ningún
input funcional para esta columna en ningún modo; siempre se guarda
vacío/0.

`final_comentarios` → `comentario` **sí existe y es funcional** en el
formulario de edición (`subcategoria-ecc.php:3086-3122`), pero está
condicionado a `if ($generacionNumero == 8)`. Los radios del formulario de
alta solo ofrecen `generacionNumero` de `1` a `5`
(`subcategoria-ecc.php:4118-4159`), por lo que `8` (junto con `0` y `77`,
usados en condiciones similares en `subcategoria-ecc.php:2022, 2196, 2208,
3218`) es un **valor legado** de una versión anterior del sistema. En la
práctica, este campo de comentario solo aparece al editar reportes
**antiguos** que ya tienen `generacionNumero = 8` guardado en BD; ningún
reporte creado con el formulario actual puede llegar a ese valor, así que
la sección nunca se muestra para reportes nuevos.

No es un bug: cada modo (insertar/actualizar) usa su propio par de nombres
de forma consistente, solo son estilísticamente distintos entre sí.

### Uso de `tbl_adjuntos`

Se usa **únicamente** para las evidencias fotográficas de "bautizos"
(`act_bau_img[]`, `act_bau_fec[]`, `act_bau_can[]`), tanto al insertar
(`subcategoria-ecc.php:657-688`) como al actualizar
(`subcategoria-ecc.php:1103-1251`).

⚠️ **A diferencia de evangelistas (que usa `adj_tip = 4` para cárceles),
aquí el INSERT/UPDATE de `tbl_adjuntos` NUNCA asigna `adj_tip`** — solo
inserta `adj_nom, adj_url, adj_fec, adj_can, adj_rep_fk`. El campo queda con
su valor por defecto de la tabla. Las consultas que leen estos adjuntos
filtran solo por `adj_rep_fk`, sin `adj_tip`, porque en este archivo no hay
necesidad de distinguir tipos de adjunto dentro de un mismo reporte.

### Datos guardados en BD sin input funcional en el formulario (bugs/código muerto)

| Columna | Estado real |
|---|---|
| `comentario` (modo insertar) | Lee `$_REQUEST["final_comentarios"]`, pero no existe ningún `<input name="final_comentarios">` alcanzable en el formulario de alta — solo existe dentro de ramas muertas (`GCEL`). **Siempre se guarda vacío al crear.** |
| `rep_ndis` (modo insertar) | El único `<input name="rep_ndis">` del formulario de alta está dentro de un bloque comentado (`subcategoria-ecc.php:5208-5268`). **Siempre vacío/0 al crear** (en edición sí funciona). |
| `rep_entr` (entrenador) | El `<input name="entrenador">` está comentado en los tres formularios. **Nunca se llena por el usuario.** |
| `sitioReunion` ("Cárcel ubicación") | El `<select name="sitioReunion">` está comentado en los tres formularios; como `isset($_REQUEST["sitioReunion"])` es `false`, siempre cae al `else` y **se guarda como `0`**. |
| `pabellon` ("Lugar") | `<input name="pabellon">` comentado en los tres formularios. **Siempre vacío.** |
| `plantador` | No existe ningún `<input name="plantador">` en todo el archivo (ni comentado). **Columna siempre vacía.** |
| `capacitacion_txt` | No existe ningún `<input name="capacitacion_txt">` en todo el archivo. **Columna siempre vacía.** |
| `idGrupoMadre` | No hay input; el valor solo puede llegar como parámetro GET (`idGrupoMadre` en la URL) que queda en `$_REQUEST` porque el `<form>` no define `action` y reenvía sobre la URL actual con su query string. Dependencia frágil pero no necesariamente un bug. |
| `iglesias_reconocidas` | Se calcula (`= 0` hardcodeado) pero **ni siquiera aparece en la lista de columnas del INSERT/UPDATE** — variable muerta que no llega a escribirse en BD. |

### Lógica INSERT / UPDATE / DELETE

Controlada por `$_POST["funcion"]` (`subcategoria-ecc.php:128`):

- **`"insertar"`** (`subcategoria-ecc.php:146-763`): valida que `usua_id`
  exista y sea `usuario.tipo = 163`; si no, `$error_datos = 4`. Inserta en
  `sat_reportes` (`412-642`), obtiene `$ultimoId`, y si
  `$bautizadosPeriodo > 0` inserta las filas de `tbl_adjuntos`
  (`657-688`). Luego sube/comprime hasta 3 fotos.
- **`"eliminar"`** (`subcategoria-ecc.php:765-770`): `DELETE FROM
  sat_reportes WHERE id = '$idReporteActual'` — **borrado físico**, sin
  soft-delete ni validación adicional de permisos en ese bloque.
- **`"actualizar"`** (`subcategoria-ecc.php:771-1354`): misma validación de
  usuario, `UPDATE sat_reportes` (`965-1097`), actualiza filas existentes de
  `tbl_adjuntos` (`1103-1173`), inserta las nuevas filas de `act_bau_*`
  agregadas (`1199-1251`), y reemplaza `archivo1/2/3` si llegaron nuevos.
- ⚠️ **Efecto secundario en cada GET**: si viene `$_REQUEST["id"]` y el
  perfil de sesión es `162` o `163`, se ejecuta `UPDATE sat_reportes SET
  mapeo_fecha = CURDATE() WHERE id = ...` (`subcategoria-ecc.php:96-112`)
  **con solo abrir la página para ver el reporte**, no solo al guardar —
  esto pisa silenciosamente `mapeo_fecha` en cada visualización.

### Relaciones (JOIN) relevantes para combos/selects

- **Buscador de usuario ("Pastor/Plantador/Entrenador")**:
  `obtenerOpcionesUsuarioEcc()` (`subcategoria-ecc.php:47-69`) —
  `SELECT DISTINCT U.id, U.nombre FROM usuario AS U WHERE U.id != 2 AND
  U.tipo = '163' ORDER BY U.nombre ASC`.
- **Zona/Regional del reporte** (solo lectura, cabecera de edición,
  `subcategoria-ecc.php:1404-1418`) confirma el mismo patrón ya documentado
  en `usuario_empresa`:
  ```sql
  SELECT CA.descripcion AS zona, C.descripcion AS regional, ...
  FROM sat_reportes
  LEFT JOIN usuario AS U ON U.id = sat_reportes.idUsuario
  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
  LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
  LEFT JOIN categorias AS CA ON CA.id = C.idSec
  ```
  Es decir: `usuario_empresa.empresa_pd → categorias.id` da la "regional", y
  `categorias.idSec` de esa fila da la "zona" superior.
- **Combo "Provincia"**: `SELECT id_departamento, departamento FROM
  dane_departamentos ORDER BY departamento asc`.
- **Combo "Municipio"**: no se arma inline; se delega a AJAX a
  `datos_ubicacion.php` con `id_depa`, que devuelve el HTML del
  `<select name="municipio">`.
- **`sat_grupos`**: solo se lee (`sat_grupos.nombre` vía
  `idGrupoMadre`); el bloque que creaba un grupo madre nuevo
  (`INSERT INTO sat_grupos`) está completamente comentado
  (`subcategoria-ecc.php:328-400`) — todo reporte de ECC cuelga de un
  `idGrupoMadre` preexistente que llega por la URL.
- **`tbl_regional_ubicacion`** (combo "Cárcel ubicación", filtrado por
  `reub_reg_fk = $_SESSION['empresa_pd']`) existe en el código pero está
  dentro de HTML comentado — coherente con que `sitioReunion` esté muerto.

============================================================
## FORMULARIO: gestionar-sub-programa-ecc.php
============================================================

**No es un duplicado de `subcategoria-ecc.php`.** Es un formulario "hub"
(4223 líneas) que atiende **varios flujos distintos** seleccionados por
`$_REQUEST["generacion"]` ([gestionar-sub-programa-ecc.php:344-345](gestionar-sub-programa-ecc.php#L344-L345)),
más un módulo totalmente ajeno de inventario. Comparte estructura y muchos
nombres de campo con `subcategoria-ecc.php` (evidencia de copy-paste), pero
diverge en el manejo de `rep_tip` y en el alcance funcional.

### `rep_tip` — variable según la generación

| Generación (`$_REQUEST["generacion"]`) | `rep_tip` asignado |
|---|---|
| `EVAN` (capacitadores) | **317** fijo (`:461-462`) |
| `GCEL` (gran celebración) | **327** fijo (`:463-464`) |
| `CERO` / `OTRA` | **nunca se asigna** — variable indefinida → se guarda vacío/0 |
| `SOPA` (deshidratados) | N/A — no usa `sat_reportes` |

⚠️ `consultar-sub-programa-ecc.php` filtra `rep_tip IN (308, 317, 327)`. Un
reporte creado aquí con `generacion=CERO/OTRA` **queda huérfano** (invisible
en el listado). En la práctica casi no ocurre: la pantalla de selección
normal solo ofrece 2 botones ("Actividades de capacitadores" y "Gran
celebración"); el tercer botón visible enlaza directamente a
`subcategoria-ecc.php` en vez de generar `generacion=CERO`.

**Diferencia con `subcategoria-ecc.php`:** ese archivo es para el usuario de
campo (siempre `rep_tip=308`); este es para **capacitadores/coordinadores**
(perfil `162`/`163`) que reportan evangelismo (317) y gran celebración
(327), y además sirve como pantalla de edición genérica para cualquier
reporte ECC ya existente sin importar su `rep_tip` original.

### Mapa de campos → columna

| Label visible | Input | Columna |
|---|---|---|
| Selector de tags de usuario | `usua_id` (hidden, multi-tag JS) | `idUsuario` |
| Plantador/Pastor/Líder (solo si generación no es 77/8) | `plantador` | `plantador` |
| Fecha del reporte (readonly) | `fechaReporte` | `fechaReporte` |
| Fecha de inicio | `fechaInicio` | `fechaInicio` |
| Barrio (Evento) | `pabellon` | `pabellon` |
| Dirección / Método de evangelismo | `direccion` | `direccion` |
| Ciudad (Evento) | `ciudad` | `ciudad` |
| Grupo madre / Denominación | `grupoMadre_txt` | `grupoMadre_txt` |
| Generación | `generacionNumero` | `generacionNumero` |
| Asistencia hom/muj/jov/nin | `final_asistencia_hom/muj/jov/nin` | `asistencia_hom/muj/jov/nin` |
| Bautizados, discipulado, decisiones, preparándose | `final_bautizados`, `final_discipulado`, `final_desiciones`, `final_preparandose` | columnas homónimas |
| Fotos de bautizos | `act_bau_img[]`, `act_bau_fec[]`, `act_bau_can[]` | → **`tbl_adjuntos`** |
| Foto del grupo | `archivo1` | `ext1` |
| Activo/Inactivo | `inactivo` | `inactivo` |
| Comentarios | `final_comentarios` | `comentario` (solo si `generacionNumero==8`) |
| Provincia | `departamento` (select real y funcional) | **no se guarda en ninguna parte** |

### Discrepancias verificadas

- **`capacitacion_txt`** — leído y guardado en INSERT/UPDATE, pero el único
  input está dentro de un comentario PHP `/* ... */`
  (`gestionar-sub-programa-ecc.php:1161-1165`). Siempre vacío.
- **`sitioReunion`** — comentado en HTML `<!-- -->`
  (`gestionar-sub-programa-ecc.php:1208-1210`). Siempre vacío.
- **`comentario`** — mismo patrón que en `subcategoria-ecc.php`: el textarea
  `final_comentarios` solo aparece si `generacionNumero == 8` (valor
  legado, no seleccionable hoy). Siempre vacío para reportes nuevos.
- **`departamento`** — caso inverso: el `<select>` **sí es real y
  visible**, con datos de `dane_departamentos`, pero su valor **nunca se
  incluye** en el INSERT/UPDATE de `sat_reportes`
  (`gestionar-sub-programa-ecc.php:2101-2162` vs. columnas del INSERT
  `:511-563` / UPDATE `:787-831`) — se pierde silenciosamente, solo sirve
  para la cascada JS del combo de municipio.
- **`fechaReporte` en modo actualizar** — se captura del POST pero **no
  aparece en el UPDATE SET** (`:787-831`) — queda congelada en el valor del
  INSERT original, nunca se puede corregir después.
- **`idGrupoMadre`** y **`rep_tip`** tampoco se actualizan en modo
  `actualizar` (ausentes del UPDATE SET).
- **`rep_ndis`** no existe en este archivo (0 ocurrencias) — no aplica el
  bug encontrado en `subcategoria-ecc.php`.

### `tbl_adjuntos`

Igual que en `subcategoria-ecc.php`: usado solo para fotos de "bautizos"
(`act_bau_*`), **nunca asigna `adj_tip`**
(`gestionar-sub-programa-ecc.php:633-655`, `863-885`, `903-925`).

### Lógica INSERT / UPDATE / DELETE y efectos secundarios

- Distinción por `$_POST["funcion"]`: `"insertar"` (`:379`), `"actualizar"`
  (`:704`), `"eliminar"` (`:700-703`) — el `DELETE` es un simple `DELETE
  FROM sat_reportes`, **no borra los `tbl_adjuntos` ni los archivos físicos
  asociados**, quedan huérfanos.
- ⚠️ **Mismo efecto secundario en GET que en `subcategoria-ecc.php`**: cada
  visita con `?id=X` dispara `UPDATE sat_reportes SET mapeo_fecha = NOW()`
  antes de procesar cualquier `$_POST["funcion"]`
  (`gestionar-sub-programa-ecc.php:354-362`) — aquí **sí está acotado a
  perfiles 162/163**, a diferencia de `subcategoria-ecc.php` donde no había
  esa condición.
- Bug de lógica: `gestionar-sub-programa-ecc.php:1337` tiene
  `if($generacionNumero == 77 && $generacionNumero == 8)` — condición
  **imposible** (debería ser `||`), esa etiqueta nunca se activa.
- Código muerto evidente de copy-paste desde un módulo de gestión de
  usuarios/vehículos ajeno: redirecciones a `doc=admin_usu4`
  (`:4014-4019`) y mensajes de error sobre "vehículo" (`:2035-2037`).
- Manejo de adjuntos en `actualizar` es más elaborado que en
  `subcategoria-ecc.php`: distingue fotos "antiguas a modificar"
  (`act_bau_id[]`, UPDATE `:863-886`) de "nuevas" (INSERT `:900-926`).

### Módulo completamente ajeno: "Deshidratados" (banco de alimentos)

Las líneas `gestionar-sub-programa-ecc.php:65-316` implementan un sistema de
inventario de comida (`$_POST["SendOpcion"]` = "1a", "2a", "1aa", "2aa",
"3aa"), con `INSERT INTO inventario` e `INSERT/UPDATE beneficiarios`. **No
tiene relación alguna con `sat_reportes` ni con ECC** — está incrustado en
el mismo archivo físico, aparentemente por conveniencia/descuido de
desarrollo. Usa su propio combo: `SELECT IdBeneficiado, Nombre FROM
beneficiarios ORDER BY 1` (`:2787`).

### JOINs relevantes

- Usuario responsable: `SELECT DISTINCT id, nombre FROM usuario WHERE id !=
  2 AND tipo = '163'` (`:41-63`, sin JOIN a `categorias`).
- Datos del reporte al editar: `SELECT sat_reportes.*, sat_grupos.nombre,
  U.id, U.nombre FROM sat_reportes LEFT JOIN sat_grupos ON sat_grupos.id =
  sat_reportes.idGrupoMadre LEFT JOIN usuario AS U ON U.id =
  sat_reportes.idUsuario WHERE sat_reportes.id = X GROUP BY
  sat_reportes.id` (`:1011-1016`).
- Reportes anterior/siguiente (navegación): subconsultas `MAX(id) WHERE id
  < X` / `MIN(id) WHERE id > X` sobre `sat_reportes` **sin filtrar por
  `rep_tip`** (`:1110,1119`) — la navegación puede saltar entre reportes de
  programas completamente distintos.

### Comparación con `subcategoria-ecc.php`

| Aspecto | `subcategoria-ecc.php` | `gestionar-sub-programa-ecc.php` |
|---|---|---|
| `rep_tip` | Fijo = 308 | Variable: 317 (EVAN), 327 (GCEL), indefinido para CERO/OTRA |
| Público objetivo | Usuario de campo | Capacitadores/coordinadores (162/163) + edición genérica |
| Módulos adicionales | No | Sí: inventario "Deshidratados" (tablas `inventario`, `beneficiarios`) |
| `rep_ndis` comentado | Sí, ambos modos | No existe el campo |
| `sitioReunion` comentado | Sí | Sí, igual |
| `comentario` atado a generación legado (`==8`) | Sí | Sí, idéntico |
| Efecto secundario UPDATE `mapeo_fecha` en GET | Sin condición de perfil | Acotado a perfiles 162/163 |
| Campo visible pero descartado al guardar | No reportado | `departamento` |
| `fechaReporte` en UPDATE | No verificado | Confirmado: se captura pero no se persiste |
| `adj_tip` en `tbl_adjuntos` | Sin asignar | Igual, sin asignar |

**Conclusión:** ambos archivos comparten un antepasado común de copy-paste
(mismos patrones de campos comentados, mismo condicional de
`generacionNumero==8`), pero han divergido en el manejo de `rep_tip` y en el
alcance funcional — `gestionar-sub-programa-ecc.php` es notablemente más
frágil por la cantidad de código heredado de otros módulos no relacionados.

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