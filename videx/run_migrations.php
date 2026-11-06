<?php
/**
 * run_migrations.php
 * Ejecuta las migraciones SQL necesarias para el modulo de email.
 * ELIMINAR ESTE ARCHIVO DEL SERVIDOR despues de ejecutarlo.
 */
session_start();
if (empty($_SESSION['id']) || $_SESSION['superusuario'] != 1) {
    die('<h2>Acceso denegado. Solo superusuario.</h2>');
}

include_once(__DIR__ . '/funciones.php');

$migraciones = [
    [
        'desc' => 'Agregar columna encriptacion a mail_config',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_config' AND COLUMN_NAME = 'encriptacion'",
        'sql'   => "ALTER TABLE mail_config ADD COLUMN encriptacion VARCHAR(10) NOT NULL DEFAULT 'ssl' AFTER puerto",
    ],
    [
        'desc' => 'Agregar columna encriptacion a mail_workqueue',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_workqueue' AND COLUMN_NAME = 'encriptacion'",
        'sql'   => "ALTER TABLE mail_workqueue ADD COLUMN encriptacion VARCHAR(10) NOT NULL DEFAULT 'ssl' AFTER port",
    ],
    [
        'desc' => 'Agregar columna detalle_error a mail_historico',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'detalle_error'",
        'sql'   => "ALTER TABLE mail_historico ADD COLUMN detalle_error TEXT NULL AFTER error",
    ],
    [
        'desc' => 'Agregar columna asunto a mail_historico',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'asunto'",
        'sql'   => "ALTER TABLE mail_historico ADD COLUMN asunto VARCHAR(500) NULL AFTER idCampana",
    ],
    [
        'desc' => 'Agregar columna contenido a mail_historico',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'contenido'",
        'sql'   => "ALTER TABLE mail_historico ADD COLUMN contenido LONGTEXT NULL AFTER asunto",
    ],
    // ── Mailing v2: Auditoría + Rate Limiting ────────────────────────────────
    [
        'desc' => 'Crear tabla mail_audit (registro por-correo)',
        'check' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_audit'",
        'sql'   => "CREATE TABLE IF NOT EXISTS mail_audit (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            idCampana   INT UNSIGNED NULL DEFAULT NULL,
            correo      VARCHAR(255) NOT NULL,
            nombre      VARCHAR(255) NOT NULL DEFAULT '',
            estado      ENUM('enviado','error','rebote','invalido','duplicado') NOT NULL,
            razon       VARCHAR(1000) NOT NULL DEFAULT '',
            asunto      VARCHAR(500) NOT NULL DEFAULT '',
            remitente   VARCHAR(255) NOT NULL DEFAULT '',
            fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            idUsuario   INT UNSIGNED NULL DEFAULT NULL,
            retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_audit_estado  (estado),
            KEY idx_audit_campana (idCampana),
            KEY idx_audit_fecha   (fecha),
            KEY idx_audit_correo  (correo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ],
    [
        'desc' => 'Crear tabla mail_blacklist (rebotes permanentes)',
        'check' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_blacklist'",
        'sql'   => "CREATE TABLE IF NOT EXISTS mail_blacklist (
            id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
            correo    VARCHAR(255) NOT NULL,
            razon     VARCHAR(1000) NOT NULL DEFAULT '',
            idCampana INT UNSIGNED NULL DEFAULT NULL,
            fecha     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_blacklist_correo (correo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ],
    [
        'desc' => 'Crear tabla mail_queue_control (pausa/reanudacion)',
        'check' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_queue_control'",
        'sql'   => "CREATE TABLE IF NOT EXISTS mail_queue_control (
            id        TINYINT UNSIGNED NOT NULL DEFAULT 1,
            paused    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            paused_by INT UNSIGNED NULL DEFAULT NULL,
            paused_at DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ],
    [
        'desc' => 'Inicializar fila de control en mail_queue_control',
        'check' => "SELECT id FROM mail_queue_control WHERE id = 1",
        'sql'   => "INSERT IGNORE INTO mail_queue_control (id, paused) VALUES (1, 0)",
    ],
    [
        'desc' => 'Agregar columna retry_count a mail_workqueue',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_workqueue' AND COLUMN_NAME = 'retry_count'",
        'sql'   => "ALTER TABLE mail_workqueue ADD COLUMN retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER mail_nombres",
    ],
    [
        'desc' => 'Agregar columna batch_id a mail_workqueue',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_workqueue' AND COLUMN_NAME = 'batch_id'",
        'sql'   => "ALTER TABLE mail_workqueue ADD COLUMN batch_id VARCHAR(40) NOT NULL DEFAULT '' AFTER retry_count, ADD KEY idx_wq_batch (batch_id)",
    ],
    // ── Proyecto de Inversion: permisos dedicados (antes dependia del ──────
    // permiso generico gp_consulta/gp_modificacion de Gestion Proyectos,
    // asi que cualquier usuario con acceso a Proveedores/Dominios/Tickets
    // tambien podia entrar a Inversion). ───────────────────────────────────
    [
        'desc' => 'Agregar columna gp_inv_consulta a usuario',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'gp_inv_consulta'",
        'sql'   => "ALTER TABLE usuario ADD COLUMN gp_inv_consulta TINYINT(1) NOT NULL DEFAULT 0",
    ],
    [
        'desc' => 'Agregar columna gp_inv_modificacion a usuario',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'gp_inv_modificacion'",
        'sql'   => "ALTER TABLE usuario ADD COLUMN gp_inv_modificacion TINYINT(1) NOT NULL DEFAULT 0",
    ],
    [
        'desc' => 'Agregar columna gp_inv_eliminar a usuario (permiso especial)',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'gp_inv_eliminar'",
        'sql'   => "ALTER TABLE usuario ADD COLUMN gp_inv_eliminar TINYINT(1) NOT NULL DEFAULT 0",
    ],
    // ── Alertas de vencimiento: SSL en Dominios, vencimiento opcional en ───
    // Servicios. Los correos destinatarios y el log de envios (gp_alertas_email /
    // gp_alertas_enviadas) se crean solos via GpAlertasService::asegurarEsquema(). ─
    [
        'desc' => 'Agregar columna ssl_fecha_vencimiento a gp_dom_dominio',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gp_dom_dominio' AND COLUMN_NAME = 'ssl_fecha_vencimiento'",
        'sql'   => "ALTER TABLE gp_dom_dominio ADD COLUMN ssl_fecha_vencimiento DATE NULL",
    ],
    [
        'desc' => 'Agregar columna ssl_proveedor a gp_dom_dominio',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gp_dom_dominio' AND COLUMN_NAME = 'ssl_proveedor'",
        'sql'   => "ALTER TABLE gp_dom_dominio ADD COLUMN ssl_proveedor VARCHAR(150) NULL",
    ],
    [
        'desc' => 'Agregar columna ssl_notas a gp_dom_dominio',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gp_dom_dominio' AND COLUMN_NAME = 'ssl_notas'",
        'sql'   => "ALTER TABLE gp_dom_dominio ADD COLUMN ssl_notas VARCHAR(255) NULL",
    ],
    [
        'desc' => 'Agregar columna fecha_vencimiento a gp_servicio (opcional)',
        'check' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gp_servicio' AND COLUMN_NAME = 'fecha_vencimiento'",
        'sql'   => "ALTER TABLE gp_servicio ADD COLUMN fecha_vencimiento DATE NULL",
    ],
];

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Migraciones VIDEX</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="p-4">';

echo '<h4 class="mb-4">Migraciones de base de datos — VIDEX Email</h4>';
echo '<table class="table table-bordered">';
echo '<thead><tr><th>Migracion</th><th>Estado</th></tr></thead><tbody>';

foreach ($migraciones as $m) {
    $PSN = new DBbase_Sql;
    $PSN->query($m['check']);
    if ($PSN->num_rows() > 0) {
        echo '<tr class="table-success"><td>'.$m['desc'].'</td><td><strong>&#10003; Ya existe</strong></td></tr>';
    } else {
        $PSN2 = new DBbase_Sql;
        $ok = $PSN2->query($m['sql']);
        if ($ok !== false) {
            echo '<tr class="table-success"><td>'.$m['desc'].'</td><td><strong>&#10003; Ejecutada OK</strong></td></tr>';
        } else {
            echo '<tr class="table-danger"><td>'.$m['desc'].'</td><td><strong>&#10007; Error al ejecutar</strong><br><small>'.$m['sql'].'</small></td></tr>';
        }
    }
}

echo '</tbody></table>';
echo '<div class="alert alert-warning mt-3"><strong>Importante:</strong> Elimina este archivo del servidor una vez ejecutadas las migraciones.</div>';
echo '</body></html>';
?>
