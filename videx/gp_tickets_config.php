<?php
/**
 * Tickets/Tareas - Configuracion
 * Constantes y helpers de permiso. Sigue el mismo patron que gp_config.php.
 */

const GP_TICKET_ESTADOS = [
    'solicitado' => 'Solicitado',
    'revisado_pendiente' => 'Revisado - Pendiente',
    'en_ejecucion' => 'En ejecución',
    'finalizado' => 'Finalizado',
];

const GP_TICKET_ARCHIVOS_PERMITIDOS = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];

const GP_TICKET_ARCHIVO_MAX_BYTES = 10485760; // 10 MB

/*
 * Permisos del modulo (parametrizables por usuario desde Usuario > Acceso):
 * - gp_tickets_consulta: puede ver el modulo
 * - gp_tickets_modificacion: puede crear/editar tickets y escribir en el chat
 * - gp_tickets_fechas: puede editar manualmente fecha de creacion/inicio/fin
 * No son excluyentes entre si.
 */
function gp_tickets_puede_consultar() {
    return isset($_SESSION['gp_tickets_consulta']) && $_SESSION['gp_tickets_consulta'] == 1;
}

function gp_tickets_puede_modificar() {
    return isset($_SESSION['gp_tickets_modificacion']) && $_SESSION['gp_tickets_modificacion'] == 1;
}

function gp_tickets_puede_modificar_fechas() {
    return isset($_SESSION['gp_tickets_fechas']) && $_SESSION['gp_tickets_fechas'] == 1;
}

/*
 * Roles POR TICKET (ademas de los permisos de modulo de arriba):
 * - Administrador del sistema (superusuario): ve y edita todos los tickets.
 * - Solicitante (quien lo creo) y Encargado (asignado_a): ven y editan el ticket
 *   (titulo, descripcion, estado, proyecto, encargado, colaboradores).
 * - Colaborador: ve el ticket y SOLO puede escribir en el seguimiento (chat).
 * - Cualquier otro usuario: no ve el ticket, aunque tenga acceso al modulo.
 * Crear tickets nuevos sigue requiriendo gp_tickets_modificacion. La logica de
 * quien puede que esta en GpTicketService (puedeVerTicket / puedeEditarTicket)
 * y se aplica en backend; el frontend solo oculta controles por comodidad.
 */
function gp_tickets_es_admin() {
    return isset($_SESSION['superusuario']) && (int) $_SESSION['superusuario'] === 1;
}

function gp_tickets_requerir_acceso() {
    if (!gp_tickets_puede_consultar() && !gp_tickets_puede_modificar()) {
        die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;">
            <h3>No tiene permiso para acceder a este modulo.</h3>
            <p>Solicite acceso a Tickets/Tareas a un administrador.</p>
        </div>');
    }
}

function gp_tickets_requerir_modificacion() {
    if (!gp_tickets_puede_modificar()) {
        http_response_code(403);
        die(json_encode(['error' => 'No tiene permiso de modificación en Tickets/Tareas.']));
    }
}

/*
 * Semaforo de la fecha estimada de entrega (campo fecha_entrega_estimada,
 * editable solo por quien puede editar el ticket: solicitante/encargado/admin,
 * ver GpTicketService::puedeEditarTicket()).
 *   verde    -> 10 dias o mas para la fecha.
 *   amarillo -> entre 4 y 9 dias ("proximo a vencer").
 *   rojo     -> 3 dias o menos, incluido vencido ("atencion urgente").
 * Devuelve null si no hay fecha o si el ticket ya esta finalizado (el plazo
 * deja de ser relevante una vez entregado).
 */
function gp_tickets_semaforo($fechaEntrega, $estado = null) {
    if (empty($fechaEntrega) || $fechaEntrega === '0000-00-00') {
        return null;
    }
    if ($estado === 'finalizado') {
        return null;
    }
    $hoy = new DateTime('today');
    $fecha = DateTime::createFromFormat('Y-m-d', substr($fechaEntrega, 0, 10));
    if (!$fecha) {
        return null;
    }
    $fecha->setTime(0, 0, 0);
    $dias = (int) $hoy->diff($fecha)->format('%r%a');

    if ($dias >= 10) {
        $color = 'verde';
        $label = 'En tiempo';
    } elseif ($dias >= 4) {
        $color = 'amarillo';
        $label = 'Próximo a vencer';
    } else {
        $color = 'rojo';
        $label = $dias < 0 ? 'Vencido' : 'Atención urgente';
    }

    return ['color' => $color, 'label' => $label, 'dias' => $dias];
}
