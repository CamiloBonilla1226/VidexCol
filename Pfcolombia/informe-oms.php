<?php
// Verificar sesión
if (!isset($_SESSION['id']) || $_SESSION['id'] == "" || $_SESSION['id'] == 0) {
  header('Location: index.php');
  exit;
}
?>

<style>
  .oms-container {
    max-width: 900px;
    margin: 20px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
  }

  .oms-header {
    background: #2c3e50;
    color: white;
    padding: 25px 30px;
    text-align: center;
  }

  .oms-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }

  .oms-info-box {
    background: #f8f9fa;
    border-left: 4px solid #3498db;
    padding: 15px 20px;
    margin: 20px 30px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.6;
  }

  .oms-guide-box {
    background: #fff;
    border: 2px solid #e9ecef;
    margin: 20px 30px;
    border-radius: 8px;
    overflow: hidden;
  }

  .oms-guide-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    color: #2c3e50;
  }

  .oms-guide-header i {
    color: #3498db;
    font-size: 20px;
  }

  .oms-guide-steps {
    margin: 0;
    padding: 20px 30px 20px 50px;
    counter-reset: step-counter;
  }

  .oms-guide-steps li {
    position: relative;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
    line-height: 1.8;
    color: #495057;
  }

  .oms-guide-steps li:last-child {
    border-bottom: none;
  }

  .oms-guide-steps li strong {
    color: #2c3e50;
    font-size: 14px;
  }

  .oms-guide-steps li br {
    margin-bottom: 8px;
  }

  .oms-guide-steps li .oms-download-link {
    margin-top: 8px;
  }

  .oms-form {
    padding: 30px;
  }

  .oms-form-group {
    margin-bottom: 25px;
  }

  .oms-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 13px;
  }

  .oms-label i {
    color: #3498db;
    margin-right: 5px;
  }

  .oms-input,
  .oms-select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
  }

  .oms-input:focus,
  .oms-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
  }

  .oms-row {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
  }

  .oms-col {
    flex: 1;
  }

  .oms-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
  }

  .oms-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .oms-btn-reset {
    background: #f1f3f5;
    color: #495057;
  }

  .oms-btn-reset:hover {
    background: #e9ecef;
  }

  .oms-btn-submit {
    background: #27ae60;
    color: white;
  }

  .oms-btn-submit:hover {
    background: #229954;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
  }

  .oms-info-section {
    background: #f8f9fa;
    padding: 20px 30px;
    margin-top: 20px;
    border-radius: 0 0 12px 12px;
  }

  .oms-info-title {
    color: #495057;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .oms-columns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
  }

  .oms-column-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .oms-column-list li {
    padding: 6px 0;
    font-size: 12px;
    color: #6c757d;
    border-bottom: 1px solid #e9ecef;
  }

  .oms-column-list li:last-child {
    border-bottom: none;
  }

  .oms-download-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #3498db;
    color: white !important;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
  }

  .oms-download-link:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    color: white !important;
    text-decoration: none;
  }

  @media (max-width: 768px) {
    .oms-row {
      flex-direction: column;
      gap: 0;
    }

    .oms-container {
      margin: 10px;
      border-radius: 8px;
    }

    .oms-form {
      padding: 20px;
    }

    .oms-buttons {
      flex-direction: column;
    }

    .oms-btn {
      width: 100%;
      justify-content: center;
    }

    .oms-guide-box {
      margin: 10px;
    }

    .oms-guide-steps {
      padding: 15px 20px 15px 35px;
    }

    .oms-download-link {
      display: flex;
      justify-content: center;
      width: 100%;
    }
  }
</style>

<div class="oms-container">
  <div class="oms-header">
    <h2>
      <i class="fas fa-file-excel"></i>
      Exportar Reporte OMS
    </h2>
  </div>

  <div class="oms-guide-box">
    <div class="oms-guide-header">
      <i class="fas fa-graduation-cap"></i>
      <strong>Guía de Uso</strong>
    </div>
    <ol class="oms-guide-steps">
      <li>
        <strong>Descargar la plantilla oficial RMI20</strong>
        <br>
        <a href="plantilla-rmi20.xlsm" download class="oms-download-link">
          <i class="fas fa-download"></i> Descargar Plantilla RMI20
        </a>
      </li>
      <li>
        <strong>Generar el reporte con tus datos</strong>
        <br>
        Selecciona el rango de fechas y haz clic en "Generar Excel"
      </li>
      <li>
        <strong>Copiar y pegar los datos</strong>
        <br>
        Abre el archivo descargado, copia todos los datos y pégalos en la <strong>primera fila</strong> de la plantilla
      </li>
      <li>
        <strong>Completa los datos del reporte de la hoja "spec"</strong>
        <br>
        En la plantilla, cambia los datos de la hoja "spec" según tu reporte
      </li>
      <li>
        <strong>Guardar y enviar</strong>
        <br>
        Guarda la plantilla con los datos actualizados y envíala a OMS
      </li>
    </ol>
  </div>

  <form id="formExportOMS" method="GET" action="generaExcel-rmi20.php" target="_blank" class="oms-form">
    <div class="oms-row">
      <div class="oms-col">
        <div class="oms-form-group">
          <label for="fechaInicial" class="oms-label">
            <i class="fas fa-calendar-alt"></i> Fecha de Inicio Desde
          </label>
          <input type="date"
            class="oms-input"
            id="fechaInicial"
            name="fechaInicial"
            value="<?php echo date('Y-m-01'); ?>"
            required>
        </div>
      </div>

      <div class="oms-col">
        <div class="oms-form-group">
          <label for="fechaFinal" class="oms-label">
            <i class="fas fa-calendar-alt"></i> Fecha de Inicio Hasta
          </label>
          <input type="date"
            class="oms-input"
            id="fechaFinal"
            name="fechaFinal"
            value="<?php echo date('Y-m-d'); ?>"
            required>
        </div>
      </div>
    </div>

    <input type="hidden" name="limit" value="10000">

    <div class="oms-buttons">
      <button type="reset" class="oms-btn oms-btn-reset">
        <i class="fas fa-eraser"></i> Limpiar
      </button>
      <button type="submit" class="oms-btn oms-btn-submit">
        <i class="fas fa-file-excel"></i> Generar Excel
      </button>
    </div>
  </form>

  <div class="oms-info-section">
    <div class="oms-info-title">
      <i class="fas fa-table"></i> Columnas del Reporte RMI20
    </div>
    <div class="oms-columns-grid">
      <div class="oms-column-list">
        <li>1. Equipo</li>
        <li>2. Nombre del Líder</li>
        <li>3. Nombre del Grupo</li>
        <li>4. Fecha de Inicio</li>
        <li>5. Generación</li>
        <li>6. Ubicación del Grupo</li>
        <li>7. Grupo Madre</li>
        <li>8. Asistencia del Grupo</li>
        <li>9. Total de Creyentes</li>
        <li>10. Nuevos Creyentes</li>
        <li>11. Total de Bautizados</li>
        <li>12. Nuevos Bautizados</li>
        <li>13. Oración</li>
      </div>
      <div class="oms-column-list">
        <li>14. Compañerismo</li>
        <li>15. Adoración</li>
        <li>16. Aplicar la Biblia</li>
        <li>17. Evangelismo</li>
        <li>18. Santa Cena</li>
        <li>19. Dar</li>
        <li>20. Bautismo</li>
        <li>21. Trabajadores</li>
        <li>22-25. Campos 1-4</li>
        <li>26. Informe</li>
        <li>27. Ubicación del Entrenador</li>
        <li>28. Coordinador</li>
        <li>29. Identificación</li>
      </div>
      <div class="oms-column-list">
        <li>30. Es Iglesia</li>
        <li>31. Suma de Salud</li>
        <li>32. Promedio de Salud</li>
        <li>33. Desde</li>
        <li>34. Hasta</li>
        <li>35. Reunido</li>
        <li>36. Socio de Ministerio</li>
        <li>37. Denominación</li>
        <li>38. Latitud</li>
        <li>39. Longitud</li>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('formExportOMS').addEventListener('submit', function(e) {
    var fechaInicial = document.getElementById('fechaInicial').value;
    var fechaFinal = document.getElementById('fechaFinal').value;

    if (fechaInicial > fechaFinal) {
      e.preventDefault();
      alert('La fecha inicial no puede ser mayor que la fecha final');
      return false;
    }
  });
</script>
