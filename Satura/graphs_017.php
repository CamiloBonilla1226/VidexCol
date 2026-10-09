<!DOCTYPE html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta charset="utf-8">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>
<style>
  #map {
    height: 85vh;
  }
  #box-info{
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
      
  }
  #p-info {
     font-size:1.3em;
     color : #192655;
  }
</style>


<body>
      <?php
    session_start();

    $PSN = new DBbase_Sql;

    $sql = "SELECT idMenu FROM usuarios_menu_graphs WHERE idMenu = 17 AND idUsuario = '" . $_SESSION["id"] . "'";
    $PSN->query($sql);

    if ($PSN->num_rows() == 0) {
        echo '<p>no hay usuario</p>';
    } else {
        $sql = "SELECT usuario.*, cliente.id as idCliente, cliente.nombre as nomcliente ,dane_municipios.*,dane_departamentos.* FROM usuario";
        $sql .= " LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id ";
        $sql .= "LEFT JOIN dane_municipios ON dane_municipios.id_municipio = usuario.usua_muni LEFT JOIN dane_departamentos ON dane_departamentos.id_departamento = dane_municipios.departamento_id";
        $sql .= " LEFT JOIN usuario as cliente ON cliente.id = usuario_relacion.idUsuario2 AND cliente.tipo = 3";
        // Solo usuarios activos (acceso = 1), misma lógica que usuario_buscar.php
        $sql .= " WHERE usuario.acceso = 1";
        $PSN->query($sql);
        if ($PSN->num_rows() == 0) {
            echo json_encode("sin resultado");
        } else {
            echo '<ol style="display:none;">';
            while ($PSN->next_record()) {
                $lat = $PSN->f('lat');
                $lon = $PSN->f('lon');
                $direccion = $PSN->f('direccion');
                $departamento = $PSN->f('departamento');
                $municipo = $PSN->f('municipio');
                $nombre = $PSN->f('nombre');

                echo '<li class="lat-lon">'. $lat . ',' . $lon . ',' .$nombre . ',' .$direccion . ',' .$departamento . ',' .$municipo . '</li>';
            }
            echo '</ol>';
        }
    }


    ?>
    


    <div id="box-info">
    <h1>¿Dónde estamos?</h1>
    <p id="p-info">Este es un mapa que recopila donde se encuentran los facilitadores de Satura</p>
  </div>
  <div id="map"></div>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
  
    
    let map = L.map("map").setView([4.570868, -74.297333], 6);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    
    var myIcon = L.icon({
    iconUrl: 'https://app.saturacolombia.org/img/maps-icon-s.png',
    iconSize: [38,58],
});
    

    
    const latLonUsuario = document.querySelectorAll('.lat-lon')
    latLonUsuario.forEach((ubi) => {
       let latLon = ubi.textContent.split(",")
        console.log(latLon)
        L.marker([Number(latLon[0]),Number(latLon[1])], {
                icon: myIcon
            }).addTo(map).bindPopup(`${latLon[2]}: ${latLon[3]}, ${latLon[4]} ${latLon[5]}`);
    })

 
    
  </script>

</body>

</html>