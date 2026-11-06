<?php
/*******************************************
Sumatoria de estados de
cotización (borrador, lista
enviar, enviada, aprobada con
pagos, no aprobada, pagada
totalmente) mes a mes.

ID MENU = 6
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
$totalCotizaciones = 0;
$totalCotizado = 0;
$idComercial = 0;
$idCliente = 0;
$sqlJoinAdicional = "";
$nombreGrafica = "METODOS DE COTIZACIÓN DE LAS COTIZACIONES CREADAS";


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 6 
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

if(soloNumeros($_REQUEST["idCliente"]) != "")
{
    $idCliente = soloNumeros($_REQUEST["idCliente"]);
	$sqlFiltros .= " AND cliente.id = '".$idCliente."'";
}

if(soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "")
{
	$sqlFiltros .= " AND cliente.interno = '".soloNumeros($_REQUEST["interno"])."'";
}

if($_SESSION["clientes_filtro"] != ""){
	$sqlFiltros .= " AND cliente.id IN (".$_SESSION["clientes_filtro"].")";			
}

/*
*	PIE - Grafica de PIE
*/
$nombreGrafica ="METODOS DE COTIZACIÓN DE LAS COTIZACIONES CREADAS";
$datos = array();
//
//
$sql= "SELECT 
        COUNT(cotizacion.id) as total_cotizaciones, SUM(cotizacion.valorCotizado) as total_cotizado, cliente.idComercial, categorias.descripcion, MONTH(cotizacion.creacionFecha) as mes, YEAR(cotizacion.creacionFecha) as anho";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" LEFT JOIN categorias ON categorias.id = cotizacion.metodoCotizacion ";
$sql.=" WHERE cotizacion.creacionFecha BETWEEN '".$busquedaFechaIni." 00:00:00' AND '".$busquedaFechaFin." 23:59:59'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cotizacion.metodoCotizacion";
//
//echo $sql;
//
$datosArr[] = '["Metodo", "Cotizaciones", { role: \'style\' }]';
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    $contador = 0;
	while($PSN->next_record())
	{
        $datosArr[] = "['".$PSN->f('descripcion')."', ".intval($PSN->f('total_cotizaciones')).", '".$arrayColores[$contador]."']";
        //
        $totalCotizaciones += $PSN->f('total_cotizaciones');
        $totalCotizado += $PSN->f('total_cotizado');
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
                is3D: true,
                chartArea: { width: '90%', height: '85%' },
                legend: { position: 'right' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('g006-chart'));
            window.g006Chart = chart;
            chart.draw(data, options);

            window.addEventListener('resize', function() {
                if (window.g006Chart) window.g006Chart.draw(data, options);
            });
      }
    </script>
    <?php
}
?>
<style>
    .g006-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g006-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
    .g006-hero h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
    .g006-hero-subtitle { opacity: 0.9; margin-top: 8px; font-size: 14px; }
    .g006-filters { background: #f8fafc; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
    .g006-filters h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #1e293b; }
    .g006-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .g006-field { display: flex; flex-direction: column; }
    .g006-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
    .g006-field select, .g006-field input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; }
    .g006-field select:focus, .g006-field input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .g006-actions { display: flex; gap: 10px; margin-top: 15px; }
    .g006-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .g006-result { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
    .g006-result-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
    .g006-result-title { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
    .g006-result-total { font-size: 14px; color: #64748b; }
    .g006-result-value { font-size: 20px; font-weight: 700; color: #0f766e; }
    .g006-note { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin-top: 15px; font-size: 12px; color: #92400e; border-radius: 4px; }
    .g006-empty { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; text-align: center; color: #92400e; font-size: 14px; }
    #g006-chart { width: 100%; height: 420px; }
    @media (max-width: 900px) { .g006-grid { grid-template-columns: repeat(2, 1fr); } .g006-hero h1 { font-size: 24px; } }
    @media (max-width: 560px) { .g006-grid { grid-template-columns: 1fr; } .g006-hero { padding: 20px; } .g006-hero h1 { font-size: 20px; } .g006-filters, .g006-result { padding: 16px; } #g006-chart { height: 320px; } }
</style>

<div class="g006-container">
    <div class="g006-hero">
        <h1><?=$nombreGrafica; ?></h1>
        <div class="g006-hero-subtitle">Distribuci&oacute;n por m&eacute;todo de cotizaci&oacute;n</div>
    </div>

    <form action="index.php" method="get" name="form1" class="g006-filters">
        <input type="hidden" name="doc" value="graphs_006" />
        <h3>Filtros de b&uacute;squeda</h3>

        <div class="g006-grid">
            <div class="g006-field">
                <label for="idComercial">Comercial</label>
                <select name="idComercial" id="idComercial">
                    <option value="">Todos</option>
                    <?php
                        $sql = "SELECT usuario.id, usuario.nombre FROM usuario WHERE tipo = 162";
                        if(isset($forzar_comercial) && $forzar_comercial > 0){
                            $sql .= " AND id = '".$idComercial."'";
                        }
                        $sql .= " ORDER BY nombre asc";
                        $PSN2->query($sql);
                        $numero = $PSN2->num_rows();
                        if($numero > 0){
                            while($PSN2->next_record()){
                                ?><option value="<?=$PSN2->f('id'); ?>" <?php if($idComercial == $PSN2->f('id')){ echo 'selected="selected"'; } ?>><?=$PSN2->f('nombre'); ?></option><?php
                            }
                        }
                    ?>
                </select>
            </div>

            <div class="g006-field">
                <label for="idCliente">Prospecto</label>
                <select name="idCliente" id="idCliente">
                    <option value="">Todos</option>
                    <?php
                        $sql = "SELECT cliente.id, cliente.nombre FROM cliente WHERE 1";
                        if(isset($forzar_cliente) && $forzar_cliente > 0){
                            $sql .= " AND id = '".$idCliente."'";
                        }
                        if($_SESSION["clientes_filtro"] != ""){
                            $sql .= " AND cliente.id IN (".$_SESSION["clientes_filtro"].")";
                        }
                        $sql .= " ORDER BY nombre asc";
                        $PSN2->query($sql);
                        $numero = $PSN2->num_rows();
                        if($numero > 0){
                            while($PSN2->next_record()){
                                ?><option value="<?=$PSN2->f('id'); ?>" <?php if($idCliente == $PSN2->f('id')){ echo 'selected="selected"'; } ?>><?=$PSN2->f('nombre'); ?></option><?php
                            }
                        }
                    ?>
                </select>
            </div>

            <div class="g006-field">
                <label for="interno">Tipo de Cliente</label>
                <select name="interno" id="interno">
                    <option value="">Todos</option>
                    <option value="0" <?php if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                    <option value="1" <?php if(isset($_GET['interno']) && $_GET['interno'] == 1){ echo 'selected="selected"'; } ?>>Propio</option>
                </select>
            </div>

            <div class="g006-field">
                <label for="fechaInicial">Fecha Inicial</label>
                <input name="fechaInicial" type="date" id="fechaInicial" value="<?=$busquedaFechaIni; ?>" />
            </div>

            <div class="g006-field">
                <label for="fechaFinal">Fecha Final</label>
                <input name="fechaFinal" type="date" id="fechaFinal" value="<?=$busquedaFechaFin; ?>" />
            </div>
        </div>

        <div class="g006-actions">
            <button type="submit" class="g006-btn">Aplicar Filtros</button>
        </div>
    </form>

    <?php if($varError == 1){ ?>
        <div class="g006-empty">
            <strong>Sin resultados</strong><br>
            No se ha encontrado ning&uacute;n registro para el rango de fechas seleccionado.
        </div>
    <?php } ?>

    <?php if($varError != 1){ ?>
        <div class="g006-result">
            <div class="g006-result-header">
                <h2 class="g006-result-title"><?=$nombreGrafica; ?></h2>
                <div>
                    <div class="g006-result-total">Total de cotizaciones: <span class="g006-result-value"><?=$totalCotizaciones; ?></span></div>
                    <div class="g006-result-total" style="margin-top: 8px;">Valor total: <span class="g006-result-value">$<?=number_format($totalCotizado, 0, ",", "."); ?></span></div>
                </div>
            </div>
            <div id="g006-chart"></div>
            <div class="g006-note">
                <strong>Nota:</strong> Esta gr&aacute;fica toma como referencia la FECHA DE CREACI&Oacute;N de la cotizaci&oacute;n b&aacute;sica.
            </div>
        </div>
    <?php } ?>
</div>