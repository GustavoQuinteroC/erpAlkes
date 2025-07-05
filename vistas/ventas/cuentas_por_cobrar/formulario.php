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
                                            <h3 class="card-title">Información de la Cuenta Por Cobrar</h3>
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
                                                <!-- Primera columna (más campos) -->
                                                <div class="col-md-6">
                                                    <!-- Identificación -->
                                                    <div class="form-group row mb-3">
                                                        <label for="folio"
                                                            class="col-sm-4 col-form-label text-start">Folio</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-hash"></i></span>
                                                                <input type="text" class="form-control" id="folio"
                                                                    name="folio"
                                                                    placeholder="Folio o número de referencia" readonly>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fechas -->
                                                    <div class="form-group row mb-3">
                                                        <label for="fecha"
                                                            class="col-sm-4 col-form-label text-start">Fecha</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-calendar"></i></span>
                                                                <input type="date" class="form-control" id="fecha"
                                                                    name="fecha">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label for="fecha_vencimiento"
                                                            class="col-sm-4 col-form-label text-start">Vencimiento</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-calendar-x"></i></span>
                                                                <input type="date" class="form-control"
                                                                    id="fecha_vencimiento" name="fecha_vencimiento">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Información del deudor -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idsocio"
                                                            class="col-sm-4 col-form-label text-start">Cliente</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person"></i></span>
                                                                <select id="idsocio" name="idsocio"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxonventasCxc.cargarSocio({ seleccion: this.value });">
                                                                    <?php echo getClientes(); ?>
                                                                </select>
                                                                <button class="btn btn-outline-secondary" type="button"
                                                                    id="botonBuscarSocio" name="botonBuscarSocio"
                                                                    title="Buscar socio"
                                                                    onclick="JaxonventasCxc.modalSeleccionarSocio();">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label for="idsubcuenta"
                                                            class="col-sm-4 col-form-label text-start">Subcuenta</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person"></i></span>
                                                                <select id="idsubcuenta" name="idsubcuenta"
                                                                    class="form-select select2-field"
                                                                    onchange="JaxonventasCxc.cargarSubcuenta({ seleccion: this.value }, document.getElementById('idsocio').value);">
                                                                </select>
                                                                <button class="btn btn-outline-secondary" type="button"
                                                                    id="botonBuscarSubcuenta"
                                                                    name="botonBuscarSubcuenta" title="Buscar subcuenta"
                                                                    onclick="JaxonventasCxc.modalSeleccionarSubcuenta(document.getElementById('idsocio').value);">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Concepto -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idconcepto"
                                                            class="col-sm-4 col-form-label text-start">Concepto</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-journal-text"></i></span>
                                                                <select id="idconcepto" name="idconcepto"
                                                                    class="form-select select2-field">
                                                                    <?php echo getConceptosCuentas('Cargo', 'CXC'); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Vendedor -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idvendedor"
                                                            class="col-sm-4 col-form-label text-start">Vendedor</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person-badge"></i></span>
                                                                <select id="idvendedor" name="idvendedor"
                                                                    class="form-select select2-field">
                                                                    <?php echo getUsuarios(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Documento -->
                                                    <div class="form-group row mb-3">
                                                        <label for="documento"
                                                            class="col-sm-4 col-form-label text-start">Documento</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-file-earmark-text"></i></span>
                                                                <input type="text" class="form-control" id="documento"
                                                                    name="documento"
                                                                    placeholder="Documento de referencia">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Segunda columna (menos campos) -->
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
                                                        <label for="importe"
                                                            class="col-sm-4 col-form-label text-start">Importe</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-cash-stack"></i></span>
                                                                <input type="number" step="0.01" class="form-control"
                                                                    id="importe" name="importe" placeholder="0.00">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label for="saldo"
                                                            class="col-sm-4 col-form-label text-start">Saldo</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-wallet2"></i></span>
                                                                <input type="number" step="0.01" class="form-control"
                                                                    id="saldo" name="saldo" placeholder="0.00">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label for="idc_metodopago"
                                                            class="col-sm-4 col-form-label text-start">Método
                                                            Pago</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-credit-card"></i></span>
                                                                <select id="idc_metodopago" name="idc_metodopago"
                                                                    class="form-select">
                                                                    <?php echo getCfdiMetodoPago(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Referencias -->
                                                    <div class="form-group row mb-3">
                                                        <label for="referencia"
                                                            class="col-sm-4 col-form-label text-start">Referencia</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-tag"></i></span>
                                                                <input type="text" class="form-control" id="referencia"
                                                                    name="referencia"
                                                                    placeholder="Referencia adicional">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Datos bancarios -->
                                                    <div class="form-group row mb-3">
                                                        <label for="banco"
                                                            class="col-sm-4 col-form-label text-start">Banco</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-bank"></i></span>
                                                                <input type="text" class="form-control" id="banco"
                                                                    name="banco" placeholder="Banco relacionado">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label for="cuenta_bancaria"
                                                            class="col-sm-4 col-form-label text-start">Cuenta
                                                            Bancaria</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-credit-card"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="cuenta_bancaria" name="cuenta_bancaria"
                                                                    placeholder="Número de cuenta bancaria">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row d-none" id="cardPartidas">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Abonos a la cuenta</h3>
                                            <div class="card-tools d-flex align-items-center gap-2">
                                                <button id="addPartidas" name="addPartidas"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonventasCxc.modalAgregarAbono(jaxon.getFormValues('formulario<?= $_GET['rand'] ?>'));"
                                                    type="button">
                                                    <span class="bi bi-plus-lg me-1"></span> Agregar abono
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
                                            <div class="table-responsive">
                                                <table id="tablaPartidas"
                                                    class="table table-striped table-hover display" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Partida</th>
                                                            <th>Folio</th>
                                                            <th>Estado</th>
                                                            <th>Concepto</th>
                                                            <th>Fecha</th>
                                                            <th>Creador</th>
                                                            <th>Documento</th>
                                                            <th>Referencia</th>
                                                            <th>Moneda</th>
                                                            <th>Forma</th>
                                                            <th>Monto</th>
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
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <!-- Campo de notas -->
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
        JaxonventasCxc.inializarFormulario();
    </script>
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
</body>

</html>