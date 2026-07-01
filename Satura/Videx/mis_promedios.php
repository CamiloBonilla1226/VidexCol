<?php
// Control de acceso - Solo Facilitadores (tipo 163)
if($_SESSION["perfil"] != 163) {
    ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Acceso Denegado</h4>
                    <p>Esta sección es exclusiva para facilitadores.</p>
                </div>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Configurar fechas por defecto (mes actual)
$fecha_inicio_default = date('Y-m-01'); // Primer día del mes actual
$fecha_fin_default = date('Y-m-t'); // Último día del mes actual

// Obtener fechas del formulario o usar valores por defecto
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : $fecha_inicio_default;
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : $fecha_fin_default;

// Definir escala de evaluación fija
$escala_actual = array(
    'nombre' => 'Escala de Evaluación',
    'excelente_min' => 3.0,
    'bueno_min' => 2.5,
    'regular_min' => 2.0
);

?>

<div class="container-fluid" style="max-width: 98%; padding: 10px;">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary custom-panel">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fas fa-chart-line"></i> Mis Promedios de Mapeo
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <!-- Formulario de Filtro -->
                    <form method="POST" class="form-horizontal" style="margin-bottom: 20px;">
                        <div class="row filter-row">
                            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_inicio" class="control-label filter-label">
                                        <i class="fas fa-calendar-alt"></i> Fecha Inicio:
                                    </label>
                                    <input type="date" class="form-control filter-input" id="fecha_inicio" name="fecha_inicio" 
                                           value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    <label for="fecha_fin" class="control-label filter-label">
                                        <i class="fas fa-calendar-alt"></i> Fecha Fin:
                                    </label>
                                    <input type="date" class="form-control filter-input" id="fecha_fin" name="fecha_fin" 
                                           value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label class="control-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block filter-button">
                                        <i class="fas fa-search"></i> Consultar Mis Promedios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Información del período seleccionado -->
                    <div class="alert alert-info">
                        <i class="fas fa-user"></i> 
                        <strong><?php echo $_SESSION["nombre"]; ?></strong> - Promedios del 
                        <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> 
                        al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong>
                    </div>

                    <!-- Información de la escala actual -->
                    <div class="alert alert-warning">
                        <strong>Escala de Evaluación:</strong>
                        <span class="label label-success">Excelente: 3.0-4.0</span>
                        <span class="label label-warning">Bueno: 2.5-2.9</span>
                        <span class="label label-info">Regular: 2.0-2.4</span>
                        <span class="label label-danger">Por Mejorar: ≤1.9</span>
                    </div>

                    <?php
                    // Conexión a la base de datos
                    $PSN = new DBbase_Sql;
                    
                    // Consulta SQL para obtener promedios del facilitador autenticado
                    // Solo incluir reportes con mapeo (generaciones 1-5)
                    $sql = "SELECT
                                r.id,
                                r.fechaReporte,
                                r.grupoMadre_txt,
                                r.nombreGrupo_txt,
                                r.mapeo_oracion,
                                r.mapeo_companerismo,
                                r.mapeo_adoracion,
                                r.mapeo_biblia,
                                r.mapeo_evangelizar,
                                r.mapeo_cena,
                                r.mapeo_dar,
                                r.mapeo_bautizar,
                                r.mapeo_trabajadores,
                                ROUND(
                                    (r.mapeo_oracion + r.mapeo_companerismo + r.mapeo_adoracion + r.mapeo_biblia +
                                     r.mapeo_evangelizar + r.mapeo_cena + r.mapeo_dar + r.mapeo_bautizar + r.mapeo_trabajadores) / 9, 2
                                ) AS promedio_reporte
                            FROM
                                sat_reportes r
                            WHERE
                                r.creacionUsuario = " . $_SESSION["id"] . "
                                AND r.id_actividad = 1
                                AND r.fechaReporte BETWEEN '".$fecha_inicio."' AND '".$fecha_fin."'
                                AND r.generacionNumero BETWEEN 1 AND 5
                                AND r.mapeo_oracion IS NOT NULL
                            ORDER BY
                                r.fechaReporte DESC";
                    
                    $PSN->query($sql);
                    
                    // Función para determinar categoría según escala
                    function determinarCategoria($promedio, $escala) {
                        if($promedio == 0) {
                            return array(
                                'categoria' => 'sin_datos',
                                'nombre' => 'Sin datos',
                                'color' => 'default'
                            );
                        } elseif($promedio >= $escala['excelente_min']) {
                            return array(
                                'categoria' => 'excelente',
                                'nombre' => 'Excelente',
                                'color' => 'success'
                            );
                        } elseif($promedio >= $escala['bueno_min']) {
                            return array(
                                'categoria' => 'bueno',
                                'nombre' => 'Bueno',
                                'color' => 'warning'
                            );
                        } elseif($promedio >= $escala['regular_min']) {
                            return array(
                                'categoria' => 'regular',
                                'nombre' => 'Regular',
                                'color' => 'info'
                            );
                        } else {
                            return array(
                                'categoria' => 'necesita_mejora',
                                'nombre' => 'Por Mejorar',
                                'color' => 'danger'
                            );
                        }
                    }
                    
                    // Recolectar datos
                    $reportes = array();
                    $suma_promedios = 0;
                    $total_reportes = 0;
                    
                    while($PSN->next_record()) {
                        $promedio = floatval($PSN->f('promedio_reporte'));
                        $categoria = determinarCategoria($promedio, $escala_actual);

                        $reportes[] = array(
                            'id' => $PSN->f('id'),
                            'fecha' => $PSN->f('fechaReporte'),
                            'grupo_madre' => $PSN->f('grupoMadre_txt'),
                            'nombre_grupo' => $PSN->f('nombreGrupo_txt'),
                            'oracion' => $PSN->f('mapeo_oracion'),
                            'companerismo' => $PSN->f('mapeo_companerismo'),
                            'adoracion' => $PSN->f('mapeo_adoracion'),
                            'biblia' => $PSN->f('mapeo_biblia'),
                            'evangelizar' => $PSN->f('mapeo_evangelizar'),
                            'cena' => $PSN->f('mapeo_cena'),
                            'dar' => $PSN->f('mapeo_dar'),
                            'bautizar' => $PSN->f('mapeo_bautizar'),
                            'trabajadores' => $PSN->f('mapeo_trabajadores'),
                            'promedio' => $promedio,
                            'categoria' => $categoria
                        );
                        $suma_promedios += $promedio;
                        $total_reportes++;
                    }
                    
                    if(count($reportes) > 0) {
                        $promedio_general = $suma_promedios / $total_reportes;
                        $categoria_general = determinarCategoria($promedio_general, $escala_actual);
                        ?>
                        
                        <!-- Resumen General -->
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-6">
                                <div class="panel panel-<?php echo $categoria_general['color']; ?>">
                                    <div class="panel-heading text-center">
                                        <h4 style="margin: 10px 0;"><i class="fas fa-chart-line"></i> MI PROMEDIO GENERAL</h4>
                                    </div>
                                    <div class="panel-body text-center">
                                        <h2 style="margin: 15px 0; font-weight: bold;">
                                            <?php echo number_format($promedio_general, 2); ?>
                                        </h2>
                                        <span class="label label-<?php echo $categoria_general['color']; ?>" style="font-size: 14px; padding: 6px 12px;">
                                            <?php echo $categoria_general['nombre']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-info">
                                    <div class="panel-heading text-center">
                                        <h4 style="margin: 10px 0;"><i class="fas fa-file-alt"></i> TOTAL DE REPORTES</h4>
                                    </div>
                                    <div class="panel-body text-center">
                                        <h2 style="margin: 15px 0; font-weight: bold;">
                                            <?php echo $total_reportes; ?>
                                        </h2>
                                        <span class="label label-info" style="font-size: 14px; padding: 6px 12px;">
                                            Reportes en período
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de Reportes Detallada -->
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead class="bg-primary">
                                    <tr>
                                        <th style="color: white;" class="text-center">ID</th>
                                        <th style="color: white;">Fecha</th>
                                        <th style="color: white;">Grupo</th>
                                        <th style="color: white;" class="text-center">Oración</th>
                                        <th style="color: white;" class="text-center">Compañ.</th>
                                        <th style="color: white;" class="text-center">Adorac.</th>
                                        <th style="color: white;" class="text-center">Biblia</th>
                                        <th style="color: white;" class="text-center">Evang.</th>
                                        <th style="color: white;" class="text-center">Cena</th>
                                        <th style="color: white;" class="text-center">Dar</th>
                                        <th style="color: white;" class="text-center">Baut.</th>
                                        <th style="color: white;" class="text-center">Trab.</th>
                                        <th style="color: white;" class="text-center">Promedio</th>
                                        <th style="color: white;" class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach($reportes as $reporte) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $reporte['id']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reporte['grupo_madre']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($reporte['nombre_grupo']); ?></small>
                                            </td>
                                            <td class="text-center"><?php echo $reporte['oracion']; ?></td>
                                            <td class="text-center"><?php echo $reporte['companerismo']; ?></td>
                                            <td class="text-center"><?php echo $reporte['adoracion']; ?></td>
                                            <td class="text-center"><?php echo $reporte['biblia']; ?></td>
                                            <td class="text-center"><?php echo $reporte['evangelizar']; ?></td>
                                            <td class="text-center"><?php echo $reporte['cena']; ?></td>
                                            <td class="text-center"><?php echo $reporte['dar']; ?></td>
                                            <td class="text-center"><?php echo $reporte['bautizar']; ?></td>
                                            <td class="text-center"><?php echo $reporte['trabajadores']; ?></td>
                                            <td class="text-center">
                                                <span class="label label-<?php echo $reporte['categoria']['color']; ?>" style="font-size: 12px; padding: 4px 8px;">
                                                    <?php echo number_format($reporte['promedio'], 2); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="label label-<?php echo $reporte['categoria']['color']; ?>">
                                                    <?php echo $reporte['categoria']['nombre']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <?php
                    } else {
                        ?>
                        <!-- Estado Sin Reportes - Diseño mejorado -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default no-reports-panel" style="margin-top: 20px;">
                                    <div class="panel-body text-center" style="padding: 40px;">
                                        <div style="margin-bottom: 20px;">
                                            <i class="fas fa-file-alt" style="font-size: 64px; color: #ccc;"></i>
                                        </div>
                                        <h3 style="color: #666; margin-bottom: 15px;">No hay reportes en este período</h3>
                                        <p style="color: #999; font-size: 16px; margin-bottom: 20px;">
                                            No tienes reportes registrados del <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> 
                                            al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong>
                                        </p>
                                        
                                        <div class="alert alert-info" style="margin: 20px 0;">
                                            <strong><i class="fas fa-lightbulb"></i> Sugerencias:</strong>
                                            <ul style="text-align: left; margin-top: 10px; margin-bottom: 0;">
                                                <li>Intenta seleccionar un rango de fechas más amplio</li>
                                                <li>Verifica que hayas creado reportes de mapeo en el sistema</li>
                                                <li>Contacta al administrador si crees que hay un error</li>
                                            </ul>
                                        </div>
                                        
                                        <div style="margin-top: 25px;">
                                            <a href="?php=reportar" class="btn btn-primary" style="margin-right: 10px;">
                                                <i class="fas fa-plus"></i> Crear Nuevo Reporte
                                            </a>
                                            <button type="button" class="btn btn-default" onclick="location.reload();">
                                                <i class="fas fa-refresh"></i> Actualizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* === ESTILOS MEJORADOS PARA MIS PROMEDIOS === */

/* Panel principal con gradiente y sombra mejorada */
.custom-panel {
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.custom-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.custom-panel .panel-heading {
    background: linear-gradient(135deg, #425fa5 0%, #334a87 100%) !important;
    border: none !important;
    padding: 20px 25px;
    position: relative;
}

.custom-panel .panel-heading::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.custom-panel .panel-title {
    color: white !important;
    font-size: 18px;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.custom-panel .panel-title i {
    margin-right: 10px;
    font-size: 20px;
    color: #fff;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.custom-panel .panel-body {
    background: #fff;
    padding: 30px 25px;
}

/* Filtros mejorados con efectos modernos */
.filter-row {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    padding: 25px;
    margin: 0 0 25px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(66,95,165,0.1);
    position: relative;
    overflow: hidden;
}

.filter-row::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #425fa5, #334a87);
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-label i {
    color: #425fa5;
    margin-right: 8px;
    font-size: 16px;
}

.filter-input {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-input:focus {
    border-color: #425fa5;
    box-shadow: 0 0 0 3px rgba(66,95,165,0.15), 0 4px 15px rgba(0,0,0,0.1);
    background: #fff;
    transform: translateY(-1px);
}

.filter-button {
    background: linear-gradient(135deg, #425fa5 0%, #334a87 100%);
    border: none;
    border-radius: 10px;
    padding: 14px 20px;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #fff;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 4px 15px rgba(66,95,165,0.4);
    position: relative;
    overflow: hidden;
}

.filter-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s;
}

.filter-button:hover::before {
    left: 100%;
}

.filter-button:hover {
    background: linear-gradient(135deg, #334a87 0%, #283a6b 100%);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(66,95,165,0.5);
}

.filter-button:active {
    transform: translateY(-1px);
}

/* Paneles de resumen - Diseño elegante y minimalista */
.panel-success, .panel-info, .panel-warning, .panel-danger {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    background: #fff;
    border-left: 4px solid transparent;
}

.panel-success {
    border-left-color: #28a745;
}

.panel-info {
    border-left-color: #425fa5;
}

.panel-warning {
    border-left-color: #ffc107;
}

.panel-danger {
    border-left-color: #dc3545;
}

.panel-success:hover, .panel-info:hover, .panel-warning:hover, .panel-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.panel-success .panel-heading {
    background: #fff !important;
    color: #28a745 !important;
    border-bottom: 1px solid #f8f9fa;
}

.panel-info .panel-heading {
    background: #fff !important;
    color: #425fa5 !important;
    border-bottom: 1px solid #f8f9fa;
}

.panel-warning .panel-heading {
    background: #fff !important;
    color: #856404 !important;
    border-bottom: 1px solid #f8f9fa;
}

.panel-danger .panel-heading {
    background: #fff !important;
    color: #dc3545 !important;
    border-bottom: 1px solid #f8f9fa;
}

.panel-success .panel-heading h4,
.panel-info .panel-heading h4,
.panel-warning .panel-heading h4,
.panel-danger .panel-heading h4 {
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.panel-success .panel-body h2,
.panel-info .panel-body h2,
.panel-warning .panel-body h2,
.panel-danger .panel-body h2 {
    color: #2c3e50;
    font-weight: 700;
    margin: 10px 0;
}

.panel-success .panel-body .label,
.panel-info .panel-body .label,
.panel-warning .panel-body .label,
.panel-danger .panel-body .label {
    background: #f8f9fa !important;
    color: #6c757d !important;
    border: 1px solid #dee2e6;
    box-shadow: none;
}

/* Tabla con estilos modernos */
.table {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
}

.table th {
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 15px 12px;
    border: none;
}

.table td {
    font-size: 12px;
    font-weight: 500;
    vertical-align: middle;
    padding: 12px;
    border-top: 1px solid #f8f9fa;
    transition: background-color 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.002);
    transition: all 0.3s ease;
}

.bg-primary th {
    background: linear-gradient(135deg, #425fa5 0%, #334a87 100%) !important;
    color: #fff !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

/* Labels - Diseño minimalista y elegante */
.label {
    display: inline-block;
    min-width: 50px;
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: 1px solid transparent;
    transition: all 0.2s ease;
    box-shadow: none;
}

.label:hover {
    transform: none;
    opacity: 0.9;
}

.label-success {
    background: #d4edda !important;
    color: #155724 !important;
    border-color: #c3e6cb !important;
}

.label-warning {
    background: #fff3cd !important;
    color: #856404 !important;
    border-color: #ffeaa7 !important;
}

.label-info {
    background: #d1ecf1 !important;
    color: #0c5460 !important;
    border-color: #bee5eb !important;
}

.label-danger {
    background: #f8d7da !important;
    color: #721c24 !important;
    border-color: #f5c6cb !important;
}

/* Alertas - Diseño limpio */
.alert {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    font-weight: 500;
    background: #fff;
}

.alert-info {
    background: #f8f9fa;
    color: #495057;
    border-left: 3px solid #425fa5;
}

.alert-warning {
    background: #fffbf0;
    color: #856404;
    border-left: 3px solid #ffc107;
}

/* Panel sin reportes - Diseño elegante */
.no-reports-panel {
    border: 1px solid #e9ecef !important;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    background: #fff;
    transition: all 0.3s ease;
}

.no-reports-panel:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.no-reports-panel .panel-body {
    padding: 40px 30px !important;
}

.no-reports-panel i {
    transition: all 0.3s ease;
    color: #adb5bd;
}

.no-reports-panel:hover i {
    color: #6c757d !important;
}

/* Botones - Diseño profesional */
.btn {
    border-radius: 5px;
    font-weight: 600;
    font-size: 13px;
    padding: 10px 18px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid transparent;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.btn-primary {
    background: #425fa5;
    border-color: #425fa5;
    color: #fff;
}

.btn-primary:hover {
    background: #334a87;
    border-color: #334a87;
}

.btn-default {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
}

.btn-default:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

/* Animaciones de entrada */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.custom-panel {
    animation: slideInUp 0.6s ease-out;
}

/* Responsividad mejorada */
@media (max-width: 768px) {
    .custom-panel .panel-heading {
        padding: 15px 20px;
    }
    
    .custom-panel .panel-title {
        font-size: 16px;
    }
    
    .custom-panel .panel-body {
        padding: 20px 15px;
    }
    
    .filter-row {
        padding: 20px 15px;
        margin: 0 0 20px 0;
    }
    
    .filter-input {
        padding: 12px 15px;
        font-size: 14px;
    }
    
    .filter-button {
        margin-top: 15px;
        font-size: 12px;
        padding: 12px 18px;
    }
    
    .table th, .table td {
        font-size: 10px;
        padding: 8px 6px;
    }
    
    .label {
        font-size: 10px;
        padding: 4px 8px;
        min-width: 40px;
    }
    
    .no-reports-panel .panel-body {
        padding: 30px 20px !important;
    }
    
    .no-reports-panel i {
        font-size: 48px !important;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .table-responsive {
        border: none;
    }
    
    .table th, .table td {
        font-size: 9px;
        padding: 6px 4px;
    }
    
    .filter-label {
        font-size: 12px;
    }
    
    .custom-panel .panel-title {
        font-size: 14px;
    }
}

/* Efectos de scroll suave */
html {
    scroll-behavior: smooth;
}

/* Mejoras en la accesibilidad */
.filter-input:focus,
.btn:focus {
    outline: 2px solid #425fa5;
    outline-offset: 2px;
}

/* Loading states */
.table tbody tr {
    transition: opacity 0.3s ease;
}

/* Print styles */
@media print {
    .filter-row,
    .btn,
    .no-reports-panel .btn {
        display: none !important;
    }
    
    .custom-panel {
        box-shadow: none;
        border: 1px solid #ccc;
    }
    
    .table {
        font-size: 10px;
    }
}
</style>