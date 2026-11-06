<?php
/*******************************************
Sumatorias comercial x
comercial y total comerciales.
Basados en una línea, de
tiempo por trimestres, deben
tener estos descriptores:
+1 cantidad de nuevos
contactos y/o de campaña
enviada.
+2 cantidad de prospectos.
+3 cantidad de cotizaciones
enviadas.
+4 cantidad de aprobadas con

pagos.

ID MENU = 7
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
$idComercial = 0;
$idCliente = 0;
$sqlJoinAdicional = "";
$totalProspectos = 0;
$total_cotizaciones = 0;
$total_cotizaciones_apro = 0;
$total_cotizaciones_rechazo = 0;
$nombreGrafica = "FUNNEL PROSPECTOS";


$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu_graphs ";
$sql.=" WHERE idMenu = 7 
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

$nombreGrafica ="FUNNEL PROSPECTOS";
$sql= "SELECT 
        COUNT(cliente.id) as total_prospectos, usuario.nombre";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
//$sql.=" WHERE cliente.creacionFecha BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
$sql .= $sqlFiltros;
$sql.=" WHERE cliente.estado = 1 GROUP BY cliente.idComercial";

//echo $sql;

$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
        //
		$totalProspectos += $PSN->f('total_prospectos');
        //
	}
}

//  Cotizaciones
$sql= "SELECT 
        COUNT(cotizacion.id) as total_cotizaciones";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" WHERE cotizacion.fechaCotizacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql.=" AND cotizacion.estado IN (62, 63, 64)";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cliente.idComercial";


$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    while($PSN->next_record())
    {
        //
        $total_cotizaciones += $PSN->f('total_cotizaciones');
        //
    }
}

//  Cotizaciones estado 4 y 7
$sql= "SELECT 
        COUNT(cotizacion.id) as total_cotizaciones";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" WHERE cotizacion.fechaCotizacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql.=" AND cotizacion.estado IN (65, 67)";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cliente.idComercial";


$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    while($PSN->next_record())
    {
        //
        $total_cotizaciones_apro += $PSN->f('total_cotizaciones');
        //
    }
}
//  Cotizaciones estado Rechazados
$sql= "SELECT 
        COUNT(cotizacion.id) as total_cotizaciones";
$sql.=" FROM ".$sqlJoinAdicional." cliente ";
$sql.=" LEFT JOIN usuario ON usuario.id = cliente.idComercial ";
$sql.=" LEFT JOIN cotizacion ON cotizacion.idCliente = cliente.id ";
$sql.=" WHERE cotizacion.fechaCotizacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql.=" AND cotizacion.estado = 68";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cliente.idComercial";


$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
    while($PSN->next_record())
    {
        //
        $total_cotizaciones_rechazo += $PSN->f('total_cotizaciones');
        //
    }
}
//

?>
<style>
    .g007-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g007-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
    .g007-hero h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
    .g007-hero-subtitle { opacity: 0.9; margin-top: 8px; font-size: 14px; }
    .g007-filters { background: #f8fafc; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
    .g007-filters h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 600; color: #1e293b; }
    .g007-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .g007-field { display: flex; flex-direction: column; }
    .g007-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
    .g007-field select, .g007-field input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; }
    .g007-field select:focus, .g007-field input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .g007-actions { display: flex; gap: 10px; margin-top: 15px; }
    .g007-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .g007-result { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
    .g007-result-title { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0 0 20px 0; }
    .g007-funnel { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
    .g007-step { color: #fff; border-radius: 10px; padding: 14px 18px; font-size: 15px; font-weight: 600; text-align: center; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12); }
    .g007-step-1 { background: linear-gradient(135deg, #4f46e5, #6366f1); width: 100%; }
    .g007-step-2 { background: linear-gradient(135deg, #2563eb, #3b82f6); width: 88%; margin: 0 auto; }
    .g007-step-3 { background: linear-gradient(135deg, #ea580c, #f97316); width: 76%; margin: 0 auto; }
    .g007-step-4 { background: linear-gradient(135deg, #059669, #10b981); width: 64%; margin: 0 auto; }
    .g007-empty { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; text-align: center; color: #92400e; font-size: 14px; }
    @media (max-width: 900px) { .g007-grid { grid-template-columns: repeat(2, 1fr); } .g007-hero h1 { font-size: 24px; } }
    @media (max-width: 560px) { .g007-grid { grid-template-columns: 1fr; } .g007-hero { padding: 20px; } .g007-hero h1 { font-size: 20px; } .g007-filters, .g007-result { padding: 16px; } }
</style>

<div class="g007-container">
    <div class="g007-hero">
        <h1><?=$nombreGrafica; ?></h1>
        <div class="g007-hero-subtitle">Embudo de conversi&oacute;n comercial</div>
    </div>

    <form action="index.php" method="get" name="form1" class="g007-filters">
        <input type="hidden" name="doc" value="graphs_007" />
        <h3>Filtros de b&uacute;squeda</h3>

        <div class="g007-grid">
            <div class="g007-field">
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

            <div class="g007-field">
                <label for="interno">Tipo de Cliente</label>
                <select name="interno" id="interno">
                    <option value="">Todos</option>
                    <option value="0" <?php if(isset($_GET['interno']) && $_GET['interno'] == "0"){ echo 'selected="selected"'; } ?>>Cliente</option>
                    <option value="1" <?php if(isset($_GET['interno']) && $_GET['interno'] == 1){ echo 'selected="selected"'; } ?>>Propio</option>
                </select>
            </div>

            <div class="g007-field">
                <label for="fechaInicial">Fecha Inicial</label>
                <input name="fechaInicial" type="date" id="fechaInicial" value="<?=$busquedaFechaIni; ?>" />
            </div>

            <div class="g007-field">
                <label for="fechaFinal">Fecha Final</label>
                <input name="fechaFinal" type="date" id="fechaFinal" value="<?=$busquedaFechaFin; ?>" />
            </div>
        </div>

        <div class="g007-actions">
            <button type="submit" class="g007-btn">Aplicar Filtros</button>
        </div>
    </form>

    <?php if($varError == 1){ ?>
        <div class="g007-empty">
            <strong>Sin resultados</strong><br>
            No se ha encontrado ning&uacute;n registro para el rango de fechas seleccionado.
        </div>
    <?php } ?>

    <?php if($varError != 1){ ?>
        <div class="g007-result">
            <h2 class="g007-result-title"><?=$nombreGrafica; ?></h2>
            <ul class="g007-funnel">
                <li class="g007-step g007-step-1">Todos los prospectos: <?=intval($totalProspectos); ?></li>
                <li class="g007-step g007-step-2">Cotizaciones pendientes: <?=intval($total_cotizaciones); ?></li>
                <li class="g007-step g007-step-3">Cotizaciones no aprobadas: <?=intval($total_cotizaciones_rechazo); ?></li>
                <li class="g007-step g007-step-4">Cotizaciones aprobadas: <?=intval($total_cotizaciones_apro); ?></li>
            </ul>
        </div>
    <?php } ?>
</div>