<?php
session_start();
require_once("../../controladores/individuales/jaxon/inicio.php");
validarSesion();
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());
$modulo = $ruta[(count($ruta) - 2)];
$submodulo = $ruta[(count($ruta) - 1)];
echo $jaxon->getScript(true);
?>


<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= getBackground(); ?>"> <!--begin::Head-->

<head>
	<?= headHtml($modulo, $submodulo); ?>
</head>

<body class="layout-fixed-complete sidebar-expand-lg bg-body-tertiary"> <!--begin::App Wrapper-->
    <div class="app-wrapper"> <!--begin::Sidebar-->
        <?= menuLateral(); ?>
        <div class="app-main-wrapper"> <!--begin::Header-->
            <?= encabezado(); ?>
            <main class="app-main"> <!--begin::App Content Header-->
                <div class="app-content-header"> <!--begin::Container-->
                    <div class="container-fluid"> <!--begin::Row-->
                        <div class="row">
                            <div class="col-sm-8">
                                <h3 class="mb-0">Ventas - Facturas <small>(FAA00001)</small></h3>
                            </div>
                            <div class="col-sm-4">
                                <ol class="breadcrumb float-sm-end">
                                    <li class="breadcrumb-item"><a href="#">Ventas</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        Facturas
                                    </li>
                                </ol>
                            </div>
                        </div> <!--end::Row-->
                    </div> <!--end::Container-->
                </div> <!--end::App Content Header--> <!--begin::App Content-->
                <div class="app-content"> <!--begin::Container-->
                    <div class="container-fluid"> <!--begin::Row-->
                        <div class="row">
                            <div class="col-12"> <!-- Default box -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Title</h3>
                                        <div class="card-tools"> <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Collapse"> <i data-lte-icon="expand" class="bi bi-plus-lg"></i> <i data-lte-icon="collapse" class="bi bi-dash-lg"></i> </button> <button type="button" class="btn btn-tool" data-lte-toggle="card-remove" title="Remove"> <i class="bi bi-x-lg"></i> </button> </div>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            Lorem ipsum dolor sit amet, consetetur sadipscing elitr.
                                        </p>
                                    </div>
                                    <div class="card-footer">Footer</div>
                                </div>
                            </div>
                        </div> <!--end::Row-->
                    </div> <!--end::Container-->
                </div> <!--end::App Content-->
            </main>
            <div class="app-content-bottom-area">
                <div class="row">
                    <div class="col-12 text-end"> <button type="submit" class="btn btn-primary" name="save" value="create">Create Admin</button> </div>
                </div>
            </div> <!--end::App Main--> <!--begin::Footer-->
            <!--<footer class="app-footer">--> <!--begin::To the end (descomentar esto en caso de necesitar footer-->
            <!--</footer>--> <!--end::Footer-->
        </div>
    </div> <!--end::App Wrapper--> <!--begin::Script--> <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <?= scriptsHtml(); ?>
</body><!--end::Body-->

</html>