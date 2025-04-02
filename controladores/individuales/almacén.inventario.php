<?php
// controlador.php
require_once (__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenInventario extends alkesGlobal
{
    function inializarFormulario()
    {
        
        return $this->response;
    }
}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenInventario::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












