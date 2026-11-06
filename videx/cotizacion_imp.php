<?php
/*
*	LOGUEO
*/
//Si es un usuario externo o cliente o proveedor NO mostrar.
if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(!isset($_GET["opc"]))
{
	$opc = 2;
}
else
{
	$opc = eliminarInvalidos($_GET["opc"]);
}

//
//	PARAMETROS CONFIGURABLES
//
$tablaConsulta = "cotizacion";
$webArchivo = "cotizacion";
$nombreConsulta = "COTIZACIONES";


if(isset($_GET["id"]) && soloNumeros($_GET["id"]) != "" && soloNumeros($_GET["id"]) != 0)
{
		$PSN = new DBbase_Sql;
        //
        $sql= "SELECT ".$tablaConsulta.".*, usuario.nombre as  creacionUsuarioNom, modif.nombre as modUsuarioNom";
        $sql.=" FROM ".$tablaConsulta;
            $sql.=" LEFT JOIN usuario ON usuario.id = ".$tablaConsulta.".creacionUsuario ";
            $sql.=" LEFT JOIN usuario modif ON modif.id = ".$tablaConsulta.".modUsuario ";			
        $sql.=" WHERE ".$tablaConsulta.".id='".soloNumeros($_GET["id"])."'";

        $PSN->query($sql);
        $num=$PSN->num_rows();
        if($num > 0)
        {
            if($PSN->next_record())
            {
                echo stripslashes($PSN->f('mensajecotizacion'));
            }
        }
}
?>