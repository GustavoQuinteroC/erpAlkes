<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenClientes extends alkesGlobal
{
    function inializarFormulario()
    {
        global $database;
        if ($_GET['id'] != 0) {
            if (!validarEmpresaPorRegistro("almacenes", $_GET['id'])) {
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            } else {
                //consultas a la bd para obtener los datos necesarios para la consulta del almacén
                $almacen = $database->get('almacenes', '*', ['id' => $_GET['id']]);

                // Asignaciones a los campos
                $this->response->assign("smallTitulos", "innerHTML", $almacen['nombre']);
                
                
                
            }
        }
        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' type='button' value='Guardar' onclick='JaxonalmacenClientes.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                <i class='bi bi-save'></i> Guardar
            </button>
        ");
        return $this->response;
    }

    function cambiarUsoCfdi($idregimen)
    {
        $this->response->assign("idc_usocfdi","innerHTML",getCfdiUsoCfdi($idregimen));
        return $this->response;
    }

    function ajustesCodigoPostal($codigoPostal)
    {
        global $database;
        $registroCodigoPostal = $database->get("cfdi_codigopostal", "*", ["c_codigopostal" => $codigoPostal]);
        $registroEstado = $database->get("cfdi_estado", "*", ["c_estado" => $registroCodigoPostal['c_estado']]);
        $registroMunicipio = $database->get("cfdi_municipio", "*", ["c_estado" => $registroCodigoPostal['c_estado'], "c_municipio" => $registroCodigoPostal['c_municipio']]);
        
        
        $this->response->assign("idc_estado","innerHTML",getCfdiEstado());
        $this->response->assign("idc_municipio","innerHTML",getCfdiMunicipio($registroEstado['id']));
        $this->response->assign("idc_colonia","innerHTML",getCfdiColonia($codigoPostal));

        $this->response->script('
                $("#idc_estado").val("' . $registroEstado['id'] . '").trigger("change");
                $("#idc_municipio").val("' . $registroMunicipio['id'] . '").trigger("change");
            ');
        return $this->response;
    }

    function validar($form)
    {
        // Definir las reglas de validación
        $reglas = [
            'clave' =>            ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'nombre_comercial' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'nivel' =>            ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Sucursal', 'Empresa']],
            'idvendedor' =>       ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'correo' =>           ['obligatorio' => false, 'tipo' => 'email', 'max' => 254],
            'web' =>              ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'telefono' =>         ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'telefono_fijo' =>    ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'fax' =>              ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'banco' =>            ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'cuenta' =>           ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'descuento' =>        ['obligatorio' => true, 'tipo' => 'float', 'min' => 0.0000, 'max_val' => 99999999.9999],
            'status' =>           ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Activo', 'Inactivo', 'Suspendido']],
            'tipo' =>             ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Cliente', 'Proveedor', 'Ambos']],

            'razon_social' =>          ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'rfc' =>                   ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'pattern' => '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{2}[A0-9]$/'],
            'idc_regimen' =>           ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_moneda' =>            ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_metodopago' =>        ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_usocfdi' =>           ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_formapago' =>         ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'calle' =>                 ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'numero_exterior' =>       ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'numero_interior' =>       ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'codigo_postal' =>         ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 10000, 'max_val' => 99999],
            'idc_estado' =>            ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idc_municipio' =>         ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idc_colonia' =>           ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'credito_monto_cliente' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0.0000, 'max_val' => 99999999.9999],
            'credito_dias_cliente' =>  ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 0],
            'notas' =>                 ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

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
        else
        {
            $this->guardar($form);
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardar($form)
    {
        
        // Retornar la respuesta Jaxon
        return $this->response;
    }
}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenClientes::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












