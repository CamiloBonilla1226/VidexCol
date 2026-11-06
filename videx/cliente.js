/**
 * Cliente Module - Client-side Logic
 * Form validation, interactions, confirmations
 *
 * @package VIDEX
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Cliente module initialized');
});

/**
 * Submit client form
 */
function submitClientForm(action) {
    const form = document.getElementById('clienteForm');
    if (!form) {
        console.error('Form not found');
        return false;
    }

    // Validate required fields
    const nombre = document.getElementById('nombre');
    if (!nombre || nombre.value.trim() === '') {
        alert('Por favor ingrese el nombre del cliente');
        nombre?.focus();
        return false;
    }

    const tipoCliente = document.getElementById('tipoCliente');
    if (!tipoCliente || tipoCliente.value === '') {
        alert('Por favor seleccione tipo de cliente');
        tipoCliente?.focus();
        return false;
    }

    const idComercial = document.getElementById('idComercial');
    if (!idComercial || idComercial.value === '') {
        alert('Por favor seleccione comercial encargado');
        idComercial?.focus();
        return false;
    }

    // Set hidden action field
    const funcionField = document.getElementById('funcion');
    if (funcionField) {
        funcionField.value = action;
    }

    // Show confirmation
    const actionText = (action === 'insertar') ? 'crear' : 'actualizar';
    if (!confirm('¿Está seguro que desea ' + actionText + ' este cliente?')) {
        return false;
    }

    // Submit form
    form.submit();
    return false;
}

/**
 * Confirm delete with modal-style confirmation
 */
function confirmDelete(clientId, clientName) {
    const message = '¿ESTÁ SEGURO que desea ELIMINAR el cliente "' + clientName + '"?\n\n' +
                    'Esta acción no se puede deshacer.\n\n' +
                    '(El cliente será marcado como inactivo en la base de datos)';

    if (confirm(message)) {
        const form = document.getElementById('clienteForm');
        if (form) {
            document.getElementById('funcion').value = 'eliminar';
            form.submit();
        }
    }
    return false;
}

/**
 * Clear all filters instantly
 */
function clearFilters() {
    // Get current form
    const filterForm = document.querySelector('.cliente-filter-form');
    if (filterForm) {
        // Reset all input values
        filterForm.querySelectorAll('input[type="text"]').forEach(el => {
            el.value = '';
        });
        filterForm.querySelectorAll('select').forEach(el => {
            if (!el.name.startsWith('doc') && !el.name.startsWith('opc')) {
                el.value = '';
            }
        });
        // Submit the form
        filterForm.submit();
    }
    return false;
}

/**
 * Auto-submit filter form on select change
 */
function setupAutoSubmitFilters() {
    const filterForm = document.querySelector('.cliente-filter-form');
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
}

/**
 * Real-time validation as user types
 */
function setupRealtimeValidation() {
    const nombreInput = document.getElementById('nombre');
    if (nombreInput) {
        nombreInput.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    }

    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    }

    const telefonoInputs = [
        document.getElementById('telefono'),
        document.getElementById('celular')
    ];

    telefonoInputs.forEach(input => {
        if (input) {
            input.addEventListener('blur', function() {
                if (this.value && !isNumeric(this.value)) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }
    });
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Check if string contains only numbers
 */
function isNumeric(str) {
    return /^\d+$/.test(str);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', setupRealtimeValidation);
document.addEventListener('DOMContentLoaded', setupAutoSubmitFilters);
