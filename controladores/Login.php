<?php
require_once("/app/9001/controladores/globales/php/funciones.php");

use Jaxon\Jaxon;
use function Jaxon\jaxon;
use Jaxon\Response\Response;
use Medoo\Medoo;

$url_base = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$jaxon = jaxon();
$jaxon->setOption('core.request.uri', $url_base);


function login($form)
{
    
    $response = jaxon()->newResponse();
    $user = $form['user'];
    $pass = str_replace("'", "", $form['clave']);
    $pass = str_replace('"', '', $pass);
    $pass = sha1($pass);
    
    $database = new Medoo();
    // Realizar la consulta con Medoo
    $usuario = $database->select("usuarios", [
        "id",
        "identidad",
    ], [
        "AND" => [
            "estado" => "Activo",
            "usuario" => $user,
            "password" => $pass
        ]
    ]);
    if (!empty($usuario)) {
        session_start();
        $_SESSION['idusuario'] = $usuario[0]['id'];
        $response->redirect('Vistas/Dashboard/index.php');
    } else {
        $response->alert("Usuario y/o clave inválido error:");
    }

    return $response;
}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, 'login');

if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}
?>