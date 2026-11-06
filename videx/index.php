<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/html; charset=utf-8');

if($_GET["doc"] == "mail_envio"){
    ignore_user_abort(true);
    set_time_limit(0);
    include_once('phpmailer/src/PHPMailer.php');
    include_once('phpmailer/src/SMTP.php');
    include_once('phpmailer/src/Exception.php');
    $mail = new PHPMailer();
}

session_start();
include_once('funciones.php');

if(!isset($_GET["salir"]) && $_SESSION["id"] != "" && $_SESSION["id"] != 0 && $_SESSION["sistema"] != "videx") {
    header("Location: index.php?salir=");
    exit;
}

$array_meses = array("Enero", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
$array_semana = array("Mon" => "Lunes", "Tue" => "Martes", "Wed" => "Miercoles", "Thu" => "Jueves", "Fri" => "Viernes", "Sat" => "Sábado", "Sun" => "Domingo");

$mesactual = intval(date("m"));
$anhoactual = intval(date("Y"));
$diaactual = intval(date("d"));
$semanaactual = date("D");

if(isset($_GET[salir])) {
    session_unset();
    redirect('index.php');
}

if(isset($_POST["logueo"]) && trim($_POST["logueo"]) != "") {
    $PSN = new DBbase_Sql;
    $logueo = eliminarInvalidos($_POST["logueo"]);
    $pass = eliminarInvalidos($_POST["passwordlogueo"]);
    $error = 0;

    $sql = "SELECT usuario.* FROM usuario WHERE acceso = 1 AND identificacion='" . $logueo . "'";
    $PSN->query($sql);

    if($PSN->next_record()) {
        /*
         * Los usuarios creados desde el modulo "usuario" refactorizado
         * (usuario_service.php) guardan la contrasena con password_hash()
         * (bcrypt, 60 caracteres), pero este login solo comparaba con md5().
         * Eso dejaba a cualquier usuario nuevo sin poder iniciar sesion. Se
         * detecta el formato por longitud para soportar ambos.
         */
        $hashGuardado = $PSN->f('password');
        $loginValido = (strlen($hashGuardado) === 32)
            ? (md5($pass) == $hashGuardado)
            : password_verify($pass, $hashGuardado);

        if($loginValido) {
            $_SESSION["administrador"] = "admin";
            $_SESSION["sistema"] = "videx";
            $_SESSION["nombre"] = $PSN->f('nombre');
            $_SESSION["identificacion"] = $PSN->f('identificacion');
            $_SESSION["direccion"] = $PSN->f('direccion');
            $_SESSION["telefono1"] = $PSN->f('telefono1');
            $_SESSION["telefono2"] = $PSN->f('telefono2');
            $_SESSION["celular"] = $PSN->f('celular');
            $_SESSION["email"] = $PSN->f('email');
            $_SESSION["id"] = $PSN->f('id');
            $_SESSION["superusuario"] = $PSN->f('superusuario');
            $_SESSION["menu_graphs"] = $PSN->f('acceso_graphs');
            $_SESSION["acceso_solo_lectura"] = $PSN->f('acceso_solo_lectura');
            $_SESSION["gp_consulta"] = $PSN->f('gp_consulta');
            $_SESSION["gp_modificacion"] = $PSN->f('gp_modificacion');
            $_SESSION["gp_ver_credenciales"] = $PSN->f('gp_ver_credenciales');
            $_SESSION["gp_tickets_consulta"] = $PSN->f('gp_tickets_consulta');
            $_SESSION["gp_tickets_modificacion"] = $PSN->f('gp_tickets_modificacion');
            $_SESSION["gp_tickets_fechas"] = $PSN->f('gp_tickets_fechas');
            // Proyecto de Inversion: permisos dedicados (independientes del gp_consulta/
            // gp_modificacion generico de Gestion Proyectos). gp_inv_eliminar NO se cachea
            // aqui: es permiso especial y se consulta en vivo en gp_inversion_config.php.
            $_SESSION["gp_inv_consulta"] = $PSN->f('gp_inv_consulta');
            $_SESSION["gp_inv_modificacion"] = $PSN->f('gp_inv_modificacion');
            // Informativo: el modulo Claves consulta este permiso en vivo en la BD (gp_claves_config.php).
            $_SESSION["gp_claves"] = $PSN->f('gp_claves');
            $_SESSION["clientes_filtro"] = "";
            $_SESSION['KCFINDER'] = array('disabled' => false);
            $_SESSION["perfil"] = $PSN->f('tipo');

            if($_SESSION["perfil"] == 160){
                $_SESSION["tipo_user_cli"] = $PSN->f('tipo_user_cli');
                $sql = "SELECT usuario_relacion.idUsuario2 FROM usuario_relacion, usuario WHERE idUsuario1 = '" . $_SESSION["id"] . "' AND usuario.id = usuario_relacion.idUsuario2 AND usuario.tipo = 3";
                $PSN->query($sql);
                if($PSN->next_record()) {
                    $_SESSION["micliente"] = $PSN->f('idUsuario2');
                }
            } else if($_SESSION["perfil"] == 3) {
                $_SESSION["micliente"] = $PSN->f('id');
            }

            $sql = "SELECT usuarios_clientes.* FROM usuarios_clientes WHERE idUsuario = '" . $_SESSION["id"] . "'";
            $PSN->query($sql);
            if($PSN->num_rows() > 0){
                $array_temp = array();
                while($PSN->next_record()){
                    $array_temp[] = $PSN->f('idCliente');
                }
                $_SESSION["clientes_filtro"] = implode(",", $array_temp);
            }

            if($_POST["redireccion"] != "") {
                redirect(eliminarInvalidos($_POST["redireccion"]));
            } else {
                redirect('index.php?doc=main');
            }
        } else {
            $error = 2;
        }
    } else {
        $error = 1;
    }
}

if(trim($_SESSION["imagen"]) == "") {
    $_SESSION["imagen"] = "LogoWeb.jpg";
}

/*
 * Compuerta de permisos: ningun modulo se incluye sin verificar que el usuario
 * lo tenga asignado (ver acceso_modulos.php). Sin permiso -> de vuelta al
 * dashboard. Va antes de excelX porque esa rama tambien hace include del doc.
 */
if(isset($_GET["doc"]) && trim($_GET["doc"]) != "" && is_logged_in()) {
    include_once('acceso_modulos.php');
    if(!videx_acceso_permitido($_GET["doc"], $_GET["cat"] ?? null)) {
        error_log('VIDEX acceso denegado: usuario=' . ($_SESSION["id"] ?? '?') . ' doc=' . $_GET["doc"] . ' cat=' . ($_GET["cat"] ?? ''));
        header('Location: index.php?doc=main&sin_permiso=1');
        exit;
    }
}

if(isset($_GET["excelX"])) {
    // Esta rama incluye el doc directamente: sin sesion no se exporta nada
    if(!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=archivo" . date("Ymd_His") . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    $docu = eliminarInvalidos($_GET["doc"]);
    include_once($docu . ".php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/sms-app.png">
    <title><?=$gloPrograma; ?> - <?=$gloEmpresa; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Charts (solo para módulos que lo requieren) -->
    <?php
    $docParam = $_GET["doc"] ?? "";
    $googleChartsModules = ['graphs_001','graphs_002','graphs_003','graphs_004','graphs_005','graphs_006','graphs_008'];
    if (in_array($docParam, $googleChartsModules)):
    ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <?php endif; ?>

    <!-- CSS Puro -->
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="videx-compat.css">
    <link rel="stylesheet" href="videx-forms.css">
    <link rel="stylesheet" href="sidebar.css">

    <!-- Módulo CSS (solo si está autenticado) -->
    <?php if (($_GET["doc"] ?? "") !== "cotizacion" && is_logged_in()): ?>
        <link rel="stylesheet" href="usuario-moderno.css">
        <link rel="stylesheet" href="usuario-form.css">
        <link rel="stylesheet" href="usuario-buscar.css">
        <link rel="stylesheet" href="admin-buscarcatm.css">
        <link rel="stylesheet" href="campanha.css">
        <link rel="stylesheet" href="sms-grupos.css">
        <link rel="stylesheet" href="cliente.css">
        <link rel="stylesheet" href="contactos.css">
    <?php endif; ?>

    <!-- Modern Auth CSS -->
    <style>
        :root {
            --videx-primary: #2563eb;
            --videx-primary-dark: #1d4ed8;
            --videx-success: #16a34a;
            --videx-danger: #dc2626;
            --videx-warning: #d97706;
            --videx-bg: #ffffff;
            --videx-bg-secondary: #f9fafb;
            --videx-text-primary: #1f2937;
            --videx-text-secondary: #6b7280;
            --videx-border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: var(--videx-text-primary);
            background-color: var(--videx-bg);
        }

        /* ========== AUTH LAYOUT ========== */
        .auth-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
        }

        .auth-navbar {
            background-color: var(--videx-bg);
            border-bottom: 1px solid var(--videx-border);
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .auth-navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .auth-navbar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--videx-text-primary);
            font-weight: 600;
            font-size: 16px;
            transition: opacity 0.2s ease;
        }

        .auth-navbar-logo:hover {
            opacity: 0.8;
        }

        .auth-navbar-logo img {
            height: 40px;
            width: auto;
        }

        .auth-navbar-link {
            color: var(--videx-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .auth-navbar-link:hover {
            color: var(--videx-primary-dark);
            text-decoration: underline;
        }

        .auth-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            max-width: 1000px;
            width: 100%;
            align-items: center;
        }

        .auth-form-wrapper {
            background-color: var(--videx-bg);
            border: 1px solid var(--videx-border);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .auth-form-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .auth-form-logo {
            height: 60px;
            width: auto;
            margin-bottom: 1rem;
        }

        .auth-form-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--videx-text-primary);
            margin: 0;
        }

        .auth-form-subtitle {
            font-size: 13px;
            color: var(--videx-text-secondary);
            margin-top: 0.5rem;
        }

        .auth-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .auth-form-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--videx-text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .auth-form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--videx-border);
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            background-color: var(--videx-bg);
            color: var(--videx-text-primary);
            transition: all 0.2s ease;
            min-height: 40px;
        }

        /* Campo de contraseña con boton para mostrar/ocultar */
        .auth-password-wrap {
            position: relative;
        }

        .auth-password-wrap .auth-form-input {
            padding-right: 44px;
        }

        .auth-password-toggle {
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: var(--videx-text-secondary);
            font-size: 16px;
            cursor: pointer;
            border-radius: 0 6px 6px 0;
        }

        .auth-password-toggle:hover,
        .auth-password-toggle:focus-visible {
            color: var(--videx-primary);
            outline: none;
        }

        .auth-form-input:focus {
            outline: none;
            border-color: var(--videx-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: var(--videx-bg);
        }

        .auth-form-input::placeholder {
            color: var(--videx-text-secondary);
        }

        .auth-form-button {
            width: 100%;
            padding: 10px 16px;
            min-height: 44px;
            border: none;
            border-radius: 6px;
            background-color: var(--videx-primary);
            color: white;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .auth-form-button:hover {
            background-color: var(--videx-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .auth-form-button:active {
            transform: translateY(0);
        }

        .auth-error {
            padding: 10px 12px;
            border-radius: 6px;
            background-color: var(--videx-danger-light);
            border: 1px solid var(--videx-danger);
            border-left: 4px solid var(--videx-danger);
            color: #7f1d1d;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .auth-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .auth-content-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--videx-text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .auth-content-subtitle {
            font-size: 18px;
            font-weight: 600;
            color: var(--videx-primary);
            margin: 0;
        }

        .auth-content-text {
            font-size: 14px;
            color: var(--videx-text-secondary);
            line-height: 1.6;
            margin: 0;
        }

        .auth-feature {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .auth-feature-icon {
            font-size: 20px;
            color: var(--videx-primary);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .auth-feature-text {
            font-size: 13px;
            color: var(--videx-text-secondary);
            line-height: 1.5;
        }

        .auth-footer {
            background-color: var(--videx-bg);
            border-top: 1px solid var(--videx-border);
            padding: 1.5rem;
            text-align: center;
        }

        .auth-footer-text {
            font-size: 12px;
            color: var(--videx-text-secondary);
            margin: 0;
        }

        .auth-footer-link {
            color: var(--videx-primary);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .auth-footer-link:hover {
            color: var(--videx-primary-dark);
            text-decoration: underline;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .auth-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .auth-content {
                display: none;
            }

            .auth-form-wrapper {
                padding: 1.5rem;
            }

            .auth-content-title {
                font-size: 24px;
            }

            .auth-main {
                padding: 1rem;
            }

            .auth-navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .auth-form-wrapper {
                padding: 1rem;
                border-radius: 8px;
            }

            .auth-main {
                padding: 1rem;
            }

            .auth-form-title {
                font-size: 18px;
            }

            .auth-form-logo {
                height: 50px;
            }

            .auth-navbar-logo img {
                height: 32px;
            }
        }
    </style>

    <!-- jQuery (required by CKEditor) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- App Scripts (only if logged in) -->
    <?php if (is_logged_in()): ?>
        <script src="js/usuario.js?v=<?php echo @filemtime('js/usuario.js'); ?>"></script>
        <script src="js/admin_buscarcatm.js"></script>
        <script src="js/campanha.js"></script>
        <script src="js/sms_grupos.js"></script>
        <script src="js/cliente.js?v=<?php echo @filemtime('js/cliente.js'); ?>"></script>
        <script src="js/contactos.js"></script>
        <!-- NOTE: js/cotizacion.js loaded ONLY when needed, not globally -->
    <?php endif; ?>

    <script>
        // Sidebar & Auth shared scripts
        let sidebarPinned = false;
        let isHovering = false;

        function toggleSidebar() {
            const sidebar = document.getElementById('videxSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar) return;

            if (window.innerWidth > 767) {
                sidebarPinned = !sidebarPinned;
                if (sidebarPinned) {
                    sidebar.style.width = '250px';
                } else {
                    if (!isHovering) {
                        sidebar.style.width = '70px';
                    }
                }
            } else {
                sidebar.classList.toggle('mobile-open');
                if (backdrop) {
                    backdrop.classList.toggle('active');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('videxSidebar');
            if (!sidebar) return;

            if (window.innerWidth > 767) {
                sidebar.addEventListener('mouseenter', function() {
                    isHovering = true;
                    this.style.width = '250px';
                });

                sidebar.addEventListener('mouseleave', function() {
                    isHovering = false;
                    if (!sidebarPinned) {
                        this.style.width = '70px';
                    }
                });
            }

            setActiveMenuItem();
        });

        function setActiveMenuItem() {
            const doc = new URLSearchParams(window.location.search).get('doc');
            const items = document.querySelectorAll('.menu-item');
            items.forEach(item => {
                item.classList.remove('active');
                if (item.href.includes('doc=' + doc)) {
                    item.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', function() {
            var nav = document.getElementById('videxNavbar');
            if (nav) {
                nav.classList.toggle('scrolled', window.scrollY > 8);
            }
        }, { passive: true });

        function getfocus(id){
            if(document.getElementById(id)) {
                document.getElementById(id).focus();
            }
        }

        function salirSistema() {
            if(confirm("Desea salir del sistema?")) {
                window.location.href = "index.php?salir=";
            }
        }

        function cambiar(url) {
            window.location.href = url.value;
        }
    </script>
</head>
<body>
<?php
$displayError = 0;
if(isset($_GET["doc"]) && !empty($_GET["doc"]) && is_logged_in()) {
    // ========== LOGGED IN - APP INTERFACE ==========
    ?>
    <!-- Mobile Hamburger -->
    <button class="mobile-hamburger" id="mobileMenuToggle" onclick="toggleSidebar()" title="Abrir menú">
        <i class="bi bi-list"></i>
    </button>

    <div class="videx-wrap">
        <aside class="videx-sidebar" id="videxSidebar">
            <div class="sidebar-header">
                <a href="index.php?doc=main" class="sidebar-logo-link" title="Ir a inicio">
                    <img src="images/logoanglo.png" class="sidebar-logo" alt="VIDEX">
                    <span class="sidebar-title">VIDEX</span>
                </a>
                <button class="sidebar-toggle" onclick="toggleSidebar()" title="Contraer menú">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-menu">
                <?php
                $principal_map = array(
                    1 => array('icon' => 'bi-gear', 'name' => 'Administración'),
                    2 => array('icon' => 'bi-chat-dots', 'name' => 'SMS'),
                    20 => array('icon' => 'bi-envelope', 'name' => 'Emailing'),
                    3 => array('icon' => 'bi-people', 'name' => 'Contactos'),
                    4 => array('icon' => 'bi-truck', 'name' => 'Flota'),
                    5 => array('icon' => 'bi-wrench', 'name' => 'Mantenimiento'),
                    6 => array('icon' => 'bi-clipboard2-check', 'name' => 'Auditoría de Proyectos'),
                    7 => array('icon' => 'bi-kanban', 'name' => 'Gestión Proyectos'),
                    99 => array('icon' => 'bi-person-circle', 'name' => 'Mi cuenta')
                );

                if($_SESSION["perfil"] == 4){
                    ?>
                    <div class="menu-group">
                        <a class="menu-item" href="index.php?doc=mant_buscar" title="Solicitudes de mantenimiento">
                            <i class="bi bi-search"></i>
                            <span class="menu-text">Solicitudes</span>
                        </a>
                        <a class="menu-item" href="index.php?doc=mant_buscar_cot" title="Consulta de contactos">
                            <i class="bi bi-people"></i>
                            <span class="menu-text">Contactos</span>
                        </a>
                        <a class="menu-item" href="index.php?doc=mant_buscar_ot" title="Ordenes de trabajo">
                            <i class="bi bi-file-earmark-text"></i>
                            <span class="menu-text">Órdenes OT</span>
                        </a>
                    </div>
                    <?php
                } else {
                    $PSNMenu = new DBbase_Sql;
                    $sqlMenu = "SELECT menu.* FROM menu, usuarios_menu WHERE menu.id = usuarios_menu.idMenu AND usuarios_menu.idUsuario = " . $_SESSION["id"];
                    $sqlMenu .= " ORDER BY principal, orden asc";
                    $PSNMenu->query($sqlMenu);

                    if($PSNMenu->num_rows() > 0) {
                        $categorias_mostradas = array();

                        while($PSNMenu->next_record()) {
                            $principal = $PSNMenu->f("principal");
                            $url = "index.php?doc=" . $PSNMenu->f("php");
                            if($PSNMenu->f("opc") > 0) { $url .= "&opc=" . $PSNMenu->f("opc"); }
                            if($PSNMenu->f("extra") != "") { $url .= "&" . $PSNMenu->f("extra"); }

                            if (!isset($categorias_mostradas[$principal])) {
                                $categorias_mostradas[$principal] = array(
                                    'name' => $principal_map[$principal]['name'] ?? ('Categoría ' . $principal),
                                    'icon' => $principal_map[$principal]['icon'] ?? 'bi-circle-fill',
                                    'items' => array()
                                );
                            }

                            $categorias_mostradas[$principal]['items'][] = array(
                                'nombre' => $PSNMenu->f("nombre"),
                                'url' => $url
                            );
                        }

                        if($_SESSION["menu_graphs"] == 1) {
                            $PSNMenu2 = new DBbase_Sql;
                            $sqlMenu2 = "SELECT menu_graphs.* FROM menu_graphs, usuarios_menu_graphs WHERE menu_graphs.id = usuarios_menu_graphs.idMenu AND usuarios_menu_graphs.idUsuario = " . $_SESSION["id"] . " ORDER BY orden asc";
                            $PSNMenu2->query($sqlMenu2);

                            if($PSNMenu2->num_rows() > 0) {
                                $categorias_mostradas['graphs'] = array(
                                    'name' => 'GRÁFICAS',
                                    'icon' => 'bi-bar-chart-line',
                                    'items' => array()
                                );

                                while($PSNMenu2->next_record()) {
                                    $url = "index.php?doc=" . $PSNMenu2->f("php");
                                    if($PSNMenu2->f("opc") > 0) { $url .= "&opc=" . $PSNMenu2->f("opc"); }
                                    if($PSNMenu2->f("extra") != "") { $url .= "&" . $PSNMenu2->f("extra"); }

                                    $categorias_mostradas['graphs']['items'][] = array(
                                        'nombre' => $PSNMenu2->f("nombre"),
                                        'url' => $url
                                    );
                                }
                            }
                        }

                        if($_SESSION["superusuario"] == 1) {
                            $categorias_mostradas['admin'] = array(
                                'name' => 'ADMIN',
                                'icon' => 'bi-shield-lock',
                                'items' => array(
                                    array('nombre' => 'Saldo SMS', 'url' => 'index.php?doc=sms_saldo'),
                                    array('nombre' => 'Crear Personal', 'url' => 'index.php?doc=Personal&opc=1'),
                                    array('nombre' => 'Consulta Personal', 'url' => 'index.php?doc=Personal&opc=2')
                                )
                            );
                        }

                        ?>
                        <div class="menu-group">
                            <?php
                            foreach ($categorias_mostradas as $cat_id => $cat_data) {
                                ?>
                                <a class="menu-main-cat" href="index.php?doc=category&cat=<?= urlencode($cat_id); ?>" title="<?= htmlspecialchars($cat_data['name']); ?>">
                                    <i class="bi <?= $cat_data['icon']; ?>"></i>
                                    <span class="menu-text"><?= $cat_data['name']; ?></span>
                                    <i class="bi bi-chevron-right menu-arrow"></i>
                                </a>
                                <?php
                                foreach ($cat_data['items'] as $item) {
                                    ?><span class="menu-subitem" data-category="<?= $cat_id; ?>" data-url="<?= htmlspecialchars($item['url']); ?>" data-name="<?= htmlspecialchars($item['nombre']); ?>" data-title="<?= htmlspecialchars($item['nombre']); ?>" style="display: none;"></span><?php
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span class="user-name"><?= htmlspecialchars(explode(' ', trim($_SESSION["nombre"]))[0]); ?></span>
                </div>
                <a class="logout-link" href="javascript:salirSistema();" title="Salir del sistema">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Salir</span>
                </a>
            </div>
        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

        <main class="main-content">
            <?php
            $docu = eliminarInvalidos($_GET["doc"]);
            if(trim($docu) == "") {
                $docu = "main";
            }

            // Load cotizacion.js ONLY if viewing cotizacion module
            if($docu == "cotizacion") {
                echo '<script src="js/cotizacion.js"></script>';
            }

            include_once($docu . ".php");
            ?>
            <footer class="videx-footer mt-4">
                <hr class="mb-2">
                <div class="text-center small text-muted pb-2">
                    Bienvenido <strong><?=$_SESSION["nombre"]; ?></strong> &mdash;
                    <?=$array_semana[$semanaactual]; ?> <?=$diaactual; ?> de <?=$array_meses[$mesactual]; ?> del <?=$anhoactual; ?>
                    <br>
                    <?=$gloPrograma; ?> &mdash; VIDEX Bogotá D.E., Colombia &mdash;
                    Copyright 2019&ndash;<?=date("Y"); ?>
                    <a href="https://videx.lat/" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 500;"><?=$gloEmpresa; ?></a>
                </div>
            </footer>
        </main>
    </div>

    <?php
} else {
    // ========== NOT LOGGED IN - AUTH PAGE ==========
    if($mostrar_dashboard == 1) { unset($mostrar_dashboard); }
    $displayError = (isset($error) && $error > 0) ? $error : 0;
    ?>
    <div class="auth-container">
        <header class="auth-navbar">
            <div class="auth-navbar-content">
                <a href="#" class="auth-navbar-logo">
                    <img src="images/logoanglo.png" alt="VIDEX">
                    <span><?=$gloPrograma; ?></span>
                </a>
                <a href="https://videx.lat/" target="_blank" class="auth-navbar-link">
                    <i class="bi bi-globe me-1"></i>videx.lat
                </a>
            </div>
        </header>

        <main class="auth-main">
            <div class="auth-grid">
                <!-- LOGIN FORM -->
                <div class="auth-form-wrapper">
                    <div class="auth-form-header">
                        <img src="images/logoanglo.png" alt="VIDEX" class="auth-form-logo">
                        <h1 class="auth-form-title">Iniciar Sesión</h1>
                        <p class="auth-form-subtitle">Acceso a VIDEX</p>
                    </div>

                    <form name="form1" method="post" action="" novalidate>
                        <?php if($displayError > 0): ?>
                            <div class="auth-error">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span><?=($displayError == 1 ? 'Usuario no encontrado.' : 'Contraseña incorrecta.'); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="auth-form-group">
                            <label for="logueo" class="auth-form-label">Usuario</label>
                            <input type="text" id="logueo" name="logueo" class="auth-form-input"
                                   value="<?=htmlspecialchars(($_POST["logueo"] ?? ''), ENT_QUOTES); ?>"
                                   placeholder="ejemplo@correo.com" required autofocus autocomplete="username">
                        </div>

                        <div class="auth-form-group">
                            <label for="passwordlogueo" class="auth-form-label">Contraseña</label>
                            <div class="auth-password-wrap">
                                <input type="password" id="passwordlogueo" name="passwordlogueo" class="auth-form-input"
                                       placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" class="auth-password-toggle" id="togglePassword"
                                        title="Mostrar contraseña" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <?php if(isset($_GET["doc"])): ?>
                            <input type="hidden" name="redireccion" value="<?=$_SERVER['REQUEST_URI']; ?>">
                        <?php endif; ?>

                        <button type="submit" class="auth-form-button">
                            <i class="bi bi-box-arrow-in-right"></i>Ingresar
                        </button>
                    </form>
                </div>

                <!-- WELCOME CONTENT -->
                <div class="auth-content">
                    <div>
                        <h2 class="auth-content-title">Bienvenido</h2>
                        <p class="auth-content-subtitle">
                            PLATAFORMA DE SMS + EMAIL + WHATSAPP<br>
                            <a href="https://videx.lat/" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 16px;">videx.lat</a>
                        </p>
                        <p class="auth-content-text">A la plataforma de VIDEX, tu solución completa para mensajería masiva. Desde aquí puedes procesar, analizar e informar con tan solo un clic de distancia.</p>
                    </div>

                    <div class="auth-feature">
                        <i class="bi bi-chat-dots auth-feature-icon"></i>
                        <div class="auth-feature-text">
                            <strong>SMS Masivos</strong><br>
                            Envía mensajes SMS a miles de contactos instantáneamente
                        </div>
                    </div>

                    <div class="auth-feature">
                        <i class="bi bi-envelope auth-feature-icon"></i>
                        <div class="auth-feature-text">
                            <strong>Email Marketing</strong><br>
                            Campañas de email profesionales y automatizadas
                        </div>
                    </div>

                    <div class="auth-feature">
                        <i class="bi bi-graph-up auth-feature-icon"></i>
                        <div class="auth-feature-text">
                            <strong>Analítica Completa</strong><br>
                            Reportes detallados y métricas en tiempo real
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <script>
            (function () {
                var btn = document.getElementById('togglePassword');
                var input = document.getElementById('passwordlogueo');
                if (!btn || !input) return;

                btn.addEventListener('click', function () {
                    var mostrar = input.type === 'password';
                    input.type = mostrar ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
                    var etiqueta = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
                    btn.title = etiqueta;
                    btn.setAttribute('aria-label', etiqueta);
                    btn.querySelector('i').className = 'bi ' + (mostrar ? 'bi-eye-slash' : 'bi-eye');
                    input.focus();
                });
            })();
        </script>

        <footer class="auth-footer">
            <p class="auth-footer-text">
                <?=$gloPrograma; ?> &mdash; VIDEX Bogotá D.E., Colombia &mdash;
                Copyright 2019&ndash;<?=date("Y"); ?>
                <a href="https://videx.lat/" target="_blank" class="auth-footer-link"><?=$gloEmpresa; ?></a>
            </p>
        </footer>
    </div>
    <?php
}
?>
</body>
</html>
