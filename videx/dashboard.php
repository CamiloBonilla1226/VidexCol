<?php
$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

//echo "Perfil:".$_SESSION["perfil"];

if($_SESSION["perfil"] != 1  && $_SESSION["perfil"] != 2)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

$PSN = new DBbase_Sql;

/*
*   INICIALIZACIÓN DE VARIABLES
*/
$varError = 0;
$nombreGraficaDashboard = "DASHBOARD COMERCIAL";
$totalSeguimientos = 0;
$totalCotizacionesEstado = 0;
$totalValorCobrado = 0;
$totalValCotApr = 0;
$totalNoCotApr = 0;
$totalValCotPend = 0;
$totalNoCotPend = 0;
$totalValCotRech = 0;
$totalNoCotRech = 0;
$totalValFac = 0;
$totalNoFacCot = 0;
$totalNoAbonos = 0;
$totalGastos = 0;
$totalNoGastosxTipo = 0;
$totalNoAbonosxTipo = 0;


$busquedaFecha = date("Y");
if(isset($_REQUEST["fecha"]) && soloNumeros($_REQUEST["fecha"]) != ""){
	$busquedaFecha = soloNumeros($_REQUEST["fecha"]);
}

$sqlFiltros = "";
if(soloNumeros($_GET["categoriaPrincipal"]) != "")
{
    echo "<script>console.log('1 filtro aplicado');</script>";
	$sqlFiltros .= " AND cotizacion.categoriaPrincipal = '".soloNumeros($_REQUEST["categoriaPrincipal"])."'";
}

if(soloNumeros($_GET["estado"]) != "")
{
   echo "<script>console.log('2 filtro aplicado');</script>";
	$sqlFiltros .= " AND cotizacion.estado = '".soloNumeros($_REQUEST["estado"])."'";
}

if(soloNumeros($_REQUEST["interno"]) != "" && $_REQUEST["interno"] != "")
{
    echo "<script>console.log('3 filtro aplicado');</script>";
	$sqlFiltros .= " AND cliente.interno = '".soloNumeros($_REQUEST["interno"])."'";
}

if($_SESSION["clientes_filtro"] != ""){
    echo "<script>console.log('4 filtro aplicado');</script>";
	$sqlFiltros .= " AND cliente.id IN (".$_SESSION["clientes_filtro"].")";			
}

/*
*	PIE - Grafica de seguimientos (Tipo de seguimientos, fechas)
*/
$nombreGrafica = $busquedaFecha." Seguimientos por TIPO";
$sql= "SELECT COUNT(seguimiento.id) as seguimientos, categorias.descripcion ";
$sql.=" FROM seguimiento ";
	$sql.=" LEFT JOIN categorias ON categorias.id = seguimiento.tipo ";
		$sql.=" LEFT JOIN cotizacion ON cotizacion.id = seguimiento.idCotizacion ";
		$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" where YEAR(seguimiento.fechaCreacion) = '".$busquedaFecha."'";
//$sql.=" AND cotizacion.id = seguimiento.idCotizacion";
$sql .= $sqlFiltros;
$sql.=" GROUP BY seguimiento.tipo";
$sql.=" ORDER BY seguimientos DESC";
//
$totalSeguimientos = 0;
$datos = array();
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		$datos[] = "['".$PSN->f('descripcion')."', ".$PSN->f('seguimientos')."]";
		$totalSeguimientos += $PSN->f('seguimientos');
	}
}
//

/*
*	PIE - COTIZACIONES POR ESTADO
*/
$nombreGrafica2 = $busquedaFecha." Cotizaciones por ESTADO";
$sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZADO, SUM(cotizacion.valorCobrado) as TOTAL_COBRADO, categorias.descripcion, categorias.id ";
$sql.=" FROM cotizacion ";
$sql.=" LEFT JOIN categorias ON categorias.id = cotizacion.estado ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE YEAR(cotizacion.fechaCotizacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY cotizacion.estado";
$sql.=" ORDER BY TOTAL_COTIZADO DESC";

$totalCotizacionesEstado = 0;
$datos2 = array();
$PSN->query($sql);
$num=$PSN->num_rows();

if($num > 0)
{
	while($PSN->next_record())
	{
		switch($PSN->f("id")){
			case 62: //Borrador
				$color = "gray";
				break;
			case 63: //Lists para enviar
				$color = "yellow";
				break;
			case 64: //Enviada
				$color = "orange";
				break;
			case 65: //Aprobada
				$color = "#009900";
				break;
			case 66: //Pendiente de pago
				$color = "#ff5000";
				break;
			case 67: //Pagada
				$color = "blue";
				break;
			case 68: //No aprobada
				$color = "red";
				break;
			default: //DEFAULT
				$color = "#bbb";
				break;
		}
		$valor = number_format($PSN->f('TOTAL_COTIZADO'), 0, ',' , '.');
		$datos2[] = "['".$PSN->f('descripcion')." [$".$valor."]', ".$PSN->f('cotizaciones').", '".$color."']";
		$totalCotizacionesEstado += $PSN->f('cotizaciones');
		$totalValorCobrado += $PSN->f('TOTAL_COBRADO');
	}
	//
}


/*
*	BARRAS COTIZACIONES APROBADAS V.S. EL RESTO MES A MES
*/
$nombreGrafica3 = $busquedaFecha." Cotizaciones APROBADAS vs PENDIENTES y RECHAZADAS";
$sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
$sql.=" FROM cotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" where cotizacion.estado IN (65, 66, 67) AND  YEAR(cotizacion.fechaCotizacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY MONTH(fechaCotizacion)";
$sql.=" ORDER BY mes asc";

for($i=0;$i<=12;$i++){
	$datos3[$i][0] = 0;
	$datos3[$i][1] = 0;
	$datos3[$i][2] = 0;
	$datos3Xnum[$i][0] = 0;
	$datos3Xnum[$i][1] = 0;
	$datos3Xnum[$i][2] = 0;
}

$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		//Por VALOR de cotizaci�n
		$datos3[$PSN->f('mes')][0] += $PSN->f('TOTAL_COTIZACIONES');
		//Por NUMERO de cotizaciones
		$datos3Xnum[$PSN->f('mes')][0] += $PSN->f('cotizaciones');
		$totalValCotApr += $PSN->f('TOTAL_COTIZACIONES');
		$totalNoCotApr += $PSN->f('cotizaciones');
	}
}

	/*
	*	AHORA OBTENEMOS LAS PENDIENTES
	*/
	$sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
	$sql.=" FROM cotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
	$sql.=" where cotizacion.estado IN (62, 63, 64) AND  YEAR(cotizacion.fechaCotizacion) = '".$busquedaFecha."'";
	$sql .= $sqlFiltros;
	$sql.=" GROUP BY MONTH(fechaCotizacion)";
	$sql.=" ORDER BY mes asc";

	$PSN->query($sql);
	$num=$PSN->num_rows();
	if($num > 0)
	{
		while($PSN->next_record())
		{
			//Por VALOR de cotizaci�n
			$datos3[$PSN->f('mes')][1] += $PSN->f('TOTAL_COTIZACIONES');
			//Por NUMERO de cotizaciones
			$datos3Xnum[$PSN->f('mes')][1] += $PSN->f('cotizaciones');
			$totalValCotPend += $PSN->f('TOTAL_COTIZACIONES');
			$totalNoCotPend += $PSN->f('cotizaciones');
		}
	}
	//
	/*
	*	AHORA OBTENEMOS LAS RECHAZADAS
	*/
	$sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
	$sql.=" FROM cotizacion ";
		$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
	$sql.=" where cotizacion.estado = 68 AND  YEAR(cotizacion.fechaCotizacion) = '".$busquedaFecha."'";
	$sql .= $sqlFiltros;
	$sql.=" GROUP BY MONTH(fechaCotizacion)";
	$sql.=" ORDER BY mes asc";

	$PSN->query($sql);
	$num=$PSN->num_rows();
	if($num > 0)
	{
		while($PSN->next_record())
		{
			//Por VALOR de cotizaci�n
			$datos3[$PSN->f('mes')][2] += $PSN->f('TOTAL_COTIZACIONES');
			//Por NUMERO de cotizaciones
			$datos3Xnum[$PSN->f('mes')][2] += $PSN->f('cotizaciones');
			$totalValCotRech += $PSN->f('TOTAL_COTIZACIONES');
			$totalNoCotRech += $PSN->f('cotizaciones');
		}
	}

	//
	//$datos3imp = $datos3[1];
	$datos4imp = "['Enero', ".$datos3[1][0].", ".$datos3[1][2].", ".$datos3[1][1]."]";
	$datos5imp = "['Enero', ".$datos3Xnum[1][0].", ".$datos3Xnum[1][2].", ".$datos3Xnum[1][1]."]";
	//
	$i = 2;
	while($i <= 12){
		//$datos3imp .= ",".$datos3[$i];
		$datos4imp .= ",['".$mesesNom[$i]."', ".$datos3[$i][0].", ".$datos3[$i][2].", ".$datos3[$i][1]."]";
		//
		$datos5imp .= ",['".$mesesNom[$i]."', ".$datos3Xnum[$i][0].", ".$datos3Xnum[$i][2].", ".$datos3Xnum[$i][1]."]";
		$i++;
	}



/*
*	BARRAS FACTURACI�N MES A MES
*/
$nombreGraficaFac = $busquedaFecha." Facturaci&oacute;n MES a MES";
$sql= "SELECT COUNT(DISTINCT cotizacion.id) as cotizaciones, COUNT(abonos.id) as totalAbonos, SUM(abonos.valor) as TOTAL_RECIBIDO,  MONTH(abonos.fechaCreacion) as mes ";
$sql.=" FROM abonos, cotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE cotizacion.id = abonos.idCotizacion AND YEAR(abonos.fechaCreacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY MONTH(abonos.fechaCreacion)";
$sql.=" ORDER BY mes asc";

for($i=0;$i<=12;$i++){
	$datosFac[$i][0] = 0;
}

$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		//Por VALOR de FACTURACION
		$datosFac[$PSN->f('mes')][0] += $PSN->f('TOTAL_RECIBIDO');
		//Por NUMERO de ABONOS, ETC
		$totalValFac += $PSN->f('TOTAL_RECIBIDO');
		$totalNoFacCot += $PSN->f('cotizaciones');
		$totalNoAbonos += $PSN->f('totalAbonos');
	}
}

	//
	$datosFacimp = "['Enero', ".$datosFac[1][0]."]";
	$i = 2;
	while($i <= 12){
		//$datos3imp .= ",".$datos3[$i];
		$datosFacimp .= ",['".$mesesNom[$i]."', ".$datosFac[$i][0]."]";
		$i++;
	}




/*
*	BARRAS ABONOS VS. GASTOS
*/
$nombreGraficaAbnGas = $busquedaFecha." ABONOS vs GASTOS";
$sql= "SELECT COUNT(DISTINCT cotizacion.id) as cotizaciones, COUNT(abonos.id) as totalAbonos, SUM(abonos.valor) as TOTAL_RECIBIDO,  MONTH(abonos.fechaCreacion) as mes ";
$sql.=" FROM abonos, cotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE cotizacion.id = abonos.idCotizacion AND YEAR(abonos.fechaCreacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY MONTH(abonos.fechaCreacion)";
$sql.=" ORDER BY mes asc";


for($i=0;$i<=12;$i++){
	$datosAbnGas[$i][0] = 0;
	$datosAbnGas[$i][1] = 0;

	$datosAbnGasXnum[$i][0] = 0;
	$datosAbnGasXnum[$i][1] = 0;
}

$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		//Por VALOR de cotizaci�n
		$datosAbnGas[$PSN->f('mes')][0] += $PSN->f('TOTAL_RECIBIDO');
		//Por NUMERO de cotizaciones
		$datosAbnGasXnum[$PSN->f('mes')][0] += $PSN->f('cotizaciones');
	}
}

	/*
	*	AHORA OBTENEMOS LOS GASTOS
	*/
    $sql= "SELECT COUNT(DISTINCT cotizacion.id) as cotizaciones, COUNT(gastos.id) as totalAbonos, SUM(gastos.valor) as TOTAL_GASTADO,  MONTH(gastos.fechaCreacion) as mes ";
    $sql.=" FROM gastos, cotizacion ";
		$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
    $sql.=" WHERE cotizacion.id = gastos.idCotizacion AND YEAR(gastos.fechaCreacion) = '".$busquedaFecha."'";
    $sql .= $sqlFiltros;
    $sql.=" GROUP BY MONTH(gastos.fechaCreacion)";
    $sql.=" ORDER BY mes asc";
    

	$PSN->query($sql);
	$num=$PSN->num_rows();
	if($num > 0)
	{
		while($PSN->next_record())
		{
			//Por VALOR de cotizaci�n
			$datosAbnGas[$PSN->f('mes')][1] += $PSN->f('TOTAL_GASTADO');
			$totalGastos += $PSN->f('TOTAL_GASTADO');
			//Por NUMERO de cotizaciones
			$datosAbnGasXnum[$PSN->f('mes')][1] += $PSN->f('cotizaciones');
		}
	}
	/*
	*	AHORA OBTENEMOS LOS GASTOS
	*/
    $sql= "SELECT COUNT(gastos.id) as totalAbonos, SUM(gastos.valor) as TOTAL_GASTADO,  MONTH(gastos.fechaCreacion) as mes ";
    $sql.=" FROM gastos ";
    $sql .= "WHERE gastos.idCotizacion = 0 AND YEAR(gastos.fechaCreacion) = '".$busquedaFecha."'";
    //$sql .= $sqlFiltros;
    $sql.=" GROUP BY MONTH(gastos.fechaCreacion)";
    $sql.=" ORDER BY mes asc";

	$PSN->query($sql);
	$num=$PSN->num_rows();
	if($num > 0)
	{
		while($PSN->next_record())
		{
			//Por VALOR de cotizaci�n
			$datosAbnGas[$PSN->f('mes')][1] += $PSN->f('TOTAL_GASTADO');
			$totalGastos += $PSN->f('TOTAL_GASTADO');
		}
	}

	//
	//$datos3imp = $datos3[1];
	$datos4AbnGasimp = "['Enero', ".$datosAbnGas[1][0].", ".$datosAbnGas[1][1]."]";
	$datos5AbnGasimp = "['Enero', ".$datosAbnGasXnum[1][0].", ".$datosAbnGasXnum[1][1]."]";
	//
	$i = 2;
	while($i <= 12){
		$datos4AbnGasimp .= ",['".$mesesNom[$i]."', ".$datosAbnGas[$i][0].", ".$datosAbnGas[$i][1]."]";
		$datos5AbnGasimp .= ",['".$mesesNom[$i]."', ".$datosAbnGasXnum[$i][0].", ".$datosAbnGasXnum[$i][1]."]";
		$i++;
	}

/*
*	PIE - GASTOS POR TIPO
*/
$sql= "SELECT COUNT(gastos.id) as totalGastos, SUM(gastos.valor) as TOTAL_GASTOS, categorias.descripcion ";
$sql.=" FROM gastos ";
	$sql.=" LEFT JOIN categorias ON categorias.id = gastos.tipo ";
	$sql.=" LEFT JOIN cotizacion ON cotizacion.id = gastos.idCotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE YEAR(gastos.fechaCreacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY gastos.tipo";
$sql.=" ORDER BY TOTAL_GASTOS DESC";

$totalGastosxTipo = 0;
$datosGastosxTipo = array();
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		$valorTemp = number_format($PSN->f('TOTAL_GASTOS'), 0, ',' , '.');
        $datosGastosxTipo[] = "['".$PSN->f('descripcion')." ($".$valorTemp.")', ".$PSN->f('totalGastos')."]";
	
		$totalNoGastosxTipo += $PSN->f('totalGastos');
		$totalGastosxTipo += $PSN->f('TOTAL_GASTOS');
	}
}


/*
*	PIE - ABONOS POR TIPO
*/
$nombreGraficaAbonosxTipo = $busquedaFecha." Facturaci&oacute;n por METODO DE PAGO";
$sql= "SELECT COUNT(abonos.id) as totalAbonos, SUM(abonos.valor) as TOTAL_RECIBIDO, categorias.descripcion ";
$sql.=" FROM abonos ";
$sql.=" LEFT JOIN categorias ON categorias.id = abonos.tipo ";

	$sql.=" LEFT JOIN cotizacion ON cotizacion.id = abonos.idCotizacion ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE YEAR(abonos.fechaCreacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY abonos.tipo";
$sql.=" ORDER BY TOTAL_RECIBIDO DESC";

$totalAbonosxTipo = 0;
$datosAbonosxTipo = array();
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		$valorTemp = number_format($PSN->f('TOTAL_RECIBIDO'), 0, ',' , '.');
$datosAbonosxTipo[] = "['".$PSN->f('descripcion')." ($".$valorTemp.")', ".$PSN->f('totalAbonos')."]";
		
	
		$totalNoAbonosxTipo += $PSN->f('totalAbonos');
		$totalAbonosxTipo += $PSN->f('TOTAL_RECIBIDO');
	}
	//
}


/*
*	PIE - COTIZACIONES POR CATEGORIA
*/
//$nombreGrafica2 = $busquedaFecha." Cotizaciones por CATEGORIA";
$sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZADO, SUM(cotizacion.valorCobrado) as TOTAL_COBRADO, categorias.descripcion ";
$sql.=" FROM cotizacion ";
$sql.=" LEFT JOIN categorias ON categorias.id = cotizacion.categoriaPrincipal ";
	$sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
$sql.=" WHERE YEAR(cotizacion.fechaCotizacion) = '".$busquedaFecha."'";
$sql .= $sqlFiltros;
$sql.=" GROUP BY categoriaPrincipal";
$sql.=" ORDER BY TOTAL_COTIZADO DESC";

$datosPorCat = array();
$PSN->query($sql);
$num=$PSN->num_rows();
if($num > 0)
{
	while($PSN->next_record())
	{
		$valor = number_format($PSN->f('TOTAL_COTIZADO'), 0, ',' , '.');
		$datosPorCat[] = "['".$PSN->f('descripcion')." [$".$valor."]', ".$PSN->f('cotizaciones')."]";
	}
	//
}


?><script src="https://www.gstatic.com/charts/loader.js"></script><script type="text/javascript">
  google.charts.load("current", {packages:["corechart", "treemap"]});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
		//GRAFICA DE SEGUIMIENTOS POR TIPO //
		  var data = google.visualization.arrayToDataTable([
			['Seguimientos', 'Cantidad'],
			<?=implode(",", $datos); ?>
		]);

		var options = {
			pieHole: 0.4,
		};

		var chart = new google.visualization.PieChart(document.getElementById('gdash-chart-1'));
		chart.draw(data, options);	//PIE

		// GRAFICA DE COTIZACIONES POR ESTADO //  
		var data2 = google.visualization.arrayToDataTable([
			['Estado', 'Valor', { role: 'style' }],
			<?=implode(",", $datos2); ?>
		]);

		var options2 = {
			pieHole: 0.4,
			is3D: true,
			/*slices: {
			0: {offset: 0.2}
			},*/
		};
    
		var chart2 = new google.visualization.ColumnChart(document.getElementById('gdash-chart-2'));
		chart2.draw(data2);
		//var chart2 = new google.visualization.PieChart(document.getElementById('donutchart2'));
		//chart2.draw(data2, options2);

		// BAR GRAFICA DE COTIZACIONES APROBADAS VS PENDIENTES //  
		var data4 = google.visualization.arrayToDataTable([
			['Meses', 'Aprobadas','Rechazadas', 'Pendientes'],
			<?=$datos4imp; ?>
		]);

		var options4 = {
			isStacked: true,
			legend: {position: 'top', maxLines: 2, alignment: 'center'},
             vAxis: { textPosition: 'none'},
			colors: ['green', 'red', 'orange']
		};

/*		var options = {
		  width: 400,
		  height: 240,
		  title: 'Toppings I Like On My Pizza',
		};	  */
	  
		var chart5 = new google.visualization.ColumnChart(document.getElementById('bar3'));
		chart5.draw(data4, options4);

	
	  
	  
		// BAR GRAFICA DE COTIZACIONES APROBADAS VS PENDIENTES x CANTIDAD //  
		var data6 = google.visualization.arrayToDataTable([
			['Meses', 'Aprobadas','Rechazadas', 'Pendientes'],
			<?=$datos5imp; ?>
		]);

		var options6 = {
			legend: {position: 'top', maxLines: 2, alignment: 'center'},
			colors: ['green', 'red', 'orange'],
             vAxis: { textPosition: 'none'}
		};

		var chart6 = new google.visualization.ColumnChart(document.getElementById('bar4'));
		chart6.draw(data6, options6);


	  
		// GRAFICA DE FACUTRACION MES A MES //  
		var data7 = google.visualization.arrayToDataTable([
			['Meses', 'Abonos'],
			<?=$datosFacimp; ?>
		]);
	  
		var options7 = {
			legend: {position: 'top', maxLines: 2, alignment: 'center'},
             vAxis: { textPosition: 'none'}
		};

		var chart7 = new google.visualization.ColumnChart(document.getElementById('bar5'));
		chart7.draw(data7, options7);

		// GRAFICA DE ABONOS POR TIPO //  
		var data10 = google.visualization.arrayToDataTable([
			['Tipo', 'Valor'],
			<?=implode(",", $datosAbonosxTipo); ?>
		]);

		var options10 = {
			pieHole: 0.4,
			is3D: true,
			/*slices: {
			0: {offset: 0.2}
			},*/
		};
	  
		var chart10 = new google.visualization.PieChart(document.getElementById('gdash-chart-10'));
		chart10.draw(data10, options10);

		// GRAFICA DE ABONADO VS PENDIENTE //  
		var data8 = google.visualization.arrayToDataTable([
			['Estado', 'Valor'],
			['Pendiente', <?=($totalValorCobrado-$totalAbonosxTipo); ?>],['Abonado', <?=$totalAbonosxTipo; ?>]
		]);

		var options8 = {
			pieHole: 0.4,
			is3D: true,
			colors: ['orange', 'green']
			/*slices: {
			0: {offset: 0.2}
			},*/
		};
	  
		var chart8 = new google.visualization.PieChart(document.getElementById('gdash-chart-8'));
		chart8.draw(data8, options8);

		// GRAFICA DE COTIZACIONES POR ESTADO //  
		var data9 = google.visualization.arrayToDataTable([
			['Categoria', 'Valor'],
			<?=implode(",", $datosPorCat); ?>
		]);

		var options9 = {
			pieHole: 0.4,
			is3D: true,
			/*slices: {
			0: {offset: 0.2}
			},*/
		};

		var chart9 = new google.visualization.PieChart(document.getElementById('gdash-chart-9'));
		chart9.draw(data9, options9);	  

		// BAR GRAFICA DE ABONOS VS GASTOS //  
		var data4AbnGas = google.visualization.arrayToDataTable([
			['Meses', 'Abonos','Gastos'],
			<?=$datos4AbnGasimp; ?>
		]);

		var options4AbnGas = {
			legend: {position: 'top', maxLines: 2, alignment: 'center'},
             vAxis: { textPosition: 'none'},
			colors: ['green', 'red']
		};

		var chart5AbnGas = new google.visualization.ColumnChart(document.getElementById('bar3AbnGas'));
		chart5AbnGas.draw(data4AbnGas, options4AbnGas);

		// GRAFICA DE GASTOS POR TIPO //  
		var data10Gas = google.visualization.arrayToDataTable([
			['Tipo', 'Valor'],
			<?=implode(",", $datosGastosxTipo); ?>
		]);

		var options10Gas = {
    		pieHole: 0.4,
            chartArea: {  width: "100%" },
			is3D: true
		};
	  
		var chart10Gas = new google.visualization.PieChart(document.getElementById('gdash-chart-10-gas'));
		chart10Gas.draw(data10Gas, options10Gas);

	  
  }
</script>

<style>
	.gdash-shell { padding: 20px; max-width: 1500px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
	.gdash-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px 24px; border-radius: 12px; margin-bottom: 24px; }
	.gdash-hero h1 { margin: 0; font-size: 30px; }
	.gdash-filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 22px; }
	.gdash-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
	.gdash-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
	.gdash-row { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 16px; }
	#gdash-chart-1, #gdash-chart-2, #gdash-chart-8, #gdash-chart-9, #gdash-chart-10, #gdash-chart-10-gas, #bar3, #bar4, #bar5, #bar3AbnGas { width: 100%; min-height: 340px; }
	@media (max-width: 900px) { .gdash-grid { grid-template-columns: repeat(2, 1fr); } }
	@media (max-width: 560px) { .gdash-grid { grid-template-columns: 1fr; } .gdash-hero h1 { font-size: 22px; } }
</style>

<div class="gdash-shell">
	<div class="gdash-hero">
		<h1><?=$nombreGraficaDashboard; ?></h1>
	</div>

<div class="container gdash-filters">
	<div class="row">
		<h2 class="alert alert-info text-center">.GRAFICOS.</h2>
	</div>

<form action="index.php" method="get" name="form1" class="form">
	<input type="hidden" name="doc" value="dashboard" />
<!-- GRAFICA DE COTIZACIONES MES A MES APROBADAS VS OTRAS X VALOR COTIZADO //-->
	<div class="form-group">
		<label class="form-label" for="fecha">Fecha: </label>
		
			<select name="fecha" class="form-control" onchange="form1.submit()">
			<?
				for($i=2015;$i<=date("Y");$i++)
				{
					?><option value="<?=$i; ?>" <?
					if($busquedaFecha == $i){
						?>selected="selected"<?
					}
					?>><?=$i; ?></option><?
				}
			?></select>
		</div>
		
		<label class="form-label" for="categoriaPrincipal">Categor&iacute;a: </label>
		
			<select name="categoriaPrincipal" class="form-control" onchange="form1.submit()">
			<option value="">Todos</option>
				<?
				$PSNTEMP = new DBbase_Sql;
				$sql= "SELECT categorias.* ";
					$sql.=" FROM categorias ";
					$sql.=" WHERE idSec = 18 ";
				$sql.=" ORDER BY descripcion asc ";

				$PSNTEMP->query($sql);
				$num=$PSNTEMP->num_rows();
				if($num > 0)
				{
					while($PSNTEMP->next_record())
					{
						?><option value="<?=$PSNTEMP->f('id'); ?>" <?
						if(soloNumeros($_GET["categoriaPrincipal"]) == $PSNTEMP->f('id'))
						{
							?>selected="selected"<? 
						}
						?>><?=$PSNTEMP->f('descripcion'); ?></option><?
					}
				}
				?>
			</select>
	</div>
	<div class="form-group">
		<label class="form-label"  for="estado">Estado: </label>
		
			<select name="estado" class="form-control" onchange="form1.submit()">
                <option value="">Todos</option>
				<?
				$PSNTEMP = new DBbase_Sql;
				$sql= "SELECT categorias.* ";
					$sql.=" FROM categorias ";
					$sql.=" WHERE idSec = 16 ";
				$sql.=" ORDER BY descripcion asc ";
				
				$PSNTEMP->query($sql);
				$num=$PSNTEMP->num_rows();
				if($num > 0)
				{
					while($PSNTEMP->next_record())
					{
						?><option value="<?=$PSNTEMP->f('id'); ?>" <?
						if(soloNumeros($_GET["estado"]) == $PSNTEMP->f('id'))
						{
							?>selected="selected"<? 
						}
						?>><?=$PSNTEMP->f('descripcion'); ?></option><?
					}
				}
				?>
                </select>
		</div>
		
		<label class="form-label" for="contactoNombre">Cliente &oacute; propio</label>
		<select name="interno" class="form-control" onchange="this.form.submit()">
		<option value="">Todos</option>
		<option value="0" <? if($_GET['interno'] == "0"){ echo 'selected="selected="'; } ?>>Cliente</option>
		<option value="1" <? if($_GET['interno'] == 1){ echo 'selected="selected="'; } ?>>Propio</option>
		</select>
	</div>

</form>
</div>

<div class="rowchart gdash-row">
<!-- GRAFICA DE COTIZACIONES MES A MES APROBADAS VS OTRAS X VALOR COTIZADO //-->
  <div class="col-md-12 text-center">
    <h2><?=$nombreGrafica3; ?></h2>
	  <p><strong>Gr&aacute;fica generada por el VALOR COTIZADO</strong><br />
	  Aprobadas <?=$totalNoCotApr; ?> cotizaciones por un valor de $ <?=number_format($totalValCotApr,0,",","."); ?><br />	  
	  Rechazadas <?=$totalNoCotRech; ?> cotizaciones por un valor de $ <?=number_format($totalValCotRech,0,",","."); ?><br />
	  Pendientes <?=$totalNoCotPend; ?> cotizaciones por un valor de $ <?=number_format($totalValCotPend,0,",","."); ?></p>
  </div> 
  <div class="col-md-12">
  	<div id="bar3"  class="chart"></div>
  </div>

<!-- GRAFICA DE COTIZACIONES MES A MES APROBADAS VS OTRAS X CANTIDAD //-->
  <div class="col-md-12 text-center">
    <h2><?=$nombreGrafica3; ?></h2>
	  <p><strong>Gr&aacute;fica generada por CANTIDAD de cotizaciones</strong><br />
	  Aprobadas <?=$totalNoCotApr; ?> cotizaciones por un valor de $ <?=number_format($totalValCotApr,0,",","."); ?><br />	  
	  Rechazadas <?=$totalNoCotRech; ?> cotizaciones por un valor de $ <?=number_format($totalValCotRech,0,",","."); ?><br />
	  Pendientes <?=$totalNoCotPend; ?> cotizaciones por un valor de $ <?=number_format($totalValCotPend,0,",","."); ?></p>
  </div> 
  <div class="col-md-12">
  	<div id="bar4"  class="chart"></div>
  </div>

  <!-- GRAFICA DE COTIZACIONES POR CATEGORIA //-->
  <div class="col-md-12 text-center">
    <h2>Cotizaciones por categor&iacute;a</h2>
	 <p>Total cotizaciones: <?=$totalCotizacionesEstado; ?></p>
	<div id="gdash-chart-9" class="chart"></div>
  </div>

<!-- GRAFICA DE SEGUIMIENTOS POR TIPO //-->
  <div class="col-md-6 text-center">
    <h2><?=$nombreGrafica; ?></h2>
	 <p>Total seguimientos: <?=number_format($totalSeguimientos,0,".",","); ?></p>
	<div id="gdash-chart-1" class="chart"></div>
  </div>
  
  <!-- GRAFICA DE COTIZACIONES POR ESTADO //-->
  <div class="col-md-6 text-center">
    <h2><?=$nombreGrafica2; ?></h2>
	  <p>Total cotizaciones: <?=$totalCotizacionesEstado; ?></p>
	<div id="gdash-chart-2" class="chart"></div>
  </div>
</div>

 <!-- GRAFICA DE FACTURACION MES A MES //-->
  <div class="col-md-12 text-center">
    <h2><?=$nombreGraficaFac; ?></h2>
	  <p>Se han recibido <?=$totalNoAbonos; ?> abonos de <?=$totalNoFacCot; ?> cotizaciones por un valor de $<?=number_format($totalValFac,0,",","."); ?></p>
  </div> 
  <div class="col-md-12">
  	<div id="bar5"  class="chart"></div>
  </div>

  <!-- GRAFICA DE ABONOS POR TIPO //-->
	<div class="col-md-6 text-center gdash-card">
    <h2>Abonos vs valor pendiente</h2>
	 <p>Se han COBRADO $<?=number_format($totalValorCobrado,0,".",","); ?> de lo cual se han recibido en abonos $<?=number_format($totalAbonosxTipo,0,".",","); ?> queda pendiente por recibir $<?
		 $pendienteRecibido = $totalValorCobrado-$totalAbonosxTipo;
		 echo number_format($pendienteRecibido,0,".",","); ?> </p>
	<div id="gdash-chart-8" class="chart"></div>
  </div>
  
  
  <!-- GRAFICA DE ABONOS POR TIPO //-->
	<div class="col-md-6 text-center gdash-card">
    <h2>Abonos por TIPO</h2>
	 <p>Se han COBRADO $<?=number_format($totalValorCobrado,0,".",","); ?> de lo cual se han recibido en abonos $<?=number_format($totalAbonosxTipo,0,".",","); ?> queda pendiente por recibir $<?
		 $pendienteRecibido = $totalValorCobrado-$totalAbonosxTipo;
		 echo number_format($pendienteRecibido,0,".",","); ?> </p>
	<div id="gdash-chart-10" class="chart"></div>
  </div>
  
<div class="rowchart gdash-row">
<!-- GRAFICA DE ABONOS VS GASTOS //-->
  <div class="col-md-12 text-center">
    <h2><?=$nombreGraficaAbnGas; ?></h2>
	  <p><strong>Gr&aacute;fica generada por valores de ABONOS vs GASTOS mes a mes</strong><br />
	  <strong>Total abonos:</strong> $<?=number_format($totalAbonosxTipo,0,".",","); ?><br />	  
	  <strong>Total gastos:</strong> $<?=number_format($totalGastos,0,".",","); ?><br />
	  <strong>Abonos - Gastos:</strong> $<?=number_format($totalAbonosxTipo-$totalGastos,0,".",","); ?>
  </div> 
  <div class="col-md-12">
  	<div id="bar3AbnGas"  class="chart"></div>
  </div>
</div>

<div class="rowchart gdash-row">
  <!-- GRAFICA DE ABONOS POR TIPO //-->
  <div class="col-md-12 text-center">
    <h2><?=$busquedaFecha; ?> Gastos por TIPO</h2>
	 <p>Se han GASTADO $<?=number_format($totalGastos,0,".",","); ?></p>
	<div id="gdash-chart-10-gas" class="chart"></div>
  </div>
</div>

</div>
