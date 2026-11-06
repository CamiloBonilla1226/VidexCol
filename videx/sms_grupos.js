/**
 * SMS Grupos Module - Client-side Logic
 * Form validation, user interactions, AJAX operations
 *
 * @package VIDEX
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('SMS Grupos module initialized');

    // Form submission handler
    const form = document.getElementById('form1');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre');
            if (nombre && nombre.value.trim() === '') {
                alert('Por favor ingrese el nombre del grupo');
                e.preventDefault();
                return false;
            }
            return true;
        });
    }
});

/**
 * Clear all users from group with confirmation
 */
function limpiarGrupo() {
    if (confirm('¿ESTÁ SEGURO que desea ELIMINAR todos los usuarios del grupo?\n\nEsta acción no se puede deshacer.\n\nNOTA: El grupo NO será eliminado, solo los usuarios asociados.')) {
        document.getElementById('funcion').value = 'limpiar';
        document.getElementById('form1').submit();
    }
    return false;
}

/**
 * Validate bulk upload format
 */
function validateBulkUpload() {
    const textarea = document.getElementById('cargue_masivo');
    if (!textarea || textarea.value.trim() === '') {
        return true; // Optional field
    }

    const lines = textarea.value.trim().split('\n');
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line === '') continue;

        const fields = line.split('\t');
        if (fields.length < 1 || fields[0].trim() === '') {
            alert('Línea ' + (i + 1) + ': El campo "Nombre" es requerido');
            return false;
        }
    }

    return true;
}

/**
 * Format bulk upload template
 */
function getBulkUploadTemplate() {
    return `Nombre	Identificación	Dirección	Página Web	Email	Tel Fijo	Celular	Celular 2
Juan Pérez	123456789	Calle 1	www.juan.com	juan@email.com	(1)2345678	3001234567	3001234568
María García	987654321	Calle 2	www.maria.com	maria@email.com	(1)8765432	3009876543	3009876544`.replace(/\t/g, '\t');
}

/**
 * Load group data and show edit modal
 */
function loadGroupModal(groupId) {
    const modal = document.getElementById('editGroupModal');
    const loading = document.getElementById('modalLoading');
    const form = document.getElementById('editGroupForm');

    if (!modal || !loading || !form) {
        console.error('Modal elements not found');
        return;
    }

    // Show loading state
    loading.style.display = 'block';
    form.classList.add('d-none');
    modal.classList.add('show');

    // Show backdrop
    let backdrop = document.getElementById('modalBackdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'modalBackdrop';
        backdrop.className = 'modal-backdrop';
        backdrop.onclick = closeEditModal;
        document.body.appendChild(backdrop);
    }
    backdrop.style.display = 'block';

    // Fetch group data
    fetch('get_group.php?id=' + encodeURIComponent(groupId))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.group) {
                populateModalForm(data.group);
                loading.style.display = 'none';
                form.classList.remove('d-none');
                attachModalCloseHandlers();
            } else {
                throw new Error(data.error || 'Failed to load group');
            }
        })
        .catch(error => {
            console.error('Modal Error:', error);
            alert('Error al cargar el grupo: ' + error.message);
            closeEditModal();
        });
}

/**
 * Populate modal form with group data
 */
function populateModalForm(group) {
    const idField = document.getElementById('modalGroupId');
    const nombreField = document.getElementById('modalNombre');
    const descripcionField = document.getElementById('modalDescripcion');
    const cargueField = document.getElementById('modalCargue');

    if (idField) idField.value = group.id || '';
    if (nombreField) nombreField.value = group.nombre || '';
    if (descripcionField) descripcionField.value = group.descripcion || '';
    if (cargueField) cargueField.value = '';

    // Focus on nombre field for better UX
    if (nombreField) nombreField.focus();
}

/**
 * Close edit modal
 */
function closeEditModal() {
    const modal = document.getElementById('editGroupModal');
    const backdrop = document.getElementById('modalBackdrop');

    if (modal) {
        modal.classList.remove('show');
    }
    if (backdrop) {
        backdrop.style.display = 'none';
    }
}

/**
 * Attach close handlers to modal elements
 */
function attachModalCloseHandlers() {
    const closeBtn = document.querySelector('#editGroupModal .close');
    const cancelBtn = document.querySelector('#editGroupModal .btn-secondary');
    const backdrop = document.getElementById('modalBackdrop');

    if (closeBtn) {
        closeBtn.onclick = closeEditModal;
    }
    if (cancelBtn) {
        cancelBtn.onclick = closeEditModal;
    }
    if (backdrop) {
        backdrop.onclick = closeEditModal;
    }
}

/**
 * Submit modal edit form
 */
function submitModalEdit() {
    const nombreField = document.getElementById('modalNombre');

    if (!nombreField || nombreField.value.trim() === '') {
        alert('Por favor ingrese el nombre del grupo');
        return false;
    }

    if (!confirm('¿Está seguro que desea actualizar este grupo?')) {
        return false;
    }

    const funcion = document.getElementById('modalFuncion');
    if (funcion) {
        funcion.value = 'actualizar';
    }

    const form = document.getElementById('editGroupForm');
    if (form) {
        form.submit();
    }
    return false;
}

/**
 * Submit modal clear/delete form
 */
function submitModalClear() {
    if (!confirm('¿ESTÁ SEGURO que desea ELIMINAR todos los usuarios del grupo?\n\nEsta acción no se puede deshacer.\n\nNOTA: El grupo NO será eliminado, solo los usuarios asociados.')) {
        return false;
    }

    const funcion = document.getElementById('modalFuncion');
    if (funcion) {
        funcion.value = 'limpiar';
    }

    const form = document.getElementById('editGroupForm');
    if (form) {
        form.submit();
    }
    return false;
}
