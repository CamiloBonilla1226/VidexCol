<?php
    $nombreGrafica ="COSTOS DIRECTOS";
    $varError = 0;
    $varError1 = 0;
    $varError2 = 0;


    $busquedaFechaIni = date("Y-m-01");
    if(isset($_REQUEST["fechaInicial"]) && soloNumeros($_REQUEST["fechaInicial"]) != ""){
        $busquedaFechaIni = eliminarInvalidos($_REQUEST["fechaInicial"]);
    }
    $busquedaFechaFin = date("Y-m-d");
    if(isset($_REQUEST["fechaFinal"]) && soloNumeros($_REQUEST["fechaFinal"]) != ""){
	    $busquedaFechaFin = eliminarInvalidos($_REQUEST["fechaFinal"]);
    }
    if(isset($_REQUEST["idCliente"])){
        $idCliente = $_REQUEST["idCliente"];
    }else{
        $idCliente = 0;
    }

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
    $datosArr[] = '["Cliente", "Costos"]';
    
    $sql= "SELECT personal.nombres as Nombres, SUM(valor) as Costos FROM gastosauditoria";
    $sql.=" inner join cliente on gastosauditoria.idCliente = cliente.id";
    $sql.=" inner join personal on gastosauditoria.idPersona = personal.cc";
    $sql.=" where gastosauditoria.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql.=" and gastosauditoria.idCliente = " .$idCliente;
    $sql.=" group by personal.nombres ";

    $PSN->query($sql);
    $num=$PSN->num_rows();
    if($num > 0){
        $contador = 0;
	    while($PSN->next_record()){
            $datosArr[] = "['".$PSN->f('Nombres')."', ".intval($PSN->f('Costos'))."]";
            $contador++;
	    }
    }else{
        $varError1 = 1;
    }

    //Graph 2
    $PSN2 = new DBbase_Sql;
    $datosArr2[] = '["Fecha","Abono", "Gasto"]';
    $datosArr3[] = '["Fecha", "Gasto"]';
    $sql2= "select DATE_FORMAT(A.Fecha, '%b %Y') as Fecha,   IFNULL(sum(C.Gasto),0) as Gasto, IFNULL(SUM(B.Abono),0) As Abono"; 
    $sql2.=" from (";
    $sql2.="        Select fechaCreacion AS Fecha";
    $sql2.="        from cliente";
    $sql2.="        inner join abonos on cliente.id = abonos.idCliente";
    $sql2.="        where cliente.id = ".$idCliente;
    $sql2.=" AND abonos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.=" group by fechaCreacion";
    $sql2.="        union ";
    $sql2.="        Select fechaCreacion AS Fecha";
    $sql2.="        from cliente";
    $sql2.="        inner join gastos on cliente.id = gastos.idCliente";
    $sql2.="        where cliente.id = ".$idCliente;
    $sql2.=" AND gastos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.=" group by fechaCreacion";
    $sql2.="        union";
    $sql2.="        Select fechaCreacion AS Fecha";
    $sql2.="        from cliente";
    $sql2.="        inner join gastosauditoria on cliente.id = gastosauditoria.idCliente";
    $sql2.="        where cliente.id = ".$idCliente;
    $sql2.=" AND gastosauditoria.fechaCreacion BETWEEN'".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.=" group by fechaCreacion";
    $sql2.=" ) A";
    $sql2.=" left join ( ";
    $sql2.="            Select fechaCreacion AS Fecha, sum(abonos.Valor) as Abono";
    $sql2.="            from cliente";
    $sql2.="            inner join abonos on cliente.id = abonos.idCliente";
    $sql2.="            where cliente.id = ".$idCliente;
    $sql2.="    AND abonos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.=" group by fechaCreacion";
    $sql2.=" ) B on A.Fecha = B.Fecha";
    $sql2.=" left join (";
    $sql2.="            Select fechaCreacion AS Fecha, sum(gastos.Valor) as Gasto";
    $sql2.="            from cliente";
    $sql2.="            inner join gastos on cliente.id = gastos.idCliente";
    $sql2.="            where cliente.id = ".$idCliente;
    $sql2.="    AND gastos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.="    group by fechaCreacion";
    $sql2.="            union";
    $sql2.="            Select fechaCreacion AS Fecha, sum(gastosauditoria.Valor) as Gasto";
    $sql2.="            from cliente";
    $sql2.="            inner join gastosauditoria on cliente.id = gastosauditoria.idCliente";
    $sql2.="            where cliente.id = ".$idCliente;
    $sql2.="    AND gastosauditoria.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
    $sql2.="    group by fechaCreacion";             
    $sql2.=" ) C on A.Fecha = C.Fecha";
    $sql2.=" group by DATE_FORMAT(A.Fecha, '%b %Y')";
    $sql2.=" order by A.Fecha";

	//echo "<font color='white'>".$sql2."</font>";

    $TotalGasto = 0;
    $TotalAbono = 0;
    $PSN2->query($sql2);
    $num=$PSN2->num_rows();
    if($num > 0){
        $contador = 0;
	    while($PSN2->next_record()){
            $datosArr2[] = "['".$PSN2->f('Fecha')."', ".intval($PSN2->f('Abono')).", ".intval($PSN2->f('Gasto'))."]";
            $datosArr3[] = "['".$PSN2->f('Fecha')."', ".intval($PSN2->f('Gasto'))."]";
            $TotalGasto += intval($PSN2->f('Gasto'));
            $TotalAbono += intval($PSN2->f('Abono'));
	    }
    }else{
        $varError2 = 1;
    }

    //

    if($varError2 != 1){
        $Utilidad = 0;
        $Perdida = 0;
        $DiffU = 0;
        $DiffP = 0;
        $TA = number_format($TotalAbono, 0);
        $TG = number_format($TotalGasto, 0);
        $total = $TotalAbono - $TotalGasto;
        $Totales = 'Total Abono: $'.$TA.'  -  Total Gasto: $'.$TG;
        if ($total > 0){
            $Utilidad =  ($total * 100 ) / $TotalAbono;
            $label = "Porcentaje de Utilidad %". round($Utilidad, 2).' ';
            $DiffU = $total;
            $colores = 'blue';
        }else{
            $Perdida = ($total * (-1) * 100 ) / $TotalAbono;
            $label = "Porcentaje de Perdida %". round($Perdida, 2).' ';
            $DiffP = $total*(-1);
            $colores = 'red';
        }
    }

    if($varError1 != 1 || $varError2 != 1){
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load("current", {packages:["corechart", "bar"]});
    google.charts.setOnLoadCallback(drawAllCharts);
    
    function drawAllCharts() {
        <?php if($varError1 != 1) { ?>
        drawChart();
        <?php } ?>
        <?php if($varError2 != 1) { ?>
        drawChart2();
        drawChart3();
        drawChart4();
        <?php } ?>
    }
    
    <?php if($varError1 != 1) { ?>
    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            <?=implode(",", $datosArr); ?>
        ]);

        var options = {         
            pieHole: 0.4,
        };

        var chart = new google.visualization.PieChart(document.getElementById('gcd-chart-a'));
        chart.draw(data, options);
    }
    <?php } ?>

    <?php if($varError2 != 1) { ?>
    function drawChart2() {
        var data2= google.visualization.arrayToDataTable([
            <?=implode(",", $datosArr2); ?>
        ]);

        var options2 = {
            colors: ['blue','green'],
            title:'<?=$Totales; ?>', 
            titleTextStyle:{
                color: '#3498DB',
            }
          
        };

        var chart2 = new google.charts.Bar(document.getElementById('gcd-chart-b'));
        chart2.draw(data2, google.charts.Bar.convertOptions(options2));
    }

    function drawChart3() {
        var data3 = google.visualization.arrayToDataTable([
            <?=implode(",", $datosArr3); ?>
        ]);

        var options3 = {
            title: '',
            legend: { position: 'bottom' }
        };

        var chart3 = new google.visualization.LineChart(document.getElementById('gcd-chart-c'));
        chart3.draw(data3, options3);
    }

    function drawChart4() {
        var data4 = google.visualization.arrayToDataTable([
            ['Tipo', '',{ role: 'style' },'<?=$label;?>', { role: 'style' }],
            ['Abono', <?=$TotalAbono; ?>,'green',<?=$DiffP;?>, 'red'] ,
            ['Gasto', <?=$TotalGasto; ?>,'orange',<?=$DiffU;?>,'blue'],
        ]);

        var options4 = {
            legend: { position: 'none' },
            title: '<?=$label;?>',
            titleTextStyle:{
                color: '<?=$colores; ?>',
            },
            bar: { groupWidth: '20%' },
            isStacked: true
        };
       
        var chart4 = new google.visualization.ColumnChart(document.getElementById('gcd-chart-d'));
        chart4.draw(data4, options4);
    }
    <?php } ?>
</script>
<?php
    }
?>

<style>
    .gcd-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .gcd-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px 24px; border-radius: 12px; margin-bottom: 24px; }
    .gcd-hero h1 { margin: 0; font-size: 28px; }
    .gcd-filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; }
    .gcd-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; align-items: end; }
    .gcd-field { display: flex; flex-direction: column; }
    .gcd-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; }
    .gcd-field select, .gcd-field input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
    .gcd-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .gcd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 18px; }
    #gcd-chart-a, #gcd-chart-b, #gcd-chart-c, #gcd-chart-d { width: 100%; min-height: 360px; }
    @media (max-width: 900px) { .gcd-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .gcd-grid { grid-template-columns: 1fr; } }
</style>

<div class="gcd-container">
    <div class="gcd-hero">
        <h1><?=$nombreGrafica; ?></h1>
    </div>
    <form action="index.php" method="get" name="form1" class="form">
        <input type="hidden" name="doc" value="graphs_costos_directos" />
        <div class="gcd-filters">
            <div class="gcd-grid">
                <div class="gcd-field">
                    <label class="form-label" for="idCliente">Prospecto</label>
            
           
                <select name="idCliente" id="idCliente"  class="form-control">
                    <option value="0">Seleccione un prospecto...</option>
                <?
                    $PSNTEMP = new DBbase_Sql;
                    if ($_SESSION["superusuario"] != 1){
                        $sql= " SELECT cliente.id as id, cliente.nombre  as nombre";
                        $sql.=" FROM cliente ";
                        $sql.=" inner join usuarios_clientes on cliente.id = usuarios_clientes.idCliente";
                        $sql.=" inner join usuario on usuario.id = usuarios_clientes.idUsuario";
                        $sql.=" where estado != 0 AND usuario.id = ".$_SESSION["id"];
                        $sql.=" ORDER BY cliente.nombre asc";
                    }else{
                        $sql= " SELECT cliente.id as id, cliente.nombre  as nombre FROM cliente where estado != 0 ORDER BY cliente.nombre asc";
                    }
                    

                    $PSNTEMP->query($sql);
                    $num=$PSNTEMP->num_rows();
                    if($num > 0)
                    {
                        while($PSNTEMP->next_record())
                        {
                            ?>
                            <option value="<?=$PSNTEMP->f('id'); ?>" 
                            <?
                            if(soloNumeros($_REQUEST["idCliente"]) == $PSNTEMP->f('id'))
                            {
                                ?>selected="selected"<? 
                            }
                            ?>><?=$PSNTEMP->f('nombre'); ?></option><?
                        }
                    }
                ?>
                </select>
                </div>
                <div class="gcd-field">
                    <label class="form-label" for="fechaInicial"><strong>Fecha Inicial:</strong></label>
            
                <input name="fechaInicial" type="date" id="fechaInicial" maxlength="250" value="<?=$busquedaFechaIni; ?>" class="form-control" />
                </div>
                <div class="gcd-field">
                    <label class="form-label" for="fechaFinal"><strong>Fecha Final:</strong></label>
            
                <input name="fechaFinal" type="date" id="fechaFinal" maxlength="250" value="<?=$busquedaFechaFin; ?>" class="form-control" />
                </div>
                <div class="gcd-field">
                    <input type="submit" value="Buscar" class="gcd-btn" />
                </div>
            </div>
        </div>
    </form>
    <?
        if($varError1 == 1 && $varError2 == 1 ){
    ?>
    <div class="container">
        <div class="row">
            <h5 class="alert alert-warning text-center">No se ha encontrado ningun registro para el rango de fechas seleccionado (RECUERDE QUE DEBE SIEMPRE SELECCIONAR UN PROSPECTO).</h5>
        </div>
    </div>
    <?  
        } 
        if($varError1 != 1){
    ?>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 text-center">
            <h2><?=$nombreGrafica; ?></h2>
            <div class="gcd-card"><div id="gcd-chart-a" class="chart"></div></div>
        </div>
    </div>
    <?
        }
        if($varError2 != 1){
    ?>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 text-center">
            <h2>Egresos/Ingresos</h2>
            <div class="gcd-card"><div id="gcd-chart-b" class="chart"></div></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 text-center">
            <h2>Flujo de Caja</h2>
            <div class="gcd-card"><div id="gcd-chart-c" class="chart"></div></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 text-center">
            <h2>Margen de Utilidad/Perdida</h2>
            <div class="gcd-card"><div id="gcd-chart-d" class="chart"></div></div>
        </div>
    </div>
    <?
        }
    ?>
</div>   
<?
	if(intval($_REQUEST["idCliente"]) != 0){
		$paginaActualTxT = "graphs_costos_directos";
		$_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT,"excel_costos_directos", $_SERVER['REQUEST_URI']);
		//
		?><center>
		<a href="<?=$_SERVER['REQUEST_URI']; ?>&excelX=1" class='btn btn-info'><span class='glyphicon glyphicon-cloud-download'></span> DESCARGAR PARA EXCEL</a>
		</center><?
	}
?>
