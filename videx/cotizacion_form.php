<?php

function renderCotizacionReadonlyWarning()
{
    ?>
    <div class="row">
        <h5 class="alert alert-danger text-center"><strong>Atencion:</strong> <?php echo htmlspecialchars(COTIZACION_MESSAGES['readonly']); ?></h5>
    </div>
    <?php
}

function renderCotizacionUnauthorizedMessage()
{
    die('<h1>' . htmlspecialchars(COTIZACION_MESSAGES['unauthorized']) . '</h1>');
}

function renderCotizacionExcelTable(array $rows, $inicio)
{
    $num = count($rows);
    $totalRegistros = $num;
    ?>
    <table>
        <tr>
            <th colspan="15">Se han encontrado <?php echo intval($totalRegistros); ?> registros.</th>
        </tr>
        <tr>
            <th>No</th>
            <th>ID en la Base de Datos</th>
            <th>Prospecto</th>
            <th>Estado</th>
            <th>Estado Pago</th>
            <th>Categoria Principal</th>
            <th>Metodo Cotizacion</th>
            <th>Contacto Nombre</th>
            <th>Valor Cotizado</th>
            <th>Valor Cobrado</th>
            <th>Abonos recibidos</th>
            <th>Saldo</th>
            <th>Fecha Cotizacion</th>
            <th>Fecha Facturacion</th>
            <th>Fecha Ultimo Pago</th>
        </tr>
        <?php if ($num > 0): ?>
            <?php $contador = $inicio + 1; ?>
            <?php foreach ($rows as $row): ?>
                <tr <?php if ($contador % 2 == 0) { ?>bgcolor="#EEEEEE"<?php } ?> <?php if (($row['estadoC'] ?? 1) != 1) { echo "style='background:#f5c6cb;'"; } ?>>
                    <td><?php echo $contador; ?></td>
                    <td><?php echo $row['id'] ?? ''; ?></td>
                    <td><?php echo $row['nombreCliente'] ?? ''; ?></td>
                    <td align="center" style="white-space:nowrap;"><?php echo $row['nomEstado'] ?? ''; ?></td>
                    <td align="center"><?php echo $row['nomEstadoPago'] ?? ''; ?></td>
                    <td><?php echo $row['nomCategoriaPrincipal'] ?? ''; ?></td>
                    <td align="center"><?php echo $row['nomMetodoCotizacion'] ?? ''; ?></td>
                    <td><?php echo $row['contactoNombre'] ?? ''; ?></td>
                    <td align="right"><?php echo $row['valorCotizado'] ?? 0; ?></td>
                    <td align="right"><?php echo $row['valorCobrado'] ?? 0; ?></td>
                    <td><?php echo $row['totalAbonos'] ?? 0; ?></td>
                    <td><?php echo (($row['valorCobrado'] ?? 0) - ($row['totalAbonos'] ?? 0)); ?></td>
                    <td><?php echo $row['fechaCotizacion'] ?? ''; ?></td>
                    <td><?php echo $row['fechaFacturacion'] ?? ''; ?></td>
                    <td><?php echo $row['fechaUltimoPago'] ?? ''; ?></td>
                </tr>
                <?php $contador++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" align="center"><h2>.No hay registros.</h2></td>
            </tr>
        <?php endif; ?>
    </table>
    <br />
    <p align="left"></p>
    <?php
}

function renderCotizacionClientOption($client, $selectedId)
{
    ?>
    <option value="<?php echo htmlspecialchars($client['id']); ?>" <?php echo ((int) $selectedId === (int) $client['id']) ? 'selected="selected"' : ''; ?>>
        <?php echo htmlspecialchars($client['nombre'] . ' (' . ($client['totalCot'] ?? 0) . ')'); ?>
    </option>
    <?php
}

function renderCotizacionCategoryOptions(array $items, $selectedId)
{
    foreach ($items as $item) {
        ?>
        <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php echo ((int) $selectedId === (int) $item['id']) ? 'selected="selected"' : ''; ?>>
            <?php echo htmlspecialchars($item['descripcion']); ?>
        </option>
        <?php
    }
}

function renderCotizacionFilterForm(array $filters, array $clients, array $categories, $nombreConsulta, $activos, $webArchivo)
{
    ?>
    <div class="container cotizacion-shell" data-cotizacion-root="1">
        <div class="cotizacion-panel-header">
            <h2>.FILTROS DE BUSQUEDA - <?php echo htmlspecialchars($nombreConsulta); ?><?php if ($activos != 1) { echo ' - HISTORICO (INACTIVOS)'; } ?>.</h2>
            <div class="cotizacion-panel-subtitle">Filtra cotizaciones por prospecto, estado, fechas y categoria.</div>
        </div>

        <form action="index.php" name="form" id="form" method="get" class="form cotizacion-form-grid">
            <input type="hidden" name="doc" value="<?php echo htmlspecialchars($webArchivo); ?>" />
            <input type="hidden" name="opc" value="2" />
            <input type="hidden" name="activos" value="<?php echo intval($activos); ?>" />

            <div class="cotizacion-card">
                <h3>Filtros de consulta</h3>
                <div class="cotizacion-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="idCliente">Prospecto</label>
                        <select name="idCliente" id="idCliente" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($clients as $client): ?>
                                <?php renderCotizacionClientOption($client, $filters['idCliente'] ?? ''); ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="interno">¿Cliente ó propio?</label>
                        <select name="interno" id="interno" class="form-control">
                            <option value="">Todos</option>
                            <option value="0" <?php echo (($filters['interno'] ?? '') === '0') ? 'selected="selected"' : ''; ?>>Cliente</option>
                            <option value="1" <?php echo (($filters['interno'] ?? '') === 1 || ($filters['interno'] ?? '') === '1') ? 'selected="selected"' : ''; ?>>Propio</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoNombre">Nombre del contacto</label>
                        <input type="text" name="contactoNombre" id="contactoNombre" value="<?php echo htmlspecialchars($filters['contactoNombre'] ?? ''); ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoCelular">Celular del contacto</label>
                        <input type="text" name="contactoCelular" id="contactoCelular" value="<?php echo htmlspecialchars($filters['contactoCelular'] ?? ''); ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaInicial">Fecha inicial</label>
                        <input name="fechaInicial" type="date" id="fechaInicial" placeholder="AAAA-MM-DD" value="<?php echo htmlspecialchars($filters['fechaInicial'] ?? ''); ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaFinal">Fecha Final</label>
                        <input name="fechaFinal" type="date" id="fechaFinal" placeholder="AAAA-MM-DD" value="<?php echo htmlspecialchars($filters['fechaFinal'] ?? ''); ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estado">Estado de la cotizaci&oacute;n</label>
                        <select name="estado" id="estado" class="form-control">
                            <option value="">Todos</option>
                            <?php renderCotizacionCategoryOptions($categories['estado'] ?? [], $filters['estado'] ?? ''); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estadoPago">Estado del pago</label>
                        <select name="estadoPago" id="estadoPago" class="form-control">
                            <option value="">Todos</option>
                            <?php renderCotizacionCategoryOptions($categories['estadoPago'] ?? [], $filters['estadoPago'] ?? ''); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="categoriaPrincipal">Categoria</label>
                        <select name="categoriaPrincipal" id="categoriaPrincipal" class="form-control">
                            <option value="">Todos</option>
                            <?php renderCotizacionCategoryOptions($categories['categoriaPrincipal'] ?? [], $filters['categoriaPrincipal'] ?? ''); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="metodoCotizacion">Metodo de cotizaci&oacute;n</label>
                        <select name="metodoCotizacion" id="metodoCotizacion" class="form-control">
                            <option value="">Todos</option>
                            <?php renderCotizacionCategoryOptions($categories['metodoCotizacion'] ?? [], $filters['metodoCotizacion'] ?? ''); ?>
                        </select>
                    </div>
                </div>

                <div class="cotizacion-toolbar" style="justify-content:flex-end; margin-top:1rem;">
                    <button type="submit" class="btn btn-success">Buscar</button>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function renderCotizacionListTable(array $records, $totalRecords = null)
{
    ?>
    <div class="container cotizacion-shell" data-cotizacion-root="1">
        <div class="cotizacion-panel-header">
            <h2>Resultados de cotización</h2>
            <div class="cotizacion-panel-subtitle">Se encontraron <?php echo intval($totalRecords === null ? count($records) : $totalRecords); ?> registros.</div>
        </div>

        <div class="cotizacion-table-wrap">
            <table border="0" cellspacing="0" cellpadding="2" align="center" class="table table-bordered cotizacion-table-modern">
                <thead>
                <tr>
                    <th align="center">No</th>
                    <th align="center">Prospecto</th>
                    <th style="white-space:nowrap;" align="center">Estado</th>
                    <th style="white-space:nowrap;" align="center">Estado Pago</th>
                    <th align="center">Categoria Principal</th>
                    <th align="center">Metodo Cotizacion</th>
                    <th align="center">Contacto Nombre</th>
                    <th align="center">Valor Cotizado</th>
                    <th align="center">Valor Cobrado</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($records)): ?>
                    <?php $contador = 1; ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td data-label="No"><a href="index.php?doc=cotizacion&opc=2&id=<?php echo htmlspecialchars($record['id']); ?>"><?php echo $contador; ?></a></td>
                            <td data-label="Prospecto"><a href="index.php?doc=cotizacion&opc=2&id=<?php echo htmlspecialchars($record['id']); ?>"><?php echo htmlspecialchars($record['nombreCliente']); ?></a></td>
                            <td data-label="Estado" align="center" style="white-space:nowrap;"><a href="index.php?doc=cotizacion&opc=2&id=<?php echo htmlspecialchars($record['id']); ?>"><?php echo htmlspecialchars($record['nomEstado']); ?></a></td>
                            <td data-label="Estado Pago" align="center"><?php echo htmlspecialchars($record['nomEstadoPago']); ?></td>
                            <td data-label="Categoria Principal"><?php echo htmlspecialchars($record['nomCategoriaPrincipal']); ?></td>
                            <td data-label="Metodo Cotizacion" align="center"><?php echo htmlspecialchars($record['nomMetodoCotizacion']); ?></td>
                            <td data-label="Contacto Nombre"><?php echo htmlspecialchars($record['contactoNombre']); ?></td>
                            <td data-label="Valor Cotizado" align="right">$<?php echo number_format((float) $record['valorCotizado'], 0, ",", "."); ?></td>
                            <td data-label="Valor Cobrado" align="right">$<?php echo number_format((float) $record['valorCobrado'], 0, ",", "."); ?></td>
                        </tr>
                        <?php $contador++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="cotizacion-empty">No hay registros para mostrar.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function renderCotizacionPagination($currentPage, $totalPages, $requestUri)
{
    ?>
    <div class="container cotizacion-shell" data-cotizacion-root="1">
            <ul class="pagination">
                <?php if (($currentPage - 1) > 0): ?>
                    <li><a href="<?php echo htmlspecialchars($requestUri); ?>&pagina=<?php echo ($currentPage - 1); ?>">&laquo;</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($currentPage == $i): ?>
                        <li class='active'><a href="<?php echo htmlspecialchars($requestUri); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php else: ?>
                        <li><a href="<?php echo htmlspecialchars($requestUri); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if (($currentPage + 1) <= $totalPages): ?>
                    <li><a href="<?php echo htmlspecialchars($requestUri); ?>&pagina=<?php echo ($currentPage + 1); ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
    </div>
    <?php
}

function renderCotizacionExportButton(array $filters)
{
    $params = [
        'doc=cotizacion',
        'opc=3',
        'excelX=1',
        'excel=1',
    ];

    foreach ($filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[] = urlencode($key) . '=' . urlencode((string) $value);
        }
    }

    ?>
    <div class="cotizacion-toolbar" style="justify-content:flex-end; margin: 0 0 1rem;">
        <a href="index.php?<?php echo implode('&', $params); ?>" class="btn btn-info"><i class="bi bi-download"></i> DESCARGAR PARA EXCEL</a>
    </div>
    <?php
}

function cotizacionFormValue($array, $key, $default = '')
{
    return $array[$key] ?? $default;
}

function renderCotizacionRecordForm(array $context, $webArchivo, $recordId = 0, $readonly = false)
{
    $mode = $context['mode'] ?? 'create';
    $record = $context['record'] ?? [];
    $categories = $context['categories'] ?? [];
    $clients = $context['clientes'] ?? [];
    $isEdit = $mode === 'edit';
    ?>
    <div class="container cotizacion-shell" data-cotizacion-root="1">
        <div class="cotizacion-panel-header">
            <h2><?php echo $isEdit ? '.ACTUALIZACION DE COTIZACION.' : '.NUEVA COTIZACION.'; ?></h2>
            <div class="cotizacion-panel-subtitle">Gestion comercial en modo claro y estructura simplificada.</div>
        </div>

        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form cotizacion-form-grid">
            <input type="hidden" name="funcion" id="funcion" value="<?php echo $isEdit ? 'actualizar' : 'crear'; ?>" />
            <input type="hidden" name="idContacto" id="idContacto" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'idContacto', '')); ?>" />

            <div class="cotizacion-card">
                <h3>Datos del prospecto</h3>
                <div class="cotizacion-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="idCliente">Prospecto</label>
                        <select name="idCliente" id="idCliente" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo htmlspecialchars((string) $client['id']); ?>" <?php echo ((int) cotizacionFormValue($record, 'idCliente', 0) === (int) $client['id']) ? 'selected="selected"' : ''; ?>>
                                    <?php echo htmlspecialchars($client['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tipoContacto">Tipo de contacto</label>
                        <select name="tipoContacto" id="tipoContacto" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php renderCotizacionCategoryOptions($categories['tipoContacto'] ?? [], cotizacionFormValue($record, 'tipoContacto', '')); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoNombre">Nombre del contacto</label>
                        <input name="contactoNombre" id="contactoNombre" type="text" maxlength="250" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'contactoNombre', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoEmail">Email del contacto</label>
                        <input name="contactoEmail" id="contactoEmail" type="email" maxlength="250" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'contactoEmail', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoTelefono">Telefono del contacto</label>
                        <input name="contactoTelefono" id="contactoTelefono" type="text" maxlength="250" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'contactoTelefono', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactoCelular">Celular del contacto</label>
                        <input name="contactoCelular" id="contactoCelular" type="text" maxlength="250" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'contactoCelular', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>
                </div>
            </div>

            <div class="cotizacion-card">
                <h3>Estado y montos</h3>
                <div class="cotizacion-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado cotizacion</label>
                        <select name="estado" id="estado" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php renderCotizacionCategoryOptions($categories['estado'] ?? [], cotizacionFormValue($record, 'estado', '')); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estadoPago">Estado de pago</label>
                        <select name="estadoPago" id="estadoPago" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php renderCotizacionCategoryOptions($categories['estadoPago'] ?? [], cotizacionFormValue($record, 'estadoPago', '')); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="categoriaPrincipal">Categoria principal</label>
                        <select name="categoriaPrincipal" id="categoriaPrincipal" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php renderCotizacionCategoryOptions($categories['categoriaPrincipal'] ?? [], cotizacionFormValue($record, 'categoriaPrincipal', '')); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="metodoCotizacion">Metodo de cotizacion</label>
                        <select name="metodoCotizacion" id="metodoCotizacion" class="form-control" <?php echo $readonly ? 'disabled' : ''; ?>>
                            <option value="">Seleccione</option>
                            <?php renderCotizacionCategoryOptions($categories['metodoCotizacion'] ?? [], cotizacionFormValue($record, 'metodoCotizacion', '')); ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valorCotizado">Valor cotizado</label>
                        <input name="valorCotizado" id="valorCotizado" type="number" min="0" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'valorCotizado', 0)); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valorCobrado">Valor cobrado</label>
                        <input name="valorCobrado" id="valorCobrado" type="number" min="0" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'valorCobrado', 0)); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valorRecibido">Abonos recibidos</label>
                        <input name="valorRecibido" id="valorRecibido" type="number" min="0" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'valorRecibido', 0)); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaCotizacion">Fecha cotizacion</label>
                        <input name="fechaCotizacion" id="fechaCotizacion" type="date" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'fechaCotizacion', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaFacturacion">Fecha facturacion</label>
                        <input name="fechaFacturacion" id="fechaFacturacion" type="date" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'fechaFacturacion', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaUltimoPago">Fecha ultimo pago</label>
                        <input name="fechaUltimoPago" id="fechaUltimoPago" type="date" value="<?php echo htmlspecialchars((string) cotizacionFormValue($record, 'fechaUltimoPago', '')); ?>" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?> />
                    </div>
                </div>
            </div>

            <div class="cotizacion-card">
                <h3>Detalle comercial</h3>
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripcion</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?>><?php echo htmlspecialchars((string) cotizacionFormValue($record, 'descripcion', '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mensajecotizacion">Mensaje de cotizacion</label>
                    <textarea name="mensajecotizacion" id="mensajecotizacion" class="form-control" <?php echo $readonly ? 'readonly' : ''; ?>><?php echo htmlspecialchars((string) cotizacionFormValue($record, 'mensajecotizacion', '')); ?></textarea>
                </div>
            </div>

            <div class="cotizacion-toolbar">
                <?php if (!$readonly): ?>
                    <button type="submit" class="btn btn-success"><?php echo $isEdit ? 'Actualizar' : 'Guardar'; ?></button>
                    <?php if ($isEdit && (int) $recordId > 0): ?>
                        <button
                            type="submit"
                            class="btn btn-danger"
                            onclick="if(confirm('Confirma eliminar esta cotizacion?')){document.getElementById('funcion').value='eliminar';}else{return false;}"
                        >Eliminar</button>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="index.php?doc=<?php echo htmlspecialchars($webArchivo); ?>&opc=2" class="btn btn-default">Volver al listado</a>
                <?php if ($isEdit && (int) $recordId > 0): ?>
                    <button type="button" class="btn btn-info" onclick="asignarObservacion()">Seguimiento</button>
                    <button type="button" class="btn btn-info" onclick="asignarAbono()">Abono</button>
                    <button type="button" class="btn btn-info" onclick="asignarGasto()">Gasto</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Información Adicional - Embedded Sections -->
    <?php if ($isEdit && (int) $recordId > 0): ?>
    <div class="cotizacion-sections-container">

        <!-- Seguimientos Section -->
        <div class="cotizacion-section">
            <h3>Seguimientos</h3>
            <div class="cotizacion-section-content">
                <?php
                // Preserve original $_GET["id"]
                $original_get_id = $_GET["id"] ?? null;
                $_GET["id"] = (int) $recordId;
                include('int_seguimientos.php');
                // Restore original
                if ($original_get_id) $_GET["id"] = $original_get_id;
                else unset($_GET["id"]);
                ?>
            </div>
        </div>

        <!-- Gastos Section -->
        <div class="cotizacion-section">
            <h3>Gastos</h3>
            <div class="cotizacion-section-content">
                <?php
                // Preserve original $_GET["id"]
                $original_get_id = $_GET["id"] ?? null;
                $_GET["id"] = (int) $recordId;
                include('int_gastos.php');
                // Restore original
                if ($original_get_id) $_GET["id"] = $original_get_id;
                else unset($_GET["id"]);
                ?>
            </div>
        </div>

        <!-- Facturación Section -->
        <div class="cotizacion-section">
            <h3>Facturación</h3>
            <div class="cotizacion-section-content">
                <?php
                // Preserve original $_GET["id"]
                $original_get_id = $_GET["id"] ?? null;
                $_GET["id"] = (int) $recordId;
                include('int_facturacion.php');
                // Restore original
                if ($original_get_id) $_GET["id"] = $original_get_id;
                else unset($_GET["id"]);
                ?>
            </div>
        </div>

    </div>
    <?php endif; ?>
    <?php
}
