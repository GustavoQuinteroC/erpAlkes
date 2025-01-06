<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

//calcular nombres de modulos semidinamicamente
$modulo = $ruta[(count($ruta) - 2)];
$submodulo = $ruta[(count($ruta) - 1)];
$subsubmodulo = null;
$pathControlador = __DIR__ . "/../../../controladores/individuales/$modulo.$submodulo.php";
// Verifica si el archivo existe antes de incluirlo
if (file_exists($pathControlador)) {
    require_once($pathControlador);
    validarSesion();
    echo $jaxon->getScript(true);
    $_SESSION['partidasImpuestos'.$_GET['rand']] = array();
} else {
    // Manejo de errores si el archivo controlador no existe
    die("No se encontró el archivo del controlador en: $pathControlador");
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= getBackground(); ?>">

<head>
    <?= headHtml($modulo, $submodulo); ?>
</head>

<body class="layout-fixed-complete sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?= menuLateral($modulo, $submodulo, $subsubmodulo); ?>
        <div class="app-main-wrapper">
            <?= encabezado(); ?>
            <main class="app-main">
                <?= titulos($modulo, $submodulo, $subsubmodulo); ?>
                <div class="app-content">
                    <div class="container-fluid">
                        <form action="#" id="formProducto<?= $_GET['rand']; ?>" name="formProducto" method="post">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Datos generales</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-collapse" title="Collapse">
                                                    <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                    <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-remove" title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Primera columna -->
                                                <div class="col-md-5">
                                                    <div class="form-group row mb-3">
                                                        <label for="codigo_barras"
                                                            class="col-sm-3 col-form-label text-start">Código</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-upc-scan"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="codigo_barras" name="codigo_barras"
                                                                    placeholder="Código unico del producto">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="nombre"
                                                            class="col-sm-3 col-form-label text-start">Nombre</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-card-text"></i></span>
                                                                <input type="text" class="form-control" id="nombre"
                                                                    name="nombre"
                                                                    placeholder="Nombre unico del producto">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="marca"
                                                            class="col-sm-3 col-form-label text-start">Marca</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-shop"></i></span>
                                                                <input type="text" class="form-control" id="marca"
                                                                    name="marca" placeholder="Marca del producto">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="descripcion"
                                                            class="col-sm-3 col-form-label text-start">Descripción</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-pencil-fill"></i></span>
                                                                <textarea class="form-control" id="descripcion"
                                                                    name="descripcion" rows="8"
                                                                    placeholder="Descripción del producto"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Segunda columna -->
                                                <div class="col-md-7">
                                                    <div class="row">
                                                        <!-- Primera subcolumna de la segunda columna -->
                                                        <div class="col-sm-6">
                                                            <div class="form-group row mb-3">
                                                                <label for="estado"
                                                                    class="col-sm-4 col-form-label text-start">Estado</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-toggle-on"></i></span>
                                                                        <select id="estado" name="estado"
                                                                            class="form-select">
                                                                            <option value="activo" selected>Activo
                                                                            </option>
                                                                            <option value="inactivo">Inactivo</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="idtipo"
                                                                    class="col-sm-4 col-form-label text-start">Tipo</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-list"></i></span>
                                                                        <select id="idtipo" name="idtipo"
                                                                            class="form-select">
                                                                            <?php echo getProductosTipos(); ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="idcategoria"
                                                                    class="col-sm-4 col-form-label text-start">Categoría</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-box"></i></span>
                                                                        <select id="idcategoria" name="idcategoria"
                                                                            class="form-select"
                                                                            onchange="JaxonalmacenProductos.actualizaSubCategorias(this.value)">
                                                                            <?php echo getCategorias(); ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="idsubcategoria"
                                                                    class="col-sm-4 col-form-label text-start">Subcategoría</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-box-seam"></i></span>
                                                                        <select id="idsubcategoria"
                                                                            name="idsubcategoria" class="form-select"
                                                                            onchange="JaxonalmacenProductos.actualizaSubSubCategorias(this.value)">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="idsubsubcategoria"
                                                                    class="col-sm-4 col-form-label text-start">Subsubcategoría</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-box2"></i></span>
                                                                        <select id="idsubsubcategoria"
                                                                            name="idsubsubcategoria"
                                                                            class="form-select">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="lote_serie"
                                                                    class="col-sm-4 col-form-label text-start">Lote o
                                                                    Serie</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-list-check"></i></span>
                                                                        <select id="lote_serie" name="lote_serie"
                                                                            class="form-select">
                                                                            <option value="Sí" selected>Sí</option>
                                                                            <option value="No">No</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="kit"
                                                                    class="col-sm-4 col-form-label text-start">Kit</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-dropbox"></i></span>
                                                                        <select id="kit" name="kit"
                                                                            class="form-select">
                                                                            <option value="Sí" selected>Sí</option>
                                                                            <option value="No">No</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Segunda subcolumna de la segunda columna -->
                                                        <div class="col-sm-6">
                                                            <div class="form-group row mb-3">
                                                                <label for="costo"
                                                                    class="col-sm-4 col-form-label text-start">Costo</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-currency-dollar"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="costo" name="costo" placeholder="Costo">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="costo2"
                                                                    class="col-sm-4 col-form-label text-start">Costo
                                                                    2</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-currency-dollar"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="costo2" name="costo2"
                                                                            placeholder="Costo 2">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="costo3"
                                                                    class="col-sm-4 col-form-label text-start">Costo
                                                                    3</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-currency-dollar"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="costo3" name="costo3"
                                                                            placeholder="Costo 3">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="precio"
                                                                    class="col-sm-4 col-form-label text-start">Precio</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-tag"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="precio" name="precio"
                                                                            placeholder="Precio">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="precio2"
                                                                    class="col-sm-4 col-form-label text-start">Precio
                                                                    2</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-tag"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="precio2" name="precio2"
                                                                            placeholder="Precio 2">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="precio3"
                                                                    class="col-sm-4 col-form-label text-start">Precio
                                                                    3</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-tag"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="precio3" name="precio3"
                                                                            placeholder="Precio 3">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Datos fiscales</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-collapse" title="Collapse">
                                                    <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                    <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-remove" title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Primera columna -->
                                                <div class="col-md-6">
                                                    <!-- Unidad -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idunidad"
                                                            class="col-sm-3 col-form-label text-start">Unidad</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-box"></i></span>
                                                                <select id="idunidad" class="form-select select2-field"
                                                                    name="idunidad">
                                                                    <?php echo getCfdiClaveUnidades(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Segunda columna -->
                                                <div class="col-md-6">
                                                    <!-- Moneda -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idmoneda"
                                                            class="col-sm-3 col-form-label text-start">Moneda</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-currency-dollar"></i></span>
                                                                <select id="idmoneda" name="idmoneda"
                                                                    class="form-select select2-field">
                                                                    <?php echo getCfdiMoneda(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <!-- Etiqueta -->
                                                <label for="idclave_producto_servicio"
                                                    class="col-sm-2 col-form-label text-start">Clave SAT</label>

                                                <!-- Input de clave -->
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                        <input type="text" id="idclave_producto_servicio"
                                                            name="idclave_producto_servicio" class="form-control"
                                                            placeholder="Ingresa la clave del producto o servicio"
                                                            onchange="JaxonalmacenProductos.validarClaveSat(this.value)">
                                                    </div>
                                                </div>

                                                <!-- Input de descripción -->
                                                <div class="col-md-7">
                                                    <input type="text" id="descripcion_producto_servicio"
                                                        class="form-control text-muted"
                                                        placeholder="Descripción de la clave ingresada" readonly>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Impuestos</h3>
                                            <div class="card-tools d-flex align-items-center gap-2">
                                                <button tabindex="400" id="addImpuesto"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonalmacenProductos.modalAddImpuesto();"
                                                    type="button">
                                                    <span class="bi bi-plus-lg me-1"></span> Agregar
                                                </button>
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-collapse" title="Collapse">
                                                    <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                    <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                    data-lte-toggle="card-remove" title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <div id='tablaImpuestos' class="row">
                                                <!-- Contenido -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row mb-3">
                                <label for="notas" class="col-sm-4 col-form-label text-start">Notas del
                                    desarrollador:</label>
                                <div class="col-sm-8">
                                    <p id="notas" class="form-text text-muted">
                                        1- que cuando se active lote o serie se desactrive kit y cuando se active
                                        kit se
                                        desactive lote o serie.
                                    </p>
                                    <p id="notas" class="form-text text-muted">
                                        2- al guardar validar si si era lote entonces confirmar que todos los lotes
                                        de
                                        ese producto se iran a 0, tambien si desactrivado lotes y se quiere activar
                                        lanzar antes una alerta sobre que primero se tiene que bajar todas las
                                        xexistencias en todos los almacenes a 0 antes de poder activar lotes.
                                    </p>
                                    <p id="notas" class="form-text text-muted">
                                        3- Si el producto se quiere cambiar en cuestion a la configuracion de kit,
                                        validar si ya hay movimientos con ese idproducto y si lo hay mandar una
                                        alerta
                                        de que no se puede cambiar la configuracion por que hay movimientos ya con
                                        kit.
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="modales"></div>
            </main>
            <?= botones(); ?>
        </div>
    </div>
    <?= scriptsHtml(); ?>
</body>

</html>