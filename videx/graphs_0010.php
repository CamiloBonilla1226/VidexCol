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
$nombreGrafica = "Porcentaje Efectividad";
$arrayConPorcentajesPorCadaAno = array();
$arrayConCantidadDeCotizacionesHechasYNoHechasPorAno = array();

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
    .g0010-container { padding: 20px; max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g0010-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px 24px; border-radius: 12px; margin-bottom: 24px; }
    .g0010-hero h1 { margin: 0; font-size: 28px; }
    .g0010-filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; }
    .g0010-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; align-items: end; }
    .g0010-field { display: flex; flex-direction: column; }
    .g0010-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; }
    .g0010-field select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
    .g0010-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .g0010-empty { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 14px; margin-bottom: 16px; }
    .g0010-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .g0010-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
    .g0010-chart { width: 100%; min-height: 320px; }
    @media (max-width: 900px) { .g0010-grid, .g0010-cards { grid-template-columns: 1fr; } }
</style>

<div class="g0010-container">
    <?php
    if($busquedaFechaInicio == $busquedaFechaFinal){
        $varError = 1;
        echo "<div class='g0010-empty'><strong>Error:</strong> La fecha de inicio y final deben ser distintas para evaluar la efectividad.</div>";
    }else if($busquedaFechaFinal <= $busquedaFechaInicio){
        $varError = 1;
        echo "<div class='g0010-empty'><strong>Error:</strong> La fecha final debe ser mayor a la fecha inicial.</div>";
    }else{
        for($year = $busquedaFechaInicio; $year <= $busquedaFechaFinal; $year++){
            $sql = "SELECT cotizacion.estado FROM `cotizacion`WHERE YEAR(fechaCotizacion) = '".$year."'";
            
            $PSN->query($sql);
            $num=$PSN->num_rows();
            if($num > 0){
                $cantidadDeCotizacionesHechas = 0;
                $cantidadDeCotizacionesNoHechas = 0;
                while($PSN->next_record()){
                    if($PSN->f("estado") == '65' || $PSN->f("estado") == '66' || $PSN->f("estado") == '67'){
                        $cantidadDeCotizacionesHechas = $cantidadDeCotizacionesHechas + 1;
                    }else if($PSN->f("estado") == '62' || $PSN->f("estado") == '63' || $PSN->f("estado") == '64' || $PSN->f("estado") == '68'){
                        $cantidadDeCotizacionesNoHechas = $cantidadDeCotizacionesNoHechas + 1;
                    }
                    $arrayConCantidadDeCotizacionesHechasYNoHechasPorAno[$year] = array("hechas" => $cantidadDeCotizacionesHechas,"noHechas" => $cantidadDeCotizacionesNoHechas);
                }

            }
        }

        // echo "Contenido del array:\n";
        // echo "<pre>";  // <pre> preserva el formato para una mejor legibilidad
        // echo print_r($arrayConCantidadDeCotizacionesHechasYNoHechasPorAno, true);  // El segundo parámetro de print_r devuelve el resultado como una cadena
        // echo "</pre>";

        function reglaDeTres($cantidadEnTotalCotizaciones,$hechas,$noHechas){
            $y = $hechas * 100;
            $x = $y / $cantidadEnTotalCotizaciones;
            return $x;

        }

        foreach($arrayConCantidadDeCotizacionesHechasYNoHechasPorAno as $ano => $datos){
            $cantidadEnTotalCotizaciones = $datos["hechas"] +  $datos["noHechas"];
            if($cantidadEnTotalCotizaciones == 0) continue;
            $porcentajeDeEfectividad = reglaDeTres($cantidadEnTotalCotizaciones,$datos["hechas"],$datos["noHechas"]);
            $porcentajeDeNoEfectividad = 100 - $porcentajeDeEfectividad;
            $arrayConPorcentajesPorCadaAno[$ano] = array("hechas" => $datos["hechas"],"efectividad"=> round($porcentajeDeEfectividad, 1),"noHechas" => $datos["noHechas"],"Noefectividad"=> round($porcentajeDeNoEfectividad, 1));
        }
    }
    ?>
    <div class="g0010-hero">
        <h1><?=$nombreGrafica; ?> en Cotizaciones</h1>
    </div>

    <form action="index.php" method="get" name="form1" class="g0010-filters">
        <input type="hidden" name="doc" value="graphs_0010"/>
        <div class="g0010-grid">
            <div class="g0010-field">
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

            <div class="g0010-field">
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

            <div class="g0010-field">
                <button type="submit" class="g0010-btn">Evaluar</button>
            </div>
        </div>
    </form>

        <?php
        if(empty($arrayConPorcentajesPorCadaAno)){
            echo "<div class='g0010-empty'>No se encontraron registros con estados de cotizaci&oacute;n v&aacute;lidos para el rango de a&ntilde;os seleccionado.</div>";
        }
        echo '<div class="g0010-cards boxCharts">';
        foreach ($arrayConPorcentajesPorCadaAno as $year => $resultados) {
            echo "<div class='g0010-card' data-year='" . $year . "'>
                <p style='display:none'>" . $resultados["efectividad"] . "," . $resultados["Noefectividad"] . "," . $resultados["hechas"] . "," . $resultados["noHechas"] ."</p>
            </div>";
        }
        echo '</div>';
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(function() {
        var boxCharts = document.querySelector('.boxCharts');
        if (!boxCharts || boxCharts.children.length === 0) return;

        Array.from(boxCharts.children).forEach(function(div) {
            var info = div.querySelector('p').textContent.split(',');
            var efectividad  = parseFloat(info[0]) || 0;
            var noEfectividad = parseFloat(info[1]) || 0;
            var hechas   = parseInt(info[2]) || 0;
            var noHechas = parseInt(info[3]) || 0;
            var ano = div.dataset.year || '';

            var data = google.visualization.arrayToDataTable([
                ['Resultado', 'Porcentaje'],
                ['Aprobados (' + hechas + ')', efectividad],
                ['No aprobados (' + noHechas + ')', noEfectividad]
            ]);
            var options = {
                title: 'Efectividad anno ' + ano,
                pieHole: 0.5,
                backgroundColor: 'transparent',
                legend: { position: 'bottom' }
            };
            new google.visualization.PieChart(div).draw(data, options);
        });
    });
</script>
</div>