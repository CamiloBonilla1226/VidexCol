<?php
/*******************************************
ÁRBOL DE UN GRUPO (GENERACIÓN 0-5) - vista dedicada
Archivo: graphs_dashboard3_arbol.php

Vista liviana que muestra el árbol genealógico ENFOCADO en un solo
grupo: su cadena completa de ancestros (hasta generación 0) + sus
hijos directos (máximo 4, o todos si se pide "hijosCompletos=1").

Vive en un archivo aparte (separado de graphs_dashboard3.php) para
que abrir o cambiar de árbol no tenga que recargar los otros gráficos
del dashboard (madurez espiritual, actividades especiales) ni el
listado completo de tarjetas por generación. Eso evita el efecto de
"primero carga otra vista y luego la correcta" que ocurría al reusar
la página completa del dashboard solo para ver un árbol puntual.

ID MENU (permiso): 23
*******************************************/

/* =========================
   HELPERS
   ========================= */
function req_num($key){
    return (isset($_REQUEST[$key]) && soloNumeros($_REQUEST[$key]) != "") ? soloNumeros($_REQUEST[$key]) : "";
}
function req_date_or_default($key, $default){
    if(isset($_REQUEST[$key]) && soloNumeros($_REQUEST[$key]) != ""){
        return eliminarInvalidos($_REQUEST[$key]);
    }
    $_REQUEST[$key] = $default;
    return $default;
}

$PSN = new DBbase_Sql;

/* =========================
   AUTORIZACIÓN
   ========================= */
$sql = "SELECT idMenu
        FROM usuarios_menu_graphs
        WHERE idMenu = 23
          AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0){
    die("NO está autorizado a ver este dashboard.");
}

/* =========================
   FILTROS (se preservan para poder volver al listado completo)
   ========================= */
$fechaInicial = "2026-01-01";
$fechaFinal   = date("Y-m-d");

if(isset($_SESSION["perfil"]) && $_SESSION["perfil"] == 163){
    $_REQUEST["idUsuario"] = $_SESSION["id"];
}

$fechaInicial = req_date_or_default("fechaInicial", $fechaInicial);
$fechaFinal   = req_date_or_default("fechaFinal",   $fechaFinal);

$buscar_idUsuario         = req_num("idUsuario");
$empresa_paisid           = req_num("empresa_paisid");
$buscar_idGrupoGenealogia = req_num("idGrupoGenealogia");
$buscar_hijosCompletos    = req_num("hijosCompletos");

function arbolUrl($params){
    $base = [
        'doc'            => 'graphs_dashboard3_arbol',
        'idUsuario'      => isset($GLOBALS['buscar_idUsuario']) ? $GLOBALS['buscar_idUsuario'] : '',
        'empresa_paisid' => isset($GLOBALS['empresa_paisid']) ? $GLOBALS['empresa_paisid'] : '',
        'fechaInicial'   => isset($GLOBALS['fechaInicial']) ? $GLOBALS['fechaInicial'] : '',
        'fechaFinal'     => isset($GLOBALS['fechaFinal']) ? $GLOBALS['fechaFinal'] : '',
    ];
    $todos = array_merge($base, $params);
    $qs = [];
    foreach($todos as $k => $v){
        $qs[] = urlencode($k).'='.urlencode((string)$v);
    }
    return 'index.php?'.implode('&', $qs);
}
function volverListadoUrl(){
    return arbolUrlConDoc('graphs_dashboard3', ['idGrupoGenealogia' => '', 'hijosCompletos' => '']);
}
function arbolUrlConDoc($doc, $params){
    $base = [
        'doc'            => $doc,
        'idUsuario'      => isset($GLOBALS['buscar_idUsuario']) ? $GLOBALS['buscar_idUsuario'] : '',
        'empresa_paisid' => isset($GLOBALS['empresa_paisid']) ? $GLOBALS['empresa_paisid'] : '',
        'fechaInicial'   => isset($GLOBALS['fechaInicial']) ? $GLOBALS['fechaInicial'] : '',
        'fechaFinal'     => isset($GLOBALS['fechaFinal']) ? $GLOBALS['fechaFinal'] : '',
    ];
    $todos = array_merge($base, $params);
    $qs = [];
    foreach($todos as $k => $v){
        $qs[] = urlencode($k).'='.urlencode((string)$v);
    }
    return 'index.php?'.implode('&', $qs);
}

/* =========================
   DATOS DEL ÁRBOL
   ========================= */
$varError                = 0; // 0 = ok, 1 = grupo no encontrado, 2 = no se especificó grupo
$nombreGrupoSeleccionado = "";
$datosGenealogia         = [];
$generacionesGenealogia  = [];
$totalHijosDirectos      = 0;
$hijosOcultos             = 0;
$jsOpcionesGenealogia     = [];
$LIMITE_HIJOS_VISIBLES    = 4;

$paletaGeneraciones = ['#0259a5','#27ae60','#f39c12','#8e44ad','#e74c3c','#16a085','#d35400','#2c3e50','#c0392b','#2980b9'];

if($buscar_idGrupoGenealogia === ""){
    $varError = 2;
} else {
    $sqlFiltroGenealogia = "";
    if($buscar_idUsuario !== ""){
        $sqlFiltroGenealogia .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
    }
    if($empresa_paisid !== ""){
        $sqlFiltroGenealogia .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
    }

    $sql = "SELECT
                sat_reportes.id,
                sat_reportes.nombreGrupo_txt,
                sat_reportes.idGrupoMadre,
                sat_reportes.generacionNumero,
                sat_reportes.fechaInicio,
                sat_reportes.plantador,
                sat_reportes.grupoMadre_txt
            FROM sat_reportes
            LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
            WHERE sat_reportes.id_grupo = 0
              ".$sqlFiltroGenealogia."
            ORDER BY sat_reportes.generacionNumero ASC, sat_reportes.fechaInicio ASC";

    $PSN->query($sql);
    if($PSN->num_rows() == 0){
        $varError = 1;
    } else {
        $filas = [];
        $filasPorId = [];
        $idsExistentes = [];
        $hijosPorMadre = [];
        while($PSN->next_record()){
            $fila = [
                'id'          => (int)$PSN->f('id'),
                'nombre'      => trim($PSN->f('nombreGrupo_txt')) != "" ? trim($PSN->f('nombreGrupo_txt')) : "(Sin nombre)",
                'idMadre'     => (int)$PSN->f('idGrupoMadre'),
                'generacion'  => (int)$PSN->f('generacionNumero'),
                'fecha'       => $PSN->f('fechaInicio'),
                'plantador'   => trim($PSN->f('plantador')),
                'madreTxt'    => trim($PSN->f('grupoMadre_txt')),
            ];
            $filas[] = $fila;
            $filasPorId[$fila['id']] = $fila;
            $idsExistentes[$fila['id']] = true;
        }

        foreach($filas as $f){
            if($f['idMadre'] > 0){
                $hijosPorMadre[$f['idMadre']][] = $f;
            }
        }
        foreach($hijosPorMadre as $madreId => &$listaHijos){
            usort($listaHijos, function($a, $b){
                return strcasecmp($a['nombre'], $b['nombre']);
            });
        }
        unset($listaHijos);

        $opcionesOrdenadas = $filas;
        usort($opcionesOrdenadas, function($a, $b){
            if($a['generacion'] === $b['generacion']){
                return strcasecmp($a['nombre'], $b['nombre']);
            }
            return $a['generacion'] <=> $b['generacion'];
        });
        foreach($opcionesOrdenadas as $op){
            $jsOpcionesGenealogia[] = [
                'id'         => $op['id'],
                'nombre'     => $op['nombre'],
                'generacion' => $op['generacion'],
                'url'        => arbolUrl(['idGrupoGenealogia' => $op['id']]),
            ];
        }

        if(!isset($filasPorId[$buscar_idGrupoGenealogia])){
            $varError = 1;
        } else {
            $nombreGrupoSeleccionado = $filasPorId[$buscar_idGrupoGenealogia]['nombre'];

            $formatFecha = function($f){
                if(!empty($f) && $f != "0000-00-00"){
                    $ts = strtotime($f);
                    if($ts) return date("d/m/Y", $ts);
                }
                return "";
            };

            $cadena = [];
            $actual = (int)$buscar_idGrupoGenealogia;
            $visitados = [];
            while(isset($filasPorId[$actual]) && !isset($visitados[$actual])){
                $visitados[$actual] = true;
                array_unshift($cadena, $filasPorId[$actual]);
                $madreId = $filasPorId[$actual]['idMadre'];
                if($madreId > 0 && isset($idsExistentes[$madreId])){
                    $actual = $madreId;
                } else {
                    break;
                }
            }

            $hijosDirectos  = isset($hijosPorMadre[$buscar_idGrupoGenealogia]) ? $hijosPorMadre[$buscar_idGrupoGenealogia] : [];
            $totalHijosDirectos = count($hijosDirectos);
            $mostrarHijosCompletos = ($buscar_hijosCompletos == 1);
            $hijosVisibles  = $mostrarHijosCompletos ? $hijosDirectos : array_slice($hijosDirectos, 0, $LIMITE_HIJOS_VISIBLES);
            $hijosOcultos   = max(0, $totalHijosDirectos - count($hijosVisibles));

            foreach($cadena as $fila){
                $esRaiz = ($fila['idMadre'] <= 0 || !isset($idsExistentes[$fila['idMadre']]));
                $color = $paletaGeneraciones[$fila['generacion'] % count($paletaGeneraciones)];

                $datosGenealogia[] = [
                    'id'         => $fila['id'],
                    'idMadre'    => $esRaiz ? '' : $fila['idMadre'],
                    'nombre'     => $fila['nombre'],
                    'generacion' => $fila['generacion'],
                    'fecha'      => $formatFecha($fila['fecha']),
                    'plantador'  => $fila['plantador'],
                    'madreTxt'   => $fila['madreTxt'],
                    'color'      => $color,
                    'tipo'       => 'normal',
                ];
                $generacionesGenealogia[$fila['generacion']] = true;
            }

            foreach($hijosVisibles as $hijo){
                $color = $paletaGeneraciones[$hijo['generacion'] % count($paletaGeneraciones)];

                $datosGenealogia[] = [
                    'id'         => $hijo['id'],
                    'idMadre'    => $buscar_idGrupoGenealogia,
                    'nombre'     => $hijo['nombre'],
                    'generacion' => $hijo['generacion'],
                    'fecha'      => $formatFecha($hijo['fecha']),
                    'plantador'  => $hijo['plantador'],
                    'madreTxt'   => $hijo['madreTxt'],
                    'color'      => $color,
                    'tipo'       => 'normal',
                ];
                $generacionesGenealogia[$hijo['generacion']] = true;
            }

            if($hijosOcultos > 0){
                $genHijos = !empty($hijosVisibles)
                    ? $hijosVisibles[0]['generacion']
                    : ((int)$filasPorId[$buscar_idGrupoGenealogia]['generacion'] + 1);

                $datosGenealogia[] = [
                    'id'         => 'more_'.$buscar_idGrupoGenealogia,
                    'idMadre'    => $buscar_idGrupoGenealogia,
                    'nombre'     => '+'.$hijosOcultos.' grupo'.($hijosOcultos == 1 ? '' : 's').' más',
                    'generacion' => $genHijos,
                    'fecha'      => '',
                    'plantador'  => '',
                    'madreTxt'   => '',
                    'color'      => '#7f8c8d',
                    'tipo'       => 'mas',
                    'url'        => arbolUrl(['idGrupoGenealogia' => $buscar_idGrupoGenealogia, 'hijosCompletos' => 1]),
                ];
            }

            ksort($generacionesGenealogia);
        }
    }
}
?>

<style>
.db-card{
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
  margin-bottom: 18px;
  overflow: hidden;
}
.db-card__head{
  padding: 14px 16px;
  border-bottom: 1px solid rgba(0,0,0,.06);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.db-card__title{
  margin:0;
  font-size: 13px;
  font-weight: 900;
  letter-spacing: .4px;
  text-transform: uppercase;
}
.db-card__meta{
  display:flex;
  align-items:center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content:flex-end;
}
.db-pill{
  display:inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(2, 117, 216, .10);
  color: #0259a5;
  font-size: 12px;
  font-weight: 900;
}
.db-card__body{ padding: 14px 16px 18px 16px; }
.chart-box--org{
  height: 620px;
  overflow: auto;
}

.arbol-volver{
  display:inline-flex;
  align-items:center;
  gap: 6px;
  font-weight: 700;
  margin-bottom: 16px;
}

.genealogia-filtro{
  display:flex;
  align-items:flex-end;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px dashed rgba(0,0,0,.1);
}
.genealogia-filtro__label{
  display:flex;
  flex-direction: column;
  gap: 4px;
  font-weight: 700;
  min-width: 260px;
  max-width: 420px;
  flex: 1 1 260px;
  margin: 0;
}
.genealogia-combo{
  position: relative;
}
.genealogia-combo__lista{
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 320px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid rgba(0,0,0,.12);
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(0,0,0,.14);
  z-index: 20;
}
.genealogia-combo__lista.activa{
  display: block;
}
.genealogia-combo__grupo{
  padding: 6px 12px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #99a;
  background: rgba(0,0,0,.03);
}
.genealogia-combo__item{
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #1c2b3a;
  cursor: pointer;
}
.genealogia-combo__item:hover,
.genealogia-combo__item.activo{
  background: rgba(2,117,216,.10);
  color: #0259a5;
}
.genealogia-combo__vacio{
  padding: 10px 12px;
  font-size: 13px;
  color: #999;
}

.genealogia-legend{
  display:flex;
  flex-wrap:wrap;
  gap: 8px;
  margin-bottom: 14px;
  padding-top: 2px;
}
.genealogia-legend__item{
  display:inline-flex;
  align-items:center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(0,0,0,.04);
  font-size: 12px;
  font-weight: 700;
  color: #333;
}
.genealogia-legend__swatch{
  width: 12px;
  height: 12px;
  border-radius: 4px;
  flex-shrink: 0;
}

#chart_genealogia table.google-visualization-orgchart-table{
  border-collapse: separate;
  border-spacing: 0;
}
#chart_genealogia td.google-visualization-orgchart-node{
  background: transparent !important;
  border: none !important;
  padding: 4px !important;
}
.genealogia-node{
  display: block;
  box-sizing: border-box;
  width: 100%;
  height: 100%;
  min-width: 150px;
  min-height: 48px;
  padding: 6px 10px;
  border-radius: 8px;
  color: #fff;
  font-family: inherit;
  text-align: center;
  line-height: 1.35;
  cursor: pointer;
  transition: filter .15s ease;
}
.genealogia-node:hover{
  filter: brightness(1.08);
}
.genealogia-node__nombre{
  font-weight: 900;
  font-size: 12px;
  white-space: normal;
  word-break: break-word;
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  max-width: 100%;
  margin: 0 auto;
}
.genealogia-node__fecha{
  font-size: 11px;
  opacity: .92;
}
.genealogia-node--mas{
  display: block;
  cursor: pointer;
  border: 2px dashed rgba(255,255,255,.75);
  text-decoration: none;
}
.genealogia-node--mas:hover{
  opacity: .85;
  text-decoration: none;
}

/* ===== Modal de reportes de un grupo ===== */
.reportes-overlay{
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 9998;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.reportes-overlay.active{
  display: flex;
}
.reportes-modal{
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,.18);
  max-width: 640px;
  width: 100%;
  max-height: 85vh;
  padding: 24px 24px 20px 24px;
  position: relative;
  display: flex;
  flex-direction: column;
}
.reportes-modal__close{
  position: absolute;
  top: 14px;
  right: 16px;
  background: none;
  border: none;
  font-size: 22px;
  color: #888;
  cursor: pointer;
  line-height: 1;
  padding: 0;
}
.reportes-modal__close:hover{ color: #333; }
.reportes-modal__title{
  font-size: 16px;
  font-weight: 900;
  margin: 0 0 16px 0;
  color: #0259a5;
  padding-right: 24px;
}
.reportes-modal__body{
  overflow-y: auto;
  flex: 1 1 auto;
}
.reportes-modal__loading,
.reportes-modal__vacio{
  padding: 24px 8px;
  text-align: center;
  color: #888;
  font-size: 14px;
}
.reporte-item{
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: box-shadow .15s ease, border-color .15s ease;
}
.reporte-item:hover{
  border-color: rgba(2,117,216,.35);
  box-shadow: 0 4px 14px rgba(0,0,0,.08);
}
.reporte-item:last-child{ margin-bottom: 0; }
.reporte-item__head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}
.reporte-item__tipo{
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: #0259a5;
}
.reporte-item__fecha{
  font-size: 12px;
  color: #93a0ab;
}
.reporte-item__badges{
  display:flex;
  flex-wrap:wrap;
  gap: 6px;
  margin-bottom: 6px;
}
.reporte-badge{
  display:inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  background: rgba(2,117,216,.10);
  color: #0259a5;
  font-size: 11.5px;
  font-weight: 700;
}
.reporte-comentario{
  font-size: 13px;
  color: #444;
  line-height: 1.5;
  white-space: pre-wrap;
}

@media (max-width: 992px){
  .chart-box--org{ height: 480px; }
}
@media (max-width: 767px){
  .db-card{ border-radius: 12px; }
  .db-card__title{ font-size: 12px; }
  .chart-box--org{ height: 420px; }
}
</style>

<div class="container">

  <a href="<?=volverListadoUrl();?>" class="btn btn-default arbol-volver">← Volver al listado completo</a>

  <div class="db-card">
    <div class="db-card__head">
      <div class="db-card__title-wrap">
        <h4 class="db-card__title">🌳 RECORRIDO DE GRUPOS (GENERACIÓN 0-5)</h4>
      </div>
      <div class="db-card__meta">
        <?php if($varError == 0){ ?>
          <span class="db-pill">Árbol de: <?=htmlspecialchars($nombreGrupoSeleccionado, ENT_QUOTES, 'UTF-8');?></span>
          <span class="db-pill">Hijos directos: <?=$totalHijosDirectos;?></span>
          <span class="db-pill">Generaciones: <?=count($generacionesGenealogia);?></span>
        <?php } ?>
      </div>
    </div>
    <div class="db-card__body">

      <?php if(!empty($jsOpcionesGenealogia)){ ?>
        <div class="genealogia-filtro">
          <div class="genealogia-filtro__label genealogia-combo" id="genComboWrap">
            <strong>Ver otro grupo:</strong>
            <input type="text" id="genComboInput" class="form-control" autocomplete="off"
                   placeholder="Escribe el nombre de un grupo..."
                   value="<?=$varError == 0 ? htmlspecialchars($nombreGrupoSeleccionado, ENT_QUOTES, 'UTF-8') : '';?>" />
            <div class="genealogia-combo__lista" id="genComboLista"></div>
          </div>
        </div>
        <script type="text/javascript">
          var GEN_OPCIONES = <?=json_encode($jsOpcionesGenealogia, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);?>;
        </script>
      <?php } ?>

      <?php if($varError == 2){ ?>
        <div class="alert alert-warning text-center" style="margin-bottom:0;">
          No se especificó ningún grupo. <a href="<?=volverListadoUrl();?>">Vuelve al listado completo</a> y elige uno.
        </div>
      <?php } elseif($varError == 1){ ?>
        <div class="alert alert-warning text-center" style="margin-bottom:0;">
          No se encontró el grupo solicitado (puede que ya no exista o no pertenezca al facilitador seleccionado).
        </div>
      <?php } else { ?>
        <div class="genealogia-legend">
          <?php foreach($generacionesGenealogia as $gen => $ok){
            $color = $paletaGeneraciones[$gen % count($paletaGeneraciones)];
            ?>
            <span class="genealogia-legend__item">
              <span class="genealogia-legend__swatch" style="background:<?=$color;?>"></span>
              Generación <?=$gen;?>
            </span>
          <?php } ?>
        </div>
        <div id="chart_genealogia" class="chart-box chart-box--org"></div>
      <?php } ?>

    </div>
  </div>

</div><!-- /container -->

<!-- ===== Modal de reportes de un grupo ===== -->
<div class="reportes-overlay" id="reportesOverlay">
  <div class="reportes-modal">
    <button class="reportes-modal__close" id="reportesModalClose" title="Cerrar">&times;</button>
    <h4 class="reportes-modal__title" id="reportesModalTitle"></h4>
    <div class="reportes-modal__body" id="reportesModalBody"></div>
  </div>
</div>

<script type="text/javascript">
google.charts.load("current", {packages:["orgchart"]});
google.charts.setOnLoadCallback(drawGenealogia);

(function(){
  var resizeTimer = null;
  window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(drawGenealogia, 220);
  });
  window.addEventListener('orientationchange', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(drawGenealogia, 220);
  });
})();

(function(){
  if('ResizeObserver' in window){
    var ro = new ResizeObserver(function(){
      if(window.__dbRoTimer) clearTimeout(window.__dbRoTimer);
      window.__dbRoTimer = setTimeout(drawGenealogia, 220);
    });
    var el = document.getElementById('chart_genealogia');
    if(el) ro.observe(el);
  }
})();

/* ===== Búsqueda aproximada (sin acentos, tolera errores) ===== */
var DB_REGEX_DIACRITICOS = new RegExp('[' + String.fromCharCode(768) + '-' + String.fromCharCode(879) + ']', 'g');

function dbNormalizar(s){
  s = (s || '').toString().toLowerCase().trim();
  if(s.normalize){
    s = s.normalize('NFD').replace(DB_REGEX_DIACRITICOS, '');
  }
  return s;
}

function dbLevenshtein(a, b){
  var la = a.length, lb = b.length;
  if(la === 0) return lb;
  if(lb === 0) return la;
  var fila = [];
  for(var j = 0; j <= la; j++){ fila[j] = j; }
  for(var i = 1; i <= lb; i++){
    var anterior = fila[0];
    fila[0] = i;
    for(j = 1; j <= la; j++){
      var temp = fila[j];
      fila[j] = (b.charAt(i - 1) === a.charAt(j - 1))
        ? anterior
        : Math.min(anterior + 1, fila[j] + 1, fila[j - 1] + 1);
      anterior = temp;
    }
  }
  return fila[la];
}

function dbCoincideAproximado(nombre, query){
  if(!query) return true;
  var nombreNorm = dbNormalizar(nombre);
  var queryNorm = dbNormalizar(query);
  if(nombreNorm.indexOf(queryNorm) !== -1) return true;

  var palabrasNombre = nombreNorm.split(/\s+/).filter(Boolean);
  var palabrasQuery = queryNorm.split(/\s+/).filter(Boolean);
  if(palabrasQuery.length === 0) return true;

  return palabrasQuery.every(function(pq){
    return palabrasNombre.some(function(pn){
      if(pn.indexOf(pq) !== -1 || pq.indexOf(pn) !== -1) return true;
      var maxDist = pq.length <= 4 ? 1 : (pq.length <= 8 ? 2 : 3);
      return dbLevenshtein(pn, pq) <= maxDist;
    });
  });
}

/* ===== Combobox "Ver otro grupo" ===== */
var GEN_COMBO_ACTIVE_INDEX = -1;

function dbEscaparHtml(s){
  var div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function dbComboLimitarPorGeneracion(coincidencias, porGeneracion){
  var porGen = {};
  var ordenGen = [];
  coincidencias.forEach(function(op){
    if(!porGen[op.generacion]){
      porGen[op.generacion] = [];
      ordenGen.push(op.generacion);
    }
    porGen[op.generacion].push(op);
  });
  ordenGen.sort(function(a, b){ return a - b; });

  var resultado = [];
  ordenGen.forEach(function(gen){
    resultado = resultado.concat(porGen[gen].slice(0, porGeneracion));
  });
  return resultado;
}

function dbComboRenderLista(query){
  var lista = document.getElementById('genComboLista');
  if(!lista || typeof GEN_OPCIONES === 'undefined') return;
  var q = (query || '').trim();
  var coincidencias = q === '' ? GEN_OPCIONES : GEN_OPCIONES.filter(function(op){
    return dbCoincideAproximado(op.nombre, q);
  });
  var filtradas = dbComboLimitarPorGeneracion(coincidencias, 10);
  GEN_COMBO_ACTIVE_INDEX = -1;

  if(filtradas.length === 0){
    lista.innerHTML = '<div class="genealogia-combo__vacio">Sin coincidencias</div>';
    lista.classList.add('activa');
    return;
  }

  var html = '';
  var genActual = null;
  filtradas.forEach(function(op){
    if(genActual !== op.generacion){
      html += '<div class="genealogia-combo__grupo">Generación ' + op.generacion + '</div>';
      genActual = op.generacion;
    }
    html += '<div class="genealogia-combo__item" data-url="' + dbEscaparHtml(op.url) + '">' + dbEscaparHtml(op.nombre) + '</div>';
  });
  lista.innerHTML = html;
  lista.classList.add('activa');

  Array.prototype.forEach.call(lista.querySelectorAll('.genealogia-combo__item'), function(el){
    el.addEventListener('mousedown', function(e){
      e.preventDefault();
      window.location.href = el.getAttribute('data-url');
    });
  });
}

function dbComboOcultarLista(){
  var lista = document.getElementById('genComboLista');
  if(lista) lista.classList.remove('activa');
}

function dbComboMoverActivo(delta){
  var lista = document.getElementById('genComboLista');
  if(!lista) return;
  var items = lista.querySelectorAll('.genealogia-combo__item');
  if(items.length === 0) return;
  GEN_COMBO_ACTIVE_INDEX += delta;
  if(GEN_COMBO_ACTIVE_INDEX < 0) GEN_COMBO_ACTIVE_INDEX = items.length - 1;
  if(GEN_COMBO_ACTIVE_INDEX >= items.length) GEN_COMBO_ACTIVE_INDEX = 0;
  Array.prototype.forEach.call(items, function(el, i){
    el.classList.toggle('activo', i === GEN_COMBO_ACTIVE_INDEX);
  });
  items[GEN_COMBO_ACTIVE_INDEX].scrollIntoView({block:'nearest'});
}

function dbComboSeleccionarActivo(){
  var lista = document.getElementById('genComboLista');
  if(!lista) return;
  var items = lista.querySelectorAll('.genealogia-combo__item');
  if(items.length === 0) return;
  var idx = GEN_COMBO_ACTIVE_INDEX >= 0 ? GEN_COMBO_ACTIVE_INDEX : 0;
  var el = items[idx];
  if(el) window.location.href = el.getAttribute('data-url');
}

(function(){
  var input = document.getElementById('genComboInput');
  if(!input) return;
  input.addEventListener('input', function(){
    dbComboRenderLista(input.value);
  });
  input.addEventListener('focus', function(){
    dbComboRenderLista(input.value);
  });
  input.addEventListener('keydown', function(e){
    if(e.key === 'ArrowDown'){ e.preventDefault(); dbComboMoverActivo(1); }
    else if(e.key === 'ArrowUp'){ e.preventDefault(); dbComboMoverActivo(-1); }
    else if(e.key === 'Enter'){ e.preventDefault(); dbComboSeleccionarActivo(); }
    else if(e.key === 'Escape'){ dbComboOcultarLista(); }
  });
  input.addEventListener('blur', function(){
    setTimeout(dbComboOcultarLista, 120);
  });
})();

/* ===== Modal de reportes de un grupo (clic en un nodo del árbol) ===== */
var ETIQUETAS_ACTIVIDAD = {
  1: 'Coach', 2: 'Ninguna', 5: 'Otra actividad', 8: 'Gran Celebración',
  10: 'Siembra abundante', 11: 'Caminata de oración', 12: 'Identificar al hijo de paz',
  13: 'Oración Exp y Ferviente', 14: 'Taller', 77: 'Evangelismo', 99: 'Bautizo', 100: 'Capacitación'
};

function dbAbrirReportes(idGrupo, nombreGrupo){
  var overlay = document.getElementById('reportesOverlay');
  var title = document.getElementById('reportesModalTitle');
  var body = document.getElementById('reportesModalBody');
  if(!overlay || !title || !body) return;

  title.textContent = '📋 Reportes de: ' + nombreGrupo;
  body.innerHTML = '<div class="reportes-modal__loading">Cargando reportes...</div>';
  overlay.classList.add('active');

  fetch('graphs_dashboard3_reportes_ajax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idGrupo: idGrupo })
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(!data || !data.success){
      body.innerHTML = '<div class="reportes-modal__vacio">No se pudieron cargar los reportes.</div>';
      return;
    }
    dbRenderReportes(body, data.reportes || []);
  })
  .catch(function(){
    body.innerHTML = '<div class="reportes-modal__vacio">Ocurrió un error al cargar los reportes.</div>';
  });
}

function dbCerrarReportes(){
  var overlay = document.getElementById('reportesOverlay');
  if(overlay) overlay.classList.remove('active');
}

/* Abre el reporte individual en su formulario real (mismo destino que
   usa el resto de la app, ej. grupos.php, al hacer clic en un reporte). */
function dbAbrirReporteIndividual(idReporte){
  window.location.href = 'index.php?doc=reportar&id=' + idReporte;
}

function dbRenderReportes(container, reportes){
  if(!reportes.length){
    container.innerHTML = '<div class="reportes-modal__vacio">Este grupo no tiene reportes registrados.</div>';
    return;
  }

  container.innerHTML = reportes.map(function(r){
    var etiqueta = ETIQUETAS_ACTIVIDAD[r.id_actividad] || 'Reporte';
    var badges = '<span class="reporte-badge">👥 ' + (r.asistencia_total || 0) + ' asistentes</span>';
    if(r.id_actividad === 99 && r.bautizados){
      badges += '<span class="reporte-badge">💧 ' + r.bautizados + ' bautizados</span>';
    }
    if((r.id_actividad === 77 || r.id_actividad === 1) && r.desiciones){
      badges += '<span class="reporte-badge">🙌 ' + r.desiciones + ' decisiones</span>';
    }
    if(r.id_actividad === 1){
      if(r.discipulado) badges += '<span class="reporte-badge">📖 ' + r.discipulado + ' en discipulado</span>';
      if(r.preparandose) badges += '<span class="reporte-badge">🌱 ' + r.preparandose + ' preparándose</span>';
    }
    var comentario = r.comentario ? '<div class="reporte-comentario">' + dbEscaparHtml(r.comentario) + '</div>' : '';
    var idReporte = parseInt(r.id, 10) || 0;

    return '<div class="reporte-item" onclick="dbAbrirReporteIndividual(' + idReporte + ')" title="Abrir este reporte">'
         +   '<div class="reporte-item__head">'
         +     '<span class="reporte-item__tipo">' + dbEscaparHtml(etiqueta) + '</span>'
         +     '<span class="reporte-item__fecha">🕓 ' + (r.fecha || 'Sin fecha') + '</span>'
         +   '</div>'
         +   '<div class="reporte-item__badges">' + badges + '</div>'
         +   comentario
         + '</div>';
  }).join('');
}

(function(){
  var closeBtn = document.getElementById('reportesModalClose');
  var overlay = document.getElementById('reportesOverlay');
  if(closeBtn) closeBtn.addEventListener('click', dbCerrarReportes);
  if(overlay) overlay.addEventListener('click', function(e){
    if(e.target === overlay) dbCerrarReportes();
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') dbCerrarReportes();
  });
})();

/* ===== Dibuja el árbol (OrgChart) ===== */
function drawGenealogia(){
  <?php if($varError == 0){ ?>
  var GEN_NODOS = <?=json_encode(array_map(function($n){
    return ['id' => $n['id'], 'nombre' => $n['nombre'], 'tipo' => $n['tipo']];
  }, $datosGenealogia), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);?>;

  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Nombre');
  data.addColumn('string', 'Grupo madre');
  data.addColumn('string', 'ToolTip');
  data.addRows([
    <?php
      $rows = [];
      foreach($datosGenealogia as $n){
          $nodeId   = (string)$n['id'];
          $parentId = ($n['idMadre'] === '') ? '' : (string)$n['idMadre'];
          $nombreEsc = htmlspecialchars($n['nombre'], ENT_QUOTES, 'UTF-8');

          if($n['tipo'] === 'mas'){
              $urlEsc = htmlspecialchars($n['url'], ENT_QUOTES, 'UTF-8');
              $htmlLabel = '<a class="genealogia-node genealogia-node--mas" style="background:'.$n['color'].';" href="'.$urlEsc.'">'
                         . '<div class="genealogia-node__nombre">'.$nombreEsc.'</div>'
                         . '<div class="genealogia-node__fecha">Ver todos</div>'
                         . '</a>';
              $tooltip = 'Haz clic para ver los '.$n['nombre'];
          } else {
              $fechaTxt  = $n['fecha'] !== "" ? $n['fecha'] : "Sin fecha registrada";

              $htmlLabel = '<div class="genealogia-node" style="background:'.$n['color'].';">'
                         . '<div class="genealogia-node__nombre" title="'.$nombreEsc.'">'.$nombreEsc.'</div>'
                         . '<div class="genealogia-node__fecha">🕓 '.htmlspecialchars($fechaTxt, ENT_QUOTES, 'UTF-8').'</div>'
                         . '</div>';

              $tooltipParts = [];
              $tooltipParts[] = 'Grupo: '.$n['nombre'];
              $tooltipParts[] = 'Generación: '.$n['generacion'];
              $tooltipParts[] = 'Inicio: '.$fechaTxt;
              if($n['plantador'] !== "") $tooltipParts[] = 'Plantador: '.$n['plantador'];
              if($n['madreTxt']  !== "") $tooltipParts[] = 'Grupo madre: '.$n['madreTxt'];
              $tooltip = implode(" | ", $tooltipParts);
          }

          $rows[] = "[{v:".json_encode($nodeId).", f:".json_encode($htmlLabel)."}, "
                   . json_encode($parentId).", ".json_encode($tooltip)."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var el = document.getElementById('chart_genealogia');
  if(!el) return;

  var chart = new google.visualization.OrgChart(el);

  google.visualization.events.addListener(chart, 'select', function(){
    var sel = chart.getSelection();
    if(!sel.length) return;
    var nodo = GEN_NODOS[sel[0].row];
    if(!nodo || nodo.tipo === 'mas') return;
    dbAbrirReportes(nodo.id, nodo.nombre);
  });

  chart.draw(data, {
    allowHtml: true,
    allowCollapse: true,
    size: 'medium'
  });
  <?php } ?>
}
</script>
