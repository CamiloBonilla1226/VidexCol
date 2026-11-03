<?php

/*******************************************
DASHBOARD DE FACILITADORES — SOCIEDAD MISIONERA (ECUADOR)
Adaptado de grafica_facilitadores.php de Satura.
Inventario, estado, regional, último reporte, grupos,
cantidad de reportes, perfil completo y actividad en el tiempo.

Fuente de datos (ver CLAUDE.md, "Unificación de Facilitadores y ECC"):
- Facilitadores: usuario.tipo IN (162, 163, 167), excluyendo excluido_reportes = 1
  (mismo universo que el combo "Miembro de la regional" de consultar-facilitador.php).
- Reportes: TODOS, de ambas tablas y de todos los programas:
  ecu_reportes (cualquier tipo_reporte, fecha = fecha_inicio) +
  sat_reportes (cualquier rep_tip, fecha = fechaReporte). Se suman en PHP.
- Grupos:   ecu_grupos con generacion >= 2, de cualquier tipo_actividad (OMS no cuenta).
- Agrupación: regional = usuario_empresa.empresa_pd -> categorias.id;
  zona = categorias.idSec de esa regional (catálogo de zonas: categorias.idSec = 85).
  El país no sirve para agrupar: empresa_paisid vale 282 (Ecuador) para todos.

Nota: no se usan subconsultas (SELECT anidados). consultar-facilitador.php
documenta que el wrapper de BD de este sistema rechazó SQL con más de un
SELECT; por eso los agregados se calculan con consultas planas agrupadas
por usuario y se combinan en PHP (siguen siendo pocas consultas fijas,
nunca una por facilitador).
 *******************************************/

/*
*   PERMISOS (mismo esquema de alcance que main.php / consultar-sub-programa-ecc.php)
*   - Superusuario o usuario.tipo = 2 (admin): ve todo y puede filtrar por zona.
*   - Perfil 167 (coordinador de zona): solo su zona ($_SESSION["id_zona"]).
*   - Perfil 162 (coordinador regional): solo su regional ($_SESSION["empresa_pd"]).
*   - Cualquier otro perfil: sin acceso.
*/
$gf_perfil = isset($_SESSION["perfil"]) ? intval($_SESSION["perfil"]) : 0;
$gf_esAdmin = (isset($_SESSION["superusuario"]) && $_SESSION["superusuario"] == 1) || $gf_perfil == 2;
$gf_zonaSesion = isset($_SESSION["id_zona"]) ? intval($_SESSION["id_zona"]) : 0;
$gf_regionalSesion = isset($_SESSION["empresa_pd"]) ? intval($_SESSION["empresa_pd"]) : 0;

if (!isset($_SESSION["id"]) || intval($_SESSION["id"]) == 0 || (!$gf_esAdmin && $gf_perfil != 162 && $gf_perfil != 167)) {
  die("<h5 class='alert alert-danger text-center'>NO está autorizado a ver esta gráfica</h5>");
}
if (!$gf_esAdmin && $gf_perfil == 167 && $gf_zonaSesion <= 0) {
  die("<h5 class='alert alert-warning text-center'>Su usuario no tiene una zona asignada. Solicite al administrador que la configure.</h5>");
}
if (!$gf_esAdmin && $gf_perfil == 162 && $gf_regionalSesion <= 0) {
  die("<h5 class='alert alert-warning text-center'>Su usuario no tiene una regional asignada. Solicite al administrador que la configure.</h5>");
}

$PSN = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

$mesesNom = array("", "Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");
$nombresMesLargo = array("", "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");

if (!function_exists('h')) {
  function h($s)
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

/*
*   FILTROS (enteros o lista blanca)
*/
// Zona: solo el admin la elige; el perfil 167 queda fijo en la suya; el 162 no la usa (ya está acotado a su regional)
if ($gf_esAdmin) {
  $buscar_zona = isset($_REQUEST["zona"]) ? intval($_REQUEST["zona"]) : 0;
} else if ($gf_perfil == 167) {
  $buscar_zona = $gf_zonaSesion;
} else {
  $buscar_zona = 0;
}
$buscar_estado = (isset($_REQUEST["estado"]) && in_array($_REQUEST["estado"], array("1", "0"), true)) ? $_REQUEST["estado"] : "";

// Filtro sobre usuario (U) / usuario_empresa (UE) / regional (C)
$sqlFiltro = " AND U.tipo IN (162, 163, 167) AND COALESCE(U.excluido_reportes, 0) = 0";
if ($buscar_zona > 0) {
  $sqlFiltro .= " AND C.idSec = " . $buscar_zona;
}
if (!$gf_esAdmin && $gf_perfil == 162) {
  $sqlFiltro .= " AND UE.empresa_pd = " . $gf_regionalSesion;
}
if ($buscar_estado !== "") {
  $sqlFiltro .= " AND U.acceso = " . intval($buscar_estado);
}

// JOINs comunes para aplicar $sqlFiltro (regional = categorias C, zona = categorias CA)
$sqlJoinsUsuario = " LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
  LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
  LEFT JOIN categorias AS CA ON CA.id = C.idSec";

/*
*   FUENTES DE REPORTES: se cuentan TODOS los reportes del usuario, de ambas tablas
*   y de todos los programas (sin filtrar tipo_reporte ni rep_tip):
*   - ecu_reportes (sistema nuevo: 318 Facilitadores y 308 Capacitadores).
*   - sat_reportes (sistema anterior: 308, 317, 318, 327, etc.).
*   Único requisito: fecha válida. Igual que los listados existentes, no se filtra por sat_reportes.inactivo.
*   Cada fuente se consulta por separado (sin UNION ni subconsultas) y se suma en PHP.
*/
$fuentesReporte = array(
  array("tabla" => "ecu_reportes", "pk" => "R.idreporte", "uid" => "R.idusuario", "fecha" => "R.fecha_inicio", "tipo" => "R.tipo_reporte"),
  array("tabla" => "sat_reportes", "pk" => "R.id", "uid" => "R.idUsuario", "fecha" => "R.fechaReporte", "tipo" => "R.rep_tip"),
);

/*
*   Enlace para abrir un reporte, según la tabla y el programa
*   (mismas pantallas que abren los listados consultar-*.php).
*/
function gf_urlReporte($tabla, $tipo, $id)
{
  $tipo = intval($tipo);
  $id = intval($id);
  if ($tabla == "ecu_reportes") {
    return "index.php?doc=" . ($tipo == 308 ? "editar_capacitador" : "editar_facilitador") . "&idreporte=" . $id;
  }
  if ($tipo == 318) {
    $doc = "gestionar-sub-programa-evangelistas";   // Evangelistas / Facilitadores (sistema anterior)
  } else if ($tipo == 307) {
    $doc = "gestionar-sub-programa-lpp";            // LPP
  } else {
    $doc = "gestionar-sub-programa-ecc";            // 308, 317, 327 y demás: editor genérico de ECC
  }
  return "index.php?doc=" . $doc . "&id=" . $id;
}
$manana = date("Y-m-d", strtotime("+1 day"));

/*
*   INVENTARIO
*   1) Facilitadores filtrados.  2) Reportes agregados por usuario.  3) Grupos agregados por usuario.
*   Tres consultas fijas combinadas en PHP (sin N+1).
*/
$sql = "SELECT U.id, U.nombre, U.acceso, U.identificacion, U.email, U.celular, U.direccion, U.usua_muni,
    UE.empresa_pd, C.descripcion AS regional, CA.descripcion AS zona
  FROM usuario AS U" . $sqlJoinsUsuario . "
  WHERE 1 " . $sqlFiltro . "
  ORDER BY U.nombre ASC";
$PSN->query($sql);
$filasUsuario = array();
while ($PSN->next_record()) {
  $idU = intval($PSN->f('id'));
  // Por si un usuario tuviera más de una fila en usuario_empresa: se toma la primera
  if (!isset($filasUsuario[$idU])) {
    $fila = array();
    foreach (array("nombre", "acceso", "identificacion", "email", "celular", "direccion", "usua_muni", "regional", "zona") as $campo) {
      $fila[$campo] = (string)$PSN->f($campo);
    }
    $filasUsuario[$idU] = $fila;
  }
}

/*
*   Reportes por usuario: [total, fecha del último, url del último].
*   Se traen solo columnas livianas (id, tipo, fecha) de los reportes de los facilitadores
*   filtrados (IN con ids enteros, sin subconsulta) y en PHP se cuenta y se elige el último:
*   fecha más reciente y, si empatan en fecha, el id mayor dentro de la misma tabla.
*/
$repPorUsuario = array();
if (!empty($filasUsuario)) {
  $idsFiltrados = implode(",", array_map("intval", array_keys($filasUsuario)));
  foreach ($fuentesReporte as $fr) {
    $sql = "SELECT " . $fr["uid"] . " AS uid, " . $fr["pk"] . " AS rid, " . $fr["tipo"] . " AS tipo, " . $fr["fecha"] . " AS fecha
      FROM " . $fr["tabla"] . " AS R
      WHERE " . $fr["fecha"] . " > '2000-01-01'
        AND " . $fr["uid"] . " IN (" . $idsFiltrados . ")";
    $PSN->query($sql);
    $ultimoId = array();   // id del último reporte por usuario dentro de ESTA tabla (para desempatar)
    while ($PSN->next_record()) {
      $uid = intval($PSN->f('uid'));
      $rid = intval($PSN->f('rid'));
      $fecha = substr((string)$PSN->f('fecha'), 0, 10);   // sat_reportes puede traer hora
      if (!isset($repPorUsuario[$uid])) {
        $repPorUsuario[$uid] = array(0, "", "");
      }
      $repPorUsuario[$uid][0]++;
      $esMasNuevo = $fecha > $repPorUsuario[$uid][1]
        || ($fecha == $repPorUsuario[$uid][1] && isset($ultimoId[$uid]) && $rid > $ultimoId[$uid]);
      if ($esMasNuevo) {
        $repPorUsuario[$uid][1] = $fecha;
        $repPorUsuario[$uid][2] = gf_urlReporte($fr["tabla"], $PSN->f('tipo'), $rid);
        $ultimoId[$uid] = $rid;
      }
    }
  }
}

// Grupos del sistema nuevo de ambas actividades (318 y 308), sin contar OMS (generación 0)
$gruPorUsuario = array();
if (!empty($filasUsuario)) {
  $sql = "SELECT G.id_usuario AS uid, COUNT(*) AS total_grupos
    FROM ecu_grupos AS G
    WHERE G.generacion >= 2
    GROUP BY G.id_usuario";
  $PSN->query($sql);
  while ($PSN->next_record()) {
    $gruPorUsuario[intval($PSN->f('uid'))] = intval($PSN->f('total_grupos'));
  }
}

$hoy = new DateTime(date("Y-m-d"));
$facilitadores = array();
$tot_activos = $tot_completos = $tot_reportes = $tot_grupos = 0;

// Rangos de días desde el último reporte: [etiqueta, color]
$rangos = array(
  "r30"  => array("≤ 30 días", "#16a34a"),
  "r90"  => array("31–90 días", "#65a30d"),
  "r180" => array("91–180 días", "#d97706"),
  "rmas" => array("> 180 días", "#dc2626"),
  "rsin" => array("Sin reportes", "#cbd5e1"),
);
$actividad = array("r30" => 0, "r90" => 0, "r180" => 0, "rmas" => 0, "rsin" => 0);
// Conteo por regional: [activos, inactivos]
$porRegional = array();

foreach ($filasUsuario as $idU => $u) {
  // Perfil completo: campos requeridos (la regional reemplaza al país, que es fijo en Ecuador)
  $campos = array(
    "identificación" => trim($u['identificacion']),
    "correo" => trim($u['email']),
    "celular" => trim($u['celular']),
    "dirección" => trim($u['direccion']),
    "municipio" => intval($u['usua_muni']) > 0 ? "x" : "",
    "regional" => trim($u['regional']) != "" ? "x" : "",
  );
  $faltantes = array();
  foreach ($campos as $k => $v) {
    if ($v === "") {
      $faltantes[] = $k;
    }
  }

  $activo = intval($u['acceso']) == 1;
  $total = isset($repPorUsuario[$idU]) ? $repPorUsuario[$idU][0] : 0;
  $ultimo = isset($repPorUsuario[$idU]) ? $repPorUsuario[$idU][1] : "";
  $urlUltimo = isset($repPorUsuario[$idU]) ? $repPorUsuario[$idU][2] : "";
  $grupos = isset($gruPorUsuario[$idU]) ? $gruPorUsuario[$idU] : 0;
  $regional = trim($u['regional']) != "" ? trim($u['regional']) : "Sin regional";
  $zona = trim($u['zona']);

  if ($activo) {
    $tot_activos++;
  }
  if (empty($faltantes)) {
    $tot_completos++;
  }
  $tot_reportes += $total;
  $tot_grupos += $grupos;

  $dias = null;
  if ($total > 0 && $ultimo != "") {
    $dias = max(0, intval((new DateTime($ultimo))->diff($hoy)->format("%r%a")));
    if ($dias <= 30) {
      $rango = "r30";
    } else if ($dias <= 90) {
      $rango = "r90";
    } else if ($dias <= 180) {
      $rango = "r180";
    } else {
      $rango = "rmas";
    }
  } else {
    $rango = "rsin";
  }
  $actividad[$rango]++;

  if (!isset($porRegional[$regional])) {
    $porRegional[$regional] = array(0, 0);
  }
  $porRegional[$regional][$activo ? 0 : 1]++;

  $facilitadores[] = array(
    "id" => $idU,
    "nombre" => trim($u['nombre']),
    "regional" => $regional,
    "zona" => $zona,
    "activo" => $activo,
    "total" => $total,
    "grupos" => $grupos,
    "ultimo" => $ultimo,
    "url_ultimo" => $urlUltimo,
    "dias" => $dias,
    "rango" => $rango,
    "faltantes" => $faltantes,
  );
}

// Orden del inventario: del reporte más reciente al más antiguo; sin reportes al final; luego por nombre
usort($facilitadores, function ($a, $b) {
  if (($a["ultimo"] == "") != ($b["ultimo"] == "")) {
    return $a["ultimo"] == "" ? 1 : -1;
  }
  if ($a["ultimo"] != $b["ultimo"]) {
    return strcmp($b["ultimo"], $a["ultimo"]);
  }
  return strcasecmp($a["nombre"], $b["nombre"]);
});

$tot_facilitadores = count($facilitadores);
$tot_inactivos = $tot_facilitadores - $tot_activos;
$pct_activos = $tot_facilitadores > 0 ? round($tot_activos * 100 / $tot_facilitadores) : 0;
$pct_completos = $tot_facilitadores > 0 ? round($tot_completos * 100 / $tot_facilitadores) : 0;
$prom_reportes = $tot_facilitadores > 0 ? round($tot_reportes / $tot_facilitadores, 1) : 0;
// Regionales ordenadas por cantidad de facilitadores (mayor a menor)
uasort($porRegional, function ($a, $b) {
  return ($b[0] + $b[1]) - ($a[0] + $a[1]);
});

/*
*   ACTIVIDAD EN EL TIEMPO (últimos 12 meses)
*   (ecu_reportes no tiene tipo de actividad: no hay gráfica "Reportes por actividad")
*/
$reportesMes = array();
$inicioMeses = new DateTime(date("Y-m-01"));
$inicioMeses->modify("-11 months");
$cursor = clone $inicioMeses;
for ($i = 0; $i < 12; $i++) {
  $reportesMes[$cursor->format("Y-m")] = array($mesesNom[intval($cursor->format("n"))] . " " . $cursor->format("y"), 0, 0);
  $cursor->modify("+1 month");
}

if ($tot_facilitadores > 0) {
  /*
  *   Por cada fuente: reportes agrupados por mes y usuario. Así, en PHP se suman los
  *   reportes de ambas tablas y se cuentan los facilitadores distintos del mes sin
  *   duplicar a quien reportó en las dos. JOIN directo a usuario con el mismo filtro
  *   (en vez de IN (SELECT ...)); COUNT(DISTINCT pk) por si hay filas duplicadas en usuario_empresa.
  */
  $usuariosMes = array();
  foreach ($fuentesReporte as $fr) {
    $sql = "SELECT DATE_FORMAT(" . $fr["fecha"] . ", '%Y-%m') AS mes, " . $fr["uid"] . " AS uid, COUNT(DISTINCT " . $fr["pk"] . ") AS reportes
      FROM " . $fr["tabla"] . " AS R
      INNER JOIN usuario AS U ON U.id = " . $fr["uid"] . $sqlJoinsUsuario . "
      WHERE " . $fr["fecha"] . " >= '" . $inicioMeses->format("Y-m-d") . "'
        AND " . $fr["fecha"] . " < '" . $manana . "'" . $sqlFiltro . "
      GROUP BY mes, uid";
    $PSN->query($sql);
    while ($PSN->next_record()) {
      $mes = $PSN->f('mes');
      if (isset($reportesMes[$mes])) {
        $reportesMes[$mes][1] += intval($PSN->f('reportes'));
        $usuariosMes[$mes][intval($PSN->f('uid'))] = true;
      }
    }
  }
  foreach ($usuariosMes as $mes => $usuarios) {
    $reportesMes[$mes][2] = count($usuarios);
  }
}
$fac_mes_actual = $reportesMes[date("Y-m")][2];
$nombreMesActual = $nombresMesLargo[intval(date("n"))];

/*
*   DATOS PARA GOOGLE CHARTS
*/
$dataMes = array(array("Mes", "Reportes", "Facilitadores que reportaron"));
foreach ($reportesMes as $m) {
  $dataMes[] = $m;
}
$dataActividad = array(array("Último reporte", "Facilitadores", array("role" => "style")));
foreach ($actividad as $k => $v) {
  $dataActividad[] = array($rangos[$k][0], $v, $rangos[$k][1]);
}
$dataRegional = array(array("Regional", "Activos", "Inactivos"));
foreach ($porRegional as $k => $v) {
  $dataRegional[] = array($k, $v[0], $v[1]);
}
$alturaRegional = max(240, count($porRegional) * 36 + 50);

$jsonOpts = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

// Iniciales para el avatar
function gf_iniciales($nombre)
{
  $partes = preg_split('/\s+/u', trim($nombre));
  $ini = "";
  foreach ($partes as $p) {
    if ($p !== "") {
      $ini .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
    }
    if ((function_exists('mb_strlen') ? mb_strlen($ini, 'UTF-8') : strlen($ini)) >= 2) {
      break;
    }
  }
  return function_exists('mb_strtoupper') ? mb_strtoupper($ini, 'UTF-8') : strtoupper($ini);
}

// Texto del alcance para quien no es admin (se muestra en el encabezado)
$gf_alcance = "";
if (!$gf_esAdmin) {
  if ($gf_perfil == 162) {
    $PSN2->query("SELECT descripcion FROM categorias WHERE id = " . $gf_regionalSesion);
    $gf_alcance = $PSN2->next_record() ? "Alcance: " . $PSN2->f("descripcion") : "Alcance: su regional";
  } else {
    $PSN2->query("SELECT descripcion FROM categorias WHERE id = " . $gf_zonaSesion);
    $gf_alcance = $PSN2->next_record() ? "Alcance: " . $PSN2->f("descripcion") : "Alcance: su zona";
  }
}

$hayFiltros = (($gf_esAdmin && $buscar_zona > 0) || $buscar_estado !== "");

?>
<style>
  /* ===== Dashboard de facilitadores (todo bajo .gf para no afectar el resto del sitio) ===== */
  .gf {
    --gf-bg: #f6f7f9;
    --gf-card: #ffffff;
    --gf-ink: #0f172a;
    --gf-ink-2: #334155;
    --gf-muted: #64748b;
    --gf-faint: #94a3b8;
    --gf-line: #e5e7eb;
    --gf-line-2: #f1f5f9;
    --gf-accent: #2563eb;
    --gf-accent-soft: #eff6ff;
    --gf-ok: #16a34a;
    --gf-ok-soft: #f0fdf4;
    --gf-warn: #d97706;
    --gf-warn-soft: #fffbeb;
    --gf-bad: #dc2626;
    --gf-bad-soft: #fef2f2;
    --gf-radius: 12px;

    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: var(--gf-ink);
    background: var(--gf-bg);
    padding: 28px 24px 40px;
    border-radius: 16px;
    margin-top: 12px;
    margin-bottom: 30px;
    -webkit-font-smoothing: antialiased;
  }

  .gf *,
  .gf *::before,
  .gf *::after {
    box-sizing: border-box;
  }

  /* --- Encabezado --- */
  .gf-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
  }

  .gf-head h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -.02em;
    color: var(--gf-ink);
  }

  .gf-head p {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--gf-muted);
  }

  .gf-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 0;
  }

  .gf-select {
    appearance: none;
    -webkit-appearance: none;
    height: 36px;
    min-width: 170px;
    padding: 0 34px 0 12px;
    font-size: 13px;
    color: var(--gf-ink-2);
    background: var(--gf-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 12px center;
    border: 1px solid var(--gf-line);
    border-radius: 8px;
    cursor: pointer;
    transition: border-color .15s;
  }

  .gf-select:hover,
  .gf-select:focus {
    border-color: #cbd5e1;
    outline: none;
  }

  .gf-select:focus-visible {
    box-shadow: 0 0 0 3px var(--gf-accent-soft);
    border-color: var(--gf-accent);
  }

  .gf-link {
    font-size: 13px;
    color: var(--gf-muted);
    padding: 0 6px;
  }

  .gf-link:hover {
    color: var(--gf-ink);
    text-decoration: none;
  }

  /* --- Tarjetas --- */
  .gf-card {
    background: var(--gf-card);
    border: 1px solid var(--gf-line);
    border-radius: var(--gf-radius);
    padding: 20px 22px;
    height: 100%;
  }

  .gf-card-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--gf-ink);
  }

  .gf-card-sub {
    margin: 3px 0 14px;
    font-size: 12.5px;
    color: var(--gf-muted);
  }

  /* --- Rejillas --- */
  .gf-grid {
    display: grid;
    gap: 16px;
    margin-bottom: 16px;
  }

  .gf-grid-kpi {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .gf-grid-7-5 {
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
  }

  @media (max-width: 991px) {
    .gf-grid-kpi {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gf-grid-7-5 {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  @media (max-width: 520px) {
    .gf {
      padding: 20px 14px 30px;
    }

    .gf-grid-kpi {
      grid-template-columns: minmax(0, 1fr);
    }

    .gf-select {
      min-width: 0;
      flex: 1;
    }
  }

  /* --- KPIs --- */
  .gf-kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .gf-kpi-label {
    font-size: 13px;
    color: var(--gf-muted);
    font-weight: 500;
  }

  .gf-kpi-ico {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: var(--gf-line-2);
    color: var(--gf-ink-2);
  }

  .gf-kpi-num {
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -.02em;
    line-height: 1.1;
    margin: 10px 0 4px;
    font-variant-numeric: tabular-nums;
  }

  .gf-kpi-det {
    font-size: 12.5px;
    color: var(--gf-muted);
  }

  .gf-bar {
    height: 6px;
    border-radius: 99px;
    background: var(--gf-line-2);
    margin-top: 12px;
    overflow: hidden;
    display: flex;
  }

  .gf-bar span {
    display: block;
    height: 100%;
  }

  /* --- Inventario --- */
  .gf-inv-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }

  .gf-tools {
    display: flex;
    gap: 8px;
    align-items: center;
  }

  .gf-search {
    position: relative;
  }

  .gf-search i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gf-faint);
    font-size: 13px;
  }

  .gf-search input {
    height: 36px;
    width: 260px;
    max-width: 100%;
    padding: 0 12px 0 34px;
    font-size: 13px;
    border: 1px solid var(--gf-line);
    border-radius: 8px;
    color: var(--gf-ink);
    background: var(--gf-card);
    transition: border-color .15s, box-shadow .15s;
  }

  .gf-search input:focus {
    outline: none;
    border-color: var(--gf-accent);
    box-shadow: 0 0 0 3px var(--gf-accent-soft);
  }

  .gf-btn {
    height: 36px;
    padding: 0 14px;
    font-size: 13px;
    font-weight: 500;
    color: var(--gf-ink-2);
    background: var(--gf-card);
    border: 1px solid var(--gf-line);
    border-radius: 8px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, border-color .15s;
  }

  .gf-btn:hover:not(:disabled) {
    background: var(--gf-line-2);
  }

  .gf-btn:disabled {
    opacity: .45;
    cursor: default;
  }

  .gf-btn i {
    margin-right: 6px;
    color: var(--gf-muted);
  }

  .gf-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 12px 0 14px;
  }

  .gf-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 30px;
    padding: 0 12px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--gf-ink-2);
    background: var(--gf-card);
    border: 1px solid var(--gf-line);
    border-radius: 99px;
    cursor: pointer;
    transition: all .15s;
  }

  .gf-chip:hover {
    border-color: #cbd5e1;
  }

  .gf-chip.is-on {
    background: var(--gf-ink);
    border-color: var(--gf-ink);
    color: #fff;
  }

  .gf-chip .n {
    font-size: 11.5px;
    color: var(--gf-faint);
    font-variant-numeric: tabular-nums;
  }

  .gf-chip.is-on .n {
    color: #cbd5e1;
  }

  .gf-table-wrap {
    overflow-x: auto;
    margin: 0 -22px;
  }

  .gf-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
  }

  .gf-table th {
    text-align: left;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--gf-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 10px 14px;
    background: #fafbfc;
    border-top: 1px solid var(--gf-line);
    border-bottom: 1px solid var(--gf-line);
    white-space: nowrap;
  }

  .gf-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--gf-line-2);
    vertical-align: middle;
    color: var(--gf-ink-2);
  }

  .gf-table th:first-child,
  .gf-table td:first-child {
    padding-left: 22px;
  }

  .gf-table th:last-child,
  .gf-table td:last-child {
    padding-right: 22px;
  }

  .gf-table tbody tr:hover td {
    background: #fafbfc;
  }

  .gf-table .num {
    text-align: right;
    font-variant-numeric: tabular-nums;
  }

  .gf-person {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
  }

  .gf-avatar {
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--gf-accent-soft);
    color: var(--gf-accent);
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .gf-person a {
    color: var(--gf-ink);
    font-weight: 600;
  }

  .gf-person a:hover {
    color: var(--gf-accent);
    text-decoration: none;
  }

  .gf-sub {
    display: block;
    font-size: 12px;
    color: var(--gf-faint);
    margin-top: 1px;
  }

  .gf-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 99px;
    white-space: nowrap;
  }

  .gf-pill::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  .gf-pill.ok {
    color: var(--gf-ok);
    background: var(--gf-ok-soft);
  }

  .gf-pill.off {
    color: var(--gf-muted);
    background: var(--gf-line-2);
  }

  .gf-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
  }

  .gf-ultimo {
    display: block;
    color: var(--gf-ink-2);
  }

  .gf-ultimo:hover,
  .gf-ultimo:focus {
    color: var(--gf-accent);
    text-decoration: none;
  }

  .gf-ultimo i {
    margin-left: 6px;
    font-size: 10px;
    color: var(--gf-faint);
  }

  .gf-ultimo:hover i,
  .gf-ultimo:hover .gf-sub {
    color: var(--gf-accent);
  }

  .gf-perfil-ok {
    color: var(--gf-ok);
    font-weight: 500;
  }

  .gf-perfil-falta {
    color: var(--gf-warn);
    font-weight: 500;
  }

  .gf-empty {
    text-align: center;
    color: var(--gf-muted);
    padding: 40px 10px;
    font-size: 14px;
  }

  .gf-empty i {
    display: block;
    font-size: 26px;
    color: var(--gf-faint);
    margin-bottom: 10px;
  }

  .gf-pag {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 14px;
    font-size: 13px;
    color: var(--gf-muted);
  }

  .gf-pag .gf-btn {
    height: 32px;
    padding: 0 12px;
  }

  .gf-chart {
    width: 100%;
  }
</style>

<div class="container">
  <div class="gf">

    <!-- ENCABEZADO + FILTROS -->
    <div class="gf-head">
      <div>
        <h2>Facilitadores</h2>
        <p>Inventario, estado y actividad de reportes<?= $gf_alcance !== "" ? " · " . h($gf_alcance) : ""; ?> · Actualizado al <?= intval(date("j")) . " de " . $nombresMesLargo[intval(date("n"))] . " de " . date("Y"); ?></p>
      </div>
      <form action="index.php" method="get" name="form1" class="gf-filtros">
        <input type="hidden" name="doc" value="grafica_facilitadores" />
        <?php if ($gf_esAdmin) { ?>
          <select name="zona" class="gf-select" aria-label="Zona" onchange="this.form.submit()">
            <option value="">Todas las zonas</option>
            <?php
            $sql = "SELECT id, descripcion FROM categorias WHERE idSec = 85 ORDER BY descripcion ASC";
            $PSN2->query($sql);
            while ($PSN2->next_record()) { ?>
              <option value="<?= intval($PSN2->f('id')); ?>" <?= ($buscar_zona == $PSN2->f('id')) ? 'selected="selected"' : ''; ?>><?= h($PSN2->f('descripcion')); ?></option>
            <?php } ?>
          </select>
        <?php } ?>
        <select name="estado" class="gf-select" aria-label="Estado" onchange="this.form.submit()">
          <option value="">Activos e inactivos</option>
          <option value="1" <?= $buscar_estado === "1" ? 'selected' : ''; ?>>Solo activos</option>
          <option value="0" <?= $buscar_estado === "0" ? 'selected' : ''; ?>>Solo inactivos</option>
        </select>
        <?php if ($hayFiltros) { ?>
          <a href="index.php?doc=grafica_facilitadores" class="gf-link"><i class="fas fa-times"></i> Limpiar</a>
        <?php } ?>
      </form>
    </div>

    <?php if ($tot_facilitadores == 0) { ?>
      <div class="gf-card gf-empty">
        <i class="fas fa-user-slash"></i>
        No se encontró ningún facilitador con los filtros seleccionados.
      </div>
    <?php } else { ?>

      <!-- 1. INDICADORES -->
      <div class="gf-grid gf-grid-kpi">
        <div class="gf-card">
          <div class="gf-kpi-top">
            <span class="gf-kpi-label">Facilitadores</span>
            <span class="gf-kpi-ico"><i class="fas fa-users"></i></span>
          </div>
          <div class="gf-kpi-num"><?= number_format($tot_facilitadores); ?></div>
          <div class="gf-kpi-det"><?= $tot_activos; ?> activos · <?= $tot_inactivos; ?> inactivos</div>
          <div class="gf-bar" title="<?= $pct_activos; ?>% activos"><span style="width: <?= $pct_activos; ?>%; background: var(--gf-ok)"></span></div>
        </div>
        <div class="gf-card">
          <div class="gf-kpi-top">
            <span class="gf-kpi-label">Reportaron este mes</span>
            <span class="gf-kpi-ico"><i class="fas fa-calendar-check"></i></span>
          </div>
          <div class="gf-kpi-num"><?= number_format($fac_mes_actual); ?></div>
          <div class="gf-kpi-det">facilitadores con reportes en <?= $nombreMesActual; ?></div>
          <div class="gf-bar"><span style="width: <?= $tot_facilitadores > 0 ? round($fac_mes_actual * 100 / $tot_facilitadores) : 0; ?>%; background: var(--gf-accent)"></span></div>
        </div>
        <div class="gf-card">
          <div class="gf-kpi-top">
            <span class="gf-kpi-label">Reportes</span>
            <span class="gf-kpi-ico"><i class="fas fa-file-alt"></i></span>
          </div>
          <div class="gf-kpi-num"><?= number_format($tot_reportes); ?></div>
          <div class="gf-kpi-det"><?= $prom_reportes; ?> por facilitador · <?= number_format($tot_grupos); ?> grupos</div>
        </div>
        <div class="gf-card">
          <div class="gf-kpi-top">
            <span class="gf-kpi-label">Requieren atención</span>
            <span class="gf-kpi-ico"><i class="fas fa-exclamation"></i></span>
          </div>
          <div class="gf-kpi-num"><?= number_format($actividad["rsin"]); ?></div>
          <div class="gf-kpi-det">nunca han reportado · <?= $actividad["rmas"]; ?> llevan más de 180 días</div>
        </div>
      </div>

      <!-- 2. ACTIVIDAD EN EL TIEMPO + ÚLTIMO REPORTE -->
      <div class="gf-grid gf-grid-7-5">
        <div class="gf-card">
          <p class="gf-card-title">Actividad en el tiempo</p>
          <p class="gf-card-sub">Reportes y facilitadores que reportaron, mes a mes · últimos 12 meses</p>
          <div id="gf_chart_tiempo" class="gf-chart" style="height:300px"></div>
        </div>
        <div class="gf-card">
          <p class="gf-card-title">Último reporte</p>
          <p class="gf-card-sub">Facilitadores según hace cuánto reportaron por última vez</p>
          <div id="gf_chart_actividad" class="gf-chart" style="height:300px"></div>
        </div>
      </div>

      <!-- 3. POR REGIONAL (ancho completo: ecu_reportes no tiene tipo de actividad) -->
      <div class="gf-grid">
        <div class="gf-card">
          <p class="gf-card-title">Facilitadores por regional</p>
          <p class="gf-card-sub">Activos e inactivos</p>
          <div id="gf_chart_regional" class="gf-chart" style="height: <?= $alturaRegional; ?>px"></div>
        </div>
      </div>

      <!-- 4. INVENTARIO -->
      <div class="gf-card">
        <div class="gf-inv-head">
          <div>
            <p class="gf-card-title">Inventario</p>
            <p class="gf-card-sub" style="margin-bottom:0">Ordenado del reporte más reciente al más antiguo</p>
          </div>
          <div class="gf-tools">
            <div class="gf-search">
              <i class="fas fa-search"></i>
              <input type="text" id="gfBuscar" placeholder="Buscar facilitador o regional" autocomplete="off" />
            </div>
            <button type="button" class="gf-btn" id="gfExportar" title="Descargar la lista filtrada en Excel"><i class="fas fa-file-excel"></i>Excel</button>
          </div>
        </div>

        <?php
        $cntAtrasado = $actividad["r180"] + $actividad["rmas"];
        $cntIncompleto = $tot_facilitadores - $tot_completos;
        ?>
        <div class="gf-chips" id="gfChips">
          <button type="button" class="gf-chip is-on" data-filtro="">Todos <span class="n"><?= $tot_facilitadores; ?></span></button>
          <button type="button" class="gf-chip" data-filtro="al-dia">Al día (≤ 90 días) <span class="n"><?= $actividad["r30"] + $actividad["r90"]; ?></span></button>
          <button type="button" class="gf-chip" data-filtro="atrasado">Más de 90 días <span class="n"><?= $cntAtrasado; ?></span></button>
          <button type="button" class="gf-chip" data-filtro="sin-reportes">Sin reportes <span class="n"><?= $actividad["rsin"]; ?></span></button>
          <button type="button" class="gf-chip" data-filtro="incompleto">Perfil incompleto <span class="n"><?= $cntIncompleto; ?></span></button>
        </div>

        <div class="gf-table-wrap">
          <table class="gf-table" id="gfTabla">
            <thead>
              <tr>
                <th>Facilitador</th>
                <th>Estado</th>
                <th>Último reporte</th>
                <th class="num">Grupos</th>
                <th class="num">Reportes</th>
                <th>Perfil</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($facilitadores as $f) {
                $etiquetas = array();
                $colorDias = $rangos[$f["rango"]][1];
                if ($f["dias"] === null) {
                  $textoDias = "Sin reportes";
                  $etiquetas[] = "sin-reportes";
                } else {
                  $textoDias = $f["dias"] == 0 ? "Hoy" : ($f["dias"] == 1 ? "Ayer" : "Hace " . $f["dias"] . " días");
                  $etiquetas[] = $f["dias"] > 90 ? "atrasado" : "al-dia";
                }
                if (!empty($f["faltantes"])) {
                  $etiquetas[] = "incompleto";
                }
                $fechaTxt = "";
                if ($f["dias"] !== null) {
                  $ts = strtotime($f["ultimo"]);
                  $fechaTxt = intval(date("j", $ts)) . " " . $mesesNom[intval(date("n", $ts))] . " " . date("Y", $ts);
                }
              ?>
                <tr data-etiquetas="<?= implode(" ", $etiquetas); ?>"
                  data-csv="<?= h(json_encode(array(
                              $f["nombre"],
                              $f["regional"],
                              $f["activo"] ? "Activo" : "Inactivo",
                              $f["dias"] === null ? "" : $f["ultimo"],
                              $f["dias"] === null ? "" : $f["dias"],
                              $f["grupos"],
                              $f["total"],
                              empty($f["faltantes"]) ? "Completo" : "Falta: " . implode(", ", $f["faltantes"]),
                            ), JSON_UNESCAPED_UNICODE)); ?>">
                  <td>
                    <div class="gf-person">
                      <span class="gf-avatar"><?= h(gf_iniciales($f["nombre"])); ?></span>
                      <div>
                        <a href="index.php?doc=usuario&id=<?= $f["id"]; ?>"><?= h($f["nombre"]); ?></a>
                        <span class="gf-sub"><?= h($f["regional"]); ?><?= $f["zona"] !== "" ? " · " . h($f["zona"]) : ""; ?></span>
                      </div>
                    </div>
                  </td>
                  <td><span class="gf-pill <?= $f["activo"] ? 'ok' : 'off'; ?>"><?= $f["activo"] ? 'Activo' : 'Inactivo'; ?></span></td>
                  <td style="white-space:nowrap">
                    <?php if ($f["url_ultimo"] !== "") { ?>
                      <a href="<?= h($f["url_ultimo"]); ?>" class="gf-ultimo" title="Abrir el último reporte">
                        <span class="gf-dot" style="background: <?= $colorDias; ?>"></span><?= $textoDias; ?><i class="fas fa-external-link-alt"></i>
                        <?php if ($fechaTxt !== "") { ?><span class="gf-sub" style="padding-left:16px"><?= h($fechaTxt); ?></span><?php } ?>
                      </a>
                    <?php } else { ?>
                      <span class="gf-dot" style="background: <?= $colorDias; ?>"></span><?= $textoDias; ?>
                      <?php if ($fechaTxt !== "") { ?><span class="gf-sub" style="padding-left:16px"><?= h($fechaTxt); ?></span><?php } ?>
                    <?php } ?>
                  </td>
                  <td class="num"><?= $f["grupos"]; ?></td>
                  <td class="num"><?= $f["total"]; ?></td>
                  <td>
                    <?php if (empty($f["faltantes"])) { ?>
                      <span class="gf-perfil-ok"><i class="fas fa-check" style="margin-right:4px"></i>Completo</span>
                    <?php } else { ?>
                      <span class="gf-perfil-falta">Falta <?= count($f["faltantes"]); ?> dato<?= count($f["faltantes"]) > 1 ? 's' : ''; ?></span>
                      <span class="gf-sub"><?= h(implode(", ", $f["faltantes"])); ?></span>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <div id="gfSinCoincidencias" class="gf-empty" style="display:none"><i class="fas fa-search"></i>No hay facilitadores que coincidan.</div>
        <div class="gf-pag">
          <span id="gfPagInfo"></span>
          <div>
            <button type="button" class="gf-btn" id="gfPagAnt"><i class="fas fa-chevron-left"></i>Anterior</button>
            <button type="button" class="gf-btn" id="gfPagSig">Siguiente <i class="fas fa-chevron-right" style="margin:0 0 0 6px"></i></button>
          </div>
        </div>
      </div>

      <script type="text/javascript">
        (function() {
          var datosMes = <?= json_encode($dataMes, $jsonOpts); ?>;
          var datosActividad = <?= json_encode($dataActividad, $jsonOpts); ?>;
          var datosRegional = <?= json_encode($dataRegional, $jsonOpts); ?>;

          var FUENTE = '-apple-system, "Segoe UI", Roboto, Arial, sans-serif';
          var tx = {color: "#64748b", fontSize: 11, fontName: FUENTE};
          var txLeyenda = {color: "#334155", fontSize: 12, fontName: FUENTE};
          var ejeV = {textStyle: tx, gridlines: {color: "#f1f5f9"}, baselineColor: "#e5e7eb", minorGridlines: {count: 0}, format: "0", viewWindow: {min: 0}};
          var base = {fontName: FUENTE, backgroundColor: "transparent", tooltip: {textStyle: {fontName: FUENTE, fontSize: 12}}};

          function opts(o) {
            return $.extend(true, {}, base, o);
          }

          function dibujar() {
            // Actividad en el tiempo: columnas (reportes) + línea (facilitadores)
            var dtMes = google.visualization.arrayToDataTable(datosMes);
            new google.visualization.ComboChart(document.getElementById("gf_chart_tiempo")).draw(dtMes, opts({
              seriesType: "bars",
              series: {
                0: {color: "#bfdbfe"},
                1: {type: "line", color: "#2563eb", lineWidth: 2.5, pointSize: 5, targetAxisIndex: 1}
              },
              vAxes: {
                0: $.extend(true, {}, ejeV, {title: "Reportes", titleTextStyle: $.extend({}, tx, {italic: false})}),
                1: $.extend(true, {}, ejeV, {gridlines: {count: 0}, title: "Facilitadores", titleTextStyle: $.extend({}, tx, {italic: false})})
              },
              hAxis: {textStyle: tx},
              legend: {position: "top", alignment: "end", textStyle: txLeyenda},
              chartArea: {left: 50, right: 50, top: 30, bottom: 30},
              bar: {groupWidth: "60%"}
            }));

            // Último reporte: columnas por rango con cantidad y % encima
            var dtAct = google.visualization.arrayToDataTable(datosActividad);
            var total = 0;
            for (var i = 0; i < dtAct.getNumberOfRows(); i++) {
              total += dtAct.getValue(i, 1);
            }
            var vista = new google.visualization.DataView(dtAct);
            vista.setColumns([0, 1, 2, {
              type: "string",
              role: "annotation",
              calc: function(dt, fila) {
                var v = dt.getValue(fila, 1);
                return v + (total > 0 ? "  ·  " + Math.round(v * 100 / total) + "%" : "");
              }
            }]);
            new google.visualization.ColumnChart(document.getElementById("gf_chart_actividad")).draw(vista, opts({
              legend: {position: "none"},
              vAxis: ejeV,
              hAxis: {textStyle: $.extend({}, tx, {fontSize: 11.5, color: "#334155"}), maxAlternation: 1, slantedText: false},
              annotations: {alwaysOutside: true, textStyle: {color: "#334155", fontSize: 11.5, fontName: FUENTE, bold: false}, stem: {length: 4}},
              chartArea: {left: 36, right: 10, top: 24, bottom: 34},
              bar: {groupWidth: "62%"}
            }));

            // Por regional: barras apiladas activos / inactivos
            var dtRegional = google.visualization.arrayToDataTable(datosRegional);
            new google.visualization.BarChart(document.getElementById("gf_chart_regional")).draw(dtRegional, opts({
              isStacked: true,
              colors: ["#16a34a", "#e2e8f0"],
              legend: {position: "top", alignment: "end", textStyle: txLeyenda},
              hAxis: ejeV,
              vAxis: {textStyle: $.extend({}, tx, {fontSize: 12, color: "#334155"})},
              chartArea: {left: 170, right: 16, top: 30, bottom: 28},
              bar: {groupWidth: "58%"}
            }));
          }

          google.charts.load("current", {packages: ["corechart"], language: "es"});
          google.charts.setOnLoadCallback(dibujar);

          var timer;
          $(window).on("resize", function() {
            clearTimeout(timer);
            timer = setTimeout(dibujar, 250);
          });

          /*
           *  INVENTARIO: búsqueda, filtros rápidos, paginación (10 por página) y exportación CSV
           */
          var porPagina = 10,
            pagina = 1,
            filtro = "";
          var $filas = $("#gfTabla tbody tr");

          function normalizar(s) {
            s = (s || "").toLowerCase();
            return s.normalize ? s.normalize("NFD").replace(/[̀-ͯ]/g, "") : s;
          }

          function visibles() {
            var q = normalizar($("#gfBuscar").val());
            return $filas.filter(function() {
              var okTexto = q === "" || normalizar($(this).children("td").first().text()).indexOf(q) > -1;
              var okFiltro = filtro === "" || (" " + $(this).attr("data-etiquetas") + " ").indexOf(" " + filtro + " ") > -1;
              return okTexto && okFiltro;
            });
          }

          function pintar() {
            var $vis = visibles();
            var total = $vis.length;
            var paginas = Math.max(1, Math.ceil(total / porPagina));
            pagina = Math.min(Math.max(1, pagina), paginas);
            var desde = (pagina - 1) * porPagina;

            $filas.hide();
            $vis.slice(desde, desde + porPagina).show();

            $("#gfSinCoincidencias").toggle(total === 0);
            $("#gfTabla thead").toggle(total > 0);
            $("#gfPagInfo").text(total === 0 ? "" : "Mostrando " + (desde + 1) + "–" + Math.min(desde + porPagina, total) + " de " + total);
            $("#gfPagAnt").prop("disabled", pagina <= 1);
            $("#gfPagSig").prop("disabled", pagina >= paginas);
            $("#gfExportar").prop("disabled", total === 0);
          }

          $("#gfBuscar").on("input", function() {
            pagina = 1;
            pintar();
          });
          $("#gfChips .gf-chip").on("click", function() {
            $("#gfChips .gf-chip").removeClass("is-on");
            $(this).addClass("is-on");
            filtro = $(this).attr("data-filtro");
            pagina = 1;
            pintar();
          });
          $("#gfPagAnt").on("click", function() {
            pagina--;
            pintar();
          });
          $("#gfPagSig").on("click", function() {
            pagina++;
            pintar();
          });

          /*
           *  EXPORTAR A EXCEL (.xlsx con formato). ExcelJS se carga solo al hacer clic.
           */
          var EXCELJS_URL = "https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js";
          var resumen = {
            facilitadores: <?= intval($tot_facilitadores); ?>,
            activos: <?= intval($tot_activos); ?>,
            inactivos: <?= intval($tot_inactivos); ?>,
            mes: <?= intval($fac_mes_actual); ?>,
            reportes: <?= intval($tot_reportes); ?>,
            grupos: <?= intval($tot_grupos); ?>,
            sinReportes: <?= intval($actividad["rsin"]); ?>,
            completos: <?= intval($tot_completos); ?>
          };
          var nombreMes = <?= json_encode($nombreMesActual, $jsonOpts); ?>;
          var alcance = <?= json_encode($gf_alcance, $jsonOpts); ?>;

          function cargarExcelJS(cb) {
            if (window.ExcelJS) {
              return cb(null);
            }
            var s = document.createElement("script");
            s.src = EXCELJS_URL;
            s.onload = function() {
              cb(null);
            };
            s.onerror = function() {
              cb(new Error("No se pudo cargar ExcelJS"));
            };
            document.head.appendChild(s);
          }

          function descargar(blob, nombre) {
            var a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = nombre;
            document.body.appendChild(a);
            a.click();
            setTimeout(function() {
              URL.revokeObjectURL(a.href);
              a.remove();
            }, 0);
          }

          // Texto de los filtros aplicados (para dejar constancia en el archivo)
          function textoFiltros() {
            var partes = [];
            if ($("select[name=zona]").length) {
              partes.push($("select[name=zona] option:selected").text());
            } else if (alcance !== "") {
              partes.push(alcance);
            }
            partes.push($("select[name=estado] option:selected").text());
            var $chip = $("#gfChips .gf-chip.is-on").clone();
            $chip.find(".n").remove();
            partes.push($.trim($chip.text()));
            var q = $.trim($("#gfBuscar").val());
            if (q !== "") {
              partes.push('Búsqueda: "' + q + '"');
            }
            return partes.join("  ·  ");
          }

          function generarExcel() {
            var C = {
              tinta: "FF0F172A", tinta2: "FF334155", gris: "FF64748B", linea: "FFE5E7EB",
              zebra: "FFF8FAFC", cabecera: "FF1E293B", acento: "FF2563EB",
              ok: "FF15803D", okBg: "FFDCFCE7", off: "FF475569", offBg: "FFF1F5F9",
              warn: "FFB45309", warnBg: "FFFEF3C7", bad: "FFB91C1C", badBg: "FFFEE2E2"
            };
            var fuente = "Calibri";
            var borde = {bottom: {style: "thin", color: {argb: C.linea}}};

            var wb = new ExcelJS.Workbook();
            wb.creator = "Sociedad Misionera";
            wb.created = new Date();

            /* ---------- Hoja 1: Facilitadores ---------- */
            var ws = wb.addWorksheet("Facilitadores", {
              views: [{state: "frozen", ySplit: 5, showGridLines: false}],
              pageSetup: {orientation: "landscape", fitToPage: true, fitToWidth: 1, fitToHeight: 0, paperSize: 9}
            });
            ws.columns = [
              {key: "nombre", width: 36},
              {key: "regional", width: 24},
              {key: "estado", width: 12},
              {key: "ultimo", width: 16},
              {key: "dias", width: 14},
              {key: "grupos", width: 10},
              {key: "reportes", width: 11},
              {key: "perfil", width: 42}
            ];

            ws.mergeCells("A1:H1");
            ws.getCell("A1").value = "Facilitadores · Sociedad Misionera";
            ws.getCell("A1").font = {name: fuente, size: 16, bold: true, color: {argb: C.tinta}};
            ws.getRow(1).height = 26;

            ws.mergeCells("A2:H2");
            ws.getCell("A2").value = "Generado el <?= intval(date("j")) . " de " . $nombresMesLargo[intval(date("n"))] . " de " . date("Y"); ?>";
            ws.getCell("A2").font = {name: fuente, size: 10, color: {argb: C.gris}};

            ws.mergeCells("A3:H3");
            ws.getCell("A3").value = "Filtros: " + textoFiltros();
            ws.getCell("A3").font = {name: fuente, size: 10, color: {argb: C.gris}};
            ws.getRow(4).height = 8;

            var encabezados = ["Facilitador", "Regional","Estado", "Último reporte", "Días sin reportar", "Grupos", "Reportes", "Perfil"];
            var hr = ws.getRow(5);
            hr.values = encabezados;
            hr.height = 22;
            hr.eachCell(function(c, col) {
              c.font = {name: fuente, size: 10, bold: true, color: {argb: "FFFFFFFF"}};
              c.fill = {type: "pattern", pattern: "solid", fgColor: {argb: C.cabecera}};
              c.alignment = {vertical: "middle", horizontal: col >= 5 && col <= 7 ? "right" : "left", indent: 1};
            });

            var n = 0;
            visibles().each(function() {
              var d = JSON.parse($(this).attr("data-csv"));
              var fecha = null;
              if (d[3]) {
                var p = String(d[3]).split("-");
                fecha = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2]));
              }
              var dias = d[4] === "" || d[4] === null ? null : +d[4];
              var row = ws.addRow({
                nombre: d[0],
                regional: d[1],
                estado: d[2],
                ultimo: fecha || "Sin reportes",
                dias: dias,
                grupos: +d[5],
                reportes: +d[6],
                perfil: d[7]
              });
              n++;
              row.height = 20;
              row.eachCell({includeEmpty: true}, function(c, col) {
                c.font = {name: fuente, size: 10, color: {argb: C.tinta2}};
                c.alignment = {vertical: "middle", horizontal: col >= 5 && col <= 7 ? "right" : "left", indent: 1};
                c.border = borde;
                if (n % 2 === 0) {
                  c.fill = {type: "pattern", pattern: "solid", fgColor: {argb: C.zebra}};
                }
              });
              row.getCell("nombre").font = {name: fuente, size: 10, bold: true, color: {argb: C.tinta}};

              // Estado como "etiqueta" de color
              var activo = d[2] === "Activo";
              row.getCell("estado").font = {name: fuente, size: 10, bold: true, color: {argb: activo ? C.ok : C.off}};
              row.getCell("estado").fill = {type: "pattern", pattern: "solid", fgColor: {argb: activo ? C.okBg : C.offBg}};
              row.getCell("estado").alignment = {vertical: "middle", horizontal: "center"};

              // Último reporte / días: color según antigüedad
              if (fecha) {
                row.getCell("ultimo").numFmt = "dd/mm/yyyy";
                var col = dias <= 90 ? C.ok : (dias <= 180 ? C.warn : C.bad);
                row.getCell("dias").font = {name: fuente, size: 10, bold: true, color: {argb: col}};
                row.getCell("dias").numFmt = "#,##0";
              } else {
                row.getCell("ultimo").font = {name: fuente, size: 10, italic: true, color: {argb: C.gris}};
                row.getCell("dias").value = "—";
                row.getCell("dias").font = {name: fuente, size: 10, color: {argb: C.gris}};
              }
              row.getCell("grupos").numFmt = "#,##0";
              row.getCell("reportes").numFmt = "#,##0";

              // Perfil
              var completo = d[7] === "Completo";
              row.getCell("perfil").font = {name: fuente, size: 10, color: {argb: completo ? C.ok : C.warn}};
            });

            if (n > 0) {
              ws.autoFilter = {from: {row: 5, column: 1}, to: {row: 5 + n, column: 8}};
              // Fila de totales
              var tr = ws.addRow({
                nombre: "Total (" + n + " facilitadores)",
                grupos: {formula: "SUBTOTAL(109,F6:F" + (5 + n) + ")"},
                reportes: {formula: "SUBTOTAL(109,G6:G" + (5 + n) + ")"}
              });
              tr.height = 22;
              tr.eachCell({includeEmpty: true}, function(c, col) {
                c.font = {name: fuente, size: 10, bold: true, color: {argb: C.tinta}};
                c.alignment = {vertical: "middle", horizontal: col >= 5 && col <= 7 ? "right" : "left", indent: 1};
                c.border = {top: {style: "medium", color: {argb: C.cabecera}}};
                c.numFmt = "#,##0";
              });
            }

            /* ---------- Hoja 2: Resumen ---------- */
            var rs = wb.addWorksheet("Resumen", {views: [{showGridLines: false}]});
            rs.columns = [{width: 34}, {width: 16}, {width: 40}];
            rs.mergeCells("A1:C1");
            rs.getCell("A1").value = "Resumen de facilitadores";
            rs.getCell("A1").font = {name: fuente, size: 16, bold: true, color: {argb: C.tinta}};
            rs.getRow(1).height = 26;
            rs.mergeCells("A2:C2");
            rs.getCell("A2").value = "Según los filtros de zona y estado aplicados en la página" + (alcance !== "" ? " · " + alcance : "");
            rs.getCell("A2").font = {name: fuente, size: 10, color: {argb: C.gris}};

            var pct = function(a, b) {
              return b > 0 ? a / b : 0;
            };
            var filas = [
              ["Facilitadores", resumen.facilitadores, null],
              ["Activos", resumen.activos, pct(resumen.activos, resumen.facilitadores)],
              ["Inactivos", resumen.inactivos, pct(resumen.inactivos, resumen.facilitadores)],
              ["Reportaron en " + nombreMes, resumen.mes, pct(resumen.mes, resumen.facilitadores)],
              ["Nunca han reportado", resumen.sinReportes, pct(resumen.sinReportes, resumen.facilitadores)],
              ["Perfil completo", resumen.completos, pct(resumen.completos, resumen.facilitadores)],
              ["Reportes (total)", resumen.reportes, null],
              ["Grupos (total)", resumen.grupos, null]
            ];
            var h2 = rs.getRow(4);
            h2.values = ["Indicador", "Valor", "% de facilitadores"];
            h2.height = 22;
            h2.eachCell(function(c, col) {
              c.font = {name: fuente, size: 10, bold: true, color: {argb: "FFFFFFFF"}};
              c.fill = {type: "pattern", pattern: "solid", fgColor: {argb: C.cabecera}};
              c.alignment = {vertical: "middle", horizontal: col === 1 ? "left" : "right", indent: 1};
            });
            filas.forEach(function(f, i) {
              var r = rs.addRow(f);
              r.height = 20;
              r.eachCell({includeEmpty: true}, function(c, col) {
                c.font = {name: fuente, size: 10, color: {argb: col === 1 ? C.tinta : C.tinta2}, bold: col === 2};
                c.alignment = {vertical: "middle", horizontal: col === 1 ? "left" : "right", indent: 1};
                c.border = borde;
                if (i % 2 === 1) {
                  c.fill = {type: "pattern", pattern: "solid", fgColor: {argb: C.zebra}};
                }
              });
              r.getCell(2).numFmt = "#,##0";
              r.getCell(3).numFmt = "0%";
            });

            return wb.xlsx.writeBuffer();
          }

          $("#gfExportar").on("click", function() {
            var $btn = $(this);
            var html = $btn.html();
            $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>Generando…');
            cargarExcelJS(function(err) {
              if (err) {
                $btn.prop("disabled", false).html(html);
                alert("No fue posible generar el Excel. Revisa tu conexión a internet e inténtalo de nuevo.");
                return;
              }
              generarExcel().then(function(buf) {
                descargar(new Blob([buf], {type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"}),
                  "facilitadores_<?= date("Y-m-d"); ?>.xlsx");
              }).catch(function() {
                alert("Ocurrió un error al generar el Excel.");
              }).then(function() {
                $btn.prop("disabled", false).html(html);
              });
            });
          });

          pintar();
        })();
      </script>
    <?php } ?>
  </div>
</div>
