<?php
$cotizacionAssetVersion = '20260417-1730';
?><link rel="stylesheet" href="scripts/jquery-ui.css?v=<?php echo $cotizacionAssetVersion; ?>">
<link rel="stylesheet" href="cotizacion.css?v=<?php echo $cotizacionAssetVersion; ?>">
<script src="scripts/jquery-1.12.4.js"></script>
<script src="scripts/jquery-ui.js"></script>
<script src="js/cotizacion.js?v=<?php echo $cotizacionAssetVersion; ?>"></script>
<script>
(function () {
    function ensureCotizacionClass() {
        if (document.body && !document.body.classList.contains('cotizacion-page')) {
            document.body.classList.add('cotizacion-page');
        }
    }

    ensureCotizacionClass();
    document.addEventListener('DOMContentLoaded', ensureCotizacionClass);

    if (window.MutationObserver) {
        var observer = new MutationObserver(function () {
            ensureCotizacionClass();
        });

        observer.observe(document.documentElement, {
            attributes: true,
            childList: true,
            subtree: true,
            attributeFilter: ['class']
        });
    }
})();
</script>
<?php

require_once 'cotizacion_config.php';
require_once 'cotizacion_service.php';
require_once 'cotizacion_controller.php';
require_once 'cotizacion_form.php';

$cotizacionController = new CotizacionController();
$cotizacionController->authorize();

$opc = isset($_GET['opc']) ? (int) eliminarInvalidos($_GET['opc']) : 2;
$activos = isset($_GET['activos']) ? (int) soloNumeros($_GET['activos']) : 0;
$pagina = isset($_GET['pagina']) ? max(1, (int) soloNumeros($_GET['pagina'])) : 1;
$readonly = (int) ($_SESSION['acceso_solo_lectura'] ?? 0) === 1;

$webArchivo = 'cotizacion';
$nombreConsulta = COTIZACION_NAME;

function cotizacionAlertRedirect($message, $url)
{
    ?>
    <script>
        alert(<?php echo json_encode($message); ?>);
        window.location.href = <?php echo json_encode($url); ?>;
    </script>
    <?php
}

if ($opc === 3 && isset($_GET['excel'])) {
    $filters = $cotizacionController->buildFiltersFromRequest($_GET);
    $service = new CotizacionService();
    $rows = $service->exportCotizaciones(
        $filters,
        (int) ($_SESSION['perfil'] ?? 0),
        (int) ($_SESSION['id'] ?? 0),
        $_SESSION['clientes_filtro'] ?? ''
    );
    renderCotizacionExcelTable($rows, 0);
    return;
}

if ($opc === 1) {
    if (isset($_POST['contactoNombre'])) {
        if ($readonly) {
            renderCotizacionReadonlyWarning();
            return;
        }

        $newId = $cotizacionController->saveCreate($_POST);
        cotizacionAlertRedirect(
            COTIZACION_MESSAGES['create_success'],
            'index.php?doc=' . $webArchivo . '&opc=2&id=' . (int) $newId
        );
        return;
    }

    $createContext = $cotizacionController->getCreateContext();
    renderCotizacionRecordForm($createContext, $webArchivo, 0, $readonly);
    return;
}

if ($opc === 2 && isset($_GET['id']) && (int) soloNumeros($_GET['id']) > 0) {
    $recordId = (int) soloNumeros($_GET['id']);

    if (isset($_POST['contactoNombre'])) {
        if ($readonly) {
            renderCotizacionReadonlyWarning();
            return;
        }

        $funcion = eliminarInvalidos($_POST['funcion'] ?? 'actualizar');
        if ($funcion === 'eliminar') {
            $cotizacionController->delete($recordId);
            cotizacionAlertRedirect(
                COTIZACION_MESSAGES['delete_success'],
                'index.php?doc=' . $webArchivo . '&opc=2'
            );
            return;
        }

        $cotizacionController->saveUpdate($recordId, $_POST);
        cotizacionAlertRedirect(
            COTIZACION_MESSAGES['update_success'],
            'index.php?doc=' . $webArchivo . '&opc=2&id=' . $recordId
        );
        return;
    }

    $editContext = $cotizacionController->getEditContext($recordId);
    if (empty($editContext['record'])) {
        ?>
        <div class="container">
            <h2 class="alert alert-danger text-center"><?php echo htmlspecialchars(COTIZACION_MESSAGES['invalid_id']); ?></h2>
        </div>
        <?php
        return;
    }

    renderCotizacionRecordForm($editContext, $webArchivo, $recordId, $readonly);
    return;
}

$filters = $cotizacionController->buildFiltersFromRequest($_GET);
$listViewData = $cotizacionController->getListViewContext($filters, $activos, $pagina);

$paginationRequestUri = $_SERVER['REQUEST_URI'] ?? 'index.php?doc=cotizacion&opc=2';
$paginationRequestUri = preg_replace('/([?&])pagina=\d+&?/', '$1', $paginationRequestUri);
$paginationRequestUri = rtrim($paginationRequestUri, '&?');
if (strpos($paginationRequestUri, '?') === false) {
    $paginationRequestUri .= '?doc=cotizacion&opc=2';
}
?>
<div class="container cotizacion-shell" data-cotizacion-root="1">
    <div class="cotizacion-panel-header">
        <h2>.<?php echo htmlspecialchars($nombreConsulta); ?>.</h2>
        <div class="cotizacion-panel-subtitle">Vista principal en modo claro permanente.</div>
    </div>

    <div class="cotizacion-toolbar">
        <a href="index.php?doc=<?php echo htmlspecialchars($webArchivo); ?>&opc=1" class="btn btn-success">Nueva cotizacion</a>
    </div>

    <?php
    renderCotizacionFilterForm(
        $listViewData['filters'],
        $listViewData['clients'],
        $listViewData['categories'],
        $nombreConsulta,
        $activos,
        $webArchivo
    );
    renderCotizacionListTable($listViewData['records'], $listViewData['totalRecords']);
    renderCotizacionPagination($listViewData['page'], $listViewData['pages'], $paginationRequestUri);
    renderCotizacionExportButton($listViewData['filters']);
    ?>
</div>
