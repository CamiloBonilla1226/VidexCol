# CLAUDE.md — Satura APP

## Descripción del proyecto

**Satura APP** es un sistema web de gestión de grupos y reportes para una organización ministerial/iglesia. Permite registrar grupos, sus actividades y diferentes tipos de reportes (evangelismo, gran celebración, bautizo, coach).

Stack: **PHP** + **MySQL** (administrado con phpMyAdmin)  
Ruta local: `C:\Users\LENOVO\Desktop\Videx\Videx`

---

## Base de datos

### Tabla principal: `sat_reportes`

Esta tabla cumple **doble función**: almacena tanto **grupos** como **reportes** en la misma tabla.

#### Distinguir un grupo de un reporte

| Condición | Significa |
|---|---|
| `id_grupo = 0` | El registro **ES un grupo** |
| `id_grupo > 0` | El registro **ES un reporte**, y pertenece al grupo con ese `id` |

> Un reporte siempre pertenece a un grupo. Para obtener el grupo de un reporte: `WHERE id = reporte.id_grupo`

#### Herencia de datos

Un reporte hereda atributos del grupo padre. Por ejemplo, si el grupo es de generación 2, todos sus reportes también son de generación 2. Los campos heredados relevantes incluyen `generacionNumero`, `grupoMadre_txt`, `nombreGrupo_txt`, `idGrupoMadre`, entre otros.

---

### Actividades (`id_actividad`)

Los reportes pueden tener las siguientes actividades. Son **independientes al grupo**:

| id_actividad | Nombre |
|---|---|
| `1` | Coach |
| `2` | Ninguna |
| `5` | Otra actividad |
| `8` | Gran Celebración |
| `10` | Siembra abundante |
| `11` | Caminata de oración |
| `12` | Identificar al hijo de paz |
| `13` | Oración Exp y Ferviente |
| `14` | Taller |
| `77` | Evangelismo |
| `99` | Bautizo |
| `100` | Capacitación |

---

### Campos de la tabla `sat_reportes`

```
id                  INT PK AUTO_INCREMENT
idUsuario           INT
inactivo            TINYINT
idGrupoMadre        INT
id_grupo            INT   → 0 = es grupo, >0 = id del grupo padre
id_actividad        INT   → ver tabla de actividades
generacionNumero    INT
plantador           VARCHAR(255)
fechaReporte        DATE
fechaInicio         DATE
sitioReunion        VARCHAR(255)
grupoMadre_txt      VARCHAR(255)
nombreGrupo_txt     VARCHAR(255)
capacitacion_txt    VARCHAR(255)
barrio              VARCHAR(255)
direccion           VARCHAR(255)
ciudad              VARCHAR(255)
asistencia_total    INT
asistencia_hom      INT
asistencia_muj      INT
asistencia_jov      INT
asistencia_nin      INT
bautizados          INT
discipulado         INT
desiciones          INT
preparandose        INT
bautizadosPeriodo   INT
iglesias_reconocidas INT
creacionFecha       DATETIME
creacionUsuario     INT
modificacionFecha   DATE
modificacionUsuario INT
ext1                VARCHAR(10)
ext2                VARCHAR(10)
mapeo_anho          INT
mapeo_cuarto        TINYINT
ext3                VARCHAR(10)
mapeo_fecha         DATE
mapeo_comprometido  TINYINT  → 0/1 (¿comprometido como iglesia?)
mapeo_oracion       TINYINT  → 1-4
mapeo_companerismo  TINYINT  → 1-4
mapeo_adoracion     TINYINT  → 1-4
mapeo_biblia        TINYINT  → 1-4
mapeo_evangelizar   TINYINT  → 1-4
mapeo_cena          TINYINT  → 1-4
mapeo_dar           TINYINT  → 1-4
mapeo_bautizar      TINYINT  → 1-4
mapeo_trabajadores  TINYINT  → 1-4
comentario          TEXT
```

---

### Tabla: `usuario`

Almacena los usuarios del sistema (personas y/o clientes con acceso a la plataforma).

```
id                     INT PK AUTO_INCREMENT
tipo                   INT(1)
tipo_user_cli          INT
nombre                 VARCHAR(255)
identificacion         VARCHAR(50)
tipoIdentificacion     INT(4)
direccion              VARCHAR(255)
telefono1              VARCHAR(50)
telefono2              VARCHAR(50)
celular                VARCHAR(50)
celular2               VARCHAR(50)
email                  VARCHAR(255)
url                    VARCHAR(255)
url2                   TEXT
observaciones          TEXT
login                  VARCHAR(50)
password               VARCHAR(255)
superusuario           TINYINT
acceso                 TINYINT(1)
acceso_graphs          TINYINT
creacionUsuario        INT
creacionFecha          DATE
modUsuario             INT
modFecha               DATE
usua_muni              INT           → FK/índice (MUL)
lat                    VARCHAR(10)
lon                    VARCHAR(12)
aviso                  VARCHAR(255)
excluido_reportes      TINYINT(1)    → default 0
```

- `id` referencia a `idUsuario` en `sat_reportes` (creador/dueño del registro) y es la PK que enlaza con `usuario_empresa.idUsuario`.
- `superusuario` y `acceso` controlan permisos/nivel de acceso a la plataforma.
- `excluido_reportes = 1` indica que el usuario debe excluirse de los cálculos/listados de reportes.

---

### Tabla: `usuario_empresa`

Datos de la empresa/organización asociada a un usuario. Relación **1 a 1** con `usuario` (la PK es `idUsuario`, sin autoincremento propio).

```
idUsuario                 INT PK        → FK a usuario.id
empresa_tipo              INT
empresa_nombre             VARCHAR(255)
empresa_nit               VARCHAR(50)
empresa_representante     VARCHAR(255)
empresa_contacto          VARCHAR(255)
empresa_direccion         VARCHAR(255)
empresa_url               VARCHAR(255)
empresa_telefono1         VARCHAR(50)
empresa_telefono2         VARCHAR(50)
empresa_celular1          VARCHAR(50)
empresa_celular2          VARCHAR(50)
empresa_email1            VARCHAR(255)
empresa_email2            VARCHAR(255)
empresa_cargo             VARCHAR(255)
empresa_aprobacion        DECIMAL(12,0)
empresa_paisid            INT
empresa_pais              VARCHAR(255)
empresa_socio             VARCHAR(255)
empresa_proceso           VARCHAR(255)
empresa_pd                VARCHAR(255)
empresa_sitio_cor         VARCHAR(255)
empresa_sitio             VARCHAR(255)
empresa_rm                VARCHAR(255)
empresa_circuito          INT
```

- Para obtener los datos de empresa de un usuario: `JOIN usuario_empresa ue ON ue.idUsuario = u.id`.
- `empresa_aprobacion` indica el estado/nivel de aprobación de la empresa.
---

## Tipos de reporte y sus campos

### Reporte de Evangelismo (`id_actividad = 77`)
- `fechaReporte`
- `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin`, `asistencia_total`
- `desiciones` (decisiones de fe)
- `comentario`
- foto (campo/mecanismo a definir)

### Reporte de Gran Celebración (`id_actividad = 8`)
- `fechaReporte`
- `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin`, `asistencia_total`
- `comentario`
- foto

### Reporte de Bautizo (`id_actividad = 99`)
- `fechaReporte`
- `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin`, `asistencia_total`
- `bautizados`
- foto

### Reporte de Coach (`id_actividad = 1`)
- `fechaReporte`
- `asistencia_hom`, `asistencia_muj`, `asistencia_jov`, `asistencia_nin`, `asistencia_total`
- `discipulado`
- `desiciones` (decisiones de fe)
- `preparandose`
- foto
- **Campos de mapeo de madurez** (escala 1–4, excepto `mapeo_comprometido` que es 0/1):

| Campo | Pregunta |
|---|---|
| `mapeo_comprometido` | ¿Este grupo está comprometido como iglesia? |
| `mapeo_oracion` | Oración |
| `mapeo_companerismo` | Compañerismo |
| `mapeo_adoracion` | Adoración |
| `mapeo_biblia` | Aplicar la Biblia |
| `mapeo_evangelizar` | Evangelizar |
| `mapeo_cena` | Cena del Señor |
| `mapeo_dar` | Dar ofrenda |
| `mapeo_bautizar` | Bautizar |
| `mapeo_trabajadores` | Entrenar nuevos líderes |

---

## Reglas importantes al generar código

1. **Nunca confundir grupo con reporte.** Siempre verificar `id_grupo` antes de tratar un registro.
2. **Al crear un reporte**, siempre asociarlo a un grupo existente (`id_grupo > 0`).
3. **Al listar grupos**, filtrar con `id_grupo = 0`.
4. **Al listar reportes de un grupo**, filtrar con `id_grupo = {id_del_grupo}`.
5. **Los campos de mapeo** solo aplican al reporte de Coach (`id_actividad = 1`).
6. **`mapeo_comprometido`** solo acepta `0` o `1`. Los demás campos `mapeo_*` aceptan valores del `1` al `4`.
7. Al hacer JOINs de un reporte con su grupo: `JOIN sat_reportes g ON g.id = r.id_grupo`.

---

