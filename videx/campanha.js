/**
 * Campaign Module JavaScript - Refactored
 * Consolidated validation, real-time feedback, toast notifications
 *
 * Improvements:
 * - 3 functions (submitFormCreate/Update/Delete) → 1 consolidated (submitCampaignForm)
 * - Real-time field validation
 * - Toast notification system
 * - Clear all filters with JS
 * - Mobile-friendly interactions
 *
 * @package VIDEX
 * @version 2.0
 */

/**
 * Consolidated Campaign Form Submission
 * Handles create, update, and delete actions
 */
function submitCampaignForm(action = 'create') {
    const form = document.getElementById('form1');
    const nombre = document.getElementById('nombre').value.trim();
    const fechaInicial = document.getElementById('fechaInicial').value.trim();
    const fechaFinal = document.getElementById('fechaFinal').value.trim();
    const tipoCampana = document.getElementById('tipoCampana').value;

    // Validation
    if (!nombre) {
        showFieldError('nombre', 'Por favor ingrese el nombre de la campaña');
        return false;
    }

    if (!tipoCampana) {
        showFieldError('tipoCampana', 'Por favor seleccione el tipo de campaña');
        return false;
    }

    if (!fechaInicial) {
        showFieldError('fechaInicial', 'Por favor seleccione la fecha inicial');
        return false;
    }

    if (!fechaFinal) {
        showFieldError('fechaFinal', 'Por favor seleccione la fecha final');
        return false;
    }

    if (new Date(fechaFinal) <= new Date(fechaInicial)) {
        showFieldError('fechaFinal', 'La fecha final debe ser posterior a la fecha inicial');
        return false;
    }

    // Clear previous errors
    clearFieldErrors();

    // Confirmation based on action
    let confirmMsg = '';
    if (action === 'create') {
        confirmMsg = '¿Está seguro que desea crear esta campaña?';
    } else if (action === 'update') {
        confirmMsg = '¿Está seguro que desea actualizar esta campaña?';
    } else if (action === 'delete') {
        confirmMsg = 'ADVERTENCIA: ¿Está SEGURO que desea ELIMINAR la campaña "' + nombre + '"? Esta acción no se puede deshacer.';
    }

    if (confirm(confirmMsg)) {
        document.getElementById('funcion').value =
            (action === 'create') ? 'insertar' :
            (action === 'update') ? 'actualizar' : 'eliminar';
        form.submit();
    }

    return false;
}

/**
 * Delete Campaign with Modal Confirmation
 */
function confirmDelete() {
    const nombre = document.getElementById('nombre').value.trim();
    if (!nombre) {
        alert('Error: No se pudo obtener el nombre de la campaña');
        return false;
    }

    if (confirm('ADVERTENCIA: ¿ESTÁ SEGURO que desea ELIMINAR la campaña "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        submitCampaignForm('delete');
    }
    return false;
}

/**
 * Show Field-Level Error
 */
function showFieldError(fieldId, errorMsg) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.add('is-invalid');

    // Remove existing error message
    const existingError = field.parentElement.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }

    // Add new error message
    const errorEl = document.createElement('div');
    errorEl.className = 'invalid-feedback d-block';
    errorEl.innerHTML = errorMsg;
    field.parentElement.appendChild(errorEl);

    // Scroll to field
    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    field.focus();
}

/**
 * Clear All Field Errors
 */
function clearFieldErrors() {
    document.querySelectorAll('.form-control.is-invalid').forEach(field => {
        field.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(error => {
        error.remove();
    });
}

/**
 * Clear All Filters
 */
function clearAllFilters() {
    document.getElementById('nombre').value = '';
    document.getElementById('tipoCampana').value = '';
    document.getElementById('estado').value = '';
    document.getElementById('fechaInicial').value = '';
    document.getElementById('fechaFinal').value = '';

    // Redirect to clean list
    window.location.href = 'index.php?doc=campanha&opc=2';
}

/**
 * Toggle Mobile Row Details
 */
function toggleRowDetails(btn) {
    const row = btn.closest('tr');
    const detailsRow = row.nextElementSibling;

    if (detailsRow && detailsRow.classList.contains('campaign-details')) {
        detailsRow.classList.toggle('d-none');
        btn.classList.toggle('active');
    }
}

/**
 * Real-time Field Validation
 */
document.addEventListener('DOMContentLoaded', function() {
    // Form submit handlers
    const form = document.getElementById('form1');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Server will validate, this is just UI enhancement
        });
    }

    // Auto-submit filter form on select change (Optional)
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const selects = filterForm.querySelectorAll('select');
        // Note: Don't auto-submit on change for better UX - let user use Search button
    }

    // Auto-focus on first form field
    const firstField = form ? form.elements[0] : null;
    if (firstField && firstField.name !== 'doc' && firstField.name !== 'opc' && firstField.name !== 'id' && firstField.name !== 'funcion') {
        // Don't auto-focus to avoid focus on page load issues
    }

    // Real-time date validation
    const fechaInicial = document.getElementById('fechaInicial');
    const fechaFinal = document.getElementById('fechaFinal');

    if (fechaInicial && fechaFinal) {
        const validateDates = function() {
            if (fechaInicial.value && fechaFinal.value) {
                const start = new Date(fechaInicial.value);
                const end = new Date(fechaFinal.value);
                if (end <= start) {
                    fechaFinal.classList.add('is-invalid');
                    if (!fechaFinal.parentElement.querySelector('.invalid-feedback')) {
                        const errorEl = document.createElement('div');
                        errorEl.className = 'invalid-feedback d-block';
                        errorEl.textContent = 'Debe ser posterior a fecha inicial';
                        fechaFinal.parentElement.appendChild(errorEl);
                    }
                } else {
                    fechaFinal.classList.remove('is-invalid');
                    const error = fechaFinal.parentElement.querySelector('.invalid-feedback');
                    if (error) error.remove();
                }
            }
        };

        fechaInicial.addEventListener('change', validateDates);
        fechaFinal.addEventListener('change', validateDates);
    }

    // Enter key to submit search
    const nombreInput = document.getElementById('nombre');
    if (filterForm && nombreInput) {
        nombreInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterForm.submit();
            }
        });
    }

    // Expandable row details for mobile
    document.querySelectorAll('.campaign-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Optional: expand details on row click (excluding action buttons)
            if (!e.target.closest('a, button')) {
                const detailsRow = this.nextElementSibling;
                if (detailsRow && detailsRow.classList.contains('campaign-details')) {
                    detailsRow.classList.toggle('d-none');
                }
            }
        });
    });

    // Toast auto-dismiss (5 seconds)
    const toast = document.querySelector('.toast');
    if (toast) {
        setTimeout(function() {
            toast.classList.remove('show');
        }, 5000);
    }

    console.log('Campaign module initialized');
});

/**
 * Load Campaign Modal - Fetch data via AJAX and populate form
 */
function loadCampaignModal(campaignId) {
    const modal = document.getElementById('editCampaignModal');
    const form = document.getElementById('editCampaignForm');
    const loading = document.getElementById('modalLoading');

    // Show loading
    loading.classList.remove('d-none');
    form.classList.add('d-none');

    // Fetch campaign data
    fetch('index.php?doc=campanha&action=get_campaign&id=' + campaignId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.campaign) {
                populateModalForm(data.campaign, data.categories);
                loading.classList.add('d-none');
                form.classList.remove('d-none');

                // Show modal
                if (typeof jQuery !== 'undefined') {
                    jQuery(modal).modal('show');
                } else {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                }
            } else {
                alert('Error al cargar la campaña: ' + (data.error || 'Error desconocido'));
                loading.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la campaña');
            loading.classList.add('d-none');
        });
}

/**
 * Populate Modal Form with Campaign Data
 */
function populateModalForm(campaign, categories) {
    document.getElementById('modalCampaignId').value = campaign.id;
    document.getElementById('modalNombre').value = campaign.nombre || '';
    document.getElementById('modalTipoCampana').value = campaign.tipoCampana || '';
    document.getElementById('modalEstado').value = campaign.estado || '1';
    document.getElementById('modalFechaInicial').value = campaign.fechaInicial || '';
    document.getElementById('modalFechaFinal').value = campaign.fechaFinal || '';
    document.getElementById('modalObservaciones').value = campaign.observaciones || '';

    // Populate category select
    const select = document.getElementById('modalTipoCampana');
    select.innerHTML = '<option value="">-- Seleccionar --</option>';
    for (let id in categories) {
        const option = document.createElement('option');
        option.value = id;
        option.textContent = categories[id];
        if (id == campaign.tipoCampana) {
            option.selected = true;
        }
        select.appendChild(option);
    }
}

/**
 * Submit Modal Edit Form
 */
function submitModalEdit() {
    const form = document.getElementById('editCampaignForm');
    const nombre = document.getElementById('modalNombre').value.trim();
    const fechaInicial = document.getElementById('modalFechaInicial').value.trim();
    const fechaFinal = document.getElementById('modalFechaFinal').value.trim();
    const tipoCampana = document.getElementById('modalTipoCampana').value;

    // Validation
    if (!nombre) {
        alert('Por favor ingrese el nombre de la campaña');
        return false;
    }

    if (!tipoCampana) {
        alert('Por favor seleccione el tipo de campaña');
        return false;
    }

    if (!fechaInicial || !fechaFinal) {
        alert('Por favor complete las fechas');
        return false;
    }

    if (new Date(fechaFinal) <= new Date(fechaInicial)) {
        alert('La fecha final debe ser posterior a la fecha inicial');
        return false;
    }

    if (confirm('¿Está seguro que desea actualizar esta campaña?')) {
        form.submit();
    }

    return false;
}

/**
 * Delete Campaign from Modal
 */
function submitModalDelete() {
    const nombre = document.getElementById('modalNombre').value.trim();

    if (confirm('ADVERTENCIA: ¿ESTÁ SEGURO que desea ELIMINAR la campaña "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        const form = document.getElementById('editCampaignForm');
        const funcionInput = form.querySelector('input[name="funcion"]');
        funcionInput.value = 'eliminar';
        form.submit();
    }

    return false;
}
