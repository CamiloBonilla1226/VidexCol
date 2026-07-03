<?php
/**
 * grupos.php
 * Panel de administración de grupos consolidados
 * Requiere que funciones.php y config.php ya estén cargadas
 */

// El usuario debe estar autenticado (verificado por index.php)
if (!isset($_SESSION['id'])) {
    echo "No autorizado";
    exit();
}

$idFacilitador = $_SESSION['id'];
$nombreFacilitador = $_SESSION['nombre'] ?? 'Usuario';
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #e8e8e8;
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        background: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
        color: #333;
        margin-bottom: 10px;
        font-size: 28px;
    }

    .header p {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }

    .info-box {
        background: #f8f8f8;
        border-left: 4px solid #2c3e50;
        padding: 15px;
        margin-top: 15px;
        border-radius: 5px;
        font-size: 13px;
        color: #555;
    }

    /* Grid de Cards */
    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 10px;
        min-height: 400px;
    }

    .card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .card:hover {
        border-color: #2c3e50;
        box-shadow: 0 4px 12px rgba(44, 62, 80, 0.15);
        transform: translateY(-2px);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        flex: 1;
    }

    .card-badge {
        background: #e8e8e8;
        color: #2c3e50;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .consolidated-badge {
        background: #e8e8e8;
        color: #5a6c57;
    }

    .card-body {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .card-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .card-item-label {
        font-size: 12px;
        color: #999;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-item-value {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    .search-section {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    #searchFilter {
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    #searchFilter:focus {
        outline: none;
        border-color: #2c3e50;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .btn-primary {
        background: #2c3e50;
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #333;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
    }

    .status-message {
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 6px;
        display: none;
        font-weight: 600;
    }

    .status-message.success {
        background: #e8f5e9;
        color: #5a6c57;
        display: block;
    }

    .status-message.error {
        background: #ffcdd2;
        color: #c62828;
        display: block;
    }

    .status-message.info {
        background: #bbdefb;
        color: #1565c0;
        display: block;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state h2 {
        color: #666;
        margin-bottom: 10px;
    }

    .main-content {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 20px;
        margin-bottom: 30px;
        position: relative;
    }

    .mobile-menu-button {
        display: none;
        position: fixed;
        bottom: 30px;
        left: 30px;
        background: #2c3e50;
        color: white;
        border: none;
        padding: 15px 20px;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(44, 62, 80, 0.4);
        z-index: 100;
        transition: all 0.3s ease;
    }

    .mobile-menu-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(44, 62, 80, 0.5);
    }

    .slider-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 200;
    }

    .slider-overlay.active {
        display: block;
    }

    .left-panel {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        height: 600px;
    }

    .left-panel-header {
        background: #2c3e50;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 13px;
        flex-shrink: 0;
        height: 40px;
        display: flex;
        align-items: center;
    }

    .search-container {
        padding: 12px 15px;
        flex-shrink: 0;
        border-bottom: 2px solid #ebf5fb;
    }

    .search-input-left {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 12px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
        height: 36px;
    }

    .search-input-left:focus {
        border-color: #2c3e50;
        background: #f9f9f9;
    }

    .search-input-left::placeholder {
        color: #bbb;
    }

    .groups-list {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .group-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .group-item:hover {
        background: #f9f9f9;
        border-left: 4px solid #2c3e50;
        padding-left: 11px;
    }

    .group-item.selected {
        background: #f5f5f5;
        border-left: 4px solid #2c3e50;
        padding-left: 11px;
    }

    .group-item-title {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .group-item-info {
        font-size: 12px;
        color: #999;
        line-height: 1.4;
    }

    .right-panel {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        height: 600px;
        min-height: 600px;
    }

    .right-panel-header {
        background: #2c3e50;
        color: white;
        padding: 12px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        height: 40px;
    }

    .right-panel-header h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-new-report {
        background: white;
        color: #2c3e50;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.3s ease;
    }

    .btn-new-report:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .reports-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .empty-message {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .report-item {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.2s ease;
    }

    .report-item:hover {
        border-color: #2c3e50;
        box-shadow: 0 2px 8px rgba(44, 62, 80, 0.15);
    }

    .report-id {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .report-details {
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }

    .tabs-container {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        flex-shrink: 0;
    }

    .tab-button {
        flex: 1;
        padding: 12px 15px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        color: #666;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .tab-button:hover {
        color: #333;
        background: #f9f9f9;
    }

    .tab-button.active {
        color: #2c3e50;
        border-bottom-color: #2c3e50;
    }

    .tab-content {
        display: none;
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        padding-right: 16px;
    }

    .tab-content.active {
        display: block;
    }

    .info-section {
        margin-bottom: 20px;
    }

    .info-label {
        font-weight: 600;
        color: #666;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 0.5px;
    }

    .info-value {
        color: #333;
        font-size: 14px;
        padding: 8px 12px;
        background: #f9f9f9;
        border-radius: 4px;
        border-left: 3px solid #2c3e50;
    }

    .info-divider {
        height: 1px;
        background: #e0e0e0;
        margin: 20px 0;
    }

    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }

    .image-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .image-item:hover {
        transform: scale(1.05);
    }

    .report-image {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 6px;
        border: 2px solid #e0e0e0;
        transition: all 0.2s ease;
    }

    .report-image:hover {
        border-color: #2c3e50;
        box-shadow: 0 2px 8px rgba(44, 62, 80, 0.2);
    }

    .image-info {
        font-size: 11px;
        color: #666;
        text-align: center;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .header h1 {
            font-size: 20px;
        }

        .main-content {
            grid-template-columns: 1fr;
        }

        .left-panel {
            display: none;
            position: fixed !important;
            left: -100% !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 320px !important;
            height: 100vh !important;
            z-index: 250 !important;
            border-radius: 0 !important;
            transition: left 0.3s ease !important;
            margin-top: 0 !important;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15) !important;
        }

        .left-panel.active {
            display: flex !important;
            left: 0 !important;
        }

        .mobile-menu-button {
            display: block;
        }

        .right-panel {
            min-height: 400px;
            border-radius: 0;
            box-shadow: none;
        }
    }

    .edit-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 500;
        align-items: center;
        justify-content: center;
    }

    .edit-modal.active {
        display: flex;
    }

    .edit-modal-content {
        background: white;
        border-radius: 10px;
        padding: 30px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .edit-modal-header {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .edit-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 30px;
        height: 30px;
    }

    .edit-modal-close:hover {
        color: #333;
    }

    .edit-form-group {
        margin-bottom: 20px;
    }

    .edit-form-label {
        font-weight: 600;
        color: #333;
        font-size: 13px;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
    }

    .edit-form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .edit-form-input:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
    }

    .edit-lideres-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }

    .edit-lider-tag {
        background: #f5f5f5;
        border: 1px solid #2c3e50;
        color: #333;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .edit-lider-tag button {
        background: none;
        border: none;
        color: #2c3e50;
        cursor: pointer;
        font-size: 16px;
        padding: 0;
        display: flex;
        align-items: center;
    }

    .edit-lider-tag button:hover {
        color: #e74c3c;
    }

    .edit-lider-input-group {
        display: flex;
        gap: 8px;
    }

    .edit-lider-input {
        flex: 1;
    }

    .edit-lider-add-btn {
        background: #2c3e50;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .edit-lider-add-btn:hover {
        background: #1a252f;
    }

    .edit-modal-buttons {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .edit-modal-button {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .edit-modal-button.save {
        background: #2c3e50;
        color: white;
    }

    .edit-modal-button.save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
    }

    .edit-modal-button.cancel {
        background: #f0f0f0;
        color: #333;
    }

    .edit-modal-button.cancel:hover {
        background: #e0e0e0;
    }

    .edit-modal-section {
        margin-bottom: 20px;
    }

    .edit-modal-section label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .edit-modal-section input[type="text"],
    .edit-modal-section input[type="date"],
    .edit-modal-section input[type="number"],
    .edit-modal-section textarea,
    .edit-modal-section select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        font-size: 14px;
    }

    .edit-modal-section input[type="text"]:focus,
    .edit-modal-section input[type="date"]:focus,
    .edit-modal-section input[type="number"]:focus,
    .edit-modal-section textarea:focus,
    .edit-modal-section select:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 5px rgba(44, 62, 80, 0.3);
    }

    .edit-modal-section textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-field-error {
        display: none;
        margin-top: 6px;
        color: #c0392b;
        font-size: 12px;
        font-weight: 600;
    }

    .form-field-error.active {
        display: block;
    }

    .form-general-error.active {
        background: #fdecea;
        border: 1px solid #f5b7b1;
        border-radius: 4px;
        padding: 10px 12px;
        margin: 10px 0 14px 0;
        line-height: 1.4;
    }

    .form-field-invalid {
        border-color: #c0392b !important;
        box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.12) !important;
    }

    .edit-lider-tag {
        display: inline-block;
        background: #f5f5f5;
        color: #2c3e50;
        padding: 8px 12px;
        border-radius: 20px;
        margin-right: 8px;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .edit-lider-tag button {
        background: none;
        border: none;
        color: #2c3e50;
        cursor: pointer;
        margin-left: 8px;
        font-weight: bold;
    }

    .edit-lider-add-btn {
        background: #2c3e50;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 10px;
    }

    .edit-lider-add-btn:hover {
        background: #1a252f;
    }

    /* Estilos para modal de nuevo reporte */
    .activity-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .activity-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .activity-modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .activity-modal-header {
        font-size: 20px;
        font-weight: bold;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .activity-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .activity-modal-close:hover {
        color: #333;
    }

    .activity-options {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .activity-button {
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: left;
    }

    .activity-button:hover {
        border-color: #2c3e50;
        background: #f5f5f5;
        transform: translateY(-2px);
    }

    .activity-button.evangelismo {
        border-left: 4px solid #8e44ad;
    }

    .activity-button.gran-celebracion {
        border-left: 4px solid #8e44ad;
    }

    .activity-button.bautizo {
        border-left: 4px solid #8e44ad;
    }

    .activity-button.reunion {
        border-left: 4px solid #8e44ad;
    }

    .activity-button.otra {
        border-left: 4px solid #8e44ad;
    }

    .activity-button strong {
        display: block;
        font-size: 16px;
        margin-bottom: 5px;
        color: #333;
    }

    .activity-button span {
        font-size: 13px;
        color: #666;
    }

    /* Estilos para formulario de nuevo reporte */
    .new-report-form {
        display: none;
    }

    .new-report-form.active {
        display: block;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
        font-size: 13px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 13px;
        font-family: inherit;
    }

    #mapeosSection #mapeo_comprometido {
        min-height: 40px;
        padding-top: 8px;
        padding-bottom: 8px;
        line-height: 1.4;
        font-size: 13px;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 5px rgba(44, 62, 80, 0.3);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .form-row.full {
        grid-template-columns: 1fr;
    }

    .attendance-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .attendance-input {
        display: flex;
        flex-direction: column;
    }

    .attendance-input label {
        font-size: 12px;
        margin-bottom: 3px;
    }

    .attendance-input input {
        margin-bottom: 0;
    }

    .modal-button-group {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .modal-button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .modal-button.primary {
        background: #2c3e50;
        color: white;
    }

    .modal-button.primary:hover {
        background: #1a252f;
    }

    .modal-button.secondary {
        background: #f0f0f0;
        color: #333;
    }

    .modal-button.secondary:hover {
        background: #e0e0e0;
    }
</style>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
            <div>
                <h1>📊 IPG </h1>
                <p>Visualiza y gestiona todos tus grupos con sus datos actuales.</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateGroupModal()" title="Crear un nuevo grupo" style="white-space: nowrap;">
                ➕ Crear Nuevo IPG
            </button>
        </div>
        <div class="info-box">
            <strong>ℹ️ Información:</strong>
            Este panel muestra todos los IPG que has registrado. Puedes ver información detallada de cada IPG incluyendo nombre, ubicación, dirección, grupo madre y líderes.
        </div>
    </div>

    <!-- Mensaje de estado -->
    <div id="statusMessage" class="status-message"></div>

    <!-- Botón de menú móvil -->
    <button class="mobile-menu-button" onclick="toggleMobileMenu()">
        ☰ Grupos
    </button>

    <!-- Overlay para móvil -->
    <div class="slider-overlay" id="sliderOverlay" onclick="closeMobileMenu()"></div>

    <!-- Main Content - Dos Columnas -->
    <div class="main-content">
        <!-- Left Panel - Lista de Grupos -->
        <div class="left-panel" id="mobileSlider">
            <div class="left-panel-header">
                📋 Mis IPG (${gruposCount})
            </div>
            <div class="search-container">
                <input
                    type="text"
                    id="searchFilter"
                    placeholder="🔍 Buscar grupo..."
                    class="search-input-left"
                    onkeyup="filterGroups()"
                />
            </div>
            <div class="groups-list" id="groupsList">
                <!-- Los grupos se cargarán aquí -->
            </div>
        </div>

        <!-- Right Panel - Información y Reportes -->
        <div class="right-panel">
            <div class="right-panel-header">
                <h3 id="groupPanelTitle">📋 Información del IPG</h3>
                <button class="btn-new-report" onclick="newReport()" id="btnNewReport">
                    + Nuevo
                </button>
            </div>

            <!-- Tabs -->
            <div class="tabs-container">
                <button class="tab-button active" onclick="switchTab('info')">
                    ℹ️ Información General
                </button>
                <button class="tab-button" onclick="switchTab('reports')">
                    📄 Reportes
                </button>
            </div>

            <!-- Tab: Información General -->
            <div id="tab-info" class="tab-content active">
                <div class="empty-message">
                    <p>Selecciona un grupo para ver su información</p>
                </div>
            </div>

            <!-- Tab: Reportes -->
            <div id="tab-reports" class="tab-content">
                <div class="empty-message">
                    <p>Selecciona un grupo para ver sus reportes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="buttons-section">
        <button class="btn btn-primary" onclick="window.history.back()">
            ← Volver
        </button>
    </div>
</div>

<!-- Modal de Edición -->
<div class="edit-modal" id="editModal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>✏️ Editar Grupo</span>
            <button class="edit-modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form id="editForm" onsubmit="saveGroupChanges(event)">
            <div class="form-field-error form-general-error" id="editFormError"></div>
            <div class="edit-form-group">
                <label class="edit-form-label">Nombre del Grupo</label>
                <input type="text" id="editNombre" class="edit-form-input" placeholder="Nombre del grupo" oninput="clearFormError('editNombre', 'editNombreError')">
                <div class="form-field-error" id="editNombreError"></div>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Ciudad</label>
                <input type="text" id="editCiudad" class="edit-form-input" placeholder="Ciudad">
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Barrio</label>
                <input type="text" id="editBarrio" class="edit-form-input" placeholder="Barrio">
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Dirección</label>
                <input type="text" id="editDireccion" class="edit-form-input" placeholder="Dirección">
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">¿Tiene Grupo Madre?</label>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="editTieneGrupoMadre" value="no" checked onchange="toggleEditGrupoMadreSelect()">
                        No (será Generación 0)
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="editTieneGrupoMadre" value="si" onchange="toggleEditGrupoMadreSelect()">
                        Sí
                    </label>
                </div>
            </div>

            <div class="edit-form-group" id="editGrupoMadreSelect" style="display: none;">
                <label class="edit-form-label">Seleccionar Grupo Madre *</label>
                <select id="editGrupoMadreDropdown" class="edit-form-input">
                    <option value="">-- Cargando grupos --</option>
                </select>
                <div id="editGeneracionInfo" style="margin-top: 10px; padding: 10px; background: #e8f4f8; border-radius: 4px; display: none;">
                    <small>La nueva generación será: <strong id="editGeneracionDisplay">-</strong></small>
                </div>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Generación</label>
                <input type="number" id="editGeneracion" class="edit-form-input" placeholder="Generación" min="0" max="5" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Líderes</label>
                <div class="edit-lideres-container" id="lideresContainer"></div>
                <div class="edit-lider-input-group">
                    <input type="text" id="liderInput" class="edit-form-input edit-lider-input" placeholder="Agregar nuevo líder" oninput="clearFormError('liderInput', 'liderInputError')">
                    <button type="button" class="edit-lider-add-btn" onclick="addLider()">Agregar</button>
                </div>
                <div class="form-field-error" id="liderInputError"></div>
            </div>

            <div class="edit-modal-buttons">
                <button type="button" class="edit-modal-button cancel" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="edit-modal-button save">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Crear Nuevo Grupo -->
<div class="edit-modal" id="createGroupModal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>➕ Crear Nuevo IPG</span>
            <button class="edit-modal-close" onclick="closeCreateGroupModal()">✕</button>
        </div>

        <form id="createGroupForm" onsubmit="saveNewGroup(event)" novalidate>
            <div class="form-field-error form-general-error" id="createGroupFormError"></div>
            <!-- INFORMACIÓN DEL GRUPO -->
            <h4 style="margin-top: 15px; margin-bottom: 10px; color: #333;">📋 Información del IPG</h4>

            <div class="edit-modal-section">
                <label>Nombre del Grupo *</label>
                <input type="text" id="newGroupName" name="nombre" required placeholder="Ej: Grupo de Jóvenes" oninput="clearFormError('newGroupName', 'newGroupNameError')">
                <div class="form-field-error" id="newGroupNameError"></div>
            </div>

            <div class="edit-modal-section">
                <label>Descripción</label>
                <textarea id="newGroupDescription" name="descripcion" placeholder="Descripción del grupo" rows="3"></textarea>
            </div>

            <div class="edit-modal-section">
                <label>¿Tiene Grupo Madre?</label>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="tieneGrupoMadre" value="no" checked onchange="toggleGrupoMadreSelect()">
                        No (será Generación 0)
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="tieneGrupoMadre" value="si" onchange="toggleGrupoMadreSelect()">
                        Sí
                    </label>
                </div>
            </div>

            <div class="edit-modal-section" id="grupoMadreSelect" style="display: none;">
                <label>Seleccionar Grupo Madre *</label>
                <select id="grupoMadreDropdown" name="grupoMadre" onchange="clearFormError('grupoMadreDropdown', 'grupoMadreError')">
                    <option value="">-- Cargando grupos --</option>
                </select>
                <div id="generacionInfo" style="margin-top: 10px; padding: 10px; background: #e8f4f8; border-radius: 4px; display: none;">
                    <small>La nueva generación será: <strong id="generacionDisplay">-</strong></small>
                </div>
                <div class="form-field-error" id="grupoMadreError"></div>
            </div>

            <div class="edit-modal-section">
                <label>Ubicación (Ciudad, Barrio) *</label>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <input type="text" id="newGroupCiudad" name="ciudad" placeholder="Ciudad" style="width: 100%;" oninput="clearFormError('newGroupCiudad', 'newGroupCiudadError')">
                        <div class="form-field-error" id="newGroupCiudadError"></div>
                    </div>
                    <div style="flex: 1;">
                        <input type="text" id="newGroupBarrio" name="barrio" placeholder="Barrio" style="width: 100%;" oninput="clearFormError('newGroupBarrio', 'newGroupBarrioError')">
                        <div class="form-field-error" id="newGroupBarrioError"></div>
                    </div>
                </div>
            </div>

            <div class="edit-modal-section">
                <label>Dirección *</label>
                <input type="text" id="newGroupDireccion" name="direccion" placeholder="Dirección del lugar de reunión" oninput="clearFormError('newGroupDireccion', 'newGroupDireccionError')">
                <div class="form-field-error" id="newGroupDireccionError"></div>
            </div>

            <div class="edit-modal-section">
                <label>Líder del Grupo *</label>
                <input type="text" id="newGroupLider" name="lider" placeholder="Nombre del líder" oninput="clearFormError('newGroupLider', 'newGroupLiderError')">
                <div class="form-field-error" id="newGroupLiderError"></div>
                <div id="newGroupLideresUi" style="margin-top: 10px;">
                    <div class="edit-lideres-container" id="newGroupLideresContainer"></div>
                    <div class="edit-lider-input-group">
                        <input type="text" id="newGroupLiderInput" class="edit-form-input edit-lider-input" placeholder="Agregar nuevo líder" oninput="clearFormError('newGroupLiderInput', 'newGroupLiderError')">
                        <button type="button" class="edit-lider-add-btn" onclick="addNewGroupLider()">Agregar</button>
                    </div>
                </div>
            </div>

            <!-- DATOS DEL PRIMER REPORTE -->
            <h4 style="margin-top: 20px; margin-bottom: 10px; color: #333;">📊 Primer Reporte (Datos Iniciales)</h4>

            <input type="hidden" name="actividad" value="reunion_cotidiana">

            <div class="edit-modal-section">
                <label>Fecha del Primer Encuentro *</label>
                <input type="date" id="newGroupFecha" name="fecha" required onchange="clearFormError('newGroupFecha', 'newGroupFechaError')">
                <div class="form-field-error" id="newGroupFechaError"></div>
            </div>

            <div class="edit-modal-section" id="newGroupAsistenciaSection">
                <label>Asistencia en el Primer Encuentro *</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 13px;">👨 Hombres</label>
                        <input type="number" id="newGroupAsisHom" name="asistencia_hom" min="0" value="0" onchange="calculateTotalAsistencia()" oninput="calculateTotalAsistencia()">
                    </div>
                    <div>
                        <label style="font-size: 13px;">👩 Mujeres</label>
                        <input type="number" id="newGroupAsisMuj" name="asistencia_muj" min="0" value="0" onchange="calculateTotalAsistencia()" oninput="calculateTotalAsistencia()">
                    </div>
                    <div>
                        <label style="font-size: 13px;">👦 Jóvenes</label>
                        <input type="number" id="newGroupAsisJov" name="asistencia_jov" min="0" value="0" onchange="calculateTotalAsistencia()" oninput="calculateTotalAsistencia()">
                    </div>
                    <div>
                        <label style="font-size: 13px;">🧒 Niños</label>
                        <input type="number" id="newGroupAsisNin" name="asistencia_nin" min="0" value="0" onchange="calculateTotalAsistencia()" oninput="calculateTotalAsistencia()">
                    </div>
                </div>
                <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                    <small style="color: #666;">Total: <strong id="totalAsistenciaDisplay">0</strong></small>
                </div>
                <div class="form-field-error" id="newGroupAsistenciaError"></div>
            </div>

            <div class="edit-modal-buttons">
                <button type="button" class="edit-modal-button cancel" onclick="closeCreateGroupModal()">Cancelar</button>
                <button type="submit" class="edit-modal-button save">Crear Grupo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Nuevo Reporte -->
<div class="activity-modal" id="activityModal">
    <div class="activity-modal-content">
        <!-- Vista de selección de actividad -->
        <div id="activitySelection">
            <div class="activity-modal-header">
                <span>📝 Nuevo Reporte</span>
                <button class="activity-modal-close" onclick="closeActivityModal()">✕</button>
            </div>

            <p style="color: #666; margin-bottom: 20px;">Selecciona el tipo de actividad a reportar:</p>

            <div class="activity-options">
                <button type="button" class="activity-button evangelismo" onclick="selectActivity('evangelismo')">
                    <strong>🌍 Evangelismo</strong>
                    <span>Actividad de evangelización realizada</span>
                </button>

                <button type="button" class="activity-button gran-celebracion" onclick="selectActivity('gran_celebracion')">
                    <strong>🎉 Gran Celebración</strong>
                    <span>Evento especial de celebración</span>
                </button>

                <button type="button" class="activity-button bautizo" onclick="selectActivity('bautizo')">
                    <strong>💧 Bautizo</strong>
                    <span>Actividad de bautismo con evidencia fotográfica</span>
                </button>

                <button type="button" class="activity-button reunion" onclick="selectActivity('reunion_cotidiana')">
                    <strong>🤝 Coach</strong>
                    <span>Reunión regular del grupo</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('siembra_abundante')">
                    <strong>🌱 Siembra Abundante</strong>
                    <span>Actividad de siembra abundante</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('caminata_oracion')">
                    <strong>🚶 Caminata de Oración</strong>
                    <span>Caminata de oración por el sector</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('identificar_hijo_paz')">
                    <strong>🕊️ Identificar al Hijo de Paz</strong>
                    <span>Actividad para identificar al hijo de paz</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('oracion_exp_ferviente')">
                    <strong>🙏 Oración Exp. y Ferviente</strong>
                    <span>Oración expectante y ferviente</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('taller')">
                    <strong>🛠️ Taller</strong>
                    <span>Taller de capacitación</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('otra_actividad')">
                    <strong>➕ Otra Actividad</strong>
                    <span>Otra actividad no listada</span>
                </button>

                <button type="button" class="activity-button otra" onclick="selectActivity('capacitacion')">
                    <strong>🎓 Capacitación</strong>
                    <span>Actividad de capacitación</span>
                </button>
            </div>
        </div>

        <!-- Vista de formulario de nuevo reporte -->
        <div id="reportForm" style="display: none;">
            <div class="activity-modal-header">
                <span id="formTitle">📝 Nuevo Reporte</span>
                <button class="activity-modal-close" onclick="closeActivityModal()">✕</button>
            </div>

            <form id="newReportForm" onsubmit="saveNewReport(event)">
                <div class="form-field-error form-general-error" id="newReportFormError"></div>
                <!-- Campo de Fecha de Actividad -->
                <div class="form-group">
                    <label>Fecha de la Actividad</label>
                    <input type="date" id="fechaActividad" required>
                </div>

                <!-- Sección de Asistencia -->
                <div class="form-group">
                    <label id="asistenciaLabel">Asistencia</label>
                    <div class="attendance-grid">
                        <div class="attendance-input">
                            <label>Hombres</label>
                            <input type="number" id="asistencia_hom" min="0" value="0">
                        </div>
                        <div class="attendance-input">
                            <label>Mujeres</label>
                            <input type="number" id="asistencia_muj" min="0" value="0">
                        </div>
                        <div class="attendance-input">
                            <label>Jóvenes</label>
                            <input type="number" id="asistencia_jov" min="0" value="0">
                        </div>
                        <div class="attendance-input">
                            <label>Niños</label>
                            <input type="number" id="asistencia_nin" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-field-error" id="reportAsistenciaError"></div>
                </div>

                <div class="form-group" id="metricasEvangelismoSection" style="display: none;">
                    <label id="metricasReporteTitulo">Asistencia Total</label>
                    <div style="padding: 10px; background: #f0f0f0; border-radius: 4px; margin-bottom: 12px;">
                        <strong id="asistenciaTotalReporte">0</strong>
                    </div>
                    <div class="attendance-grid">
                        <div class="attendance-input" id="metricFieldDiscipulado">
                            <label>Discipulado</label>
                            <input type="number" id="discipulado" min="0" value="0">
                        </div>
                        <div class="attendance-input" id="metricFieldDecisiones">
                            <label id="desicionesExtraLabel">Decisiones de Fé</label>
                            <input type="number" id="desiciones_extra" min="0" value="0">
                        </div>
                        <div class="attendance-input" id="metricFieldPreparandose">
                            <label>Preparandose</label>
                            <input type="number" id="preparandose" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- Campo opcional: Bautizados -->
                <div class="form-group" id="bautizadosSection" style="display: none;">
                    <label>Cantidad de Bautizados</label>
                    <input type="number" id="bautizados" min="0" value="0">
                </div>

                <!-- Campo opcional: Decisiones -->
                <div class="form-group" id="decisionesSection" style="display: none;">
                    <label>Decisiones de Fé</label>
                    <input type="number" id="desiciones" min="0" value="0">
                </div>
                <div class="form-field-error" id="reportMetricasError"></div>

                <!-- Campo opcional: Comentarios -->
                <div class="form-group" id="comentariosSection" style="display: none;">
                    <label>Comentarios</label>
                    <textarea id="comentario" rows="3" placeholder="Detalles adicionales..."></textarea>
                </div>

                <!-- Sección de Evidencia Fotográfica -->
                <div class="form-group">
                    <label>Evidencia Fotográfica (Mínimo 1 - Máximo 3 imágenes)</label>
                    <div id="fotosInputsContainer" style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                        <div class="foto-evidencia-item" data-slot="1">
                            <label style="font-size: 12px;">Foto 1</label>
                            <input type="file" class="fotos-evidencia-input" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: block; margin-top: 6px;">
                        </div>
                    </div>
                    <button type="button" id="addOtraFotoBtn" class="modal-button secondary" style="width: 100%; margin-top: 10px;">Agregar otra foto</button>
                    <small style="color: #666; display: block; margin-top: 8px;">Debe cargar al menos una imagen. Máximo 3 imágenes, 5 MB por imagen. Formatos: JPG, PNG, WebP</small>
                    <div id="fotosPreview" style="margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;"></div>
                    <div id="fotosCountMsg" style="margin-top: 8px; font-size: 12px; color: #666;"></div>
                    <div class="form-field-error" id="reportFotosError"></div>
                </div>

                <!-- Sección de Mapeos (solo para Coach) -->
                <div id="mapeosSection" style="display: none;">
                    <h4 style="margin: 20px 0 15px 0; color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px;">Funciones Realizadas</h4>

                    <div class="form-group">
                        <label>Este grupo esta comprometido como iglesia?</label>
                        <select id="mapeo_comprometido" class="form-control">
                            <option value="">Seleccione una opción</option>
                            <option value="3">NO comprometido</option>
                            <option value="4">SI comprometido como iglesia</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Oración</label>
                        <select id="mapeo_oracion" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Compañerismo</label>
                        <select id="mapeo_companerismo" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Adoración</label>
                        <select id="mapeo_adoracion" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Aplicar la Biblia</label>
                        <select id="mapeo_biblia" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Evangelizar</label>
                        <select id="mapeo_evangelizar" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cena del Señor</label>
                        <select id="mapeo_cena" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dar (Ofrendas)</label>
                        <select id="mapeo_dar" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Bautizar</label>
                        <select id="mapeo_bautizar" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Entrenar Nuevos Líderes</label>
                        <select id="mapeo_trabajadores" class="mapeo-select">
                            <option value="0">Selecciona una opción</option>
                            <option value="1">No realiza la tarea</option>
                            <option value="2">Realiza en compañía del facilitador</option>
                            <option value="3">Realiza pero este mes no lo hizo</option>
                            <option value="4">Realiza autónomamente</option>
                        </select>
                    </div>

                    <!-- Gráfica de Mapeo en tiempo real -->
                    <div id="mapeoChartContainer" style="margin-top: 20px; text-align: center;">
                        <h4 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px;">Imagen del Mapeo</h4>
                        <canvas id="mapeoCanvas" width="550" height="550" style="max-width: 100%; border: 1px solid #ddd; border-radius: 8px; background: #fff;"></canvas>
                    </div>
                    <div class="form-field-error" id="reportMapeosError"></div>
                </div>

                <input type="hidden" id="tipoActividad">
                <input type="hidden" id="reporteIds">

                <div class="modal-button-group">
                    <button type="button" class="modal-button secondary" onclick="backToActivitySelection()">Atrás</button>
                    <button type="submit" class="modal-button primary">Guardar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let grupos = [];
    let filteredGrupos = [];
    let selectedGrupo = null;
    const MAX_REPORT_IMAGES = 3;
    const REPORT_MAX_IMAGE_SIZE = 5 * 1024 * 1024;
    const REPORT_ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

    function toggleMobileMenu() {
        const mobileSlider = document.getElementById('mobileSlider');
        const sliderOverlay = document.getElementById('sliderOverlay');
        mobileSlider.classList.toggle('active');
        sliderOverlay.classList.toggle('active');
    }

    function closeMobileMenu() {
        const mobileSlider = document.getElementById('mobileSlider');
        const sliderOverlay = document.getElementById('sliderOverlay');
        mobileSlider.classList.remove('active');
        sliderOverlay.classList.remove('active');
    }

    function showStatusMessage(message, type = 'info') {
        const statusDiv = document.getElementById('statusMessage');
        statusDiv.textContent = message;
        statusDiv.className = `status-message ${type}`;
        setTimeout(() => {
            statusDiv.className = 'status-message';
        }, 5000);
    }

    function showFormError(fieldId, errorId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(errorId);

        if (field) {
            field.classList.add('form-field-invalid');
        }

        if (error) {
            error.textContent = message;
            error.classList.add('active');
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearFormError(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(errorId);

        if (field) {
            field.classList.remove('form-field-invalid');
        }

        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function showAsistenciaError(message) {
        ['newGroupAsisHom', 'newGroupAsisMuj', 'newGroupAsisJov', 'newGroupAsisNin'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.add('form-field-invalid');
            }
        });

        const error = document.getElementById('newGroupAsistenciaError');
        if (error) {
            error.textContent = message;
            error.classList.add('active');
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearAsistenciaError() {
        ['newGroupAsisHom', 'newGroupAsisMuj', 'newGroupAsisJov', 'newGroupAsisNin'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.remove('form-field-invalid');
            }
        });

        const error = document.getElementById('newGroupAsistenciaError');
        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function showReportAsistenciaError(message) {
        ['asistencia_hom', 'asistencia_muj', 'asistencia_jov', 'asistencia_nin'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.add('form-field-invalid');
            }
        });

        const error = document.getElementById('reportAsistenciaError');
        if (error) {
            error.textContent = message;
            error.classList.add('active');
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearReportAsistenciaError() {
        ['asistencia_hom', 'asistencia_muj', 'asistencia_jov', 'asistencia_nin'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.remove('form-field-invalid');
            }
        });

        const error = document.getElementById('reportAsistenciaError');
        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function clearCreateGroupFormErrors() {
        clearInlineFormError('createGroupFormError');
        clearFormError('newGroupName', 'newGroupNameError');
        clearFormError('newGroupCiudad', 'newGroupCiudadError');
        clearFormError('newGroupBarrio', 'newGroupBarrioError');
        clearFormError('newGroupDireccion', 'newGroupDireccionError');
        clearFormError('newGroupLider', 'newGroupLiderError');
        clearFormError('newGroupLiderInput', 'newGroupLiderError');
        clearFormError('newGroupFecha', 'newGroupFechaError');
        clearFormError('grupoMadreDropdown', 'grupoMadreError');
        clearAsistenciaError();
    }

    function renderNewGroupLideresUI() {
        const container = document.getElementById('newGroupLideresContainer');
        if (!container) {
            return;
        }

        container.innerHTML = createGroupFormData.lideresArray.map((lider, index) => `
            <div class="edit-lider-tag">
                ${lider}
                <button type="button" onclick="removeNewGroupLider(${index})">x</button>
            </div>
        `).join('');
    }

    function addNewGroupLider() {
        const liderInput = document.getElementById('newGroupLiderInput');
        if (!liderInput) {
            return;
        }

        const nuevoLider = normalizarNombreLider(liderInput.value);
        const errorLider = validarNombreLider(nuevoLider);

        if (errorLider) {
            showFormError('newGroupLiderInput', 'newGroupLiderError', errorLider);
            liderInput.focus();
            return;
        }

        clearFormError('newGroupLiderInput', 'newGroupLiderError');

        if (nuevoLider && !createGroupFormData.lideresArray.includes(nuevoLider)) {
            createGroupFormData.lideresArray.push(nuevoLider);
            liderInput.value = '';
            clearFormError('liderInput', 'liderInputError');
            clearFormError('liderInput', 'liderInputError');
            renderNewGroupLideresUI();
        } else if (createGroupFormData.lideresArray.includes(nuevoLider)) {
            showFormError('newGroupLiderInput', 'newGroupLiderError', 'Este líder ya existe');
            liderInput.focus();
        }
    }

    function removeNewGroupLider(index) {
        createGroupFormData.lideresArray.splice(index, 1);
        renderNewGroupLideresUI();
    }

    function obtenerIdReporteGrupo(grupo) {
        const ids = [];
        ['id', 'id_reporte', 'idReporte', 'reporte_id'].forEach(campo => {
            const valor = parseInt(grupo[campo], 10);
            if (valor > 0) {
                ids.push(valor);
            }
        });

        if (Array.isArray(grupo.reportes_ids)) {
            grupo.reportes_ids.forEach(id => {
                const valor = parseInt(id, 10);
                if (valor > 0) {
                    ids.push(valor);
                }
            });
        }

        if (ids.length === 0) {
            return '';
        }

        return Math.min(...ids);
    }

    function obtenerIdGrupoSeleccionado(grupo) {
        if (!grupo) {
            return 0;
        }

        const idGuardado = parseInt(grupo.idGrupoSeleccionado || grupo.id_grupo_base || grupo.idGrupoBase, 10);
        if (idGuardado > 0) {
            return idGuardado;
        }

        return parseInt(obtenerIdReporteGrupo(grupo), 10) || 0;
    }

    function normalizarReportesIds(ids) {
        if (!Array.isArray(ids)) {
            return [];
        }

        const normalizados = ids
            .map(id => parseInt(id, 10))
            .filter(id => id > 0);

        return [...new Set(normalizados)];
    }

    function obtenerEtiquetaActividad(reporte) {
        const idActividad = parseInt(reporte.id_actividad || 0, 10);
        const etiquetasActividad = {
            1: 'Coach',
            2: 'Ninguna',
            5: 'Otra actividad',
            8: 'Gran Celebracion',
            10: 'Siembra abundante',
            11: 'Caminata de oracion',
            12: 'Identificar al hijo de paz',
            13: 'Oracion Exp y Ferviente',
            14: 'Taller',
            77: 'Evangelismo',
            99: 'Bautizo',
            100: 'Capacitacion'
        };

        if (etiquetasActividad[idActividad]) {
            return etiquetasActividad[idActividad];
        }

        const generacion = parseInt(reporte.generacionNumero || 0, 10);
        if (generacion === 77) {
            return 'Evangelismo';
        }
        if (generacion === 8) {
            return 'Gran Celebracion';
        }
        return generacion > 0 ? `Generacion ${generacion}` : 'Desconocido';
    }

    function renderReporteInfoBasica(reportes) {
        if (!Array.isArray(reportes)) {
            return;
        }

        reportes.forEach(reporte => {
            const infoContainer = document.getElementById(`report-info-${reporte.id}`);
            if (!infoContainer) {
                return;
            }

            const fecha = reporte.fechaInicio
                ? new Date(reporte.fechaInicio).toLocaleDateString('es-CO', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                })
                : 'Sin fecha';

            infoContainer.innerHTML = `<strong>${obtenerEtiquetaActividad(reporte)}</strong> • ${fecha} • Asistencia: ${parseInt(reporte.asistencia_total || 0, 10)}`;
        });
    }

    function normalizarNombreLider(nombre) {
        return (nombre || '').trim().replace(/\s+/g, ' ');
    }

    function validarNombreLider(nombre) {
        const lider = normalizarNombreLider(nombre);

        if (!lider) {
            return 'El nombre del lider es obligatorio';
        }

        if (Array.from(lider).length < 10) {
            return 'El nombre del lider debe tener minimo 10 caracteres';
        }

        let esAlfabetico = false;
        try {
            esAlfabetico = new RegExp('^[\\p{L} ]+$', 'u').test(lider);
        } catch (e) {
            esAlfabetico = /^[A-Za-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F ]+$/.test(lider);
        }

        if (!esAlfabetico) {
            return 'El nombre del lider solo debe contener letras y espacios';
        }

        return '';
    }

    function validarNombreGrupo(nombre) {
        const grupo = (nombre || '').trim().replace(/\s+/g, ' ');

        if (!grupo) {
            return 'El nombre del grupo es obligatorio';
        }

        if (Array.from(grupo).length < 7) {
            return 'El nombre del grupo debe tener minimo 7 caracteres';
        }

        const tieneLetrasONumeros = /[A-Za-z0-9\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F]/.test(grupo);
        if (!tieneLetrasONumeros) {
            return 'El nombre del grupo debe ser alfabetico o alfanumerico';
        }

        return '';
    }

    function showReportMetricasError(fieldId, message) {
        ['bautizados', 'desiciones', 'discipulado', 'desiciones_extra', 'preparandose'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.toggle('form-field-invalid', id === fieldId);
            }
        });

        const error = document.getElementById('reportMetricasError');
        if (error) {
            error.textContent = message;
            error.classList.add('active');
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function showReportMapeosError(fieldId, message) {
        ['mapeo_comprometido', 'mapeo_oracion', 'mapeo_companerismo', 'mapeo_adoracion', 'mapeo_biblia', 'mapeo_evangelizar', 'mapeo_cena', 'mapeo_dar', 'mapeo_bautizar', 'mapeo_trabajadores'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.toggle('form-field-invalid', id === fieldId);
            }
        });

        const error = document.getElementById('reportMapeosError');
        if (error) {
            error.textContent = message;
            error.classList.add('active');
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearReportMetricasError() {
        ['bautizados', 'desiciones', 'discipulado', 'desiciones_extra', 'preparandose'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.remove('form-field-invalid');
            }
        });

        const error = document.getElementById('reportMetricasError');
        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function clearReportMapeosError() {
        ['mapeo_comprometido', 'mapeo_oracion', 'mapeo_companerismo', 'mapeo_adoracion', 'mapeo_biblia', 'mapeo_evangelizar', 'mapeo_cena', 'mapeo_dar', 'mapeo_bautizar', 'mapeo_trabajadores'].forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.remove('form-field-invalid');
            }
        });

        const error = document.getElementById('reportMapeosError');
        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function clearReportFormErrors() {
        clearInlineFormError('newReportFormError');
        clearInlineFormError('reportFotosError');
        clearReportAsistenciaError();
        clearReportMetricasError();
        clearReportMapeosError();
    }

    function showInlineFormError(errorId, message) {
        const error = document.getElementById(errorId);
        if (!error) {
            showStatusMessage(message, 'error');
            return;
        }

        error.textContent = message;
        error.classList.add('active');
        error.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearInlineFormError(errorId) {
        const error = document.getElementById(errorId);
        if (error) {
            error.textContent = '';
            error.classList.remove('active');
        }
    }

    function showCreateGroupFormError(message) {
        showInlineFormError('createGroupFormError', message);
    }

    function showReportFormError(message) {
        showInlineFormError('newReportFormError', message);
    }

    function showReportFotosError(message) {
        showInlineFormError('reportFotosError', message);
    }

    function showEditFormError(message) {
        showInlineFormError('editFormError', message);
    }

    function formatearLider(lider) {
        if (!lider) return 'No especificado';
        try {
            const parsed = JSON.parse(lider);
            if (Array.isArray(parsed)) {
                return parsed.filter(l => l && typeof l === 'string').join(', ') || 'No especificado';
            }
        } catch (e) {
            // Si no es JSON, retornar como está
        }
        return lider;
    }

    function selectGrupo(grupoData, element) {
        grupoData.idGrupoSeleccionado = obtenerIdGrupoSeleccionado(grupoData);
        selectedGrupo = grupoData;

        // Actualizar estilos
        document.querySelectorAll('.group-item').forEach(item => {
            item.classList.remove('selected');
        });
        element.classList.add('selected');

        // Actualizar panel derecho
        updateGroupPanel(grupoData);

        // Cerrar slider en móvil
        if (window.innerWidth <= 768) {
            closeMobileMenu();
        }
    }

    function switchTab(tabName) {
        // Actualizar botones de pestaña
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Actualizar contenido de pestañas
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabName}`).classList.add('active');
    }

    function updateGroupPanel(grupoData) {
        const groupPanelTitle = document.getElementById('groupPanelTitle');
        const tabInfo = document.getElementById('tab-info');
        const tabReports = document.getElementById('tab-reports');

        groupPanelTitle.textContent = `📋 ${grupoData.nombre_exacto}`;

        // Actualizar tab de información
        updateInfoTab(grupoData, tabInfo, null, true);

        // Actualizar tab de reportes
        updateReportsTab(grupoData, tabReports);
    }

    function updateInfoTab(grupoData, tabInfo, imagenesPrecargadas = null, cargandoReportes = false) {
        let generacionText = 'No especificada';
        if (grupoData.generacion) {
            generacionText = grupoData.generacion;
        }
        const reportesIds = normalizarReportesIds(grupoData.reportes_mostrados_ids || grupoData.reportes_ids || []);
        const totalReportes = cargandoReportes ? 'Cargando...' : reportesIds.length;

        let infoHTML = `
            <button class="btn btn-primary" style="width: 100%; margin-bottom: 20px;" onclick="openEditModal()" title="Editar información del grupo">
                ✏️ Editar Información
            </button>

            <div class="info-section">
                <div class="info-label">Nombre del Grupo</div>
                <div class="info-value">${grupoData.nombre_exacto || 'No especificado'}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Generación</div>
                <div class="info-value">${generacionText}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Ubicación</div>
                <div class="info-value">${grupoData.ubicacion || 'No especificada'}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Dirección</div>
                <div class="info-value">${grupoData.direccion || 'No especificada'}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Barrio</div>
                <div class="info-value">${grupoData.barrio || 'No especificado'}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Grupo Madre</div>
                <div class="info-value">${grupoData.grupo_madre || 'No especificado'}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Líder</div>
                <div class="info-value">${formatearLider(grupoData.lider)}</div>
            </div>
            <div class="info-section">
                <div class="info-label">Total de Reportes</div>
                <div class="info-value" id="totalReportesValue">${totalReportes}</div>
            </div>
        `;

        // Agregar sección de imágenes si hay reportes
        if (!cargandoReportes && reportesIds.length > 0) {
            infoHTML += `
                <div class="info-divider"></div>
                <div class="info-section">
                    <div class="info-label">📸 Imágenes de Reportes</div>
                    <div id="imagesContainer" class="images-grid">
                        <div style="text-align: center; color: #999; padding: 20px;">Cargando imágenes...</div>
                    </div>
                </div>
            `;
        }

        tabInfo.innerHTML = infoHTML;

        // Cargar imágenes si existen reportes
        if (!cargandoReportes && reportesIds.length > 0) {
            if (Array.isArray(imagenesPrecargadas)) {
                renderImagesInInfoSection(imagenesPrecargadas);
            } else {
                loadImagesInInfoSection(reportesIds);
            }
        }
    }

    function renderImagesInInfoSection(imagenes) {
        const container = document.getElementById('imagesContainer');
        if (!container) {
            return;
        }

        if (!Array.isArray(imagenes) || imagenes.length === 0) {
            container.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">No hay imÃ¡genes disponibles</div>';
            return;
        }

        container.innerHTML = imagenes.map((img) => {
            const thumbnail = img.rutaThumbnail || img.ruta;
            return `
                <div class="image-item" style="position: relative; width: 100%; aspect-ratio: 1; border-radius: 4px; overflow: hidden; cursor: pointer; border: 1px solid #ddd; background: #f5f5f5;">
                    <img src="${thumbnail}" alt="${img.nombre}"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         title="Reporte ${img.reporte_id}">
                    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background 0.2s;"
                         onmouseover="this.style.background='rgba(0,0,0,0.2)'"
                         onmouseout="this.style.background='rgba(0,0,0,0)'"></div>
                </div>
            `;
        }).join('');

        container.querySelectorAll('.image-item').forEach((item, idx) => {
            item.addEventListener('click', function() {
                openImageModal(idx, imagenes.map(i => i.ruta));
            });
        });
    }

    function loadImagesInInfoSection(reporteIds) {
        console.log('loadImagesInInfoSection llamado con reporteIds:', reporteIds);

        fetch('obtener_imagenes_reportes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reporteIds: reporteIds
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos en loadImagesInInfoSection:', data);

            const container = document.getElementById('imagesContainer');
            if (!container) {
                console.warn('Contenedor imagesContainer no encontrado');
                return;
            }

            if (data.success && data.imagenes && data.imagenes.length > 0) {
                console.log('Imágenes recibidas:', data.imagenes);

                const imagesHTML = data.imagenes.map((img, index) => {
                    const thumbnail = img.rutaThumbnail || img.ruta;
                    return `
                        <div class="image-item" style="position: relative; width: 100%; aspect-ratio: 1; border-radius: 4px; overflow: hidden; cursor: pointer; border: 1px solid #ddd; background: #f5f5f5;">
                            <img src="${thumbnail}" alt="${img.nombre}"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 title="Reporte ${img.reporte_id}">
                            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background 0.2s;"
                                 onmouseover="this.style.background='rgba(0,0,0,0.2)'"
                                 onmouseout="this.style.background='rgba(0,0,0,0)'"></div>
                        </div>
                    `;
                }).join('');

                container.innerHTML = imagesHTML;

                // Agregar event listeners para abrir modal
                container.querySelectorAll('.image-item').forEach((item, idx) => {
                    item.addEventListener('click', function() {
                        const allImages = data.imagenes.map(i => i.ruta);
                        openImageModal(idx, allImages);
                    });
                });

                console.log('Imágenes cargadas en info section');
            } else {
                container.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">No hay imágenes disponibles</div>';
            }
        })
        .catch(error => {
            console.error('Error al cargar imágenes en info section:', error);
            const container = document.getElementById('imagesContainer');
            if (container) {
                container.innerHTML = '<div style="text-align: center; color: #f44; padding: 20px;">Error al cargar imágenes</div>';
            }
        });
    }

    function loadReportImagesAndInfo(reporteIds) {
        console.log('loadReportImagesAndInfo llamado con reporteIds:', reporteIds);

        // Fetch para obtener las imágenes e información de los reportes
        fetch('obtener_imagenes_reportes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reporteIds: reporteIds
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos de obtener_imagenes_reportes.php:', data);

            if (data.success && data.imagenes) {
                console.log('Imágenes recibidas:', data.imagenes);

                // Agrupar imágenes por reporte
                const imagesByReport = {};
                data.imagenes.forEach(img => {
                    if (!imagesByReport[img.reporte_id]) {
                        imagesByReport[img.reporte_id] = [];
                    }
                    imagesByReport[img.reporte_id].push(img);
                });

                console.log('Imágenes agrupadas por reporte:', imagesByReport);

                // Llenar los contenedores de cada reporte
                Object.keys(imagesByReport).forEach(reporteId => {
                    console.log(`Buscando contenedor para reporte ${reporteId}`);
                    const container = document.getElementById(`images-${reporteId}`);
                    if (container) {
                        console.log(`Contenedor encontrado para reporte ${reporteId}`);
                        const imagesArray = imagesByReport[reporteId].map(i => i.ruta);
                        const imagesHTML = imagesByReport[reporteId].map((img, index) => {
                            const thumbnail = img.rutaThumbnail || img.ruta;
                            return `
                                <div class="image-thumbnail" data-index="${index}" data-report="${reporteId}" style="position: relative; width: 100%; aspect-ratio: 1; border-radius: 4px; overflow: hidden; cursor: pointer; border: 1px solid #ddd; background: #f5f5f5;">
                                    <img src="${thumbnail}" alt="${img.nombre}"
                                         style="width: 100%; height: 100%; object-fit: cover;"
                                         title="${img.nombre}">
                                    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background 0.2s;"
                                         onmouseover="this.style.background='rgba(0,0,0,0.2)'"
                                         onmouseout="this.style.background='rgba(0,0,0,0)'"></div>
                                </div>
                            `;
                        }).join('');
                        container.innerHTML = imagesHTML;
                        console.log(`Imágenes HTML insertadas en contenedor ${reporteId}`);

                        // Agregar event listeners a las miniaturas
                        container.querySelectorAll('.image-thumbnail').forEach((thumb, idx) => {
                            thumb.addEventListener('click', function() {
                                openImageModal(idx, imagesArray);
                            });
                        });
                    } else {
                        console.warn(`Contenedor no encontrado para reporte ${reporteId}`);
                    }
                });

                // Cargar información de los reportes desde la BD
                if (data.reportes) {
                    console.log('Reportes recibidos:', data.reportes);
                    data.reportes.forEach(reporte => {
                        console.log(`Procesando reporte ${reporte.id}`);
                        const infoContainer = document.getElementById(`report-info-${reporte.id}`);
                        if (infoContainer) {
                            console.log(`Contenedor info encontrado para reporte ${reporte.id}`);
                            const tiposActividad = {
                                '77': '🌍 Evangelismo',
                                '8': '🎉 Gran Celebración',
                                '99': '💧 Bautizo',
                                '1': '🤝 Coach (1)',
                                '2': '🤝 Coach (2)',
                                '3': '🤝 Coach (3)',
                                '4': '🤝 Coach (4)',
                                '5': '🤝 Coach (5)'
                            };
                            const tiposIdActividad = {
                                '1': 'Coach',
                                '2': 'Ninguna',
                                '5': 'Otra actividad',
                                '8': 'Gran Celebracion',
                                '10': 'Siembra abundante',
                                '11': 'Caminata de oracion',
                                '12': 'Identificar al hijo de paz',
                                '13': 'Oracion Exp y Ferviente',
                                '14': 'Taller',
                                '77': 'Evangelismo',
                                '99': 'Bautizo',
                                '100': 'Capacitacion'
                            };
                            const idActividad = parseInt(reporte.id_actividad || 0, 10);
                            const tipo = tiposIdActividad[idActividad] || tiposActividad[reporte.generacionNumero] || 'Desconocido';
                            const fecha = new Date(reporte.fechaInicio).toLocaleDateString('es-CO', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                            infoContainer.innerHTML = `<strong>${tipo}</strong> • ${fecha} • Asistencia: ${reporte.asistencia_total}`;

                            // Agregar botón "Ver Mapeo" para reportes de Coach (generación 1-5)
                            if (idActividad === 1) {
                                const btnContainer = document.getElementById(`mapeo-btn-${reporte.id}`);
                                if (btnContainer) {
                                    btnContainer.innerHTML = `<button onclick="toggleMapeoChart(${reporte.id})" class="btn btn-sm btn-info" style="margin-top: 8px; font-size: 11px; padding: 4px 10px; border-radius: 4px;">📊 Ver Mapeo</button>`;
                                    // Guardar datos de mapeo en el botón para usarlos al renderizar
                                    btnContainer.dataset.mapeo = JSON.stringify({
                                        mapeo_oracion: parseInt(reporte.mapeo_oracion) || 0,
                                        mapeo_companerismo: parseInt(reporte.mapeo_companerismo) || 0,
                                        mapeo_adoracion: parseInt(reporte.mapeo_adoracion) || 0,
                                        mapeo_biblia: parseInt(reporte.mapeo_biblia) || 0,
                                        mapeo_evangelizar: parseInt(reporte.mapeo_evangelizar) || 0,
                                        mapeo_cena: parseInt(reporte.mapeo_cena) || 0,
                                        mapeo_dar: parseInt(reporte.mapeo_dar) || 0,
                                        mapeo_bautizar: parseInt(reporte.mapeo_bautizar) || 0,
                                        mapeo_trabajadores: parseInt(reporte.mapeo_trabajadores) || 0
                                    });
                                }
                            }
                        } else {
                            console.warn(`Contenedor info no encontrado para reporte ${reporte.id}`);
                        }
                    });
                } else {
                    console.warn('No se recibieron reportes en data.reportes');
                }
            } else {
                console.warn('Data no es success o imagenes vacío:', data);
            }
        })
        .catch(error => {
            console.error('Error al cargar imágenes:', error);
        });
    }

    function loadReportImages(reporteIds) {
        // Mantener función antigua para compatibilidad
        loadReportImagesAndInfo(reporteIds);
    }

    function openImageModal(currentIndex, imagesArray) {
        let currentImageIndex = currentIndex;
        const images = imagesArray;
        const totalImages = images.length;

        // Crear modal de imagen
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow: auto;
        `;

        function updateImage() {
            const imgContainer = modal.querySelector('.image-container');
            const imgElement = modal.querySelector('.modal-image');
            const counterElement = modal.querySelector('.image-counter');

            imgElement.src = images[currentImageIndex];
            counterElement.textContent = `${currentImageIndex + 1} de ${totalImages}`;

            // Habilitar/deshabilitar botones
            modal.querySelector('.btn-prev').disabled = currentImageIndex === 0;
            modal.querySelector('.btn-next').disabled = currentImageIndex === totalImages - 1;
        }

        function nextImage() {
            if (currentImageIndex < totalImages - 1) {
                currentImageIndex++;
                updateImage();
            }
        }

        function prevImage() {
            if (currentImageIndex > 0) {
                currentImageIndex--;
                updateImage();
            }
        }

        function closeModal() {
            document.removeEventListener('keydown', handleKeypress);
            modal.remove();
        }

        function handleKeypress(e) {
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
        }

        modal.innerHTML = `
            <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px 20px 20px;">
                <div class="image-container" style="display: flex; align-items: center; justify-content: center; position: relative; flex: 1; width: 100%; max-width: 95vw; overflow: auto;">
                    <img class="modal-image" src="${images[currentImageIndex]}" style="max-width: 95vw; max-height: 75vh; width: auto; height: auto; border-radius: 8px; object-fit: contain;">
                </div>

                <div style="display: flex; align-items: center; gap: 15px; margin-top: 20px; width: 100%; justify-content: center; flex-shrink: 0;">
                    <button class="btn-prev" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.2s;">◀ Anterior</button>
                    <span class="image-counter" style="color: white; font-size: 14px; min-width: 80px; text-align: center;">${currentImageIndex + 1} de ${totalImages}</span>
                    <button class="btn-next" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.2s;">Siguiente ▶</button>
                </div>

                <button class="btn-close" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.95); border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 1001; transition: all 0.2s;">✕ Cerrar</button>
            </div>
        `;

        // Agregar event listeners
        modal.querySelector('.btn-next').addEventListener('click', nextImage);
        modal.querySelector('.btn-prev').addEventListener('click', prevImage);
        modal.querySelector('.btn-close').addEventListener('click', closeModal);

        // Cerrar al hacer clic fuera de la imagen
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Agregar listener para teclado
        document.addEventListener('keydown', handleKeypress);

        document.body.appendChild(modal);
        updateImage();
    }

    function renderReportsList(grupoData, tabReports, reporteIds) {
        const reportesIds = normalizarReportesIds(reporteIds);
        grupoData.reportes_mostrados_ids = reportesIds;
        grupoData.reportes = reportesIds.length;

        if (reportesIds.length > 0) {
            const reportsHTML = reportesIds.map((reporteId, index) => `
                <div class="report-item"
                     data-href="index.php?doc=reportar&id=${reporteId}"
                     style="display: flex; gap: 15px; padding: 12px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fafafa; margin-bottom: 10px; cursor: pointer; transition: background-color 0.2s ease, border-color 0.2s ease;">
                    <div style="flex-shrink: 0;">
                        <div style="background: #2c3e50; color: white; width: 60px; height: 60px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">
                            #${reporteId}
                        </div>
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #333;">Reporte ${index + 1} de ${reportesIds.length}</strong>
                            <span style="background: #e8e8e8; color: #2c3e50; padding: 4px 8px; border-radius: 4px; font-size: 12px;">ID: ${reporteId}</span>
                        </div>
                        <div id="report-info-${reporteId}" style="font-size: 12px; color: #666; margin-bottom: 8px;">
                            Cargando información...
                        </div>
                        <div id="images-${reporteId}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 6px; margin-top: 8px;">
                            <!-- Las imágenes se cargarán aquí -->
                        </div>
                        <div id="mapeo-btn-${reporteId}"></div>
                        <div id="mapeo-chart-${reporteId}" style="display: none; text-align: center; margin-top: 10px;">
                            <canvas id="mapeo-canvas-${reporteId}" width="400" height="400" style="max-width: 100%; border: 1px solid #ddd; border-radius: 8px; background: #fff;"></canvas>
                        </div>
                    </div>
                </div>
            `).join('');

            tabReports.innerHTML = reportsHTML;

            tabReports.querySelectorAll('.report-item').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f6ff';
                    this.style.borderColor = '#b9d4f5';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '#fafafa';
                    this.style.borderColor = '#e0e0e0';
                });
                item.addEventListener('click', function(e) {
                    if (e.target.closest('.image-thumbnail, button, canvas, a')) {
                        return;
                    }
                    const href = this.dataset.href;
                    if (href) {
                        window.location.href = href;
                    }
                });
            });

            // Cargar información e imágenes de los reportes
            if (reportesIds.length > 0) {
                loadReportImagesAndInfo(reportesIds);
            }
        } else {
            tabReports.innerHTML = `
                <div class="empty-message">
                    <p>Este grupo no tiene reportes registrados</p>
                </div>
            `;
        }
    }

    function agruparImagenesPorReporte(imagenes) {
        const imagesByReport = {};
        (imagenes || []).forEach(img => {
            const reporteId = parseInt(img.reporte_id, 10);
            if (!imagesByReport[reporteId]) {
                imagesByReport[reporteId] = [];
            }
            imagesByReport[reporteId].push(img);
        });
        return imagesByReport;
    }

    function renderReporteResumen(reporte) {
        const fecha = reporte.fechaInicio
            ? new Date(reporte.fechaInicio).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
            : 'Sin fecha';
        const asistencia = parseInt(reporte.asistencia_total || 0, 10);
        const discipulado = parseInt(reporte.discipulado || 0, 10);
        const desiciones = parseInt(reporte.desiciones || 0, 10);
        const preparandose = parseInt(reporte.preparandose || 0, 10);
        const idActividad = parseInt(reporte.id_actividad || 0, 10);
        const generacion = parseInt(reporte.generacionNumero || 0, 10);
        let metricasHTML = `<span>👥 Asistencia: <strong style="color: #2c3e50;">${asistencia}</strong></span>`;

        if (idActividad === 77 || (idActividad === 0 && generacion === 77)) {
            metricasHTML += `<br><span>✝️ Decisiones de Fé: <strong style="color: #2c3e50;">${desiciones}</strong></span>`;
        } else if (idActividad === 1 || (idActividad === 0 && generacion >= 1 && generacion <= 5)) {
            metricasHTML += ` | <span>📖 Discipulado: <strong style="color: #2c3e50;">${discipulado}</strong></span><br>
                    <span>✝️ Decisiones de Fé: <strong style="color: #2c3e50;">${desiciones}</strong></span> | 
                    <span>🎯 Preparándose: <strong style="color: #2c3e50;">${preparandose}</strong></span>`;
        }

        return `
            <div style="line-height: 1.6;">
                <div style="font-weight: 600; color: #2c3e50; margin-bottom: 6px;">${obtenerEtiquetaActividad(reporte)}</div>
                <div style="color: #555; font-size: 11px;">
                    <span style="font-weight: 500;">📅 ${fecha}</span><br>
                    ${metricasHTML}
                </div>
            </div>
        `;
    }

    function renderReportsListFast(grupoData, tabReports, reportes, imagenes = []) {
        const reportesLista = Array.isArray(reportes) ? reportes : [];
        const reportesIds = normalizarReportesIds(reportesLista.map(reporte => reporte.id));
        const imagesByReport = agruparImagenesPorReporte(imagenes);
        grupoData.reportes_mostrados_ids = reportesIds;
        grupoData.reportes_ids = reportesIds;
        grupoData.reportes = reportesIds.length;

        if (reportesLista.length === 0) {
            tabReports.innerHTML = `
                <div class="empty-message">
                    <p>Este grupo no tiene reportes registrados</p>
                </div>
            `;
            return;
        }

        tabReports.innerHTML = reportesLista.map((reporte, index) => {
            const reporteId = parseInt(reporte.id, 10);
            const images = imagesByReport[reporteId] || [];
            const imagesHTML = images.map((img, imgIndex) => {
                const thumbnail = img.rutaThumbnail || img.ruta;
                return `
                    <div class="image-thumbnail" data-index="${imgIndex}" data-report="${reporteId}" style="position: relative; width: 100%; aspect-ratio: 1; border-radius: 4px; overflow: hidden; cursor: pointer; border: 1px solid #ddd; background: #f5f5f5;">
                        <img src="${thumbnail}" alt="${img.nombre}"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             title="${img.nombre}">
                        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background 0.2s;"
                             onmouseover="this.style.background='rgba(0,0,0,0.2)'"
                             onmouseout="this.style.background='rgba(0,0,0,0)'"></div>
                    </div>
                `;
            }).join('');
            const idActividad = parseInt(reporte.id_actividad || 0, 10);
            const mapeoData = JSON.stringify({
                mapeo_oracion: parseInt(reporte.mapeo_oracion) || 0,
                mapeo_companerismo: parseInt(reporte.mapeo_companerismo) || 0,
                mapeo_adoracion: parseInt(reporte.mapeo_adoracion) || 0,
                mapeo_biblia: parseInt(reporte.mapeo_biblia) || 0,
                mapeo_evangelizar: parseInt(reporte.mapeo_evangelizar) || 0,
                mapeo_cena: parseInt(reporte.mapeo_cena) || 0,
                mapeo_dar: parseInt(reporte.mapeo_dar) || 0,
                mapeo_bautizar: parseInt(reporte.mapeo_bautizar) || 0,
                mapeo_trabajadores: parseInt(reporte.mapeo_trabajadores) || 0
            }).replace(/"/g, '&quot;');

            return `
                <div class="report-item"
                     data-href="index.php?doc=reportar&id=${reporteId}"
                     style="display: flex; gap: 15px; padding: 12px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fafafa; margin-bottom: 10px; cursor: pointer; transition: background-color 0.2s ease, border-color 0.2s ease;">
                    <div style="flex-shrink: 0;">
                        <div style="background: #2c3e50; color: white; width: 60px; height: 60px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">
                            #${reporteId}
                        </div>
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #333;">Reporte ${index + 1} de ${reportesIds.length}</strong>
                            <span style="background: #e8e8e8; color: #2c3e50; padding: 4px 8px; border-radius: 4px; font-size: 12px;">ID: ${reporteId}</span>
                        </div>
                        <div id="report-info-${reporteId}" style="font-size: 12px; color: #666; margin-bottom: 8px;">
                            ${renderReporteResumen(reporte)}
                        </div>
                        <div id="images-${reporteId}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 6px; margin-top: 8px;">
                            ${imagesHTML || '<span style="font-size: 12px; color: #999;">Sin imagenes</span>'}
                        </div>
                        <div id="mapeo-btn-${reporteId}" data-mapeo="${mapeoData}">
                            ${idActividad === 1 ? `<button onclick="toggleMapeoChart(${reporteId})" class="btn btn-sm btn-info" style="margin-top: 8px; font-size: 11px; padding: 4px 10px; border-radius: 4px;">Ver Mapeo</button>` : ''}
                        </div>
                        <div id="mapeo-chart-${reporteId}" style="display: none; text-align: center; margin-top: 10px;">
                            <canvas id="mapeo-canvas-${reporteId}" width="400" height="400" style="max-width: 100%; border: 1px solid #ddd; border-radius: 8px; background: #fff;"></canvas>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        tabReports.querySelectorAll('.report-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f0f6ff';
                this.style.borderColor = '#b9d4f5';
            });
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '#fafafa';
                this.style.borderColor = '#e0e0e0';
            });
            item.addEventListener('click', function(e) {
                if (e.target.closest('.image-thumbnail, button, canvas, a')) {
                    return;
                }
                const href = this.dataset.href;
                if (href) {
                    window.location.href = href;
                }
            });
        });

        tabReports.querySelectorAll('.image-thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function() {
                const reporteId = parseInt(this.dataset.report, 10);
                const images = (imagesByReport[reporteId] || []).map(i => i.ruta);
                const index = parseInt(this.dataset.index, 10) || 0;
                openImageModal(index, images);
            });
        });
    }

    function updateReportsTab(grupoData, tabReports) {
        const idGrupo = obtenerIdGrupoSeleccionado(grupoData);

        if (!idGrupo) {
            tabReports.innerHTML = `
                <div class="empty-message">
                    <p>No se pudo identificar el grupo seleccionado</p>
                </div>
            `;
            return;
        }

        tabReports.innerHTML = `
            <div class="empty-message">
                <p>Cargando reportes...</p>
            </div>
        `;

        fetch('obtener_reportes_grupo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idGrupo: idGrupo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reportesIds = normalizarReportesIds(data.reportes_ids || []);
                grupoData.reportes_ids = reportesIds;
                grupoData.reportes_mostrados_ids = reportesIds;
                grupoData.reportes = reportesIds.length;
                updateInfoTab(grupoData, document.getElementById('tab-info'), data.imagenes || [], false);
                renderReportsListFast(grupoData, tabReports, data.reportes || [], data.imagenes || []);
            } else {
                console.error('Error al cargar reportes del grupo:', data.message);
                grupoData.reportes_ids = [];
                grupoData.reportes_mostrados_ids = [];
                grupoData.reportes = 0;
                updateInfoTab(grupoData, document.getElementById('tab-info'), [], false);
                renderReportsListFast(grupoData, tabReports, [], []);
            }
        })
        .catch(error => {
            console.error('Error al cargar reportes del grupo:', error);
            grupoData.reportes_ids = [];
            grupoData.reportes_mostrados_ids = [];
            grupoData.reportes = 0;
            updateInfoTab(grupoData, document.getElementById('tab-info'), [], false);
            renderReportsListFast(grupoData, tabReports, [], []);
        });
    }

    function renderGroups() {
        const groupsList = document.getElementById('groupsList');
        const leftPanelHeader = document.querySelector('.left-panel-header');

        if (filteredGrupos.length === 0) {
            groupsList.innerHTML = `
                <div class="empty-message">
                    <p>No se encontraron grupos</p>
                </div>
            `;
            return;
        }

        leftPanelHeader.textContent = `📋 Mis IPG (${filteredGrupos.length})`;

        const groupsHTML = filteredGrupos.map((grupoData) => {
            return `
                <div class="group-item">
                    <div class="group-item-title">${grupoData.nombre_exacto}</div>
                    <div class="group-item-info">
                        <div>📍 ${grupoData.ubicacion || 'Sin ubicación'}</div>
                        <div>📊 ${grupoData.reportes} reporte(s)</div>
                    </div>
                </div>
            `;
        }).join('');

        groupsList.innerHTML = groupsHTML;

        // Agregar listeners de click a los items
        document.querySelectorAll('.group-item').forEach((item, index) => {
            item.addEventListener('click', function() {
                selectGrupo(filteredGrupos[index], this);
            });
        });
    }

    function filterGroups() {
        const searchTerm = document.getElementById('searchFilter').value.toLowerCase();
        filteredGrupos = grupos.filter(grupo => {
            const nombre = (grupo.nombre_exacto || '').toLowerCase();
            const ubicacion = (grupo.ubicacion || '').toLowerCase();
            const lider = (grupo.lider || '').toLowerCase();
            return nombre.includes(searchTerm) || ubicacion.includes(searchTerm) || lider.includes(searchTerm);
        });
        renderGroups();
    }

    function newReport() {
        if (!selectedGrupo) {
            showStatusMessage('Por favor selecciona un grupo primero', 'error');
            return;
        }

        // Abrir modal de selección de actividad
        openActivityModal();
    }

    function openActivityModal() {
        const modal = document.getElementById('activityModal');
        modal.classList.add('active');
        resetNewReportForm();

        // Reset form
        document.getElementById('activitySelection').style.display = 'block';
        document.getElementById('reportForm').style.display = 'none';

        // Guardar IDs de reportes del grupo seleccionado
        if (selectedGrupo) {
            document.getElementById('reporteIds').value = JSON.stringify(selectedGrupo.reportes_mostrados_ids || []);
        }
    }

    function closeActivityModal() {
        const modal = document.getElementById('activityModal');
        modal.classList.remove('active');
        resetNewReportForm();
    }

    function selectActivity(tipoActividad) {
        // Guardar tipo de actividad
        document.getElementById('tipoActividad').value = tipoActividad;

        // Establecer fecha actual como valor por defecto
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaActividad').value = today;

        // Mostrar/ocultar campos según tipo de actividad
        const bautizadosSection = document.getElementById('bautizadosSection');
        const decisionesSection = document.getElementById('decisionesSection');
        const comentariosSection = document.getElementById('comentariosSection');
        const mapeosSection = document.getElementById('mapeosSection');
        const metricasEvangelismoSection = document.getElementById('metricasEvangelismoSection');
        const metricasReporteTitulo = document.getElementById('metricasReporteTitulo');
        const asistenciaLabel = document.getElementById('asistenciaLabel');
        const camposMetricas = {
            discipulado: {
                wrapper: document.getElementById('metricFieldDiscipulado'),
                input: document.getElementById('discipulado')
            },
            decisiones: {
                wrapper: document.getElementById('metricFieldDecisiones'),
                input: document.getElementById('desiciones_extra')
            },
            preparandose: {
                wrapper: document.getElementById('metricFieldPreparandose'),
                input: document.getElementById('preparandose')
            }
        };

        function configurarCampoMetrica(campo, visible) {
            if (!campo || !campo.wrapper || !campo.input) {
                return;
            }

            campo.wrapper.style.display = visible ? '' : 'none';
            if (!visible) {
                campo.input.value = 0;
            }
        }

        function configurarMetricas(opciones = {}) {
            metricasEvangelismoSection.style.display = opciones.mostrar ? 'block' : 'none';
            metricasReporteTitulo.textContent = opciones.titulo || 'Asistencia Total';
            configurarCampoMetrica(camposMetricas.discipulado, !!opciones.discipulado);
            configurarCampoMetrica(camposMetricas.decisiones, !!opciones.decisiones);
            configurarCampoMetrica(camposMetricas.preparandose, !!opciones.preparandose);
        }

        // Ocultar todos por defecto
        bautizadosSection.style.display = 'none';
        decisionesSection.style.display = 'none';
        comentariosSection.style.display = 'none';
        mapeosSection.style.display = 'none';
        configurarMetricas({ mostrar: false });
        document.getElementById('discipulado').value = 0;
        document.getElementById('desiciones_extra').value = 0;
        document.getElementById('preparandose').value = 0;
        document.getElementById('bautizados').value = 0;
        document.getElementById('desiciones').value = 0;
        clearReportMetricasError();

        // Mostrar según tipo
        if (tipoActividad === 'bautizo') {
            bautizadosSection.style.display = 'block';
            asistenciaLabel.textContent = 'Asistencia';
        } else if (tipoActividad === 'gran_celebracion') {
            comentariosSection.style.display = 'block';
            asistenciaLabel.textContent = 'Asistencia';
        } else if ([
            'evangelismo',
            'siembra_abundante',
            'caminata_oracion',
            'identificar_hijo_paz',
            'oracion_exp_ferviente',
            'taller',
            'otra_actividad',
            'capacitacion'
        ].includes(tipoActividad)) {
            comentariosSection.style.display = 'block';
            configurarMetricas({
                mostrar: true,
                titulo: 'Alcanzados Total',
                decisiones: true
            });
            asistenciaLabel.textContent = 'Alcanzados';
        } else if (tipoActividad === 'reunion_cotidiana') {
            configurarMetricas({
                mostrar: true,
                titulo: 'Asistencia Total',
                discipulado: true,
                decisiones: true,
                preparandose: true
            });
            mapeosSection.style.display = 'block';
            asistenciaLabel.textContent = 'Asistencia';
        }
        actualizarAsistenciaReporte();

        // Cambiar título del formulario
        const titles = {
            'evangelismo': '🌍 Nuevo Reporte - Evangelismo',
            'gran_celebracion': '🎉 Nuevo Reporte - Gran Celebración',
            'bautizo': '💧 Nuevo Reporte - Bautizo',
            'reunion_cotidiana': '🤝 Nuevo Reporte - Coach',
            'siembra_abundante': '🌱 Nuevo Reporte - Siembra Abundante',
            'caminata_oracion': '🚶 Nuevo Reporte - Caminata de Oración',
            'identificar_hijo_paz': '🕊️ Nuevo Reporte - Identificar al Hijo de Paz',
            'oracion_exp_ferviente': '🙏 Nuevo Reporte - Oración Exp. y Ferviente',
            'taller': '🛠️ Nuevo Reporte - Taller',
            'otra_actividad': '➕ Nuevo Reporte - Otra Actividad',
            'capacitacion': '🎓 Nuevo Reporte - Capacitación'
        };
        document.getElementById('formTitle').textContent = titles[tipoActividad] || 'Nuevo Reporte';

        // Cambiar a vista de formulario
        document.getElementById('activitySelection').style.display = 'none';
        document.getElementById('reportForm').style.display = 'block';
    }

    function backToActivitySelection() {
        clearReportFormErrors();
        document.getElementById('activitySelection').style.display = 'block';
        document.getElementById('reportForm').style.display = 'none';
    }

    // Manejar vista previa de imágenes y validación
    function obtenerAsistenciaTotalReporte() {
        return (parseInt(document.getElementById('asistencia_hom').value) || 0)
            + (parseInt(document.getElementById('asistencia_muj').value) || 0)
            + (parseInt(document.getElementById('asistencia_jov').value) || 0)
            + (parseInt(document.getElementById('asistencia_nin').value) || 0);
    }

    function actualizarAsistenciaReporte() {
        const total = obtenerAsistenciaTotalReporte();
        const totalElement = document.getElementById('asistenciaTotalReporte');
        if (totalElement) {
            totalElement.textContent = total;
        }
        if (total > 0) {
            clearReportAsistenciaError();
            validarMetricasContraAsistencia(false);
        } else {
            clearReportMetricasError();
        }
    }

    function validarAsistenciaMinimaReporte() {
        const total = obtenerAsistenciaTotalReporte();
        if (total < 1) {
            showReportAsistenciaError('La asistencia total debe ser mínimo 1');
            document.getElementById('asistencia_hom').focus();
            return false;
        }

        clearReportAsistenciaError();
        return true;
    }

    function obtenerCamposNumericosValidablesReporte() {
        const campos = [];
        const bautizadosSection = document.getElementById('bautizadosSection');
        const decisionesSection = document.getElementById('decisionesSection');
        const metricasSection = document.getElementById('metricasEvangelismoSection');

        if (bautizadosSection && bautizadosSection.style.display !== 'none') {
            campos.push({ id: 'bautizados', label: 'Cantidad de Bautizados' });
        }

        if (decisionesSection && decisionesSection.style.display !== 'none') {
            campos.push({ id: 'desiciones', label: 'Decisiones de Fe' });
        }

        if (metricasSection && metricasSection.style.display !== 'none') {
            [
                { id: 'discipulado', wrapperId: 'metricFieldDiscipulado', label: 'Discipulado' },
                { id: 'desiciones_extra', wrapperId: 'metricFieldDecisiones', label: 'Decisiones de Fe' },
                { id: 'preparandose', wrapperId: 'metricFieldPreparandose', label: 'Preparandose' }
            ].forEach(campo => {
                const wrapper = document.getElementById(campo.wrapperId);
                if (wrapper && wrapper.style.display !== 'none') {
                    campos.push({ id: campo.id, label: campo.label });
                }
            });
        }

        return campos;
    }

    function validarMetricasContraAsistencia(enfocarCampo = true) {
        const total = obtenerAsistenciaTotalReporte();
        const campos = obtenerCamposNumericosValidablesReporte();

        if (campos.length === 0) {
            clearReportMetricasError();
            return true;
        }

        for (const campo of campos) {
            const input = document.getElementById(campo.id);
            const valor = parseInt(input.value, 10) || 0;
            if (valor > total) {
                showReportMetricasError(campo.id, `${campo.label} no puede ser mayor a la asistencia total`);
                if (enfocarCampo && input) {
                    input.focus();
                }
                return false;
            }
        }

        clearReportMetricasError();
        return true;
    }

    function obtenerCamposFotoReporte() {
        return Array.from(document.querySelectorAll('.fotos-evidencia-input'));
    }

    function validarArchivoFotoReporte(file) {
        if (!REPORT_ALLOWED_IMAGE_TYPES.includes(file.type)) {
            return `Archivo ${file.name}: formato no permitido. Solo JPG, PNG, WebP`;
        }

        if (file.size > REPORT_MAX_IMAGE_SIZE) {
            return `Archivo ${file.name}: excede 5 MB (${(file.size / 1024 / 1024).toFixed(1)} MB)`;
        }

        return '';
    }

    function actualizarEstadoBotonAgregarFoto() {
        const addFotoBtn = document.getElementById('addOtraFotoBtn');
        if (!addFotoBtn) {
            return;
        }

        const totalCampos = obtenerCamposFotoReporte().length;
        addFotoBtn.disabled = totalCampos >= MAX_REPORT_IMAGES;
        addFotoBtn.style.display = totalCampos >= MAX_REPORT_IMAGES ? 'none' : 'block';
    }

    function renderFotosPreview() {
        const fotosPreview = document.getElementById('fotosPreview');
        const fotosCountMsg = document.getElementById('fotosCountMsg');

        if (!fotosPreview || !fotosCountMsg) {
            return;
        }

        fotosPreview.innerHTML = '';
        const fotosValidas = obtenerFotosEvidenciaValidas();

        fotosValidas.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.createElement('div');
                preview.style.cssText = `
                    position: relative;
                    width: 100%;
                    aspect-ratio: 1;
                    border-radius: 6px;
                    overflow: hidden;
                    background: #f0f0f0;
                    border: 2px solid #2c3e50;
                `;
                preview.innerHTML = `
                    <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.6); color: white; font-size: 11px; padding: 2px 6px; border-radius: 3px;">${index + 1}</div>
                `;
                fotosPreview.appendChild(preview);
            };
            reader.readAsDataURL(file);
        });

        if (fotosValidas.length === 0) {
            fotosCountMsg.textContent = '';
            fotosCountMsg.style.color = '#666';
        } else if (fotosValidas.length === 1) {
            fotosCountMsg.textContent = '1 imagen cargada (mínimo requerido)';
            fotosCountMsg.style.color = '#27ae60';
        } else {
            fotosCountMsg.textContent = `${fotosValidas.length} imágenes cargadas (${MAX_REPORT_IMAGES - fotosValidas.length} espacios disponibles)`;
            fotosCountMsg.style.color = '#27ae60';
        }
    }

    function manejarCambioFotosReporte(event) {
        const input = event.target;
        const file = input.files && input.files[0] ? input.files[0] : null;

        if (file) {
            const errorArchivo = validarArchivoFotoReporte(file);
            if (errorArchivo) {
                showReportFotosError(errorArchivo);
                input.value = '';
            } else {
                clearInlineFormError('reportFotosError');
            }
        }

        renderFotosPreview();
    }

    function agregarCampoFotoReporte() {
        const container = document.getElementById('fotosInputsContainer');
        if (!container) {
            return;
        }

        const totalCampos = obtenerCamposFotoReporte().length;
        if (totalCampos >= MAX_REPORT_IMAGES) {
            showReportFotosError(`Solo puedes cargar máximo ${MAX_REPORT_IMAGES} imágenes por reporte.`);
            return;
        }

        const nuevoIndice = totalCampos + 1;
        const wrapper = document.createElement('div');
        wrapper.className = 'foto-evidencia-item';
        wrapper.dataset.slot = nuevoIndice;
        wrapper.innerHTML = `
            <label style="font-size: 12px;">Foto ${nuevoIndice}</label>
            <input type="file" class="fotos-evidencia-input" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: block; margin-top: 6px;">
        `;

        const input = wrapper.querySelector('.fotos-evidencia-input');
        if (input) {
            input.addEventListener('change', manejarCambioFotosReporte);
        }

        container.appendChild(wrapper);
        actualizarEstadoBotonAgregarFoto();
    }

    function resetNewReportForm() {
        const form = document.getElementById('newReportForm');
        const fotosContainer = document.getElementById('fotosInputsContainer');
        const fotosPreview = document.getElementById('fotosPreview');
        const fotosCountMsg = document.getElementById('fotosCountMsg');

        if (form) {
            form.reset();
        }

        clearReportFormErrors();

        if (fotosContainer) {
            fotosContainer.innerHTML = `
                <div class="foto-evidencia-item" data-slot="1">
                    <label style="font-size: 12px;">Foto 1</label>
                    <input type="file" class="fotos-evidencia-input" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: block; margin-top: 6px;">
                </div>
            `;

            const primerInput = fotosContainer.querySelector('.fotos-evidencia-input');
            if (primerInput) {
                primerInput.addEventListener('change', manejarCambioFotosReporte);
            }
        }

        if (fotosPreview) {
            fotosPreview.innerHTML = '';
        }

        if (fotosCountMsg) {
            fotosCountMsg.textContent = '';
            fotosCountMsg.style.color = '#666';
        }

        actualizarAsistenciaReporte();
        actualizarEstadoBotonAgregarFoto();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addFotoBtn = document.getElementById('addOtraFotoBtn');

        ['asistencia_hom', 'asistencia_muj', 'asistencia_jov', 'asistencia_nin'].forEach(function(id) {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', actualizarAsistenciaReporte);
                input.addEventListener('change', actualizarAsistenciaReporte);
            }
        });

        ['bautizados', 'desiciones', 'discipulado', 'desiciones_extra', 'preparandose'].forEach(function(id) {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', function() {
                    if (obtenerAsistenciaTotalReporte() > 0) {
                        validarMetricasContraAsistencia(false);
                    } else {
                        clearReportMetricasError();
                    }
                });
                input.addEventListener('change', function() {
                    if (obtenerAsistenciaTotalReporte() > 0) {
                        validarMetricasContraAsistencia(false);
                    } else {
                        clearReportMetricasError();
                    }
                });
            }
        });

        if (addFotoBtn) {
            addFotoBtn.addEventListener('click', agregarCampoFotoReporte);
        }

        resetNewReportForm();
    });

    function obtenerFotosEvidenciaValidas() {
        return obtenerCamposFotoReporte()
            .map(input => (input.files && input.files[0]) ? input.files[0] : null)
            .filter(file => file && REPORT_ALLOWED_IMAGE_TYPES.includes(file.type) && file.size <= REPORT_MAX_IMAGE_SIZE);
    }

    function saveNewReport(event) {
        event.preventDefault();
        clearReportFormErrors();

        if (!selectedGrupo) {
            showReportFormError('No hay grupo seleccionado');
            return;
        }

        if (!validarAsistenciaMinimaReporte()) {
            return;
        }

        const fotosValidas = obtenerFotosEvidenciaValidas();
        if (fotosValidas.length === 0) {
            showReportFotosError('Debe cargar al menos 1 evidencia fotográfica para guardar el reporte (máximo 3)');
            return;
        }
        if (fotosValidas.length > MAX_REPORT_IMAGES) {
            showReportFotosError(`Solo puedes cargar máximo ${MAX_REPORT_IMAGES} imágenes por reporte (cargaste ${fotosValidas.length})`);
            return;
        }
        if (!validarMetricasContraAsistencia()) {
            return;
        }

        const tipoActividad = document.getElementById('tipoActividad').value;
        const reporteIds = JSON.parse(document.getElementById('reporteIds').value || '[]');
        const idGrupo = obtenerIdGrupoSeleccionado(selectedGrupo);

        if (!idGrupo) {
            showReportFormError('No se pudo identificar el grupo seleccionado');
            return;
        }

        const datosReporte = {
            tipoActividad: tipoActividad,
            idGrupo: idGrupo,
            reporteIds: reporteIds,
            fechaActividad: document.getElementById('fechaActividad').value,
            asistencia_hom: parseInt(document.getElementById('asistencia_hom').value) || 0,
            asistencia_muj: parseInt(document.getElementById('asistencia_muj').value) || 0,
            asistencia_jov: parseInt(document.getElementById('asistencia_jov').value) || 0,
            asistencia_nin: parseInt(document.getElementById('asistencia_nin').value) || 0
        };

        // Agregar campos opcionales si están visibles
        if (document.getElementById('bautizadosSection').style.display !== 'none') {
            datosReporte.bautizados = parseInt(document.getElementById('bautizados').value) || 0;
        }
        if (document.getElementById('decisionesSection').style.display !== 'none') {
            datosReporte.desiciones = parseInt(document.getElementById('desiciones').value) || 0;
        }
        if (document.getElementById('metricasEvangelismoSection').style.display !== 'none') {
            if (document.getElementById('metricFieldDiscipulado').style.display !== 'none') {
                datosReporte.discipulado = parseInt(document.getElementById('discipulado').value) || 0;
            }
            if (document.getElementById('metricFieldDecisiones').style.display !== 'none') {
                datosReporte.desiciones = parseInt(document.getElementById('desiciones_extra').value) || 0;
            }
            if (document.getElementById('metricFieldPreparandose').style.display !== 'none') {
                datosReporte.preparandose = parseInt(document.getElementById('preparandose').value) || 0;
            }
        }
        if (document.getElementById('comentariosSection').style.display !== 'none') {
            datosReporte.comentario = document.getElementById('comentario').value;
        }
        if (document.getElementById('mapeosSection').style.display !== 'none') {
            const compromisoSelect = document.getElementById('mapeo_comprometido');
            const camposMapeo = [
                { id: 'mapeo_oracion', etiqueta: 'Oracion' },
                { id: 'mapeo_companerismo', etiqueta: 'Companerismo' },
                { id: 'mapeo_adoracion', etiqueta: 'Adoracion' },
                { id: 'mapeo_biblia', etiqueta: 'Aplicar la Biblia' },
                { id: 'mapeo_evangelizar', etiqueta: 'Evangelizar' },
                { id: 'mapeo_cena', etiqueta: 'Cena del Senor' },
                { id: 'mapeo_dar', etiqueta: 'Dar (Ofrendas)' },
                { id: 'mapeo_bautizar', etiqueta: 'Bautizar' },
                { id: 'mapeo_trabajadores', etiqueta: 'Entrenar Nuevos Lideres' }
            ];

            if (!compromisoSelect || compromisoSelect.value === '') {
                showReportMapeosError('mapeo_comprometido', 'Debe seleccionar si este grupo esta comprometido como iglesia.');
                if (compromisoSelect) {
                    compromisoSelect.focus();
                }
                return;
            }

            for (let i = 0; i < camposMapeo.length; i++) {
                const campo = camposMapeo[i];
                const select = document.getElementById(campo.id);
                if (!select || parseInt(select.value, 10) < 1) {
                    showReportMapeosError(campo.id, 'Debe seleccionar una opcion para: ' + campo.etiqueta + '.');
                    if (select) {
                        select.focus();
                    }
                    return;
                }
            }

            datosReporte.mapeo_comprometido = parseInt(compromisoSelect.value, 10) || 0;
            datosReporte.mapeo_oracion = parseInt(document.getElementById('mapeo_oracion').value) || 0;
            datosReporte.mapeo_companerismo = parseInt(document.getElementById('mapeo_companerismo').value) || 0;
            datosReporte.mapeo_adoracion = parseInt(document.getElementById('mapeo_adoracion').value) || 0;
            datosReporte.mapeo_biblia = parseInt(document.getElementById('mapeo_biblia').value) || 0;
            datosReporte.mapeo_evangelizar = parseInt(document.getElementById('mapeo_evangelizar').value) || 0;
            datosReporte.mapeo_cena = parseInt(document.getElementById('mapeo_cena').value) || 0;
            datosReporte.mapeo_dar = parseInt(document.getElementById('mapeo_dar').value) || 0;
            datosReporte.mapeo_bautizar = parseInt(document.getElementById('mapeo_bautizar').value) || 0;
            datosReporte.mapeo_trabajadores = parseInt(document.getElementById('mapeo_trabajadores').value) || 0;
        }

        console.log('Datos del reporte:', datosReporte);

        // Mostrar estado de carga
        const btnSubmit = event.target.querySelector('button[type="submit"]');
        const textOriginal = btnSubmit.textContent;
        btnSubmit.textContent = 'Guardando...';
        btnSubmit.disabled = true;

        fetch('crear_reporte.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosReporte)
        })
        .then(response => response.text().then(text => {
            console.log('Response status:', response.status);
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        }))
        .then(data => {
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;

            if (data.success) {
                // Reporte creado exitosamente
                const nuevoReporteId = data.nuevoReporteId;
                if (selectedGrupo && nuevoReporteId) {
                    const reportesActuales = normalizarReportesIds(selectedGrupo.reportes_mostrados_ids || []);
                    reportesActuales.push(parseInt(nuevoReporteId, 10));
                    selectedGrupo.reportes_mostrados_ids = normalizarReportesIds(reportesActuales);
                    selectedGrupo.reportes = selectedGrupo.reportes_mostrados_ids.length;
                }

                // Procesar imágenes si existen
                const fotosValidas = obtenerFotosEvidenciaValidas();
                if (fotosValidas.length > 0) {
                    btnSubmit.textContent = 'Guardando imágenes...';
                    btnSubmit.disabled = true;
                    uploadReportImages(nuevoReporteId, fotosValidas, btnSubmit, textOriginal);
                } else {
                    showStatusMessage(`✅ Reporte creado correctamente`, 'success');
                    closeActivityModal();

                    // Recargar reportes del grupo
                    if (selectedGrupo) {
                        updateGroupPanel(selectedGrupo);
                    }
                }
            } else {
                const mensajeError = data.message || 'No se pudo crear el reporte';
                const mensajeNormalizado = mensajeError.toLowerCase();
                if (mensajeNormalizado.includes('asistencia')) {
                    showReportAsistenciaError(mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('asistencia_hom').focus();
                } else if (mensajeNormalizado.includes('bautizados')) {
                    showReportMetricasError('bautizados', mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('bautizados').focus();
                } else if (mensajeNormalizado.includes('discipulado')) {
                    showReportMetricasError('discipulado', mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('discipulado').focus();
                } else if (mensajeNormalizado.includes('decisiones')) {
                    const campoDecisionesId = document.getElementById('decisionesSection').style.display !== 'none'
                        ? 'desiciones'
                        : 'desiciones_extra';
                    showReportMetricasError(campoDecisionesId, mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById(campoDecisionesId).focus();
                } else if (mensajeNormalizado.includes('preparandose')) {
                    showReportMetricasError('preparandose', mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('preparandose').focus();
                } else if (mensajeNormalizado.includes('mapeo') || mensajeNormalizado.includes('comprometido como iglesia')) {
                    showReportMapeosError('mapeo_comprometido', mensajeError.replace(/^Error:\s*/i, ''));
                    const compromisoSelect = document.getElementById('mapeo_comprometido');
                    if (compromisoSelect) {
                        compromisoSelect.focus();
                    }
                } else if (mensajeNormalizado.includes('imagen') || mensajeNormalizado.includes('foto')) {
                    showReportFotosError(mensajeError.replace(/^Error:\s*/i, ''));
                } else {
                    showReportFormError(mensajeError.replace(/^Error:\s*/i, ''));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;
            showReportFormError('Error de conexión al guardar el reporte: ' + error.message);
        });
    }

    function uploadReportImages(reporteId, files, btnSubmit, textOriginal) {
        const formData = new FormData();
        formData.append('reporteId', reporteId);

        // Validar y agregar imágenes
        let validFiles = 0;

        for (let i = 0; i < files.length; i++) {
            if (REPORT_ALLOWED_IMAGE_TYPES.includes(files[i].type) && files[i].size <= REPORT_MAX_IMAGE_SIZE) {
                formData.append(`imagenes[]`, files[i]);
                validFiles++;
            }
        }

        if (validFiles === 0) {
            showReportFotosError('No hay imágenes válidas. Debe cargar al menos 1 imagen (máximo 3)');
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;
            return;
        }
        if (validFiles > MAX_REPORT_IMAGES) {
            showReportFotosError(`Solo puedes cargar máximo ${MAX_REPORT_IMAGES} imágenes por reporte (intentaste cargar ${validFiles})`);
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;
            return;
        }

        fetch('guardar_imagenes_reporte.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;

            if (data.success) {
                showStatusMessage(`✅ Reporte creado con ${data.imagesCount} imagen(s)`, 'success');
                closeActivityModal();

                // Recargar reportes del grupo
                if (selectedGrupo) {
                    updateGroupPanel(selectedGrupo);
                }
            } else {
                showReportFotosError(data.message || 'Error al guardar imágenes');
            }
        })
        .catch(error => {
            console.error('Error al subir imágenes:', error);
            console.error('Error details:', error.message);
            btnSubmit.textContent = textOriginal;
            btnSubmit.disabled = false;
            showReportFotosError('Error al guardar imágenes: ' + error.message);
        });
    }

    function loadGrupos() {
        console.log('🔄 Cargando grupos del facilitador...');

        fetch('obtener_variantes_grupos_facilitador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idFacilitador: <?php echo $idFacilitador; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Datos recibidos:', data);

            if (data.success) {
                // Traer todos los grupos del facilitador
                grupos = data.grupos || [];
                filteredGrupos = [...grupos];

                console.log(`📊 Se encontraron ${grupos.length} grupos`);

                if (grupos.length === 0) {
                    showStatusMessage('No hay grupos para mostrar.', 'info');
                } else {
                    showStatusMessage(`Se cargaron ${grupos.length} grupos correctamente`, 'success');
                }

                renderGroups();
            } else {
                showStatusMessage('Error: ' + (data.message || 'No se pudieron cargar los grupos'), 'error');
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error al cargar grupos:', error);
            showStatusMessage('Error al conectar con el servidor', 'error');
        });
    }

    // Cargar grupos al iniciar
    document.addEventListener('DOMContentLoaded', () => {
        loadGrupos();
    });

    // ===== FUNCIONES DE EDICIÓN =====
    let editFormData = {
        lideresArray: []
    };
    let createGroupFormData = {
        lideresArray: []
    };

    function openEditModal() {
        if (!selectedGrupo) {
            showStatusMessage('Por favor selecciona un grupo primero', 'info');
            return;
        }
        clearInlineFormError('editFormError');
        clearFormError('editNombre', 'editNombreError');
        clearFormError('liderInput', 'liderInputError');

        // Llenar formulario con datos actuales del grupo
        document.getElementById('editNombre').value = selectedGrupo.nombre_exacto || '';
        document.getElementById('editCiudad').value = selectedGrupo.ciudad || '';
        document.getElementById('editBarrio').value = selectedGrupo.barrio || '';
        document.getElementById('editDireccion').value = selectedGrupo.direccion || '';
        document.getElementById('editGeneracion').value = selectedGrupo.generacion || '0';

        // Establecer radio buttons para grupo madre
        // Si generación es 0, entonces no tiene grupo madre (debe ser "no aplica")
        const tieneGrupoMadre = (selectedGrupo.generacion === 0 || selectedGrupo.grupo_madre === 'no aplica' || !selectedGrupo.grupo_madre) ? 'no' : 'si';
        document.querySelector('input[name="editTieneGrupoMadre"][value="' + tieneGrupoMadre + '"]').checked = true;

        // Mostrar u ocultar selector de grupo madre según corresponda
        const grupoMadreSelect = document.getElementById('editGrupoMadreSelect');
        if (tieneGrupoMadre === 'si') {
            grupoMadreSelect.style.display = 'block';
            loadAvailableGroupsForEditModal(selectedGrupo.id_unico);
            // Esperar a que se carguen los grupos para establecer el seleccionado
            setTimeout(() => {
                const dropdown = document.getElementById('editGrupoMadreDropdown');
                // Encontrar la opción que corresponde al grupo madre actual
                for (let i = 0; i < dropdown.options.length; i++) {
                    if (dropdown.options[i].textContent.includes(selectedGrupo.grupo_madre)) {
                        dropdown.selectedIndex = i;
                        updateEditGeneracionDisplay();
                        break;
                    }
                }
            }, 300);
        } else {
            grupoMadreSelect.style.display = 'none';
            document.getElementById('editGrupoMadreDropdown').value = '';
        }

        // Procesar líderes (pueden ser JSON array o string)
        editFormData.lideresArray = [];
        if (selectedGrupo.lider) {
            try {
                const liderParsed = JSON.parse(selectedGrupo.lider);
                if (Array.isArray(liderParsed)) {
                    editFormData.lideresArray = liderParsed.filter(l => l && typeof l === 'string');
                }
            } catch (e) {
                editFormData.lideresArray = String(selectedGrupo.lider)
                    .split(',')
                    .map(l => normalizarNombreLider(l))
                    .filter(Boolean);
            }
        }

        renderLideresUI();
        document.getElementById('liderInput').value = '';
        clearFormError('liderInput', 'liderInputError');
        const grupoMadreRadio = document.querySelector('input[name="editTieneGrupoMadre"]');
        if (grupoMadreRadio) {
            const grupoMadreFormGroup = grupoMadreRadio.closest('.edit-form-group');
            if (grupoMadreFormGroup) {
                grupoMadreFormGroup.style.display = 'none';
            }
        }
        const grupoMadreSelectOculto = document.getElementById('editGrupoMadreSelect');
        if (grupoMadreSelectOculto) {
            grupoMadreSelectOculto.style.display = 'none';
        }

        // Mostrar modal
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        editFormData.lideresArray = [];
        clearInlineFormError('editFormError');
        clearFormError('liderInput', 'liderInputError');
    }

    function renderLideresUI() {
        const container = document.getElementById('lideresContainer');
        container.innerHTML = editFormData.lideresArray.map((lider, index) => `
            <div class="edit-lider-tag">
                ${lider}
                <button type="button" onclick="removeLider(${index})">✕</button>
            </div>
        `).join('');
    }

    function addLider() {
        const liderInput = document.getElementById('liderInput');
        const nuevoLider = normalizarNombreLider(liderInput.value);
        const errorLider = validarNombreLider(nuevoLider);

        if (errorLider) {
            showFormError('liderInput', 'liderInputError', errorLider);
            liderInput.focus();
            return;
        }

        if (nuevoLider && !editFormData.lideresArray.includes(nuevoLider)) {
            editFormData.lideresArray.push(nuevoLider);
            liderInput.value = '';
            clearFormError('liderInput', 'liderInputError');
            renderLideresUI();
        } else if (editFormData.lideresArray.includes(nuevoLider)) {
            showFormError('liderInput', 'liderInputError', 'Este líder ya existe');
            liderInput.focus();
        }
    }

    function removeLider(index) {
        if (editFormData.lideresArray.length <= 1) {
            showFormError('liderInput', 'liderInputError', 'Debe quedar al menos un lÃ­der capacitador en el grupo');
            return;
        }

        editFormData.lideresArray.splice(index, 1);
        clearFormError('liderInput', 'liderInputError');
        renderLideresUI();
    }

    // ============= FUNCIONES PARA CREAR NUEVO GRUPO =============

    function openCreateGroupModal() {
        console.log('Abriendo modal para crear grupo');
        const modal = document.getElementById('createGroupModal');
        modal.classList.add('active');

        // Cargar grupos disponibles como grupo madre
        loadAvailableGroupsForParent();

        // Limpiar formulario
        document.getElementById('createGroupForm').reset();
        document.getElementById('grupoMadreSelect').style.display = 'none';
        createGroupFormData.lideresArray = [];
        renderNewGroupLideresUI();
        const liderAntiguo = document.getElementById('newGroupLider');
        if (liderAntiguo) {
            liderAntiguo.style.display = 'none';
            liderAntiguo.value = '';
            const liderLabel = liderAntiguo.closest('.edit-modal-section')?.querySelector('label');
            if (liderLabel) {
                liderLabel.textContent = 'Líderes del Grupo';
            }
        }
        const liderNuevo = document.getElementById('newGroupLiderInput');
        if (liderNuevo) {
            liderNuevo.value = '';
        }
        clearCreateGroupFormErrors();
        calculateTotalAsistencia();
    }

    function closeCreateGroupModal() {
        document.getElementById('createGroupModal').classList.remove('active');
        document.getElementById('createGroupForm').reset();
        document.getElementById('grupoMadreSelect').style.display = 'none';
        createGroupFormData.lideresArray = [];
        renderNewGroupLideresUI();
        const liderAntiguo = document.getElementById('newGroupLider');
        if (liderAntiguo) {
            liderAntiguo.value = '';
        }
        const liderNuevo = document.getElementById('newGroupLiderInput');
        if (liderNuevo) {
            liderNuevo.value = '';
        }
        clearCreateGroupFormErrors();
        calculateTotalAsistencia();
    }

    function toggleGrupoMadreSelect() {
        const tieneGrupoMadre = document.querySelector('input[name="tieneGrupoMadre"]:checked').value;
        const grupoMadreSelect = document.getElementById('grupoMadreSelect');
        const grupoMadreDropdown = document.getElementById('grupoMadreDropdown');

        if (tieneGrupoMadre === 'si') {
            grupoMadreSelect.style.display = 'block';
            grupoMadreDropdown.required = true;
        } else {
            grupoMadreSelect.style.display = 'none';
            grupoMadreDropdown.required = false;
            grupoMadreDropdown.value = '';
            // Establecer generación a 0 cuando no tiene grupo madre
            const generacionDefault = document.getElementById('newGroupGenDefault');
            if (generacionDefault) {
                generacionDefault.textContent = '0';
            }
            document.getElementById('generacionInfo').style.display = 'none';
        }
    }

    function loadAvailableGroupsForParent() {
        console.log('Cargando grupos disponibles para ser grupo madre');

        fetch('obtener_variantes_grupos_facilitador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idFacilitador: <?php echo $idFacilitador; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Grupos recibidos:', data);

            if (data.success && data.grupos) {
                // Filtrar grupos que NO sean generación 5
                const gruposValidos = data.grupos.filter(g => g.generacion < 5);

                const dropdown = document.getElementById('grupoMadreDropdown');
                dropdown.innerHTML = '<option value="">-- Seleccionar grupo madre --</option>';

                gruposValidos.forEach(grupo => {
                    const option = document.createElement('option');
                    option.value = grupo.id_unico;
                    const idGrupoMadre = obtenerIdReporteGrupo(grupo);
                    if (idGrupoMadre) {
                        option.dataset.idGrupoMadre = idGrupoMadre;
                    }
                    const grupoMadreInfo = grupo.grupo_madre ? ` (${grupo.grupo_madre})` : ' (Raíz)';
                    option.textContent = `${grupo.nombre_exacto} (Gen ${grupo.generacion})${grupoMadreInfo} - ${grupo.lider}`;
                    option.dataset.generacion = grupo.generacion;
                    dropdown.appendChild(option);
                });

                // Agregar event listener para actualizar generación
                dropdown.addEventListener('change', function() {
                    updateGeneracionDisplay();
                });
            }
        })
        .catch(error => console.error('Error al cargar grupos madre:', error));
    }

    function updateGeneracionDisplay() {
        const dropdown = document.getElementById('grupoMadreDropdown');
        const selectedOption = dropdown.options[dropdown.selectedIndex];
        const generacionInfo = document.getElementById('generacionInfo');
        const generacionDisplay = document.getElementById('generacionDisplay');
        const generacionDefault = document.getElementById('newGroupGenDefault');

        if (dropdown.value) {
            const generacionMadre = parseInt(selectedOption.dataset.generacion);
            const generacionNueva = generacionMadre + 1;
            generacionDisplay.textContent = generacionNueva;
            if (generacionDefault) {
                generacionDefault.textContent = generacionNueva;
            }
            generacionInfo.style.display = 'block';
        } else {
            generacionInfo.style.display = 'none';
            if (generacionDefault) {
                generacionDefault.textContent = '0';
            }
        }
    }

    function toggleEditGrupoMadreSelect() {
        const tieneGrupoMadre = document.querySelector('input[name="editTieneGrupoMadre"]:checked').value;
        const grupoMadreSelect = document.getElementById('editGrupoMadreSelect');
        const grupoMadreDropdown = document.getElementById('editGrupoMadreDropdown');

        if (tieneGrupoMadre === 'si') {
            grupoMadreSelect.style.display = 'block';
            grupoMadreDropdown.required = true;
            loadAvailableGroupsForEditModal(selectedGrupo.id_unico);
        } else {
            grupoMadreSelect.style.display = 'none';
            grupoMadreDropdown.required = false;
            grupoMadreDropdown.value = '';
            // Establecer generación a 0 cuando no tiene grupo madre
            document.getElementById('editGeneracion').value = '0';
            document.getElementById('editGeneracionInfo').style.display = 'none';
        }
    }

    function loadAvailableGroupsForEditModal(currentGroupId) {
        console.log('Cargando grupos disponibles para editar grupo madre, excluyendo:', currentGroupId);

        fetch('obtener_variantes_grupos_facilitador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idFacilitador: <?php echo $idFacilitador; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Grupos recibidos para edición:', data);

            if (data.success && data.grupos) {
                // Filtrar grupos que NO sean generación 5 y que NO sean el grupo actual
                const gruposValidos = data.grupos.filter(g =>
                    g.generacion < 5 && g.id_unico !== currentGroupId
                );

                const dropdown = document.getElementById('editGrupoMadreDropdown');
                dropdown.innerHTML = '<option value="">-- Seleccionar grupo madre --</option>';

                gruposValidos.forEach(grupo => {
                    const option = document.createElement('option');
                    option.value = grupo.id_unico;
                    const grupoMadreInfo = grupo.grupo_madre ? ` (${grupo.grupo_madre})` : ' (Raíz)';
                    option.textContent = `${grupo.nombre_exacto} (Gen ${grupo.generacion})${grupoMadreInfo} - ${grupo.lider}`;
                    option.dataset.generacion = grupo.generacion;
                    dropdown.appendChild(option);
                });

                // Agregar event listener para actualizar generación
                dropdown.addEventListener('change', function() {
                    updateEditGeneracionDisplay();
                });
            }
        })
        .catch(error => console.error('Error al cargar grupos madre para edición:', error));
    }

    function updateEditGeneracionDisplay() {
        const dropdown = document.getElementById('editGrupoMadreDropdown');
        const generacionInfo = document.getElementById('editGeneracionInfo');
        const generacionDisplay = document.getElementById('editGeneracionDisplay');
        const generacionInput = document.getElementById('editGeneracion');

        if (dropdown.value && dropdown.selectedIndex > 0) {
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            const generacionMadre = parseInt(selectedOption.dataset.generacion);
            const generacionNueva = generacionMadre + 1;
            generacionDisplay.textContent = generacionNueva;
            generacionInput.value = generacionNueva;
            generacionInfo.style.display = 'block';
        } else {
            generacionInput.value = '0';
            generacionInfo.style.display = 'none';
        }
    }

    function getTotalAsistenciaNuevoGrupo() {
        const hom = parseInt(document.getElementById('newGroupAsisHom').value) || 0;
        const muj = parseInt(document.getElementById('newGroupAsisMuj').value) || 0;
        const jov = parseInt(document.getElementById('newGroupAsisJov').value) || 0;
        const nin = parseInt(document.getElementById('newGroupAsisNin').value) || 0;

        return hom + muj + jov + nin;
    }

    function calculateTotalAsistencia() {
        const total = getTotalAsistenciaNuevoGrupo();
        document.getElementById('totalAsistenciaDisplay').textContent = total;

        if (total > 0) {
            clearAsistenciaError();
        }
    }

    function saveNewGroup(event) {
        event.preventDefault();
        console.log('Guardando nuevo grupo');
        clearCreateGroupFormErrors();

        // Datos del grupo
        const nombre = document.getElementById('newGroupName').value.trim();
        const descripcion = document.getElementById('newGroupDescription').value.trim();
        const ciudad = document.getElementById('newGroupCiudad').value.trim();
        const barrio = document.getElementById('newGroupBarrio').value.trim();
        const direccion = document.getElementById('newGroupDireccion').value.trim();
        const liderPendiente = normalizarNombreLider(document.getElementById('newGroupLiderInput')?.value || '');
        if (liderPendiente) {
            const errorLiderPendiente = validarNombreLider(liderPendiente);
            if (errorLiderPendiente) {
                showFormError('newGroupLiderInput', 'newGroupLiderError', errorLiderPendiente);
                document.getElementById('newGroupLiderInput').focus();
                return;
            }

            if (!createGroupFormData.lideresArray.includes(liderPendiente)) {
                createGroupFormData.lideresArray.push(liderPendiente);
            }
            document.getElementById('newGroupLiderInput').value = '';
            renderNewGroupLideresUI();
        }
        const lideres = createGroupFormData.lideresArray.slice();
        const tieneGrupoMadre = document.querySelector('input[name="tieneGrupoMadre"]:checked').value;
        const grupoMadreDropdown = document.getElementById('grupoMadreDropdown');
        const grupoMadreOption = grupoMadreDropdown.options[grupoMadreDropdown.selectedIndex];
        const grupoMadreHash = grupoMadreDropdown.value;
        const grupoMadreId = grupoMadreOption ? (grupoMadreOption.dataset.idGrupoMadre || grupoMadreHash) : '';

        // Datos del primer reporte
        const actividad = 'reunion_cotidiana'; // Siempre es Coach
        const fecha = document.getElementById('newGroupFecha').value;
        const asistencia_hom = parseInt(document.getElementById('newGroupAsisHom').value) || 0;
        const asistencia_muj = parseInt(document.getElementById('newGroupAsisMuj').value) || 0;
        const asistencia_jov = parseInt(document.getElementById('newGroupAsisJov').value) || 0;
        const asistencia_nin = parseInt(document.getElementById('newGroupAsisNin').value) || 0;
        const asistencia_total = asistencia_hom + asistencia_muj + asistencia_jov + asistencia_nin;

        const errorNombreGrupo = validarNombreGrupo(nombre);
        if (errorNombreGrupo) {
            showFormError('newGroupName', 'newGroupNameError', errorNombreGrupo);
            document.getElementById('newGroupName').focus();
            return;
        }

        if (!ciudad) {
            showFormError('newGroupCiudad', 'newGroupCiudadError', 'Debes ingresar la ciudad');
            document.getElementById('newGroupCiudad').focus();
            return;
        }

        if (!barrio) {
            showFormError('newGroupBarrio', 'newGroupBarrioError', 'Debes ingresar el barrio');
            document.getElementById('newGroupBarrio').focus();
            return;
        }

        if (!direccion) {
            showFormError('newGroupDireccion', 'newGroupDireccionError', 'Debes ingresar la dirección');
            document.getElementById('newGroupDireccion').focus();
            return;
        }

        if (lideres.length === 0) {
            showFormError('newGroupLiderInput', 'newGroupLiderError', 'Debes agregar al menos un líder');
            document.getElementById('newGroupLiderInput').focus();
            return;
        }

        if (tieneGrupoMadre === 'si' && !grupoMadreId) {
            showFormError('grupoMadreDropdown', 'grupoMadreError', 'Debes seleccionar un grupo madre');
            document.getElementById('grupoMadreDropdown').focus();
            return;
        }

        if (!fecha) {
            showFormError('newGroupFecha', 'newGroupFechaError', 'Debes seleccionar la fecha del primer encuentro');
            document.getElementById('newGroupFecha').focus();
            return;
        }

        if (asistencia_total < 1) {
            showAsistenciaError('La asistencia total debe ser mínimo 1');
            document.getElementById('newGroupAsisHom').focus();
            return;
        }

        const datosNuevoGrupo = {
            // Datos del grupo
            nombre: nombre,
            descripcion: descripcion,
            ciudad: ciudad,
            barrio: barrio,
            direccion: direccion,
            lideres: lideres,
            tieneGrupoMadre: tieneGrupoMadre,
            grupoMadreId: grupoMadreId,
            grupoMadreHash: grupoMadreHash,
            // Datos del primer reporte
            tipoActividad: actividad,
            fechaActividad: fecha,
            asistencia_hom: asistencia_hom,
            asistencia_muj: asistencia_muj,
            asistencia_jov: asistencia_jov,
            asistencia_nin: asistencia_nin
        };

        console.log('Datos del nuevo grupo:', datosNuevoGrupo);

        fetch('crear_grupo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datosNuevoGrupo)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text raw:', text);
            console.log('Response text length:', text.length);
            console.log('First 500 chars:', text.substring(0, 500));
            try {
                const data = JSON.parse(text);
                console.log('Respuesta de crear_grupo.php:', data);

                if (data.success) {
                    showStatusMessage('Grupo creado exitosamente', 'success');
                    closeCreateGroupModal();

                    // Recargar lista de grupos
                    setTimeout(() => {
                        loadGrupos();
                    }, 500);
                } else {
                    const mensajeError = data.message || 'No se pudo crear el grupo';
                    const mensajeNormalizado = mensajeError.toLowerCase();

                    if (mensajeNormalizado.includes('lider')) {
                        showFormError('newGroupLiderInput', 'newGroupLiderError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupLiderInput').focus();
                    } else if (mensajeNormalizado.includes('madre')) {
                        showFormError('grupoMadreDropdown', 'grupoMadreError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('grupoMadreDropdown').focus();
                    } else if (mensajeNormalizado.includes('ciudad')) {
                        showFormError('newGroupCiudad', 'newGroupCiudadError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupCiudad').focus();
                    } else if (mensajeNormalizado.includes('barrio')) {
                        showFormError('newGroupBarrio', 'newGroupBarrioError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupBarrio').focus();
                    } else if (mensajeNormalizado.includes('direcci') || mensajeNormalizado.includes('direccion')) {
                        showFormError('newGroupDireccion', 'newGroupDireccionError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupDireccion').focus();
                    } else if (mensajeNormalizado.includes('fecha')) {
                        showFormError('newGroupFecha', 'newGroupFechaError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupFecha').focus();
                    } else if (mensajeNormalizado.includes('grupo') || mensajeNormalizado.includes('nombre')) {
                        showFormError('newGroupName', 'newGroupNameError', mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupName').focus();
                    } else if (mensajeNormalizado.includes('asistencia')) {
                        showAsistenciaError(mensajeError.replace(/^Error:\s*/i, ''));
                        document.getElementById('newGroupAsisHom').focus();
                    } else {
                        showCreateGroupFormError(mensajeError.replace(/^Error:\s*/i, ''));
                    }
                }
            } catch (parseError) {
                console.error('Error al parsear JSON:', parseError);
                console.error('Respuesta cruda completa:', text);
                console.error('Empieza con:', text.charAt(0));
                showCreateGroupFormError('Error del servidor. Intenta guardar nuevamente.');
            }
        })
        .catch(error => {
            console.error('Error al crear grupo:', error);
            showCreateGroupFormError('Error al crear el grupo: ' + error.message);
        });
    }

    function saveGroupChanges(event) {
        event.preventDefault();
        clearInlineFormError('editFormError');

        if (!selectedGrupo) {
            showEditFormError('No hay grupo seleccionado');
            return;
        }

        const errorNombreGrupo = validarNombreGrupo(document.getElementById('editNombre').value || '');
        if (errorNombreGrupo) {
            showFormError('editNombre', 'editNombreError', errorNombreGrupo);
            document.getElementById('editNombre').focus();
            return;
        }
        clearFormError('editNombre', 'editNombreError');

        if (!Array.isArray(editFormData.lideresArray) || editFormData.lideresArray.length === 0) {
            showFormError('liderInput', 'liderInputError', 'Debe agregar al menos un lÃ­der capacitador');
            document.getElementById('liderInput').focus();
            return;
        }

        // Obtener el valor de grupo madre del selector
        const grupoMadreValue = selectedGrupo.grupo_madre || '';
        const idGrupo = obtenerIdGrupoSeleccionado(selectedGrupo);

        if (!idGrupo) {
            showEditFormError('No se pudo identificar el grupo base');
            return;
        }

        const datosActualizacion = {
            idGrupo: idGrupo,
            reporteIds: selectedGrupo.reportes_ids,
            nombre_exacto: document.getElementById('editNombre').value || null,
            ciudad: document.getElementById('editCiudad').value || null,
            barrio: document.getElementById('editBarrio').value || null,
            direccion: document.getElementById('editDireccion').value || null,
            grupo_madre: grupoMadreValue,
            generacion: parseInt(selectedGrupo.generacion, 10) || parseInt(document.getElementById('editGeneracion').value) || 0,
            lider: editFormData.lideresArray.length > 0 ? editFormData.lideresArray : []
        };

        // Mostrar estado de carga
        const btnSave = event.target.querySelector('.edit-modal-button.save');
        const textOriginal = btnSave.textContent;
        btnSave.textContent = 'Guardando...';
        btnSave.disabled = true;

        fetch('actualizar_grupo_consolidado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosActualizacion)
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            btnSave.textContent = textOriginal;
            btnSave.disabled = false;

            if (data.success) {
                showStatusMessage(`✅ Grupo actualizado correctamente. ${data.reportes_actualizados} reportes fueron actualizados.`, 'success');

                // Actualizar los datos del grupo seleccionado
                selectedGrupo.nombre_exacto = datosActualizacion.nombre_exacto || selectedGrupo.nombre_exacto;
                selectedGrupo.ciudad = datosActualizacion.ciudad || selectedGrupo.ciudad;
                selectedGrupo.barrio = datosActualizacion.barrio || selectedGrupo.barrio;
                selectedGrupo.direccion = datosActualizacion.direccion || selectedGrupo.direccion;
                selectedGrupo.grupo_madre = datosActualizacion.grupo_madre || selectedGrupo.grupo_madre;
                selectedGrupo.lider = Array.isArray(datosActualizacion.lider) && datosActualizacion.lider.length > 0
                    ? datosActualizacion.lider
                    : '';

                // Actualizar la vista
                updateGroupPanel(selectedGrupo);

                // Cerrar modal
                closeEditModal();
            } else {
                const mensajeError = data.message || 'No se pudo actualizar el grupo';
                if (mensajeError.toLowerCase().includes('grupo') || mensajeError.toLowerCase().includes('nombre')) {
                    showFormError('editNombre', 'editNombreError', mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('editNombre').focus();
                } else if (mensajeError.toLowerCase().includes('lider')) {
                    showFormError('liderInput', 'liderInputError', mensajeError.replace(/^Error:\s*/i, ''));
                    document.getElementById('liderInput').focus();
                } else {
                    showEditFormError(mensajeError.replace(/^Error:\s*/i, ''));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btnSave.textContent = textOriginal;
            btnSave.disabled = false;
            showEditFormError('Error de conexión al guardar cambios: ' + error.message);
        });
    }

    // ========== GRÁFICA DE MAPEO ==========
    const _mapeoImageCache = {};
    const _mapeoPositions = [
        { id: 'mapeo_evangelizar',   x: 430, y: 35  },
        { id: 'mapeo_biblia',        x: 200, y: 185 },
        { id: 'mapeo_cena',          x: 650, y: 185 },
        { id: 'mapeo_adoracion',     x: 50,  y: 355 },
        { id: 'mapeo_trabajadores',  x: 430, y: 355 },
        { id: 'mapeo_dar',           x: 800, y: 355 },
        { id: 'mapeo_companerismo',  x: 200, y: 520 },
        { id: 'mapeo_bautizar',      x: 650, y: 520 },
        { id: 'mapeo_oracion',       x: 430, y: 670 }
    ];

    function _loadMapeoImage(src) {
        return new Promise((resolve, reject) => {
            if (_mapeoImageCache[src]) { resolve(_mapeoImageCache[src]); return; }
            const img = new Image();
            img.onload = function() { _mapeoImageCache[src] = img; resolve(img); };
            img.onerror = reject;
            img.src = src;
        });
    }

    // Renderiza la gráfica de mapeo en un canvas dado, con los valores proporcionados
    async function renderMapeoOnCanvas(canvasId, valores) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const size = canvas.width;
        const scale = size / 1024;
        const iconSize = Math.round(150 * scale);
        const yInicial = Math.round(100 * scale);
        const ctx = canvas.getContext('2d');

        ctx.clearRect(0, 0, size, size);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);

        try {
            const bgImg = await _loadMapeoImage('mapeo_img/compromiso_si.png');
            ctx.drawImage(bgImg, 0, 0, size, size);
        } catch(e) {
            ctx.beginPath();
            ctx.arc(size/2, size/2, size/2 - 10, 0, Math.PI * 2);
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.stroke();
        }

        for (const field of _mapeoPositions) {
            const val = parseInt(valores[field.id]) || 0;
            if (val === 0) continue;
            const imgSrc = 'mapeo_img/' + field.id + val + '.png';
            try {
                const img = await _loadMapeoImage(imgSrc);
                const x = Math.round(field.x * scale);
                const y = Math.round(field.y * scale) + yInicial;
                ctx.drawImage(img, x, y, iconSize, iconSize);
            } catch(e) {
                console.warn('No se pudo cargar:', imgSrc);
            }
        }
    }

    // Renderiza la gráfica del formulario leyendo los selects
    function renderMapeoChart() {
        const valores = {};
        _mapeoPositions.forEach(function(f) {
            const sel = document.getElementById(f.id);
            valores[f.id] = sel ? parseInt(sel.value) || 0 : 0;
        });
        renderMapeoOnCanvas('mapeoCanvas', valores);
    }

    // Toggle de la gráfica de mapeo en reportes guardados
    function toggleMapeoChart(reporteId) {
        const chartDiv = document.getElementById('mapeo-chart-' + reporteId);
        if (!chartDiv) return;

        if (chartDiv.style.display === 'none') {
            chartDiv.style.display = 'block';
            const btnContainer = document.getElementById('mapeo-btn-' + reporteId);
            const datos = JSON.parse(btnContainer.dataset.mapeo || '{}');
            renderMapeoOnCanvas('mapeo-canvas-' + reporteId, datos);
            btnContainer.querySelector('button').textContent = '📊 Ocultar Mapeo';
        } else {
            chartDiv.style.display = 'none';
            const btnContainer = document.getElementById('mapeo-btn-' + reporteId);
            btnContainer.querySelector('button').textContent = '📊 Ver Mapeo';
        }
    }

    // Escuchar cambios en selects del formulario
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.mapeo-select').forEach(function(select) {
            select.addEventListener('change', function() {
                clearReportMapeosError();
                renderMapeoChart();
            });
        });
        const compromisoSelect = document.getElementById('mapeo_comprometido');
        if (compromisoSelect) {
            compromisoSelect.addEventListener('change', clearReportMapeosError);
        }
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.attributeName === 'style') {
                    const section = document.getElementById('mapeosSection');
                    if (section && section.style.display !== 'none') {
                        renderMapeoChart();
                    }
                }
            });
        });
        const mapeosEl = document.getElementById('mapeosSection');
        if (mapeosEl) {
            observer.observe(mapeosEl, { attributes: true });
        }
    });
</script>

<!-- Fin de grupos.php -->
