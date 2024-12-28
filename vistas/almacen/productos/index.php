<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

// Busca el índice donde comienza el módulo relevante
// Asumimos que los módulos relevantes están después de "vistas"
$indiceInicio = array_search("vistas", $ruta);
if ($indiceInicio === false) {
    die("No se encontró el directorio 'vistas' en la ruta actual.");
}

// Extrae los segmentos relevantes después de "vistas"
$segmentosRelevantes = array_slice($ruta, $indiceInicio + 1);

// Determina los niveles dinámicamente (modulo, submodulo, subsubmodulo)
$modulo = isset($segmentosRelevantes[0]) ? $segmentosRelevantes[0] : '';
$submodulo = isset($segmentosRelevantes[1]) ? $segmentosRelevantes[1] : '';
$subsubmodulo = isset($segmentosRelevantes[2]) ? $segmentosRelevantes[2] : '';

// Determina cuántos niveles hacia atrás necesitas
$numRetrocesos = count($segmentosRelevantes) + 1; // +1 para salir de "vistas"
$retroceso = str_repeat('../', $numRetrocesos);

// Construye dinámicamente la ruta del archivo controlador
$pathControlador = $retroceso . "controladores/individuales/jaxon";
if ($modulo) {
    $pathControlador .= "/$modulo";
}
if ($submodulo) {
    $pathControlador .= "/$submodulo";
}
if ($subsubmodulo) {
    $pathControlador .= "/$subsubmodulo";
}
$pathControlador .= ".php";

// Verifica si el archivo existe antes de incluirlo
if (file_exists($pathControlador)) {
    require_once($pathControlador);
    echo $jaxon->getScript(true);
    validarSesion();
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
        <?= menuLateral(); ?>
        <div class="app-main-wrapper">
            <?= encabezado(); ?>
            <main class="app-main">
                <?= titulos($modulo, $submodulo, $subsubmodulo); ?>
                <div class="app-content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                        <h3 class="card-title">Title</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>" data-lte-toggle="card-collapse"
                                                title="Collapse"> <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>" data-lte-toggle="card-remove"
                                                title="Remove"> <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            Lorem ipsum dolor sit amet, consetetur sadipscing elitr.
                                        </p>
                                    </div>
                                    <div class="card-footer">Footer</div>
                                </div>
                            </div>
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