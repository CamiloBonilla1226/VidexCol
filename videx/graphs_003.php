<?php
/*******************************************
En un rango de fechas hacer 
sumatoria del valor total
cotizado a los nuevos
prospectos.

Valor total el día 1 y valor
total el día último.
La diferencia es = valor total
de nuevos prospectos vr línea
de tiempo.

ID MENU = 3
*******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 3 
AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0)
{
    die("NO esta autorizado a ver esta grafica.");
}

/*
*   INICIALIZACIÓN DE VARIABLES
*/
$varError = 0;
$totalCotizado = 0;
$idComercial = 0;
$sqlJoinAdicional = "";
$nombresComercial = array();

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
$nombreGrafica ="COTIZACIONES MENSUAL POR VALOR COTIZADO";
$datos = array();
//
//
$sql= "SELECT 
        COUNT(DISTINCT cliente.id) as total_prospectos, 
		SUM(cotizacion.valorCotizado) as total_cotizado, 
		usuario.id, usuario.nombre, 
		MONTH(cotizacion.fechaCotizacion) as mes, 
		YEAR(cotizacion.fechaCotizacion) as anho";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" WHERE cotizacion.fechaCotizacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY anho, mes";
//
if(isset($_GET["sql"])){
	echo $sql;	
}

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
        $nombresComercial[$PSN->f('anho')][$PSN->f('mes')] = $PSN->f('total_cotizado');
        $totalCotizado += $PSN->f('total_cotizado');
	}
}else{
    $varError = 1;
}
//
$mesInicial = date("n", strtotime($busquedaFechaIni));
$anhoInicial = date("Y", strtotime($busquedaFechaIni));
//
$anhoFinal = date("Y", strtotime($busquedaFechaFin));
$mesFinal = date("n", strtotime($busquedaFechaFin));
//
//
$datosArr[] = '["Meses", "Valor Cotizado", { role: \'style\' }]';
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

            var chart = new google.visualization.ColumnChart(document.getElementById('g003-chart'));
            window.g003Chart = chart;
            chart.draw(data, options);
            
            window.addEventListener('resize', function() {
                if (window.g003Chart) window.g003Chart.draw(data, options);
            });
      }
    </script>

    <style>
        .g003-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .g003-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .g003-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .g003-hero-subtitle {
            opacity: 0.9;
            margin-top: 8px;
            font-size: 14px;
        }

        .g003-filters {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .g003-filters h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .g003-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .g003-field {
            display: flex;
            flex-direction: column;
        }

        .g003-field label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .g003-field select,
        .g003-field input {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            transition: all 0.3s ease;
        }

        .g003-field select:hover,
        .g003-field input:hover {
            border-color: #94a3b8;
        }

        .g003-field select:focus,
        .g003-field input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .g003-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .g003-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .g003-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .g003-btn:active {
            transform: translateY(0);
        }

        .g003-result {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .g003-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .g003-result-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .g003-result-total {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
        }

        .g003-result-total .label {
            font-size: 12px;
            color: #64748b;
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        #g003-chart {
            width: 100%;
            height: 400px;
            margin-top: 20px;
        }

        .g003-empty {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #92400e;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .g003-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .g003-hero h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 560px) {
            .g003-grid {
                grid-template-columns: 1fr;
            }
            .g003-hero {
                padding: 20px;
            }
            .g003-hero h1 {
                font-size: 20px;
            }
            .g003-filters {
                padding: 16px;
            }
            .g003-result {
                padding: 16px;
            }
            #g003-chart {
                height: 300px;
            }
        }
    </style>

    <div class="g003-container">
        <div class="g003-hero">
            <h1><?=$nombreGrafica; ?></h1>
            <div class="g003-hero-subtitle">Análisis de cotizaciones en el rango de fechas seleccionado</div>
        </div>

        <form action="index.php" method="get" name="form1" class="g003-filters">
            <input type="hidden" name="doc" value="graphs_003" />
            
            <h3>Filtros de búsqueda</h3>
            
            <div class="g003-grid">
                <div class="g003-field">
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

                <div class="g003-field">
                    <label for="interno">Tipo de Cliente</label>
                    <select name="interno" id="interno">
                        <option value="">Todos</option>
                        <option value="0" <?php if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                        <option value="1" <?php if(isset($_GET['interno']) && $_GET['interno'] == 1){ echo 'selected="selected"'; } ?>>Propio</option>
                    </select>
                </div>

                <div class="g003-field">
                    <label for="fechaInicial">Fecha Inicial</label>
                    <input name="fechaInicial" type="date" id="fechaInicial" value="<?=$busquedaFechaIni; ?>" />
                </div>

                <div class="g003-field">
                    <label for="fechaFinal">Fecha Final</label>
                    <input name="fechaFinal" type="date" id="fechaFinal" value="<?=$busquedaFechaFin; ?>" />
                </div>
            </div>

            <div class="g003-actions">
                <button type="submit" class="g003-btn">Aplicar Filtros</button>
            </div>
        </form>

        <div class="g003-result">
            <div class="g003-result-header">
                <h2 class="g003-result-title"><?=$nombreGrafica; ?></h2>
                <div class="g003-result-total">
                    <span class="label">Total Cotizado</span>
                    <strong>$<?=number_format($totalCotizado, 0, ",", "."); ?></strong>
                </div>
            </div>
            <div id="g003-chart"></div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="g003-container">
        <div class="g003-empty">
            <strong>⚠️ Sin resultados</strong><br>
            No se encontraron registros para el rango de fechas seleccionado. Por favor, intenta con otro rango.
        </div>
    </div>
    <?php
}
?>