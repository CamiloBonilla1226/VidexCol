# Análisis del proyecto VIDEX — qué entendí

> Documento de análisis. **No se modificó ningún archivo del proyecto**; solo se creó este archivo.
> Fecha: 2026-11-06. Revisado: ~235 archivos PHP/CSS/JS en la raíz del repo (sin framework, PHP "plano").

---

## 1. Resumen en una frase

VIDEX es un **CRM + control financiero en PHP/MySQL**. Se gestionan *prospectos/clientes*, se les hacen *cotizaciones*, y sobre cada cotización se registran **abonos (lo que el cliente paga = ingreso)** y **gastos (lo que cuesta ejecutarla = egreso)**. Además hay **gastos globales** (no pertenecen a ninguna cotización) y un módulo aparte de **Proyectos de Inversión**. Hay muchas gráficas (Google Charts) y exportación a Excel.

**¿Entendí lo que necesitas?** Sí en lo esencial, con una advertencia importante: **el sistema hoy NO tiene una clasificación explícita "directo vs. indirecto"** ni un reparto de los gastos globales entre proyectos (ver sección 5). Eso es justamente lo que habría que construir.

---

## 2. Base de datos

| Dato | Valor |
|---|---|
| Motor | MySQL / MariaDB (conexión con `mysqli`) |
| **Nombre de la BD principal** | **`twsselvr_videx_app`** |
| Host | `localhost` |
| Usuario | `twsselvr_root` |
| Definición de la conexión | clase `DBbase_Sql` en [funciones.php](funciones.php) (líneas ~11-40) |

Hallazgos sobre la conexión:

- ⚠️ **La contraseña de la BD está escrita en texto plano dentro de `funciones.php`** y ese archivo está en git. Recomendación: moverla a un archivo de configuración fuera del repositorio / variable de entorno y rotar la clave.
- En el mismo archivo hay clases heredadas de otros proyectos (`DBbase_SqlB` → `jocadima_gilberto_inversion`, `DBbase_SqlC` → `jocadima_gilberto`). Parecen código viejo reutilizado; **no son la BD de VIDEX**. Hay que confirmar si todavía se usan.
- **No existe un archivo `.sql` / dump** en el repo. El esquema de las tablas antiguas (cotizacion, gastos, abonos, cliente…) **no está versionado**; lo que sé de ellas viene de los `INSERT`/`SELECT` del código. Para ver tipos de datos exactos necesito un dump de la BD (`mysqldump --no-data`) o acceso de lectura.

---

## 3. Tablas principales y qué guardan

### 3.1 Núcleo comercial / financiero (tablas "legadas", sin CREATE en el repo)

| Tabla | Qué guarda | Columnas que se ven en el código |
|---|---|---|
| **`cliente`** | Prospectos/clientes (en la UI se llaman "prospectos") | `id, nombre, nit, representante_legal, direccion, telefono, celular, email, estado, tipoCliente, interno, observaciones, paginaweb…` |
| **`cotizacion`** | Cada cotización/proyecto vendido al cliente. **Es la unidad "proyecto" del lado comercial.** | `id, idCliente, idContacto, estado, estadoPago, categoriaPrincipal, metodoCotizacion, tipoContacto, descripcion, mensajecotizacion, valorCotizado, valorCobrado, valorRecibido, fechaCotizacion, fechaFacturacion, fechaUltimoPago, creacionUsuario, creacionFecha, modUsuario, modFecha` |
| **`abonos`** | **Pagos que el cliente hace** (INGRESOS) ligados a una cotización | `id, idCotizacion, idUsuario, idCliente, tipo, valor, observacion, fechaCreacion, fechaAuditoria, ext` |
| **`gastos`** | **Egresos**. Si `idCotizacion > 0` pertenece a una cotización; si `idCotizacion = 0` es **gasto global** | `id, idCotizacion, idUsuario, idCliente, tipo, valor, observacion, fechaCreacion, fechaAuditoria, ext` |
| **`categorias`** | Tabla de catálogos genérica (tipos, estados…). Se separa por sección `idSec` | `id, idSec, descripcion` |
| `contactos`, `seguimiento`, `seguimientocambios`, `campana` | Contactos del cliente, seguimientos comerciales, campañas | — |

**Catálogos en `categorias` (campo `idSec`)** — lo que pude identificar:
- `idSec = 24` → **Tipos de gasto** (se usa en `gasto_crear`, `pop_gasto`, `gastos`).
- Estados de cotización (ids de `categorias`): `62` Borrador, `63` Lista para enviar, `64` Enviada, `65` Aprobada, `66` Pendiente de pago, `67` Pagada, `68` No aprobada (sale de [dashboard.php](dashboard.php)).
- Otras secciones (estado de pago, categoría principal, método de cotización, tipo de contacto) se cargan con `COTIZACION_SECTION_*` en `cotizacion_config.php`.

### 3.2 Línea paralela de "Auditoría" (proyectos de auditoría)

| Tabla | Qué guarda |
|---|---|
| `proyecto_auditoria` | Proyectos de auditoría (`id_prospecto` → cliente) |
| `gastosauditoria` | Gastos de auditoría: `idProyecto, idUsuario, idCliente, idPersona, tipo, valor, observacion, fechaCreacion, ext` |
| `abonosauditoria` | Abonos de auditoría |
| `seguimientoauditoria` | Seguimientos de auditoría |
| `personal` | Personas (clave `cc` = cédula; `nombres`). Se usa para atribuir **costos directos por persona** |

> Esta línea es la que alimenta [graphs_costos_directos.php](graphs_costos_directos.php) y [excel_costos_directos.php](excel_costos_directos.php): costos por persona y por prospecto, abonos vs gastos, utilidad/pérdida.

### 3.3 Módulo "Gestión de Proyectos" (prefijo `gp_`, es de TI/infraestructura)

Estas tablas **se crean solas** (`CREATE TABLE IF NOT EXISTS` dentro de cada `*_service.php`).
`gp_proyecto`, `gp_servicio`, `gp_proveedor`, `gp_dom_dominio`, `gp_dom_proveedor`, `gp_ticket`, `gp_ticket_mensaje`, `gp_ticket_colaborador`, `gp_clave`, `gp_clave_log`, `gp_*_credencial`, `gp_alertas_email`, `gp_alertas_enviadas`.
Son dominios, servicios, proveedores, credenciales, tickets y alertas de vencimiento. **No son los proyectos financieros**; no los confundas con `cotizacion`.

### 3.4 Módulo "Proyecto de Inversión" (el más cercano a "cuánto se invirtió / cuánto genera")

Definido en [gp_inversion_service.php](gp_inversion_service.php) (tablas auto-creadas, borrado lógico con `inactivo`):

| Tabla | Qué guarda |
|---|---|
| `gp_inversion_proyecto` | Proyecto de inversión de un cliente (`cliente_id`, `nombre`, `descripcion`, `estado`) |
| `gp_inversion_cuenta` | Cuentas/bancos del proyecto (multi-depósito) |
| `gp_inversion_movimiento` | **Ingresos y egresos** (`tipo_movimiento` = `ingreso`/`egreso`), `fecha`, `valor DECIMAL(18,2)`, `moneda` (COP), `cuenta_id`, `tipo_ingreso`, `origen_fondos`, `destino_fondo`, `clasificacion_egreso`, `capital_asociado`, `concepto` |
| `gp_inversion_anexo` | Hasta 3 archivos adjuntos por movimiento (PDF/imagen/ZIP/XLSX…, máx. 10 MB, guardados fuera del docroot) |

Reglas de negocio ya implementadas ahí:
- `tipo_ingreso`: `aporte_capital`, `rendimiento` (utilidades), `retorno_inversion`.
- `clasificacion_egreso`: `gasto_operativo`, `inversion`.
- `calcularConsolidado()` → capital invertido, utilidades, retorno, gastos, nuevas inversiones, saldo neto y **ROI = utilidades / capital × 100**.
- También: saldo por cuenta, evolución mensual y rentabilidad por cuenta; informe ejecutivo imprimible en [gp_inversion_informe.php](gp_inversion_informe.php).
- Permisos propios: columnas `gp_inv_consulta`, `gp_inv_modificacion`, `gp_inv_eliminar` y `gp_inv_cliente_id` en la tabla `usuario` (agregadas por [run_migrations.php](run_migrations.php)). Un inversionista externo ve solo su proyecto, en lectura.

### 3.5 Seguridad, SMS y correo

- `usuario` (tabla central de usuarios; perfil, `superusuario`, `acceso_solo_lectura`, `clientes_filtro`…), `usuarios_menu`, `usuarios_menu_graphs` (qué menús/gráficas ve cada usuario), `menu`, `menu_graphs`, `usuarios_clientes` (qué clientes puede ver cada usuario), `usuario_relacion`.
- SMS: `sms_usuarios`, `sms_grupos`, `sms_historico`, `sms_asociacion`, `sms_usuarios_documentos_add`.
- Correo masivo: `mail_config`, `mail_workqueue`, `mail_historico`, `mail_audit`, `mail_blacklist`, `mail_queue_control`.
- Otras con restos de otro sistema (flota/vehículos): `veh_solicitudes_cot*`, `vehiculo_usuarios`, `estudiante` (código heredado).

---

## 4. Cómo fluye el dinero hoy

```
cliente (prospecto)
   └─ cotizacion  (valorCotizado / valorCobrado / valorRecibido, estado, fechas)
         ├─ abonos   (idCotizacion = X)  → lo que NOS PAGAN        (ingreso)
         └─ gastos   (idCotizacion = X)  → lo que cuesta ESE trabajo (egreso "del proyecto")

gastos con idCotizacion = 0  → "GASTOS GLOBALES"  (pantalla gastos.php / gasto_crear.php)
                                no pertenecen a ningún proyecto ni cliente
```

- Los gastos globales se listan en [gastos.php](gastos.php) (filtro `WHERE gastos.idCotizacion = 0`) y se crean en [gasto_crear.php](gasto_crear.php) (inserta con `idCotizacion = 0` y **sin `idCliente`**).
- Los gastos por cotización se crean desde [pop_gasto.php](pop_gasto.php) (guarda `idCotizacion` e `idCliente`). Se ven en [int_gastos.php](int_gastos.php).
- Los abonos se crean desde [pop_abono.php](pop_abono.php).
- Cada gasto/abono admite un archivo anexo guardado como `archivos/abn{id}.{ext}`.

### Gráficas y reportes que ya existen
| Archivo | Qué muestra |
|---|---|
| [dashboard.php](dashboard.php) | Dashboard comercial anual: seguimientos, cotizaciones por estado, aprobadas vs pendientes vs rechazadas, **facturación mes a mes**, gastos y abonos por tipo |
| [graphs_008.php](graphs_008.php) | Tipos de gasto y tipos de abono; tiene un check **"globales"** (`idCotizacion = 0`) |
| [graphs_costos_directos.php](graphs_costos_directos.php) | Por prospecto: costos por persona (torta), abonos vs gastos por mes, flujo de caja, % utilidad/pérdida |
| [excel_costos_directos.php](excel_costos_directos.php) | Exporta a Excel esos costos y gastos |
| `graphs_001`–`007`, `009`–`0012` | Prospectos, cotizaciones (valor/cantidad), estados, métodos, funnel, crecimiento anual/ventas, efectividad |
| [gp_inversion_informe.php](gp_inversion_informe.php) | ROI y consolidado del proyecto de inversión |

---

## 5. Lo que pides vs. lo que existe (análisis de brechas)

Tu requerimiento: *"por proyecto y por año: cuánto se invirtió, cuánto nos pagan, y repartir los pagos globales (indirectos) por proyecto, separando directos e indirectos."*

| Necesidad | Estado actual |
|---|---|
| Cuánto nos pagan por proyecto/año | ✅ **Existe**: `abonos` por `idCotizacion` + fecha |
| Cuánto se invirtió (gastos directos) por proyecto/año | ✅ **Existe**: `gastos` con `idCotizacion > 0` |
| Gastos globales (indirectos) | ⚠️ **Existen pero sueltos**: `gastos.idCotizacion = 0`, **sin vínculo a proyecto** |
| Clasificar un gasto como directo/indirecto | ❌ **No hay campo ni regla**. Se deduce de `idCotizacion` (0 = global). En el código no aparece la palabra "indirecto" |
| **Repartir los globales entre proyectos** | ❌ **No existe**. Hace falta definir el criterio de prorrateo |
| Margen/utilidad por proyecto (ingresos − directos − indirectos asignados) | ❌ No existe. Solo hay abonos − gastos **directos** (en la línea de auditoría) |
| Vista por año | ⚠️ Parcial: el dashboard filtra por año, pero no consolida por proyecto |

### Observaciones técnicas que afectan el análisis
1. **"Proyecto" es ambiguo**: aquí hay tres cosas — `cotizacion` (venta), `proyecto_auditoria` (auditoría) y `gp_inversion_proyecto` (inversión). Hay que decidir cuál es "el proyecto" para tus reportes (mi suposición: **`cotizacion`**, quizá agrupada por `cliente`).
2. **Dos mundos de gastos**: `gastos`/`abonos` (cotizaciones) y `gastosauditoria`/`abonosauditoria` (auditoría). Para un consolidado anual hay que decidir si se suman ambos.
3. **Gráficas que pueden mezclar**: `graphs_costos_directos.php` junta `gastos` y `gastosauditoria` por cliente con `UNION`/`LEFT JOIN` por fecha; con varias filas en la misma fecha puede **duplicar o distorsionar sumas**. Conviene revisarlo antes de confiar en esos totales.
4. **Seguridad**: SQL armado concatenando variables (muchos archivos) y contraseña en el repo. El módulo `gp_inversion_*` está mejor hecho (escapes, CSRF, permisos) y sirve de modelo.
5. **Calidad del repo**: hay archivos basura/diagnóstico (`*.crdownload`, `debug_*.log`, `error_log`, `fix_css_emergency.php`, `htaccess (1)`, `test*.php`, `old_mant_int_ot.php`). Es posible que algunos sean accesibles desde la web.

---

## 6. Propuesta (para cuando des el visto bueno; aún no se hizo nada)

**A. Clasificación directo/indirecto**
- Directo = `gastos.idCotizacion > 0` (ya existe).
- Indirecto = `gastos.idCotizacion = 0` (gasto global). Opcional: agregar una columna `naturaleza` (`directo`/`indirecto`) o usar `categorias idSec=24` para marcar qué tipos de gasto son indirectos por naturaleza.

**B. Asignar indirectos a proyectos** (necesito que me digas el criterio):
1. Proporcional al **ingreso** (abonos) de cada proyecto en el año.
2. Proporcional al **valor cotizado**.
3. **Partes iguales** entre proyectos activos.
4. **Manual**: nueva tabla `gasto_asignacion (gasto_id, cotizacion_id, porcentaje/valor)` para que tú decidas cuánto de cada gasto global va a cada proyecto.
   (Mi recomendación: 4, con una sugerencia automática tipo 1.)

**C. Reportes/gráficas nuevas**
- Tabla por año y proyecto: *Ingresos (abonos) · Gastos directos · Gastos indirectos asignados · Utilidad · Margen %*.
- Barras apiladas directos vs indirectos por proyecto; torta de indirectos por tipo; evolución mensual; filtros por año/cliente; exportar a Excel.

**D. Prerrequisitos**
- Un dump del esquema (o acceso a la BD) para confirmar tipos y que `gastos.valor` / `abonos.valor` sean numéricos.
- Quitar la contraseña del repo.

---

## 7. Preguntas que necesito que me respondas

1. **¿"Proyecto" = cada cotización aprobada**, o un cliente completo, o el proyecto de auditoría / de inversión?
2. **¿Cómo quieres repartir los gastos globales?** (proporcional a ingresos, partes iguales, manual…)
3. ¿Los **gastos de auditoría** (`gastosauditoria`) cuentan como **directos** del proyecto? ¿Y el **personal** (nómina) cómo entra?
4. ¿Qué estados de cotización cuentan como proyecto "real" (65 Aprobada, 66 Pendiente de pago, 67 Pagada)?
5. ¿El año se toma por **fecha del gasto/abono** (`fechaCreacion`) o por fecha de la cotización?
6. ¿El módulo de **Inversión** (`gp_inversion_*`) entra en este análisis o es independiente?
7. ¿Puedes darme un **dump de la BD** o un acceso de solo lectura para validar con datos reales?
