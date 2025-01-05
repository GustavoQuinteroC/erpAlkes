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

    function modalAddImpuesto()
    {
        // Definir los campos para el formulario modal
        $campos = [
            [
                'id' => 'impuesto',
                'label' => 'Impuesto',
                'type' => 'select',
                'options' => getCfdiImpuesto(),
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'tipoImpuesto',
                'label' => 'Tipo de Impuesto',
                'type' => 'select',
                'options' => '
                    <option value="" selected disabled>Elije una opción...</option>
                    <option value="Traslado">Traslado</option>
                    <option value="Retencion">Retencion</option>',
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'tipoFactor',
                'label' => 'Tipo de factor',
                'type' => 'select',
                'options' => getCfdiTipoFactor(),
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'porcentaje',
                'label' => 'Porcentaje en entero (ej: 16)',
                'type' => 'number',
                'value' => '', // Valor por defecto
            ],
        ];

        // Título del modal
        $titulo = 'Agregar Impuesto';

        // Callback que se ejecutará al guardar
        $funcionCallBack = 'JaxonalmacenProductos.addImpuesto'; // Nombre de la función JavaScript

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function addImpuesto($form)
    {
        // Definir las reglas de validación
        $reglas = [
            'impuesto' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'tipoImpuesto' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'tipoFactor' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'porcentaje' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 0, 'pattern' => '/^\d+$/'],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);
        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            $this->modalAddImpuesto();
            // Mostrar alerta con el error
            $this->alerta(
                "Error en la validación",
                $error,
                "error",
                $campo
            );
            // Retornar la respuesta Jaxon
            return $this->response;
        }

        // Si la validación es exitosa, agregar el impuesto a la sesión
        $_SESSION['partidasImpuestos' . $_GET['rand']][] = [
            'impuesto' => $form['impuesto'],
            'tipoImpuesto' => $form['tipoImpuesto'],
            'tipoFactor' => $form['tipoFactor'],
            'porcentaje' => $form['porcentaje'],
            'estado' => 'Activo',
        ];

        // Retornar la respuesta Jaxon
        return $this->response;
    }




}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenProductos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












