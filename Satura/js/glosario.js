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
})();
