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
                                            <h3 class="card-title">Datos del Movimiento</h3>
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
                                                <!-- Columna izquierda -->
                                                <div class="col-md-6">
                                                    <!-- Campo: folio (input readonly) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="folio"
                                                            class="col-sm-4 col-form-label text-start">Folio</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-file-earmark-text"></i></span>
                                                                <input type="text" class="form-control" id="folio"
                                                                    name="folio" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: idsocio (select vacío con botón de búsqueda) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idsocio"
                                                            class="col-sm-4 col-form-label text-start">Socio</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person"></i></span>
                                                                <select id="idsocio" name="idsocio"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxonalmacenMovimientos.cargarSocio({ seleccion: this.value });">
                                                                    <?php echo getSocios(); ?>
                                                                </select>
                                                                <button class="btn btn-outline-secondary" type="button"
                                                                    title="Buscar socio"
                                                                    onclick="JaxonalmacenMovimientos.modalSeleccionarSocio();">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: idconcepto (select vacío) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idconcepto"
                                                            class="col-sm-4 col-form-label text-start">Concepto</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-list-ul"></i></span>
                                                                <select id="idconcepto" name="idconcepto"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxonalmacenMovimientos.deshabilitaConcepto();">
                                                                    <?php echo getAlmacenesMovimientosConceptos(); ?>
                                                                </select>
                                                                <!-- Botón para autorizar el cambio -->
                                                                <button class="btn btn-warning" type="button"
                                                                    id="cambiarConcepto"
                                                                    onclick="JaxonalmacenMovimientos.modalConfirmacionHabilitaConcepto();"
                                                                    disabled>
                                                                    <i class="bi bi-arrow-repeat"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: idalmacen (select vacío) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idalmacen"
                                                            class="col-sm-4 col-form-label text-start">Almacén</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <!-- Ícono de almacén -->
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-archive"></i></span>
                                                                <!-- Select de almacenes -->
                                                                <select id="idalmacen" name="idalmacen"
                                                                    class="form-select"
                                                                    onchange="JaxonalmacenMovimientos.deshabilitaAlmacen();">
                                                                    <?php echo getAlmacenesPorSucursal(); ?>
                                                                </select>
                                                                <!-- Botón para autorizar el cambio -->
                                                                <button class="btn btn-warning" type="button"
                                                                    id="cambiarAlmacen"
                                                                    onclick="JaxonalmacenMovimientos.modalConfirmacionHabilitaAlmacen();"
                                                                    disabled>
                                                                    <i class="bi bi-arrow-repeat"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Campo: fecha y hora (input de tipo fecha y hora) -->
                                                    <div class="form-group row mb-3">
                                                        <label for="fechahora"
                                                            class="col-sm-4 col-form-label text-start">Fecha y
                                                            Hora</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-calendar"></i></span>
                                                                <input type="datetime-local" class="form-control"
                                                                    id="fechahora" name="fechahora">
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
                                                                <input type="text" class="form-control" id="documento"
                                                                    name="documento">
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
                                                                <input type="text" class="form-control" id="referencia"
                                                                    name="referencia">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Columna derecha -->
                                                <div class="col-md-6">
                                                    <div class="form-group row mb-3">
                                                        <label for="iddireccion_origen"
                                                            class="col-sm-4 col-form-label text-start">Dirección
                                                            Origen</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-geo-alt"></i></span>
                                                                <select id="iddireccion_origen"
                                                                    name="iddireccion_origen" class="form-select"
                                                                    onchange="JaxonalmacenMovimientos.cambiarDireccion(this.value, 'origen');">
                                                                    <?php echo getDirecciones(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Textarea para detallar la dirección con icono de mapa -->
                                                    <div class="form-group row mb-3">
                                                        <label for="detalleDireccion_origen"
                                                            class="col-sm-4 col-form-label text-start">Detalle
                                                            Dirección de Origen</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-map"></i></span>
                                                                <textarea class="form-control"
                                                                    id="detalleDireccion_origen"
                                                                    name="detalleDireccion_origen" rows="4"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="iddireccion_destino"
                                                            class="col-sm-4 col-form-label text-start">Dirección
                                                            Destino</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-geo-alt"></i></span>
                                                                <select id="iddireccion_destino"
                                                                    name="iddireccion_destino" class="form-select"
                                                                    onchange="JaxonalmacenMovimientos.cambiarDireccion(this.value, 'destino');">
                                                                    <?php echo getDirecciones(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Textarea para detallar la dirección con icono de mapa -->
                                                    <div class="form-group row mb-3">
                                                        <label for="detalleDireccion_destino"
                                                            class="col-sm-4 col-form-label text-start">Detalle
                                                            Dirección de Destino</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-map"></i></span>
                                                                <textarea class="form-control"
                                                                    id="detalleDireccion_destino"
                                                                    name="detalleDireccion_destino" rows="4"></textarea>
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
                                                <button tabindex="400" id="addImpuesto" name="addImpuesto"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonalmacenMovimientos.modalSeleccionarProductos(jaxon.getFormValues('formulario<?= $_GET['rand'] ?>'));"
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
                                            <!-- Tabla con DataTables (inicialmente vacía) -->
                                            <table id="tablaPartidas" class="table table-striped table-hover display"
                                                style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Código de Barras</th>
                                                        <th>Nombre</th>
                                                        <th>Descripción</th>
                                                        <th>Unidad</th>
                                                        <th>Existencia</th>
                                                        <th>Cantidad</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Las filas se llenarán dinámicamente -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mb-3">
                                <label for="notas" class="col-sm-4 col-form-label text-start">Notas del
                                    desarrollador:</label>
                                <div class="col-sm-8">
                                    <p id="notas" class="form-text text-muted">
                                        1- al procesar el movimiento volver a comprobar la existencia en caso de ser un
                                        movimiento de tipo salida.
                                        1- validar existencia en partidas.
                                        1- hacer una funcion validacantidadpartida y hacer todas las actualizaciones
                                        desde esa funcion, esa funcion se encargara de hacer la consulta a la base de
                                        datos sobre el inventario y en caso de no ajustar, entonces pondra tanto la
                                        partida como sus lotes en 0.
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
        JaxonalmacenMovimientos.inializarFormulario();
    </script>
    <!-- Inicializar DataTable -->
    <script>
        // Inicializar DataTable
        var tablaPartidas = $('#tablaPartidas').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' // Español
            },
            responsive: true,
            ordering: true,
            searching: true,
            paging: false,
            info: false,
            lengthChange: false
        });
    </script>
</body>

</html>