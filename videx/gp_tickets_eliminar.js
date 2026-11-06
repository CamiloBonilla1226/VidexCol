/**
 * Tickets/Tareas - Eliminar ticket con modal de confirmacion.
 * Lo usan la lista (gp_tickets.php) y el detalle (gp_ticket.php).
 *
 *   gptEliminarTicket({ id, titulo, csrf, redirect })
 *
 * El permiso real se valida en el servidor (gp_ticket_ajax.php); el boton solo
 * se muestra a quien puede eliminar. Tras eliminar: va a `redirect`, o recarga.
 */
(function () {
    var overlay = null;
    var cfg = null;
    var focoPrevio = null;
    var enCurso = false;

    function construir() {
        if (overlay) { return; }
        overlay = document.createElement('div');
        overlay.className = 'gpt-modal-overlay';
        overlay.hidden = true;
        overlay.innerHTML =
            '<div class="gpt-modal" role="alertdialog" aria-modal="true" aria-labelledby="gptModalTitulo" aria-describedby="gptModalTexto">' +
            '  <div class="gpt-modal-icono"><i class="bi bi-trash3"></i></div>' +
            '  <h3 id="gptModalTitulo">¿Eliminar este ticket?</h3>' +
            '  <p id="gptModalTexto" class="gpt-modal-texto">El ticket dejará de aparecer en las listas del solicitante, el encargado y los colaboradores. Esta acción no se puede deshacer desde la plataforma.</p>' +
            '  <div class="gpt-modal-ticket"><i class="bi bi-ticket-detailed"></i><span id="gptModalNombre"></span></div>' +
            '  <div class="gpt-modal-error" id="gptModalError" role="alert" hidden></div>' +
            '  <div class="gpt-modal-acciones">' +
            '    <button type="button" class="gpt-btn gpt-btn-secundario" id="gptModalCancelar">Cancelar</button>' +
            '    <button type="button" class="gpt-btn gpt-btn-peligro" id="gptModalConfirmar"><i class="bi bi-trash3"></i> <span>Sí, eliminar</span></button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function (e) { if (e.target === overlay && !enCurso) { cerrar(); } });
        document.getElementById('gptModalCancelar').addEventListener('click', function () { if (!enCurso) { cerrar(); } });
        document.getElementById('gptModalConfirmar').addEventListener('click', confirmar);
        document.addEventListener('keydown', function (e) {
            if (overlay.hidden) { return; }
            if (e.key === 'Escape' && !enCurso) { e.preventDefault(); cerrar(); return; }
            if (e.key === 'Tab') {
                // El foco se queda dentro del modal (solo hay dos botones).
                var botones = [document.getElementById('gptModalCancelar'), document.getElementById('gptModalConfirmar')].filter(function (b) { return !b.disabled; });
                if (!botones.length) { e.preventDefault(); return; }
                var i = botones.indexOf(document.activeElement);
                var siguiente = e.shiftKey ? (i <= 0 ? botones.length - 1 : i - 1) : (i === botones.length - 1 ? 0 : i + 1);
                e.preventDefault();
                botones[siguiente].focus();
            }
        });
    }

    function cerrar() {
        overlay.hidden = true;
        document.body.classList.remove('gpt-modal-abierto');
        if (focoPrevio && focoPrevio.focus) { focoPrevio.focus(); }
        focoPrevio = null;
    }

    function mostrarError(msg) {
        var el = document.getElementById('gptModalError');
        el.textContent = msg;
        el.hidden = !msg;
    }

    function confirmar() {
        if (enCurso || !cfg) { return; }
        enCurso = true;
        var btn = document.getElementById('gptModalConfirmar');
        var cancelar = document.getElementById('gptModalCancelar');
        var etiqueta = btn.querySelector('span');
        btn.disabled = true;
        cancelar.disabled = true;
        btn.classList.add('gpt-cargando');
        etiqueta.textContent = 'Eliminando...';
        mostrarError('');

        function restaurar() {
            enCurso = false;
            btn.disabled = false;
            cancelar.disabled = false;
            btn.classList.remove('gpt-cargando');
            etiqueta.textContent = 'Sí, eliminar';
        }

        var datos = new FormData();
        datos.append('accion', 'eliminar');
        datos.append('id', cfg.id);
        datos.append('csrf_token', cfg.csrf);

        fetch('gp_ticket_ajax.php', { method: 'POST', body: datos, credentials: 'same-origin' })
            .then(function (r) { return r.json().catch(function () { return { error: 'Respuesta inesperada del servidor.' }; }); })
            .then(function (resp) {
                if (resp && resp.ok) {
                    try { sessionStorage.setItem('gptTicketEliminado', resp.titulo || cfg.titulo || ''); } catch (e) {}
                    if (cfg.redirect) { window.location.href = cfg.redirect; } else { window.location.reload(); }
                    return;
                }
                restaurar();
                mostrarError((resp && resp.error) || 'No se pudo eliminar el ticket.');
            })
            .catch(function () {
                restaurar();
                mostrarError('No se pudo conectar con el servidor. Revise su conexión e intente de nuevo.');
            });
    }

    window.gptEliminarTicket = function (opciones) {
        construir();
        cfg = opciones;
        enCurso = false;
        focoPrevio = document.activeElement;
        document.getElementById('gptModalNombre').textContent = opciones.titulo || ('Ticket #' + opciones.id);
        mostrarError('');
        overlay.hidden = false;
        document.body.classList.add('gpt-modal-abierto');
        // El foco inicial va en "Cancelar": eliminar nunca queda a un Enter de distancia.
        document.getElementById('gptModalCancelar').focus();
    };

    // Aviso tras eliminar (la pagina se recarga o redirige a la lista).
    document.addEventListener('DOMContentLoaded', function () {
        var titulo = null;
        try { titulo = sessionStorage.getItem('gptTicketEliminado'); sessionStorage.removeItem('gptTicketEliminado'); } catch (e) {}
        if (titulo === null) { return; }
        var aviso = document.createElement('div');
        aviso.className = 'gpt-toast';
        aviso.setAttribute('role', 'status');
        aviso.innerHTML = '<i class="bi bi-check-circle-fill"></i> <span></span>';
        aviso.querySelector('span').textContent = titulo ? 'Ticket eliminado: ' + titulo : 'Ticket eliminado.';
        document.body.appendChild(aviso);
        setTimeout(function () { aviso.classList.add('gpt-toast-visible'); }, 30);
        setTimeout(function () { aviso.classList.remove('gpt-toast-visible'); setTimeout(function () { aviso.remove(); }, 400); }, 4500);
    });
})();
