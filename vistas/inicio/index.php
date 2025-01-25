<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

//calcular nombres de modulos semidinamicamente
$modulo = $ruta[(count($ruta) - 1)];
$pathControlador = __DIR__ . "/../../controladores/individuales/$modulo.php";

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
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                        <h3 class="card-title">Cosas por hacer globales</h3>
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
                                        <ul class="list-group">
                                            <!-- Categoría 1 -->
                                            <li class="list-group-item">
                                                <strong>Roles</strong>
                                                <ul>
                                                    <li>poner 4 columnas en los permisos (ver, editar, ver todos los
                                                        registros en el listado, ver dashboard)</li>
                                                </ul>
                                            </li>
                                            <!-- Categoría 2 -->
                                            <li class="list-group-item">
                                                <strong>Usuarios</strong>
                                                <ul>
                                                    <li>poner 4 columnas en los permisos (ver, editar, ver todos los
                                                        registros en el listado, ver dashboard)</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Sucursales</strong>
                                                <ul>
                                                    <li>cuando se cree una nueva sucursal se tienen que agregar sus
                                                        registros en consecutivos</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Empresas - Registro</strong>
                                                <ul>
                                                    <li>cuando se cree una nueva empresa se tiene que crear una sucursal
                                                        con todo lo que conlleva por ejemplo crear los consecutivos</li>
                                                    <li>cuando se cree una nueva empresa se tiene que crear un usuario
                                                        con todos los permisos comprados</li>
                                                </ul>
                                            </li>
                                        </ul>
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