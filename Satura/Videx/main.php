<?php
//print_r( $_SESSION ); 
//    echo $_SESSION["perfil"];
//    if ($_SESSION["perfil"] == 2) {
//        redirect('index.php?doc=main_admin');
//    }

function obtenerPorcentaje($cantidad, $total) {
    $porcentaje = ((float)$cantidad * 100) / $total; // Regla de tres
    $porcentaje = round($porcentaje, 2);  // Quitar los decimales
    return $porcentaje;
}

function cargarDatosGrafica($anio = null){
    if ($anio == null) {
        $fechInicio = "2021-01-01";
        $fechFin = date("Y-m-d");
    } else {
        $fechInicio = $anio."-02-01";
        $fechFin = ($anio+1)."-01-31";
    }
    $sqlWhere = "";
    $sqlUser = "";
    $datosView = array();
    // usuario diferente al jefe
    if($_SESSION["perfil"] == 163){
        $buscar_idUsuario = soloNumeros($_SESSION["id"]);
        $sqlUser .= "sat_reportes.idUsuario = '".$buscar_idUsuario."' AND ";
    }
    $sqlWhere .= " AND sat_reportes.fechaReporte >= '".$fechInicio."'";
    $sqlWhere .= " AND sat_reportes.fechaReporte <= '".$fechFin."'";
    $sqlFiltro_limpio = $sqlWhere;
    $sqlWhere .= " AND sat_reportes.generacionNumero != 0 AND sat_reportes.generacionNumero != 77";
    // AND sat_reportes.generacionNumero != 8";
    //echo $sqlWhere;
    $GRF_DATOS = new DBbase_Sql;

    // Estas tres metricas no dependen de generacion:
    // Evangelismo: asistencia_total de reportes con id_actividad = 77.
    // Discipulado y bautizos: suma directa de todos los reportes filtrados.
    $sqlMetricas = "SELECT 
        COALESCE(SUM(CASE WHEN sat_reportes.id_actividad = 77 THEN sat_reportes.asistencia_total ELSE 0 END), 0) as evangelismo,
        COALESCE(SUM(sat_reportes.discipulado), 0) as discipulado,
        COALESCE(SUM(sat_reportes.bautizados), 0) as bautizos
    FROM sat_reportes 
    LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
    WHERE ".$sqlUser." 1 ".$sqlFiltro_limpio;
    $GRF_DATOS->query($sqlMetricas);
    if($GRF_DATOS->next_record() > 0){
        $datosView['evangelismo'] = intval($GRF_DATOS->f('evangelismo'));
        $datosView['discipulado'] = intval($GRF_DATOS->f('discipulado'));
        $datosView['bautizos'] = intval($GRF_DATOS->f('bautizos'));
    } else {
        $datosView['evangelismo'] = 0;
        $datosView['discipulado'] = 0;
        $datosView['bautizos'] = 0;
    }
    
    $sql = "SELECT 
        SUM(asistencia_total) as asistencia,
        COUNT(id) as iglesias,
        SUM(desiciones) as desiciones";
    $sql.=" FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
    $sql.=" WHERE ".$sqlUser." 1 ".$sqlWhere."";
    //    print_r($sql);
    //    echo $sql." ".$sqlWhere;
    //echo $sql;
    $GRF_DATOS->query($sql);
    $num=$GRF_DATOS->num_rows();
    //echo "<h2>".$anio."</h2>";
    // falloso desde
    /*    if($num > 0){
            while($GRF_DATOS->next_record()){
                // Discipulado se calcula aparte sin filtro de generacion.
                $datosView['bautizos'] = $GRF_DATOS->f('bautizos');
                $datosView['desiciones'] = $GRF_DATOS->f('desiciones');
                $datosView['asistencia'] = $GRF_DATOS->f('evangelismo');
                echo "Evangelismo: ".$GRF_DATOS->f('evangelismo')."<br>";
                echo "<br>Discipulado: ".$GRF_DATOS->f('discipulado')."<br>";
                echo "<br>Bautizos: ".$GRF_DATOS->f('bautizos')."<br>";
            }
        }else{
            $varError = 1;
        } */
    // falloso hasta
    // nueva versión desde
    if($GRF_DATOS->next_record() > 0){
        $datosView['desiciones'] = $GRF_DATOS->f('desiciones');
        $datosView['asistencia'] = $GRF_DATOS->f('asistencia');
        //echo "Evangelismo: ".$GRF_DATOS->f('evangelismo');
        //echo "<br>Discipulado: ".$GRF_DATOS->f('discipulado');
        //echo "<br>Bautizos: ".$GRF_DATOS->f('bautizos');
    } 
    // nueva versión hasta

    // Fallosos desde
    /*    $GRF_DATOS->query($sql." AND generacionNumero = 1");
        $num=$GRF_DATOS->num_rows();
        if($num > 0){
            while($GRF_DATOS->next_record()){
                //echo "<br>Generacion 1: ".$GRF_DATOS->f('iglesias');
                $datosView['gen-1'] = $GRF_DATOS->f('iglesias');
            }
        }
        $GRF_DATOS->query($sql." AND generacionNumero = 2");
        $num=$GRF_DATOS->num_rows();
        if($num > 0){
            while($GRF_DATOS->next_record()){
                //echo "<br>Generacion 2: ".$satura_iglesias2 = $GRF_DATOS->f('iglesias');
                $datosView['gen-2'] = $GRF_DATOS->f('iglesias'); 
            }
        }

        $GRF_DATOS->query($sql." AND generacionNumero = 3");
        $num=$GRF_DATOS->num_rows();
        if($num > 0){
            while($GRF_DATOS->next_record()){
                //echo "<br>Generacion 3: ".$GRF_DATOS->f('iglesias');
                $datosView['gen-3'] = $GRF_DATOS->f('iglesias');
            }
        } */
    // Fallosos hasta
    // Nuevos desde
    $GRF_DATOS->query($sql." AND generacionNumero = 1");
    $num=$GRF_DATOS->num_rows();
    if($GRF_DATOS->next_record() > 0){
        //echo "<br>Generacion 1: ".$GRF_DATOS->f('iglesias');
        $datosView['gen-1'] = $GRF_DATOS->f('iglesias');
    }
    $GRF_DATOS->query($sql." AND generacionNumero = 2");
    $num=$GRF_DATOS->num_rows();
    if($GRF_DATOS->next_record() > 0){
        //echo "<br>Generacion 2: ".$satura_iglesias2 = $GRF_DATOS->f('iglesias');
        $datosView['gen-2'] = $GRF_DATOS->f('iglesias'); 
    }

    $GRF_DATOS->query($sql." AND generacionNumero = 3");
    $num=$GRF_DATOS->num_rows();
    if($GRF_DATOS->next_record() > 0){
        //echo "<br>Generacion 3: ".$GRF_DATOS->f('iglesias');
        $datosView['gen-3'] = $GRF_DATOS->f('iglesias');
    }
    // Nuevos hasta

    //var_dump($datosView);
    return $datosView;
}

function cargarMetasGrafica($anio = null){
    $sqlWhere = "";
    $metasView = array();
    $GRF_METAS = new DBbase_Sql;
    if ($anio==null) {
        $sql = "SELECT SUM(evangelismo) AS evangelismo,SUM(discipulado) AS discipulado,SUM(bautizos) AS bautizos,SUM(iglesias) AS iglesias,SUM(iglesias2) AS iglesias2,SUM(iglesias3) AS iglesias3";
        $sql.=" FROM usuario_metas ";
        //        if($_SESSION["perfil"] == 163){
        if ($_SESSION["id"] != 9) {
            $sql.=" WHERE idUsuario = '".$_SESSION["id"]."'";
        }
        //        }else{
        //            $sql.=" WHERE idUsuario = 0";
        //        }
    } else {
        $sql = "SELECT SUM(evangelismo) AS evangelismo,SUM(discipulado) AS discipulado,SUM(bautizos) AS bautizos,SUM(iglesias) AS iglesias,SUM(iglesias2) AS iglesias2,SUM(iglesias3) AS iglesias3  ";
        $sql.=" FROM usuario_metas ";
        $sql.=" WHERE anho = '".$anio."'";
        //        if($_SESSION["perfil"] == 163){
        if ($_SESSION["id"] != 9) {
            $sql.=" AND idUsuario = '".$_SESSION["id"]."'";
        }
        //        }else{
        //            $sql.=" AND idUsuario = 0";
        //        }
    }
    //    echo $sql . "- <br>";
    //echo $sql;
    $GRF_METAS->query($sql);
    $num_GRF=$GRF_METAS->num_rows();

    // consulta fallosa desde
    /*    if($num_GRF > 0){
            $wEvan = 0;
            while($GRF_METAS->next_record()){
                $metasView['evangelismo'] = $GRF_METAS->f('evangelismo');
                $metasView['discipulado'] = $GRF_METAS->f('discipulado');
                $metasView['bautizos'] = $GRF_METAS->f('bautizos');
                $metasView['desiciones'] = 0;
                $metasView['gen-1'] = $GRF_METAS->f('iglesias');
                $metasView['gen-2'] = $GRF_METAS->f('iglesias2');
                $metasView['gen-3'] = $GRF_METAS->f('iglesias3');
                $metasView['asistencia'] = 0;
                echo $GRF_METAS->f('evangelismo') . " - <br>"; 
                $wtemp = $GRF_METAS->f('evangelismo');
                echo $wtemp . " ^ <br>";
                $wEvan .= intval($wtemp);
                echo $wEvan . " # <br>";
            }
            echo $wEvan . " / <br>";
        } */
    
    // consulta fallosa hasta
    
    // Resultados de las metas desde
    if($GRF_METAS->next_record() > 0){
        $metasView['evangelismo'] = $GRF_METAS->f('evangelismo');
        $metasView['discipulado'] = $GRF_METAS->f('discipulado');
        $metasView['bautizos'] = $GRF_METAS->f('bautizos');
        $metasView['desiciones'] = 0;
        $metasView['gen-1'] = $GRF_METAS->f('iglesias');
        $metasView['gen-2'] = $GRF_METAS->f('iglesias2');
        $metasView['gen-3'] = $GRF_METAS->f('iglesias3');
        $metasView['asistencia'] = 0;
    } 
    // Resultado de las metas hasta
    
    return $metasView; 
}

$PSN = new DBbase_Sql;
if($_SESSION["id"] == ""){
    $_SESSION["id"] = 0;
}
$nombreGrafica ="MIS METAS";
?>
<br>
<div class="container-fluid" style="display: flex; flex-wrap: wrap;">
    <div class="jumbotron" >
        <div class="container-fluid cont-info ">
            <?php if($_SESSION["youtube"] != ""){?>
                <div class="col-sm-3 item-grf">
                    <iframe width="100%" height="200"  src="https://www.youtube.com/embed/<?=$_SESSION["youtube"]; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            <?php
                $ancho = 9;
            } else { 
                $ancho = 8;
            ?>
                <div class="col-sm-4 item-grf">
                    <img src="images/titulo.png" class="img-responsive" />
                </div>
            <?php }?>
            <div class="col-sm-<?php echo $ancho; ?> item-tex">
                <h2 style="margin: 0px"><?=$gloPrograma; ?></h2>
                <h3 style="margin: 0px"><?=$_SESSION["empresa_socio"]; //gloEmpresa; ?></h3>
                <h3 style="margin: 0px">Bienvenid@ <?=$_SESSION["nombre"]; ?></h3>
                <p style="margin: 0px">Desde aquí usted podrá contar con toda la información a tan solo un clic de distancia.</p>
                <ul class="social">
                    <li class="social-item"><a href="https://saturacolombia.org/" target="_blank"><i class="fas fa-globe-americas"></i></a></li>
                    <li class="social-item"><a href="https://www.facebook.com/saturacolombia/" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                    <li class="social-item"><a href="https://www.instagram.com/saturacolombia/" target="_blank"><i class="fab fa-instagram"></i></a></li>
                    <li class="social-item"><a href="https://www.youtube.com/user/SaturaColombia" target="_blank"><i class="fab fa-youtube"></i></a></li>
                    <li class="social-item"><a href="https://twitter.com/saturacolombia" target="_blank"><i class="fab fa-twitter"></i></a></li>
                </ul>
            </div>    
        </div>
    </div>

    <!-- Banner de Auditoría de Grupos -->
    <div style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <div class="container-fluid">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="color: white; margin: 0 0 10px 0; font-size: 24px; font-weight: bold;">
                        🔍 Auditoría de Grupos Habilitada
                    </h3>
                    <p style="color: rgba(255,255,255,0.9); margin: 0 0 15px 0; font-size: 14px;">
                        Consolida las variantes de tus grupos para tener datos consistentes en tus reportes. Accede ahora a la herramienta de auditoría.
                    </p>
                </div>
                <div style="flex-shrink: 0;">
                    <a href="index.php?doc=auditoria_grupos" 
                       style="display: inline-block; background: white; color: #667eea; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.25)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)';">
                        Ir a Auditoría →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php
    $sql = "SELECT RP.* ";
    $sql.=" FROM sat_reportes AS RP ";
    $sql.=" GROUP BY YEAR(RP.creacionFecha) 
            ORDER BY RP.creacionFecha ASC";
    $PSN->query($sql);
    $num_ARP=$PSN->num_rows();
    if ($num_ARP > 0) { 
    ?>
        <div class="cont-tit" style="width: 100%;">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center"><?=$nombreGrafica; ?></h3>
            </div>
            <div class="hr"><hr></div>
        </div> 
        <div class="row" style="width: 100%;" >
            <div class="t-container">
                <ul class="t-tabs">  
                    <?php 
                    while($PSN->next_record()){
                        $anio = date("Y", strtotime($PSN->f('creacionFecha')));
                        echo '<li class="t-tab ';
                        if ($anio == date('Y')){ 
                            echo ' selected ';
                        }
                        echo '" > '.$anio.'</li>';
                    }
                    ?>
                    <li class="t-tab">Total plan maestro</li>
                </ul>
                <?php 
                $sql = "SELECT RP.* ";
                $sql.=" FROM sat_reportes AS RP ";
                $sql.=" GROUP BY YEAR(RP.creacionFecha) 
                        ORDER BY RP.creacionFecha ASC";
                $PSN->query($sql);
                $num_RES=$PSN->num_rows();
                while($PSN->next_record()){
                    $anio = date("Y", strtotime($PSN->f('creacionFecha')));
                    $datfYear = array();
                    $datfYear[$anio] = cargarDatosGrafica($anio);
                    //var_dump($datfYear[$anio]);
                    $metfYear = array(); 
                    $metfYear[$anio] = cargarMetasGrafica($anio);
                ?>
                    
                <script>
                    google.charts.load("current", {packages:["corechart"]});
                    google.charts.setOnLoadCallback(drawChart<?php echo $anio;?>);
                    function drawChart<?php echo $anio;?>() {
                        var data = google.visualization.arrayToDataTable([
                            ['Nombre', 'Meta', 'Datos <?php echo $anio; ?> '], 
                            <?php 
                            echo "['".obtenerPorcentaje($datfYear[$anio]['evangelismo'], $metfYear[$anio]['evangelismo'])."%  Evangelismo',".intval($metfYear[$anio]['evangelismo']).",".intval($datfYear[$anio]['evangelismo'])."],";
                            echo "['".obtenerPorcentaje($datfYear[$anio]['discipulado'], $metfYear[$anio]['discipulado'])."% Discipulado',".intval($metfYear[$anio]['discipulado']).",".intval($datfYear[$anio]['discipulado'])."],";
                            /*echo "['".obtenerPorcentaje($datfYear[$anio]['desiciones'], $metfYear[$anio]['desiciones'])."% Desiciones',".intval($metfYear[$anio]['desiciones']).",".intval($datfYear[$anio]['desiciones'])."],";*/
                            echo "['".obtenerPorcentaje($datfYear[$anio]['bautizos'], $metfYear[$anio]['bautizos'])."% Bautizos',".intval($metfYear[$anio]['bautizos']).",".intval($datfYear[$anio]['bautizos'])."],";
                            echo "['".obtenerPorcentaje($datfYear[$anio]['gen-1'], $metfYear[$anio]['gen-1'])."% IPG Generación 1',".intval($metfYear[$anio]['gen-1']).",".intval($datfYear[$anio]['gen-1'])."],";
                            echo "['".obtenerPorcentaje($datfYear[$anio]['gen-2'], $metfYear[$anio]['gen-2'])."% IPG Generación 2',".intval($metfYear[$anio]['gen-2']).",".intval($datfYear[$anio]['gen-2'])."],";
                            echo "['".obtenerPorcentaje($datfYear[$anio]['gen-3'], $metfYear[$anio]['gen-3'])."% IPG Generación 3',".intval($metfYear[$anio]['gen-3']).",".intval($datfYear[$anio]['gen-3'])."],";
                            ?>
                        ]);
                        // Información eliminada de la gráfica
                        // echo "['Total grupos y Asistencia',".intval($datfYear[$anio]['gen-1']+$datfYear[$anio]['gen-2']+$datfYear[$anio]['gen-3']).",".intval($datfYear[$anio]['asistencia'])."]";

                        var view = new google.visualization.DataView(data);
                        view.setColumns([
                            0, 
                            { 
                                calc: "stringify",
                                sourceColumn: 2,
                                type: "string",
                                role: "annotation" 
                            }, 
                            1,
                            { 
                                calc: "stringify",
                                sourceColumn: 1,
                                type: "string",
                                role: "annotation" 
                            }, 
                            2
                        ]);

                        var options = {
                            chartArea: {
                                // leave room for y-axis labels
                                width: '70%',
                                height: '80%'
                            },
                            bar: {groupWidth: "95%"},
                            legend: { position: 'none' },
                            width: '90%',
                            colors: ['limegreen', 'crimson']
                        };
                        var chart = new google.visualization.BarChart(document.getElementById('grafica<?php echo $anio;?>'));
                        chart.draw(view, options); 
                    }
                </script>
                <?php 
                }
                
                $datfYear = array();
                $datfYear['maestro'] = cargarDatosGrafica();
                $metfYear = array(); 
                $metfYear['maestro'] = cargarMetasGrafica(); 
                ?>
                
                <script type="text/javascript">
                    google.charts.load('current', {'packages':['bar']});
                    google.charts.setOnLoadCallback(drawChart);
                    function drawChart() {
                        var data = google.visualization.arrayToDataTable([
                            ['Nombre', 'Meta', 'Datos Plan maestro '], 
                            <?php 
                            echo "['".obtenerPorcentaje($datfYear['maestro']['evangelismo'], $metfYear['maestro']['evangelismo'])."%  Evangelismo',".intval($metfYear['maestro']['evangelismo']).",".intval($datfYear['maestro']['evangelismo'])."],";
                            echo "['".obtenerPorcentaje($datfYear['maestro']['discipulado'], $metfYear['maestro']['discipulado'])."% Discipulado',".intval($metfYear['maestro']['discipulado']).",".intval($datfYear['maestro']['discipulado'])."],";
                            /*echo "['".obtenerPorcentaje($datfYear['maestro']['desiciones'], $metfYear['maestro']['desiciones'])."% Desiciones',".intval($metfYear['maestro']['desiciones']).",".intval($datfYear['maestro']['desiciones'])."],";*/
                            echo "['".obtenerPorcentaje($datfYear['maestro']['bautizos'], $metfYear['maestro']['bautizos'])."% Bautizos',".intval($metfYear['maestro']['bautizos']).",".intval($datfYear['maestro']['bautizos'])."],";
                            echo "['".obtenerPorcentaje($datfYear['maestro']['gen-1'], $metfYear['maestro']['gen-1'])."% IPG Generación 1',".intval($metfYear['maestro']['gen-1']).",".intval($datfYear['maestro']['gen-1'])."],";
                            echo "['".obtenerPorcentaje($datfYear['maestro']['gen-2'], $metfYear['maestro']['gen-2'])."% IPG Generación 2',".intval($metfYear['maestro']['gen-2']).",".intval($datfYear['maestro']['gen-2'])."],";
                            echo "['".obtenerPorcentaje($datfYear['maestro']['gen-3'], $metfYear['maestro']['gen-3'])."% IPG Generación 3',".intval($metfYear['maestro']['gen-3']).",".intval($datfYear['maestro']['gen-3'])."],";
                            echo "['Total grupos y Asistencia',".intval($datfYear['maestro']['gen-1']+$datfYear['maestro']['gen-2']+$datfYear['maestro']['gen-3']).",".intval($datfYear['maestro']['asistencia'])."]";
                            ?>
                        ]);

                        var view = new google.visualization.DataView(data);
                        view.setColumns([
                            0, 
                            { 
                                calc: "stringify",
                                sourceColumn: 2,
                                type: "string",
                                role: "annotation" 
                            }, 
                            1,
                            { 
                                calc: "stringify",
                                sourceColumn: 1,
                                type: "string",
                                role: "annotation" 
                            }, 
                            2
                        ]);

                        var options = {
                            chartArea: {
                                // leave room for y-axis labels
                                width: '70%',
                                height: '80%'
                            },
                            bar: {groupWidth: "95%"},
                            legend: { position: 'none' },
                            width: '90%',
                            colors: ['limegreen', 'crimson']
                        };
                        var chart = new google.visualization.BarChart(document.getElementById('graficaMaestro'));
                        chart.draw(view, options);
                    }
                </script>
                
                <ul class="t-contents">
                    <?php 
                    $sql = "SELECT RP.* ";
                    $sql.=" FROM sat_reportes AS RP ";
                    $sql.=" GROUP BY YEAR(RP.creacionFecha) 
                            ORDER BY RP.creacionFecha ASC";
                    $PSN->query($sql);
                    $num_RES=$PSN->num_rows();
                    while($PSN->next_record()){
                        $anio = date("Y", strtotime($PSN->f('creacionFecha')));
                        echo '<li class="t-content ';
                        if ($anio==date('Y')){ 
                            echo ' selected ';
                        }
                        echo '" > <h2>Gráfica del Año '.$anio."</h2>";
                        echo '<div id="grafica'.$anio.'" class="chart"></div></li>';
                    }
                    ?>
                    <li class="t-content" >
                        <h2>Gráfica de plan maestro</h2>
                        <div id="graficaMaestro" class="chart"></div>
                    </li>
                </ul>
            </div>
        </div>
    <?php 
    }
    ?>
</div>
              
<script>
    function easyTabs() {
  var groups = document.querySelectorAll('.t-container');
  if (groups.length > 0) {
    for (i = 0; i < groups.length; i++) {
      var tabs = groups[i].querySelectorAll('.t-tab');
      for (t = 0; t < tabs.length; t++) {
        tabs[t].setAttribute("index", t+1);
        if (t == 0) tabs[t].className = "t-tab";
      }
      var contents = groups[i].querySelectorAll('.t-content');
      for (c = 0; c < contents.length; c++) {
        contents[c].setAttribute("index", c+1);
        if (c == 0) contents[c].className = "t-content";
      }
    }
    var clicks = document.querySelectorAll('.t-tab');
    for (i = 0; i < clicks.length; i++) {
      clicks[i].onclick = function() {
        var tSiblings = this.parentElement.children;
        for (i = 0; i < tSiblings.length; i++) {
          tSiblings[i].className = "t-tab";
        }
        this.className = "t-tab selected";
        var idx = this.getAttribute("index");
        var cSiblings = this.parentElement.parentElement.querySelectorAll('.t-content');
        for (i = 0; i < cSiblings.length; i++) {
          cSiblings[i].className = "t-content";
          if (cSiblings[i].getAttribute("index") == idx) {
            cSiblings[i].className = "t-content selected";
          }
        }
      };
    }
  }
}


(function() { 
  easyTabs();
})();
</script>
                    
