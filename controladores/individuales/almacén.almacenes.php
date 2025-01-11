<?php
session_start();
// controlador.php
require_once(__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenAlmacenes extends alkesGlobal
{
    function inializarFormulario()
    {
        global $database;

        return $this->response;
    }


}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenAlmacenes::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












