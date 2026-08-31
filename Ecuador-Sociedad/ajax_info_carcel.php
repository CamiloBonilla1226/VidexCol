<?php
/*
 * ajax_info_carcel.php
 * Endpoint JSON usado por reportar_facilitador.php: al seleccionar una
 * cárcel (tbl_regional_ubicacion) devuelve su dirección y departamento,
 * para mostrarlos en campos informativos de solo lectura.
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$respuesta = array("ok" => false);

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idCarcel = isset($_GET["id_carcel"]) ? intval($_GET["id_carcel"]) : 0;

if($idUsuarioSesion == 0){
    http_response_code(401);
    $respuesta["mensaje"] = "Debe iniciar sesión para continuar.";
    echo json_encode($respuesta);
    exit;
}

if($idCarcel <= 0){
    http_response_code(400);
    $respuesta["mensaje"] = "Cárcel no especificada.";
    echo json_encode($respuesta);
    exit;
}

$PSN1 = new DBbase_Sql;
$PSN1->connect();

/*
*   PENDIENTE POR IMPLEMENTAR: filtro de zona (usuario.tipo = 2 ve todas
*   las cárceles; cualquier otro solo las de su propia regional, comparando
*   tbl_regional_ubicacion.reub_reg_fk con usuario_empresa.empresa_pd). Ver
*   el comentario en reportar_facilitador.php donde se arma $listaCarceles:
*   queda pendiente por los 8 registros con reub_reg_fk huérfano que no
*   coinciden con ningún empresa_pd.
*/
$usuarioTipo = 0;
$PSN2 = new DBbase_Sql;
$PSN2->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $usuarioTipo = intval($PSN2->f("tipo"));
}

$empresaPd = 0;
$PSN3 = new DBbase_Sql;
$PSN3->query("SELECT empresa_pd FROM usuario_empresa WHERE idUsuario = ".$idUsuarioSesion." LIMIT 1");
if($PSN3->num_rows() > 0){
    $PSN3->next_record();
    $empresaPd = intval($PSN3->f("empresa_pd"));
}

/*
*   El departamento se obtiene vía el municipio (dane_municipios.departamento_id),
*   aunque el municipio en sí ya no se muestra en el formulario.
*/
$sql = "SELECT r.reub_id, r.reub_nom, r.reub_dir, d.departamento
        FROM tbl_regional_ubicacion r
        LEFT JOIN dane_municipios m ON m.id_municipio = r.reub_mun_fk
        LEFT JOIN dane_departamentos d ON d.id_departamento = m.departamento_id
        WHERE r.reub_id = ".$idCarcel;
/*
if($usuarioTipo != 2){
    $sql .= " AND r.reub_reg_fk = ".$empresaPd;
}
*/
$sql .= " LIMIT 1";

$PSN1->query($sql);

if($PSN1->num_rows() == 0){
    http_response_code(404);
    $respuesta["mensaje"] = "Cárcel no encontrada.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->next_record();

/*
*   Formato: "reub_dir - departamento", omitiendo con cuidado la parte que
*   venga vacía.
*/
$texto = trim($PSN1->f("reub_dir"));
if($PSN1->f("departamento") != ""){
    $texto .= ($texto != "" ? " - " : "").$PSN1->f("departamento");
}

$respuesta["ok"] = true;
$respuesta["reub_id"] = $idCarcel;
$respuesta["reub_nom"] = $PSN1->f("reub_nom");
$respuesta["reub_dir"] = $PSN1->f("reub_dir");
$respuesta["departamento"] = $PSN1->f("departamento");
$respuesta["texto"] = $texto;

echo json_encode($respuesta);
