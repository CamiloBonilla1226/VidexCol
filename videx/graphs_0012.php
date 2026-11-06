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
$nombreGrafica = "Crecimiento en ventas";
$arrayValorTotalProyectosAprobados = array();

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
    .g0012-container { padding: 20px; max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    .g0012-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 28px 24px; border-radius: 12px; margin-bottom: 24px; }
    .g0012-hero h1 { margin: 0; font-size: 28px; }
    .g0012-filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; }
    .g0012-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; align-items: end; }
    .g0012-field { display: flex; flex-direction: column; }
    .g0012-field label { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; }
    .g0012-field select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
    .g0012-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .g0012-empty { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 14px; margin-bottom: 16px; }
    .g0012-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
    #g0012-chart { width: 100%; min-height: 420px; }
    @media (max-width: 900px) { .g0012-grid { grid-template-columns: 1fr; } }
</style>

<div class="g0012-container">
    <?php
    if($busquedaFechaInicio == $busquedaFechaFinal){
        $varError = 1;
        echo "<div class='g0012-empty'><strong>Error:</strong> La fecha de inicio y final deben ser distintas para evaluar el crecimiento.</div>";
    }else if($busquedaFechaFinal <= $busquedaFechaInicio){
        $varError = 1;
        echo "<div class='g0012-empty'><strong>Error:</strong> La fecha final debe ser mayor que la fecha inicial.</div>";
    }else{
        for ($year = $busquedaFechaInicio; $year <= $busquedaFechaFinal; $year++) {

            // TODO:primera consulta para sacar valor total de proyectos aprobados y total de proyectos aprobados
            $sql= "SELECT COUNT(cotizacion.id) as cotizaciones, SUM(cotizacion.valorCotizado) as TOTAL_COTIZACIONES, MONTH(fechaCotizacion) as mes ";
            $sql.=" FROM cotizacion ";
            $sql.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente ";
            $sql.=" where cotizacion.estado IN (65, 66, 67) AND  YEAR(cotizacion.fechaCotizacion) = '".$year."'";
            $sql.=" GROUP BY MONTH(fechaCotizacion)";
            $sql.=" ORDER BY mes asc";

            $PSN->query($sql);
            $num=$PSN->num_rows();
            $totalValCotApr = 0;
            if($num > 0){
                while ($PSN->next_record()) {
                    $totalValCotApr += $PSN->f('TOTAL_COTIZACIONES');

                }
                $arrayValorTotalProyectosAprobados[$year] = $totalValCotApr;
            }
        }

    }
    ?>
    <div class="g0012-hero">
        <h1><?=$nombreGrafica; ?></h1>
    </div>
    <form action="index.php" method="get" name="form1" class="g0012-filters">
        <input type="hidden" name="doc" value="graphs_0012"/>
        <div class="g0012-grid">
            <div class="g0012-field">
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
            <div class="g0012-field">
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
            <div class="g0012-field">
                <button type="submit" class="g0012-btn">Evaluar</button>
            </div>
        </div>
    </form>
    <script src="https://www.gstatic.com/charts/loader.js"></script>

<!-- Lista de porcentajes de crecimiento -->
<?php
echo "<ul style='display:none;'>";
foreach ($arrayValorTotalProyectosAprobados as $ano => $valor) {
    echo "<li class='valor'>$ano:$valor</li>";
}
echo "</ul>";
?>
<div class="g0012-card">
    <div id="g0012-chart"></div>
</div>
    <script >
      google.charts.load("current", {packages:["corechart"]});
      const allPorcentajes = document.querySelectorAll('.valor')

      if(chekearPorcentajes()){
        
        google.charts.setOnLoadCallback(drawBarChart);
        
        function drawBarChart() {
            
        let data = new google.visualization.DataTable();
        data.addColumn('string', 'Año');
        data.addColumn('number', 'Valor: ');
        allPorcentajes.forEach((e) => {
            let ano = e.textContent.split(":")[0]
            let porcentaje = Number(e.textContent.split(":")[1])
             data.addRows([
            [ano, porcentaje],
            ]);
        })
        

        let options = {
            title: 'Crecimiento en ventas',
            hAxis: {title: 'Año'},
            vAxis: {title: 'Crecimiento valor'},
            colors: ['#3366cc'], // Color de las barras
        };

                var chart = new google.visualization.ColumnChart(document.getElementById('g0012-chart'));
        chart.draw(data, options);
        }
      }


      function chekearPorcentajes(){
        let mostrarGraficaPorcentaje = (allPorcentajes.length == 0) ? false : true;
        

        return mostrarGraficaPorcentaje      
      } 
</script>
</div>
