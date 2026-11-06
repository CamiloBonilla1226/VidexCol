<?php
/*
 * LOGUEO
 */

if (!isset($_GET['opc'])) {
    $opc = 2;
} else {
    $opc = eliminarInvalidos($_GET['opc']);
}

$tablaConsulta = 'personal';
$webArchivo = 'Personal';
$nombreConsulta = 'PERSONAL';

function personal_render_styles()
{
    ?>
    <style>
        #personal-modern {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --line: #d9e2ec;
            --line-strong: #c8d5e3;
            --text: #1b2a3a;
            --primary: #1f5d92;
            --primary-soft: #e8f2fb;
            color-scheme: light;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: var(--text);
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
        }

        #personal-modern * { box-sizing: border-box; }

        #personal-modern .card {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto 12px auto;
            padding: 16px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(16, 42, 67, 0.05);
        }

        #personal-modern .well,
        #personal-modern .alert-info {
            background: var(--primary-soft);
            color: #17486f;
            border: 1px solid #cfe0ef;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 0;
        }

        #personal-modern .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap: 12px;
        }

        #personal-modern .grid.filters {
            grid-template-columns: repeat(4, minmax(180px, 1fr));
        }

        #personal-modern .full { grid-column: 1 / -1; }
        #personal-modern .form-group { margin-bottom: 0; }

        #personal-modern .form-label,
        #personal-modern label {
            display: inline-block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text);
        }

        #personal-modern .form-control,
        #personal-modern select,
        #personal-modern textarea,
        #personal-modern input[type="text"] {
            display: block;
            width: 100%;
            padding: 9px 11px;
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            background: #fff;
            color: #1b2a3a;
            font-size: 14px;
        }

        #personal-modern .actions {
            margin-top: 14px;
            text-align: center;
        }

        #personal-modern .btn {
            padding: 9px 14px;
            border-radius: 8px;
            font-weight: 600;
        }

        #personal-modern .btn-success {
            background: #1f8a55;
            border-color: #1f8a55;
            color: #fff;
        }

        #personal-modern .btn-danger {
            background: #c94545;
            border-color: #c94545;
            color: #fff;
        }

        #personal-modern .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        #personal-modern table {
            width: 100%;
            min-width: 860px;
            margin: 0;
            border-collapse: collapse;
        }

        #personal-modern th {
            background: #eef4fa;
            color: #224762;
        }

        #personal-modern th,
        #personal-modern td {
            border-color: var(--line) !important;
            padding: 9px 10px;
            font-size: 13px;
            vertical-align: top;
        }

        #personal-modern .pagination-wrap {
            text-align: center;
            margin-top: 12px;
        }

        @media (max-width: 980px) {
            #personal-modern .grid.filters { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 760px) {
            #personal-modern .card { padding: 12px; }
            #personal-modern .grid,
            #personal-modern .grid.filters { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var root = document.getElementById('personal-modern');
            if (!root) return;
            var main = document.querySelector('.main-content, #main-content, #maincontent, #mainContent');
            if (main && !main.contains(root)) {
                main.appendChild(root);
            }

            var orphanSelectors = ['.videx-wrap > .container', '.videx-wrap > .form-group', '.videx-wrap > .row', '.videx-wrap > form'];
            orphanSelectors.forEach(function (selector) {
                document.querySelectorAll(selector).forEach(function (node) {
                    if (node === root) return;
                    if (root.contains(node)) return;
                    root.appendChild(node);
                });
            });
        });
    </script>
    <?php
}

$PSN = new DBbase_Sql;

if ($opc == 1) {
    if (isset($_POST['nombre'])) {
        $sql = "SELECT id FROM personal WHERE nombres='" . eliminarInvalidos($_REQUEST['nombre']) . "'";
        $PSN->query($sql);
        if ($PSN->num_rows() > 0 && $PSN->next_record()) {
            ?>
            <script>
                alert('YA EXISTE ESE NOMBRE EN EL SISTEMA, SERA AHORA DIRIGIDO AL MISMO');
                window.location.href = 'index.php?doc=Personal&opc=2&id=<?=$PSN->f('id');?>';
            </script>
            <?php
        } else {
            $sql = 'INSERT INTO personal (cc, nombres, tel, direccion, cel, email, estado, observaciones, id_tipo_personal) VALUES (' .
                '"' . eliminarInvalidos($_POST['cc']) . '",' .
                '"' . eliminarInvalidos($_POST['nombre']) . '",' .
                '"' . soloNumeros($_POST['telefono']) . '",' .
                '"' . eliminarInvalidos($_POST['direccion']) . '",' .
                '"' . soloNumeros($_POST['celular']) . '",' .
                '"' . eliminarInvalidos($_POST['email']) . '",' .
                '"' . soloNumeros($_POST['estado']) . '",' .
                '"' . eliminarInvalidos($_POST['observaciones']) . '",' .
                '"' . soloNumeros($_POST['perfil']) . '")';
            $PSN->query($sql);
            $ultimoId = $PSN->ultimoId();
            ?>
            <script>
                alert('Se ha creado correctamente el registro.');
                window.location.href = 'index.php?doc=Personal&opc=2&id=<?=$ultimoId;?>';
            </script>
            <?php
        }
    } else {
        personal_render_styles();
        ?>
        <div id="personal-modern">
            <div class="card">
                <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                    <div class="form-group full">
                        <h2 class="alert alert-info text-center">.CREACION DE <?=$nombreConsulta;?> EN EL SISTEMA.</h2>
                    </div>
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label" for="perfil">Perfil</label>
                            <select name="perfil" id="perfil" class="form-control">
                                <?php
                                $tmp = new DBbase_Sql;
                                $tmp->query("SELECT * FROM categorias WHERE idSec=36 ORDER BY descripcion ASC");
                                while ($tmp->next_record()) {
                                    $sel = (soloNumeros($_POST['perfil']) == $tmp->f('id')) ? 'selected="selected"' : '';
                                    echo '<option value="' . $tmp->f('id') . '" ' . $sel . '>' . $tmp->f('descripcion') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input name="nombre" id="nombre" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['nombre']);?>" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cc">CC</label>
                            <input name="cc" id="cc" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['cc']);?>" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="telefono">Telefono</label>
                            <input name="telefono" id="telefono" type="text" maxlength="250" value="<?=soloNumeros($_POST['telefono']);?>" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="direccion">Direccion</label>
                            <input name="direccion" id="direccion" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['direccion']);?>" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="celular">Celular</label>
                            <input name="celular" id="celular" type="text" maxlength="250" value="<?=soloNumeros($_POST['celular']);?>" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="estado">Estado</label>
                            <select name="estado" id="estado" class="form-control">
                                <option value="1" <? if(soloNumeros($_POST['estado']) == 1){ ?>selected="selected"<? } ?>>Activo</option>
                                <option value="0" <? if((string)eliminarInvalidos($_POST['estado']) === '0'){ ?>selected="selected"<? } ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input name="email" id="email" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['email']);?>" class="form-control" />
                        </div>
                        <div class="form-group full">
                            <label class="form-label" for="observaciones">Observaciones</label>
                            <input name="observaciones" id="observaciones" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['observaciones']);?>" class="form-control" />
                        </div>
                    </div>
                    <div class="actions">
                        <input type="hidden" name="funcion" id="funcion" value="insertar" />
                        <input type="submit" value="Guardar cambios" class="btn btn-success" />
                        <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
} else {
    if (isset($_GET['id']) && soloNumeros($_GET['id']) > 0) {
        $id = soloNumeros($_GET['id']);

        if (isset($_POST['nombre']) && $_POST['funcion'] == 'actualizar') {
            $sql = 'UPDATE personal SET ' .
                'cc="' . eliminarInvalidos($_POST['cc']) . '",' .
                'nombres="' . eliminarInvalidos($_POST['nombre']) . '",' .
                'tel="' . soloNumeros($_POST['telefono']) . '",' .
                'direccion="' . eliminarInvalidos($_POST['direccion']) . '",' .
                'cel="' . soloNumeros($_POST['celular']) . '",' .
                'email="' . eliminarInvalidos($_POST['email']) . '",' .
                'estado="' . soloNumeros($_POST['estado']) . '",' .
                'observaciones="' . eliminarInvalidos($_POST['observaciones']) . '",' .
                'id_tipo_personal="' . soloNumeros($_POST['perfil']) . '" ' .
                'WHERE id=' . $id;
            $PSN->query($sql);
            ?>
            <script>
                alert('Se ha ACTUALIZADO correctamente el registro!');
                window.location.href = 'index.php?doc=Personal&opc=2&id=<?=$id;?>';
            </script>
            <?php
        }

        $PSN->query("SELECT * FROM personal WHERE id='" . $id . "'");
        if ($PSN->num_rows() > 0 && $PSN->next_record()) {
            personal_render_styles();
            ?>
            <div id="personal-modern">
                <div class="card">
                    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <div class="form-group full">
                            <h2 class="alert alert-info text-center">.ACTUALIZACION DE <?=$nombreConsulta;?> EN EL SISTEMA.</h2>
                        </div>
                        <div class="grid">
                            <div class="form-group">
                                <label class="form-label" for="perfil">Perfil</label>
                                <select name="perfil" id="perfil" class="form-control">
                                    <?php
                                    $tmp = new DBbase_Sql;
                                    $tmp->query("SELECT * FROM categorias WHERE idSec=36 ORDER BY descripcion ASC");
                                    while ($tmp->next_record()) {
                                        $sel = ($PSN->f('id_tipo_personal') == $tmp->f('id')) ? 'selected="selected"' : '';
                                        echo '<option value="' . $tmp->f('id') . '" ' . $sel . '>' . $tmp->f('descripcion') . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label" for="nombre">Nombre</label><input name="nombre" id="nombre" type="text" maxlength="250" value="<?=$PSN->f('nombres');?>" class="form-control" /></div>
                            <div class="form-group"><label class="form-label" for="cc">CC</label><input name="cc" id="cc" type="text" maxlength="250" value="<?=$PSN->f('cc');?>" class="form-control" /></div>
                            <div class="form-group"><label class="form-label" for="telefono">Telefono</label><input name="telefono" id="telefono" type="text" maxlength="250" value="<?=$PSN->f('tel');?>" class="form-control" /></div>
                            <div class="form-group"><label class="form-label" for="direccion">Direccion</label><input name="direccion" id="direccion" type="text" maxlength="250" value="<?=$PSN->f('direccion');?>" class="form-control" /></div>
                            <div class="form-group"><label class="form-label" for="celular">Celular</label><input name="celular" id="celular" type="text" maxlength="250" value="<?=$PSN->f('cel');?>" class="form-control" /></div>
                            <div class="form-group"><label class="form-label" for="estado">Estado</label><select name="estado" id="estado" class="form-control"><option value="1" <? if($PSN->f('estado') == 1){ ?>selected="selected"<? } ?>>Activo</option><option value="0" <? if($PSN->f('estado') != 1){ ?>selected="selected"<? } ?>>Inactivo</option></select></div>
                            <div class="form-group"><label class="form-label" for="email">Email</label><input name="email" id="email" type="text" maxlength="250" value="<?=$PSN->f('email');?>" class="form-control" /></div>
                            <div class="form-group full"><label class="form-label" for="observaciones">Observaciones</label><input name="observaciones" id="observaciones" type="text" maxlength="250" value="<?=$PSN->f('observaciones');?>" class="form-control" /></div>
                        </div>
                        <div class="actions">
                            <input type="hidden" name="funcion" id="funcion" value="actualizar" />
                            <input type="submit" value="Guardar cambios" class="btn btn-success" />
                            <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        } else {
            echo '<div class="row"><h2><font color="#FF0000">ID Incorrecto. No Existe o no esta autorizado para visualizar la misma.</font></h2></div>';
        }
    } else {
        $registros = 50;
        $pagina = soloNumeros($_GET['pagina']);
        if (!$pagina) {
            $inicio = 0;
            $pagina = 1;
        } else {
            $inicio = ($pagina - 1) * $registros;
        }

        $sqlB = "SELECT personal.*, categorias.descripcion FROM personal LEFT JOIN categorias ON categorias.id = personal.id_tipo_personal WHERE 1 ";
        $sqlC = "SELECT count(personal.id) as conteo FROM personal WHERE 1 ";
        $sqlFiltros = '';

        if (eliminarInvalidos($_GET['nombre']) != '') {
            $sqlFiltros .= " AND personal.nombres LIKE '%" . eliminarInvalidos($_GET['nombre']) . "%'";
        }
        if (eliminarInvalidos($_GET['cc']) != '') {
            $sqlFiltros .= " AND personal.cc LIKE '%" . eliminarInvalidos($_GET['cc']) . "%'";
        }
        if (soloNumeros($_GET['estado']) != '') {
            $sqlFiltros .= " AND personal.estado = " . soloNumeros($_GET['estado']);
        }
        if (soloNumeros($_GET['perfil']) != '') {
            $sqlFiltros .= " AND personal.id_tipo_personal = " . soloNumeros($_GET['perfil']);
        }

        $sqlOrden = " ORDER BY personal.nombres ASC";

        $PSN->query($sqlC . $sqlFiltros);
        $numTotal = 0;
        if ($PSN->num_rows() > 0 && $PSN->next_record()) {
            $numTotal = $PSN->f('conteo');
        }

        $total_paginas = ceil($numTotal / $registros);
        $PSN->query($sqlB . $sqlFiltros . $sqlOrden . " LIMIT " . $inicio . ", " . $registros);
        $numclientes = $PSN->num_rows();

        personal_render_styles();
        ?>
        <div id="personal-modern">
            <div class="card">
                <form action="index.php" name="form" id="form" method="get" class="form">
                    <input type="hidden" name="doc" value="<?=$webArchivo;?>" />
                    <input type="hidden" name="opc" value="2" />

                    <div class="form-group full">
                        <h2 class="text-center well">.FILTROS DE BUSQUEDA - <?=$nombreConsulta;?>.</h2>
                    </div>

                    <div class="grid filters">
                        <div class="form-group"><label class="form-label" for="nombre">Nombre</label><input type="text" name="nombre" id="nombre" value="<?=eliminarInvalidos($_GET['nombre']);?>" class="form-control" /></div>
                        <div class="form-group"><label class="form-label" for="cc">CC</label><input type="text" name="cc" id="cc" value="<?=eliminarInvalidos($_GET['cc']);?>" class="form-control" /></div>
                        <div class="form-group">
                            <label class="form-label" for="perfil">Perfil</label>
                            <select name="perfil" id="perfil" class="form-control">
                                <option value="">Todos</option>
                                <?php
                                $tmp = new DBbase_Sql;
                                $tmp->query("SELECT * FROM categorias WHERE idSec=36 ORDER BY descripcion ASC");
                                while ($tmp->next_record()) {
                                    $sel = (soloNumeros($_GET['perfil']) == $tmp->f('id')) ? 'selected="selected"' : '';
                                    echo '<option value="' . $tmp->f('id') . '" ' . $sel . '>' . $tmp->f('descripcion') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="estado">Estado</label>
                            <select name="estado" id="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="1" <? if(soloNumeros($_GET['estado']) == 1){ ?>selected="selected"<? } ?>>Activo</option>
                                <option value="0" <? if((string)eliminarInvalidos($_GET['estado']) === '0'){ ?>selected="selected"<? } ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="actions">
                        <input type="submit" value="Buscar" class="btn btn-success" />
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="row"><h2 class="text-center well">.Se encontraron <?=intval($numTotal);?> registros.</h2></div>
                <div class="table-wrap">
                    <table border="0" cellspacing="0" cellpadding="2" align="center" class="table table-bordered" style="font-size:12px">
                        <thead>
                        <tr>
                            <th align="center">CC</th>
                            <th align="center">Nombres</th>
                            <th align="center">Perfil</th>
                            <th align="center">Celular</th>
                            <th align="center">Email</th>
                            <th align="center">Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($numclientes > 0) {
                            $contador = $inicio + 1;
                            while ($PSN->next_record()) {
                                $westado_terminado = $PSN->f('estado');
                                $bg = ($westado_terminado == 0) ? '#ffea61' : (($contador % 2 == 0) ? '#EEEEEE' : '#FFFFFF');
                                ?>
                                <tr bgcolor="<?=$bg;?>">
                                    <td><?=$PSN->f('cc');?></td>
                                    <td><a href="index.php?doc=<?=$webArchivo;?>&opc=2&id=<?=$PSN->f('id');?>"><?=$PSN->f('nombres');?></a></td>
                                    <td><?=$PSN->f('descripcion');?></td>
                                    <td><?=$PSN->f('cel');?></td>
                                    <td><?=$PSN->f('email');?></td>
                                    <td><? if($PSN->f('estado') == 1){ echo 'Activo'; } else { echo 'Inactivo'; } ?></td>
                                </tr>
                                <?php
                                $contador++;
                            }
                        } else {
                            ?><tr><td colspan="10" align="center"><h2>.No hay registros.</h2></td></tr><?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrap">
                    <ul class="pagination">
                        <?php
                        $paginaActualTxT = '&pagina=' . $pagina;
                        $_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT, '', $_SERVER['REQUEST_URI']);

                        if (($pagina - 1) > 0) {
                            echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=" . ($pagina - 1) . "'>&laquo;</a></li>";
                        }

                        for ($i = 1; $i <= $total_paginas; $i++) {
                            if ($pagina == $i) {
                                echo "<li class='active'><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=$i'>$i</a>";
                            } else {
                                echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=$i'>$i</a></li>";
                            }
                        }

                        if (($pagina + 1) <= $total_paginas) {
                            echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=" . ($pagina + 1) . "'>&raquo;</a></li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}
?>