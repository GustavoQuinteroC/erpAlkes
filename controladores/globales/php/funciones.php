<?php
##########################################################################
#CODIGO FUENTE DE FUNCIONES PHP GLOBALES PARA EL SISTEMA
#INICIO DE VERSION 07/JUNIO/2024
#GUSTAVO QUINTERO
#ALKES - 
##########################################################################
use Medoo\Medoo;
use Dotenv\Dotenv;

session_start();

// Cargar las variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__."/../../..");
$dotenv->load();

// Conectar a la base de datos con Medoo utilizando las variables de entorno
$database = new Medoo([
    'type' => $_ENV['DB_CONNECTION'],   // Tipo de base de datos (mysql)
    'host' => $_ENV['DB_HOST'],         // Dirección del host
    'port' => $_ENV['DB_PORT'],         // Puerto
    'database' => $_ENV['DB_DATABASE'], // Nombre de la base de datos
    'username' => $_ENV['DB_USERNAME'], // Usuario
    'password' => $_ENV['DB_PASSWORD'], // Contraseña
    'charset' => 'utf8mb4',             // Codificación de caracteres
]);


function validarSesion()
{
    if (!isset($_SESSION['usuario'])) {
        if (isset($_COOKIE['recuerdame_alkes'])) {
            global $database;
            $token = $_COOKIE['recuerdame_alkes'];
            $usuario = $database->select("usuarios", [
                "id"
            ], [
                "token_recuerdame" => $token
            ]);
            if (!empty($usuario)) {
                $_SESSION['idusuario'] = $usuario[0]['id'];
            } else {
                // Redirección al login
                $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                header("Location: $protocolo://$host");
                exit;
            }
        } else {
            // Redirección al login si no hay cookie
            $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            header("Location: $protocolo://$host");
            exit;
        }
    }
}

function getBackground()
{
    // Acceder a la variable global $database
    global $database;

    // Ejecutar la consulta usando Medoo
    $background = $database->select("usuarios", ["backgrounds"], ["id" => $_SESSION['idusuario']]);

    // Verificar si se encontró un resultado
    if (!empty($background) && isset($background[0]['backgrounds'])) {
        return $background[0]['backgrounds'];
    }

    return "";
}



function headHtml($modulo, $submodulo)
{
    // Normalizar y capitalizar los textos
    $normalizedModulo = ucwords(str_replace('_', ' ', strtolower($modulo)));
    $normalizedSubmodulo = ucwords(str_replace('_', ' ', strtolower($submodulo)));

    // Generar el HTML
    $html = "
    <!-- Font Awesome -->
    <link rel=\"stylesheet\" href=\"/plugins/fontawesome-free/css/all.min.css\">
    <!-- DataTables -->
    <link rel=\"stylesheet\" href=\"/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css\">
    <link rel=\"stylesheet\" href=\"/plugins/datatables-responsive/css/responsive.bootstrap4.min.css\">
    <link rel=\"stylesheet\" href=\"/plugins/datatables-buttons/css/buttons.bootstrap4.min.css\">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel=\"stylesheet\" href=\"/plugins/icheck-bootstrap/icheck-bootstrap.min.css\">
    <title>Alkes - $normalizedModulo / $normalizedSubmodulo</title><!--begin::Primary Meta Tags-->
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css\">
    <!--end::Fonts-->
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/styles/overlayscrollbars.min.css\">
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css\">
    <link rel=\"stylesheet\" href=\"/dist/css/adminlte.css\">
    <!-- Jquery Ui -->
    <link rel=\"stylesheet\" href=\"/_js/jquery-ui-1.11.4.custom/jquery-ui.css\" type=\"text/css\" media=\"screen\"/>";
    print $html;
}

function menuLateral() {
    global $database;

    // Obtener los módulos disponibles para el usuario (con sus permisos)
    $resultado = $database->select(
        "modulos", // Tabla de módulos
        [
            "[>]usuarios_modulos" => ["id" => "idmodulo"] // Unir con permisos del usuario
        ],
        [
            "modulos.id", 
            "modulos.nombre", 
            "modulos.ruta", 
            "modulos.icono", 
            "modulos.padre_id", 
            "usuarios_modulos.ver"
        ],
        [
            "usuarios_modulos.idusuario" => $_SESSION['idusuario'],
            "usuarios_modulos.ver" => 1 // Solo mostrar los módulos que el usuario puede ver
        ]
    );

    // Agrupar los módulos por su `padre_id`
    $modulos = [];
    foreach ($resultado as $modulo) {
        $modulos[$modulo['padre_id']][] = $modulo;
    }

    // Función recursiva para generar el menú
    function generarMenu($padre_id, $modulos) {
        if (!isset($modulos[$padre_id])) return '';

        $html = '';
        foreach ($modulos[$padre_id] as $modulo) {
            // Verificar si tiene submódulos (hijos)
            $hasChildren = isset($modulos[$modulo['id']]);
            
            // Si tiene hijos, es un item "select" (ul con li)
            if ($hasChildren) {
                $html .= "<li class=\"nav-item has-treeview\">";
                $html .= "<a href=\"#\" class=\"nav-link\">";
                $html .= "<i class=\"nav-icon bi {$modulo['icono']}\"></i>";
                $html .= "<p>{$modulo['nombre']}<i class=\"nav-arrow bi bi-chevron-right\"></i></p>";
                $html .= "</a>";
                $html .= "<ul class=\"nav nav-treeview\">" . generarMenu($modulo['id'], $modulos) . "</ul>";
                $html .= "</li>";
            } else {
                // Si no tiene hijos, es un enlace directo
                $html .= "<li class=\"nav-item\">";
                $html .= "<a href=\"{$modulo['ruta']}\" class=\"nav-link\">";
                $html .= "<i class=\"nav-icon bi {$modulo['icono']}\"></i>";
                $html .= "<p>{$modulo['nombre']}</p>";
                $html .= "</a>";
                $html .= "</li>";
            }
        }
        return $html;
    }

    // Consulta simple para obtener el nombre comercial
    $resultado = $database->select(
        "usuarios", // Tabla principal
        [
            "[>]entidades" => ["identidad" => "id"], // Unión con entidades
            "[>]empresas" => ["entidades.idempresa" => "id"] // Unión con empresas
        ],
        "empresas.nombre_comercial", // Columna seleccionada
        [
            "usuarios.id" => $_SESSION['idusuario'] // Condición WHERE
        ]
    );

    // Si no hay resultados, usar un valor por defecto
    $nombreComercial = $resultado[0] ?? "Mi Empresa";

    // HTML del menú lateral
    $html = "
    <div class=\"app-wrapper\">
        <aside class=\"app-sidebar bg-body shadow\" data-bs-theme=\"dark\">
            <div class=\"sidebar-brand\">
                <a href=\"../index.html\" class=\"brand-link\">
                    <img src=\"../../../dist/assets/img/AdminLTELogo.png\" alt=\"AdminLTE Logo\" class=\"brand-image opacity-75 shadow\">
                    <span class=\"brand-text fw-light\">$nombreComercial</span>
                </a>
            </div>
            <div class=\"sidebar-wrapper\">
                <nav class=\"mt-2\">
                    <ul class=\"nav sidebar-menu flex-column\" data-lte-toggle=\"treeview\" role=\"menu\" data-accordion=\"false\">
                        <li class=\"nav-header\">EXAMPLES</li>";

    // Generar el menú dinámico
    $html .= generarMenu(null, $modulos);

    $html .= "
                    </ul>
                </nav>
            </div>
        </aside>
    </div>";

    // Imprimir el HTML
    print $html;
}




function scriptsHtml()
{
	$html = "<!-- jQuery -->
    <script src=\"/plugins/jquery/jquery.min.js\"></script>
	<script src=\"/_js/jquery-ui-1.14.0.custom/jquery-ui.min.js\" type=\"text/javascript\"></script>
    <!-- Bootstrap 4 -->
    <script src=\"/plugins/bootstrap/js/bootstrap.bundle.min.js\"></script>
    <!-- DataTables  & Plugins -->
    <script src=\"/plugins/datatables/jquery.dataTables.min.js\"></script>
    <script src=\"/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js\"></script>
    <script src=\"/plugins/datatables-responsive/js/dataTables.responsive.min.js\"></script>
    <script src=\"/plugins/datatables-responsive/js/responsive.bootstrap4.min.js\"></script>
    <script src=\"/plugins/datatables-buttons/js/dataTables.buttons.min.js\"></script>
    <script src=\"/plugins/datatables-buttons/js/buttons.bootstrap4.min.js\"></script>
    <script src=\"/plugins/jszip/jszip.min.js\"></script>
    <script src=\"/plugins/pdfmake/pdfmake.min.js\"></script>
    <script src=\"/plugins/pdfmake/vfs_fonts.js\"></script>
    <script src=\"/plugins/datatables-buttons/js/buttons.html5.min.js\"></script>
    <script src=\"/plugins/datatables-buttons/js/buttons.print.min.js\"></script>
    <script src=\"/plugins/datatables-buttons/js/buttons.colVis.min.js\"></script>
    <!-- AdminLTE App -->
    <script src=\"/dist/js/adminlte.min.js\"></script>
    <script src=\"/plugins/select2/js/select2.full.min.js\"></script>
    <script src=\"/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js\"></script>
    <script src=\"/plugins/moment/moment.min.js\"></script>
    <script src=\"/plugins/inputmask/jquery.inputmask.min.js\"></script>
    <script src=\"/plugins/daterangepicker/daterangepicker.js\"></script>
    <script src=\"/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js\"></script>
    <script src=\"/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js\"></script>
    <script src=\"/plugins/bootstrap-switch/js/bootstrap-switch.min.js\"></script>
    <script src=\"/plugins/bs-stepper/js/bs-stepper.min.js\"></script>
    <script src=\"/plugins/dropzone/min/dropzone.min.js\"></script>
    <link rel=\"stylesheet\" href=\"/plugins/sweetalert2/sweetalert2.min.css\">
    <script src=\"/plugins/sweetalert2/sweetalert2.min.js\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js\"></script>
    <!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script src=\"https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js\"></script>
    <!--Plugin(Bootstrap 5)-->
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js\"></script>";
	print $html;
}






























function cerrarSesion()
{
    #mas o menos esta es la estructura:
    session_start();
    session_destroy();

    if (isset($_COOKIE['remember_me'])) {
        $database = new Medoo();
        $database->update("usuarios", [
            "token_recuerdame" => null
        ], [
            "token_recuerdame" => $_COOKIE['remember_me']
        ]);

        setcookie("remember_me", "", time() - 3600, "/"); // Expirar la cookie
    }

    header("Location: index.php");
    exit;
}

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


function verificaRegistroRepetido($tabla, $columna, $dato, $idb = 0)
{
    $bandera = false;
    global $database;
    $registros = $database->select($tabla, "*", [
        $columna => $dato,
        "id[!]" => $idb // Excluye el registro con este id
    ]);
    // Verificar si el usuario no está logueado
    if (count($registros) > 0) {
        $bandera = true;
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
                return filter_var($v, FILTER_VALIDATE_INT) !== false;
            },
            'float' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_FLOAT) !== false;
            },
            'email' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
            },
            'url' => function ($v) {
                return filter_var($v, FILTER_VALIDATE_URL) !== false;
            },
            'boolean' => function ($v) {
                return is_bool(filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            },
            'date' => function ($v) {
                return strtotime($v) !== false;
            }
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







?>