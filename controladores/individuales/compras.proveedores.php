<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class comprasProveedores extends alkesGlobal
{
    function inializarFormulario()
    {
        global $database;
        if (!getParametro("facturacion")) {
            $this->response->script("document.getElementById('datosFiscales').classList.add('d-none');");
            $this->response->script("document.getElementById('datosFiscales').classList.remove('d-block');");
        }
        if ($_GET['id'] != 0) {
            if (!validarEmpresaPorRegistro("socios", $_GET['id'])) {
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            } else {
                //consultas a la bd para obtener los datos necesarios para la consulta del almacén
                $socio = $database->get('socios', '*', ['id' => $_GET['id']]);

                // Asignaciones a los campos
                $this->response->assign("smallTitulos", "innerHTML", $socio['nombre_comercial']);
                $this->response->assign("clave", "value", $socio['clave']);
                $this->response->assign("nombre_comercial", "value", $socio['nombre_comercial']);
                $this->response->assign("nivel", "value", $socio['nivel']);
                $this->response->assign("correo", "value", $socio['correo']);
                $this->response->assign("web", "value", $socio['web']);
                $this->response->assign("telefono", "value", $socio['telefono']);
                $this->response->assign("telefono_fijo", "value", $socio['telefono_fijo']);
                $this->response->assign("fax", "value", $socio['fax']);
                $this->response->assign("banco", "value", $socio['banco']);
                $this->response->assign("cuenta", "value", $socio['cuenta']);
                $this->response->assign("descuento", "value", $socio['descuento']);
                $this->response->assign("estado", "value", $socio['estado']);
                $this->response->assign("tipo", "value", $socio['tipo']);
                $this->response->assign("razon_social", "value", $socio['razon_social']);
                $this->response->assign("rfc", "value", $socio['rfc']);
                $this->response->assign("idc_metodopago", "value", $socio['idc_metodopago']);
                $this->response->assign("calle", "value", $socio['calle']);
                $this->response->assign("numero_exterior", "value", $socio['numero_exterior']);
                $this->response->assign("numero_interior", "value", $socio['numero_interior']);
                $this->response->assign("codigo_postal", "value", $socio['codigo_postal']);
                $this->response->assign("credito_monto_proveedor", "value", $socio['credito_monto_proveedor']);
                $this->response->assign("credito_dias_proveedor", "value", $socio['credito_dias_proveedor']);
                $this->response->assign("notas", "value", $socio['notas']);
                $this->response->assign("idc_usocfdi", "innerHTML", getCfdiUsoCfdi($socio['idc_regimen']));
                $this->response->assign("idc_estado", "innerHTML", getCfdiEstado());
                $this->response->assign("idc_municipio", "innerHTML", getCfdiMunicipio($socio['idc_estado']));
                $this->response->assign("idc_colonia", "innerHTML", getCfdiColonia($socio['codigo_postal']));
                // Actualizar select2 sin disparar el evento onchange
                $this->response->script('
                    // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
                    document.getElementById("idc_regimen").onchange = null;

                    // Asignar valores a los campos select2 sin disparar onchange
                    $("#idvendedor").val("' . $socio['idvendedor'] . '").trigger("change.select2");
                    $("#idc_regimen").val("' . $socio['idc_regimen'] . '").trigger("change.select2");
                    $("#idc_moneda").val("' . $socio['idc_moneda'] . '").trigger("change.select2");
                    $("#idc_formapago").val("' . $socio['idc_formapago'] . '").trigger("change.select2");
                    $("#idc_usocfdi").val("' . $socio['idc_usocfdi'] . '").trigger("change.select2");
                    $("#idc_estado").val("' . $socio['idc_estado'] . '").trigger("change.select2");
                    $("#idc_municipio").val("' . $socio['idc_municipio'] . '").trigger("change.select2");
                    $("#idc_colonia").val("' . $socio['idc_colonia'] . '").trigger("change.select2");

                    // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
                    document.getElementById("idc_regimen").onchange = function() {
                        JaxoncomprasProveedores.cambiarUsoCfdi(this.value);
                    };
                ');
            }
        }
        // Obtén la ruta actual dividida en segmentos
        $ruta = explode(DIRECTORY_SEPARATOR, getcwd());

        // Calcular nombres de módulos semidinámicamente
        $modulo = $ruta[(count($ruta) - 2)];
        $submodulo = $ruta[(count($ruta) - 1)];
        $subsubmodulo = null;

        if (validaPermisoEditarModulo($modulo, $submodulo, $subsubmodulo)) {
            $rand = $_GET['rand']; // Obtener el valor dinámico
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-primary btn-sm' id='btnguardar' name='btnguardar' type='button' onclick='JaxoncomprasProveedores.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                    <i class='bi bi-floppy'></i> Guardar
                </button>
            ");
        }
        return $this->response;
    }

    function cambiarUsoCfdi($idregimen)
    {
        $this->response->assign("idc_usocfdi", "innerHTML", getCfdiUsoCfdi($idregimen));
        return $this->response;
    }

    function ajustesCodigoPostal($codigoPostal)
    {
        global $database;
        $nuevo = ($_GET['id'] == 0) ? true : false;
        $registroCodigoPostal = $database->get("cfdi_codigopostal", "*", ["c_codigopostal" => $codigoPostal]);
        $registroEstado = $database->get("cfdi_estado", "*", ["c_estado" => $registroCodigoPostal['c_estado']]);
        $registroMunicipio = $database->get("cfdi_municipio", "*", ["c_estado" => $registroCodigoPostal['c_estado'], "c_municipio" => $registroCodigoPostal['c_municipio']]);


        $this->response->assign("idc_estado", "innerHTML", getCfdiEstado());
        $this->response->assign("idc_municipio", "innerHTML", getCfdiMunicipio($registroEstado['id']));
        $this->response->assign("idc_colonia", "innerHTML", getCfdiColonia($codigoPostal));


        if ($nuevo) {
            $this->response->script('
                $("#idc_estado").val("' . $registroEstado['id'] . '").trigger("change");
                $("#idc_municipio").val("' . $registroMunicipio['id'] . '").trigger("change");
            ');
        }
        //en caso de que el registro no sea nuevo, entonces llenar con los datos ya almacenados
        else {
            $registroSocio = $database->get("socios", "*", ["id" => $_GET['id']]);
            $this->response->script('
                $("#idc_estado").val("' . $registroSocio['idc_estado'] . '").trigger("change");
                $("#idc_municipio").val("' . $registroSocio['idc_municipio'] . '").trigger("change");
                $("#idc_colonia").val("' . $registroSocio['idc_colonia'] . '").trigger("change");
            ');
        }
        return $this->response;
    }

    function validar($form)
    {
        $facturacion = getParametro("facturacion");//devuelve 0 o 1 (true o false) para determinar si la empresa hace facturacion o no
        // Definir las reglas de validación
        $reglas = [
            'nombre_comercial' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'nivel' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Sucursal', 'Empresa']],
            'idvendedor' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'correo' => ['obligatorio' => false, 'tipo' => 'email', 'max' => 254],
            'web' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'telefono' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'telefono_fijo' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'fax' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'banco' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'cuenta' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'descuento' => ['obligatorio' => true, 'tipo' => 'float', 'min' => 0.0000, 'max_val' => 99999999.9999],
            'estado' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Activo', 'Inactivo']],
            'tipo' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'in' => ['Cliente', 'Proveedor', 'Ambos']],

            'razon_social' => ['obligatorio' => $facturacion, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'rfc' => ['obligatorio' => $facturacion, 'tipo' => 'string', 'min' => 1, 'max' => 254, 'pattern' => '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{2}[A0-9]$/'],
            'idc_regimen' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 1],
            'idc_moneda' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 1],
            'idc_metodopago' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 1],
            'idc_usocfdi' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 1],
            'idc_formapago' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 1],
            'calle' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'numero_exterior' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'numero_interior' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'codigo_postal' => ['obligatorio' => $facturacion, 'tipo' => 'int', 'min_val' => 10000, 'max_val' => 99999],
            'idc_estado' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idc_municipio' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idc_colonia' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'credito_monto_proveedor' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0.0000, 'max_val' => 99999999.9999],
            'credito_dias_proveedor' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 0],
            'notas' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 500],
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
        } else {
            $resultadoValidacionRepetidoRfc = verificaRegistroRepetido("empresa", "socios", "rfc", $form['rfc'], $_GET['id']);
            if ($resultadoValidacionRepetidoRfc) {
                // El registro está repetido, mostrar un error
                $this->alerta('Error', 'Ya existe existe un proveedor con este RFC', 'error', 'rfc', true, false);
                return $this->response;
            } else {
                $this->guardar($form);
            }
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardar($form)
    {
        global $database;
        $this->response->assign("btnguardar", "disabled", "disabled"); //Deshabilitar boton de guardar para evitar que el usuario de click varias veces
        // Campos que se insertarán o actualizarán
        $datos = [
            'idsucursal' => $_SESSION['idsucursal'] ?? 0,
            'nivel' => $form['nivel'] ?? 'Sucursal',
            'idempresa' => $_SESSION['idempresa'] ?? 0,
            'idc_formapago' => $form['idc_formapago'] ?? 0,
            'idc_usocfdi' => $form['idc_usocfdi'] ?? 0,
            'idc_metodopago' => $form['idc_metodopago'] ?? 0,
            'idc_regimen' => $form['idc_regimen'] ?? 0,
            'idc_colonia' => $form['idc_colonia'] ?? 0,
            'idc_municipio' => $form['idc_municipio'] ?? 0,
            'idc_estado' => $form['idc_estado'] ?? 0,
            'idcreador' => $_SESSION['idusuario'] ?? 0,
            'idc_moneda' => $form['idc_moneda'] ?? 0,
            'idvendedor' => $form['idvendedor'] ?? 0,
            'clave' => ($_GET['id'] > 0 ? $form['clave'] ?? '' : getConsecutivo("claves_socios")), // Ternario para clave
            'razon_social' => $form['razon_social'] ?? '',
            'nombre_comercial' => $form['nombre_comercial'] ?? '',
            'rfc' => $form['rfc'] ?? '',
            'correo' => $form['correo'] ?? '',
            'web' => $form['web'] ?? '',
            'fax' => $form['fax'] ?? '',
            'telefono' => $form['telefono'] ?? '',
            'telefono_fijo' => $form['telefono_fijo'] ?? '',
            'calle' => $form['calle'] ?? '',
            'numero_exterior' => $form['numero_exterior'] ?? '',
            'numero_interior' => $form['numero_interior'] ?? '',
            'codigo_postal' => $form['codigo_postal'],
            'banco' => $form['banco'] ?? '',
            'cuenta' => $form['cuenta'] ?? '',
            'descuento' => $form['descuento'],
            'estado' => $form['estado'],
            'tipo' => $form['tipo'],
            'credito_dias_proveedor' => $form['credito_dias_proveedor'],
            'credito_monto_proveedor' => $form['credito_monto_proveedor'],
            'notas' => $form['notas'] ?? '',
        ];

        if ($_GET['id'] > 0) {
            try {
                // Actualizar el registro si existe un ID
                $database->update('socios', $datos, ['id' => $_GET['id']]);
                $this->alerta(
                    "¡ACTUALIZADO!",
                    "El proveedor ha sido actualizado con exito",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL ACTUALIZAR!",
                    "El proveedor no se pudo actualizar correctamente, por favor reporte este problema con el administrador del sistema",
                    "error"
                );
            }
        } else {
            try {
                // Insertar un nuevo registro si no existe ID
                $database->insert('socios', $datos);
                $this->alerta(
                    "¡GUARDADO!",
                    "El proveedor ha sido guardado con exito",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL GUARDAR!",
                    "El proveedor no se pudo guardar correctamente, por favor reporte este problema con el administrador del sistema",
                    "error"
                );
            }
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, comprasProveedores::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












