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
    $_SESSION['partidasImpuestos' . $_GET['rand']] = array();
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
                        <form action="#" id="formProducto<?= $_GET['rand']; ?>" name="formProducto<?= $_GET['rand']; ?>"
                            method="post">
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
                                                <div class="col-md-6">
                                                    <div class="form-group row mb-3">
                                                        <label for="nombre"
                                                            class="col-sm-4 col-form-label text-start">Nombre</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-upc-scan"></i></span>
                                                                <input type="text" class="form-control" id="nombre"
                                                                    name="nombre" placeholder="Nombre del almacén">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="direccion"
                                                            class="col-sm-4 col-form-label text-start">Direccion</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-upc-scan"></i></span>
                                                                <input type="text" class="form-control" id="direccion"
                                                                    name="direccion"
                                                                    placeholder="Dirección del almacén">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="capacidad"
                                                            class="col-sm-4 col-form-label text-start">Capacidad en
                                                            m&sup3;</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-upc-scan"></i></span>
                                                                <input type="text" class="form-control" id="nombre"
                                                                    name="capacidad"
                                                                    placeholder="capacidad del almacén en metros cubicos">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="estado"
                                                            class="col-sm-4 col-form-label text-start">Estado</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-toggle-on"></i></span>
                                                                <select id="estado" name="estado" class="form-select">
                                                                    <option value="Activo" selected>Activo
                                                                    </option>
                                                                    <option value="Inactivo">Inactivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group row mb-3">
                                                        <label for="identidad"
                                                            class="col-sm-4 col-form-label text-start">Entidad</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-box"></i></span>
                                                                <select id="identidad" name="identidad"
                                                                    class="form-select select2-field">
                                                                    <?php echo getEntidades(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idusuario"
                                                            class="col-sm-4 col-form-label text-start">Usuario
                                                            asignado</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-box"></i></span>
                                                                <select id="idusuario" name="idusuario"
                                                                    class="form-select select2-field">
                                                                    <?php echo getUsuarios(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="principal"
                                                            class="col-sm-4 col-form-label text-start">Principal por entidad</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-ui-checks"></i></span>
                                                                <select id="principal" name="principal" class="form-select">
                                                                    <option value="No" selected>No</option>
                                                                    <option value="Sí">Sí</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="consigna"
                                                            class="col-sm-4 col-form-label text-start">Es consigna</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-ui-checks"></i></span>
                                                                <select id="consigna" name="consigna" class="form-select">
                                                                    <option value="No" selected>No</option>
                                                                    <option value="Sí">Sí</option>
                                                                </select>
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
                                            <h3 class="card-title">Productos asignados</h3>
                                            <div class="card-tools d-flex align-items-center gap-2">
                                                <button tabindex="400" id="addImpuesto" name="addImpuesto"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonalmacenAlmacenes.modalAddProducto();" type="button">
                                                    <span class="bi bi-plus-lg me-1"></span> Agregar
                                                </button>
                                                <button tabindex="400" id="addImpuesto" name="addImpuesto"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonalmacenAlmacenes.asignarTodos();" type="button">
                                                    <span class="bi bi-plus-lg me-1"></span> Asignar todos
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
                                            <div id='tablaImpuestos' name='tablaImpuestos' class="row">
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
                                        1- de alguna forma hacer que el usuario con permisos pueda cambiar entre
                                        almacenes de diferentes entidades.
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
    <script>
        JaxonalmacenAlmacenes.inializarFormulario();
    </script>
</body>

</html>