<?php

/*

*	$PSN = new DBbase_Sql;

*/

// Objeto de Base de Datos

$PSN1 = new DBbase_Sql;

$PSN2 = new DBbase_Sql;

$PSN_Rep = new DBbase_Sql;
$PSN_Save01  = new DBbase_Sql;

$MyFiltro = " where B.Estado = 1";


// AG

if(isset($_REQUEST["DelInv"])){
    $DelInv = $_REQUEST["DelInv"];
    $sql01  = "update inventario set Estado = 0";
    $sql01 .=" Where IdInventario = ".$DelInv;
    $PSN_Save01->query($sql01); 
    
}
$fecha_actual = date("Y-m-d");
$FechaFin = date("Y-m-d");
$FechaIni = date("Y-m-d",strtotime($fecha_actual."- 6 month")); 

if(isset($_REQUEST["Facilitador"])){
    $Facilitador = $_REQUEST["Facilitador"];
    if ($Facilitador != 0){
        $MyFiltro .= " and C.Id = ".$Facilitador;
    }
    
}

if(isset($_REQUEST["pais"])){
    $pais = $_REQUEST["pais"];
    if ($pais != 0){
        $MyFiltro .= " and B.pais = ".$pais;
    }
}

if(isset($_REQUEST["departamento"])){
    $departamento = $_REQUEST["departamento"];
    if ($departamento != 0){
        $MyFiltro .= " and B.departamento = ".$departamento;
    }
}
else{
    $departamento = 0;
}

if(isset($_REQUEST["Depto"])){
    $Depto = $_REQUEST["Depto"];
}
else{
    $Depto = "...";
}

if(isset($_REQUEST["FechaIni"]) && eliminarInvalidos($_REQUEST["FechaIni"]) != ""){
    $FechaIni = eliminarInvalidos($_REQUEST["FechaIni"]);
    $MyFiltro .= " AND B.Fecha >= '".$FechaIni."'";
}

if(isset($_REQUEST["FechaFin"]) && eliminarInvalidos($_REQUEST["FechaFin"]) != ""){
    $FechaFin = eliminarInvalidos($_REQUEST["FechaFin"]);
    $MyFiltro .= " AND B.Fecha <= '".$FechaFin."'";
}

//

if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){

    $_REQUEST["fechaInicial"] = date("2021-02-01");

}

if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){

    $siguiente_anho = date("Y", strtotime("+1 year"));

    //$_REQUEST["fechaFinal"] = $siguiente_anho."-01-31";

    $_REQUEST["fechaFinal"] = date("Y-m-d");

}

$sqlFiltro = "";
$buscar_idUsuario = "";
$buscar_idGrupoMadre = "";
$buscar_nombre = "";
$fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
$fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
$generacionNumero = "";
$inactivo = "";
$actividadFiltro = "";
$esFacilitador = (isset($_SESSION["perfil"]) && $_SESSION["perfil"] == 163);
$sqlJoinGrupoBase = " LEFT JOIN sat_reportes AS grupo_base ON grupo_base.id = sat_reportes.id_grupo ";
$sqlFiltro .= " AND COALESCE(sat_reportes.id_grupo, 0) <> 0";

if (!function_exists('reportar_buscar_actividad_label')) {
    function reportar_buscar_actividad_label($idActividad, $generacionNumero)
    {
        $idActividad = (int)$idActividad;
        $generacionNumero = (int)$generacionNumero;

        if ($idActividad == 77) {
            return "Evangelismo";
        }
        if ($idActividad == 99) {
            return "Bautizo";
        }
        if ($idActividad == 8) {
            return "Gran celebracion";
        }
        if ($idActividad == 1) {
            return "Coach";
        }
        if ($generacionNumero == 77) {
            return "Evangelismo";
        }
        if ($generacionNumero == 8) {
            return "Gran celebracion";
        }

        return "Coach";
    }
}

/*

*   GENERAR EXCEL

*/

if(isset($_REQUEST["excelX"])){

    die("En proceso");

    

    if(isset($_REQUEST["tipo"]) && soloNumeros($_REQUEST["tipo"]) != ""){

        $buscar_tipo = soloNumeros($_REQUEST["tipo"]);

        $sqlFiltro .= " AND usuario.tipo = '".$buscar_tipo."'";

    }



    if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){

        $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);

        $sqlFiltro .= " AND usuario.nombre LIKE '%".$buscar_nombre."%'";

    }



    if(isset($_REQUEST["identificacion"]) && eliminarInvalidos($_REQUEST["identificacion"]) != ""){

        $buscar_identificacion = eliminarInvalidos($_REQUEST["identificacion"]);

        $sqlFiltro .= " AND usuario.identificacion LIKE '%".$buscar_identificacion."%'";

    } 



    if(isset($_REQUEST["cliente"]) && eliminarInvalidos($_REQUEST["cliente"]) != ""){

        $buscar_cliente = eliminarInvalidos($_REQUEST["cliente"]);

        $sqlFiltro .= " AND cliente.id = '".$buscar_cliente."'";

    } 

    

    

    

    

    //

    $sqlFiltro .= " AND usuario.tipo IN (".$temp_tiposUsuario.")";

    //

    $sql = "SELECT usuario.*, categorias.descripcion,  categorias.descripcion as nomTipoID, cliente.nombre as nomcliente ";

    $sql.=" FROM usuario ";

    $sql.=" LEFT JOIN categorias ";

    $sql.=" ON categorias.id = usuario.tipo";

    $sql.=" LEFT JOIN categorias AS tipoID";

    $sql.=" ON tipoID.id = usuario.tipoIdentificacion";

    $sql.=" LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id ";

    $sql.=" LEFT JOIN usuario as cliente ON cliente.id = usuario_relacion.idUsuario2 AND cliente.tipo = 3";

    //    

    $sql.=" WHERE usuario.id != 2 ".$sqlFiltro." GROUP BY usuario.id ORDER BY usuario.tipo ASC, usuario.nombre ASC";

    //

    $PSN1->query($sql);

    $numero=$PSN1->num_rows();

    ?><strong><?php echo $numero; ?> REGISTROS DE USUARIOS DEL TIPO: <?=$temp_letrero; ?>.</strong>

    <table border="1">

    <thead>

        <tr> 

        <th>Id</th>

        <th>Tipo de usuario</th>

        <th>Acceso al sistema</th>

        <?php

        if($ctrl == "" || $ctrl == 4){

            ?><th>Autorizado del cliente:</th><?php

        }

        ?>

        <th>Nombre</th>

        <th>Identificación</th>

        <th>Tipo de identificación</th>

        <th>Direccion</th>

        <th>Teléfono 1</th>

        <th>Teléfono 2</th>

        <th>celular</th>

        <th>Celular 2</th>

        <th>Email</th>

        <th>Pagina Web</th>

        <th>Observaciones</th>

        <?php

        if($ctrl == "" || $ctrl == 2 || $ctrl == 3){

            ?><th>Empresa - Tipo de empresa</th>

            <th>Empresa - Representante legal</th>

            <th>Empresa - Nombre contacto</th>

            <th>Empresa - Dirección</th>

            <th>Empresa - Página Web</th><?php

        }

    



        if($ctrl == "" || $ctrl == 1 || $ctrl == 4){

            ?><th>Empresa - Cargo</th>

            <th>Empresa - Teléfono 1</th>

            <th>Empresa - Teléfono 2</th>

            <th>Empresa - Celular 1</th>

            <th>Empresa - Celular 2</th>

            <th>Empresa - Email 1</th>

            <th>Empresa - Email 2</th>

            <?php

        }

    

        if($ctrl == "" || $ctrl == 3){

             ?><th>Proveedor - Tipo de persona</th>

            <th>Proveedor - Tipo de servicio</th>

            <th>Proveedor - Tipo de servicio 2</th>

            <th>Proveedor - Tipo de contrato</th>

            <th>Proveedor - Tipo de contrato 2</th>

            <th>Proveedor - Ampliación de los servicios prestados</th>

            <th>Proveedor - Fecha de inicio VIGENCIA</th>

            <th>Proveedor - Fecha final VIGENCIA</th>

            <th>Proveedor - Porcentaje de descuento</th>

            

            <?php

        }

    

        //Pestaña cliente

        if($ctrl == "" || $ctrl == 2){

            ?><th>Cliente - Tipo de persona</th>

            <th>Cliente - Tipo de servicio</th>

            <th>Cliente - Tipo de contrato:</th>

            <th>Cliente - Ampliación de los servicios ofrecidos</th>

            <th>Cliente - Valor del contrato</th>

            <th>Cliente - Día de pago</th>

            <th>Cliente - Fecha de aprobación cliente:</th>

            <th>Cliente - Fecha aprobación contrato</th>

            <th>Cliente - Fecha de inicio contrato</th>

            <th>Cliente - Fecha final contrato</th><?php

        }

    

        ?>

        </tr>

    </thead>

    <tbody>

        <?php

        if($numero > 0)

        {

            $contador = 0;

            while($PSN1->next_record())

            {

                //Solo si no se ha modificado ya el formulario.

                $id = $PSN1->f('id');

                $idUsuarioActual = $id;

                $nomcliente = $PSN1->f('nomcliente');

                $tipodesc = $PSN1->f('descripcion');

                $nombre = $PSN1->f('nombre');

                $telefono1 = $PSN1->f('telefono1');

                $celular = $PSN1->f('celular');

                $email = $PSN1->f('email');

                $temp_acceso = $PSN1->f('acceso');

                

                /*

                *	TRAEMOS LOS DATOS EMPRESARIALES

                */

                $sql = "SELECT usuario_empresa.*, categorias.descripcion ";

                $sql.=" FROM usuario_empresa LEFT JOIN categorias ON categorias.id = usuario_empresa.empresa_tipo ";

                $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";

                $PSN2->query($sql);

                if($PSN2->num_rows() > 0)

                {

                    if($PSN2->next_record())

                    {

                        $empresa_tipo = $PSN2->f("descripcion");

                        $empresa_nombre = $PSN2->f("empresa_nombre");

                        $empresa_nit = $PSN2->f("empresa_nit");

                        $empresa_representante = $PSN2->f("empresa_representante");

                        $empresa_contacto = $PSN2->f("empresa_contacto");

                        $empresa_direccion = $PSN2->f("empresa_direccion");

                        $empresa_url = $PSN2->f("empresa_url");

                        $empresa_telefono1 = $PSN2->f("empresa_telefono1");

                        $empresa_telefono2 = $PSN2->f("empresa_telefono2");

                        $empresa_celular1 = $PSN2->f("empresa_celular1");

                        $empresa_celular2 = $PSN2->f("empresa_celular2");

                        $empresa_email1 = $PSN2->f("empresa_email1");

                        $empresa_email2 = $PSN2->f("empresa_email2");

                        $empresa_cargo = $PSN2->f("empresa_cargo");

                    }

                }



                /*

                *	TRAEMOS LOS DATOS DE PROVEEDOR

                */

                $sql = "SELECT usuario_servicios.*, categorias.descripcion, cat_contrato1.descripcion as nomcontrato1, cat_contrato2.descripcion as nomcontrato2, cat_servicios1.descripcion as nomservicios1, cat_servicios2.descripcion as nomservicios2 ";

                $sql.=" FROM usuario_servicios 

                        LEFT JOIN categorias ON categorias.id = usuario_servicios.servicios_tipoPersona 

                        LEFT JOIN categorias as cat_contrato1 ON cat_contrato1.id = usuario_servicios.servicios_contrato1 

                        LEFT JOIN categorias as cat_contrato2 ON cat_contrato2.id = usuario_servicios.servicios_contrato2 

                        LEFT JOIN categorias as cat_servicios1 ON cat_servicios1.id = usuario_servicios.servicios_tipo1 

                        LEFT JOIN categorias as cat_servicios2 ON cat_servicios2.id = usuario_servicios.servicios_tipo2

                ";

                $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";

                $PSN2->query($sql);

                if($PSN2->num_rows() > 0)

                {

                    if($PSN2->next_record())

                    {

                        $servicios_tipo1 = $PSN2->f("nomservicios1");

                        $servicios_tipo2 = $PSN2->f("nomservicios2");

                        $servicios_contrato1 = $PSN2->f("nomcontrato1");

                        $servicios_contrato2 = $PSN2->f("nomcontrato2");

                        $servicios_observaciones = $PSN2->f("servicios_observaciones");

                        $servicios_fechaInicio = $PSN2->f("servicios_fechaInicio");

                        $servicios_fechaFin = $PSN2->f("servicios_fechaFin");

                        $servicios_tipoPersona = $PSN2->f("descripcion");

                        $servicios_porcentaje = $PSN2->f("servicios_porcentaje");

                        

                    }

                }



                /*

                *	TRAEMOS LOS DATOS DE CLIENTE

                */

                $sql = "SELECT usuario_cliente.*, categorias.descripcion, cat_tipo1.descripcion as nomtipo1, cat_servicio1.descripcion as nomcontrato1 ";

                $sql.=" FROM usuario_cliente 

                        LEFT JOIN categorias ON categorias.id = usuario_cliente.cliente_tipoPersona 

                        LEFT JOIN categorias as cat_tipo1 ON cat_tipo1.id = usuario_cliente.cliente_tipo1 

                        LEFT JOIN categorias as cat_servicio1 ON cat_servicio1.id = usuario_cliente.cliente_servicio1 

                ";

                $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";

                $PSN2->query($sql);

                if($PSN2->num_rows() > 0)

                {

                    if($PSN2->next_record())

                    {
                        $cliente_tipoPersona = $PSN2->f("descripcion");
                        $cliente_tipo1 = $PSN2->f("nomtipo1");
                        $cliente_servicio1 = $PSN2->f("nomcontrato1");
                        $cliente_observaciones = $PSN2->f("cliente_observaciones");
                        $cliente_valor1 = $PSN2->f("cliente_valor1");
                        $cliente_diaPago = $PSN2->f("cliente_diaPago");
                        $cliente_fechaAprob = $PSN2->f("cliente_fechaAprob");
                        $cliente_fechaAprobCont = $PSN2->f("cliente_fechaAprobCont");
                        $cliente_fechaInicial = $PSN2->f("cliente_fechaInicial");
                        $cliente_fechaFinal = $PSN2->f("cliente_fechaFinal");
                    }
                }     

                ?><tr <?php if($contador%2==0){ ?>bgcolor="#EEEEEE"<?php } ?>>
                    <td><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></td>
                    <td><?=$PSN1->f("tipo"); ?></td>
                    <td><?php if($temp_acceso == 1){
                        echo "Si";
                    }else{
                        echo "No";
                    } ?></td>
                    <?php
                    if($ctrl == "" || $ctrl == 4){
                        ?><td><?=utf8_decode($PSN1->f("nomcliente")); ?></td><?php
                    }
                    ?>
                    <td><?=utf8_decode($PSN1->f("nombre")); ?></td>
                    <td><?=$PSN1->f("identificacion"); ?></td>
                    <td><?=$PSN1->f("nomTipoID"); ?></td>
                    <td><?=utf8_decode($PSN1->f("direccion")); ?></td>
                    <td><?=$PSN1->f("telefono1"); ?></td>
                    <td><?=$PSN1->f("telefono2"); ?></td>
                    <td><?=$PSN1->f("celular"); ?></td>
                    <td><?=$PSN1->f("celular2"); ?></td>
                    <td><?=$PSN1->f("email"); ?></td>
                    <td><?=$PSN1->f("url"); ?></td>
                    <td><?=utf8_decode($PSN1->f("observaciones")); ?></td>

                    <?php
                    //Pestaña empresarial
                    if($ctrl == "" || $ctrl == 2 || $ctrl == 3){
                        ?><td><?=utf8_decode($empresa_tipo); ?></td>
                        <td><?=utf8_decode($empresa_representante); ?></td>
                        <td><?=utf8_decode($empresa_contacto); ?></td>
                        <td><?=utf8_decode($empresa_direccion); ?></td>
                        <td><?=$empresa_url; ?></td><?php
                    }

                    //Pestaña empresarial

                    if($ctrl == "" || $ctrl == 1 || $ctrl == 4){
                        ?><td><?=$empresa_cargo; ?></td>
                        <td><?=$empresa_telefono1; ?></td>
                        <td><?=$empresa_telefono2; ?></td>
                        <td><?=$empresa_celular1; ?></td>
                        <td><?=$empresa_celular2; ?></td>
                        <td><?=$empresa_email1; ?></td>
                        <td><?=$empresa_email2; ?></td><?php
                    }

        
                    //Pestaña proveedor

                    if($ctrl == "" || $ctrl == 3){
                         ?><td><?=utf8_decode($servicios_tipoPersona); ?></td>
                        <td><?=utf8_decode($servicios_tipo1); ?></td>
                        <td><?=utf8_decode($servicios_tipo2); ?></td>
                        <td><?=utf8_decode($servicios_contrato1); ?></td>
                        <td><?=utf8_decode($servicios_contrato2); ?></td>
                        <td><?=utf8_decode($servicios_observaciones); ?></td>
                        <td><?=$servicios_fechaInicio; ?></td>
                        <td><?=$servicios_fechaFin; ?></td>
                        <td><?=$servicios_porcentaje; ?></td><?php
                    }       

                    //Pestaña cliente
                    if($ctrl == "" || $ctrl == 2){
                        ?><td><?=utf8_decode($cliente_tipoPersona); ?></td>
                        <td><?=utf8_decode($cliente_tipo1); ?></td>
                        <td><?=utf8_decode($cliente_servicio1); ?></td>
                        <td><?=utf8_decode($cliente_observaciones); ?></td>
                        <td><?=$cliente_valor1; ?></td>
                        <td><?=$cliente_diaPago; ?></td>
                        <td><?=$cliente_fechaAprob; ?></td>
                        <td><?=$cliente_fechaAprobCont; ?></td>
                        <td><?=$cliente_fechaInicial; ?></td>
                        <td><?=$cliente_fechaFinal; ?></td><?php
                    }
                    ?>       
                </tr>

                <?php
                $contador++;
            }
        }
        else{
            echo "Sin registros";

        }

        ?>

    </tbody>

    </table><?php

}

else{

    // AG
    $TotalReg = 0;
    $sqlRep = "SELECT 
        IFNULL(A.idBeneficiado, 0) idBeneficiado, 
        CASE B.Tipo 
            WHEN 1 THEN 'Entrada' 
            WHEN 3 THEN 'Salida  Evangelismo' 
            ELSE 'Salida' 
        END AS TipoRegistro, 
        B.Responsable, 
        A.Nombre,
        REPLACE(CONCAT(IF(SUM(B.Donante1) > 0, 'Satura Colombia', '/'), ' / ', IF(SUM(B.Donante2) > 0, 'Otros', '/')), '/ /', '') AS Donante,
        REPLACE(CONCAT(IF(SUM(B.TipoSopa1) > 0, 'Mix de vegetales 1 lb', '/'), ' / ', IF(SUM(B.TipoSopa2) > 0, 'Mix de vegetales 3 lb', '/')), '/ /', '') AS TipoSopa,
        (SUM(B.TipoSopa1) + SUM(B.TipoSopa2)) AS CantidadEntregada,
        MIN(B.fecha) AS fechaMin,
        MAX(B.fecha) AS fechaMax
    FROM 
        beneficiarios A
    RIGHT JOIN 
        inventario B ON A.idBeneficiado = B.Beneficiario
    INNER JOIN 
        usuario C ON B.responsable = C.Nombre";
    $sqlRep .= $MyFiltro;
    $sqlRep .=" GROUP BY 
        B.responsable, 
        A.Nombre, 
        TipoRegistro";
    $PSN_Rep->query($sqlRep);

    if($PSN_Rep->num_rows() > 0)
    {
        while($PSN_Rep->next_record())
        {
            $TotalReg += 1; 
        }
    }

    //

    $registros = 50;

    $pagina = soloNumeros($_GET["pagina"]);

    if (!$pagina) { 
        $inicio = 0; 
        $pagina = 1; 
    } 
    else
    { 
        $inicio = ($pagina - 1) * $registros; 
    }

    /*
    *	TRAEMOS LOS registros.
    */

    $sql = "SELECT count(DISTINCT sat_reportes.id) as conteo ";
    $sql .= " FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario ";
    $sql .= $sqlJoinGrupoBase;
    $sql .= " WHERE 1 ";
    //

    if($_SESSION["perfil"] == 163){
        $_REQUEST["idUsuario"] = $_SESSION["id"];
    }

    //

    if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != ""){
        $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
        $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
    }

    //

    if(isset($_REQUEST["idGrupoMadre"]) && soloNumeros($_REQUEST["idGrupoMadre"]) != ""){
        $buscar_idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $sqlFiltro .= " AND sat_reportes.idGrupoMadre = '".$buscar_idGrupoMadre."'";
    }

    if(isset($_REQUEST["inactivo"]) && soloNumeros($_REQUEST["inactivo"]) != ""){
        $inactivo = soloNumeros($_REQUEST["inactivo"]);
        if($inactivo == 99){
            $sqlFiltro .= " AND sat_reportes.inactivo = 1";
        }
        else if($inactivo == 1){
            $sqlFiltro .= " AND sat_reportes.inactivo = 0";
        }
    }

    //

    if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){
        $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
        $sqlFiltro .= " AND sat_reportes.plantador LIKE '%".$buscar_nombre."%'";
    }

    //

    if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
        $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
    }

    //

    if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
        $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
    }

    if(isset($_REQUEST["actividadFiltro"]) && soloNumeros($_REQUEST["actividadFiltro"]) != ""){
        $actividadFiltro = soloNumeros($_REQUEST["actividadFiltro"]);
        $sqlFiltro .= " AND sat_reportes.id_actividad = '".$actividadFiltro."'";
    }

    if(isset($_REQUEST["generacionNumero"]) && soloNumeros($_REQUEST["generacionNumero"]) != ""){
        $generacionNumero = eliminarInvalidos($_REQUEST["generacionNumero"]);
        if((int)$generacionNumero >= 0 && (int)$generacionNumero <= 5){
            $sqlFiltro .= " AND sat_reportes.generacionNumero = '".$generacionNumero."'";
        }else{
            $generacionNumero = "";
        }
    } 


    //    
    $sql .= $sqlFiltro." ORDER BY sat_reportes.id DESC";
    //

    $PSN1->query($sql);
    if($PSN1->num_rows() > 0)
    {
        if($PSN1->next_record())
        {
            $total_registros = $PSN1->f('conteo');
        }
    }

    $totalesFiltroProceso = array(
        "asistencia_total" => 0,
        "desiciones"   => 0,
        "preparandose" => 0,
        "discipulado"  => 0,
        "bautizados"   => 0
    );

    $sqlTotalesProceso = "SELECT
            SUM(COALESCE(sat_reportes.asistencia_total, 0)) as asistencia_total,
            SUM(COALESCE(sat_reportes.desiciones, 0)) as desiciones,
            SUM(COALESCE(sat_reportes.preparandose, 0)) as preparandose,
            SUM(COALESCE(sat_reportes.discipulado, 0)) as discipulado,
            SUM(COALESCE(sat_reportes.bautizados, 0)) as bautizados
        FROM sat_reportes
        ".$sqlJoinGrupoBase."
        WHERE 1 ".$sqlFiltro;

    $PSN_totales = new DBbase_Sql;
    $PSN_totales->query($sqlTotalesProceso);
    if($PSN_totales->num_rows() > 0 && $PSN_totales->next_record()){
        $totalesFiltroProceso["asistencia_total"] = (int)$PSN_totales->f('asistencia_total');
        $totalesFiltroProceso["desiciones"]   = (int)$PSN_totales->f('desiciones');
        $totalesFiltroProceso["preparandose"] = (int)$PSN_totales->f('preparandose');
        $totalesFiltroProceso["discipulado"]  = (int)$PSN_totales->f('discipulado');
        $totalesFiltroProceso["bautizados"]   = (int)$PSN_totales->f('bautizados');
    }

    $total_paginas = ceil($total_registros / $registros); 
    $sql_ids = "SELECT sat_reportes.id FROM sat_reportes ".$sqlJoinGrupoBase." WHERE 1 ".$sqlFiltro." ORDER BY sat_reportes.id DESC LIMIT ".$inicio.", ".$registros;
    $PSN_ids = new DBbase_Sql;
    $PSN_ids->query($sql_ids);
    $report_ids = [];
    while($PSN_ids->next_record()){
        $report_ids[] = $PSN_ids->f('id');
    }

    $mensajeSinResultados = "";
    if (count($report_ids) > 0) {
        $sql = "SELECT sat_reportes.*, usuario.nombre as nombreUsuario, sat_grupos.nombre as nombreGrupo, grupo_base.generacionNumero as generacionGrupo, actividad.nombre_actividad, adjuntos.adj_url, adjuntos.total_adjuntos ";
        $sql.=" FROM sat_reportes ";
        $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
        $sql .= " LEFT JOIN sat_grupos ON sat_grupos.id = sat_reportes.idGrupoMadre";
        $sql .= $sqlJoinGrupoBase;
        $sql .= " LEFT JOIN actividad ON actividad.id_actividad = sat_reportes.id_actividad";
        $sql .= " LEFT JOIN (
                    SELECT adj_rep_fk, MAX(NULLIF(adj_url, '')) as adj_url, COUNT(NULLIF(adj_url, '')) as total_adjuntos
                    FROM tbl_adjuntos
                    GROUP BY adj_rep_fk
                  ) as adjuntos ON sat_reportes.id = adjuntos.adj_rep_fk";
        $sql.=" WHERE sat_reportes.id IN (" . implode(',', $report_ids) . ") ORDER BY sat_reportes.id DESC";
    } else {
        $sql = "";
        if($total_registros > 0){
            $mensajeSinResultados = "La pagina solicitada no tiene registros para los filtros actuales. Puedes volver a la pagina 1.";
        }else{
            $mensajeSinResultados = "No se encontraron reportes con los filtros seleccionados.";
        }
    }
    //

    //echo $sql;
    if($sql != ""){
        $PSN1->query($sql);
        $numero=$PSN1->num_rows();
    }else{
        $numero = 0;
    }
    $actividadesFiltroDisponibles = array();
    $PSN_ActividadFiltro = new DBbase_Sql;
    $sqlActividadesFiltro = "SELECT id_actividad, nombre_actividad FROM actividad ORDER BY nombre_actividad ASC";
    $PSN_ActividadFiltro->query($sqlActividadesFiltro);
    while($PSN_ActividadFiltro->next_record()){
        $actividadesFiltroDisponibles[] = array(
            'id_actividad' => $PSN_ActividadFiltro->f('id_actividad'),
            'nombre_actividad' => $PSN_ActividadFiltro->f('nombre_actividad')
        );
    }

    ?>
    <!-- AG -->
    <style type="text/css">
        a.reportes {
        background-color: #87BBF5;
        color:#fff;
        }

        .table tbody tr:hover td, .table tbody tr:hover th {
                        background-color: #E0EEEE;
                        cursor:pointer;
                        color:#000;
                    }
                    .table thead tr{
                        background-color: #C7C7C7;
                    }
                    .table thead th{
                        vertical-align: middle;text-align: center;
                    }
                    .tabla-reportes-grupos th,
                    .tabla-reportes-grupos td{
                        vertical-align: middle !important;
                        white-space: nowrap;
                        border-right: 1px solid #d8e0e8 !important;
                    }
                    .tabla-reportes-grupos th:first-child,
                    .tabla-reportes-grupos td:first-child{
                        border-left: 1px solid #d8e0e8 !important;
                    }
                    .tabla-reportes-grupos th.col-text,
                    .tabla-reportes-grupos td.col-text{
                        text-align: left;
                    }
                    .tabla-reportes-grupos th.col-center,
                    .tabla-reportes-grupos td.col-center{
                        text-align: center;
                    }
                    .table a{
                        color:#000;
                    }
                    .report-filters-wrap{
                        background:#f8fbff;
                        border:1px solid #d9e6f2;
                        border-radius:12px;
                        padding:18px 18px 8px;
                        margin-bottom:20px;
                    }
                    .report-filters-wrap .filter-item{
                        margin-bottom:14px;
                    }
                    .report-filters-wrap .filter-label{
                        display:block;
                        font-weight:700;
                        margin-bottom:6px;
                        color:#2c3e50;
                    }
                    .report-filters-wrap .filter-label-empty{
                        visibility:hidden;
                    }
                    .report-filters-wrap .filter-actions{
                        display:flex;
                        gap:10px;
                        align-items:center;
                        flex-wrap:wrap;
                        min-height:34px;
                    }
                    .report-filters-wrap .filter-actions .btn{
                        min-width:120px;
                    }
                    .report-filters-wrap .filter-help{
                        margin-top:4px;
                        color:#5f6f81;
                        font-size:12px;
                    }
    </style>
    <div class="container">
        <br>
        <div>
            <h2 class="alert alert-info text-center">CONSULTAR REPORTES</h2>
        </div>
        <br>
        <ul class="nav nav-pills nav-justified " role="tablist" >
            <li role="presentation" class="active"><a class="reportes" id="LiMpi" href="#Mpi" aria-controls="Mpi" role="tab" data-toggle="tab">Movimiento de Plantación y Multiplicación de Iglesias</a></li>
            <li role="presentation"><a class="reportes" id="LiSO" href="#Sopas" aria-controls="Sopas" role="tab" data-toggle="tab">Deshidratados</a></li>
        </ul>       
        <div class="tab-content" id="tabContent">
            <div role="tabpanel" class="tab-pane fade in active" id="Mpi">
                <div class="container">
                    <form name="form" id="form" method="get" class="form-horizontal">
                        <input type="hidden" name="doc" value="reportar_buscar" />
                        <input type="hidden" name="pagina" value="1" />
                        <div class="cont-tit">
                            <div class="hr"><hr></div>
                            <div class="tit-cen">
                                <h3>FILTRO DE BUSQUEDA</h3>
                                <h5>de REPORTES</h5>
                            </div>
                            <div class="hr"><hr></div>
                        </div>
                        <div class="report-filters-wrap">
                            <div class="row">
                            <div class="col-sm-4 filter-item">
                                <label class="filter-label">Facilitador</label><?php
                            ?><select name="idUsuario" class="form-control">
                            <?php
                            if($_SESSION["perfil"] != 163){
                            ?><option value="">Ver todos</option><?php
                            }
                            /*

                            *	TRAEMOS LOS USUARIOS

                            */
                            $sql = "SELECT * ";
                            $sql.=" FROM usuario ";
                            $sql.=" WHERE tipo IN (162, 163) AND acceso = 1 ";
                            if($_SESSION["perfil"] == 163){
                                $sql.=" AND id = '".$_SESSION["id"]."'";
                            }
                            $sql.=" ORDER BY nombre asc";
                            $PSN2->query($sql);
                            $numero=$PSN2->num_rows();
                            if($numero > 0)
                            {
                                while($PSN2->next_record())
                                {
                                    ?><option value="<?=$PSN2->f('id'); ?>" <?php
                                    if($buscar_idUsuario == $PSN2->f('id'))
                                    {
                                        ?>selected="selected"<?php
                                    }
                                    ?>><?=$PSN2->f('nombre'); ?></option><?php
                                }
                            }
                            ?></select>
                            </div>
                            <div class="col-sm-2 filter-item">
                                <label class="filter-label">Generacion</label>
                                <select name="generacionNumero" class="form-control">
                                    <option value="">Ver todos</option>
                                    <option value="0" <?php if($generacionNumero === "0" || $generacionNumero === 0){ ?>selected="selected"<?php } ?>>0</option>
                                    <option value="1" <?php if($generacionNumero == 1){ ?>selected="selected"<?php } ?>>1</option>
                                    <option value="2" <?php if($generacionNumero == 2){ ?>selected="selected"<?php } ?>>2</option>
                                    <option value="3" <?php if($generacionNumero == 3){ ?>selected="selected"<?php } ?>>3</option>
                                    <option value="4" <?php if($generacionNumero == 4){ ?>selected="selected"<?php } ?>>4</option>
                                    <option value="5" <?php if($generacionNumero == 5){ ?>selected="selected"<?php } ?>>5</option>
                                </select>
                            </div>
                            <div class="col-sm-3 filter-item">
                                <label class="filter-label">Actividad</label>
                                <select name="actividadFiltro" class="form-control">
                                    <option value="">Ver todas</option>
                                    <?php foreach($actividadesFiltroDisponibles as $actividadItem){ ?>
                                        <option value="<?=$actividadItem['id_actividad']; ?>" <?php if($actividadFiltro == $actividadItem['id_actividad']){ ?>selected="selected"<?php } ?>><?=$actividadItem['nombre_actividad']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-3 filter-item">
                                <label class="filter-label">Estado</label>
                                <select name="inactivo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="1" <?php if($inactivo == 1){ ?>selected="selected"<?php } ?>>Activo</option>
                                    <option value="99" <?php if($inactivo == 99){ ?>selected="selected"<?php } ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-sm-3 filter-item">
                                <label class="filter-label">Fecha inicial</label>
                                <input type="date" name="fechaInicial" id="fechaInicial" value="<?=$fechaInicial; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-3 filter-item">
                                <label class="filter-label">Fecha final</label>
                                <input type="date" name="fechaFinal" id="fechaFinal" value="<?=$fechaFinal; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-6 filter-item">
                                <label class="filter-label filter-label-empty">Acciones</label>
                                <div class="filter-actions">
                                    <input type="submit" value="Filtrar" class="btn btn-success" />
                                    <a href="index.php?doc=reportar_buscar" class="btn btn-default">Limpiar filtros</a>
                                </div>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
                <div class="container">
                    <div class="cont-tit">
                        <div class="hr"><hr></div>
                        <div class="tit-cen">
                            <h3 class="text-center">RESULTADOS DE BUSQUEDA</h3>
                            <h5><?php echo $total_registros; ?> Registros encontrados</h5>
                        </div>
                        <div class="hr"><hr></div>
                    </div>
                    <?php if($numero == 0){ ?>
                    <div class="alert alert-warning" style="margin-bottom:15px;">
                        <?=$mensajeSinResultados != "" ? $mensajeSinResultados : "No se encontraron reportes con los filtros seleccionados."; ?>
                    </div>
                    <?php } ?>
                    <div style="overflow-x: auto;">
                        <?php $columnasAntesTotales = $esFacilitador ? 4 : 5; ?>
                        <table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-striped tabla-reportes-grupos" style="font-size:12px">
                            <thead>
                                <tr> 
                                    <th class="col-center">Id</th>
                                    <?php if(!$esFacilitador){ ?>
                                    <th class="col-text">Facilitador</th>
                                    <?php } ?>
                                    <th class="col-center" title="Fecha de reporte" width="80">Fec. Reporte</th>
                                    <th class="col-text">Actividad</th>
                                    <th class="col-center">Generacion</th>
                                    <th class="col-center" title="Decisiones">Deci.</th>
                                    <th class="col-center" title="Preparándose">Prep.</th>
                                    <th class="col-center" title="En Discipulado">Disc.</th>
                                    <th class="col-center" title="Bautizados">Baut.</th>
                                    <th class="col-center">Foto</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr style="background-color:#E8F4EA; font-weight:bold;">
                                    <th colspan="<?=$columnasAntesTotales; ?>" class="col-center" style="text-align:right;">Totales del filtro</th>
                                    <th class="col-center"><?=$totalesFiltroProceso["desiciones"]; ?></th>
                                    <th class="col-center"><?=$totalesFiltroProceso["preparandose"]; ?></th>
                                    <th class="col-center"><?=$totalesFiltroProceso["discipulado"]; ?></th>
                                    <th class="col-center"><?=$totalesFiltroProceso["bautizados"]; ?></th>
                                    <th class="col-center"></th>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                if($total_registros > 0)
                                {
                                    $contador = 0;
                                    while($PSN1->next_record())
                                    {
                                        //Solo si no se ha modificado ya el formulario.
                                        $id = $PSN1->f('id');
                                        $plantador = $PSN1->f("plantador");
                                        $fechaReporte = $PSN1->f("fechaReporte");
                                        $fechaInicio = $PSN1->f("fechaInicio");        
                                        $sitioReunion = $PSN1->f("sitioReunion");
                                        $grupoMadre_txt = $PSN1->f("grupoMadre_txt");
                                        $idGrupoMadre = $PSN1->f("idGrupoMadre");
                                        $generacionNumero = intval($PSN1->f("generacionNumero"));
                                        $nombreUsuario = $PSN1->f("nombreUsuario");
                                        $nombreGrupo = $PSN1->f("nombreGrupo");
                                        $mapeo_comprometido = $PSN1->f("mapeo_comprometido");
                                        $nombreGrupo_txt = $PSN1->f("nombreGrupo_txt");
                                        $mapeo_fecha = $PSN1->f("mapeo_fecha");  
                                        $mapeo_oracion = $PSN1->f("mapeo_oracion");  
                                        $mapeo_companerismo = $PSN1->f("mapeo_companerismo");  
                                        $mapeo_adoracion = $PSN1->f("mapeo_adoracion");  
                                        $mapeo_biblia = $PSN1->f("mapeo_biblia");  
                                        $mapeo_evangelizar = $PSN1->f("mapeo_evangelizar");  
                                        $mapeo_cena = $PSN1->f("mapeo_cena");  
                                        $mapeo_dar = $PSN1->f("mapeo_dar");  
                                    $mapeo_bautizar = $PSN1->f("mapeo_bautizar");  
                                        $mapeo_trabajadores = $PSN1->f("mapeo_trabajadores");  
                                        $ext1 = $PSN1->f("ext1");
                                        $ext2 = $PSN1->f("ext2");
                                        $ext3 = $PSN1->f("ext3");
                                        $asistencia_hom = $PSN1->f("asistencia_hom");
                                        $asistencia_muj = $PSN1->f("asistencia_muj");
                                        $asistencia_jov = $PSN1->f("asistencia_jov");
                                        $asistencia_nin = $PSN1->f("asistencia_nin");
                                        $bautizados = $PSN1->f("bautizados");
                                        //Calculados:
                                        $asistencia_total  = $PSN1->f("asistencia_total");
                                        $discipulado  = $PSN1->f("discipulado");
                                        $desiciones  = $PSN1->f("desiciones");
                                        $preparandose  = $PSN1->f("preparandose");
                                        $url_baut  = $PSN1->f("adj_url");
                                        $iglesias_reconocidas = $PSN1->f("iglesias_reconocidas");  
                                        $idActividad = $PSN1->f("id_actividad");
                                        $generacionGrupo = intval($PSN1->f("generacionNumero"));
                                        $actividadReporte = trim($PSN1->f("nombre_actividad"));
                                        if($actividadReporte == ""){
                                            $actividadReporte = reportar_buscar_actividad_label($idActividad, $generacionNumero);
                                        }
                                        $totalAdjuntos = (int)$PSN1->f("total_adjuntos");
                                        $tieneFoto = ($totalAdjuntos > 0 || trim($url_baut) != "" || trim($ext1) != "" || trim($ext2) != "" || trim($ext3) != "");
                                        ?><tr class='clickable-row' data-href='index.php?doc=reportar&id=<?=$id; ?>' >
                                            <td class="col-center"><a href="index.php?doc=reportar&id=<?=$id; ?>"><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></a></td>
                                            <?php if(!$esFacilitador){ ?>
                                            <td class="col-text"><a href="index.php?doc=reportar&id=<?=$id; ?>"><?=$nombreUsuario; ?></a></td>
                                            <?php } ?>
                                            <td class="col-center"><?=$fechaReporte; ?></td>
                                            <td class="col-text"><?=$actividadReporte; ?></td>
                                            <td class="col-center"><?=$generacionGrupo; ?></td>
                                            <td class="col-center"><?=$desiciones; ?></td>
                                            <td class="col-center"><?=$preparandose; ?></td>
                                            <td class="col-center"><?=$discipulado; ?></td>
                                            <td class="col-center"><?=$bautizados; ?></td>
                                            <td class="col-center"><?php
                                            if($tieneFoto){
                                                ?><i class="fas fa-thumbs-up ico-lik"></i><?php
                                            }else{
                                                ?><i class="fas fa-thumbs-down ico-dli"></i><?php
                                            }
                                            ?></td>
                                        </tr>
                                        <?php
                                        $contador++;
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tr>
                                        <td colspan="<?=($esFacilitador ? 9 : 10); ?>" class="text-center" style="padding:18px;">
                                            No hay registros para mostrar con los filtros aplicados.
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>       
                </div>
                <?php if($total_paginas > 1){ ?>
                <center>
                <div class="container">
                    <ul class="pagination">
                        <?php
                        //
                        $paginaActualTxT = "&pagina=".$pagina;
                        $_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT,"", $_SERVER['REQUEST_URI']);
                    //
                        if(($pagina - 1) > 0)
                        {
                            echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina-1)."'>&laquo;</a></li>"; 
                        }
                        for ($i=1; $i<=$total_paginas; $i++)
                        { 
                            if ($pagina == $i)
                            {
                                echo "<li class='active'><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a>"; 
                            }
                        else 
                            { 
                                echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a></li>";
                            } 
                        }
                        if(($pagina + 1)<=$total_paginas)
                        { 
                            echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina+1)."'>&raquo;</a></li>"; 
                        }
                        ?>
                    </ul>
                </div>
                <?php } ?>
            </div>
            <div role="tabpanel" class="tab-pane fade in" id="Sopas">
                <div class="container">
                    <div class="cont-tit">
                        <div class="hr"><hr></div>
                        <div class="tit-cen">
                            <h3>FILTRO DE BUSQUEDA</h3>
                            <h5>de REPORTES</h5>
                        </div>
                        <div class="hr"><hr></div>
                    </div>
                    <div class="form-group">
                        <form name="form" id="form" method="get" class="form-horizontal">
                            <input type="hidden" name="doc" value="reportar_buscar" />
                            <div class="col-sm-3">
                                <strong>Facilitador:</strong><?php
                                ?>
                                <select name="Facilitador" onchange="this.form.submit()" class="form-control">
                                <?php
                                    if($_SESSION["perfil"] != 163){
                                ?>
                                <option value="">Ver todos</option><?php
                                }

                                $sql = "SELECT * ";
                                $sql.=" FROM usuario ";
                                $sql.=" WHERE acceso = 1 ";
                                $sql.=" ORDER BY nombre asc";
                                $PSN2->query($sql);
                                $numero=$PSN2->num_rows();
                                if($numero > 0)
                                {
                                    while($PSN2->next_record())
                                    {
                                        ?><option value="<?=$PSN2->f('id'); ?>" <?php
                                        if($Facilitador == $PSN2->f('id'))
                                        {
                                            ?>selected="selected"<?php
                                        }
                                        ?>><?=$PSN2->f('nombre'); ?></option><?php
                                    }
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <strong>Pais:</strong>
                                <div class="">
                                    <select name="pais" id="pais" class="form-control">
                                        <option value="0">Seleccione el Pais</option>
                                        <option value="1" <?php if($pais == 1){ ?>selected="selected"<?php } ?>>Colombia</option>
                                        <option value="2" <?php if($pais == 2){ ?>selected="selected"<?php } ?>>Venezuela</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <strong>Departamento:</strong>
                                <div class="">
                                    <input type="hidden" name="Depto" id="Depto"/>
                                    <select name="departamento" id="departamento" class="form-control">                                
                                        <option value="<?=$departamento;?>"><?=$Depto;?></option>                                   
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <label class="filter-label">Fecha inicial</label>
                                <input type="date" name="FechaIni" id="FechaIni" value="<?=$FechaIni; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-2">
                                <label class="filter-label">Fecha final</label>
                                <input type="date" name="FechaFin" id="FechaFin" value="<?=$FechaFin; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-1" >
                                <br>
                                <input type="submit" value="Filtrar" class="btn btn-success" />
                            </div>
                        </form>
                    </div>
                </div>    
                <div class="container">    
                    <div class="cont-tit">
                        <div class="hr"><hr></div>
                        <div class="tit-cen">
                            <h3 class="text-center">RESULTADOS DE BUSQUEDA</h3>
                            <h5><?php echo $TotalReg; ?> Registros encontrados</h5>
                        </div>
                        <div class="hr"><hr></div>
                    </div> 
                    <div style="overflow-x: auto;">
                        <table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-striped">
                            <thead>
                                <tr> 
                                    <th>Tipo De Registro</th>
                                    <th>Facilitador</th>
                                    <th>Beneficiario</th>
                                    <th>Donante</th>
                                    <th>Tipo de Deshidratados</th>
                                    <th>Cantidad Entregada</th>
                                    <th>Fecha Primera Entrega</th>
                                    <th>Fecha Ultima Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                            
                            </tr>
                                <?php
                                     $PSN_Rep->query($sqlRep);
                                 if($PSN_Rep->num_rows()  > 0)
                                 { 
                                    while($PSN_Rep->next_record())
                                    {
                                        ?>
                                        <tr onclick="GetData(<?=$PSN_Rep->f('idBeneficiado'); ?>)" data-toggle="modal" data-target="#myModal">
                                            <td><?=$PSN_Rep->f("TipoRegistro"); ?></td>
                                            <td><?=$PSN_Rep->f("Responsable"); ?></td>
                                            <td><?=$PSN_Rep->f("Nombre"); ?></td>
                                            <td><?=$PSN_Rep->f("Donante"); ?></td>
                                            <td align="center"><?=$PSN_Rep->f("TipoSopa"); ?></td>
                                            <td align="center"><?=$PSN_Rep->f("CantidadEntregada"); ?></td>
                                            <td align="center"><?=$PSN_Rep->f("fechaMin"); ?></td>
                                            <td align="center"><?=$PSN_Rep->f("fechaMax"); ?></td>
                                        </tr>
                                        <?php
                                    }
                                 }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Modal -->
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">Informacion del Beneficiario y Entregas</h4>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Nombre y Apellido:</strong>
                                        <input name="nombre" type="text" id="nombre" class="form-control" readonly/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Teléfono de Contacto:</strong>
                                        <input name="telefono" type="text" id="telefono" class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Total personas que viven en la casa:</strong>
                                        <input name="PersonasCasa" type="text" id="PersonasCasa" class="form-control" readonly />
                                    </div>
                                </div>                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Total niños : </strong>
                                        <input name="NinosCasa" type="text" id="NinosCasa" class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Total niños beneficiarios de Soy Satura:</strong>
                                        <input name="SoySatura" type="text" id="SoySatura" class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Total adolescentes:  </strong>
                                        <input name="AdoCasa" type="text" id="AdoCasa" class="form-control" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Total adultos mayores: </strong>
                                        <input name="Adultos" type="text" id="Adultos" class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Total personas en condición de discapacidad: </strong>
                                        <input name="Discapacidad" type="text" id="Discapacidad" class="form-control" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Tipos de discapacidades: </strong>
                                        <input name="TipDiscapacidad" type="text" id="TipDiscapacidad"  class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Ingresos mensuales de su grupo familiar: </strong>
                                        <input name="OptIngresos" type="text" id="OptIngresos" class="form-control" readonly/>
                                    </div>
                                </div>
                            </div>  
                            <div class="row">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>Comidas que no consume como cabeza del grupo familiar: </strong>
                                        <input name="ComidaNoConsumida" type="text" id="ComidaNoConsumida" class="form-control" readonly  />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <strong>Situaciones presentadas dentro del grupo familiar: </strong>
                                        <input name="Situaciones" type="text" id="Situaciones" class="form-control" readonly/>
                                    </div>
                                </div>
                            </div>   
                            <div class="row" id="DivIpg" style="display:none;">
                                <div class="col-md-6">   
                                    <div class="form-group">
                                        <strong>IPG a la que asiste: </strong>
                                        <input name="TxtIpg" type="text" id="TxtIpg" class="form-control" readonly  />
                                    </div>
                                </div>
                            </div>     
                            <hr>
                            <div id="DataEntrega" style="overflow-x: auto;">
                            </div>    
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </center>
    <!--<br />
    <center>
    <a href="index.php?excelX=&doc=usuario_buscar&nombre=<?=$buscar_nombre; ?>&identificacion=<?=$buscar_identificacion; ?>&tipo=<?=$buscar_tipo; ?>&ctrl=<?=$ctrl; ?>" class="btn btn-info"><span class="glyphicon glyphicon-cloud-download"></span> DESCARGAR PARA EXCEL</a></center>//-->
    <script language="javascript">
    function init(){
    }
    window.onload = function(){
        init();
    }
    function GetData(id){
        $("#myModal").find("input,textarea,select").val("");
        $("#myModal input[type='checkbox']").prop('checked', false).change();
        $('#DataEntrega').html("");
        if(id != 0){
            $.ajax({
            type:'POST',
            url:'proceso.php',
            dataType: "json",
            data:{id:id},
            success:function(data){             
                $("#nombre").val(data[0]);
                $("#telefono").val(data[1]);
                $('#PersonasCasa').val(data[2]);
                $('#NinosCasa').val(data[3]);
                $('#SoySatura').val(data[4]);
                $('#AdoCasa').val(data[5]);
                $('#Adultos').val(data[6]);
                $('#Discapacidad').val(data[7]);
                $('#TipDiscapacidad').val(data[8]);
                if (data[9] = 1){
                    $('#OptIngresos').val("Actualmente no recibe ningún ingreso");
                }else if (data[9] = 2){
                    $('#OptIngresos').val("Menos de un salario mínimo");
                }else if (data[9] = 3){
                    $('#OptIngresos').val("Un salario mínimo");
                }else if (data[9] = 4){
                    $('#OptIngresos').val("Más de un salario mínimo");
                }else if (data[9] = 5){
                    $('#OptIngresos').val("No sabe");
                }
                $('#ComidaNoConsumida').val(data[10]);
                var IPG = data[12];
                if (IPG != null){
                    $('#DivIpg').show();
                    $('#TxtIpg').val(data[12]);
                }else{
                    $('#DivIpg').hide();
                }

                $('#Situaciones').val(data[11]);
                
                var Entradas = data[13];
                var tabla = "";
                tabla += '<table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-striped align-items-center" id="TblEntradas">';
                tabla += '<thead>';
                tabla += '<tr>';
                tabla += '<th>Fecha</th><th>Responsable</th><th>Donante</th><th>Tipo de Deshidratados</th><th>Total</th><th>Operacion</th>';
                tabla += '</tr>';
                tabla += '</thead>';
                tabla += '<tbody>';
                for (var i=0; i<Entradas.length; i++) {
                    tabla += '<tr>';
                    var DataEntrada = Entradas[i].split(',')
                    for (var j=0; j<DataEntrada.length; j++) {
                        tabla += '<td>';
                        
                        if(j == DataEntrada.length-1){
                            tabla += '<span data-toggle="tooltip" data-placement="top" title= " Eliminar Inventario '+DataEntrada[j]+'"><form name="form" id="form" method="get" class="form-horizontal"><input type="hidden" name="doc" value="reportar_buscar" /><input type="hidden" name="DelInv" value="'+DataEntrada[j]+'"/><div><button type="submit" class="btn btn-danger btn-sm"><span class="icon"><i class="fas fa-trash-alt"></i></span></button></button></div></form></span>'
                        }else{
                            tabla += DataEntrada[j];
                        }
                        tabla += '</td>';
                    }
                    tabla += '</tr>';
                }
                tabla += '</tbody></table>';
                $('#DataEntrega').html(tabla);
                $('#TblEntradas').DataTable({
                    "lengthMenu": [ [5, 10, 15, 20], [5, 10, 15, 20] ],
                    "pageLength": 5 ,  
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
                    }
                });
            } 
     
            });
        }
    }
    </script>
    <script> 
    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });

        $("#pais").on('change', function () {
                $("#pais option:selected").each(function () {
                    pais=$(this).val();
                    $.post("paises.php", { pais: pais }, function(data){
                        $("#departamento").html(data);
                    });			
                });
            });
        
        $("#departamento").on('change', function () {
            $("#Depto").val($('select[name="departamento"] option:selected').text()) ;
        });
        
    });
    </script><?php
}
?>

