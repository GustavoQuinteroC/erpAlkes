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
    $_SESSION['partidas' . $_GET['rand']] = array();
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
                        <form action="#" id="formulario<?= $_GET['rand']; ?>" name="formulario<?= $_GET['rand']; ?>"
                            method="post">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Información general</h3>
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
                                                    <!-- Campo: folio (input readonly) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="folio"
                                                            class="col-sm-2 col-form-label text-start">Folio</label>
                                                        <div class="col-sm-10">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-file-earmark-text"></i></span>
                                                                <input type="text" class="form-control" id="folio"
                                                                    name="folio" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: Solicita (input readonly) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="solicita"
                                                            class="col-sm-2 col-form-label text-start">Solicita</label>
                                                        <div class="col-sm-10">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person-badge"></i></span>
                                                                <input type="text" class="form-control" id="solicita"
                                                                    name="solicita" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: iddepartamento-->
                                                    <div class="form-group row mb-3">
                                                        <label for="iddepartamento"
                                                            class="col-sm-2 col-form-label text-start">Departamento</label>
                                                        <div class="col-sm-10">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-building"></i></span>
                                                                <select id="iddepartamento" name="iddepartamento"
                                                                    class="form-select select2-field">
                                                                    <?php echo getDepartamentos(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fila interna con dos columnas: Registro y Vencimiento -->
                                                    <div class="row">
                                                        <!-- Columna: Registro -->
                                                        <div class="col-md-6">
                                                            <!-- Información monetaria -->
                                                            <div class="form-group row mb-3">
                                                                <label for="idc_moneda"
                                                                    class="col-sm-4 col-form-label text-start">Moneda</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-currency-exchange"></i></span>
                                                                        <select id="idc_moneda" name="idc_moneda"
                                                                            class="form-select select2-field">
                                                                            <?php echo getCfdiMoneda(); ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="registro"
                                                                    class="col-sm-4 col-form-label text-start">Registro</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-calendar"></i></span>
                                                                        <input type="datetime-local"
                                                                            class="form-control" id="registro"
                                                                            name="registro">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Campo: referencia (input) -->
                                                            <div class="form-group row mb-3">
                                                                <label for="referencia"
                                                                    class="col-sm-4 col-form-label text-start">Referencia</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-link-45deg"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="referencia" name="referencia">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Columna: Vencimiento -->
                                                        <div class="col-md-6">
                                                            <!-- Campo: idc_metodopago-->
                                                            <div class="form-group row mb-3">
                                                                <label for="idc_metodopago"
                                                                    class="col-sm-4 col-form-label text-start">Método
                                                                    Pago</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-credit-card"></i></span>
                                                                        <select id="idc_metodopago"
                                                                            name="idc_metodopago" class="form-select">
                                                                            <?php echo getCfdiMetodoPago(); ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row mb-3">
                                                                <label for="vencimiento"
                                                                    class="col-sm-4 col-form-label text-start">Vencimiento</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-calendar-x"></i></span>
                                                                        <input type="datetime-local"
                                                                            class="form-control" id="vencimiento"
                                                                            name="vencimiento">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Campo: documento (input) -->
                                                            <div class="form-group row mb-3">
                                                                <label for="documento"
                                                                    class="col-sm-4 col-form-label text-start">Documento</label>
                                                                <div class="col-sm-8">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><i
                                                                                class="bi bi-file-earmark"></i></span>
                                                                        <input type="text" class="form-control"
                                                                            id="documento" name="documento">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Segunda columna -->
                                                <div class="col-md-6">
                                                    <!-- Campo: idsocio (select vacío con botón de búsqueda) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idsocio"
                                                            class="col-sm-4 col-form-label text-start">Proveedor</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person"></i></span>
                                                                <select id="idsocio" name="idsocio"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxoncomprasRequisiciones.cargarSocio({ seleccion: this.value });">
                                                                    <?php echo getProveedores(); ?>
                                                                </select>
                                                                <button class="btn btn-outline-secondary" type="button"
                                                                    id="botonBuscarSocio" name="botonBuscarSocio"
                                                                    title="Buscar socio"
                                                                    onclick="JaxoncomprasRequisiciones.modalSeleccionarSocio();">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: idsubcuenta (select vacío con botón de búsqueda) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idsubcuenta"
                                                            class="col-sm-4 col-form-label text-start">Subcuenta</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person"></i></span>
                                                                <select id="idsubcuenta" name="idsubcuenta"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxoncomprasRequisiciones.cargarSubcuenta({ seleccion: this.value }, document.getElementById('idsocio').value);">
                                                                </select>
                                                                <button class="btn btn-outline-secondary" type="button"
                                                                    id="botonBuscarSubcuenta"
                                                                    name="botonBuscarSubcuenta" title="Buscar subcuenta"
                                                                    onclick="JaxoncomprasRequisiciones.modalSeleccionarSubcuenta(document.getElementById('idsocio').value);">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="iddireccion_entrega"
                                                            class="col-sm-4 col-form-label text-start">Dirección de
                                                            entrega</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-geo-alt"></i></span>
                                                                <select id="iddireccion_entrega"
                                                                    name="iddireccion_entrega" class="form-select"
                                                                    onchange="JaxoncomprasRequisiciones.cambiarDireccion(this.value, 'origen');">
                                                                    <?php echo getDirecciones(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Textarea para detallar la dirección con icono de mapa -->
                                                    <div class="form-group row mb-3">
                                                        <label for="detalleDireccion_entrega"
                                                            class="col-sm-4 col-form-label text-start">Detalle
                                                            Dirección de Entrega</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-map"></i></span>
                                                                <textarea class="form-control" readOnly
                                                                    id="detalleDireccion_entrega"
                                                                    name="detalleDireccion_entrega" rows="5"></textarea>
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
                                            <h3 class="card-title">Productos agregados</h3>
                                            <div class="card-tools d-flex align-items-center gap-2">
                                                <button id="addPartidas" name="addPartidas"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    type="button"
                                                    onclick="JaxoncomprasRequisiciones.modalSeleccionarProductos(jaxon.getFormValues('formulario<?= $_GET['rand'] ?>'));">
                                                    <span class="bi bi-plus-lg me-1"></span> Productos
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
                                            <div class="table-responsive">
                                                <table id="tablaPartidas"
                                                    class="table table-striped table-hover display" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Partida</th>
                                                            <th>Código de Barras</th>
                                                            <th>Nombre</th>
                                                            <th>Descripción</th>
                                                            <th>Unidad</th>
                                                            <th>Cantidad</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- filas dinámicas -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="card-footer border-top pt-4">
                                            <div class="text-center mb-3">
                                                <h6 class="fw-semibold text-muted mb-0">
                                                    Añadir producto sin registro en el sistema
                                                </h6>
                                            </div>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-sm-2">
                                                    <label for="nuevo_codigo_barras" class="form-label">Código de
                                                        Barras</label>
                                                    <input type="text" id="nuevo_codigo_barras"
                                                        name="nuevo_codigo_barras" class="form-control" />
                                                </div>
                                                <div class="col-sm-2">
                                                    <label for="nuevo_nombre" class="form-label">Nombre</label>
                                                    <input type="text" id="nuevo_nombre" name="nuevo_nombre"
                                                        class="form-control" />
                                                </div>
                                                <div class="col-sm-4">
                                                    <label for="nuevo_descripcion"
                                                        class="form-label">Descripción</label>
                                                    <input type="text" id="nuevo_descripcion" name="nuevo_descripcion"
                                                        class="form-control" />
                                                </div>
                                                <div class="col-sm-2">
                                                    <label for="nuevo_unidad" class="form-label">Unidad</label>
                                                    <select id="nuevo_unidad" name="nuevo_unidad"
                                                        class="form-select select2-field">
                                                        <?php echo getCfdiClaveUnidades(); ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-1">
                                                    <label for="nuevo_lote_serie" class="form-label">¿Maneja lotes o
                                                        series?</label>
                                                    <select id="nuevo_lote_serie" name="nuevo_lote_serie"
                                                        class="form-select">
                                                        <option value>Elige una opción</option>
                                                        <option value="Sí">Sí</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-1">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="JaxoncomprasRequisiciones.agregarProductoSinRegistro(jaxon.getFormValues('formulario<?= $_GET['rand'] ?>'));">
                                                        <i class="bi bi-plus-lg me-1"></i> Producto
                                                    </button>
                                                </div>
                                            </div>
                                        </div> <!-- card-footer -->
                                    </div> <!-- card -->
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <div class="form-group row mb-3">
                                                <label for="notas"
                                                    class="col-sm-2 col-form-label text-start">Notas</label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" id="notas" name="notas" rows="5"
                                                        placeholder="Escribe tus notas aquí..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
        JaxoncomprasRequisiciones.inializarFormulario();
    </script>
    <!-- Inicializar DataTable -->
    <script>
        // Inicializar DataTable
        var tablaPartidas = $('#tablaPartidas').DataTable({
            language: {
                url: '/plugins/datatables/es-ES.json' // Español
            },
            responsive: false,
            ordering: true,
            searching: true,
            paging: false,
            info: false,
            autoWidth: false,
            scrollX: false,
            lengthChange: false
        });
    </script>
    <script>
        $(function () {
            $("#nuevo_codigo_barras").autocomplete({
                source: "/controladores/autocompletes/productos.php?columna=codigo_barras",
                minLength: 2,
                select: function (event, ui) {
                    JaxoncomprasRequisiciones.cargarProductoAutocomplete(ui.item.id); // Llamada Jaxon al seleccionar
                    return false;
                }
            });
        });
    </script>
    <script>
        $(function () {
            $("#nuevo_nombre").autocomplete({
                source: "/controladores/autocompletes/productos.php?columna=nombre",
                minLength: 2,
                select: function (event, ui) {
                    JaxoncomprasRequisiciones.cargarProductoAutocomplete(ui.item.id); // Llamada Jaxon al seleccionar
                    return false;
                }
            });
        });
    </script>
    <script>
        $(function () {
            $("#nuevo_descripcion").autocomplete({
                source: "/controladores/autocompletes/productos.php?columna=descripcion",
                minLength: 2,
                select: function (event, ui) {
                    JaxoncomprasRequisiciones.cargarProductoAutocomplete(ui.item.id); // Llamada Jaxon al seleccionar
                    return false;
                }
            });
        });
    </script>
</body>

</html>