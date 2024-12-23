<?php
// controlador.php
include (__DIR__ .'/../../globales/jaxon/funciones.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class Inicio extends alkesGlobal
{
    function validar($form)
    {
        
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, Inicio::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












