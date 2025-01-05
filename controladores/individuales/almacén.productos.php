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

    function validarClaveSat($cadenaEscrita)
    {
        global $database;

        // Consulta usando Medoo para buscar la clave
        $registro = $database->get("cfdi_claveprodserv", ["descripcion", "palabras_similares"], [
            "c_claveprodserv" => $cadenaEscrita
        ]);

        // Verificar si se encontró un resultado
        if ($registro) {
            // La clave existe; construir el texto
            $descripcion = $registro['descripcion'];
            $palabras_similares = $registro['palabras_similares'];

            if (!empty($palabras_similares)) {
                // Si hay palabras similares, inclúyelas entre paréntesis
                $texto = "$descripcion ($palabras_similares)";
            } else {
                // Solo la descripción
                $texto = $descripcion;
            }
        } else {
            // La clave no existe
            $texto = "La clave ingresada no existe en el catálogo del SAT";
        }

        // Asignar el texto al campo de descripción usando Jaxon
        $this->response->assign("descripcion_producto_servicio", "value", $texto);

        // Retornar la respuesta Jaxon
        return $this->response;
    }


}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenProductos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












