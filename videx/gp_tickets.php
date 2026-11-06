<?php
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');
gp_tickets_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpTicketService();
$buscar = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$estadoFiltro = isset($_GET['estado']) ? trim((string) $_GET['estado']) : '';
$proyectoId = isset($_GET['proyecto_id']) ? (int) $_GET['proyecto_id'] : 0;
$asignadoFiltro = isset($_GET['asignado_a']) ? (int) $_GET['asignado_a'] : 0;

// Se trae todo (sin filtrar por estado) para poder clasificar en una tabla
// por estado; el filtro de estado del toolbar solo decide cual seccion
// queda visible/abierta por defecto, no cuales tickets se consultan.
// Admin ve todo; el resto solo los tickets que creo, le asignaron o donde es colaborador.
$esAdmin = gp_tickets_es_admin();
$tickets = $service->listarTickets($buscar, '', $proyectoId, (int) $_SESSION['id'], $esAdmin, $asignadoFiltro);
$proyectos = $service->listarProyectosParaSelect();
$asignados = $service->listarAsignadosParaSelect((int) $_SESSION['id'], $esAdmin);

function gptIniciales($nombre) {
    $partes = preg_split('/\s+/', trim($nombre));
    $iniciales = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $iniciales .= strtoupper(substr($p, 0, 1));
    }
    return $iniciales ?: '?';
}

// Clasificacion por estado, en el orden definido en GP_TICKET_ESTADOS.
$ticketsPorEstado = array();
foreach (array_keys(GP_TICKET_ESTADOS) as $k) {
    $ticketsPorEstado[$k] = array();
}
foreach ($tickets as $t) {
    $k = $t['estado'];
    if (!isset($ticketsPorEstado[$k])) {
        $ticketsPorEstado[$k] = array();
    }
    $ticketsPorEstado[$k][] = $t;
}

// Metricas resumidas de una tabla de estado, mostradas mientras el colapso
// esta cerrado (ver gpt-estado-metrics en gp_tickets.css).
function gptMetricasEstado($lista) {
    $total = count($lista);
    $sinAsignar = 0;
    $masAntiguoDias = null;
    $semaforos = array('verde' => 0, 'amarillo' => 0, 'rojo' => 0);

    foreach ($lista as $t) {
        if (empty($t['asignado_a_nombre'])) {
            $sinAsignar++;
        }
        if (!empty($t['creacion_fecha'])) {
            $dias = (int) floor((time() - strtotime($t['creacion_fecha'])) / 86400);
            if ($masAntiguoDias === null || $dias > $masAntiguoDias) {
                $masAntiguoDias = $dias;
            }
        }
        $s = gp_tickets_semaforo($t['fecha_entrega_estimada'] ?? null, $t['estado']);
        if ($s) {
            $semaforos[$s['color']]++;
        }
    }

    return array(
        'total' => $total,
        'sin_asignar' => $sinAsignar,
        'mas_antiguo_dias' => $masAntiguoDias,
        'verde' => $semaforos['verde'],
        'amarillo' => $semaforos['amarillo'],
        'rojo' => $semaforos['rojo'],
    );
}
?>
<link rel="stylesheet" href="gp_tickets.css?v=<?= @filemtime('gp_tickets.css'); ?>" />

<div class="gpt-wrap">
    <div class="gpt-header">
        <div>
            <h1>Tickets / Tareas</h1>
            <p><?= $esAdmin ? 'Seguimiento de solicitudes y tareas de los proyectos.' : 'Sus tickets: los que creó, los que tiene a cargo y aquellos donde colabora.'; ?></p>
        </div>
        <?php if (gp_tickets_puede_modificar()) { ?>
            <a href="index.php?doc=gp_ticket&opc=1" class="gpt-btn"><i class="bi bi-plus-lg"></i> Nuevo ticket</a>
        <?php } ?>
    </div>

    <form method="get" class="gpt-toolbar">
        <input type="hidden" name="doc" value="gp_tickets" />
        <div class="gpt-search">
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Buscar por título..." value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" />
        </div>
        <select name="estado" class="gpt-select" onchange="this.form.submit()">
            <option value="">Estado: Todos</option>
            <?php foreach (GP_TICKET_ESTADOS as $k => $v) { ?>
                <option value="<?= $k; ?>" <?= $estadoFiltro === $k ? 'selected' : ''; ?>><?= $v; ?></option>
            <?php } ?>
        </select>
        <select name="proyecto_id" class="gpt-select" onchange="this.form.submit()">
            <option value="">Proyecto: Todos</option>
            <?php foreach ($proyectos as $p) { ?>
                <option value="<?= (int) $p['id']; ?>" <?= $proyectoId === (int) $p['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></option>
            <?php } ?>
        </select>
        <select name="asignado_a" class="gpt-select" onchange="this.form.submit()">
            <option value="">Asignado a: Todos</option>
            <?php foreach ($asignados as $u) { ?>
                <option value="<?= (int) $u['id']; ?>" <?= $asignadoFiltro === (int) $u['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($u['nombre'], ENT_QUOTES); ?></option>
            <?php } ?>
        </select>
        <button type="submit" class="gpt-btn gpt-btn-secundario"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>

    <div class="gpt-estados-wrap">
        <?php foreach (GP_TICKET_ESTADOS as $estadoKey => $estadoLabel) {
            if ($estadoFiltro !== '' && $estadoFiltro !== $estadoKey) {
                continue;
            }

            $lista = $ticketsPorEstado[$estadoKey];
            $metricas = gptMetricasEstado($lista);
            $abiertoPorDefecto = ($estadoFiltro === $estadoKey);
        ?>
        <details class="gpt-estado-collapse" <?= $abiertoPorDefecto ? 'open' : ''; ?>>
            <summary class="gpt-estado-summary">
                <div class="gpt-estado-summary-header">
                    <span class="gpt-badge-estado gpt-badge-<?= $estadoKey; ?>"><?= $estadoLabel; ?></span>
                    <span class="gpt-estado-total"><?= $metricas['total']; ?> ticket<?= $metricas['total'] == 1 ? '' : 's'; ?></span>
                    <i class="bi bi-chevron-down gpt-estado-arrow"></i>
                </div>
                <div class="gpt-estado-metrics gpt-meta-grid">
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-collection"></i> Total</label>
                        <div class="gpt-meta-valor"><?= $metricas['total']; ?></div>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-person-x"></i> Sin asignar</label>
                        <div class="gpt-meta-valor"><?= $metricas['sin_asignar']; ?></div>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-hourglass-split"></i> Más antiguo</label>
                        <div class="gpt-meta-valor"><?= $metricas['mas_antiguo_dias'] === null ? '—' : $metricas['mas_antiguo_dias'] . ' día' . ($metricas['mas_antiguo_dias'] == 1 ? '' : 's'); ?></div>
                    </div>
                    <div class="gpt-meta-card gpt-meta-card-semaforo">
                        <label><i class="bi bi-circle-fill" style="color:#16a34a;"></i> En tiempo</label>
                        <div class="gpt-meta-valor"><?= $metricas['verde']; ?></div>
                    </div>
                    <div class="gpt-meta-card gpt-meta-card-semaforo">
                        <label><i class="bi bi-circle-fill" style="color:#d97706;"></i> Próximo a vencer</label>
                        <div class="gpt-meta-valor"><?= $metricas['amarillo']; ?></div>
                    </div>
                    <div class="gpt-meta-card gpt-meta-card-semaforo">
                        <label><i class="bi bi-circle-fill" style="color:#dc2626;"></i> Atención urgente</label>
                        <div class="gpt-meta-valor"><?= $metricas['rojo']; ?></div>
                    </div>
                </div>
            </summary>

            <div class="gpt-card">
                <?php if (empty($lista)) { ?>
                    <div class="gpt-empty">
                        <i class="bi bi-inbox"></i>
                        <p>No hay tickets en este estado.</p>
                    </div>
                <?php } else { ?>
                    <div class="gpt-table-scroll">
                    <table class="gpt-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Proyecto</th>
                                <th>Solicitado por</th>
                                <th>Asignado a</th>
                                <th>Entrega</th>
                                <th>Fecha</th>
                                <th>Seguimiento</th>
                                <th class="gpt-col-acciones"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista as $t) {
                                $semaforoFila = gp_tickets_semaforo($t['fecha_entrega_estimada'] ?? null, $t['estado']);
                                ?>
                                <tr class="<?= $semaforoFila ? 'gpt-fila-' . $semaforoFila['color'] : ''; ?>" onclick="window.location='index.php?doc=gp_ticket&opc=2&id=<?= (int) $t['id']; ?>'">
                                    <td class="gpt-titulo-fila"><?= htmlspecialchars($t['titulo'], ENT_QUOTES); ?></td>
                                    <td><span class="gpt-badge-estado gpt-badge-<?= $t['estado']; ?>"><?= GP_TICKET_ESTADOS[$t['estado']] ?? $t['estado']; ?></span></td>
                                    <td><?= $t['proyecto_nombre'] ? htmlspecialchars($t['proyecto_nombre'], ENT_QUOTES) : '<span style="color:#cbd5e1;">—</span>'; ?></td>
                                    <td>
                                        <span class="gpt-persona">
                                            <span class="gpt-avatar"><?= gptIniciales($t['solicitado_por_nombre'] ?? '?'); ?></span>
                                            <?= htmlspecialchars($t['solicitado_por_nombre'] ?? '—', ENT_QUOTES); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($t['asignado_a_nombre']) { ?>
                                            <span class="gpt-persona">
                                                <span class="gpt-avatar"><?= gptIniciales($t['asignado_a_nombre']); ?></span>
                                                <?= htmlspecialchars($t['asignado_a_nombre'], ENT_QUOTES); ?>
                                            </span>
                                        <?php } else { ?>
                                            <span style="color:#cbd5e1;">Sin asignar</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['fecha_entrega_estimada'])) { ?>
                                            <span class="gpt-persona">
                                                <span class="gpt-semaforo-dot gpt-semaforo-<?= $semaforoFila['color'] ?? 'neutral'; ?>" title="<?= $semaforoFila ? htmlspecialchars($semaforoFila['label'], ENT_QUOTES) : 'Entregado'; ?>"></span>
                                                <?= date('d M Y', strtotime($t['fecha_entrega_estimada'])); ?>
                                            </span>
                                        <?php } else { ?>
                                            <span style="color:#cbd5e1;">Sin definir</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($t['creacion_fecha'])); ?></td>
                                    <td><span class="gpt-seguimiento-count"><i class="bi bi-chat-dots"></i> <?= (int) $t['total_mensajes']; ?></span></td>
                                    <td class="gpt-col-acciones">
                                        <?php if ($service->puedeEliminarTicket($t, (int) $_SESSION['id'], $esAdmin)) { ?>
                                            <button type="button" class="gpt-btn-fila-eliminar" title="Eliminar ticket" aria-label="Eliminar ticket: <?= htmlspecialchars($t['titulo'], ENT_QUOTES); ?>"
                                                data-id="<?= (int) $t['id']; ?>" data-titulo="<?= htmlspecialchars($t['titulo'], ENT_QUOTES); ?>"
                                                onclick="event.stopPropagation(); gptEliminarTicket({ id: this.getAttribute('data-id'), titulo: this.getAttribute('data-titulo'), csrf: GPT_CSRF });"><i class="bi bi-trash3"></i></button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    </div>
                    <div class="gpt-footer">Mostrando <?= count($lista); ?> ticket<?= count($lista) == 1 ? '' : 's'; ?></div>
                <?php } ?>
            </div>
        </details>
        <?php } ?>
    </div>
</div>

<script>var GPT_CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>';</script>
<script src="gp_tickets_eliminar.js?v=<?= @filemtime('gp_tickets_eliminar.js'); ?>"></script>
