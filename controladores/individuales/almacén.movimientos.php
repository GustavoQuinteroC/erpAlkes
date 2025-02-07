<?php
// controlador.php
require_once (__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenMovimientos extends alkesGlobal
{
    function inializarFormulario()
    {
        $this->response->assign('fechahora', 'value', date('Y-m-d\TH:i'));
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












