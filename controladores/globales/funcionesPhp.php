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
$dotenv = Dotenv::createImmutable(__DIR__."/../..");
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
    session_start();
    global $database;

    if (!isset($_SESSION['idusuario'])) {
        if (isset($_COOKIE['recuerdame_alkes'])) {
            $token = $_COOKIE['recuerdame_alkes'];
            $usuario = $database->select("usuarios", [
                "id",
                "identidad",
                "estado"
            ], [
                "token_recuerdame" => $token
            ]);

            if (!empty($usuario)) {
                $estado_usuario = $usuario[0]['estado'];

                // Verificar si el usuario está inactivo
                if ($estado_usuario === "Inactivo") {
                    cerrarSesion();
                }

                // Obtener información adicional de la entidad y la empresa
                $entidad = $database->select("entidades", [
                    "[>]empresas" => ["idempresa" => "id"]
                ], [
                    "entidades.estado(entidad_estado)",
                    "empresas.id(empresa_id)",
                    "empresas.estado(empresa_estado)"
                ], [
                    "entidades.id" => $usuario[0]['identidad']
                ]);

                if (!empty($entidad)) {
                    // Verificar si la entidad o empresa están inactivas
                    if ($entidad[0]['entidad_estado'] === "Inactivo" || $entidad[0]['empresa_estado'] === "Inactivo") {
                        cerrarSesion();
                    }

                    // Actualizar la sesión con idempresa
                    $_SESSION['idusuario'] = $usuario[0]['id'];
                    $_SESSION['identidad'] = $usuario[0]['identidad'];
                    $_SESSION['idempresa'] = $entidad[0]['empresa_id']; // Actualizamos idempresa aquí
                } else {
                    // Si no hay entidad válida, cerrar sesión
                    cerrarSesion();
                }
            } else {
                // Redirección al login si no se encuentra el usuario
                header("Location: " . $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_HOST']);
                exit;
            }
        } else {
            // Redirección al login si no hay cookie
            header("Location: " . $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_HOST']);
            exit;
        }
    } else {
        // Validar sesión activa
        $usuario = $database->select("usuarios", [
            "estado"
        ], [
            "id" => $_SESSION['idusuario']
        ]);

        if (!empty($usuario) && $usuario[0]['estado'] === "Inactivo") {
            cerrarSesion();
        }

        // Verificar la entidad y la empresa de la sesión activa
        $entidad = $database->select("entidades", [
            "[>]empresas" => ["idempresa" => "id"]
        ], [
            "entidades.estado(entidad_estado)",
            "empresas.id(empresa_id)",
            "empresas.estado(empresa_estado)"
        ], [
            "entidades.id" => $_SESSION['identidad']
        ]);

        if (!empty($entidad)) {
            // Verificar si la entidad o empresa están inactivas
            if ($entidad[0]['entidad_estado'] === "Inactivo" || $entidad[0]['empresa_estado'] === "Inactivo") {
                cerrarSesion();
            }

            // Asegurarnos de que idempresa esté en la sesión
            $_SESSION['idempresa'] = $entidad[0]['empresa_id'];
        } else {
            cerrarSesion();
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
    <!-- Meta Tags -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
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
    <link rel=\"stylesheet\" href=\"/dist/css/adminlte.css\">";
    print $html;
}

function encabezado()
{
    global $database;

    // Asegúrate de que $_SESSION['idusuario'] está configurada.
    if (!isset($_SESSION['idusuario'])) {
        return '<p>Error: No se ha establecido la sesión del usuario.</p>';
    }

    // Consultar datos del usuario desde la base de datos.
    $usuarioId = $_SESSION['idusuario'];
    $usuario = $database->get("usuarios", ["nombre", "departamento", "ingreso", "backgrounds"], ["id" => $usuarioId]);

    // Verificar si la consulta devolvió resultados.
    if (!$usuario) {
        return '<p>Error: Usuario no encontrado en la base de datos.</p>';
    }

    $nombreUsuario = htmlspecialchars($usuario['nombre']);
    $departamento = htmlspecialchars($usuario['departamento'] ?? 'Sin departamento');

    // Determinar el icono según el tema del usuario.
    $tema = $usuario['backgrounds'] ?? 'light';
    $iconoTema = $tema === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';

    // Formatear la fecha de ingreso en español en formato numérico.
    $ingreso = $usuario['ingreso'];
    if ($ingreso) {
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            'UTC',
            IntlDateFormatter::GREGORIAN,
            'MM/yyyy'
        );
        $fechaIngreso = $formatter->format(strtotime($ingreso));
    } else {
        $fechaIngreso = 'Fecha no disponible';
    }

    $html = '
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"> 
                    <a class="nav-link" data-lte-toggle="sidebar" role="button"> 
                        <i class="bi bi-list"></i> 
                    </a> 
                </li>
                <li class="nav-item d-none d-md-block"> 
                    <a href="https://alkes.xyz/soporte" class="nav-link">Soporte</a> 
                </li>
                <li class="nav-item d-none d-md-block"> 
                    <a href="https://alkes.xyz/aprende" class="nav-link">Aprende</a> 
                </li>
            </ul> 
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown"> 
                    <!-- Botón de selección de tema -->
                    <a class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="' . $iconoTema . '" id="iconoTema"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: auto; min-width: 100px;">
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarBackgrounds(\'light\');">
                                <i class="bi bi-sun-fill"></i> Claro
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarBackgrounds(\'dark\');">
                                <i class="bi bi-moon-fill"></i> Oscuro
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Icono para cambiar colores -->
                <li class="nav-item dropdown"> 
                    <a class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-brush" id="iconoColor"></i> <!-- Icono de pincel -->
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: auto; min-width: 100px;">
                        <!-- Opciones de colores -->
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'primary\');">
                                <i class="bi bi-circle" style="color: #007bff;"></i> Azul
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'secondary\');">
                                <i class="bi bi-circle" style="color: #6c757d;"></i> Gris
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'info\');">
                                <i class="bi bi-circle" style="color: #17a2b8;"></i> Cian
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'success\');">
                                <i class="bi bi-circle" style="color: #28a745;"></i> Verde
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'warning\');">
                                <i class="bi bi-circle" style="color: #ffc107;"></i> Amarillo
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'danger\');">
                                <i class="bi bi-circle" style="color: #dc3545;"></i> Rojo
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'dark\');">
                                <i class="bi bi-circle" style="color: #343a40;"></i> Oscuro
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="JaxonalkesGlobal.cambiarEnfasis(\'light\');">
                                <i class="bi bi-circle" style="color: #f8f9fa;"></i> Claro
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Botón de pantalla completa -->
                <li class="nav-item"> 
                    <a class="nav-link" data-lte-toggle="fullscreen"> 
                        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i> 
                        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none;"></i> 
                    </a> 
                </li> 
                <!--begin::User Menu Dropdown-->
                <li class="nav-item dropdown user-menu"> 
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown"> 
                        <img src="/src/assets/img/users/'.$usuarioId.'.jpg" class="user-image rounded-circle shadow" alt="User Image"> 
                        <span class="d-none d-md-inline">' . $nombreUsuario . '</span> 
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end"> 
                        <!--begin::User Image-->
                        <li class="user-header text-bg-'.getEnfasis().'"> 
                            <img src="/src/assets/img/users/'.$usuarioId.'.jpg" class="rounded-circle shadow" alt="User Image">
                            <p>
                                ' . $nombreUsuario . ' - ' . $departamento . '
                                <small>Miembro desde ' . $fechaIngreso . '</small>
                            </p>
                        </li> 
                        <li class="user-footer"> 
                            <button class="btn btn-primary btn-flat" type="button" onclick="xajax_mi_perfil();">Mi perfil</button> 
                            <button class="btn btn-danger btn-flat float-end" type="button" onclick="JaxonalkesGlobal.unionCerrarSesion();">Cerrar sesión</button> 
                        </li> <!--end::Menu Footer-->
                    </ul>
                </li>
            </ul>
        </div>
    </nav>';

    return $html;
}


function menuLateral($moduloActivo = null, $submoduloActivo = null, $subsubmoduloActivo = null) {
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

    // Agrupar los módulos por su padre_id
    $modulos = [];
    foreach ($resultado as $modulo) {
        $modulos[$modulo['padre_id']][] = $modulo;
    }

    // Función recursiva para generar el menú
    function generarMenu($padre_id, $modulos, $moduloActivo, $submoduloActivo, $subsubmoduloActivo) {
        if (!isset($modulos[$padre_id])) return '';

        $html = '';
        foreach ($modulos[$padre_id] as $modulo) {
            // Verificar si tiene submódulos (hijos)
            $hasChildren = isset($modulos[$modulo['id']]);
            
            // Determinar si este módulo es el activo
            $isActive = (strtolower($modulo['nombre']) === strtolower($moduloActivo)) ? 'active' : '';

            // Si tiene hijos, es un item "select" (ul con li)
            if ($hasChildren) {
                $html .= "<li class=\"nav-item has-treeview " . ($isActive ? 'menu-open' : '') . "\">";
                $html .= "<a href=\"#\" class=\"nav-link {$isActive}\">";
                $html .= "<i class=\"nav-icon bi {$modulo['icono']}\"></i>";
                $html .= "<p>{$modulo['nombre']}<i class=\"nav-arrow bi bi-chevron-right\"></i></p>";
                $html .= "</a>";
                $html .= "<ul class=\"nav nav-treeview\">" . generarMenu($modulo['id'], $modulos, $moduloActivo, $submoduloActivo, $subsubmoduloActivo) . "</ul>";
                $html .= "</li>";
            } else {
                // Si no tiene hijos, es un enlace directo
                $isActiveSub = (strtolower($modulo['nombre']) === strtolower($submoduloActivo)) ? 'active text-bg-'.getEnfasis() : '';
                $html .= "<li class=\"nav-item\">";
                $html .= "<a href=\"{$modulo['ruta']}\" class=\"nav-link {$isActiveSub}\">";
                $html .= "<i class=\"nav-icon bi {$modulo['icono']}\"></i>";
                $html .= "<p>{$modulo['nombre']}</p>";
                $html .= "</a>";
                $html .= "</li>";
            }
        }
        return $html;
    }

    // Consulta simplificada para obtener el nombre comercial
    $resultado = $database->select(
        "entidades", // Tabla principal
        [
            "[>]empresas" => ["idempresa" => "id"] // Unión con empresas
        ],
        "empresas.nombre_comercial", // Columna seleccionada
        [
            "entidades.id" => $_SESSION['identidad'] // Condición WHERE
        ]
    );

    // Si no hay resultados, usar un valor por defecto
    $nombreComercial = $resultado[0] ?? "Mi Empresa";

    // HTML del menú lateral
    $html = "
    <div class=\"app-wrapper\">
        <aside class=\"app-sidebar bg-body shadow\" data-bs-theme=\"dark\">
            <div class=\"sidebar-brand\">
                <a href=\"/vistas/inicio/index.php\" class=\"brand-link\">
                    <img src=\"/dist/assets/img/AdminLTELogo.png\" alt=\"AdminLTE Logo\" class=\"brand-image opacity-75 shadow\">
                    <span class=\"brand-text fw-light\">$nombreComercial</span>
                </a>
            </div>
            <div class=\"sidebar-wrapper\">
                <nav class=\"mt-2\">
                    <ul class=\"nav sidebar-menu flex-column\" data-lte-toggle=\"treeview\" role=\"menu\" data-accordion=\"false\">";

    // Generar el menú dinámico
    $html .= generarMenu(null, $modulos, $moduloActivo, $submoduloActivo, $subsubmoduloActivo);

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
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js\"></script>
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (
                sidebarWrapper &&
                typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined'
            ) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>";
	print $html;
}

function cerrarSesion($redirigir = true)
{
    session_start();
    session_destroy();

    if (isset($_COOKIE['recuerdame_alkes'])) {
        global $database;
        $database->update("usuarios", [
            "token_recuerdame" => null
        ], [
            "token_recuerdame" => $_COOKIE['recuerdame_alkes']
        ]);

        setcookie("recuerdame_alkes", "", time() - 3600, "/"); // Expirar la cookie
    }

    if ($redirigir) {
        // Redirección al login
        header("Location: " . $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_HOST']);
        exit;
    }
}

function titulos($modulo, $submodulo = null, $subsubmodulo = null)
{
    // Normalizar y capitalizar las cadenas de los módulos
    $normalizedModulo = ucwords(str_replace('_', ' ', strtolower($modulo)));
    $normalizedSubmodulo = $submodulo ? ucwords(str_replace('_', ' ', strtolower($submodulo))) : '';
    $normalizedSubsubmodulo = $subsubmodulo ? ucwords(str_replace('_', ' ', strtolower($subsubmodulo))) : '';

    // Construir el título dinámico
    $titulo = $normalizedModulo;
    if ($normalizedSubmodulo) {
        $titulo .= " - $normalizedSubmodulo";
    }
    if ($normalizedSubsubmodulo) {
        $titulo .= " - $normalizedSubsubmodulo";
    }

    // Generar un identificador único para el <small>
    $smallId = 'smallTitulos';

    // Obtener la ruta actual
    $ruta_actual = basename($_SERVER['PHP_SELF']); // Obtener el nombre del archivo actual

    // Evaluar si la ruta es formulario.php y si existe la variable 'id'
    $puntos = ($ruta_actual == "formulario.php" && isset($_GET['id'])) ? ':' : '';

    // Generar el HTML con la estructura correcta
    $html = "
        <div class=\"app-content-header\"> <!-- Contenedor principal -->
            <div class=\"container-fluid\">
                <div class=\"row justify-content-center align-items-center\"> <!-- Centrar horizontal y vertical -->
                    <h5 class=\"mb-0 text-center\"><strong>$titulo$puntos</strong> <small id=\"$smallId\"></small></h5>
                </div>
            </div>
        </div>
    ";

    // Imprimir el HTML generado
    print $html;
}


function botones()
{
    // Obtener la ruta actual y la variable GET id
    $ruta_actual = basename($_SERVER['PHP_SELF']); // Obtener el nombre del archivo actual
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    // Evaluar si la ruta es formulario.php y existe la variable GET id
    if ($ruta_actual == "formulario.php" && $id !== null) {
        // Contenedor vacío
        $html = "
            <div class=\"app-content-bottom-area\"> <!-- Contenedor principal -->
                <div class=\"row\">
                    <!-- Botonera vacía -->
                    <div class=\"col-12 text-end\" id=\"botonera-contenedor\">
                    </div>
                </div>
            </div>
        ";
    } else {
        // Generar un número aleatorio de 4 dígitos
        $random_number = rand(1000, 9999);

        // Botón con el enlace a formulario.php con parámetros
        $html = "
            <div class=\"app-content-bottom-area\"> <!-- Contenedor principal -->
                <div class=\"row\">
                    <!-- Botonera alineada a la derecha -->
                    <div class=\"col-12 text-end\" id=\"botonera-contenedor\">
                        <a href=\"formulario.php?id=0&rand={$random_number}\" class=\"btn btn-primary btn-sm\">Nuevo</a>
                    </div>
                </div>
            </div>
        ";
    }

    // Imprimir el HTML generado
    print $html;
}


function getBackgrounds()
{
    global $database;
    $tema = $database->select('usuarios', 'backgrounds', ['id' => $_SESSION['idusuario']]);
    return $tema[0];
}

function getEnfasis()
{
    global $database;
    $enfasis = $database->select('usuarios', 'enfasis', ['id' => $_SESSION['idusuario']]);
    return $enfasis[0];
}

function getTextColor()
{
    // Obtener el énfasis del usuario desde la base de datos
    $enfasis = getEnfasis();

    // Lista de colores oscuros que requieren texto claro
    $darkColors = ['primary', 'secondary', 'dark', 'success', 'danger', 'black'];

    // Determinar el color del texto
    if (in_array($enfasis, $darkColors)) {
        return 'text-light';
    }

    // Para colores claros o no listados, usar texto oscuro
    return 'text-dark';
}

function filtrosTablas($modulo, $submodulo, $subsubmodulo)
{
    global $database;

    // Obtener la tabla correspondiente al módulo, submódulo y subsubmódulo
    $tabla = $database->select("tablas", ["id"], [
        "modulo" => $modulo,
        "submodulo" => $submodulo,
        "subsubmodulo" => $subsubmodulo
    ]);

    if (empty($tabla)) {
        return []; // Retornar vacío si no se encuentra la tabla
    }

    // Obtener los filtros asociados a la tabla
    $filtros = $database->select("tablas_filtros", ["nombre", "condicion"], [
        "idtabla" => $tabla[0]['id']
    ]);

    // Construir el array de retorno con los nombres de los filtros
    $filtrosReturn = [];
    foreach ($filtros as $filtro) {
        $filtrosReturn[] = [
            'nombre' => $filtro['nombre'],
        ];
    }

    return $filtrosReturn;
}

function columnasTablas($modulo, $submodulo, $subsubmodulo, $uso)
{
    global $database;
    // Obtener la tabla correspondiente al módulo, submódulo y subsubmódulo
    $tabla = $database->select('tablas', "*", [
        "modulo" => $modulo,
        "submodulo" => $submodulo,
        "subsubmodulo" => $subsubmodulo
    ]);

    if (empty($tabla)) {
        echo "<th>No hay datos disponibles</th>";
        return;
    }

    // Obtener las columnas asociadas al uso y la tabla
    $columnas = $database->select("tablas_columnas", "*", [
        "uso" => $uso,
        "idtabla" => $tabla[0]['id']
    ]);

    if (empty($columnas)) {
        echo "<th>No hay columnas disponibles</th>";
        return;
    }

    // Convertir la cadena de columnas en un array
    $columnNames = explode(',', $columnas[0]['columnas']);

    // Mapear las columnas al formato DataTables
    $columns = [];
    foreach ($columnNames as $index => $column) {
        $columns[] = ['db' => $column, 'dt' => $index];
    }

    // Generar los elementos <th> basados en el array $columns
    $html = "";
    foreach ($columns as $column) {
        // Obtener el nombre de la columna
        $columnName = htmlspecialchars($column['db']); // Escapar nombre de la columna por seguridad

        // Reemplazar guiones bajos por espacios y capitalizar el texto
        $columnName = str_replace('_', ' ', $columnName);
        $columnName = ucwords(strtolower($columnName));

        // Añadir el elemento <th> al HTML
        $html .= "<th>{$columnName}</th>";
    }

    return $html;
}


function ordenTablas($modulo, $submodulo, $subsubmodulo, $uso)
{
    global $database;

    // Obtener la tabla correspondiente al módulo, submódulo y subsubmódulo
    $tabla = $database->select("tablas", "*", [
        "modulo" => $modulo,
        "submodulo" => $submodulo,
        "subsubmodulo" => $subsubmodulo
    ]);

    if (empty($tabla)) {
        return;
    }

    // Obtener las columnas asociadas al uso y la tabla
    $resultado = $database->select("tablas_columnas", ["orden", "indice_orden"], [
        "uso" => $uso,
        "idtabla" => $tabla[0]['id']
    ]);

    if (empty($resultado)) {
        return;
    }

    // Crear el array con los valores de orden e índice
    $orden = [
        'orden' => $resultado[0]['orden'],         // Orden obtenido de la base de datos
        'indice' => $resultado[0]['indice_orden']  // Índice del orden
    ];

    return $orden;
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










?>