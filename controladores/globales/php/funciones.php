<?php
##########################################################################
#CODIGO FUENTE DE FUNCIONES PHP GLOBALES PARA EL SISTEMA
#INICIO DE VERSION 07/JUNIO/2024
#GUSTAVO QUINTERO
#ALKES - 
##########################################################################
require_once("/app/9001/vendor/autoload.php");
use Medoo\Medoo;

session_start();
$database = new Medoo();
function getBackgrounds()
{
    global $database;
    $tema = $database->select('usuarios', 'backgrounds', ['id' => $_SESSION['idusuario']]);
    return $tema[0];
}

function getCard()
{
    global $database;
    $tema = $database->select('usuarios', 'cards', ['id' => $_SESSION['idusuario']]);
    return $tema[0];
}

function getProductos_tipos()
{
    global $database;
    $registros = $database->select("productos_tipos", "*", [
        "ORDER" => ["id" => "ASC"]
    ]);
    $options = '<option value="" selected disabled>Elije una opción...</option>';
    foreach ($registros as $registro) {
        $options .= '<option value="' . $registro['id'] . '">' . $registro['nombre'] . '</option>';
    }

    print $options;
}

function getCategorias()
{
    global $database;
    $registros = $database->select("categorias", "*", [
        "ORDER" => ["id" => "ASC"]
    ]);
    $options = '<option value="" selected disabled>Elije una opción...</option>';
    foreach ($registros as $registro) {
        $options .= '<option value="' . $registro['id'] . '">' . $registro['nombre'] . '</option>';
    }

    print $options;
}


function getSubcategorias($categoria)
{
    global $database;
    $registros = $database->select("subcategorias", "*", [
        "idcategoria" => $categoria,
        "ORDER" => ["id" => "ASC"]
    ]);
    $options = '<option value="" selected disabled>Elije una opción...</option>';
    foreach ($registros as $registro) {
        $options .= '<option value="' . $registro['id'] . '">' . $registro['nombre'] . '</option>';
    }

    return $options;
}

function getSubsubcategorias($subcategoria)
{
    global $database;
    $registros = $database->select("subsubcategorias", "*", [
        "idsubcategoria" => $subcategoria,
        "ORDER" => ["id" => "ASC"]
    ]);
    $options = '<option value="" selected disabled>Elije una opción...</option>';
    foreach ($registros as $registro) {
        $options .= '<option value="' . $registro['id'] . '">' . $registro['nombre'] . '</option>';
    }

    return $options;
}


function verificarLogueo()
{
    // Verificar si el usuario no está logueado
    if (!isset($_SESSION['idusuario']) or !isset($_SESSION['identidad'])) {
        // Redirigir al usuario a la página de inicio de sesión
        header('Location: https://' . $_SERVER['HTTP_HOST'] . '/index.php');
        exit(); // Asegurar que el script se detiene después de la redirección
    }
    return;
}

function verificaRegistroRepetido($tabla, $columna, $dato, $idb=0)
{
    $bandera=false;
    global $database;
    $registros = $database->select($tabla, "*", [
        $columna => $dato,
        "id[!]" => $idb // Excluye el registro con este id
    ]);
    // Verificar si el usuario no está logueado
    if (count($registros)>0) {
        $bandera=true;
    }
    return $bandera;
}


function validar_global($form, $reglas)
{
    foreach ($form as $campo => $valor) {
        // Omitir campos que contienen "btn"
        if (strpos($campo, 'btn') !== false)
            continue;

        // Omitir campos sin reglas definidas
        if (!isset($reglas[$campo]))
            continue;

        $regla = $reglas[$campo];

        // Omitir campos opcionales vacíos
        if (!$regla['obligatorio'] && empty($valor))
            continue;

        // Validar campo obligatorio
        if ($regla['obligatorio'] && empty($valor)) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' es obligatorio y no ha sido llenado."];
        }

        // Validar tipo de dato
        $tipos = [
            'string' => 'is_string',
            'int' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_INT) !== false; },
            'float' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_FLOAT) !== false; },
            'email' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_EMAIL) !== false; },
            'url' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_URL) !== false; },
            'boolean' => function ($v) {
                return is_bool(filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); },
            'date' => function ($v) {
                return strtotime($v) !== false; }
        ];

        if (isset($regla['tipo']) && isset($tipos[$regla['tipo']]) && !$tipos[$regla['tipo']]($valor)) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe ser un(a) {$regla['tipo']} válido(a)."];
        }

        // Validar longitud mínima
        if (isset($regla['min']) && strlen($valor) < $regla['min']) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe tener al menos {$regla['min']} caracteres."];
        }

        // Validar longitud máxima
        if (isset($regla['max']) && strlen($valor) > $regla['max']) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' no debe exceder los {$regla['max']} caracteres."];
        }

        // Validar valor mínimo
        if (isset($regla['min_val']) && $valor < $regla['min_val']) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe ser mayor o igual a {$regla['min_val']}."];
        }

        // Validar valor máximo
        if (isset($regla['max_val']) && $valor > $regla['max_val']) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe ser menor o igual a {$regla['max_val']}."];
        }

        // Validar patrón
        if (isset($regla['pattern']) && !preg_match($regla['pattern'], $valor)) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' no tiene un formato válido."];
        }

        // Validar coincidencia de campos
        if (isset($regla['match']) && $valor != $form[$regla['match']]) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe coincidir con el campo '{$regla['match']}'."];
        }

        // Validar conjunto permitido
        if (isset($regla['in']) && !in_array($valor, $regla['in'])) {
            return ["campo" => $campo, "error" => "El campo '{$campo}' debe ser uno de los valores permitidos."];
        }
    }

    return true; // Validación exitosa
}



function headHtml($modulo, $submodulo)
{
    $html = '
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ALKES | ' . $modulo . ' · ' . $submodulo . '</title>
	<!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">
	<!-- DataTables -->
	<link rel="stylesheet" href="/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="/dist/css/adminlte.min.css">
	<!-- iCheck for checkboxes and radio inputs -->
	<link rel="stylesheet" href="/plugins/icheck-bootstrap/icheck-bootstrap.min.css">';
    print $html;
}

function encabezado()
{
    global $database;
    $entidades = $database->select("entidades", "*", [
        "ORDER" => ["id" => "ASC"]
    ]);

    // Obtener el id de entidad actual desde la sesión
    $entidad_actual = isset($_SESSION['identidad']) ? $_SESSION['identidad'] : null;

    // Verificar permiso para cambiar de entidad
    $permiso_cambiar_entidad = false; // Por defecto, no tiene permiso
    if (isset($_SESSION['idusuario'])) {
        $resultado = $database->select("usuarios", ["cambiar_entidad"], [
            "id" => $_SESSION['idusuario']
        ]);
        $permiso_cambiar_entidad = ($resultado[0]['cambiar_entidad'] == 'Si') ? true : false;
    }

    $options = '';
    foreach ($entidades as $entidad) {
        // Verificar si esta entidad es la entidad actual
        $active_class = ($entidad['id'] == $entidad_actual) ? 'active' : '';

        $options .= '<a class="dropdown-item ' . $active_class . '" href="#" data-id="' . $entidad['id'] . '" onclick="JaxonvivoERP.cambiarEntidad(' . $entidad['id'] . ');">' . $entidad['descripcion'] . '</a>';
    }

    // Lista de colores disponibles con sus traducciones
    $colors = [
        'primary' => 'Primario',
        'secondary' => 'Secundario',
        'info' => 'Información',
        'success' => 'Verde',
        'warning' => 'Advertencia',
        'danger' => 'Rojo',
        'black' => 'Negro',
        'gray-dark' => 'Gris Oscuro',
        'gray' => 'Gris',
        'light' => 'Claro',
        'indigo' => 'Índigo',
        'lightblue' => 'Azul Claro',
        'navy' => 'Azul Marino',
        'purple' => 'Púrpura',
        'fuchsia' => 'Fucsia',
        'pink' => 'Rosa',
        'maroon' => 'Granate',
        'orange' => 'Naranja',
        'lime' => 'Lima',
        'teal' => 'Verde Azulado',
        'olive' => 'Oliva'
    ];

    // Opciones de color para el dropdown
    $color_options = '';
    foreach ($colors as $color_en => $color_es) {
        $color_options .= '<a class="dropdown-item" href="#" onclick="JaxonalkesGlobal.cambiarCards(\'' . $color_en . '\');">
            <span class="badge bg-' . $color_en . '" style="width: 20px; height: 20px; display: inline-block;"></span> ' . $color_es . '
        </a>';
    }

    // Construir el HTML del encabezado
    $html = '<nav class="main-header navbar navbar-expand navbar-' . getBackgrounds() . '">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="/Vistas/Dashboard/index.php" class="nav-link">Inicio</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="https://soporte.redgq.uk" class="nav-link" target="_blank">Soporte</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="https://aprende.redgq.uk" class="nav-link" target="_blank">Aprende</a>
        </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">

        <!-- Messages Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-comments"></i>
            <span class="badge badge-danger navbar-badge">3</span>
            </a>
            <!-- Rest of the dropdown menu code -->
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <!-- Rest of the dropdown menu code -->
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>';

    // Añadir el menú de cambio de entidad solo si tiene permiso
    if ($permiso_cambiar_entidad) {
        $html .= '<!-- Entity Change Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" id="toggleEntityMenu">
                    <i class="fas fa-building"></i>
                </a>
                <div class="dropdown-menu p-0" aria-labelledby="toggleEntityMenu">
                    <div class="dropdown-header bg-dark text-white" style="font-size: 1.1rem;">Entidades</div>
                    ' . $options . '
                </div>
            </li>';
    }

    // Agregar menú para cambiar el color de las cards
    $html .= '<li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-paint-brush"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    ' . $color_options . '
                </div>
              </li>';

    // Agregar botón para cambiar tema
    $html .= '<li class="nav-item">
            <a class="nav-link" href="#" role="button" onclick="JaxonalkesGlobal.cambiarBackgrounds();">
            <i class="fas fa-sun"></i>
            </a>
        </li>';

    // Continuar con el resto del HTML del encabezado
    $html .= '<li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
            </a>
        </li>
        </ul>
        </nav>
        <!-- /.navbar -->';

    print $html;
}



function encabezadoContenido($modulo, $submodulo = false)
{

    // Construir la ruta para el botón "Nuevo"
    $newPage = 'formulario.php?accion=0&id=0'; ##== para que es dirname?
    ##==   $newPage = 'formulario.php?accion=0&id=0';

    $html = '    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 id="tituloEncabezado">' . $modulo . '</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
            </ol>';

    // Verificar si $submodulo es true para decidir si mostrar el botón "Nuevo"
    if (!$submodulo) {
        $html .= '<a href="' . $newPage . '" class="btn btn-primary float-right mr-3">Nuevo</a>';
    }

    $html .= '</div>
        </div>
      </div><!-- /.container-fluid -->
    </section>';
    print $html;
}


function menu($modulo, $submodulo)
{
    // Conexión a la base de datos con Medoo
    global $database;

    // Obtener todos los módulos principales
    $modulos = $database->select('modulos', [
        'id',
        'nombre',
        'ruta',
        'icono',
        'orden'
    ], [
        'padre_id' => null,
        'ORDER' => ['orden' => 'ASC']
    ]);

    $usuario = $database->select('usuarios', '*', [
        'id' => $_SESSION['idusuario']
    ]);

    // Consulta en Medoo sin abreviaciones
    $datos_empresa = $database->select('entidades', [
        '[>]empresas' => ['idempresa' => 'id']
    ], [
        'empresas.nombre_comercial',
        'entidades.descripcion'
    ], [
        'entidades.id' => $_SESSION['identidad']
    ]);



    // Construcción del menú
    $menu = '<aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="/Vistas/Dashboard/index.php" class="brand-link">
                    <img src="/assets/images/logoMinimal.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                    <span class="brand-text font-weight-light">' . $datos_empresa[0]['nombre_comercial'] . '</span>
                </a>
                <div class="sidebar">
                    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                        <div class="info">
                            <a href="#" class="d-block">' . $usuario[0]['nombre'] . ' - ' . $datos_empresa[0]['descripcion'] . '</a>
                        </div>
                    </div>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';

    // Iterar sobre los módulos principales
    foreach ($modulos as $mod) {
        // Reemplazar "_" por " " en el nombre del módulo
        $mod['nombre'] = str_replace('_', ' ', $mod['nombre']);
        $isActiveModulo = ($modulo == $mod['nombre']) ? ' active bg-' . getCard() . '' : '';
        $isOpenModulo = ($modulo == $mod['nombre']) ? ' menu-open' : '';
        $hasSubmodulos = $database->has('modulos', ['padre_id' => $mod['id']]);

        $menu .= '<li class="nav-item' . ($hasSubmodulos ? ' has-treeview' : '') . $isOpenModulo . '">
                    <a href="' . $mod['ruta'] . '" class="nav-link' . $isActiveModulo . '">
                        <i class="nav-icon fas ' . $mod['icono'] . '"></i>
                        <p>' . ucwords($mod['nombre']) . ($hasSubmodulos ? '<i class="right fas fa-angle-left"></i>' : '') . '</p>
                    </a>';

        if ($hasSubmodulos) {
            $submodulos = $database->select('modulos', [
                'nombre',
                'ruta',
                'icono'
            ], [
                'padre_id' => $mod['id'],
                'ORDER' => ['orden' => 'ASC']
            ]);

            $menu .= '<ul class="nav nav-treeview">';
            foreach ($submodulos as $submod) {
                // Reemplazar "_" por " " en el nombre del submódulo
                $submod['nombre'] = str_replace('_', ' ', $submod['nombre']);
                $isActiveSubmodulo = ($submodulo == $submod['nombre']) ? ' active' : '';
                $menu .= '<li class="nav-item">
                            <a href="' . $submod['ruta'] . '" class="nav-link' . $isActiveSubmodulo . '">
                                <i class="fas ' . $submod['icono'] . ' nav-icon"></i>
                                <p>' . ucwords($submod['nombre']) . '</p>
                            </a>
                        </li>';
            }
            $menu .= '</ul>';
        }

        $menu .= '</li>';
    }

    $menu .= '        </ul>
                    </nav>
                </div>
            </aside>';

    echo $menu;
}


function scriptHtml()
{
    $html =
        '<!-- jQuery -->
        <script src="/plugins/jquery/jquery.min.js"></script>
        <!-- Bootstrap 4 -->
        <script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- DataTables  & Plugins -->
        <script src="/plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
        <script src="/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
        <script src="/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
        <script src="/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
        <script src="/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
        <script src="/plugins/jszip/jszip.min.js"></script>
        <script src="/plugins/pdfmake/pdfmake.min.js"></script>
        <script src="/plugins/pdfmake/vfs_fonts.js"></script>
        <script src="/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
        <script src="/plugins/datatables-buttons/js/buttons.print.min.js"></script>
        <script src="/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
        <!-- AdminLTE App -->
        <script src="/dist/js/adminlte.min.js"></script>
        <script src="/plugins/select2/js/select2.full.min.js"></script>
        <script src="/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
        <script src="/plugins/moment/moment.min.js"></script>
        <script src="/plugins/inputmask/jquery.inputmask.min.js"></script>
        <script src="/plugins/daterangepicker/daterangepicker.js"></script>
        <script src="/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
        <script src="/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
        <script src="/plugins/bs-stepper/js/bs-stepper.min.js"></script>
        <script src="/plugins/dropzone/min/dropzone.min.js"></script>
		<script src="/plugins/sweetalert2/sweetalert2.min.js"></script>
		<link rel="stylesheet" href="/plugins/sweetalert2/sweetalert2.min.css">';
    print $html;
}




?>