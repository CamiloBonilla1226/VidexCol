<?php
include_once('funciones.php');
$PSN = new DBbase_Sql;

// 1. Consulta de saldo actual de mensajes (se ejecuta al cargar la página o enviar SMS)
$sqlSaldoActual = "SELECT SUM(mensajes) as mensajes FROM sms_historico";
$PSN->connect();
$result = $PSN->query($sqlSaldoActual);



// Cerrar la conexión


// 3. Consulta de grupos para envío masivo (se ejecuta al mostrar el formulario)
$sqlGruposEnvio = "SELECT sms_grupos.* FROM sms_grupos ORDER BY nombre ASC";
$resultado = $PSN->query($sqlGruposEnvio);
if (!$resultado) {
  die("Error en la consulta: " . mysqli_error($conexion));
}



$sqlClientesProspectos = "SELECT cliente.id, cliente.nombre FROM cliente ";
if ($_SESSION["perfil"] == 162) {
  // Si el usuario es un comercial, se traen solo sus prospectos
  $sqlClientesProspectos .= "WHERE idComercial = '" . $_SESSION["id"] . "' ";
}
$sqlClientesProspectos .= "ORDER BY cliente.nombre ASC";

$prospectos = $PSN->query($sqlClientesProspectos);
if (!$prospectos) {
  die("Error en la consulta: " . mysqli_error($conexion));
}


?>
<style>
  /* Contenedor principal */
  .containerPanel {
    display: flex;
    width: 100%;
    height: calc(100vh-72px);
    background-color: #f4f7f9;
    /* Color de fondo general */
  }

  /* Panel izquierdo */
  .containerPanel .left-panel {
    flex: 0 0 30%;
    max-width: 30%;
    display: flex;
    flex-direction: column;
    border-right: 2px solid #e0e4e8;
    padding: 15px;
    background-color: #ffffff;
    border-radius: 15px 0 0 15px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    position: relative;
  }

  .containerPanel .left-panel .saldoActual {
    font-size: 1.5rem;
    /* Tamaño de letra */
    color: #333;
    /* Color de texto */
    margin-bottom: 10px;
    /* Separación inferior */
    padding: 10px;
    /* Espacio interno */
    background-color: #ffffff;
    /* Fondo blanco */
    border: 1px solid #e0e4e8;
    /* Borde sutil */
    border-radius: 5px;
    /* Esquinas redondeadas */
    box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1);
    /* Sombra ligera */
  }

  /* Panel derecho */
  .containerPanel .right-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 15px;
    background-color: #ffffff;
    border-radius: 0 15px 15px 0;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
    position: relative;
  }

  /* Área de mensaje */
  .containerPanel .message-field {
    height: 20vh;
    resize: none;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    background-color: #f9fafb;
    font-size: 1rem;
  }

  /* Campo de filtro */
  .containerPanel .filter-field {
    height: 40px;
    margin-bottom: 10px;
    padding: 10px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    background-color: #f9fafb;
    font-size: 1rem;
  }

  /* Lista de grupos */
  .containerPanel .group-list {
    height: 25vh;
    overflow-y: auto;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    padding: 10px;
    background-color: #f9fafb;
    margin-bottom: 1rem;
  }

  /* Elementos dentro de la lista */
  .containerPanel .group-list .group-item {
    width: 100%;
    padding: 10px;
    border-bottom: 1px solid #e0e4e8;
    white-space: nowrap;
    overflow: hidden;
    border-radius: 5px;
    background-color: #ffffff;
    transition: background 0.3s ease;
  }

  .containerPanel .group-list .group-item label {
    font-weight: normal !important;
  }

  .containerPanel .group-list .group-item:hover {
    background-color: #e9eff5;
  }

  /* Buscador */
  .containerPanel .search-box {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    background-color: #f9fafb;
    font-size: 1rem;
  }

  /* Desplegable */
  .containerPanel .dropdown {
    display: none;
    position: absolute;
    top: 100px;
    left: 15px;
    right: 15px;
    background: #ffffff;
    border: 1px solid #e0e4e8;
    padding: 10px;
    z-index: 1000;
    overflow-y: auto;
    max-height: 50vh;
    border-radius: 10px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
  }

  /* Aseguramos que la lista no tenga estilos por defecto */
  .containerPanel .dropdown #resultadosBusqueda ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  /* Estilos para cada elemento de contacto (li) */
  .containerPanel .dropdown #resultadosBusqueda ul li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    /* Separa el span del botón */
    padding: 10px 15px;
    margin: 5px 0;
    background-color: #ffffff;
    border: 1px solid #e0e4e8;
    border-radius: 5px;
    transition: background-color 0.3s ease;
    cursor: pointer;
  }

  /* Cambio de fondo al pasar el mouse */
  .containerPanel .dropdown #resultadosBusqueda ul li:hover {
    background-color: #e9eff5;
  }

  /* Estilos para el texto del contacto */
  .containerPanel .dropdown #resultadosBusqueda ul li span {
    font-size: 1.2rem;
    color: #333;
  }

  /* Estilos para el botón "Agregar" */
  .containerPanel .dropdown #resultadosBusqueda ul li button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  /* Cambio de fondo en el botón al pasar el mouse */
  .containerPanel .dropdown #resultadosBusqueda ul li button:hover {
    background-color: #0056b3;
  }


  .containerPanel .contact-list {
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    padding: 10px;
    flex-grow: 1;
    /* Permite que tome el espacio disponible sin expandirse */
    min-height: 0;
    /* Evita que se expanda innecesariamente */
    overflow: auto;
    /* Habilita el scroll si es necesario */
    background-color: #f9fafb;
    max-height: 80vh;
    /* Evita que crezca demasiado */
    overflow: auto;
    margin-bottom: .5rem;
  }

  /* Elementos de la lista de contactos */
  .containerPanel .contact-list .contact-item {
    display: block;
    width: 100%;
    padding: 10px;
    border-bottom: 1px solid #e0e4e8;
    background-color: #ffffff;
    border-radius: 5px;
    transition: background 0.3s ease;
  }

  .containerPanel .contact-list .contact-item:hover {
    background-color: #e9eff5;
  }

  /* Fila de contactos */
  .containerPanel .contact-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
  }

  /* Celdas dentro de la fila */
  .containerPanel .contact-cell {
    flex: 1;
    padding: 5px;
    min-width: 100px;
    text-align: left;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Centrar elementos */
  .containerPanel .center {
    text-align: center;
  }

  /* Responsivo */
  @media (max-width: 600px) {

    .containerPanel .contact-cell {
      text-align: center;
      min-width: auto;
    }

    .containerPanel .contact-cell:nth-child(-n+2) {
      display: none;
    }

    .containerPanel .contact-cell:nth-child(3) {
      flex: 1 1 0%;
      text-align: left;
    }

    .containerPanel .contact-cell:nth-child(4),
    .containerPanel .contact-cell:nth-child(5) {
      flex: none;
    }

  }

  /* Botón de enviar */
  .containerPanel .send-button {
    align-self: flex-end;
    padding: 10px 20px;
    cursor: pointer;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    transition: background 0.3s ease;
  }

  .containerPanel .send-button:hover {
    background-color: #0056b3;
  }

  /* Diseño responsive */
  @media (max-width: 768px) {
    .containerPanel {
      flex-direction: column;
    }

    .containerPanel .left-panel,
    .containerPanel .right-panel {
      flex: none;
      max-width: 100%;
      width: 100%;
    }

    .containerPanel .left-panel {
      border-right: none;
      border-bottom: 2px solid #e0e4e8;
    }
  }

  /* Inputs y textareas */
  .containerPanel input,
  .containerPanel textarea {
    border: 1px solid #e0e4e8;
    border-radius: 10px;
    padding: 10px;
    font-size: 1.5rem !important;
    background-color: #f9fafb;
    transition: border 0.3s ease;
  }

  .containerPanel input:focus,
  .containerPanel textarea:focus {
    border-color: #007bff;
    outline: none;
  }
</style>
<div class="containerPanel">
  <!-- PANEL IZQUIERDO -->
  <div class="left-panel">
    <div class="saldoActual">
      <?php
      if ($result) {
        $row = $result->fetch_assoc();
        $saldoActual = $row['mensajes'];
        echo "El saldo actual es: " . $saldoActual;
      } else {
        echo "Error en la consulta: " . $conn->error;
      }
      ?>
    </div>
    <input type="text" id="buscadorUsuarios" class="search-box" placeholder="Buscador de Contactos" />
    <div id="dropdown" class="dropdown">
      <div id="resultadosBusqueda"></div>
    </div>
    <input type="text" id="buscador" class="filter-field" placeholder="Filtrar grupos" />
    <div id="contenedorGrupos" class="group-list">
      <!-- Contenedor de checkboxes -->
      <?php while ($row = mysqli_fetch_assoc($resultado)) { ?>
        <div class="grupo-item">
          <div class="group-item">
            <input type="checkbox" value="<?php echo $row['id']; ?>">
            <?php echo htmlspecialchars($row['nombre']); ?>
          </div>
        </div>
      <?php } ?>
    </div>
    <input type="text" id="buscadorProspectos" class="filter-field" placeholder="Filtrar Prospectos" />
    <div id="contenedorProspectos" class="group-list">
      <!-- Contenedor de checkboxes -->
      <?php while ($row = mysqli_fetch_assoc($prospectos)) { ?>
        <div class="grupo-item">
          <div class="group-item">
            <input id="prospecto<?php echo $row['id'] ?>" type="checkbox" value="<?php echo $row['id']; ?>">
            <label for="prospecto<?php echo $row['id'] ?>">
              <?php
              echo htmlspecialchars($row['nombre']);
              //print_r( $row);
              ?>
            </label>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
  <!-- PANEL DERECHO -->
  <div class="right-panel">
    <textarea id="mensajeParaEnviar" class="message-field" placeholder="Escriba acá el mensaje"></textarea>
    <div>
      <p>Caracteres: <span id="contadorCaracteres">0</span>/160</p>
    </div>

    <script>
      document.getElementById("mensajeParaEnviar").addEventListener("input", function() {
        const maxCaracteres = 160;
        const contador = document.getElementById("contadorCaracteres");

        if (this.value.length > maxCaracteres) {
          this.value = this.value.substring(0, maxCaracteres); // Corta el texto si supera el límite
        }

        contador.textContent = this.value.length; // Actualiza el contador
      });
    </script>

    <div id="contenedorUsuarios" class="contact-list" id="contactList"></div>
  <button class="send-button" id="btnEnviarMensaje">Enviar</button>
</div>
</div>

<script>
  const searchBox = document.getElementById('buscadorUsuarios');
  const dropdown = document.getElementById('dropdown');

  // Muestra el desplegable mientras se escribe en el campo de búsqueda
  searchBox.addEventListener('input', function() {
    const value = searchBox.value.trim();
    if (value !== '') {
      dropdown.style.display = 'block';
      // dropdown.innerHTML = `Resultados para "${value}"`;
    } else {
      dropdown.style.display = 'none';
    }
  });

  // Oculta el desplegable al presionar Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
      dropdown.style.display = 'none';
    }
  });

  // Oculta el desplegable si se hace clic fuera de él y del campo de búsqueda
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && e.target !== searchBox) {
      dropdown.style.display = 'none';
    }
  });
</script>
<?php




// 5. Consulta de histórico de SMS (se ejecuta al ver el listado histórico)

?>
<script src="sms_env.js"></script>
