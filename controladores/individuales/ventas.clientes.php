<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class ventasClientes extends alkesGlobal
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
                // Consultas a la BD para obtener los datos necesarios para la consulta del almacén
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
                $this->response->assign("credito_monto_cliente", "value", $socio['credito_monto_cliente']);
                $this->response->assign("credito_dias_cliente", "value", $socio['credito_dias_cliente']);
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
                        JaxonventasClientes.cambiarUsoCfdi(this.value);
                    };
                ');

                // Cargar las subcuentas del socio
                $this->cargarSubcuentas($socio['id']);
                $this->tablaSubcuentas();
            }
        }
        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' id='btnguardar' name='btnguardar' type='button' onclick='JaxonventasClientes.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                <i class='bi bi-floppy'></i> Guardar
            </button>
        ");
        return $this->response;
    }

    function cargarSubcuentas($idsociopadre)
    {
        global $database;

        // Consultar las subcuentas del socio
        $subcuentas = $database->select('socios_subcuentas', ['id', 'idsocio_hijo', 'estado', 'fecha_vencimiento'], ['idsocio_padre' => $idsociopadre]);

        // Verificar si se encontraron subcuentas
        if (empty($subcuentas)) {
            // Si no hay subcuentas, inicializar el array vacío
            $_SESSION['partidas' . $_GET['rand']] = [];
        } else {
            // Iterar sobre las subcuentas y llenarlas en la sesión
            $_SESSION['partidas' . $_GET['rand']] = [];
            foreach ($subcuentas as $subcuenta) {
                $_SESSION['partidas' . $_GET['rand']][] = [
                    'iddb' => $subcuenta['id'],               // El ID de la subcuenta
                    'idsubcuenta' => $subcuenta['idsocio_hijo'], // El ID del socio hijo (subcuenta)
                    'estado' => $subcuenta['estado'],          // Estado de la subcuenta
                    'fecha_vencimiento' => date('Y-m-d', strtotime($subcuenta['fecha_vencimiento'])) // Solo la fecha (formato Y-m-d)
                ];
            }
        }

        // No es necesario generar HTML, solo llenamos la sesión
        return $this->response;
    }

    function modalSeleccionarSubcuentas()
    {
        $this->modalSeleccionServerSide('ventas', 'clientes', '', 0, 'Activos', 'Modal', 'JaxonventasClientes.agregarSubcuentas', false, '', 'Selecciona los socios');
        return $this->response;
    }

    function agregarSubcuentas($form)
    {
        // Si la sesión de partidas aún no está definida, inicializarla
        if (!isset($_SESSION['partidas' . $_GET['rand']])) {
            $_SESSION['partidas' . $_GET['rand']] = [];
        }

        // Verificar si el idsubcuenta ya existe en la sesión
        foreach ($_SESSION['partidas' . $_GET['rand']] as $subcuenta) {
            if ($subcuenta['idsubcuenta'] == $form['seleccion']) {
                $this->alerta("Error", "La subcuenta ya está agregada.", "error");
                return $this->response;
            }
        }

        // Agregar la nueva subcuenta si no está duplicada
        $_SESSION['partidas' . $_GET['rand']][] = [
            'iddb' => 0,
            'idsubcuenta' => $form['seleccion'],
            'estado' => 'Activo',
            'fecha_vencimiento' => date('Y-m-d', strtotime('+1 day')) // Fecha de mañana
        ];

        $this->tablaSubcuentas();
        return $this->response;
    }

    function tablaSubcuentas()
    {
        $script = "tablaPartidas.clear();"; // Limpiar la tabla

        global $database;

        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $subcuenta) {
            if ($subcuenta['estado'] == 'Activo' || $subcuenta['estado'] == 'Inactivo') {
                // Obtener datos del socio (cliente)
                $socio = $database->get("socios", ["clave", "nombre_comercial"], ["id" => $subcuenta['idsubcuenta']]);

                if (!$socio) {
                    continue; // Si no se encuentra el socio, se omite esta entrada
                }

                // Botón dinámico según el estado
                $botonAccion = ($subcuenta['estado'] == 'Activo') ?
                    "<button type='button' class='btn btn-sm btn-danger' title='Desactivar' 
                        onclick='JaxonventasClientes.desactivarSubcuenta($index)'>
                        <i class='bi bi-x-circle'></i>
                    </button>" :
                    "<button type='button' class='btn btn-sm btn-success' title='Activar' 
                        onclick='JaxonventasClientes.activarSubcuenta($index)'>
                        <i class='bi bi-check-circle'></i>
                    </button>";

                // Input de fecha de vencimiento
                $inputFecha = "<input type='date' class='form-control' value='" . htmlspecialchars($subcuenta['fecha_vencimiento']) . "'
                    onblur='JaxonventasClientes.actualizarFechaVencimiento($index, this.value)'>";

                // Construcción de la fila de la tabla
                $fila = [
                    htmlspecialchars($socio['clave']), // Clave del socio
                    htmlspecialchars($socio['nombre_comercial']), // Nombre comercial
                    htmlspecialchars($subcuenta['estado']), // Estado
                    $inputFecha, // Input de fecha
                    $botonAccion // Botón de activar/desactivar
                ];

                // Convertir la fila a formato JavaScript
                $filaJS = json_encode($fila);
                $script .= "tablaPartidas.row.add($filaJS);";
            }
        }

        $script .= "tablaPartidas.draw();";
        $this->response->script($script);

        return $this->response;
    }

    function actualizarFechaVencimiento($index, $nuevaFecha)
    {

        // Validar que la fecha es válida
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFecha)) {
            $this->alerta("Error", "Fecha no válida.", "error");
            return $this->response;
        }

        // Actualizar la fecha de vencimiento en la sesión
        $_SESSION['partidas' . $_GET['rand']][$index]['fecha_vencimiento'] = $nuevaFecha;

        // Actualizar la tabla para reflejar los cambios
        $this->tablaSubcuentas();

        return $this->response;
    }


    function desactivarSubcuenta($index)
    {
        // Verificar que la subcuenta existe en la sesión
        if (!isset($_SESSION['partidas' . $_GET['rand']][$index])) {
            $this->response->script("console.error('Subcuenta no encontrada.');");
            return $this->response;
        }

        // Cambiar el estado a "Inactivo"
        $_SESSION['partidas' . $_GET['rand']][$index]['estado'] = 'Inactivo';

        // Actualizar la tabla
        $this->tablaSubcuentas();

        return $this->response;
    }

    function activarSubcuenta($index)
    {
        // Verificar que la subcuenta existe en la sesión
        if (!isset($_SESSION['partidas' . $_GET['rand']][$index])) {
            $this->response->script("console.error('Subcuenta no encontrada.');");
            return $this->response;
        }

        // Cambiar el estado a "Activo"
        $_SESSION['partidas' . $_GET['rand']][$index]['estado'] = 'Activo';

        // Actualizar la tabla
        $this->tablaSubcuentas();

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
        $facturacion = getParametro("facturacion"); // Determina si la empresa hace facturación

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
            'credito_monto_cliente' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0.0000, 'max_val' => 99999999.9999],
            'credito_dias_cliente' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 0],
            'notas' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 500],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        if ($resultadoValidacion !== true) {
            $this->alerta("Error en la validación", $resultadoValidacion['error'], "error", $resultadoValidacion['campo']);
            return $this->response;
        }

        // Validar que los datos en $_SESSION['partidas' . $_GET['rand']] sean correctos
        if (isset($_SESSION['partidas' . $_GET['rand']])) {
            foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $subcuenta) {
                if ($subcuenta['idsubcuenta'] <= 0) {
                    $this->alerta("Error", "El ID de subcuenta en la fila $index no es válido.", "error");
                    return $this->response;
                }

                if (!isset($subcuenta['fecha_vencimiento']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $subcuenta['fecha_vencimiento'])) {
                    $this->alerta("Error", "La fecha de vencimiento de una subcuenta no es válida.", "error");
                    return $this->response;
                }

                if (!isset($subcuenta['estado']) || !in_array($subcuenta['estado'], ['Activo', 'Inactivo'])) {
                    $this->alerta("Error", "El estado de una subcuenta no es válido.", "error");
                    return $this->response;
                }
            }
        }

        // Verificar si el RFC está repetido en la base de datos
        $resultadoValidacionRepetidoRfc = verificaRegistroRepetido("empresa", "socios", "rfc", $form['rfc'], $_GET['id']);
        if ($resultadoValidacionRepetidoRfc) {
            $this->alerta('Error', 'Ya existe un cliente con este RFC', 'error', 'rfc', true, false);
            return $this->response;
        }

        // Si todo es válido, proceder a guardar
        $this->guardar($form);

        return $this->response;
    }

    function guardar($form)
    {
        global $database;
        $this->response->assign("btnguardar", "disabled", "disabled"); // Deshabilitar boton de guardar para evitar que el usuario de click varias veces
        
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
            'credito_dias_cliente' => $form['credito_dias_cliente'],
            'credito_monto_cliente' => $form['credito_monto_cliente'],
            'notas' => $form['notas'] ?? '',
        ];

        // Si el ID del socio es mayor a 0, es una actualización
        if ($_GET['id'] > 0) {
            try {
                // Actualizar el registro del socio
                $database->update('socios', $datos, ['id' => $_GET['id']]);

                // Llamar a la función para guardar las subcuentas
                $this->guardarSubcuentas($_GET['id']);

                $this->alerta(
                    "¡ACTUALIZADO!",
                    "El cliente ha sido actualizado con éxito.",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL ACTUALIZAR!",
                    "El cliente no se pudo actualizar correctamente, por favor reporte este problema con el administrador del sistema.",
                    "error"
                );
            }
        } else {
            try {
                // Insertar un nuevo socio
                $database->insert('socios', $datos);
                $nuevoSocioId = $database->lastInsertId(); // Obtener el ID del socio recién insertado

                // Llamar a la función para guardar las subcuentas
                $this->guardarSubcuentas($nuevoSocioId);

                $this->alerta(
                    "¡CREADO!",
                    "El cliente ha sido creado con éxito.",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL GUARDAR!",
                    "El cliente no se pudo guardar correctamente, por favor reporte este problema con el administrador del sistema.",
                    "error"
                );
            }
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardarSubcuentas($idsocioPadre)
    {
        global $database;

        foreach ($_SESSION['partidas' . $_GET['rand']] as $subcuenta) {
            $idsubcuenta = $subcuenta['idsubcuenta'];
            $estado = $subcuenta['estado'];
            $fechaVencimiento = $subcuenta['fecha_vencimiento'];
            $iddb = $subcuenta['iddb']; // Indica si el registro ya existe en la BD

            try {
                if ($iddb == 0) {
                    // Insertar nueva subcuenta en la BD
                    $database->insert('socios_subcuentas', [
                        'idsocio_padre' => $idsocioPadre,
                        'idsocio_hijo' => $idsubcuenta,
                        'estado' => $estado,
                        'fecha_vencimiento' => $fechaVencimiento
                    ]);
                } else {
                    // Actualizar subcuenta existente en la BD
                    $database->update('socios_subcuentas', [
                        'estado' => $estado,
                        'fecha_vencimiento' => $fechaVencimiento
                    ], ['id' => $iddb]);
                }
            } catch (PDOException $e) {
                return $this->response;
            }
        }
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, ventasClientes::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












