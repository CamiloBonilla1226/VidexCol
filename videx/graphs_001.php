<?php
/*******************************************
En un rango de fechas:
Cuantos prospecto hay desde el día 1 y cuantos el día último.
La diferencia es = nuevos prospectos, 
con TODOS los COMERCIALES.

ID MENU = 1
*******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

$varError = 0;
$totalProspectos = 0;
$sqlJoinAdicional = "";
$idComercial = isset($_REQUEST["idComercial"]) ? soloNumeros($_REQUEST["idComercial"]) : "";


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 1 
AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0)
{
    die("NO esta autorizado a ver esta grafica.");
}

/*
*   FILTROS DE FECHAS.
*/
$busquedaFechaIni = date("Y-m-d", strtotime("-6 month"));
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
$nombreGrafica ="NUEVOS PROSPECTOS";
$datos = array();
//
//
$sql= "SELECT 
        COUNT(cliente.id) as total_prospectos, usuario.nombre";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" WHERE cliente.creacionFecha BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cliente.idComercial";
//
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
        //
		$datos[] = "['".$PSN->f('nombre')."', ".$PSN->f('total_prospectos')."]";
		$totalProspectos += $PSN->f('total_prospectos');
        //
	}
}else{
    $varError = 1;
}

if($varError != 1){
    ?><script type="text/javascript">
      google.charts.load("current", {packages:["corechart", "treemap"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
            //GRAFICA DE PIE DE NUEVOS PROSPECTOS- //['Clase', 'Cantidad', { role: 'style' }], //
              var data = google.visualization.arrayToDataTable([
                ['Clase', 'Cantidad'], 
                <?=implode(",", $datos); ?>
            ]);

            var options = {
                pieHole: 0.4,
                colors: ['#0ea5a4', '#0f766e', '#f59e0b', '#ef4444', '#6366f1', '#06b6d4', '#84cc16'],
                pieSliceTextStyle: { color: '#ffffff', fontSize: 12 },
                legend: { position: 'right', textStyle: { color: '#0f172a', fontSize: 13 } },
                chartArea: { left: 20, top: 20, width: '92%', height: '88%' },
                backgroundColor: 'transparent'
            };


            var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
            chart.draw(data, options);	//PIE
      }

      window.addEventListener('resize', function () {
        drawChart();
      });
    </script><?
}
//
?>
<style>
.g001-page {
    --g001-bg: #f1f8f8;
    --g001-card: #ffffff;
    --g001-line: #c8e2e0;
    --g001-text: #0f172a;
    --g001-soft: #475569;
    --g001-accent: #0f766e;
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: radial-gradient(circle at 10% 8%, rgba(15,118,110,.18), rgba(15,118,110,0) 40%), var(--g001-bg);
    border-radius: 18px;
    padding: 18px;
}

.g001-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.g001-head h2 {
    margin: 0;
    color: var(--g001-text);
    font-weight: 800;
    font-size: 24px;
    letter-spacing: -.02em;
}

.g001-head p {
    margin: 4px 0 0;
    color: var(--g001-soft);
    font-size: 13px;
}

.g001-card {
    background: var(--g001-card);
    border: 1px solid var(--g001-line);
    border-radius: 16px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
    padding: 16px;
}

.g001-filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.g001-field label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.g001-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}

.g001-btn {
    background: linear-gradient(180deg, #14b8a6, #0f766e);
    border: 0;
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: 10px 16px;
}

.g001-chart-card {
    margin-top: 14px;
}

.g001-chart-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.g001-chart-head h3 {
    margin: 0;
    color: #0f172a;
    font-size: 19px;
    font-weight: 800;
}

.g001-total {
    background: #ecfeff;
    border: 1px solid #bae6fd;
    color: #0c4a6e;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
}

#donutchart {
    width: 100%;
    min-height: 420px;
}

.g001-empty {
    margin-top: 14px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #9a3412;
    padding: 12px 14px;
    border-radius: 12px;
    font-weight: 600;
}

@media (max-width: 900px) {
    .g001-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .g001-page {
        padding: 12px;
    }

    .g001-filters {
        grid-template-columns: 1fr;
    }

    #donutchart {
        min-height: 320px;
    }
}
</style>

<div class="container">
<section class="g001-page">
    <div class="g001-head">
        <div>
            <h2>Nuevos prospectos por comercial</h2>
            <p>Analiza el crecimiento de prospectos en el rango de fechas seleccionado.</p>
        </div>
    </div>

    <form action="index.php" method="get" name="form1" class="g001-card">
        <input type="hidden" name="doc" value="graphs_001" />

        <div class="g001-filters">
            <div class="g001-field">
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

            <div class="g001-field">
                <label for="interno">Cliente o propio</label>
                <select name="interno" id="interno" class="form-control">
                    <option value="">Todos</option>
                    <option value="0" <? if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                    <option value="1" <? if(isset($_GET['interno']) && $_GET['interno'] == "1"){ echo 'selected="selected"'; } ?>>Propio</option>
                </select>
            </div>

            <div class="g001-field">
                <label for="fechaInicial">Fecha inicial</label>
                <input name="fechaInicial" type="date" id="fechaInicial" maxlength="250" value="<?=$busquedaFechaIni; ?>" class="form-control" />
            </div>

            <div class="g001-field">
                <label for="fechaFinal">Fecha final</label>
                <input name="fechaFinal" type="date" id="fechaFinal" maxlength="250" value="<?=$busquedaFechaFin; ?>" class="form-control" />
            </div>
        </div>

        <div class="g001-actions">
            <button type="submit" class="g001-btn">Aplicar filtros</button>
        </div>
    </form>

    <!--<div class="form-group">
        <label class="form-label" for="idCliente"><strong>Cliente:</strong></label>
        <?
            ?><select name="idCliente" onchange="this.form.submit()" id="idCliente" class="form-control"><?
            if(!isset($_REQUEST["ctrl_cli"])){
                ?><option value="">Todos</option><?
            }
            /*
            *	TRAEMOS LOS CLIENTES
            */
            $sql = "SELECT usuario.id, usuario.nombre ";
            $sql.=" FROM usuario ";
            $sql.=" WHERE tipo = 3";
            if(isset($forzar_cliente) && $forzar_cliente > 0){
                $sql.=" AND id = '".$idCliente."'";
            }
            $sql.=" ORDER BY nombre asc";
            //
            $PSN2->query($sql);
            $numero=$PSN2->num_rows();
            if($numero > 0)
            {
                while($PSN2->next_record())
                {
                    ?><option value="<?=$PSN2->f('id'); ?>" <?
                    if($idCliente == $PSN2->f('id')){
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN2->f('nombre'); ?></option><?
                }
            }
            ?></select>        
    </div>//-->

    <div class="row text-center">
        <center><input type="submit" value="Buscar" class="btn btn-success" style="float:center" /></center></div>
    </div>
</form>

<?
/*
*    
*/
if($varError == 1){
    ?><div class="g001-empty">No se ha encontrado ningun registro para el rango de fechas seleccionado.</div><?
}


if($varError != 1){
    ?>
        <div class="g001-card g001-chart-card">
                <div class="g001-chart-head">
                        <h3><?=$nombreGrafica; ?></h3>
                        <span class="g001-total">Total prospectos: <?=$totalProspectos; ?></span>
                </div>
                <div id="donutchart" class="chart"></div>
    </div>
    <?
}
?>
</section>
</div>