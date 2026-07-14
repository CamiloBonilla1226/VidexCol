# CLAUDE.md — Proyecto PFCOLOMBIA

Este archivo es la base de conocimiento del proyecto **PFCOLOMBIA** para Claude Code. Contiene el modelo de datos y las reglas de negocio necesarias para asistir en el desarrollo y mantenimiento del sistema.

---

## 1. Descripción general del proyecto

**PFCOLOMBIA** es una aplicación web que gestiona el registro y seguimiento de reportes de programas ministeriales. Permite diligenciar formularios de distintos programas, cada uno asociado a una categoría (`categorias.id`), y almacena la información en la tabla correspondiente según el programa.

### Stack técnico

- **Backend:** PHP 7.4
- **Frontend:** Bootstrap 3 + jQuery
- **Base de datos:** MySQL, acceso mediante la clase propia `DBbase_Sql`
- **Autenticación:** basada en sesión (session-based auth)
- **Enrutamiento:** `index.php?doc=` (routing por parámetro `doc`)
- **Configuración para Claude Code:** `.claude/settings.local.json`, con skills organizadas en el directorio `skills/` del proyecto (ver sección 11)

---

## 2. Catálogo de programas (`categorias`, idSec = 305)

Todos los formularios que la aplicación permite diligenciar están registrados como categorías bajo `idSec = 305`. El campo `detalle` es el slug/código corto del programa.

| id (categorias) | descripcion                              | detalle  | Tabla donde se almacena |
|------------------|-------------------------------------------|----------|---------------------------|
| 307              | La Peregrinación del Prisionero (LPP)     | lpp      | `reporte_lpp` + tablas hijas |
| 308              | Cada Comunidad para Cristo (ECC / CM)     | ecc      | `reporte_cm` + `reporte_graduado_cm` |
| 317              | Instituto Bíblico                         | Donante  | `sat_reportes` (`rep_tip = 317`) |
| 318              | Evangelistas                              | eva      | `sat_reportes` (`rep_tip = 318`) |
| 319              | Proyecto Felipe                           | pf       | `sat_reportes` (`rep_tip = 319`) |
| 347              | ECOP                                      | ecop     | `sat_reportes` (`rep_tip = 347`) |

El campo `rep_tip` en `sat_reportes` corresponde exactamente a `categorias.id`.

---

## 3. Modelo de datos — Programa 307 (LPP)

### 3.1 Tabla principal: `reporte_lpp`

Almacena los reportes del programa **La Peregrinación del Prisionero**. `programa_id` es **siempre 307**.

| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id_lpp` | int(11) PK AUTO_INCREMENT | NO | Identificador único del reporte |
| `usuario_id` | int(11), índice | NO | Usuario que registra el reporte |
| `carcel_id` | int(11), índice | NO | Cárcel donde se realizó el programa |
| `programa_id` | int(11), índice, default 307 | NO | Programa (siempre LPP) |
| `fecha_reporte` | date | NO | Fecha del reporte |
| `periodo_trimestre` | tinyint(4) | NO | Trimestre (1, 2, 3 o 4) |
| `pabellon` | int(11) | Sí | Pabellón de la cárcel |
| `poblacion_total` | int(11), default 0 | NO | Población total del pabellón/establecimiento |
| `prisioneros_invitados` | int(11), default 0 | NO | Prisioneros invitados |
| `prisioneros_iniciaron` | int(11), default 0 | NO | Prisioneros que iniciaron el programa |
| `cursos_activos` | int(11), default 0 | NO | Cantidad de cursos activos |
| `total_graduados` | int(11), default 0 | NO | Total de graduados |
| `total_voluntarios_internos` | int(11), default 0 | NO | Voluntarios internos |
| `total_voluntarios_externos` | int(11), default 0 | NO | Voluntarios externos |
| `discipulos_pasaron_cm` | int(11) | Sí | Discípulos que pasaron al programa CM (308) |
| `costo_recursos` | decimal(12,2) | Sí | Costo total de recursos del período |
| `archivo_foto` | varchar(50) | Sí | Nombre/ruta de la fotografía del reporte |
| `archivo_testimonio` | varchar(50) | Sí | Nombre/ruta del testimonio |

### 3.2 Tablas hijas de `reporte_lpp`

Todas se relacionan mediante `id_reporte_lpp` → `reporte_lpp.id_lpp` (uno a muchos).

```
reporte_lpp (1)
    │
    ├───< reporte_interno_lpp (N)   → voluntarios internos
    ├───< reporte_externo_lpp (N)   → voluntarios externos
    └───< reporte_graduado_lpp (N)  → graduados
```

**`reporte_interno_lpp`** (voluntarios internos)
- `id_interno_lpp` PK AUTO_INCREMENT
- `id_reporte_lpp` (FK → `reporte_lpp.id_lpp`, índice)
- `nombre` varchar(160) NOT NULL
- `identificacion` varchar(160) NOT NULL
- `fecha_registro` date NOT NULL

**`reporte_externo_lpp`** (voluntarios externos)
- `id_externo_lpp` PK AUTO_INCREMENT
- `id_reporte_lpp` (FK → `reporte_lpp.id_lpp`, índice)
- `nombre` varchar(160) NOT NULL
- `identificacion` varchar(160) NOT NULL
- `fecha_registro` date NOT NULL

**`reporte_graduado_lpp`** (graduados)
- `id_graduado_lpp` PK AUTO_INCREMENT
- `id_reporte_lpp` (FK → `reporte_lpp.id_lpp`, índice)
- `nombre` varchar(160) NOT NULL
- `identificacion` varchar(160) NOT NULL
- `fecha_registro` date NOT NULL

Cada `reporte_lpp` puede tener cero, uno o varios registros en cada una de las tres tablas hijas de forma independiente.

---

## 4. Modelo de datos — Programa 308 (Cada Comunidad para Cristo / CM)

### 4.1 Tabla principal: `reporte_cm`

Almacena los reportes de confraternidades del programa CM. `programa_id` es **siempre 308**. El reporte puede ser de tipo `INTRA` (intramuro) o `EXTRA` (extramuro).

**Identificación y control**
| Campo | Tipo | Descripción |
|---|---|---|
| `id_cm` | int(11) PK AUTO_INCREMENT | Identificador único |
| `programa_id` | int(11), default 308, índice | Siempre 308 |
| `tipo` | enum('INTRA','EXTRA'), índice | Tipo de confraternidad |
| `inactivo` | tinyint(1), default 0 | Reporte inactivo |
| `usuario_id` | int(11), índice | Usuario que registra |

**Datos generales**
| Campo | Tipo | Descripción |
|---|---|---|
| `entrenador` | varchar(100) | Nombre del entrenador |
| `siervo_facilitador` | varchar(255) | Nombre del siervo facilitador |
| `fecha_reporte` | date, índice | Fecha del reporte |
| `fecha_inicio_confraternidad` | date | Fecha de inicio |
| `carcel_id` | int(11), índice, nullable | Cárcel (si aplica, para INTRA) |
| `pabellon` | varchar(255), nullable | Pabellón |
| `departamento_id` | int(11), nullable | Departamento |
| `municipio_id` | int(11), índice, nullable | Municipio |
| `direccion` | varchar(255), nullable | Dirección de la confraternidad |
| `grupo_madre` | varchar(255) | Grupo madre |
| `nombre_grupo_iglesia` | varchar(255) | Nombre del grupo/iglesia |
| `generacion` | tinyint(1), default 1 | Generación del grupo |

**Indicadores de asistencia** (smallint): `asistencia_hombres`, `asistencia_mujeres`, `asistencia_jovenes`, `asistencia_ninos`, `asistencia_total`

**Indicadores ministeriales** (smallint): `miembros_bautizados`, `en_discipulado`, `decisiones_cristo`, `discipulos_lpp`, `preparandose_bautismo`, `bautizados_periodo`, `graduados_periodo`, `familias_ppl`, `familias_pospenados`

**Mapeo de madurez de la iglesia** (tinyint, salvo fecha/año):
`mapeo_fecha` (date), `mapeo_anho` (smallint), `mapeo_cuarto`, `mapeo_comprometido`, `mapeo_oracion`, `mapeo_companerismo`, `mapeo_adoracion`, `mapeo_biblia`, `mapeo_evangelizar`, `mapeo_cena`, `mapeo_dar`, `mapeo_bautizar`, `mapeo_trabajadores`

**Bautizos**: `bautizo_fecha` (date), `bautizo_cantidad` (smallint), `bautizo_foto_ext` (varchar(10))

**Graduaciones**: `graduacion_fecha` (date), `graduacion_cantidad` (smallint), `graduacion_curso_id` (int), `graduacion_foto_ext` (varchar(10))

**Archivos adjuntos**: `foto_confraternidad_ext` (varchar(10)), `testimonio_ext` (varchar(10))

**Auditoría**: `creacion_fecha` (datetime), `creacion_usuario` (int), `modificacion_fecha` (datetime), `modificacion_usuario` (int)

**Campo adicional**: `legacy_id` (int) — identificador de referencia en `sat_reportes`, usado para trazabilidad.

### 4.2 Tabla hija: `reporte_graduado_cm`

Almacena los graduados asociados a un reporte de confraternidad. Relación uno a muchos vía `id_cm`.

| Campo | Tipo | Descripción |
|---|---|---|
| `id_graduado_cm` | int(11) PK AUTO_INCREMENT | Identificador único del graduado |
| `id_cm` | int(11), índice | FK → `reporte_cm.id_cm` |
| `nombre` | varchar(255) NOT NULL | Nombre completo del graduado |
| `identificacion` | varchar(100) NOT NULL | Número de identificación |
| `legacy_adj_id` | int(11), índice, nullable | Identificador de referencia en `tbl_adjuntos`, usado para trazabilidad |

```
reporte_cm (1)
    │
    └───< reporte_graduado_cm (N)
```

---

## 5. Modelo de datos — Programas 317, 318, 319 y 347 (`sat_reportes`)

Los programas **Instituto Bíblico (317)**, **Evangelistas (318)**, **Proyecto Felipe (319)** y **ECOP (347)** almacenan su información en la tabla `sat_reportes`, complementada con `tbl_adjuntos` para los archivos.

### 5.1 Tabla `sat_reportes`

El programa al que pertenece cada reporte se identifica mediante `rep_tip`, cuyo valor corresponde al `id` de `categorias`:

- `rep_tip = 317` → Instituto Bíblico
- `rep_tip = 318` → Evangelistas
- `rep_tip = 319` → Proyecto Felipe
- `rep_tip = 347` → ECOP

**Identificación y control**
| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | int(11) PK AUTO_INCREMENT | NO | Identificador único del reporte |
| `idUsuario` | int(11) | NO | Usuario que registra el reporte |
| `inactivo` | int(11), default 0 | NO | Indica si el reporte está inactivo |
| `rep_tip` | int(11) | Sí | Identifica el programa (`categorias.id`) |

**Datos generales del grupo/reporte**
| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `comentario` | text | Sí | Comentarios del reporte |
| `idGrupoMadre` | varchar(50) | NO | Identificador del grupo madre |
| `generacionNumero` | int(11) | NO | Número de generación |
| `plantador` | varchar(255) | NO | Nombre del plantador |
| `fechaReporte` | date | NO | Fecha del reporte |
| `fechaInicio` | date | NO | Fecha de inicio del grupo |
| `sitioReunion` | int(11) | NO | Sitio de reunión |
| `grupoMadre_txt` | varchar(255) | NO | Nombre del grupo madre |
| `nombreGrupo_txt` | varchar(255) | NO | Nombre del grupo o iglesia |
| `capacitacion_txt` | varchar(255) | NO | Información de capacitación |
| `pabellon` | varchar(255) | NO | Pabellón |
| `direccion` | varchar(255) | NO | Dirección |
| `ciudad` | int(11) | Sí | Ciudad |

**Indicadores de asistencia** (int): `asistencia_total`, `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin`

**Indicadores ministeriales** (int): `bautizados`, `discipulado`, `desiciones`, `preparandose`, `bautizadosPeriodo`, `graduados`, `graduadosPeriodo`, `iglesias_reconocidas`

**Información de mapeo (madurez de la iglesia)**
| Campo | Tipo |
|---|---|
| `mapeo_anho` | varchar(10) |
| `mapeo_cuarto` | varchar(25) |
| `mapeo_fecha` | date |
| `mapeo_comprometido` | varchar(25) |
| `mapeo_oracion` | varchar(25) |
| `mapeo_companerismo` | varchar(25) |
| `mapeo_adoracion` | varchar(25) |
| `mapeo_biblia` | varchar(25) |
| `mapeo_evangelizar` | varchar(25) |
| `mapeo_cena` | varchar(25) |
| `mapeo_dar` | varchar(25) |
| `mapeo_bautizar` | varchar(50) |
| `mapeo_trabajadores` | varchar(25) |

**Campos adicionales del reporte**
| Campo | Tipo | Descripción |
|---|---|---|
| `rep_ndis` | int(11) | — |
| `rep_nuevo` | int(11) | — |
| `rep_entr` | varchar(50) | — |
| `rep_text2` | text | — |
| `rep_text3` | text | — |
| `rep_text4` | text | — |

**Indicadores adicionales**
| Campo | Tipo | Descripción |
|---|---|---|
| `number_person_without_freedom` | int(11) | Personas privadas de la libertad |
| `number_person_post_penalties` | int(11) | Personas pospenadas |

**Unidades**
| Campo | Tipo |
|---|---|
| `unidad_2` | int(11) |
| `unidad_3` | int(11) |
| `unidad_4` | int(11) |
| `unidad_5` | int(11) |
| `unidad_6` | int(11) |
| `unidad_total` | int(11) |

**Extensiones de archivos**: `ext1`, `ext2`, `ext3` (varchar(10) cada una)

**Auditoría**
| Campo | Tipo |
|---|---|
| `creacionFecha` | datetime |
| `creacionUsuario` | int(11) |
| `modificacionFecha` | date |
| `modificacionUsuario` | int(11) |

### 5.2 Tabla `tbl_adjuntos`

Complementa la información de `sat_reportes`. Registra los archivos asociados a cada reporte (fotografías, testimonios u otros documentos).

| Campo | Tipo | Descripción |
|---|---|---|
| `adj_id` | int(11) PK AUTO_INCREMENT | Identificador del archivo |
| `adj_nom` | varchar(1000) | Nombre del archivo |
| `adj_url` | varchar(50) | Ruta o nombre físico del archivo |
| `adj_fec` | date | Fecha del archivo |
| `adj_can` | varchar(30) | Cantidad asociada al archivo (cuando aplica) |
| `adj_curso` | int(11) | Curso relacionado con el archivo |
| `adj_tip` | int(11) | Tipo de archivo |
| `adj_rep_fk` | int(11) | FK → `sat_reportes.id` |
| `adj_etap` | varchar(1) | Etapa del proceso a la que pertenece el archivo |

```
sat_reportes (1)
      │
      └──────< tbl_adjuntos (N)
```

Cada reporte de `sat_reportes` puede tener cero, uno o varios archivos asociados en `tbl_adjuntos`.

---

## 6. Tabla `usuario`

Almacena los usuarios del sistema (administradores, encargados de programas, plantadores, etc.). Es la tabla referenciada por los campos `usuario_id` / `idUsuario` / `creacionUsuario` / `modUsuario` de las tablas de reportes.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int(11) PK AUTO_INCREMENT | Identificador único del usuario |
| `tipo` | int(11) | Tipo de usuario |
| `tipo_user_cli` | int(11) | Tipo de usuario cliente |
| `nombre` | varchar(255) | Nombre del usuario |
| `identificacion` | varchar(50) | Número de identificación |
| `tipoIdentificacion` | int(11) | Tipo de documento de identificación |
| `direccion` | varchar(255) | Dirección |
| `telefono1` | varchar(50) | Teléfono fijo |
| `celular` | varchar(50) | Celular |
| `email` | varchar(255) | Correo electrónico |
| `url` | varchar(255) | URL asociada |
| `url2` | text | URL adicional |
| `observaciones` | text | Observaciones |
| `login` | varchar(50) | Usuario de acceso (login) |
| `password` | varchar(255) | Contraseña (hash) |
| `superusuario` | tinyint(4) | Indica si el usuario es superusuario |
| `acceso` | tinyint(1) | Indica si el usuario tiene acceso habilitado |
| `acceso_graphs` | tinyint(4) | Indica acceso a gráficas/reportes analíticos |
| `creacionUsuario` | int(11) | Usuario que creó el registro |
| `creacionFecha` | varchar(25) | Fecha de creación |
| `modUsuario` | int(11) | Usuario que hizo la última modificación |
| `modFecha` | date | Fecha de la última modificación |
| `usua_muni` | int(11) | Municipio asociado al usuario |
| `usua_pais` | varchar(50) | País asociado al usuario |
| `excluido_reportes` | tinyint(1) | Indica si el usuario está excluido de los reportes |

---

## 7. Diagrama general de relaciones

```
categorias (idSec = 305)
    │
    ├── 307 LPP   ──► reporte_lpp ──┬──< reporte_interno_lpp
    │                                ├──< reporte_externo_lpp
    │                                └──< reporte_graduado_lpp
    │
    ├── 308 CM    ──► reporte_cm ───< reporte_graduado_cm
    │
    └── 317 / 318 / 319 / 347 ──► sat_reportes (rep_tip) ──< tbl_adjuntos
```

---

## 8. Reglas de negocio clave

- El programa de un formulario **nunca lo elige el usuario libremente**: cada formulario está atado a un `programa_id` / `rep_tip` fijo (307, 308, 317, 318, 319 o 347).
- **LPP (307):** el reporte se organiza por trimestre (`periodo_trimestre`) y cárcel/pabellón. Los voluntarios internos, externos y graduados se registran en tablas hijas independientes; no hay límite ni relación obligatoria entre ellas.
- **CM (308):** el reporte distingue entre confraternidades **INTRA** (dentro de la cárcel, usa `carcel_id`/`pabellon`) y **EXTRA** (fuera de la cárcel, usa `departamento_id`/`municipio_id`/`direccion`). `programa_id` siempre es 308.
- **317, 318, 319, 347:** comparten la misma estructura de tabla (`sat_reportes` + `tbl_adjuntos`); se diferencian únicamente por el valor de `rep_tip`. Los campos de asistencia, indicadores ministeriales y mapeo de madurez de la iglesia son comunes a los cuatro programas.

---

## 9. Convenciones de trabajo con Claude Code

- No modificar estilos/CSS existentes salvo que se solicite explícitamente (regla de la skill de frontend).
- Al trabajar sobre LPP o CM, las consultas deben apuntar a `reporte_lpp`/`reporte_cm` (y sus tablas hijas), no a `sat_reportes`.
- Al trabajar sobre Instituto Bíblico, Evangelistas, Proyecto Felipe o ECOP, las consultas deben apuntar a `sat_reportes` filtrando por `rep_tip`, junto con `tbl_adjuntos` para archivos.
- Al proponer cambios de SQL, generar sentencias explícitas y específicas, evitando modificaciones no solicitadas fuera del alcance pedido.
- Usar la clase `DBbase_Sql` para el acceso a datos, siguiendo el patrón ya existente en el proyecto.

---

## 10. Pendientes / información a completar

- Documentar endpoints/archivos PHP concretos de cada formulario (ej. `gestionar-sub-programa-lpp.php`, `crear-reporte-lpp.php`, `gestionar-sub-programa-ecc.php`) si se desea que este archivo también sirva como mapa de archivos del proyecto.
- Confirmar el significado exacto de campos sin descripción detallada en `sat_reportes` (`rep_ndis`, `rep_nuevo`, `rep_entr`, `rep_text2/3/4`, `sitioReunion`, `unidad_2` a `unidad_6`) para documentarlos con precisión.

---

## 11. Skills del proyecto

El proyecto cuenta con skills propias ubicadas en `skills/` dentro de la raíz del repositorio, con la siguiente estructura:

```
skills/
├── skillfront-skill/
│   └── SKILL.md
└── skillback-skill/
    └── SKILL.md
```

### `skillfront-skill`

Skill de **frontend** (PHP + Bootstrap 3 + jQuery). Se activa al trabajar en vistas, formularios, tablas, modales o cualquier cambio visual/UX. Sus lineamientos principales:

- Diseño **minimalista y agradable**, respetando la paleta de colores y estilos ya existentes en el proyecto (no introducir colores o estilos nuevos sin necesidad).
- Diseño **responsive** usando el grid de Bootstrap 3.
- Mantener los patrones ya establecidos en el proyecto: selectores dependientes vía AJAX, tablas de filas dinámicas con contador, subida de archivos con progreso, y validaciones de negocio **visibles** para el usuario (nunca corrección silenciosa de datos).
- No modificar estilos de otras pantallas ni del layout general si no se pidió explícitamente.

### `skillback-skill`

Skill de **backend** (PHP 7.4 + MySQL vía `DBbase_Sql`). Se activa al escribir o modificar consultas SQL, lógica de formularios, o cualquier archivo PHP del lado servidor. Sus lineamientos principales:

- Consultas **seguras**: siempre prepared statements/parámetros, nunca concatenación directa de variables en el SQL.
- Consultas **óptimas**: evitar `SELECT *`, evitar el patrón N+1, usar los índices existentes, paginar listados largos, usar transacciones al insertar reporte + tablas hijas.
- Validaciones de negocio explícitas y visibles, nunca silenciosas.
- Respetar los límites de longitud de columnas para evitar truncación de datos (bug ya ocurrido en el proyecto).
- Dirigir cada consulta a la tabla correcta según el programa (307/308 → tablas nuevas; 317/318/319/347 → `sat_reportes`), sin mezclar modelos.

> Claude debe consultar estas skills automáticamente cuando la tarea implique cambios de interfaz (`skillfront-skill`) o de lógica/consultas de servidor (`skillback-skill`), incluso si el usuario no las menciona explícitamente por nombre.