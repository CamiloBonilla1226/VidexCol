<?php



if($_SESSION["perfil"] != 1  && $_SESSION["perfil"] != 2)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

$PSN = new DBbase_Sql;

/*
*   INICIALIZACIÓN DE VARIABLES
*/
$varError = 0;
$nombreGrafica = "Proyectos aprobados, pendientes y rechazados";
$arrayConResultados = array();

$busquedaFechaInicio = date("Y");
if(isset($_REQUEST["fecha-inicio"]) && soloNumeros($_REQUEST["fecha-inicio"]) != ""){
	$busquedaFechaInicio = soloNumeros($_REQUEST["fecha-inicio"]);

}

$busquedaFechaFinal = date("Y");
if(isset($_REQUEST["fecha-final"]) && soloNumeros($_REQUEST["fecha-final"]) != ""){
	$busquedaFechaFinal = soloNumeros($_REQUEST["fecha-final"]);
}

?>
<style>
    .g0011-container { padding: 20px; max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g0011-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px 24px; border-radius: 12px; margin-bottom: 24px; }
    .g0011-hero h1 { margin: 0; font-size: 28px; }
    .g0011-filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; }
    .g0011-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; align-items: end; }
    .g0011-field { display: flex; flex-direction: column; }
    .g0011-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; }
    .g0011-field select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
    .g0011-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .g0011-empty { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 14px; margin-bottom: 16px; }
    .g0011-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
    #g0011-chart { width: 100%; min-height: 420px; }
    @media (max-width: 900px) { .g0011-grid { grid-template-columns: 1fr; } }
</style>

<div class="g0011-container">
    <?php
    if($busquedaFechaInicio == $busquedaFechaFinal){
        $varError = 1;
        echo "<div class='g0011-empty'><strong>Error:</strong> La fecha de inicio y final deben ser distintas.</div>";
    }else if($busquedaFechaFinal <= $busquedaFechaInicio){
        $varError = 1;
        echo "<div class='g0011-empty'><strong>Error:</strong> La fecha final debe ser mayor que la fecha inicial.</div>";
    }else{
        for ($year = $busquedaFechaInicio; $year <= $busquedaFechaFinal; $year++) {

            // TODO:primera consulta para sacar valor total de proyectos aprobados
            $sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
            $sql.=" FROM cotizacion ";
            $sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
            $sql.=" where cotizacion.estado IN (65, 66, 67) AND  YEAR(cotizacion.fechaCotizacion) = '".$year."'";
            $sql.=" GROUP BY MONTH(fechaCotizacion)";
            $sql.=" ORDER BY mes asc";

            $PSN->query($sql);
            $num=$PSN->num_rows();
            $totalValCotApr = 0;
            $totalNoCotApr = 0;
            if($num > 0){
                while ($PSN->next_record()) {
                    $totalNoCotApr += $PSN->f('cotizaciones');

                }
            }

            // TODO:segunda consulta para sacar el total de proyectos pendientes
            $sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
            $sql.=" FROM cotizacion ";
            $sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
            $sql.=" where cotizacion.estado IN (62, 63, 64) AND  YEAR(cotizacion.fechaCotizacion) = '".$year."'";
            $sql.=" GROUP BY MONTH(fechaCotizacion)";
            $sql.=" ORDER BY mes asc";

            $PSN->query($sql);
            $num=$PSN->num_rows();
            $totalNoCotPend = 0;
            if($num > 0){
                while ($PSN->next_record()) {
                    $totalNoCotPend += $PSN->f('cotizaciones');
                }
            }

            // TODO:tercera consulta para sacar el totla de proyectos rechazados
            $sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
            $sql.=" FROM cotizacion ";
            $sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
            $sql.=" where cotizacion.estado = 68 AND  YEAR(cotizacion.fechaCotizacion) = '".$year."'";
            $sql.=" GROUP BY MONTH(fechaCotizacion)";
            $sql.=" ORDER BY mes asc";

            $PSN->query($sql);
            $num=$PSN->num_rows();
            $totalNoCotRech = 0;
            if($num > 0){   
                while ($PSN->next_record()) {
                    $totalNoCotRech += $PSN->f('cotizaciones');
                }
            }


            $arrayConResultados[$year] = array("aprobados" => $totalNoCotApr, "pendientes" => $totalNoCotPend, "rechazados" => $totalNoCotRech);


        }

    }
    ?>
    <div class="g0011-hero">
        <h1><?=$nombreGrafica; ?></h1>
    </div>
    <form action="index.php" method="get" name="form1" class="g0011-filters">
        <input type="hidden" name="doc" value="graphs_0011"/>
        <div class="g0011-grid">
            <div class="g0011-field">
                <label for="fecha-inicio">Fecha de Inicio</label>
                <select name="fecha-inicio" id="fecha-inicio">
                <?
				for($i=2015;$i<=date("Y");$i++)
				{
					?><option value="<?=$i; ?>" <?
					if($busquedaFechaInicio == $i){
						?>selected="selected"<?
					}
					?>><?=$i; ?></option><?
				}
			?>
                </select>
            </div>
            <div class="g0011-field">
                <label for="fecha-final">Fecha Final</label>
                <select name="fecha-final" id="fecha-final">
                <?
				for($i=2015;$i<=date("Y");$i++)
				{
					?><option value="<?=$i; ?>" <?
					if($busquedaFechaFinal == $i){
						?>selected="selected"<?
					}
					?>><?=$i; ?></option><?
				}
                ?>
                </select>
            </div>
            <div class="g0011-field">
                <button type="submit" class="g0011-btn">Evaluar</button>
            </div>
        </div>
    </form>
    <script src="https://www.gstatic.com/charts/loader.js"></script>

<!-- table con cantidad de proyectos aprobados rechazados pendientes  -->
<table style="display:none">
    <thead>
        <tr>
            <th>cantidad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($arrayConResultados as $year => $resultados) : ?>
            <tr>
                <td class="cantidad"><?php echo $year; ?>,<?php echo $resultados["aprobados"]; ?>,<?php echo $resultados["pendientes"]; ?>,<?php echo $resultados["rechazados"]; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="g0011-card">
    <div id="g0011-chart"></div>
</div>
    <script >
      google.charts.load("current", {packages:["corechart"]});

      const allCantidad = document.querySelectorAll('.cantidad')

      if(chekearPorcentajes()){

        google.charts.setOnLoadCallback(drawVisualization);

        function drawVisualization(){
            let data = new google.visualization.DataTable();
            data.addColumn('string', 'A���o');
            data.addColumn('number','aprobados')
            data.addColumn('number','rechazados')
            data.addColumn('number','pendientes')
            allCantidad.forEach((e)=>{
                let ano  =e.textContent.split(",")[0]
                let aprobados = Number(e.textContent.split(",")[1])
                let rechazados = Number(e.textContent.split(',')[3])
                let pendientes = Number(e.textContent.split(",")[2])
                data.addRows([
                    [ano, aprobados,rechazados,pendientes],
                ]);
            })

            var options = {
                title : 'Cantidad de proyectos aprobados, pendientes, rechazados',
                vAxis: {title: 'Cantidad'},
                hAxis: {title: 'A���o'},

                seriesType: 'bars',
                series: {5: {type: 'line'}}
        };

                let chart = new google.visualization.ComboChart(document.getElementById('g0011-chart'));
        chart.draw(data, options);
        }
      }

      function chekearPorcentajes(){
        let mostrarGraficaCantidad = (allCantidad.length == 0) ? false :true;

        return mostrarGraficaCantidad;       
      } 
</script>
</div>
