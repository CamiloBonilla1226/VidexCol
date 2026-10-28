// Sección Glosario (libro de dos páginas). Lee GLOSARIO = [[numero, termino, definicion], ...]
// definido en glosario_graficas.php desde el helper glosario.php.
(function () {
    'use strict';

    if (typeof GLOSARIO === 'undefined') return;

    var porId = function (id) { return document.getElementById(id); };
    var $raiz = porId('glosario'), $izq = porId('glPagIzq'), $der = porId('glPagDer');
    var $buscar = porId('glBuscar'), $letras = porId('glLetras');
    var $prev = porId('glBtnPrev'), $next = porId('glBtnNext'), $estado = porId('glEstado');
    if (!$raiz) return;

    var letra = null;      // null = todas
    var spread = 0;        // índice de la doble página actual

    var esc = function (s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    var norm = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); };
    var letraDe = function (t) { return norm(t.trim())[0].toUpperCase(); };

    // 5 definiciones por página: 10 por doble página en escritorio, 5 en móvil
    var porPagina = function () { return 5; };
    var esMovil = function () { return window.matchMedia('(max-width:820px)').matches; };
    var porSpread = function () { return esMovil() ? porPagina() : porPagina() * 2; };

    function coincide(d, q) {
        return !q || norm(d[1]).indexOf(q) >= 0 || norm(d[2]).indexOf(q) >= 0;
    }

    function resaltar(texto, q) {
        if (!q) return esc(texto);
        var i = norm(texto).indexOf(q);
        if (i < 0) return esc(texto);
        return esc(texto.slice(0, i)) + '<mark>' + esc(texto.slice(i, i + q.length)) + '</mark>' + esc(texto.slice(i + q.length));
    }

    function itemHTML(d, q, capital) {
        var n = d[0];
        return '<p class="gl-def' + (capital ? ' capital' : '') + '" id="def-' + n + '">' +
            '<a class="gl-num" href="#def-' + n + '" title="Enlace a la definición ' + n + '">' + n + '</a>' +
            '<span class="gl-term">' + resaltar(d[1], q) + '</span>' + resaltar(d[2], q) + '</p>';
    }

    // Lista filtrada (búsqueda y letra) en orden alfabético A–Z
    function obtenerLista() {
        var q = norm($buscar.value.trim());
        var l = GLOSARIO.filter(function (d) { return coincide(d, q); });
        if (letra) l = l.filter(function (d) { return letraDe(d[1]) === letra; });
        l.sort(function (a, b) { return norm(a[1]).localeCompare(norm(b[1]), 'es'); });
        return { lista: l, q: q };
    }

    function pintarLetras() {
        var q = norm($buscar.value.trim());
        var disponibles = {};
        GLOSARIO.forEach(function (d) { if (coincide(d, q)) disponibles[letraDe(d[1])] = true; });
        var todas = [];
        GLOSARIO.forEach(function (d) { var l = letraDe(d[1]); if (todas.indexOf(l) < 0) todas.push(l); });
        todas.sort(function (a, b) { return a.localeCompare(b, 'es'); });
        $letras.innerHTML =
            '<button type="button" data-l="" class="' + (letra ? '' : 'activa') + '">Todas</button>' +
            todas.map(function (l) {
                return '<button type="button" data-l="' + l + '" class="' + (letra === l ? 'activa' : '') + '"' +
                    (disponibles[l] ? '' : ' disabled') + '>' + l + '</button>';
            }).join('');
        $letras.classList.add('visible');
    }

    function render() {
        var r = obtenerLista(), lista = r.lista, q = r.q;
        var por = porSpread();
        var total = Math.max(1, Math.ceil(lista.length / por));
        spread = Math.min(Math.max(spread, 0), total - 1);

        pintarLetras();

        if (!lista.length) {
            $izq.innerHTML = '<p class="gl-vacio">No se encontró ningún término con esa búsqueda.</p>';
            $der.innerHTML = '';
            $estado.textContent = '—';
            $prev.disabled = $next.disabled = true;
            return;
        }

        var trozo = lista.slice(spread * por, spread * por + por);
        var mitad = esMovil() ? trozo.length : Math.ceil(trozo.length / 2);
        var izq = trozo.slice(0, mitad), der = trozo.slice(mitad);

        var rotulo = letra ? 'Letra ' + letra : 'Orden alfabético';
        var pagIzqNum = esMovil() ? spread + 1 : spread * 2 + 1;

        $izq.innerHTML = '<div class="gl-cab">' + rotulo + '</div>' +
            izq.map(function (d, i) { return itemHTML(d, q, i === 0); }).join('') +
            '<div class="gl-pie-num">' + pagIzqNum + '</div>';
        $der.innerHTML = der.length
            ? '<div class="gl-cab">' + rotulo + '</div>' +
              der.map(function (d, i) { return itemHTML(d, q, i === 0); }).join('') +
              '<div class="gl-pie-num">' + (pagIzqNum + 1) + '</div>'
            : '';

        $estado.textContent = esMovil()
            ? 'Página ' + (spread + 1) + ' de ' + total
            : 'Páginas ' + pagIzqNum + '–' + (pagIzqNum + 1) + ' de ' + (total * 2);
        $prev.disabled = spread === 0;
        $next.disabled = spread >= total - 1;
    }

    // Ir a una definición concreta (para enlaces #def-44)
    function irADefinicion(n) {
        $buscar.value = '';
        letra = null;
        var lista = obtenerLista().lista;
        var idx = -1;
        for (var i = 0; i < lista.length; i++) {
            if (lista[i][0] === n) { idx = i; break; }
        }
        if (idx < 0) return;
        spread = Math.floor(idx / porSpread());
        render();
        var el = porId('def-' + n);
        if (el) {
            el.classList.add('resaltada');
            el.scrollIntoView({ block: 'center' });
            setTimeout(function () { el.classList.remove('resaltada'); }, 2500);
        }
    }

    function irAlInicio() {
        $raiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function desdeHash() {
        var m = location.hash.match(/^#def-(\d+)$/);
        if (m) irADefinicion(+m[1]);
    }

    $buscar.addEventListener('input', function () { spread = 0; render(); });
    $letras.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b || b.disabled) return;
        letra = b.getAttribute('data-l') || null;
        spread = 0;
        render();
    });
    $prev.addEventListener('click', function () { spread--; render(); irAlInicio(); });
    $next.addEventListener('click', function () { spread++; render(); irAlInicio(); });
    document.addEventListener('keydown', function (e) {
        var t = e.target;
        if (t === $buscar || (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)))) return;
        if (e.key === 'ArrowLeft' && !$prev.disabled) $prev.click();
        if (e.key === 'ArrowRight' && !$next.disabled) $next.click();
    });
    window.addEventListener('resize', render);
    window.addEventListener('hashchange', desdeHash);

    render();
    desdeHash();
})();
