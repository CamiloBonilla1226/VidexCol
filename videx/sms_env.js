//script js
//o
//
const grupoCache = new Map(); // Cache para almacenar los usuarios de cada grupo
const clientesCache = new Map(); // Cache para almacenar los usuarios de cada grupo
const gruposSeleccionados = new Set(); // Set para mantener un registro de los grupos seleccionados
const clientesSeleccionados = new Set(); // Set para mantener un registro de los grupos seleccionados
const usuariosUnicos = [];
let usuariosUnicosGrupo = []; // Usuarios obtenidos a partir de la selección de grupos
let usuariosUnicosClientes = []; // Usuarios obtenidos a partir de la selección de grupos
let usuariosBuscados = []; // Usuarios agregados manualmente desde el buscador
const usuariosDesmarcados = new Set();


document.getElementById("buscador").addEventListener("input", function () {
  const filtro = this.value.toLowerCase();
  const items = document.querySelectorAll("#contenedorGrupos .grupo-item");

  items.forEach((item) => {
    const texto = item.textContent.toLowerCase();
    if (texto.indexOf(filtro) > -1) {
      item.style.display = "";
    } else {
      item.style.display = "none";
    }
  });
});
document.getElementById("buscadorProspectos").addEventListener("input", function () {
  const filtro = this.value.toLowerCase();
  const items = document.querySelectorAll("#contenedorProspectos .grupo-item");

  items.forEach((item) => {
    const texto = item.textContent.toLowerCase();
    if (texto.indexOf(filtro) > -1) {
      item.style.display = "";
    } else {
      item.style.display = "none";
    }
  });
});
const checkboxes = document.querySelectorAll(
  '#contenedorGrupos input[type="checkbox"] '
);
const checkboxesClientes = document.querySelectorAll(
  '#contenedorProspectos input[type="checkbox"] '
)

checkboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", async function () {
    // Actualizar el conjunto de grupos seleccionados
    const grupoId = this.value;
    if (this.checked) {
      gruposSeleccionados.add(grupoId);
    } else {
      gruposSeleccionados.delete(grupoId);
      grupoCache.delete(grupoId); // Opcional: Eliminar de la caché si se deselecciona
    }

    // Obtener datos de grupos no cacheados
    const solicitudes = [];
    gruposSeleccionados.forEach((id) => {
      if (!grupoCache.has(id)) {
        solicitudes.push(fetchUsuariosGrupo(id));
      }
    });

    // Esperar a que todas las solicitudes se completen
    await Promise.all(solicitudes);

    // Unificar todos los usuarios de los grupos seleccionados
    const todosUsuarios = [];
    gruposSeleccionados.forEach((id) => {
      if (grupoCache.has(id)) {
        todosUsuarios.push(...grupoCache.get(id));
      }
    });

    // Eliminar duplicados usando un Set o por propiedad única (ej. id)
    usuariosUnicosGrupo = eliminarDuplicados(todosUsuarios);

    console.log("Lista consolidada de usuarios únicos:", usuariosUnicosGrupo);
    renderizarUsuarios();
  });
});

checkboxesClientes.forEach((checkbox) => {
  checkbox.addEventListener("change", async function () {
    // Actualizar el conjunto de grupos seleccionados
    const clienteId = this.value;
    if (this.checked) {
      clientesSeleccionados.add(clienteId);
    } else {
      clientesSeleccionados.delete(clienteId);
      clientesCache.delete(clienteId); // Opcional: Eliminar de la caché si se deselecciona
    }

    // Obtener datos de grupos no cacheados
    const solicitudes = [];
    clientesSeleccionados.forEach((id) => {
      if (!clientesCache.has(id)) {
        solicitudes.push(fetchUsuariosGrupo(null, id));
      }
    });

    // Esperar a que todas las solicitudes se completen
    await Promise.all(solicitudes);

    // Unificar todos los usuarios de los grupos seleccionados
    const todosUsuarios = [];
    clientesSeleccionados.forEach((id) => {
      if (clientesCache.has(id)) {
        todosUsuarios.push(...clientesCache.get(id));
      }
    });

    // Eliminar duplicados usando un Set o por propiedad única (ej. id)
    usuariosUnicosClientes = eliminarDuplicados(todosUsuarios);

    console.log("Lista consolidada de usuarios únicos:", usuariosUnicosClientes);
    renderizarUsuarios();
  });
});
// Función para obtener usuarios de un grupo y cachear el resultado
async function fetchUsuariosGrupo(grupoId = null, clienteId = null) {
  // const url = "http://localhost:8000/api/usuarios_grupos.php";
  const url = "https://videx.sms-app.co/api/usuarios_grupos.php";
  const formData = new FormData();
  formData.append("grupo", grupoId);
  formData.append("cliente", clienteId);

  try {
    const response = await fetch(url, { method: "POST", body: formData });
    if (!response.ok) throw new Error("Error en la respuesta");
    const data = await response.json();
    if (grupoId) {
      grupoCache.set(grupoId, data);
    } else if (clienteId) {
      clientesCache.set(clienteId, data);
    }
    return data;
  } catch (error) {
    console.error(`Error al obtener el grupo ${grupoId}:`, error);
    return [];
  }
}



// Función para eliminar usuarios duplicados (ej. por 'id')
function eliminarDuplicados(usuarios) {
  const unicos = new Map();
  usuarios.forEach((usuario) => {
    if (!unicos.has(usuario.id)) {
      unicos.set(usuario.id, usuario);
    }
  });
  return Array.from(unicos.values());
}
function renderizarTablaUsuarios(usuarios) {
  // Contenedor donde inyectaremos la tabla
  const contenedor = document.getElementById("contenedorUsuarios");

  // Construimos el HTML de la tabla
  let html = ``;

  usuarios.forEach((usuario, index) => {
    const isChecked = usuariosDesmarcados.has(String(usuario.id)) ? "" : "checked='checked'";
    html += `

<label data-id="${usuario.id}" class="contact-item">
  <div class="contact-row">
    <div class="contact-cell center">${index + 1}</div>
    <div class="contact-cell">${usuario.id}</div>
    <div class="contact-cell">${usuario.nombres}</div>
    <div class="contact-cell">${usuario.celular}</div>
    <div class="contact-cell center">
      <input 
        type="checkbox" 
        ${isChecked}
        class="desmarcar-checkbox" 
        data-id="${usuario.id}"
        title="Desmarcar contacto"
      />
    </div>
  </div>
</label>
        `;
  });

  html += ``;

  // Inyectamos el HTML al contenedor
  contenedor.innerHTML = html;

  // ----------------------------------------------------------------
  // AGREGAR EVENTOS PARA "DESMARCAR" A CADA CONTACTO
  // ----------------------------------------------------------------
  const desmarcarCheckboxes = document.querySelectorAll(".desmarcar-checkbox");
  desmarcarCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", (e) => {
      const idUsuario = e.target.dataset.id;
      if (!e.target.checked) {
        usuariosDesmarcados.add(idUsuario);
      } else {
        usuariosDesmarcados.delete(idUsuario);
      }

      if (!e.target.checked) {
        // Aquí decides la lógica de "desmarcar".
        // Ejemplo: simplemente remover la fila de la tabla.
        const fila = document.querySelector(`tr[data-id="${idUsuario}"]`);
        if (fila) {
          //fila.remove();
        }

        // También podrías eliminarlo de "usuariosUnicos" si mantienes
        // un estado interno, etc.
      }
    });
  });
}
// --------------------------------------------------
// Buscador de usuarios
// --------------------------------------------------
const buscadorInput = document.getElementById('buscadorUsuarios');
const resultadosBusquedaContainer = document.getElementById('resultadosBusqueda');

// (Opcional) función para evitar llamar a la API en cada tecla (debounce)
function debounce(func, delay) {
  let timer;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => func.apply(this, args), delay);
  }
}

buscadorInput.addEventListener('input', debounce(function() {
  const texto = this.value.trim();
  
  if (texto === '') {
    resultadosBusquedaContainer.innerHTML = '';
    return;
  }
  
  // Realizamos la consulta a la API (suponiendo que el endpoint espera un parámetro GET "texto")
  // Nota: Adapta la URL a tu endpoint real
  // fetch(`http://localhost:8000/api/buscar_usuarios.php?texto=${encodeURIComponent(texto)}`)
  fetch(`https://videx.sms-app.co/api/buscar_usuarios.php?texto=${encodeURIComponent(texto)}`)
    .then(response => response.json())
    .then(data => {
      // data debería ser un arreglo de objetos con { id, name }
      renderizarResultadosBusqueda(data);
      console.log(data)
    })
    .catch(err => console.error(err));
}, 300)); // 300ms de debounce

// Función para renderizar los resultados de búsqueda
function renderizarResultadosBusqueda(usuarios) {
  let html = '<ul>';
  usuarios.forEach(usuario => {
    // Buscamos si este usuario ya existe en usuariosUnicos

    const existeEnUnicos = usuariosBuscados.some(u => u.id == usuario.id);
    // Además, si está en el set de desmarcados, lo tratamos como "no agregado"
    const estaAgregado = existeEnUnicos && !usuariosDesmarcados.has(String(usuario.id));
    
    html += `<li data-id="${usuario.id}">`;
    html += `<span>${usuario.name}</span>`;
    
    if (estaAgregado) {
      // Si ya se agregó, mostramos un check y "Agregado"
      html += ` <span style="color: green;">&#10003; Agregado</span>`;
    } else {
      // Si no está agregado, mostramos el botón para agregar
      html += ` <button onclick="agregarUsuario('${usuario.id}', '${usuario.nombres}', '${usuario.celular}')">Agregar</button>`;
    }
    
    html += `</li>`;
  });
  html += '</ul>';
  resultadosBusquedaContainer.innerHTML = html;
}

// Función que se ejecuta al hacer click en "Agregar" de los resultados de búsqueda
function agregarUsuario(id, nombres, celular) {
  // Si el usuario no existe en usuariosUnicos, lo agregamos.
  if (!usuariosBuscados.some(u => u.id == id)) {
    // Suponiendo que "name" viene concatenado ("nombres - celular"),
    // separamos el nombre y el celular si lo necesitas
    // Aquí lo agregamos de forma sencilla:
    usuariosBuscados.push({ id: id, nombres: nombres, celular: celular });
  }
  
  // Aseguramos que el usuario se elimina de usuariosDesmarcados
  usuariosDesmarcados.delete(String(id));
  
  // Actualizamos la tabla de usuarios
  // renderizarTablaUsuarios(usuariosUnicos);
  renderizarUsuarios()
  
  // Actualizamos los resultados de búsqueda para reflejar el cambio
  // (Se puede re-ejecutar la búsqueda actual)
  const currentText = buscadorInput.value.trim();
  if (currentText !== "") {
    buscadorInput.dispatchEvent(new Event('input'));
  }
}

function renderizarUsuarios() {
  // Unir usuarios de grupos y usuarios agregados manualmente
  const unionUsuarios = eliminarDuplicados([...usuariosUnicosGrupo, ...usuariosBuscados, ...usuariosUnicosClientes]);
  renderizarTablaUsuarios(unionUsuarios);
}

// Función para unificar ambas listas y excluir desmarcados
function obtenerListaFinalDeContactos() {
  // 1. Unir ambas listas
  const union = eliminarDuplicados([...usuariosUnicosGrupo, ...usuariosBuscados, ...usuariosUnicosClientes]);

  // 2. Filtrar los que están en usuariosDesmarcados
  const listaFinal = union.filter(usuario => !usuariosDesmarcados.has(String(usuario.id)));

  return listaFinal;
}

// Escuchar el clic en el botón "Enviar Mensaje"
document.getElementById("btnEnviarMensaje").addEventListener("click", async function() {
  const mensaje = document.getElementById("mensajeParaEnviar").value.trim();
  const btnEnviar = this;

  // Validar que el mensaje no esté vacío
  if (!mensaje) {
    alert("Por favor ingresa un mensaje antes de enviar.");
    return;
  }

  // Obtener la lista de contactos finales (excluyendo desmarcados)
  const listaContactos = obtenerListaFinalDeContactos();

  // Si no hay contactos, podrías mostrar una alerta
  if (listaContactos.length === 0) {
    alert("No hay contactos para enviar el mensaje.");
    return;
  }
  // Desactivar el botón mientras se procesa la solicitud
  btnEnviar.disabled = true;
  btnEnviar.textContent = "Enviando...";

  // Construir el objeto que se enviará a la API
  const data = {
    mensaje: mensaje,
    contactos: listaContactos.map(c => ({
      id: c.id,
      celular: c.celular
    }))
  };

  try {
    // Llamada a la API (ejemplo con fetch y JSON)
    // const response = await fetch("http://localhost:8000/api/enviar_mensaje.php", {
    const response = await fetch("https://videx.sms-app.co/api/enviar_mensaje.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      credentials: "include"
    });

    // Procesar la respuesta de la API
    if (!response.ok) {
      throw new Error("Error en la respuesta del servidor");
    }

    const result = await response.json();
    console.log("Resultado de la API:", result);

    if (result.success) {
      alert("Mensaje enviado correctamente.");
      // Opcional: Limpiar el textarea
      document.getElementById("mensajeParaEnviar").value = "";
    } else {
      console.log(result)
      alert("Ocurrió un error al enviar el mensaje: " + (result.error || ""));
    }
  } catch (error) {
    console.error(error);
    alert("Error de red al enviar el mensaje.");
  }finally {
    // Reactivar el botón al finalizar la tarea
    btnEnviar.disabled = false;
    btnEnviar.textContent = "Enviar";
  }
  
});
