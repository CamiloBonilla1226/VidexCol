// Tooltips del glosario: elementos con data-glosario="N" y data-glosario-texto
(function () {
    var tip = null;
    var actual = null;

    function crear() {
        if (tip) return;
        tip = document.createElement('div');
        tip.className = 'glosario-tooltip';
        tip.setAttribute('role', 'tooltip');
        tip.id = 'glosarioTooltip';
        document.body.appendChild(tip);
    }

    function mostrar(el) {
        var texto = el.getAttribute('data-glosario-texto');
        if (!texto) return;
        crear();
        actual = el;
        tip.textContent = texto;
        el.setAttribute('aria-describedby', 'glosarioTooltip');
        var r = el.getBoundingClientRect();
        var sx = window.pageXOffset, sy = window.pageYOffset;
        var w = tip.offsetWidth, h = tip.offsetHeight;
        var left = r.left + sx + r.width / 2 - w / 2;
        left = Math.max(sx + 8, Math.min(left, sx + document.documentElement.clientWidth - w - 8));
        var top = r.top + sy - h - 8;
        if (top < sy + 4) top = r.bottom + sy + 8;
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        tip.classList.add('visible');
    }

    function ocultar() {
        if (!tip) return;
        tip.classList.remove('visible');
        if (actual) actual.removeAttribute('aria-describedby');
        actual = null;
    }

    function objetivo(e) {
        return e.target.closest ? e.target.closest('[data-glosario]') : null;
    }

    document.addEventListener('mouseover', function (e) {
        var el = objetivo(e);
        if (el && el !== actual) mostrar(el);
    });
    document.addEventListener('mouseout', function (e) {
        var el = objetivo(e);
        if (el && !el.contains(e.relatedTarget)) ocultar();
    });
    document.addEventListener('focusin', function (e) {
        var el = objetivo(e);
        if (el) mostrar(el);
    });
    document.addEventListener('focusout', ocultar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') ocultar();
    });
    window.addEventListener('scroll', ocultar, true);
    // Modal de definición: elementos con data-glosario-modal abren la definición al dar click
    var overlay = null;

    function crearModal() {
        if (overlay) return;
        overlay = document.createElement('div');
        overlay.className = 'glosario-modal-overlay';
        overlay.innerHTML = '<div class="glosario-modal" role="dialog" aria-modal="true" aria-labelledby="glosarioModalTitulo">' +
            '<div class="glosario-modal-header"><span class="glosario-modal-icono">i</span><h4 id="glosarioModalTitulo"></h4>' +
            '<button type="button" class="glosario-modal-close" aria-label="Cerrar">&#10005;</button></div>' +
            '<div class="glosario-modal-body"><p></p></div>' +
            '<div class="glosario-modal-footer"><a class="glosario-modal-enlace" href="#" hidden>Ver en el glosario</a>' +
            '<button type="button" class="glosario-modal-ok">Entendido</button></div></div>';
        document.body.appendChild(overlay);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.className === 'glosario-modal-close' || e.target.className === 'glosario-modal-ok') cerrarModal();
        });
    }

    // enlace (opcional): URL de la definición en la sección Glosario
    function abrirModalTexto(titulo, texto, enlace) {
        if (!texto) return;
        ocultar();
        crearModal();
        overlay.querySelector('h4').textContent = titulo;
        overlay.querySelector('p').textContent = texto;
        var a = overlay.querySelector('.glosario-modal-enlace');
        a.hidden = !enlace;
        a.setAttribute('href', enlace || '#');
        overlay.classList.add('visible');
        overlay.querySelector('.glosario-modal-ok').focus();
    }

    function abrirModal(el) {
        abrirModalTexto(el.getAttribute('data-glosario-titulo') || el.textContent.trim(), el.getAttribute('data-glosario-texto'), el.getAttribute('data-glosario-enlace'));
    }

    // Para usar desde el código (por ejemplo, gráficas): Glosario.modal(titulo, texto)
    window.Glosario = { modal: abrirModalTexto };

    function cerrarModal() {
        if (overlay) overlay.classList.remove('visible');
    }

    // En captura, para que el click en el icono no active el botón que lo contiene
    document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('[data-glosario-modal]') : null;
        if (el) {
            e.preventDefault();
            e.stopPropagation();
            abrirModal(el);
        }
    }, true);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarModal();
        var el = e.target.closest ? e.target.closest('[data-glosario-modal]') : null;
        if (el && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            abrirModal(el);
        }
    });

})();
