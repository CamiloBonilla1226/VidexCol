<?php
/*******************************************
Sumatoria de estados de
cotización (borrador, lista
enviar, enviada, aprobada con
pagos, no aprobada, pagada
totalmente) mes a mes.
Todos los comerciales.
Cada uno de los comerciales.

ID MENU = 5
*******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

//
$arrayColores = array('#1ABC9C',
                    '#2ECC71',
                    '#3498DB',
                    '#9B59B6',
                    '#34495E',
                    '#16A085',
                    '#27AE60',
                    '#2980B9',
                    '#8E44AD',
                    '#2C3E50',
                    '#F1C40F',
                    '#E67E22',
                    '#E74C3C',
                    '#ECF0F1',
                    '#95A5A6',
                    '#F39C12',
                    '#D35400',
                    '#C0392B',
                    '#BDC3C7',
                    '#7F8C8D',
                    '#1ABC9C',
                    '#2ECC71',
                    '#3498DB',
                    '#9B59B6',
                    '#34495E',
                    '#16A085',
                    '#27AE60',
                    '#2980B9',
                    '#8E44AD',
                    '#2C3E50',
                    '#F1C40F',
                    '#E67E22',
                    '#E74C3C',
                    '#ECF0F1',
                    '#95A5A6',
                    '#F39C12',
                    '#D35400',
                    '#C0392B',
                    '#BDC3C7',
                    '#7F8C8D');

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

/*
*   INICIALIZACIÓN DE VARIABLES
*/
$varError = 0;
$totalGastos = 0;
$totalGastado = 0;
$totalAbonos = 0;
$totalAbonado = 0;
$nombreGrafica = "TIPOS DE GASTOS CREADOS";
$nombreGraficaB = "TIPOS DE ABONOS CREADOS";


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 9 
AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0)
{
    die("NO esta autorizado a ver esta grafica.");
}

/*
*   FILTROS DE FECHAS.
*/
$busquedaFechaIni = date("Y-m-01");
if(isset($_REQUEST["fechaInicial"]) && soloNumeros($_REQUEST["fechaInicial"]) != ""){
	$busquedaFechaIni = eliminarInvalidos($_REQUEST["fechaInicial"]);
}
//
$busquedaFechaFin = date("Y-m-d");
if(isset($_REQUEST["fechaFinal"]) && soloNumeros($_REQUEST["fechaFinal"]) != ""){
	$busquedaFechaFin = eliminarInvalidos($_REQUEST["fechaFinal"]);
}

$globales = 0;
if(isset($_REQUEST["globales"]) && soloNumeros($_REQUEST["globales"]) != ""){
	$globales = 1;
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
if(soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "")
{
	$sqlFiltros .= " AND cliente.interno = '".soloNumeros($_REQUEST["interno"])."'";
}


if($_SESSION["clientes_filtro"] != ""){
	$sqlFiltros .= " AND gastos.idCliente IN (".$_SESSION["clientes_filtro"].")";			
}
/*
*	PIE - Grafica de PIE
*/
$nombreGrafica ="TIPOS DE GASTOS CREADOS";
$datos = array();
//
$sql= "SELECT 
        COUNT(gastos.id) as total_gastos, SUM(gastos.valor) as total_gastado, categorias.descripcion, MONTH(gastos.fechaCreacion) as mes, YEAR(gastos.fechaCreacion) as anho";
$sql.=" FROM gastos ";
$sql.=" LEFT JOIN categorias ON categorias.id = gastos.tipo ";
if((soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "") || $_SESSION["clientes_filtro"] != "")
{
	$sql.=" LEFT JOIN cotizacion ON cotizacion.id = gastos.idCotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
}
$sql.=" WHERE gastos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;

if($globales == 1){
    $sql .= " AND gastos.idCotizacion = 0";
}

$sql.=" GROUP BY gastos.tipo";
//
//echo $sql;
//
$datosArr[] = '["Tipo", "Gastos", { role: \'style\' }]';
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    $contador = 0;
	while($PSN->next_record())
	{
        $datosArr[] = "['".$PSN->f('descripcion')."', ".intval($PSN->f('total_gastado')).", '".$arrayColores[$contador]."']";
        $totalGastos += $PSN->f('total_gastos');
        $totalGastado += $PSN->f('total_gastado');
        $contador++;
	}
}else{
    $varError = 1;
}


$sqlFiltros = "";
if(soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "")
{
	$sqlFiltros .= " AND cliente.interno = '".soloNumeros($_REQUEST["interno"])."'";
}


if($_SESSION["clientes_filtro"] != ""){
	$sqlFiltros .= " AND abonos.idCliente IN (".$_SESSION["clientes_filtro"].")";			
}

/*
*	ABONOS RECIBIDOS
*/
$nombreGraficaB = "TIPOS DE ABONOS CREADOS";
$datosB = array();
//
$sql= "SELECT 
        COUNT(abonos.id) as total_abonos, SUM(abonos.valor) as total_abonado, categorias.descripcion, MONTH(abonos.fechaCreacion) as mes, YEAR(abonos.fechaCreacion) as anho";
$sql.=" FROM abonos ";
$sql.=" LEFT JOIN categorias ON categorias.id = abonos.tipo ";

if((soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "") || $_SESSION["clientes_filtro"] != "")
{
	$sql.=" LEFT JOIN cotizacion ON cotizacion.id = abonos.idCotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
}

$sql.=" WHERE abonos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;

/*if($globales == 1){
    $sql .= " AND abonos.idCotizacion = 0";
}*/

$sql.=" GROUP BY abonos.tipo";
$datosArrB[] = '["Tipo", "Abono", { role: \'style\' }]';
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    $contador = 0;
	while($PSN->next_record())
	{
        $datosArrB[] = "['".$PSN->f('descripcion')."', ".intval($PSN->f('total_abonado')).", '".$arrayColores[$contador]."']";
        $totalAbonos += $PSN->f('total_abonos');
        $totalAbonado += $PSN->f('total_abonado');
        $contador++;
	}
}else{
    $varError = 1;
}

//
if($varError != 1){
    ?>
    <script type="text/javascript">
    google.charts.load("current", {packages:["corechart", "treemap"]});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            <?=implode(",", $datosArr); ?>
        ]);

        var options = {
            animation:{
                "startup": true,
                duration: 2000,
                easing: 'out'
            },
            chartArea: { width: '90%', height: '85%' }
        };

        var chart = new google.visualization.PieChart(document.getElementById('g008-chart-a'));
        window.g008ChartA = chart;
        chart.draw(data, options);

        var datab = google.visualization.arrayToDataTable([
            <?=implode(",", $datosArrB); ?>
        ]);

        var optionsB = {
            animation:{
                "startup": true,
                duration: 2000,
                easing: 'out'
            },
            chartArea: { width: '90%', height: '85%' }
        };

        var chartB = new google.visualization.PieChart(document.getElementById('g008-chart-b'));
        window.g008ChartB = chartB;
        chartB.draw(datab, optionsB);

        window.addEventListener('resize', function() {
            if (window.g008ChartA) window.g008ChartA.draw(data, options);
            if (window.g008ChartB) window.g008ChartB.draw(datab, optionsB);
        });
    }
    </script>
    <?php
}
?>
<style>
    .g008-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g008-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
    .g008-hero h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
    .g008-hero-subtitle { opacity: 0.9; margin-top: 8px; font-size: 14px; }
    .g008-filters { background: #f8fafc; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
    .g008-filters h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #1e293b; }
    .g008-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .g008-field { display: flex; flex-direction: column; }
    .g008-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
    .g008-field select, .g008-field input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; }
    .g008-field select:focus, .g008-field input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .g008-actions { display: flex; gap: 10px; margin-top: 15px; }
    .g008-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .g008-summary { background: #eef2ff; border: 1px solid #c7d2fe; color: #312e81; padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .g008-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .g008-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
    .g008-card h2 { margin: 0 0 8px 0; font-size: 20px; color: #1e293b; }
    .g008-card p { margin: 0 0 16px 0; font-size: 14px; color: #64748b; }
    .g008-empty { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; text-align: center; color: #92400e; font-size: 14px; }
    #g008-chart-a, #g008-chart-b { width: 100%; height: 360px; }
    @media (max-width: 900px) { .g008-grid { grid-template-columns: repeat(2, 1fr); } .g008-cards { grid-template-columns: 1fr; } .g008-hero h1 { font-size: 24px; } }
    @media (max-width: 560px) { .g008-grid { grid-template-columns: 1fr; } .g008-hero { padding: 20px; } .g008-hero h1 { font-size: 20px; } .g008-filters, .g008-card { padding: 16px; } #g008-chart-a, #g008-chart-b { height: 300px; } }
</style>

<div class="g008-container">
    <div class="g008-hero">
        <h1><?=$nombreGrafica; ?> / <?=$nombreGraficaB; ?></h1>
        <div class="g008-hero-subtitle">An&aacute;lisis de tipos de gastos y abonos</div>
    </div>

    <form action="index.php" method="get" name="form1" class="g008-filters">
        <input type="hidden" name="doc" value="graphs_008" />
        <h3>Filtros de b&uacute;squeda</h3>

        <div class="g008-grid">
            <div class="g008-field">
                <label for="globales">Ver solo gastos globales</label>
                <input name="globales" type="checkbox" id="globales" value="1" <?php if($globales == 1){ echo 'checked="checked"'; } ?> />
            </div>

            <div class="g008-field">
                <label for="interno">Tipo de Cliente</label>
                <select name="interno" id="interno">
                    <option value="">Todos</option>
                    <option value="0" <?php if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                    <option value="1" <?php if(isset($_GET['interno']) && $_GET['interno'] == 1){ echo 'selected="selected"'; } ?>>Propio</option>
                </select>
            </div>

            <div class="g008-field">
                <label for="fechaInicial">Fecha Inicial</label>
                <input name="fechaInicial" type="date" id="fechaInicial" value="<?=$busquedaFechaIni; ?>" />
            </div>

            <div class="g008-field">
                <label for="fechaFinal">Fecha Final</label>
                <input name="fechaFinal" type="date" id="fechaFinal" value="<?=$busquedaFechaFin; ?>" />
            </div>
        </div>

        <div class="g008-actions">
            <button type="submit" class="g008-btn">Aplicar Filtros</button>
        </div>
    </form>

    <?php if($varError == 1){ ?>
        <div class="g008-empty">
            <strong>Sin resultados</strong><br>
            No se ha encontrado ning&uacute;n registro para el rango de fechas seleccionado.
        </div>
    <?php } ?>

    <?php if($varError != 1){
        $saldo = $totalAbonado - $totalGastado;
    ?>
        <div class="g008-summary">
            Gastos: $<?=number_format($totalGastado, 0, ",", "."); ?> | Abonos: $<?=number_format($totalAbonado, 0, ",", "."); ?> | Diferencia: $<?=number_format($saldo, 0, ",", "."); ?>
        </div>

        <div class="g008-cards">
            <div class="g008-card">
                <h2><?=$nombreGrafica; ?></h2>
                <p>Total de gastos: <?=$totalGastos; ?> por un valor total de $<?=number_format($totalGastado, 0, ",", "."); ?></p>
                <div id="g008-chart-a"></div>
            </div>

            <div class="g008-card">
                <h2><?=$nombreGraficaB; ?></h2>
                <p>Total de abonos: <?=$totalAbonos; ?> por un valor total de $<?=number_format($totalAbonado, 0, ",", "."); ?></p>
                <div id="g008-chart-b"></div>
            </div>
        </div>
    <?php } ?>
</div>