<?php
/*******************************************
En un rango de fechas.
Hacer sumatoria de cantidad
de cotizaciones mes a mes.

ID MENU = 4
*******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 4 
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
*	INICIALIZACIÓN DE VARIABLES
*/
$varError = 0;
$totalCotizaciones = 0;
$totalCotizado = 0;
$idComercial = 0;
$sqlJoinAdicional = "";
$nombreGrafica = "COTIZACIONES MENSUAL POR CANTIDAD";
$datos = array();
$nombresComercial = array();
//
$sql= "SELECT 
        COUNT(cotizacion.id) as total_cotizaciones, SUM(cotizacion.valorCotizado) as total_cotizado, usuario.id, usuario.nombre, 
		MONTH(cotizacion.fechaCotizacion) as mes, 
		YEAR(cotizacion.fechaCotizacion) as anho";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" WHERE cotizacion.fechaCotizacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY anho, mes";
//
//echo $sql;
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
        //
        /*if(in_array($PSN->f('id'), $ids_comercial) == false){
            $ids_comercial[] = $PSN->f('id');
            $nombresComercial[$PSN->f('id')]["nombre"] = $PSN->f('nombre');
        }
        $nombresComercial[$PSN->f('id')][$PSN->f('anho')][$PSN->f('mes')] = $PSN->f('total_prospectos');
        $totalProspectos += $PSN->f('total_prospectos');*/
        $nombresComercial[$PSN->f('anho')][$PSN->f('mes')] = $PSN->f('total_cotizaciones');
        $totalCotizaciones += $PSN->f('total_cotizaciones');
        $totalCotizado += $PSN->f('total_cotizado');
	}
}else{
    $varError = 1;
}
//var_dump($nombresComercial);
//
$mesInicial = date("n", strtotime($busquedaFechaIni));
$anhoInicial = date("Y", strtotime($busquedaFechaIni));
//
$anhoFinal = date("Y", strtotime($busquedaFechaFin));
$mesFinal = date("n", strtotime($busquedaFechaFin));
//
//
$datosArr[] = '["Meses", "Cotizaciones", { role: \'style\' }]';
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
//
$contador = 0;
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
        $tempValor = $nombresComercial[$tempAnho][$tempMes];
        //
        if($tempValor > 0){
            $valor = $tempValor;
        }else{
            $valor = 0;
        }
        $datosArr[] = '["'.$mes_actual.'", '.$valor.', "'.$arrayColores[$contador].'"]';
        $contador++;
    }
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
                    easing: 'out',
                },
                legend: { position: 'none' },
                chartArea: { width: '90%', height: '85%' },
                hAxis: { textStyle: { fontSize: 11 } },
                vAxis: { textStyle: { fontSize: 11 } }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('g004-chart'));
            window.g004Chart = chart;
            chart.draw(data, options);
            
            window.addEventListener('resize', function() {
                if (window.g004Chart) window.g004Chart.draw(data, options);
            });
      }
    </script>

    <style>
        .g004-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .g004-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
        .g004-hero h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        .g004-hero-subtitle { opacity: 0.9; margin-top: 8px; font-size: 14px; }
        .g004-filters { background: #f8fafc; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .g004-filters h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #1e293b; }
        .g004-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .g004-field { display: flex; flex-direction: column; }
        .g004-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
        .g004-field select, .g004-field input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: white; transition: all 0.3s ease; }
        .g004-field select:hover, .g004-field input:hover { border-color: #94a3b8; }
        .g004-field select:focus, .g004-field input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .g004-actions { display: flex; gap: 10px; margin-top: 15px; }
        .g004-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); }
        .g004-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .g004-result { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
        .g004-result-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        .g004-result-title { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
        .g004-result-total { font-size: 14px; color: #64748b; }
        .g004-result-value { font-size: 20px; font-weight: 700; color: #0f766e; }
        #g004-chart { width: 100%; height: 400px; margin-top: 20px; }
        .g004-empty { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; text-align: center; color: #92400e; font-size: 14px; }
        .g004-note { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin-top: 15px; font-size: 12px; color: #92400e; border-radius: 4px; }
        @media (max-width: 900px) { .g004-grid { grid-template-columns: repeat(2, 1fr); } .g004-hero h1 { font-size: 24px; } }
        @media (max-width: 560px) { .g004-grid { grid-template-columns: 1fr; } .g004-hero { padding: 20px; } .g004-hero h1 { font-size: 20px; } .g004-filters { padding: 16px; } .g004-result { padding: 16px; } #g004-chart { height: 300px; } }
    </style>

    <div class="g004-container">
        <div class="g004-hero">
            <h1><?=$nombreGrafica; ?></h1>
            <div class="g004-hero-subtitle">Análisis de cotizaciones mensuales por cantidad</div>
        </div>

        <form action="index.php" method="get" name="form1" class="g004-filters">
            <input type="hidden" name="doc" value="graphs_004" />
            <h3>Filtros de búsqueda</h3>
            
            <div class="g004-grid">
                <div class="g004-field">
                    <label for="idComercial">Comercial</label>
                    <select name="idComercial" id="idComercial">
                        <option value="">Todos</option>
                        <?php
                            $sql = "SELECT usuario.id, usuario.nombre FROM usuario WHERE tipo = 162";
                            if(isset($forzar_comercial) && $forzar_comercial > 0){
                                $sql.=" AND id = '".$idComercial."'";
                            }
                            $sql.=" ORDER BY nombre asc";
                            $PSN2->query($sql);
                            $numero=$PSN2->num_rows();
                            if($numero > 0) {
                                while($PSN2->next_record()) {
                                    ?><option value="<?=$PSN2->f('id'); ?>" <?php if($idComercial == $PSN2->f('id')){ echo 'selected="selected"'; } ?>><?=$PSN2->f('nombre'); ?></option><?php
                                }
                            }
                        ?>
                    </select>
                </div>

                <div class="g004-field">
                    <label for="interno">Tipo de Cliente</label>
                    <select name="interno" id="interno">
                        <option value="">Todos</option>
                        <option value="0" <?php if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                        <option value="1" <?php if(isset($_GET['interno']) && $_GET['interno'] == 1){ echo 'selected="selected"'; } ?>>Propio</option>
                    </select>
                </div>

                <div class="g004-field">
                    <label for="fechaInicial">Fecha Inicial</label>
                    <input name="fechaInicial" type="date" id="fechaInicial" value="<?=$busquedaFechaIni; ?>" />
                </div>

                <div class="g004-field">
                    <label for="fechaFinal">Fecha Final</label>
                    <input name="fechaFinal" type="date" id="fechaFinal" value="<?=$busquedaFechaFin; ?>" />
                </div>
            </div>

            <div class="g004-actions">
                <button type="submit" class="g004-btn">Aplicar Filtros</button>
            </div>
        </form>

        <div class="g004-result">
            <div class="g004-result-header">
                <h2 class="g004-result-title"><?=$nombreGrafica; ?></h2>
                <div>
                    <div class="g004-result-total">Total de cotizaciones: <span class="g004-result-value"><?=$totalCotizaciones; ?></span></div>
                    <div class="g004-result-total" style="margin-top: 8px;">Valor total: <span class="g004-result-value">$<?=number_format($totalCotizado, 0, ",", "."); ?></span></div>
                </div>
            </div>
            <div id="g004-chart"></div>
            <div class="g004-note">
                <strong>⚠️ Nota:</strong> Esta gráfica toma como referencia la FECHA DE COTIZACIÓN de la cotización básica. Si la misma está en blanco no se verá reflejada en la gráfica.
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="g004-container">
        <div class="g004-empty">
            <strong>⚠️ Sin resultados</strong><br>
            No se encontraron registros para el rango de fechas seleccionado. Por favor, intenta con otro rango.
        </div>
    </div>
    <?php
}
?>
</div>