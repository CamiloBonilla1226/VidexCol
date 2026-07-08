<div class="container">
<div class="row">
    <h3 class="alert alert-info text-center">INFORMES DE COORDINADOR</h3>
</div>

<form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">

<fieldset>
    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3 class="text-center">CONSULTAR INFORMES</h3>
            <p>Escoja una de las siguientes opciones</p>
        </div>
        <div class="hr"><hr></div>
    </div>
    <div class="cont-flex fl-cent">
            <a href="?doc=informe-coordinador-evangelistas" class="btn-mar btn btn-primary ">EVANGELISTAS<br><span class="btn-desc">Reporte</span></a>
            <a href="?doc=grafica-consolidado-lpp" class="btn-mar btn btn-danger">LA PEREGRINACIÓN<br><span class="btn-desc">DEL PRISIONERO</span></a>
            <a href="?doc=informe-coordinador-ecc" class="btn-mar btn btn-success">C&M<br><span class="btn-desc">(CAPACITAR Y MULTIPLICAR)</span></a>
            <a href="?doc=grafica-consolidado-proyecto-felipe" class="btn-mar btn btn-info">PROYECTO<br><span class="btn-desc">FELIPE</span></a>
            <a href="?doc=grafica-consolidado-instituto-biblico" class="btn-mar btn btn-warning">INSTITUTO<br><span class="btn-desc">BIBLICO</span></a>
    </div><br><br>
    <input type="hidden" name="funcion" id="funcion" value="" />
    <input type="hidden" name="generacion" id="generacion" value="<?=$idVehiculo; ?>" />
</form>