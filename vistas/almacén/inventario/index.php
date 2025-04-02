<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

// Calcular nombres de módulos semidinamicamente
$modulo = $ruta[(count($ruta) - 2)];
$submodulo = $ruta[(count($ruta) - 1)];
$subsubmodulo = null;
$pathControlador = __DIR__ . "/../../../controladores/individuales/$modulo.$submodulo.php";

// Verifica si el archivo existe antes de incluirlo
if (file_exists($pathControlador)) {
    require_once($pathControlador);
    validarSesion();
    echo $jaxon->getScript(true);
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
                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-3">
                                <select id="idalmacen" name="idalmacen" class="form-select"
                                    onchange="JaxonalkesGlobal.listadosIndexInventario('<?= $modulo; ?>', '<?= $submodulo; ?>', '<?= $subsubmodulo; ?>', 'Principal', 'Index', this.value);">
                                    <?php echo getAlmacenesPorSucursal(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <!-- Nav tabs -->
                                <div id="filtros">
                                </div>
                                <!-- Tab panes -->
                                <div class="tab-content">
                                    <!-- Contenido de la primera pestaña -->
                                    <div class="tab-pane fade show active">
                                        <div class="card p-4" id="tabla"> 
                                        </div>
                                    </div>
                                </div>
                            </div> <!--end::Row-->
                        </div>
                    </div>
                </div>
            </main>
            <?= botones(); ?>
        </div>
    </div>
    <?= scriptsHtml(); ?>
</body>
</html>
