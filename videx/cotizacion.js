(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function setActiveTab(tabId, tabs, panes) {
        tabs.forEach(function (tabLink) {
            var isActive = tabLink.getAttribute('href') === '#' + tabId;
            tabLink.parentElement.classList.toggle('active', isActive);
            tabLink.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panes.forEach(function (pane) {
            var isActive = pane.id === tabId;
            pane.classList.toggle('active', isActive);
            pane.classList.toggle('in', isActive);
            pane.style.display = isActive ? 'block' : 'none';
        });
    }

    function initTabs() {
        var tabLinks = Array.prototype.slice.call(document.querySelectorAll('.nav-tabs a[data-toggle="tab"]'));
        var panes = Array.prototype.slice.call(document.querySelectorAll('.tab-pane'));

        if (!tabLinks.length || !panes.length) {
            return;
        }

        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('click', function (event) {
                var target = tabLink.getAttribute('href');
                if (!target || target.charAt(0) !== '#') {
                    return;
                }

                event.preventDefault();
                setActiveTab(target.substring(1), tabLinks, panes);
            });
        });

        var activeTab = tabLinks.find(function (tabLink) {
            return tabLink.parentElement.classList.contains('active');
        }) || tabLinks[0];

        if (activeTab) {
            var target = activeTab.getAttribute('href');
            if (target && target.charAt(0) === '#') {
                setActiveTab(target.substring(1), tabLinks, panes);
            }
        }
    }

    function ensureModalShell() {
        var existing = document.getElementById('cotizacion-inline-modal');
        if (existing) {
            return existing;
        }

        var modal = document.createElement('div');
        modal.id = 'cotizacion-inline-modal';
        modal.className = 'cotizacion-modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = [
            '<div class="cotizacion-modal__panel" role="dialog" aria-modal="true">',
            '  <div class="cotizacion-modal__header">',
            '    <div class="cotizacion-modal__title" data-cotizacion-modal-title>Detalle</div>',
            '    <button type="button" class="cotizacion-modal__close" aria-label="Cerrar">&times;</button>',
            '  </div>',
            '  <div class="cotizacion-modal__body">',
            '    <iframe title="Cotizacion modal" data-cotizacion-modal-frame></iframe>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(modal);

        var closeButton = modal.querySelector('.cotizacion-modal__close');
        var panel = modal.querySelector('.cotizacion-modal__panel');

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modal.querySelector('[data-cotizacion-modal-frame]').src = 'about:blank';
        }

        function openModal(title, url) {
            modal.querySelector('[data-cotizacion-modal-title]').textContent = title;
            modal.querySelector('[data-cotizacion-modal-frame]').src = url;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        closeButton.addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        modal.openCotizacionModal = openModal;
        modal.closeCotizacionModal = closeModal;

        return modal;
    }

    function openLegacyModal(title, url) {
        ensureModalShell().openCotizacionModal(title, url);
    }

    function getCotizacionId() {
        var idField = document.querySelector('input[name="id"], input[name="idCotizacion"]');
        if (idField && idField.value) {
            return idField.value;
        }

        var match = window.location.search.match(/[?&]id=(\d+)/);
        return match ? match[1] : '0';
    }

    function bindModalActions() {
        window.asignarObservacion = function () {
            openLegacyModal('Nuevo seguimiento', 'pop_up.php?doc=pop_seguimiento&id=' + getCotizacionId());
        };

        window.asignarAbono = function () {
            openLegacyModal('Nuevo abono', 'pop_up.php?doc=pop_abono&id=' + getCotizacionId());
        };

        window.asignarGasto = function () {
            openLegacyModal('Nuevo gasto', 'pop_up.php?doc=pop_gasto&id=' + getCotizacionId());
        };

        window.closeCotizacionModal = function () {
            var modal = document.getElementById('cotizacion-inline-modal');
            if (modal && modal.closeCotizacionModal) {
                modal.closeCotizacionModal();
            }
        };
    }

    function ensurePageClass() {
        if (document.body && !document.body.classList.contains('cotizacion-page')) {
            document.body.classList.add('cotizacion-page');
        }
    }

    function init() {
        ensurePageClass();
        initTabs();
        bindModalActions();
        ensureModalShell();
    }

    onReady(init);
})();