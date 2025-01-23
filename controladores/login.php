<?php
require_once(__DIR__."/../vendor/autoload.php");

use Jaxon\Jaxon;
use function Jaxon\jaxon;
use Jaxon\Response\Response;
use Dotenv\Dotenv;
use Medoo\Medoo;

// Cargar las variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();

$url_base = $_SERVER['HTTP_X_FORWARDED_PROTO']."://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$jaxon = jaxon();
$jaxon->setOption('core.request.uri', $url_base);


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


function alerta($icono, $titulo, $texto, $focus = "", $funcion = "")
{
    $respuesta = jaxon()->newResponse(); // Usar jaxon()->newResponse() en lugar de crear manualmente un objeto Response

    // Preparar el script de SweetAlert 2
    $enfoqueScript = !empty($focus) ? 'setTimeout(function() { document.getElementById("' . addslashes($focus) . '").focus(); }, 2);' : '';
    $funcionScript = !empty($funcion) ? $funcion . ';' : '';
    $script = <<<SCRIPT
    Swal.fire({
        title: "{$titulo}",
        text: "{$texto}",
        icon: "{$icono}",
        showConfirmButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            {$enfoqueScript}
            {$funcionScript}
        }
    });
    SCRIPT;

    // Agregar el script al response
    $respuesta->script($script);
    return $respuesta;
}


function login($form)
{
    $response = jaxon()->newResponse();
    $user = $form['user'];
    $pass = str_replace("'", "", $form['clave']);
    $pass = str_replace('"', '', $pass);
    $pass = sha1($pass);

    // Obtener información del usuario
    global $database;
    $usuario = $database->select("usuarios", [
        "id",
        "idsucursal",
        "intentos",
        "ultimo_intento",
        "estado"
    ], [
        "usuario" => $user
    ]);

    // Verificar si el usuario no existe
    if (empty($usuario)) {
        $response->appendResponse(alerta("error", "Error", "Usuario no registrado."));
        return $response;
    }

    // Variables relevantes
    $intentos = $usuario[0]['intentos'];
    $ultimo_intento = $usuario[0]['ultimo_intento'];
    $estado_usuario = $usuario[0]['estado'];
    $tiempo_actual = strtotime(date("Y-m-d H:i:s"));
    $tiempo_ultimo_intento = strtotime($ultimo_intento);
    $diferencia_minutos = ($tiempo_actual - $tiempo_ultimo_intento) / 60;

    // Verificar si el usuario está inactivo
    if ($estado_usuario === "Inactivo") {
        $response->appendResponse(alerta("error", "Cuenta inactiva", "El usuario está inactivo. Contacte al administrador."));
        return $response;
    }

    // Manejar casos inesperados para el estado del usuario
    if ($estado_usuario !== "Activo" && $estado_usuario !== "Inactivo") {
        $response->appendResponse(alerta("error", "Error desconocido", "Estado de usuario no válido. Contacte al administrador."));
        return $response;
    }

    // Verificar si se superó el número máximo de intentos
    if ($intentos >= 3 && $diferencia_minutos < 15) {
        $tiempo_restante = 15 - floor($diferencia_minutos);
        $mensaje = "Has superado el número máximo de intentos. Inténtalo nuevamente en " . $tiempo_restante . " minuto(s).";
        $response->appendResponse(alerta("error", "Cuenta bloqueada", $mensaje));
        return $response;
    }

    // Validar credenciales con datos adicionales de sucursal y empresa
    $usuario_valido = $database->select("usuarios", 
    [
        "[>]sucursales" => ["idsucursal" => "id"], // Unión con sucursales
        "[>]empresas" => ["sucursales.idempresa" => "id"] // Unión con empresas
    ],
    [
        "empresas.id(empresa_id)",
        "empresas.estado(empresa_estado)",
        "sucursales.estado(sucursal_estado)",
        "usuarios.id(usuario_id)",
        "usuarios.idsucursal",
        "usuarios.estado(usuario_estado)"
    ], 
    [
        "AND" => [
            "usuarios.estado" => "Activo",
            "usuarios.usuario" => $user,
            "usuarios.password" => $pass
        ]
    ]);

    if (!empty($usuario_valido)) {
        // Verificar si la sucursal o la empresa están inactivas
        if ($usuario_valido[0]['sucursal_estado'] === "Inactivo") {
            $response->appendResponse(alerta("error", "Sucursal inactiva", "La sucursal asociada a esta cuenta está inactiva. Contacte al administrador."));
            return $response;
        }

        if ($usuario_valido[0]['empresa_estado'] === "Inactivo") {
            $response->appendResponse(alerta("error", "Empresa inactiva", "La empresa asociada a esta cuenta está inactiva. Contacte al administrador."));
            return $response;
        }

        // Credenciales válidas: reiniciar intentos y guardar sesión
        session_start();
        $_SESSION['idusuario'] = $usuario_valido[0]['usuario_id'];
        $_SESSION['idsucursal'] = $usuario_valido[0]['idsucursal'];
        $_SESSION['idempresa'] = $usuario_valido[0]['empresa_id'];
        $database->update("usuarios", [
            "intentos" => 0,
            "ultimo_intento" => null
        ], [
            "usuario" => $user
        ]);

        // Si seleccionó "Recuérdame"
        if (isset($form['rememberPasswordCheck']) && $form['rememberPasswordCheck'] === 'on') {
            $token = bin2hex(random_bytes(50));
            setcookie("recuerdame_alkes", $token, time() + (86400 * 30), "/"); // Cookie por 30 días

            $database->update("usuarios", [
                "token_recuerdame" => $token
            ], [
                "usuario" => $user
            ]);
        }

        $response->redirect('vistas/inicio/index.php');
    } else {
        // Credenciales inválidas
        if ($diferencia_minutos >= 15) {
            $intentos = 0;
        }

        $intentos++;
        $database->update("usuarios", [
            "intentos" => $intentos,
            "ultimo_intento" => date("Y-m-d H:i:s")
        ], [
            "usuario" => $user
        ]);

        if ($intentos >= 3) {
            $response->appendResponse(alerta("error", "Cuenta bloqueada", "Has superado el número máximo de intentos. Inténtalo nuevamente en 15 minutos."));
        } else {
            $response->appendResponse(alerta("warning", "Contraseña errónea", "Intentos restantes: " . (3 - $intentos)));
        }
    }

    return $response;
}





$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, 'login');
$jaxon->register(Jaxon::CALLABLE_FUNCTION, 'alerta');

if ($jaxon->canProcessRequest()) {
    ob_start(); // Inicia un buffer para capturar salidas
    $jaxon->processRequest(); // Procesa la solicitud de Jaxon
    $output = ob_get_clean(); // Captura la salida generada

    // Verifica si el buffer contiene salida inesperada
    if (!empty($output)) {
        error_log("Salida inesperada detectada: " . $output);
    }

    exit; // Finaliza el script después del procesamiento
}

?>
