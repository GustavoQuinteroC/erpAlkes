<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Jaxon\Jaxon;
use function Jaxon\jaxon;
use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$url_base = $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$jaxon = jaxon();
$jaxon->setOption('core.request.uri', $url_base);

$database = new Medoo([
    'type' => $_ENV['DB_CONNECTION'],
    'host' => $_ENV['DB_HOST'],
    'port' => $_ENV['DB_PORT'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
]);

function alerta($icono, $titulo, $texto, $focus = "", $funcion = "")
{
    $respuesta = jaxon()->newResponse();
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
    $respuesta->script($script);
    return $respuesta;
}

function login($form)
{
    $response = jaxon()->newResponse();
    $user = $form['user'];
    $pass = str_replace(["'", '"'], '', $form['clave']);

    global $database;
    $usuario = $database->select("usuarios", [
        "id",
        "idsucursal",
        "intentos",
        "ultimo_intento",
        "estado",
        "password"
    ], [
        "usuario" => $user
    ]);

    if (empty($usuario)) {
        $response->appendResponse(alerta("error", "Error", "Usuario no registrado."));
        return $response;
    }

    $intentos = $usuario[0]['intentos'];
    $ultimo_intento = $usuario[0]['ultimo_intento'];
    $estado_usuario = $usuario[0]['estado'];
    $password_hash = $usuario[0]['password'];
    $tiempo_actual = strtotime(date("Y-m-d H:i:s"));
    $tiempo_ultimo_intento = strtotime($ultimo_intento);
    $diferencia_minutos = ($tiempo_actual - $tiempo_ultimo_intento) / 60;

    if ($estado_usuario === "Inactivo") {
        $response->appendResponse(alerta("error", "Cuenta inactiva", "El usuario está inactivo. Contacte al administrador."));
        return $response;
    }

    if ($estado_usuario !== "Activo" && $estado_usuario !== "Inactivo") {
        $response->appendResponse(alerta("error", "Error desconocido", "Estado de usuario no válido. Contacte al administrador."));
        return $response;
    }

    if ($intentos >= 3 && $diferencia_minutos < 15) {
        $tiempo_restante = 15 - floor($diferencia_minutos);
        $mensaje = "Has superado el número máximo de intentos. Inténtalo nuevamente en {$tiempo_restante} minuto(s).";
        $response->appendResponse(alerta("error", "Cuenta bloqueada", $mensaje));
        return $response;
    }

    if (password_verify($pass, $password_hash)) {
        $usuario_valido = $database->select("usuarios", 
        [
            "[>]sucursales" => ["idsucursal" => "id"],
            "[>]empresas" => ["sucursales.idempresa" => "id"]
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
                "usuarios.usuario" => $user
            ]
        ]);

        if ($usuario_valido[0]['sucursal_estado'] === "Inactivo") {
            $response->appendResponse(alerta("error", "Sucursal inactiva", "La sucursal asociada a esta cuenta está inactiva. Contacte al administrador."));
            return $response;
        }

        if ($usuario_valido[0]['empresa_estado'] === "Inactivo") {
            $response->appendResponse(alerta("error", "Empresa inactiva", "La empresa asociada a esta cuenta está inactiva. Contacte al administrador."));
            return $response;
        }

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

        if (isset($form['rememberPasswordCheck']) && $form['rememberPasswordCheck'] === 'on') {
            $token = bin2hex(random_bytes(50));
            setcookie("recuerdame_alkes", $token, time() + (86400 * 30), "/");
            $database->update("usuarios", [
                "token_recuerdame" => $token
            ], [
                "usuario" => $user
            ]);
        }

        $response->redirect('vistas/inicio/index.php');
    } else {
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
    ob_start();
    $jaxon->processRequest();
    $output = ob_get_clean();

    if (!empty($output)) {
        error_log("Salida inesperada detectada: " . $output);
    }

    exit;
}
