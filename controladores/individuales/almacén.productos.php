<?php
// controlador.php
require_once(__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenProductos extends alkesGlobal
{
    function validar($form)
    {
        
        return $this->response;
    }

    function actualizaSubCategorias($idcategoria)
    {
        $this->response->assign("idsubcategoria", "innerHTML", getSubcategorias($idcategoria));
        return $this->response;
    }

    function actualizaSubSubCategorias($idsubcategoria)
    {
        $this->response->assign("idsubsubcategoria", "innerHTML", getSubsubcategorias($idsubcategoria));
        return $this->response;
    }
    

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenProductos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












