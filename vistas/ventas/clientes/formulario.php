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
                                                    <div class="form-group row mb-3">
                                                        <label for="clave"
                                                            class="col-sm-4 col-form-label text-start">Clave</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-key"></i></span>
                                                                <input type="text" class="form-control" id="clave"
                                                                    readOnly name="clave"
                                                                    placeholder="Clave (esta sera autogenerada)">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="nombre_comercial"
                                                            class="col-sm-4 col-form-label text-start">Nombre
                                                            Comercial</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-person-square"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="nombre_comercial" name="nombre_comercial"
                                                                    placeholder="Nombre Comercial">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="nivel"
                                                            class="col-sm-4 col-form-label text-start">Nivel</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-list-ul"></i></span>
                                                                <select id="nivel" name="nivel" class="form-select">
                                                                    <option value="Sucursal">Sucursal</option>
                                                                    <option value="Empresa">Empresa</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                                    <div class="form-group row mb-3">
                                                        <label for="correo"
                                                            class="col-sm-4 col-form-label text-start">Correo</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-envelope"></i></span>
                                                                <input type="email" class="form-control" id="correo"
                                                                    name="correo" placeholder="Correo">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="web"
                                                            class="col-sm-4 col-form-label text-start">Web</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-globe"></i></span>
                                                                <input type="url" class="form-control" id="web"
                                                                    name="web" placeholder="Sitio Web">
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                                <!-- Segunda columna -->
                                                <div class="col-md-6">
                                                    <div class="form-group row mb-3">
                                                        <label for="telefono"
                                                            class="col-sm-4 col-form-label text-start">Teléfono</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-phone"></i></span>
                                                                <input type="text" class="form-control" id="telefono"
                                                                    name="telefono" placeholder="Teléfono">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="telefono_fijo"
                                                            class="col-sm-4 col-form-label text-start">Teléfono
                                                            Fijo</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-telephone"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="telefono_fijo" name="telefono_fijo"
                                                                    placeholder="Teléfono Fijo">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="fax"
                                                            class="col-sm-4 col-form-label text-start">Fax</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-telephone"></i></span>
                                                                <input type="text" class="form-control" id="fax"
                                                                    name="fax" placeholder="Número de fax">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="banco"
                                                            class="col-sm-4 col-form-label text-start">Banco</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-bank"></i></span>
                                                                <input type="text" class="form-control" id="banco"
                                                                    name="banco" placeholder="Banco">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="cuenta"
                                                            class="col-sm-4 col-form-label text-start">Cuenta</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-credit-card"></i></span>
                                                                <input type="text" class="form-control" id="cuenta"
                                                                    name="cuenta" placeholder="Número de Cuenta">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="descuento"
                                                            class="col-sm-4 col-form-label text-start">Descuento</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-percent"></i></span>
                                                                <input type="number" class="form-control" id="descuento"
                                                                    name="descuento" placeholder="Descuento (%)" min="0"
                                                                    max="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="estado"
                                                            class="col-sm-4 col-form-label text-start">Estado</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-info-circle"></i></span>
                                                                <select id="estado" name="estado" class="form-select">
                                                                    <option value="Activo">Activo</option>
                                                                    <option value="Inactivo">Inactivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="tipo"
                                                            class="col-sm-4 col-form-label text-start">Tipo</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-people"></i></span>
                                                                <select id="tipo" name="tipo" class="form-select">
                                                                    <option value="Cliente">Cliente</option>
                                                                    <option value="Ambos">Proveedor y cliente (ambos)
                                                                    </option>
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
                            <div class="row" id="datosFiscales" class="d-block">
                                <div class="col-12">
                                    <div class="card mb-4">
                                        <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                            <h3 class="card-title">Información Fiscal y Domicilio</h3>
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
                                                    <!-- Datos fiscales -->
                                                    <div class="form-group row mb-3">
                                                        <label for="razon_social"
                                                            class="col-sm-4 col-form-label text-start">Razón
                                                            Social</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-building"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="razon_social" name="razon_social"
                                                                    placeholder="Razón Social">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="rfc"
                                                            class="col-sm-4 col-form-label text-start">RFC</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-file-earmark-text"></i></span>
                                                                <input type="text" class="form-control" id="rfc"
                                                                    name="rfc" placeholder="RFC">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_regimen"
                                                            class="col-sm-4 col-form-label text-start">Régimen
                                                            Fiscal</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-briefcase"></i></span>
                                                                <select id="idc_regimen" name="idc_regimen"
                                                                    onchange="JaxonventasClientes.cambiarUsoCfdi(this.value);"
                                                                    class="form-select select2-field">
                                                                    <?php echo getCfdiRegimen(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                                    <!-- Métodos de pago -->
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_metodopago"
                                                            class="col-sm-4 col-form-label text-start">Método de
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
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_usocfdi"
                                                            class="col-sm-4 col-form-label text-start">Uso de
                                                            CFDI</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-file-earmark"></i></span>
                                                                <select id="idc_usocfdi" name="idc_usocfdi"
                                                                    class="form-select select2-field">

                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_formapago"
                                                            class="col-sm-4 col-form-label text-start">Forma de
                                                            Pago</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-wallet2"></i></span>
                                                                <select id="idc_formapago" name="idc_formapago"
                                                                    class="form-select select2-field">
                                                                    <?php echo getCfdiFormaPago(); ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Segunda columna -->
                                                <div class="col-md-6">
                                                    <!-- Datos de domicilio -->
                                                    <div class="form-group row mb-3">
                                                        <label for="calle"
                                                            class="col-sm-4 col-form-label text-start">Calle</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-signpost-2"></i></span>
                                                                <input type="text" class="form-control" id="calle"
                                                                    name="calle" placeholder="Calle">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="numero_exterior"
                                                            class="col-sm-4 col-form-label text-start">No.
                                                            Exterior</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-door-closed"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="numero_exterior" name="numero_exterior"
                                                                    placeholder="Número Exterior">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="numero_interior"
                                                            class="col-sm-4 col-form-label text-start">No.
                                                            Interior</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-door-open"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="numero_interior" name="numero_interior"
                                                                    placeholder="Número Interior">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="codigo_postal"
                                                            class="col-sm-4 col-form-label text-start">Código
                                                            Postal</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-mailbox"></i></span>
                                                                <input type="text" class="form-control"
                                                                    id="codigo_postal" name="codigo_postal"
                                                                    placeholder="Código Postal"
                                                                    onchange="JaxonventasClientes.ajustesCodigoPostal(this.value);">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_estado"
                                                            class="col-sm-4 col-form-label text-start">Estado</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-geo-alt"></i></span>
                                                                <select id="idc_estado" name="idc_estado"
                                                                    class="form-select select2-field">
                                                                    <!-- Opciones se llenarán dinámicamente -->
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_municipio"
                                                            class="col-sm-4 col-form-label text-start">Municipio</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-geo"></i></span>
                                                                <select id="idc_municipio" name="idc_municipio"
                                                                    class="form-select select2-field">
                                                                    <!-- Opciones se llenarán dinámicamente -->
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="idc_colonia"
                                                            class="col-sm-4 col-form-label text-start">Colonia</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-house-door"></i></span>
                                                                <select id="idc_colonia" name="idc_colonia"
                                                                    class="form-select select2-field">
                                                                    <!-- Opciones se llenarán dinámicamente -->
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
                                            <h3 class="card-title">Crédito y cobranza</h3>
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
                                                    <!-- Monto de crédito -->
                                                    <div class="form-group row mb-3">
                                                        <label for="credito_monto_cliente"
                                                            class="col-sm-4 col-form-label text-start">Monto de
                                                            Crédito</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-cash"></i></span>
                                                                <input type="number" class="form-control"
                                                                    id="credito_monto_cliente"
                                                                    name="credito_monto_cliente"
                                                                    placeholder="Monto de Crédito">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <!-- Días de crédito -->
                                                    <div class="form-group row mb-3">
                                                        <label for="credito_dias_cliente"
                                                            class="col-sm-4 col-form-label text-start">Días de
                                                            Crédito</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="bi bi-calendar-day"></i></span>
                                                                <input type="number" class="form-control"
                                                                    id="credito_dias_cliente"
                                                                    name="credito_dias_cliente"
                                                                    placeholder="Días de Crédito">
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
                                            <h3 class="card-title">Listado de subcuentas</h3>
                                            <div class="card-tools d-flex align-items-center gap-2">
                                                <button id="addSubcuenta" name="addSubcuenta"
                                                    class="btn btn-sm border <?= getTextColor(); ?> bg-transparent"
                                                    onclick="JaxonventasClientes.modalSeleccionarSubcuentas();"
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
                                            <div class="table-responsive">
                                                <!-- Tabla con DataTables (inicialmente vacía) -->
                                                <table id="tablaPartidas"
                                                    class="table table-striped table-hover display" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Clave</th>
                                                            <th>Nombre comercial</th>
                                                            <th>Estado</th>
                                                            <th>Vencimiento</th>
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
        JaxonventasClientes.inializarFormulario();
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