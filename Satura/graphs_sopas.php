<style type="text/css">
    a.reportes {
        background-color: #87BBF5;
        color: #fff;
    }

    .nav-pills>li>a {
        border-radius: 12px;
        font-weight: 700;
        transition: .2s ease;
    }

    .nav-pills>li.active>a.btnEntrada {
        color: #fff;
        background: linear-gradient(135deg, #2e7d32, #43a047);
        border-color: #2e7d32;
    }

    .nav-pills>li.active>a.btnSalida {
        color: #fff;
        background: linear-gradient(135deg, #1565c0, #1e88e5);
        border-color: #1565c0;
    }

    .dash-title {
        margin: 12px 0 18px;
        font-weight: 800;
    }

    .dash-card {
        background: #fff;
        border: 1px solid #e8edf3;
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, .06);
        padding: 18px;
        margin-bottom: 18px;
    }

    .dash-card h4 {
        margin: 0 0 8px;
        font-size: 16px;
        font-weight: 800;
        color: #1f2937;
    }

    .dash-subtitle {
        margin: 0 0 14px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.4;
    }

    .chart-box {
        width: 100%;
        min-height: 340px;
        position: relative;
    }

    .chart-box.sm {
        min-height: 320px;
    }

    .chart-box.lg {
        min-height: 380px;
    }

    .chart-empty {
        min-height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        border: 1px dashed #d8d8d8;
        border-radius: 12px;
        background: #fafafa;
        color: #666;
        padding: 20px;
        font-weight: 600;
        line-height: 1.5;
    }

    .filter-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 700;
        color: #374151;
    }

    .filters-row>div {
        margin-bottom: 14px;
    }

    .btn-filter,
    .btn-clear {
        width: 100%;
        font-weight: 700;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 18px;
    }

    .grid-col-12 {
        grid-column: span 12;
    }

    .grid-col-8 {
        grid-column: span 8;
    }

    .grid-col-6 {
        grid-column: span 6;
    }

    .grid-col-4 {
        grid-column: span 4;
    }

    .grid-col-3 {
        grid-column: span 3;
    }

    .kpi-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e6edf5;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, .05);
    }

    .kpi-card:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        border-radius: 16px 16px 0 0;
    }

    .kpi-card.kpi-green:before {
        background: #2e7d32;
    }

    .kpi-card.kpi-red:before {
        background: #c62828;
    }

    .kpi-card.kpi-blue:before {
        background: #1565c0;
    }

    .kpi-card.kpi-orange:before {
        background: #ef6c00;
    }

    .kpi-card.kpi-purple:before {
        background: #6a1b9a;
    }

    .kpi-label {
        font-size: 13px;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .kpi-value {
        font-size: 32px;
        line-height: 1;
        font-weight: 800;
        color: #111827;
        margin-bottom: 6px;
    }

    .kpi-help {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
    }

    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .section-head h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: #111827;
    }

    .section-head p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    @media (max-width: 1199px) {
        .grid-col-3 {
            grid-column: span 6;
        }

        .grid-col-4 {
            grid-column: span 6;
        }

        .grid-col-8 {
            grid-column: span 12;
        }
    }

    @media (max-width: 991px) {
        .chart-box {
            min-height: 300px;
        }

        .chart-box.sm {
            min-height: 280px;
        }

        .chart-box.lg {
            min-height: 340px;
        }

        .dash-card {
            padding: 16px;
        }

        .dash-card h4 {
            font-size: 15px;
        }

        .dash-subtitle {
            font-size: 12px;
        }
    }

    @media (max-width: 767px) {
        .nav-pills>li {
            float: none;
            width: 100%;
            margin-bottom: 8px;
        }

        .nav-pills.nav-justified>li>a {
            margin-bottom: 0;
        }

        .grid-col-3,
        .grid-col-4,
        .grid-col-6,
        .grid-col-8,
        .grid-col-12 {
            grid-column: span 12;
        }

        .chart-box {
            min-height: 270px;
        }

        .chart-box.sm {
            min-height: 250px;
        }

        .chart-box.lg {
            min-height: 300px;
        }

        .dash-card {
            padding: 14px;
        }

        .dash-card h4 {
            font-size: 14px;
            line-height: 1.3;
        }

        .dash-subtitle {
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .section-head h3 {
            font-size: 18px;
        }

        .section-head p {
            font-size: 12px;
        }

        .kpi-value {
            font-size: 24px;
        }
    }

    @media (max-width: 575px) {
        .chart-box {
            min-height: 240px;
        }

        .chart-box.sm {
            min-height: 220px;
        }

        .chart-box.lg {
            min-height: 270px;
        }

        .dash-card {
            padding: 12px;
            border-radius: 12px;
        }

        .dash-title {
            font-size: 24px;
        }

        .dash-card h4 {
            font-size: 13px;
        }

        .dash-subtitle {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .kpi-label {
            font-size: 11px;
        }

        .kpi-value {
            font-size: 22px;
        }

        .kpi-help {
            font-size: 11px;
        }

        .nav-pills>li>a {
            font-size: 13px;
            padding: 10px 8px;
        }
    }
</style>

<?php
/*******************************************
 * DASHBOARD: ENTREGA DE DESHIDRATADOS
 * VERSIÓN CORREGIDA
 * - Inventario basado en la lógica real de movimientos
 * - Gráficas más simples y entendibles
 * - Se diferencian entradas, movimientos internos,
 *   entregas reales y transferencias
 *******************************************/

$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
$arrayColores = array(
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
    '#95A5A6',
    '#F39C12',
    '#D35400',
    '#C0392B',
    '#7F8C8D'
);

$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$PSN3 = new DBbase_Sql;
$PSN4 = new DBbase_Sql;
$PSN5 = new DBbase_Sql;

/* ===== HELPERS ===== */
function req_num($key, $default = 0)
{
    if (isset($_REQUEST[$key]) && soloNumeros($_REQUEST[$key]) !== "") {
        return (int) soloNumeros($_REQUEST[$key]);
    }
    return $default;
}

function req_str($key, $default = "")
{
    if (isset($_REQUEST[$key]) && $_REQUEST[$key] !== "") {
        return eliminarInvalidos($_REQUEST[$key]);
    }
    return $default;
}

function req_date($key, $default)
{
    if (isset($_REQUEST[$key]) && eliminarInvalidos($_REQUEST[$key]) !== "") {
        return eliminarInvalidos($_REQUEST[$key]);
    }
    return $default;
}

function qval($v)
{
    return "'" . addslashes($v) . "'";
}

function fmt_num($n)
{
    return number_format((float) $n, 0, ',', '.');
}

/* ===== PERMISOS ===== */
$sql = "SELECT idMenu
        FROM usuarios_menu_graphs
        WHERE idMenu IN (1,16)
          AND idUsuario = '" . $_SESSION["id"] . "'";
$PSN->query($sql);

if ($PSN->num_rows() == 0) {
    die("NO está autorizado a ver esta gráfica.");
}

/* ===== FILTROS ===== */
$fecha_actual = date("Y-m-d");
$FechaFin = req_date("FechaFin", date("Y-m-d"));
$FechaIni = isset($_REQUEST["FechaIni"]) && $_REQUEST["FechaIni"] != ""
    ? $_REQUEST["FechaIni"]
    : "";

if ($FechaIni > $FechaFin) {
    $tmp = $FechaIni;
    $FechaIni = $FechaFin;
    $FechaFin = $tmp;
}

$pais = req_num("pais", 0);
$departamento = req_num("departamento", 0);
$Depto = req_str("Depto", "...");
$Facilitador = req_num("Facilitador", 0);

if ($_SESSION["perfil"] == 163) {
    $Facilitador = (int) $_SESSION["id"];
}

/* ===== CARGA DEPARTAMENTOS ===== */
$departamentosOptions = '<option value="0">Seleccione el departamento</option>';

if ($pais > 0) {
    $idSecDepto = ($pais == 1) ? 36 : 38;

    $sqlDepto = "SELECT id, descripcion
                 FROM categorias
                 WHERE idSec = " . qval($idSecDepto) . "
                 ORDER BY descripcion ASC";
    $PSN3->query($sqlDepto);

    if ($PSN3->num_rows() > 0) {
        while ($PSN3->next_record()) {
            $idDep = (int) $PSN3->f('id');
            $nomDep = $PSN3->f('descripcion');
            $sel = ($departamento == $idDep) ? 'selected="selected"' : '';

            if ($departamento == $idDep && ($Depto == "" || $Depto == "...")) {
                $Depto = $nomDep;
            }

            $departamentosOptions .= '<option value="' . $idDep . '" ' . $sel . '>' . htmlspecialchars($nomDep) . '</option>';
        }
    }
}

/* =========================================================
 * FILTROS REALES INVENTARIO
 * ========================================================= */
$FiltroFechaLugar = " WHERE inventario.Estado IN (0,1) ";

if ($pais != 0) {
    $FiltroFechaLugar .= " AND inventario.Pais = '" . $pais . "'";
}

if ($departamento != 0) {
    $FiltroFechaLugar .= " AND inventario.Departamento = '" . $departamento . "'";
}

if ($FechaIni != "") {
    $FiltroFechaLugar .= " AND inventario.Fecha >= '" . $FechaIni . "'";
}
$FiltroFechaLugar .= " AND inventario.Fecha <= '" . $FechaFin . "'";

$esVistaFacilitador = ($Facilitador != 0);
$saldoInicialFacilitador = array(
    'tipo1' => 0,
    'tipo2' => 0
);

if ($esVistaFacilitador && $FechaIni != "") {
    $FiltroSaldoInicialFac = " WHERE inventario.Estado IN (0,1) ";

    if ($pais != 0) {
        $FiltroSaldoInicialFac .= " AND inventario.Pais = '" . $pais . "'";
    }

    if ($departamento != 0) {
        $FiltroSaldoInicialFac .= " AND inventario.Departamento = '" . $departamento . "'";
    }

    $FiltroSaldoInicialFac .= " AND inventario.Fecha < '" . $FechaIni . "'";

    $sql = "
        SELECT
            (
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
                +
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "'
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
                +
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE
                    WHEN (
                        inventario.Tipo IN (2,3,4,5)
                        AND inventario.IdUsuario = " . $Facilitador . "
                        AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                    )
                    OR (
                        inventario.Tipo = 7
                        AND inventario.IdUsuario = " . $Facilitador . "
                    )
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
            ) AS saldo_tipo1,

            (
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
                +
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "'
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
                +
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE
                    WHEN (
                        inventario.Tipo IN (2,3,4,5)
                        AND inventario.IdUsuario = " . $Facilitador . "
                        AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                    )
                    OR (
                        inventario.Tipo = 7
                        AND inventario.IdUsuario = " . $Facilitador . "
                    )
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
            ) AS saldo_tipo2
        FROM inventario
        " . $FiltroSaldoInicialFac . "
    ";

    $PSN4->query($sql);
    if ($PSN4->num_rows() > 0) {
        $PSN4->next_record();
        $saldoInicialFacilitador['tipo1'] = (float) $PSN4->f('saldo_tipo1');
        $saldoInicialFacilitador['tipo2'] = (float) $PSN4->f('saldo_tipo2');
    }
}

/* ===== SUBQUERY PARA CARACTERIZACIÓN FILTRADA ===== */
if ($esVistaFacilitador) {
    $subBenefFiltrados = "
        SELECT DISTINCT inventario.Beneficiario
        FROM inventario
        " . $FiltroFechaLugar . "
          AND inventario.Beneficiario IS NOT NULL
          AND inventario.Beneficiario <> ''
          AND inventario.IdUsuario = " . $Facilitador . "
          AND inventario.Tipo IN (4,5)
    ";
} else {
    $subBenefFiltrados = "
        SELECT DISTINCT inventario.Beneficiario
        FROM inventario
        " . $FiltroFechaLugar . "
          AND inventario.Beneficiario IS NOT NULL
          AND inventario.Beneficiario <> ''
          AND inventario.Tipo IN (4,5)
    ";
}

/* ===== FLAGS ===== */
$Graphp01 = 1;
$Graphp02 = 1;
$Graphp03 = 1;
$Graphp04 = 1;
$Graphp05 = 1;
$Graphp06 = 1;
$Graphp07 = 1;
$Graphp08 = 1;
$Graphp09 = 1;
$Graphp10 = 1;
$Graphp11 = 1;
$Graphp12 = 1;

/* =========================================================
 * KPIs INVENTARIO
 * ========================================================= */
$resumenInv = array(
    'abastecido' => 0,
    'entregado' => 0,
    'facilitadores' => 0,
    'beneficiarios' => 0,
    'transferencias' => 0,
    'saldo' => 0
);

if ($esVistaFacilitador) {
    $sql = "
        SELECT
            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . "
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS entradas_propias,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "'
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS recibido_central,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . "
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS transferencias_entrantes,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 2
                  AND inventario.IdUsuario = " . $Facilitador . "
                  AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS entregado_facilitadores,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo IN (3,4,5)
                  AND inventario.IdUsuario = " . $Facilitador . "
                  AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS entregado_beneficiarios,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 7 AND inventario.IdUsuario = " . $Facilitador . "
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS transferencias_salientes
        FROM inventario
        " . $FiltroFechaLugar . "
    ";

    $PSN4->query($sql);
    if ($PSN4->num_rows() > 0) {
        $PSN4->next_record();

        $entradasPropias = (float) $PSN4->f('entradas_propias');
        $recibidoCentral = (float) $PSN4->f('recibido_central');
        $transferIn = (float) $PSN4->f('transferencias_entrantes');
        $movFac = (float) $PSN4->f('entregado_facilitadores');
        $movBen = (float) $PSN4->f('entregado_beneficiarios');
        $transferOut = (float) $PSN4->f('transferencias_salientes');

        $resumenInv['abastecido'] = $entradasPropias + $recibidoCentral + $transferIn;
        $resumenInv['entregado'] = $movFac + $movBen;
        $resumenInv['facilitadores'] = $movFac;
        $resumenInv['beneficiarios'] = $movBen;
        $resumenInv['transferencias'] = $transferOut;
        $resumenInv['saldo'] = $saldoInicialFacilitador['tipo1'] + $saldoInicialFacilitador['tipo2']
            + ($entradasPropias + $recibidoCentral + $transferIn)
            - $movBen - $transferOut - $movFac;
    }
} else {
    $sql = "
        SELECT
            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 1
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS abastecido,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 2
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS facilitadores,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo IN (3,4,5)
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS beneficiarios,

            IFNULL(SUM(CASE
                WHEN inventario.Tipo = 7
                THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS transferencias
        FROM inventario
        " . $FiltroFechaLugar . "
    ";

    $PSN4->query($sql);
    if ($PSN4->num_rows() > 0) {
        $PSN4->next_record();
        $resumenInv['abastecido'] = (float) $PSN4->f('abastecido');
        $resumenInv['entregado'] = (float) $PSN4->f('facilitadores') + (float) $PSN4->f('beneficiarios');
        $resumenInv['facilitadores'] = (float) $PSN4->f('facilitadores');
        $resumenInv['beneficiarios'] = (float) $PSN4->f('beneficiarios');
        $resumenInv['transferencias'] = (float) $PSN4->f('transferencias');

        $resumenInv['saldo'] = $resumenInv['abastecido'] - $resumenInv['beneficiarios'];
    }
}

/* =========================================================
 * KPIs CARACTERIZACIÓN
 * ========================================================= */
$resumenCar = array(
    'beneficiarios' => 0,
    'ninos' => 0,
    'adolescentes' => 0,
    'adultos' => 0,
    'discapacitados' => 0
);

$sql = "SELECT
            COUNT(*) AS beneficiarios,
            IFNULL(SUM(TotalNinos),0) AS ninos,
            IFNULL(SUM(TotalAdolescentes),0) AS adolescentes,
            IFNULL(SUM(TotalAdultos),0) AS adultos,
            IFNULL(SUM(TotalDiscapacitados),0) AS discapacitados
        FROM beneficiarios
        WHERE IdBeneficiado IN ($subBenefFiltrados)";
$PSN5->query($sql);
if ($PSN5->num_rows() > 0) {
    $PSN5->next_record();
    $resumenCar['beneficiarios'] = (int) $PSN5->f('beneficiarios');
    $resumenCar['ninos'] = (float) $PSN5->f('ninos');
    $resumenCar['adolescentes'] = (float) $PSN5->f('adolescentes');
    $resumenCar['adultos'] = (float) $PSN5->f('adultos');
    $resumenCar['discapacitados'] = (float) $PSN5->f('discapacitados');
}

/* =========================================================
 * GRAPH 01: RESUMEN GENERAL DE MOVIMIENTOS
 * ========================================================= */
$nombreGrafica = "Resumen general de movimientos";
$datosGraph01 = array();
$datosGraph01[] = '["Movimiento", "Total", { role: "style" }]';
$datosGraph01[] = '["Abastecido", ' . (float) $resumenInv['abastecido'] . ', "#2e7d32"]';
$datosGraph01[] = '["A facilitadores", ' . (float) $resumenInv['facilitadores'] . ', "#1565c0"]';
$datosGraph01[] = '["A beneficiarios", ' . (float) $resumenInv['beneficiarios'] . ', "#ef6c00"]';
$datosGraph01[] = '["Transferido", ' . (float) $resumenInv['transferencias'] . ', "#8e24aa"]';
$Graphp01 = 0;

/* =========================================================
 * GRAPH 02: MOVIMIENTO POR PRODUCTO
 * ========================================================= */
$nombreGrafica2 = "Movimiento por producto";

if ($esVistaFacilitador) {
    $sql = "
        SELECT
            'Mix de vegetales 1 lb' AS Producto,
            IFNULL(SUM(CASE
                WHEN (inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . ")
                  OR (inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "')
                  OR (inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . ")
                THEN inventario.TipoSopa1 ELSE 0 END),0) AS Recibido,
            IFNULL(SUM(CASE
                WHEN inventario.Tipo IN (2,3,4,5)
                  AND inventario.IdUsuario = " . $Facilitador . "
                  AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                THEN inventario.TipoSopa1 ELSE 0 END),0) AS Salida
        FROM inventario
        " . $FiltroFechaLugar . "

        UNION ALL

        SELECT
            'Mix de vegetales 3 lb' AS Producto,
            IFNULL(SUM(CASE
                WHEN (inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . ")
                  OR (inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "')
                  OR (inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . ")
                THEN inventario.TipoSopa2 ELSE 0 END),0) AS Recibido,
            IFNULL(SUM(CASE
                WHEN inventario.Tipo IN (2,3,4,5)
                  AND inventario.IdUsuario = " . $Facilitador . "
                  AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
                THEN inventario.TipoSopa2 ELSE 0 END),0) AS Salida
        FROM inventario
        " . $FiltroFechaLugar . "
    ";
} else {
    $sql = "
        SELECT
            'Mix de vegetales 1 lb' AS Producto,
            IFNULL(SUM(CASE WHEN inventario.Tipo = 1 THEN inventario.TipoSopa1 ELSE 0 END),0) AS Recibido,
            IFNULL(SUM(CASE WHEN inventario.Tipo IN (3,4,5) THEN inventario.TipoSopa1 ELSE 0 END),0) AS Salida
        FROM inventario
        " . $FiltroFechaLugar . "

        UNION ALL

        SELECT
            'Mix de vegetales 3 lb' AS Producto,
            IFNULL(SUM(CASE WHEN inventario.Tipo = 1 THEN inventario.TipoSopa2 ELSE 0 END),0) AS Recibido,
            IFNULL(SUM(CASE WHEN inventario.Tipo IN (3,4,5) THEN inventario.TipoSopa2 ELSE 0 END),0) AS Salida
        FROM inventario
        " . $FiltroFechaLugar . "
    ";
}

$datosGraph02 = array();
$datosGraph02[] = '["Producto", "Recibido", "Salida"]';
$PSN->query($sql);
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph02[] = "['" . $PSN->f('Producto') . "', " . (float) $PSN->f('Recibido') . ", " . (float) $PSN->f('Salida') . "]";
    }
    $Graphp02 = 0;
}

/* =========================================================
 * GRAPH 03: ENTREGAS REALES POR MODALIDAD
 * ========================================================= */
$nombreGrafica3 = "Entregas reales por modalidad";
$sql = "
    SELECT 'Evangelismo' AS Tipo, IFNULL(SUM(CASE WHEN inventario.Tipo = 3 THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS Total
    FROM inventario
    " . $FiltroFechaLugar . "
    " . ($esVistaFacilitador ? " AND inventario.IdUsuario = " . $Facilitador : "") . "

    UNION ALL

    SELECT '1ra entrega' AS Tipo, IFNULL(SUM(CASE WHEN inventario.Tipo = 4 THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS Total
    FROM inventario
    " . $FiltroFechaLugar . "
    " . ($esVistaFacilitador ? " AND inventario.IdUsuario = " . $Facilitador : "") . "

    UNION ALL

    SELECT '2da entrega / otra entrega' AS Tipo, IFNULL(SUM(CASE WHEN inventario.Tipo = 5 THEN inventario.TipoSopa1 + inventario.TipoSopa2 ELSE 0 END),0) AS Total
    FROM inventario
    " . $FiltroFechaLugar . "
    " . ($esVistaFacilitador ? " AND inventario.IdUsuario = " . $Facilitador : "") . "
";
$datosGraph03 = array();
$datosGraph03[] = '["Modalidad", "Total"]';
$PSN->query($sql);
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph03[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . "]";
    }
    $Graphp03 = 0;
}

/* =========================================================
 * GRAPH 04: CARACTERIZACIÓN
 * ========================================================= */
$nombreGrafica4 = "Grupo poblacional atendido";
$sql = "SELECT 'Niños' AS Tipo, IFNULL(SUM(TotalNinos),0) AS Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados)
        UNION ALL
        SELECT 'Niños Soy Satura' AS Tipo, IFNULL(SUM(TotalNinosBeneficiados),0) AS Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados)
        UNION ALL
        SELECT 'Adolescentes' AS Tipo, IFNULL(SUM(TotalAdolescentes),0) AS Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados)
        UNION ALL
        SELECT 'Adultos' AS Tipo, IFNULL(SUM(TotalAdultos),0) AS Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados)
        UNION ALL
        SELECT 'Discapacitados' AS Tipo, IFNULL(SUM(TotalDiscapacitados),0) AS Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados)";
$datosGraph04 = array();
$datosGraph04[] = '["Grupo", "Total", { role: "style" }]';
$PSN->query($sql);
$countC = 0;
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph04[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . ", '" . $arrayColores[$countC % count($arrayColores)] . "']";
        $countC++;
    }
    $Graphp04 = 0;
}

/* =========================================================
 * GRAPH 05
 * ========================================================= */
$nombreGrafica5 = "Discapacidades reportadas";
$sql = "SELECT 'Movilidad' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Discapacidad LIKE '%Movilidad%'
        UNION ALL
        SELECT 'Mental' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Discapacidad LIKE '%Mental%'
        UNION ALL
        SELECT 'Auditiva' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Discapacidad LIKE '%Auditiva%'
        UNION ALL
        SELECT 'Visual' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Discapacidad LIKE '%Visual%'
        UNION ALL
        SELECT 'Otras' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Discapacidad LIKE '%Otras%'
        UNION ALL
        SELECT 'Ninguna' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND (Discapacidad LIKE '%Ninguna%' OR Discapacidad IS NULL OR Discapacidad = '')";
$datosGraph05 = array();
$datosGraph05[] = "['Tipo', 'Total', { role: 'style' }]";
$PSN->query($sql);
$countC = 0;
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph05[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . ", '" . $arrayColores[$countC % count($arrayColores)] . "']";
        $countC++;
    }
    $Graphp05 = 0;
}

/* =========================================================
 * GRAPH 06
 * ========================================================= */
$nombreGrafica6 = "Ingresos mensuales";
$sql = "SELECT 'No recibe ingresos' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Ingresos = 1
        UNION ALL
        SELECT 'Menos de un salario mínimo' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Ingresos = 2
        UNION ALL
        SELECT 'Un salario mínimo' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Ingresos = 3
        UNION ALL
        SELECT 'Más de un salario mínimo' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Ingresos = 4
        UNION ALL
        SELECT 'No sabe' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Ingresos = 5";
$datosGraph06 = array();
$datosGraph06[] = "['Tipo', 'Total', { role: 'style' }]";
$PSN->query($sql);
$countC = 2;
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph06[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . ", '" . $arrayColores[$countC % count($arrayColores)] . "']";
        $countC += 2;
    }
    $Graphp06 = 0;
}

/* =========================================================
 * GRAPH 07
 * ========================================================= */
$nombreGrafica7 = "Comidas no consumidas";
$sql = "SELECT 'Desayuno' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND ComidaNoConsumida LIKE '%Desayuno%'
        UNION ALL
        SELECT 'Almuerzo' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND ComidaNoConsumida LIKE '%Almuerzo%'
        UNION ALL
        SELECT 'Cena' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND ComidaNoConsumida LIKE '%Cena%'
        UNION ALL
        SELECT 'Todas' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND ComidaNoConsumida LIKE '%Todas%'";
$datosGraph07 = array();
$datosGraph07[] = "['Tipo', 'Total', { role: 'style' }]";
$PSN->query($sql);
$countC = 4;
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph07[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . ", '" . $arrayColores[$countC % count($arrayColores)] . "']";
        $countC += 2;
    }
    $Graphp07 = 0;
}

/* =========================================================
 * GRAPH 08
 * ========================================================= */
$nombreGrafica8 = "Problemáticas reportadas";
$sql = "SELECT 'Suicidio' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Suicidio%'
        UNION ALL
        SELECT 'Violencia' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Violencia%'
        UNION ALL
        SELECT 'Abuso' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Abuso%'
        UNION ALL
        SELECT 'Desaparición' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Desaparicion%'
        UNION ALL
        SELECT 'Desplazamiento' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Desplazamiento%'
        UNION ALL
        SELECT 'Tráfico' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND Situaciones LIKE '%Trafico%'
        UNION ALL
        SELECT 'Ninguna' Tipo, COUNT(*) Total FROM beneficiarios WHERE IdBeneficiado IN ($subBenefFiltrados) AND (Situaciones LIKE '%Ninguna%' OR Situaciones IS NULL OR Situaciones = '')";
$datosGraph08 = array();
$datosGraph08[] = "['Tipo', 'Total', { role: 'style' }]";
$PSN->query($sql);
$countC = 1;
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph08[] = "['" . $PSN->f('Tipo') . "', " . (float) $PSN->f('Total') . ", '" . $arrayColores[$countC % count($arrayColores)] . "']";
        $countC += 3;
    }
    $Graphp08 = 0;
}

/* =========================================================
 * GRAPH 09: ENTREGAS A BENEFICIARIOS POR DONANTE
 * ========================================================= */
$nombreGrafica9 = "Entregas a beneficiarios por donante";

$sql = "
    SELECT
        CASE
            WHEN inventario.Donante1 > 0 THEN 'Satura Colombia'
            WHEN inventario.Donante2 > 0 THEN 'Otros'
            ELSE 'Sin definir'
        END AS Donante,
        IFNULL(SUM(inventario.TipoSopa1 + inventario.TipoSopa2),0) AS Salida
    FROM inventario
    " . $FiltroFechaLugar . "
      AND inventario.Tipo IN (3,4,5)
      AND (inventario.Donante1 > 0 OR inventario.Donante2 > 0)
      " . ($esVistaFacilitador ? " AND inventario.IdUsuario = " . $Facilitador : "") . "
    GROUP BY Donante
    ORDER BY Donante ASC
";
$datosGraph09 = array();
$datosGraph09[] = '["Donante", "Entregado"]';
$PSN->query($sql);
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph09[] = "['" . $PSN->f('Donante') . "', " . (float) $PSN->f('Salida') . "]";
    }
    $Graphp09 = 0;
}

/* =========================================================
 * GRAPH 10: SALDO NETO POR PRODUCTO
 * ========================================================= */
$nombreGrafica10 = "Saldo neto por producto";

if ($esVistaFacilitador) {
    $sql = "
        SELECT
            'Mix de vegetales 1 lb' AS Producto,
            (
                " . $saldoInicialFacilitador['tipo1'] . "
                +
                IFNULL(SUM(CASE
                    WHEN (inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . ")
                      OR (inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "')
                      OR (inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . ")
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo IN (2,3,4,5,7) AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa1 ELSE 0 END),0)
            ) AS Saldo
        FROM inventario
        " . $FiltroFechaLugar . "

        UNION ALL

        SELECT
            'Mix de vegetales 3 lb' AS Producto,
            (
                " . $saldoInicialFacilitador['tipo2'] . "
                +
                IFNULL(SUM(CASE
                    WHEN (inventario.Tipo = 1 AND inventario.IdUsuario = " . $Facilitador . ")
                      OR (inventario.Tipo = 2 AND inventario.Facilitador = '" . $Facilitador . "')
                      OR (inventario.Tipo = 8 AND inventario.IdUsuario = " . $Facilitador . ")
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE
                    WHEN inventario.Tipo IN (2,3,4,5,7) AND inventario.IdUsuario = " . $Facilitador . "
                    THEN inventario.TipoSopa2 ELSE 0 END),0)
            ) AS Saldo
        FROM inventario
        " . $FiltroFechaLugar . "
    ";
} else {
    $sql = "
        SELECT
            'Mix de vegetales 1 lb' AS Producto,
            (
                IFNULL(SUM(CASE WHEN inventario.Tipo = 1 THEN inventario.TipoSopa1 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE WHEN inventario.Tipo IN (3,4,5) THEN inventario.TipoSopa1 ELSE 0 END),0)
            ) AS Saldo
        FROM inventario
        " . $FiltroFechaLugar . "

        UNION ALL

        SELECT
            'Mix de vegetales 3 lb' AS Producto,
            (
                IFNULL(SUM(CASE WHEN inventario.Tipo = 1 THEN inventario.TipoSopa2 ELSE 0 END),0)
                -
                IFNULL(SUM(CASE WHEN inventario.Tipo IN (3,4,5) THEN inventario.TipoSopa2 ELSE 0 END),0)
            ) AS Saldo
        FROM inventario
        " . $FiltroFechaLugar . "
    ";
}

$datosGraph10 = array();
$datosGraph10[] = '["Producto", "Saldo neto"]';
$PSN->query($sql);
if ($PSN->num_rows() > 0) {
    while ($PSN->next_record()) {
        $datosGraph10[] = "['" . $PSN->f('Producto') . "', " . (float) $PSN->f('Saldo') . "]";
    }
    $Graphp10 = 0;
}

/* =========================================================
 * GRAPH 11: ENTRADAS POR FECHA PARA FACILITADOR
 * ========================================================= */
$nombreGrafica11 = "Entradas por fecha del facilitador";
$datosGraph11 = array();
$datosGraph11[] = '["Fecha", "Mix de vegetales 1 lb", "Mix de vegetales 3 lb"]';

if ($esVistaFacilitador) {
    $sql = "
        SELECT
            inventario.Fecha,
            IFNULL(SUM(inventario.TipoSopa1), 0) AS TipoSopa1,
            IFNULL(SUM(inventario.TipoSopa2), 0) AS TipoSopa2
        FROM inventario
        " . $FiltroFechaLugar . "
          AND inventario.Tipo = 2
          AND inventario.Facilitador = '" . $Facilitador . "'
        GROUP BY inventario.Fecha
        ORDER BY inventario.Fecha ASC
    ";

    $PSN->query($sql);
    if ($PSN->num_rows() > 0) {
        while ($PSN->next_record()) {
            $datosGraph11[] = "['" . $PSN->f('Fecha') . "', " . (float) $PSN->f('TipoSopa1') . ", " . (float) $PSN->f('TipoSopa2') . "]";
        }
        $Graphp11 = 0;
    }
}

/* =========================================================
 * GRAPH 12: SALIDAS POR FECHA PARA FACILITADOR
 * ========================================================= */
$nombreGrafica12 = "Salidas por fecha del facilitador";
$datosGraph12 = array();
$datosGraph12[] = '["Fecha", "Mix de vegetales 1 lb", "Mix de vegetales 3 lb"]';

if ($esVistaFacilitador) {
    $sql = "
        SELECT
            inventario.Fecha,
            IFNULL(SUM(inventario.TipoSopa1), 0) AS TipoSopa1,
            IFNULL(SUM(inventario.TipoSopa2), 0) AS TipoSopa2
        FROM inventario
        " . $FiltroFechaLugar . "
          AND inventario.Tipo IN (2,3,4,5)
          AND (inventario.Donante1 = 1 OR inventario.Donante2 = 1)
          AND inventario.IdUsuario = " . $Facilitador . "
        GROUP BY inventario.Fecha
        ORDER BY inventario.Fecha ASC
    ";

    $PSN->query($sql);
    if ($PSN->num_rows() > 0) {
        while ($PSN->next_record()) {
            $datosGraph12[] = "['" . $PSN->f('Fecha') . "', " . (float) $PSN->f('TipoSopa1') . ", " . (float) $PSN->f('TipoSopa2') . "]";
        }
        $Graphp12 = 0;
    }
}
?>

<div class="container">
    <form action="index.php" method="get" name="form1" id="formFiltrosSopas" class="form-horizontal">
        <input type="hidden" name="doc" value="graphs_sopas" />

        <h2 class="alert alert-info text-center dash-title">Entrega de Deshidratados</h2>

        <div class="dash-card">
            <h4>Filtros</h4>

            <div class="row filters-row">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="filter-label">Facilitador:</label>
                    <?php if ($_SESSION["perfil"] != 163) { ?>
                        <select name="Facilitador" class="form-control">
                            <option value="0">Ver todos</option>
                            <?php
                            $sql = "SELECT * FROM usuario ORDER BY nombre ASC";
                            $PSN2->query($sql);
                            if ($PSN2->num_rows() > 0) {
                                while ($PSN2->next_record()) {
                                    ?>
                                    <option value="<?= $PSN2->f('id'); ?>" <?php if ($Facilitador == $PSN2->f('id')) { ?>selected="selected" <?php } ?>>
                                        <?= $PSN2->f('nombre'); ?>
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    <?php } else { ?>
                        <input class="form-control" value="Solo mis registros" disabled>
                    <?php } ?>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="filter-label">País:</label>
                    <select name="pais" id="pais" class="form-control">
                        <option value="0">Seleccione el País</option>
                        <option value="1" <?php if ($pais == 1) { ?>selected="selected" <?php } ?>>Colombia</option>
                        <option value="2" <?php if ($pais == 2) { ?>selected="selected" <?php } ?>>Venezuela</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="filter-label">Departamento:</label>
                    <input type="hidden" name="Depto" id="Depto" value="<?= htmlspecialchars($Depto); ?>" />
                    <select name="departamento" id="departamento" class="form-control">
                        <?= $departamentosOptions; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="filter-label">Fecha Inicial:</label>
                    <input type="date" name="FechaIni" id="FechaIni" value="<?= $FechaIni; ?>" class="form-control" />
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="filter-label">Fecha Final:</label>
                    <input type="date" name="FechaFin" id="FechaFin" value="<?= $FechaFin; ?>" class="form-control" />
                </div>

                <div class="col-lg-1 col-md-4 col-sm-6">
                    <label class="filter-label">&nbsp;</label>
                    <input type="submit" value="Filtrar" class="btn btn-success btn-filter" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 text-right">
                    <a href="index.php?doc=graphs_sopas" class="btn btn-default btn-clear">Limpiar filtros</a>
                </div>
            </div>
        </div>
    </form>

    <?php if ($Graphp01 == 1 && $Graphp02 == 1 && $Graphp03 == 1 && $Graphp04 == 1 && $Graphp05 == 1 && $Graphp06 == 1 && $Graphp07 == 1 && $Graphp08 == 1 && $Graphp09 == 1 && $Graphp10 == 1) { ?>
        <div class="dash-card">
            <h5 class="alert alert-warning text-center" style="margin:0;">No se ha encontrado ningún registro para el rango
                de fechas seleccionado.</h5>
        </div>
    <?php } ?>

    <div class="dash-card">
        <ul class="nav nav-pills nav-justified" role="tablist">
            <li role="presentation" class="active">
                <a class="reportes btnEntrada" href="#InventarioG" aria-controls="InventarioG" role="tab"
                    data-toggle="tab">Inventario</a>
            </li>
            <li role="presentation">
                <a class="reportes btnSalida" href="#Caracterizacion" aria-controls="Caracterizacion" role="tab"
                    data-toggle="tab">Caracterización</a>
            </li>
        </ul>

        <div class="tab-content" id="tabSopasContentGra" style="margin-top:16px;">

            <!-- INVENTARIO -->
            <div role="tabpanel" class="tab-pane fade in active" id="InventarioG">
                <div class="section-head">
                    <div>
                        <h3>Dashboard de Inventario</h3>
                        <p>Resumen simple del inventario: abastecimiento, movimientos internos, entregas reales y saldo
                            neto.</p>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="grid-col-3">
                        <div class="kpi-card kpi-green">
                            <div class="kpi-label">Abastecido</div>
                            <div class="kpi-value"><?= fmt_num($resumenInv['abastecido']); ?></div>
                            <div class="kpi-help">Entradas reales al inventario en el período.</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-blue">
                            <div class="kpi-label">A facilitadores</div>
                            <div class="kpi-value"><?= fmt_num($resumenInv['facilitadores']); ?></div>
                            <div class="kpi-help">Movimientos entregados a facilitadores.</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-orange">
                            <div class="kpi-label">Unidades entregadas</div>
                            <div class="kpi-value"><?= fmt_num($resumenInv['entregado']); ?></div>
                            <div class="kpi-help">Entregas reales a la población atendida (Beneficiarios).</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-purple">
                            <div class="kpi-label">Transferido</div>
                            <div class="kpi-value"><?= fmt_num($resumenInv['transferencias']); ?></div>
                            <div class="kpi-help">Transferencias salientes registradas.</div>
                        </div>
                    </div>

                    <div class="grid-col-12">
                        <div class="kpi-card kpi-red">
                            <div class="kpi-label">Saldo neto</div>
                            <div class="kpi-value"><?= fmt_num($resumenInv['saldo']); ?></div>
                            <div class="kpi-help">
                                <?php if ($esVistaFacilitador) { ?>
                                    Lo disponible para el facilitador según lo recibido menos lo entregado y transferido.
                                <?php } else { ?>
                                    Stock neto global: entradas externas menos entregas reales a beneficiarios.
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="grid-col-8">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica; ?></h4>
                            <p class="dash-subtitle">Muestra los cuatro movimientos clave del inventario en el período
                                seleccionado.</p>
                            <div id="graph01" class="chart-box lg"></div>
                        </div>
                    </div>

                    <div class="grid-col-4">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica3; ?></h4>
                            <p class="dash-subtitle">Separa las entregas reales según el tipo de atención registrada.
                            </p>
                            <div id="graph03" class="chart-box sm"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica2; ?></h4>
                            <p class="dash-subtitle">Compara por producto lo recibido frente a lo que salió.</p>
                            <div id="graph02" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica9; ?></h4>
                            <p class="dash-subtitle">Indica qué donante financió más entregas reales a beneficiarios.
                            </p>
                            <div id="graph09" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-12">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica10; ?></h4>
                            <p class="dash-subtitle">Saldo neto disponible por cada presentación del producto.</p>
                            <div id="graph10" class="chart-box sm"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica11; ?></h4>
                            <p class="dash-subtitle">Muestra cuanto recibio el facilitador en cada fecha del rango filtrado.</p>
                            <div id="graph11" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica12; ?></h4>
                            <p class="dash-subtitle">Muestra cuanto salio desde el facilitador en cada fecha del rango filtrado.</p>
                            <div id="graph12" class="chart-box"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARACTERIZACIÓN -->
            <div role="tabpanel" class="tab-pane fade" id="Caracterizacion">
                <div class="section-head">
                    <div>
                        <h3>Dashboard de Caracterización</h3>
                        <p>Perfil poblacional, discapacidad, ingresos, alimentación y problemáticas reportadas.</p>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="grid-col-3">
                        <div class="kpi-card kpi-purple">
                            <div class="kpi-label">Beneficiarios</div>
                            <div class="kpi-value"><?= fmt_num($resumenCar['beneficiarios']); ?></div>
                            <div class="kpi-help">Personas caracterizadas con el filtro actual.</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-green">
                            <div class="kpi-label">Niños</div>
                            <div class="kpi-value"><?= fmt_num($resumenCar['ninos']); ?></div>
                            <div class="kpi-help">Total de niños registrados.</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-blue">
                            <div class="kpi-label">Adolescentes</div>
                            <div class="kpi-value"><?= fmt_num($resumenCar['adolescentes']); ?></div>
                            <div class="kpi-help">Total de adolescentes reportados.</div>
                        </div>
                    </div>

                    <div class="grid-col-3">
                        <div class="kpi-card kpi-orange">
                            <div class="kpi-label">Discapacitados</div>
                            <div class="kpi-value"><?= fmt_num($resumenCar['discapacitados']); ?></div>
                            <div class="kpi-help">Total de personas con discapacidad reportada.</div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica4; ?></h4>
                            <p class="dash-subtitle">Resume la composición poblacional atendida.</p>
                            <div id="graph04" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica5; ?></h4>
                            <p class="dash-subtitle">Distribución de discapacidades identificadas en la población.</p>
                            <div id="graph05" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica6; ?></h4>
                            <p class="dash-subtitle">Nivel de ingresos mensuales reportado por los beneficiarios.</p>
                            <div id="graph06" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-6">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica7; ?></h4>
                            <p class="dash-subtitle">Comidas que con mayor frecuencia no son consumidas.</p>
                            <div id="graph07" class="chart-box"></div>
                        </div>
                    </div>

                    <div class="grid-col-12">
                        <div class="dash-card" style="margin-bottom:0;">
                            <h4><?= $nombreGrafica8; ?></h4>
                            <p class="dash-subtitle">Panorama de problemáticas sociales reportadas en la
                                caracterización.</p>
                            <div id="graph08" class="chart-box lg"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        $("#pais").on('change', function () {
            var pais = $(this).val();
            $.post("paises.php", { pais: pais }, function (data) {
                $("#departamento").html(data);
                $("#Depto").val('');
            });
        });

        $("#departamento").on('change', function () {
            $("#Depto").val($('select[name="departamento"] option:selected').text());
        });

        $('#formFiltrosSopas').on('submit', function (e) {
            var fi = $('#FechaIni').val();
            var ff = $('#FechaFin').val();

            if (fi && ff && fi > ff) {
                e.preventDefault();
                alert('La fecha inicial no puede ser mayor que la fecha final.');
                return false;
            }

            $("#Depto").val($('select[name="departamento"] option:selected').text());
        });
    });
</script>



<script type="text/javascript">
google.charts.load('current', {'packages':['corechart', 'bar']});

function renderEmptyState(id, mensaje){
    var el = document.getElementById(id);
    if(!el) return;
    el.innerHTML = '<div class="chart-empty">' + mensaje + '</div>';
}

function hasRows(rows){
    return Array.isArray(rows) && rows.length > 1;
}

function hasPositiveValues(rows){
    if(!hasRows(rows)) return false;
    var total = 0;
    for(var i = 1; i < rows.length; i++){
        for(var j = 1; j < rows[i].length; j++){
            if(typeof rows[i][j] === 'number'){
                total += Math.abs(rows[i][j]);
            }
        }
    }
    return total > 0;
}

function drawArrayColumn(id, rows, options){
    var el = document.getElementById(id);
    if(!el) return;

    if(!hasRows(rows)){
        renderEmptyState(id, 'No hay datos disponibles para esta gráfica con los filtros seleccionados.');
        return;
    }
    if(!hasPositiveValues(rows)){
        renderEmptyState(id, 'Aún no hay resultados para mostrar en esta gráfica con el filtro aplicado.');
        return;
    }

    var data = google.visualization.arrayToDataTable(rows);
    var chart = new google.visualization.ColumnChart(el);
    chart.draw(data, options);
}

function drawArrayBar(id, rows, options){
    var el = document.getElementById(id);
    if(!el) return;

    if(!hasRows(rows)){
        renderEmptyState(id, 'No hay datos disponibles para esta gráfica con los filtros seleccionados.');
        return;
    }
    if(!hasPositiveValues(rows)){
        renderEmptyState(id, 'Aún no hay resultados para mostrar en esta gráfica con el filtro aplicado.');
        return;
    }

    var data = google.visualization.arrayToDataTable(rows);
    var chart = new google.visualization.BarChart(el);
    chart.draw(data, options);
}

function drawArrayPie(id, rows, options){
    var el = document.getElementById(id);
    if(!el) return;

    if(!hasRows(rows)){
        renderEmptyState(id, 'No hay datos disponibles para esta gráfica con los filtros seleccionados.');
        return;
    }
    if(!hasPositiveValues(rows)){
        renderEmptyState(id, 'Aún no hay resultados para mostrar en esta gráfica con el filtro aplicado.');
        return;
    }

    var data = google.visualization.arrayToDataTable(rows);
    var chart = new google.visualization.PieChart(el);
    chart.draw(data, options);
}

function getResponsiveChartConfig(){
    var w = window.innerWidth || document.documentElement.clientWidth;

    if (w <= 575) {
        return {
            barLeft: 95,
            barWidth: '74%',
            barHeight: '78%',
            colLeft: 40,
            colWidth: '84%',
            colHeight: '72%',
            fontAxis: 10,
            fontLabels: 9,
            groupWidth: '82%'
        };
    }

    if (w <= 767) {
        return {
            barLeft: 110,
            barWidth: '78%',
            barHeight: '80%',
            colLeft: 45,
            colWidth: '85%',
            colHeight: '74%',
            fontAxis: 11,
            fontLabels: 10,
            groupWidth: '78%'
        };
    }

    if (w <= 991) {
        return {
            barLeft: 125,
            barWidth: '80%',
            barHeight: '82%',
            colLeft: 50,
            colWidth: '86%',
            colHeight: '76%',
            fontAxis: 11,
            fontLabels: 10,
            groupWidth: '76%'
        };
    }

    return {
        barLeft: 140,
        barWidth: '84%',
        barHeight: '86%',
        colLeft: 55,
        colWidth: '88%',
        colHeight: '80%',
        fontAxis: 12,
        fontLabels: 11,
        groupWidth: '75%'
    };
}

var rows01 = [<?= isset($datosGraph01) ? implode(",", $datosGraph01) : '';?>];
var rows02 = [<?= isset($datosGraph02) ? implode(",", $datosGraph02) : '';?>];
var rows03 = [<?= isset($datosGraph03) ? implode(",", $datosGraph03) : '';?>];
var rows04 = [<?= isset($datosGraph04) ? implode(",", $datosGraph04) : '';?>];
var rows05 = [<?= isset($datosGraph05) ? implode(",", $datosGraph05) : '';?>];
var rows06 = [<?= isset($datosGraph06) ? implode(",", $datosGraph06) : '';?>];
var rows07 = [<?= isset($datosGraph07) ? implode(",", $datosGraph07) : '';?>];
var rows08 = [<?= isset($datosGraph08) ? implode(",", $datosGraph08) : '';?>];
var rows09 = [<?= isset($datosGraph09) ? implode(",", $datosGraph09) : '';?>];
var rows10 = [<?= isset($datosGraph10) ? implode(",", $datosGraph10) : '';?>];
var rows11 = [<?= isset($datosGraph11) ? implode(",", $datosGraph11) : '';?>];
var rows12 = [<?= isset($datosGraph12) ? implode(",", $datosGraph12) : '';?>];

function drawChart01(){
    drawArrayColumn('graph01', rows01, {
        animation:{ startup:true, duration:1300, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'none' },
        bar:{ groupWidth:'55%' },
        vAxis:{ minValue: 0, title: 'Cantidad' }
    });
}

function drawChart02(){
    drawArrayColumn('graph02', rows02, {
        animation:{ startup:true, duration:1300, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'bottom' },
        bar:{ groupWidth:'55%' },
        vAxis:{ minValue: 0, title: 'Cantidad' }
    });
}

function drawChart03(){
    drawArrayPie('graph03', rows03, {
        pieHole: 0.40,
        chartArea:{ left:20, top:20, width:'90%', height:'80%' },
        legend:{ position:'bottom' }
    });
}

function drawChart04(){
    var rc = getResponsiveChartConfig();

    drawArrayBar('graph04', rows04, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:rc.barLeft, top:10, width:rc.barWidth, height:rc.barHeight },
        legend:{ position:'none' },
        bars:'horizontal',
        hAxis:{
            minValue:0,
            title:'Cantidad',
            textStyle:{ fontSize:rc.fontAxis }
        },
        vAxis:{
            textStyle:{ fontSize:rc.fontAxis }
        }
    });
}

function drawChart05(){
    var rc = getResponsiveChartConfig();

    drawArrayColumn('graph05', rows05, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:rc.colLeft, top:15, width:rc.colWidth, height:rc.colHeight },
        legend:{ position:'none' },
        bar:{ groupWidth:rc.groupWidth },
        vAxis:{
            minValue:0,
            title:'Total',
            textStyle:{ fontSize:rc.fontAxis }
        },
        hAxis:{
            textStyle:{ fontSize:rc.fontLabels },
            slantedText:false
        }
    });
}

function drawChart06(){
    var rc = getResponsiveChartConfig();

    drawArrayBar('graph06', rows06, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:rc.barLeft + 15, top:10, width:rc.barWidth, height:rc.barHeight },
        legend:{ position:'none' },
        bars:'horizontal',
        hAxis:{
            minValue:0,
            title:'Total',
            textStyle:{ fontSize:rc.fontAxis }
        },
        vAxis:{
            textStyle:{ fontSize:rc.fontLabels }
        }
    });
}

function drawChart07(){
    var rc = getResponsiveChartConfig();

    drawArrayColumn('graph07', rows07, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:rc.colLeft, top:15, width:rc.colWidth, height:rc.colHeight },
        legend:{ position:'none' },
        bar:{ groupWidth:rc.groupWidth },
        vAxis:{
            minValue:0,
            title:'Total',
            textStyle:{ fontSize:rc.fontAxis }
        },
        hAxis:{
            textStyle:{ fontSize:rc.fontLabels },
            slantedText:false
        }
    });
}

function drawChart08(){
    var rc = getResponsiveChartConfig();

    drawArrayBar('graph08', rows08, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:rc.barLeft + 20, top:10, width:rc.barWidth, height:rc.barHeight },
        legend:{ position:'none' },
        bars:'horizontal',
        hAxis:{
            minValue:0,
            title:'Total',
            textStyle:{ fontSize:rc.fontAxis }
        },
        vAxis:{
            textStyle:{ fontSize:rc.fontLabels }
        }
    });
}

function drawChart09(){
    drawArrayColumn('graph09', rows09, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'none' },
        bar:{ groupWidth:'50%' },
        vAxis:{ minValue:0, title:'Cantidad' }
    });
}

function drawChart10(){
    drawArrayColumn('graph10', rows10, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'none' },
        bar:{ groupWidth:'45%' },
        vAxis:{ title:'Saldo neto' }
    });
}

function drawChart11(){
    if (!<?= $esVistaFacilitador ? 'true' : 'false'; ?>) {
        renderEmptyState('graph11', 'Debe filtrar por facilitador para mostrar esta grafica.');
        return;
    }

    drawArrayColumn('graph11', rows11, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'bottom' },
        bar:{ groupWidth:'60%' },
        vAxis:{ minValue: 0, title:'Cantidad' },
        hAxis:{ title:'Fecha' }
    });
}

function drawChart12(){
    if (!<?= $esVistaFacilitador ? 'true' : 'false'; ?>) {
        renderEmptyState('graph12', 'Debe filtrar por facilitador para mostrar esta grafica.');
        return;
    }

    drawArrayColumn('graph12', rows12, {
        animation:{ startup:true, duration:1200, easing:'out' },
        chartArea:{ left:70, top:30, width:'80%', height:'68%' },
        legend:{ position:'bottom' },
        bar:{ groupWidth:'60%' },
        vAxis:{ minValue: 0, title:'Cantidad' },
        hAxis:{ title:'Fecha' }
    });
}

function drawInventario(){
    drawChart01();
    drawChart02();
    drawChart03();
    drawChart09();
    drawChart10();
    drawChart11();
    drawChart12();
}

function drawCaracterizacion(){
    drawChart04();
    drawChart05();
    drawChart06();
    drawChart07();
    drawChart08();
}

function scheduleCaracterizacionDraw(){
    clearTimeout(window.__carDraw1);
    clearTimeout(window.__carDraw2);
    clearTimeout(window.__carDraw3);
    clearTimeout(window.__carDraw4);

    window.__carDraw1 = setTimeout(function(){
        drawCaracterizacion();
    }, 120);

    window.__carDraw2 = setTimeout(function(){
        drawCaracterizacion();
    }, 260);

    window.__carDraw3 = setTimeout(function(){
        drawCaracterizacion();
    }, 480);

    window.__carDraw4 = setTimeout(function(){
        drawCaracterizacion();
    }, 750);
}

google.charts.setOnLoadCallback(function(){

    // Espera a que la página esté completamente cargada
    window.addEventListener('load', function(){

        setTimeout(function(){

            var activeTab = jQuery('ul.nav-pills li.active a').attr('href');

            if(activeTab === '#Caracterizacion'){
                scheduleCaracterizacionDraw();
            }else{
                drawInventario();
            }

        }, 300);

    });

});

function redrawCaracterizacionFirstOpen(){
    clearTimeout(window.__carTab1);
    clearTimeout(window.__carTab2);
    clearTimeout(window.__carTab3);
    clearTimeout(window.__carTab4);

    window.__carTab1 = setTimeout(function(){
        drawCaracterizacion();
    }, 120);

    window.__carTab2 = setTimeout(function(){
        drawCaracterizacion();
    }, 260);

    window.__carTab3 = setTimeout(function(){
        drawCaracterizacion();
    }, 520);

    window.__carTab4 = setTimeout(function(){
        drawCaracterizacion();
    }, 900);
}

jQuery(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
    var target = jQuery(e.target).attr("href");

    if(target === '#Caracterizacion'){
        redrawCaracterizacionFirstOpen();
    }

    if(target === '#InventarioG'){
        setTimeout(function(){
            drawInventario();
        }, 150);
    }
});

/* Si por alguna razón la pestaña visible al cargar fuera caracterización */
jQuery(function($){
    setTimeout(function(){
        var target = $('ul.nav-pills li.active a').attr('href');
        if(target === '#Caracterizacion'){
            scheduleCaracterizacionDraw();
        }
    }, 250);
});

window.addEventListener('resize', function(){
    clearTimeout(window.__redrawSopas__);

    window.__redrawSopas__ = setTimeout(function(){
        var activeTarget = jQuery('ul.nav-pills li.active a').attr('href');

        if(activeTarget === '#Caracterizacion'){
            scheduleCaracterizacionDraw();
        } else {
            drawInventario();
        }
    }, 250);
});


jQuery(window).on('load', function(){

    setTimeout(function(){

        // redibuja inventario una vez
        drawInventario();

        // si el tab visible fuera caracterización
        if(jQuery('#Caracterizacion').hasClass('active')){
            drawCaracterizacion();
        }

    }, 300);

});

jQuery(window).on('load', function(){

    setTimeout(function(){
        drawInventario();
    }, 250);

    // Si el usuario abre caracterización por primera vez, forzar buen render
    jQuery('#Caracterizacion').on('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', function () {
        if(jQuery(this).hasClass('active')){
            redrawCaracterizacionFirstOpen();
        }
    });

});

</script>
