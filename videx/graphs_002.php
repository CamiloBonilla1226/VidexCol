<?php
/*******************************************
En un rango de fechas.
Cuantos prospecto hay desde
el día 1 y cuantos el día
último. De cada
COMERCIAL
La diferencia es = nuevos
prospectos, en un lapso de
(3– 6) cortes mostrando el
avance. (éste gráfica es
animado)
https://developers.google.co
m/chart/interactive/docs/animation

ID MENU = 2
*******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

$varError = 0;
$totalProspectos = 0;
$sqlJoinAdicional = "";
$idComercial = isset($_REQUEST["idComercial"]) ? soloNumeros($_REQUEST["idComercial"]) : "";
$ids_comercial = array();
$nombresComercial = array();
$tempNombres = array();
$datosArr = array();


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 2 
AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0)
{
    die("NO esta autorizado a ver esta grafica.");
}

/*
*   FILTROS DE FECHAS.
*/
$busquedaFechaIni = date("Y-m-01", strtotime("-6 months"));
if(isset($_REQUEST["fechaInicial"]) && soloNumeros($_REQUEST["fechaInicial"]) != ""){
	$busquedaFechaIni = eliminarInvalidos($_REQUEST["fechaInicial"]);
}
//
$busquedaFechaFin = date("Y-m-d");
if(isset($_REQUEST["fechaFinal"]) && soloNumeros($_REQUEST["fechaFinal"]) != ""){
	$busquedaFechaFin = eliminarInvalidos($_REQUEST["fechaFinal"]);
}


/*****************************************************************************
//  EN ESTA GRAFICA NO CUENTA PERO PODRIA SERVIR PARA OTRAS
//Si es cliente o autorizado
*****************************************************************************/
if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 160){
    //
    $forzar_cliente = $_SESSION["micliente"];
    $idCliente = $forzar_cliente;
    $_REQUEST["idCliente"] = $forzar_cliente; 
    //  
}
else{
    $forzar_cliente = 0;
}

$sqlFiltros = "";
if(soloNumeros($_REQUEST["idComercial"]) != "")
{
    $idComercial = soloNumeros($_REQUEST["idComercial"]);
	$sqlFiltros .= " AND cliente.idComercial = '".$idComercial."'";
}

if(soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "")
{
	$sqlFiltros .= " AND cliente.interno = '".soloNumeros($_REQUEST["interno"])."'";
}

if($_SESSION["clientes_filtro"] != ""){
	$sqlFiltros .= " AND cliente.id IN (".$_SESSION["clientes_filtro"].")";			
}

/*if(soloNumeros($_REQUEST["idCliente"]) != "")
{
    $idCliente = soloNumeros($_REQUEST["idCliente"]);
	$sqlJoinAdicional .= " vehiculo, usuario,";
	$sqlFiltros .= " AND usuario.id = vehiculo.idCliente AND vehiculo.id = preoperacional_main.enc_idVehiculo";
	$sqlFiltros .= " AND vehiculo.idCliente = '".$idCliente."'";
    //
	$sqlFiltros2 .= " AND vehiculo.idCliente = '".$idCliente."'";
    
    if(soloNumeros($_REQUEST["vehiculo_clase"]) != "")
    {
        $vehiculo_clase = soloNumeros($_REQUEST["vehiculo_clase"]);
        $sqlFiltros .= " AND vehiculo.vehiculo_clase = '".$vehiculo_clase."'";
        //
        $sqlFiltros2 .= " AND vehiculo.vehiculo_clase = '".$vehiculo_clase."'";
    }    
}
else{
    if($_SESSION["perfil"] == 161){
        //$sqlFiltro .= " AND .idUsuario = ".$_SESSION["id"];
        $sqlJoinAdicional .= " vehiculo_usuarios,";
        $sqlFiltros .= " AND vehiculo_usuarios.idVehiculo = preoperacional_main.enc_idVehiculo";
        $sqlFiltros .= " AND vehiculo_usuarios.idUsuario = ".$_SESSION["id"];
        //
        $sqlJoinAdicional2 .= " vehiculo_usuarios,";
        $sqlFiltros2 .= " AND vehiculo_usuarios.idVehiculo = vehiculo.id";
        $sqlFiltros2 .= " AND vehiculo_usuarios.idUsuario = ".$_SESSION["id"];
        //        
        
    }
    //    
    if(soloNumeros($_REQUEST["vehiculo_clase"]) != "")
    {
        $vehiculo_clase = soloNumeros($_REQUEST["vehiculo_clase"]);
        $sqlJoinAdicional .= " vehiculo,";
        $sqlFiltros .= " AND vehiculo.id = preoperacional_main.enc_idVehiculo";
        $sqlFiltros .= " AND vehiculo.vehiculo_clase = '".$vehiculo_clase."'";
        //
        $sqlFiltros2 .= " AND vehiculo.vehiculo_clase = '".$vehiculo_clase."'";
    }
}*/




/*
*	PIE - Grafica de PIE mostrando cantidad de nuevos prospectos X comercial
*/
$nombreGrafica ="NUEVOS PROSPECTOS MENSUAL";
$datos = array();
//
//
$sql= "SELECT 
        COUNT(cliente.id) as total_prospectos, usuario.id, usuario.nombre, MONTH(cliente.creacionFecha) as mes, YEAR(cliente.creacionFecha) as anho";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" WHERE cliente.creacionFecha BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY anho, mes, cliente.idComercial";
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
        //
        if(in_array($PSN->f('id'), $ids_comercial) == false){
            $ids_comercial[] = $PSN->f('id');
            $nombresComercial[$PSN->f('id')]["nombre"] = $PSN->f('nombre');
        }
        //
        //echo "Registro: <br/>&nbsp;&nbsp;&nbsp;&nbsp;";
        //
        $nombresComercial[$PSN->f('id')][$PSN->f('anho')][$PSN->f('mes')] = $PSN->f('total_prospectos');
        $totalProspectos += $PSN->f('total_prospectos');
        //        
        /*echo " Nombre: ".$PSN->f('nombre');
        echo " Año: ".$PSN->f('anho');
        echo " Mes: ".$PSN->f('mes');
        echo " Cantidad: ".$PSN->f('total_prospectos');*/
        //
        //
	}
}else{
    $varError = 1;
}
//
/*
    echo '<pre>'; 
        print_r($nombresComercial); 
    echo '</pre>';
*/
//
//
$mesInicial = date("n", strtotime($busquedaFechaIni));
$anhoInicial = date("Y", strtotime($busquedaFechaIni));
//
$anhoFinal = date("Y", strtotime($busquedaFechaFin));
$mesFinal = date("n", strtotime($busquedaFechaFin));
//
/*
echo "<br />Mes Inicial: ".$mesInicial;
echo "<br />Anho Inicial: ".$anhoInicial;
echo "<br /><br />Mes Final: ".$mesFinal;
echo "<br />Anho Final: ".$anhoFinal;
*/
//
//
foreach($ids_comercial as $id_actual){
    //
    $tempNombres[] = '"'.$nombresComercial[$id_actual]["nombre"].'"';
}
//
$stringValoresNom = implode(",", $tempNombres);
$datosArr[] = '["Meses"'.($stringValoresNom != "" ? ', '.$stringValoresNom : '').']';
//
for($tempAnho=$anhoInicial;$tempAnho<=$anhoFinal;$tempAnho++){
    //
    if($tempAnho == $anhoFinal){
        $mesFinalTemp = $mesFinal;
    }else{
        $mesFinalTemp = 12;
    }
    //
    for($tempMes=$mesInicial;$tempMes<=$mesFinalTemp;$tempMes++){
        $mes_actual = $mesesNom[$tempMes];
        //
        //
        $valor = array();
        foreach($ids_comercial as $id_actual){
            //
            $tempValor = $nombresComercial[$id_actual][$tempAnho][$tempMes];
            
            if($tempValor > 0){
                $valor[] = $tempValor;
            }else{
                $valor[] = 0;
            }
            //            
            $contador++;
        }
        //
        $stringValores = implode(",", $valor);
        $datosArr[] = '["'.$mes_actual.'", '.$stringValores.']';
    }
}
//
if($varError != 1){
    ?><script type="text/javascript">
      google.charts.load("current", {packages:["corechart", "treemap"]});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
                    var data = google.visualization.arrayToDataTable([
                        <?=implode(",", $datosArr); ?>
                    ]);

                    var options = {
                        animation: {
                            startup: true,
                            duration: 2000,
                            easing: 'out'
                        },
                        curveType: 'none',
                        colors: ['#0ea5a4', '#0f766e', '#2563eb', '#7c3aed', '#f59e0b', '#ef4444', '#84cc16', '#06b6d4'],
                        legend: { position: 'bottom', textStyle: { color: '#0f172a', fontSize: 12 } },
                        chartArea: { left: 52, top: 25, width: '90%', height: '72%' },
                        hAxis: { textStyle: { color: '#334155' } },
                        vAxis: { minValue: 0, textStyle: { color: '#334155' } },
                        backgroundColor: 'transparent'
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
      }

            window.addEventListener('resize', function () {
                drawChart();
            });
    </script><?
}
?>
<style>
.g002-page {
    --g002-bg: #f6f8ff;
    --g002-card: #ffffff;
    --g002-line: #d8def5;
    --g002-text: #0f172a;
    --g002-soft: #475569;
    --g002-accent: #2563eb;
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: radial-gradient(circle at 8% 8%, rgba(37,99,235,.16), rgba(37,99,235,0) 42%), var(--g002-bg);
    border-radius: 18px;
    padding: 18px;
}

.g002-head {
    margin-bottom: 14px;
}

.g002-head h2 {
    margin: 0;
    color: var(--g002-text);
    font-weight: 800;
    font-size: 24px;
    letter-spacing: -.02em;
}

.g002-head p {
    margin: 6px 0 0;
    color: var(--g002-soft);
    font-size: 13px;
}

.g002-card {
    background: var(--g002-card);
    border: 1px solid var(--g002-line);
    border-radius: 16px;
    box-shadow: 0 12px 28px rgba(30, 41, 59, .08);
    padding: 16px;
}

.g002-filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.g002-field label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.g002-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}

.g002-btn {
    background: linear-gradient(180deg, #3b82f6, #1d4ed8);
    border: 0;
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: 10px 16px;
}

.g002-chart-card {
    margin-top: 14px;
}

.g002-chart-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.g002-chart-head h3 {
    margin: 0;
    color: #0f172a;
    font-size: 19px;
    font-weight: 800;
}

.g002-total {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
}

#donutchart {
    width: 100%;
    min-height: 420px;
}

.g002-empty {
    margin-top: 14px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #9a3412;
    padding: 12px 14px;
    border-radius: 12px;
    font-weight: 600;
}

@media (max-width: 900px) {
    .g002-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .g002-page {
        padding: 12px;
    }

    .g002-filters {
        grid-template-columns: 1fr;
    }

    #donutchart {
        min-height: 320px;
    }
}
</style>

<div class="container">
<section class="g002-page">
    <div class="g002-head">
        <h2>Nuevos prospectos mensual por comercial</h2>
        <p>Comportamiento mensual acumulado por comercial en el periodo seleccionado.</p>
    </div>

<form action="index.php" method="get" name="form1" class="g002-card">
<input type="hidden" name="doc" value="graphs_002" />

    <div class="g002-filters">
        <div class="g002-field">
            <label for="idComercial">Comercial</label>
            <?
                ?><select name="idComercial" id="idComercial" class="form-control"><?
                if(!isset($_REQUEST["ctrl_cli"])){
                    ?><option value="">Todos</option><?
                }
                $sql = "SELECT usuario.id, usuario.nombre ";
                $sql.=" FROM usuario ";
                $sql.=" WHERE tipo = 162";
                if(isset($forzar_comercial) && $forzar_comercial > 0){
                    $sql.=" AND id = '".$idComercial."'";
                }
                $sql.=" ORDER BY nombre asc";
                $PSN2->query($sql);
                $numero=$PSN2->num_rows();
                if($numero > 0)
                {
                    while($PSN2->next_record())
                    {
                        ?><option value="<?=$PSN2->f('id'); ?>" <? if($idComercial == $PSN2->f('id')){ ?>selected="selected"<? } ?>><?=$PSN2->f('nombre'); ?></option><?
                    }
                }
                ?></select>
        </div>

		<div class="g002-field">
			<label for="interno">Cliente o propio</label>
			<select name="interno" id="interno" class="form-control">
				<option value="">Todos</option>
				<option value="0" <? if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
				<option value="1" <? if(isset($_GET['interno']) && $_GET['interno'] == "1"){ echo 'selected="selected"'; } ?>>Propio</option>
			</select>
		</div>

        <div class="g002-field">
            <label for="fechaInicial">Fecha inicial</label>
            <input name="fechaInicial" type="date" id="fechaInicial" maxlength="250" value="<?=$busquedaFechaIni; ?>" class="form-control" />
        </div>

        <div class="g002-field">
            <label for="fechaFinal">Fecha final</label>
            <input name="fechaFinal" type="date" id="fechaFinal" maxlength="250" value="<?=$busquedaFechaFin; ?>" class="form-control" />
        </div>
    </div>

    <div class="g002-actions">
        <button type="submit" class="g002-btn">Aplicar filtros</button>
    </div>
</form>

<?
/*
*    
*/
if($varError == 1){
    ?><div class="g002-empty">No se ha encontrado ningun registro para el rango de fechas seleccionado.</div><?
}


if($varError != 1){
    ?>
        <div class="g002-card g002-chart-card">
                <div class="g002-chart-head">
                        <h3><?=$nombreGrafica; ?></h3>
                        <span class="g002-total">Total prospectos: <?=$totalProspectos; ?></span>
                </div>
                <div id="donutchart" class="chart"></div>
    </div>
    <?
}
?>
</section>
</div>