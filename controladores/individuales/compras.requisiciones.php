<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class comprasRequisiciones extends alkesGlobal
{
    function inializarFormulario()
    {
        registroLog('inializarFormulario', 'Inicializando formulario', []);
        global $database;

        $rand = $_GET['rand']; // Obtener el valor dinámico
        // Obtén la ruta actual dividida en segmentos
        $ruta = explode(DIRECTORY_SEPARATOR, getcwd());

        // Calcular nombres de módulos semidinámicamente
        $modulo = $ruta[(count($ruta) - 2)];
        $submodulo = $ruta[(count($ruta) - 1)];
        $subsubmodulo = null;

        if (validaPermisoEditarModulo($modulo, $submodulo, $subsubmodulo)) {
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-primary btn-sm' type='button' id='btnborrador' name='btnborrador' onclick='JaxoncomprasRequisiciones.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"borrador\");'>
                    <i class='bi bi-floppy'></i> Borrador
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-success btn-sm' type='button' id='btnprocesar' name='btnprocesar' onclick='JaxoncomprasRequisiciones.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"procesar\");'>
                    <i class='bi bi-check2-circle'></i> Procesar
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-danger btn-sm' type='button' id='btncancelar' name='btncancelar' onclick='JaxoncomprasRequisiciones.modalConfirmacionCancelar();'>
                    <i class='bi bi-x-circle'></i> Cancelar
                </button>
            ");
        } else {
            $this->response->assign('addPartidas', 'disabled', 'disabled');
        }
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-secondary btn-sm' type='button' id='btnimprimir' name='btnimprimir' onclick='JaxoncomprasRequisiciones.imprimir();'>
                <i class='bi bi-printer'></i> Imprimir
            </button>
        ");

        if ($_GET['id'] == 0) {
            registroLog('inializarFormulario', 'Iniciando un registro nuevo', []);
            $usuario = $database->get("usuarios", "*", ["id" => $_SESSION['idusuario']]);
            $this->response->assign('solicita', 'value', $usuario['nombre']);
            $this->response->assign('fecha', 'value', date('Y-m-d'));
            $this->response->assign('vencimiento', 'value', date('Y-m-d', strtotime('+30 days')));
            $this->response->script('$("#idc_moneda").val("102").trigger("change.select2");');
            $this->response->script('$("#iddepartamento").val("' . $usuario['iddepartamento'] . '").trigger("change.select2");');
            $this->response->assign('btncancelar', 'disabled', 'disabled');
            $this->response->assign('btnimprimir', 'disabled', 'disabled');
        } else {
            registroLog('inializarFormulario', 'Consultando un registro ya registrado', []);
            $this->response->script("document.getElementById('cardPartidas').classList.remove('d-none');");

            if (!validarEmpresaPorRegistro("cuentas_cargos", $_GET['id'])) {
                registroLog('inializarFormulario', 'Registro no pertenece a la empresa', []);
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            } else {
                $cuenta_cargo = $database->get("cuentas_cargos", "*", ["id" => $_GET['id']]);
                $this->response->assign('folio', 'value', $cuenta_cargo['folio']);
                $this->response->assign('fecha', 'value', $cuenta_cargo['fecha']);
                $this->response->assign('fecha_vencimiento', 'value', $cuenta_cargo['fecha_vencimiento']);
                $this->response->assign('documento', 'value', $cuenta_cargo['documento']);
                $this->response->assign('saldo', 'value', $cuenta_cargo['saldo']);
                $this->response->assign('idc_metodopago', 'value', $cuenta_cargo['idc_metodopago']);
                $this->response->assign('referencia', 'value', $cuenta_cargo['referencia']);
                $this->response->assign('banco', 'value', $cuenta_cargo['banco']);
                $this->response->assign('cuenta_bancaria', 'value', $cuenta_cargo['cuenta_bancaria']);
                $this->response->assign('notas', 'value', $cuenta_cargo['notas']);

                $this->response->script('
                    document.getElementById("idsocio").onchange = null;
                    document.getElementById("idsubcuenta").onchange = null;

                    $("#idsocio").val("' . $cuenta_cargo['idsocio'] . '").trigger("change.select2");
                    $("#idsubcuenta").val("' . $cuenta_cargo['idsubcuenta'] . '").trigger("change.select2");
                    $("#idconcepto").val("' . $cuenta_cargo['idconcepto'] . '").trigger("change.select2");
                    $("#idc_moneda").val("' . $cuenta_cargo['idc_moneda'] . '").trigger("change.select2");

                    document.getElementById("idsocio").onchange = function() {
                        JaxoncomprasRequisiciones.cargarSocio({ seleccion: this.value });
                    };
                    document.getElementById("idsubcuenta").onchange = function() {
                        JaxoncomprasRequisiciones.cargarSubcuenta({ seleccion: this.value }, document.getElementById(\'idsocio\').value);
                    };
                ');

                $this->response->assign("smallTitulos", "innerHTML", $cuenta_cargo['folio']);
                $this->tablaPartidas($_GET['id']);

                switch ($cuenta_cargo['estado']) {
                    case 'Borrador':
                        // No se debe permitir agregar partidas en estado Borrador
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;

                    case 'Activa':
                        // Solo addPartidas habilitado
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        break;

                    case 'Inactiva':
                    case 'Vencida':
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;

                    case 'Pagada':
                    case 'Cancelada':
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        $this->response->assign('btncancelar', 'disabled', 'disabled');
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;
                }

                // En todos los estados menos 'Borrador', se bloquea edición
                if ($cuenta_cargo['estado'] != 'Borrador') {
                    $this->response->assign('botonBuscarSocio', 'disabled', 'disabled');
                    $this->response->assign('botonBuscarSubcuenta', 'disabled', 'disabled');

                    // Solo desactiva addPartidas si el estado NO es 'Activa'
                    if ($cuenta_cargo['estado'] != 'Activa') {
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                    }

                    $this->response->assign('fecha', 'readOnly', 'readOnly');
                    $this->response->assign('fecha_vencimiento', 'readOnly', 'readOnly');
                    $this->response->assign('documento', 'readOnly', 'readOnly');
                    $this->response->assign('saldo', 'readOnly', 'readOnly');
                    $this->response->assign('referencia', 'readOnly', 'readOnly');
                    $this->response->assign('banco', 'readOnly', 'readOnly');
                    $this->response->assign('cuenta_bancaria', 'readOnly', 'readOnly');
                    $this->response->assign('notas', 'readOnly', 'readOnly');

                    $this->deshabilitaSelect('idsocio');
                    $this->deshabilitaSelect('idsubcuenta');
                    $this->deshabilitaSelect('idconcepto');
                    $this->deshabilitaSelect('idc_moneda');
                    $this->deshabilitaSelect('idc_metodopago');
                }
            }
        }
        return $this->response;
    }

    function modalSeleccionarSocio()
    {
        registroLog('modalSeleccionarSocio', 'Abriendo modal para seleccionar socio', []);
        $this->modalSeleccionServerSide('ventas', 'clientes', '', 0, 'Activos', 'Modal', 'JaxoncomprasRequisiciones.cargarSocio', false, '', 'Selecciona Un Cliente');
        return $this->response;
    }

    function cargarSocio($form)
    {
        registroLog('cargarSocio', 'Cargando socio seleccionado', ['socioSeleccionado' => $form['seleccion']]);
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsocio").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idsocio").val("' . $form['seleccion'] . '").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsocio").onchange = function() {
                JaxoncomprasRequisiciones.cargarSocio(this.value);
            };
        ');
        $this->response->assign("iddireccion_entrega", "value", "");
        $this->response->assign("detalleDireccion_entrega", "value", "");
        $this->response->assign("idsubcuenta", "innerHTML", getSubcuentas($form['seleccion']));
        $this->response->assign("iddireccion_entrega", "innerHTML", getDirecciones($form['seleccion']));
        return $this->response;
    }

    function modalSeleccionarSubcuenta($idsocio)
    {
        global $database;

        registroLog('modalSeleccionarSubcuenta', 'Abriendo modal para seleccionar subcuenta', ['idsocio' => $idsocio]);

        // Validar que $idsocio sea un valor válido
        if (empty($idsocio) || $idsocio == 0) {
            registroLog('modalSeleccionarSubcuenta', 'Socio no válido', ['idsocio' => $idsocio]);
            $this->alerta("¡ERROR!", "Antes debe seleccionar un cliente válido.", "error");
            return $this->response;
        }

        // Obtener las subcuentas del socio padre con los datos de la tabla socios
        $subcuentas = $database->select("socios_subcuentas", [
            "[>]socios" => ["idsocio_hijo" => "id"]
        ], [
            "socios_subcuentas.id",
            "socios.clave",
            "socios.nombre_comercial",
            "socios_subcuentas.fecha_vencimiento"
        ], [
            "socios_subcuentas.idsocio_padre" => $idsocio,
            "socios_subcuentas.estado" => 'Activo'
        ]);

        // Verificar si hay subcuentas
        if (empty($subcuentas)) {
            registroLog('modalSeleccionarSubcuenta', 'No hay subcuentas registradas', ['idsocio' => $idsocio]);
            $this->alerta("¡SIN SUBCUENTAS!", "Este cliente no tiene subcuentas registradas.", "warning");
            return $this->response;
        }

        // Encabezados de la tabla (sin incluir ID)
        $columnas = ['Clave', 'Nombre Comercial', 'Estado', 'Fecha Vencimiento'];

        // Construcción de los datos
        $data = array();
        foreach ($subcuentas as $subcuenta) {
            $data[] = array(
                $subcuenta['id'], // ID en el índice 0
                $subcuenta['clave'],
                $subcuenta['nombre_comercial'],
                $subcuenta['estado'],
                date('Y-m-d', strtotime($subcuenta['fecha_vencimiento'])), // Solo la fecha
            );
        }

        // Llamar a la función modalSeleccion para mostrar la modal
        $this->modalSeleccion(
            "Seleccionar Subcuenta",
            $columnas,
            $data,
            "JaxoncomprasRequisiciones.cargarSubcuenta",
            true,
            false,
            ", $idsocio"
        );
        return $this->response;
    }

    function cargarSubcuenta($form, $idsocioPrincipal)
    {
        registroLog('cargarSubcuenta', 'Cargando subcuenta seleccionada', ['subcuentaSeleccionada' => $form['seleccion'], 'idsocioPrincipal' => $idsocioPrincipal]);
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idsubcuenta").val("' . $form['seleccion'] . '").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = function() {
                JaxoncomprasRequisiciones.cargarSubcuenta(this.value);
            };
        ');
        $this->response->assign("iddireccion_entrega", "value", "");
        $this->response->assign("detalleDireccion_entrega", "value", "");
        $this->response->assign("iddireccion_entrega", "innerHTML", getDirecciones($idsocioPrincipal, $form['seleccion']));
        return $this->response;
    }

    function cambiarDireccion($idDireccion, $tipoDireccion)
    {
        if (!empty($idDireccion)) {
            global $database;
            $direccion = $database->get("direcciones", "*", ["id" => $idDireccion]);
            $colonia = $database->get("cfdi_colonia", "*", ["id" => $direccion['idc_colonia']]);
            $municipio = $database->get("cfdi_municipio", "*", ["id" => $direccion['idc_municipio']]);
            $estado = $database->get("cfdi_estado", "*", ["id" => $direccion['idc_estado']]);

            registroLog('cambiarDireccion', 'Cambiando dirección', ['idDireccion' => $idDireccion, 'tipoDireccion' => $tipoDireccion]);

            $html = $direccion['calle']
                . " " . $direccion['no_exterior']
                . " " . $direccion['no_interior']
                . ", " . $colonia['nombre']
                . ", " . $direccion['cp']
                . ", " . $municipio['descripcion']
                . ", " . $estado['nombre_estado'];
            $this->response->assign("detalleDireccion_$tipoDireccion", "value", $html);
        } else {
            registroLog('cambiarDireccion', "Cambiando direccion a otra..., por lo cual se libera el campo html detalleDireccion_$tipoDireccion", []);
            $this->response->assign("detalleDireccion_$tipoDireccion", "readOnly", "");
        }

        return $this->response;
    }

    function modalSeleccionarProductos()
    {
        registroLog('modalSeleccionarProductos', 'Abriendo modal para seleccionar productos inventariables y activos', []);
        $this->modalSeleccionServerSide('almacen', 'productos', '', 0, 'InventariablesActivos', 'Modal', 'JaxoncomprasRequisiciones.addProductos', true, '', 'Seleccionar productos');
        return $this->response;
    }

    function cargarProductoAutocomplete($id)
    {
        $form['seleccion'][] = $id;
        $this->addProductos($form);
        $this->response->assign('nuevo_codigo_barras', 'value', '');
        $this->response->assign('nuevo_nombre', 'value', '');
        $this->response->assign('nuevo_descripcion', 'value', '');
        $this->response->assign('nuevo_unidad', 'value', '');
        $this->alerta(
            "¡Encontrado!",
            "El producto seleccionado ya esta registrado y no es necesario agregarlo como producto nuevo",
            "success"
        );
        return $this->response;
    }

    //Agrega los productos seleccionados al arreglo de partidas en sesión
    function addProductos($form)
    {
        registroLog('addProductos', 'Agregando productos seleccionados', ['productosSeleccionados' => $form['seleccion']]);
        global $database;

        $productosDuplicados = [];   // Para nombres de productos que ya existían
        $productosAgregados = false;

        // Verifica si existe el array 'seleccion' y contiene elementos
        if (isset($form['seleccion']) && is_array($form['seleccion'])) {
            foreach ($form['seleccion'] as $idproducto) {
                // ¿Ya existe una partida ACTIVA con este producto?
                $existe = false;
                foreach ($_SESSION['partidas' . $_GET['rand']] as $partida) {
                    if ($partida['idproducto'] == $idproducto && $partida['estado'] === 'Activo') {
                        $existe = true;
                        break;
                    }
                }

                // Obtiene la info completa del producto
                $producto = $database->get("productos", "*", ["id" => $idproducto]);

                if ($existe) {
                    registroLog('addProductos', 'Producto duplicado (ya agregado)', ['idproducto' => $idproducto]);
                    $productosDuplicados[] = $producto['nombre'] ?: "Producto desconocido";
                } else {
                    // Nuevo registro en el arreglo de partidas
                    $_SESSION['partidas' . $_GET['rand']][] = [
                        'iddb' => 0,
                        'idproducto' => $producto['id'],
                        'idc_claveunidad' => $producto['idc_claveunidad'],
                        'codigo_barras' => $producto['codigo_barras'],
                        'nombre' => $producto['nombre'],
                        'descripcion' => $producto['descripcion'],
                        'lote_serie' => $producto['lote_serie'],
                        'cantidad' => 0,
                        'estado' => 'Activo',
                        'notas' => '',
                    ];
                    $productosAgregados = true;
                }
            }
        }

        // Refresca la tabla de partidas
        $this->tablaPartidas();

        // Mensajes
        if (!empty($productosDuplicados)) {
            registroLog('addProductos', 'Productos duplicados encontrados', ['productosDuplicados' => $productosDuplicados]);
            $mensaje = "Algunos productos ya existían en las partidas y no se agregaron:<br>" .
                implode("<br>", $productosDuplicados);
            $this->alerta("Productos duplicados", $mensaje, "warning");
        } elseif ($productosAgregados) {
            registroLog('addProductos', 'Productos agregados exitosamente', []);
        } else {
            registroLog('addProductos', 'No se agregó ningún producto', []);
            $this->alerta("Sin cambios", "No se agregó ningún producto.", "info");
        }

        return $this->response;
    }

    function agregarProductoSinRegistro($form)
    {
        registroLog('agregarProductoSinRegistro', 'Agregando producto sin registro', ['formulario_enviado' => $form]);
        global $database;

        // Validar campos requeridos
        $campos_requeridos = [
            'nuevo_codigo_barras',
            'nuevo_nombre',
            'nuevo_descripcion',
            'nuevo_unidad',
            'nuevo_lote_serie'
        ];

        foreach ($campos_requeridos as $campo) {
            if (!isset($form[$campo]) || empty($form[$campo])) {
                $this->alerta("Invalido", "El campo '" . str_replace('_', ' ', $campo) . "' es obligatorio.", "info", $campo);
                return $this->response;
            }
        }

        // Nuevo registro en el arreglo de partidas
        $_SESSION['partidas' . $_GET['rand']][] = [
            'iddb' => 0,
            'idproducto' => 0,
            'idc_claveunidad' => $form['nuevo_unidad'],
            'codigo_barras' => $form['nuevo_codigo_barras'],
            'nombre' => $form['nuevo_nombre'],
            'descripcion' => $form['nuevo_descripcion'],
            'lote_serie' => $form['nuevo_lote_serie'],
            'cantidad' => 0,
            'estado' => 'Activo',
            'notas' => '',
        ];

        registroLog('agregarProductoSinRegistro', 'Limpiando campos para producto nuevo', []);

        $this->response->assign('nuevo_codigo_barras', 'value', '');
        $this->response->assign('nuevo_nombre', 'value', '');
        $this->response->assign('nuevo_descripcion', 'value', '');
        $this->response->script('$("#nuevo_unidad").val("").trigger("change.select2");');
        $this->response->assign('nuevo_lote_serie', 'value', '');
        // Refresca la tabla de partidas
        $this->tablaPartidas();

        return $this->response;
    }

    function tablaPartidas()
    {
        registroLog('tablaPartidas', 'Actualizando tabla de partidas', []);

        $script = "tablaPartidas.clear();";   // Limpia la tabla en el DataTable del front‑end
        $i = 1;                          // Contador para la columna “#”
        global $database;

        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {
            if ($partida['estado'] != 'Activo') {
                continue;
            }

            /* ---------- Datos auxiliares ---------- */
            $unidad = $database->get("cfdi_claveunidad", "nombre", ["id" => $partida['idc_claveunidad']]);

            // ¿El documento se puede editar?
            $editable = ($_GET['id'] == 0) ? true : ($database->get("requsiiciones", "estado", ["id" => $_GET['id']]) === 'Borrador');

            /* ---------- Inputs/Botones según editable ---------- */
            if ($editable) {

                $inputNombre = "<input type='text'
                id='nombre{$index}'
                name='nombre{$index}'
                class='form-control'
                value='" . htmlspecialchars($partida['nombre']) . "'
                onchange='JaxoncomprasRequisiciones.actualizaNombrePartida({$index}, this.value)'>";

                $inputDescripcion = "<input type='text'
                id='descripcion{$index}'
                name='descripcion{$index}'
                class='form-control'
                value='" . htmlspecialchars($partida['descripcion']) . "'
                onchange='JaxoncomprasRequisiciones.actualizaDescripcionPartida({$index}, this.value)'>";

                $inputCantidad = "<input type='text'
                id='cantidad{$index}'
                name='cantidad{$index}'
                class='form-control'
                value='" . htmlspecialchars($partida['cantidad']) . "'
                onchange='JaxoncomprasRequisiciones.actualizaCantidadPartida({$index}, this.value)'>";

                $botones = "
                <button type='button' class='btn btn-sm btn-danger' title='Eliminar'
                    onclick='JaxoncomprasRequisiciones.desactivarPartida({$index})'>
                    <i class='bi bi-x-circle'></i>
                </button>
                <button type='button' class='btn btn-sm btn-info' title='Agregar nota'
                    onclick='JaxoncomprasRequisiciones.abrirNotaPartida({$index})'>
                    <i class='bi bi-sticky'></i>
                </button>";
            } else {
                $inputNombre = htmlspecialchars($partida['nombre']);
                $inputDescripcion = htmlspecialchars($partida['descripcion']);
                $inputCantidad = htmlspecialchars($partida['cantidad']);

                $botones = "
                <button type='button' class='btn btn-sm btn-info' title='Agregar nota'
                    onclick='JaxoncomprasRequisiciones.abrirNotaPartida({$index})'>
                    <i class='bi bi-sticky'></i>
                </button>";
            }

            /* ---------- Fila para el DataTable ---------- */
            $fila = [
                $i,
                htmlspecialchars($partida['codigo_barras']),
                $inputNombre,
                $inputDescripcion,
                htmlspecialchars($unidad),
                $inputCantidad,
                $botones
            ];

            // Inserta la fila vía DataTables
            $script .= "tablaPartidas.row.add(" . json_encode($fila) . ");";
            ++$i;
        }

        $script .= "tablaPartidas.draw();";
        $this->response->script($script);
        return $this->response;
    }

    function tablaPartidasSinRepintarTabla()
    {
        registroLog('tablaPartidasSinRepintarTabla', 'Actualizando partidas sin repintar tabla', []);

        global $database;

        // Recorremos todas las partidas guardadas en la sesión
        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {

            /* ---------- 1. Partidas inactivas no se procesan ---------- */
            if ($partida['estado'] != 'Activo') {
                continue;
            }

            // Los inputs usan el atributo VALUE
            $this->response->assign("nombre{$index}", 'value', htmlspecialchars($partida['nombre']));
            $this->response->assign("descripcion{$index}", 'value', htmlspecialchars($partida['descripcion']));
            $this->response->assign("cantidad{$index}", 'value', htmlspecialchars($partida['cantidad']));
        }

        // No se llama a draw(), porque la tabla ya está pintada
        return $this->response;
    }


    function actualizaNombrePartida($indice, $valor)
    {
        registroLog("actualizaNombrePartida', 'Modificando nombre del producto para la partida $indice", ['indice' => $indice, 'Valor' => $valor, 'Partida' => $_SESSION['partidas' . $_GET['rand']][$indice]]);
        // Definir las reglas de validación
        $reglas = [
            "nombre$indice" => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
        ];
        $form["nombre$indice"] = $valor;
        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog("actualizaNombrePartida', 'Error en la validacion global al querer actualizar el nombre del producto para la partida $indice", ['Resultado de la validacion' => $resultadoValidacion]);
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            // Mostrar alerta con el error
            $this->alerta(
                "Invalido",
                $error,
                "error",
                $campo
            );
            // Refresca la tabla de partidas
            $this->tablaPartidasSinRepintarTabla();
            // Retornar la respuesta Jaxon
            return $this->response;
        } else {
            $resultadoValidacionRepetidoNombre = verificaRegistroRepetido("empresa", "productos", "nombre", $valor, $_SESSION['partidas' . $_GET['rand']][$indice]['idproducto']);
            if ($resultadoValidacionRepetidoNombre) {
                registroLog("actualizaNombrePartida', 'Error en la validacion de repetidos al querer actualizar el nombre del producto para la partida $indice", ['Resultado de la validacion' => $resultadoValidacionRepetidoNombre]);
                // Refresca la tabla de partidas
                $this->tablaPartidasSinRepintarTabla();
                // El registro está repetido, mostrar un error
                $this->alerta('Error', 'Ya existe un producto con este nombre', 'error', "nombre{$indice}", true, false);
                return $this->response;
            } else {
                $_SESSION['partidas' . $_GET['rand']][$indice]['nombre'] = $valor;
                registroLog("actualizaNombrePartida', 'Nombre del producto actualizado para la partida $indice", []);
            }
        }
        // Refresca la tabla de partidas
        $this->tablaPartidasSinRepintarTabla();
        return $this->response;
    }

    function actualizaDescripcionPartida($indice, $valor)
    {
        registroLog("actualizaDescripcionPartida', 'Modificando descripcion del producto para la partida $indice", ['indice' => $indice, 'Valor' => $valor, 'Partida' => $_SESSION['partidas' . $_GET['rand']][$indice]]);
        // Definir las reglas de validación
        $reglas = [
            "descripcion$indice" => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 5000],
        ];
        $form["descripcion$indice"] = $valor;
        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog("actualizaDescripcionPartida', 'Error en la validacion global al querer actualizar la descripcion del producto para la partida $indice", ['Resultado de la validacion' => $resultadoValidacion]);
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            // Mostrar alerta con el error
            $this->alerta(
                "Invalido",
                $error,
                "error",
                $campo
            );
            // Refresca la tabla de partidas
            $this->tablaPartidasSinRepintarTabla();
            // Retornar la respuesta Jaxon
            return $this->response;
        } else {
            $resultadoValidacionRepetidoDescripcion = verificaRegistroRepetido("empresa", "productos", "descripcion", $valor, $_SESSION['partidas' . $_GET['rand']][$indice]['idproducto']);
            if ($resultadoValidacionRepetidoDescripcion) {
                registroLog("actualizaDescripcionPartida', 'Error en la validacion de repetidos al querer actualizar la descripcion del producto para la partida $indice", ['Resultado de la validacion' => $resultadoValidacionRepetidoDescripcion]);
                // Refresca la tabla de partidas
                $this->tablaPartidasSinRepintarTabla();
                // El registro está repetido, mostrar un error
                $this->alerta('Error', 'Ya existe un producto con esta descripcion', 'error', "descripcion{$indice}", true, false);
                return $this->response;
            } else {
                $_SESSION['partidas' . $_GET['rand']][$indice]['descripcion'] = $valor;
                registroLog("actualizaDescripcionPartida', 'Descripcion del producto actualizado para la partida $indice", []);
            }
        }
        // Refresca la tabla de partidas
        $this->tablaPartidasSinRepintarTabla();
        return $this->response;
    }

    function actualizaCantidadPartida($indice, $valor)
    {
        registroLog("actualizaCantidadPartida', 'Modificando cantidad del producto para la partida $indice", ['indice' => $indice, 'Valor' => $valor, 'Partida' => $_SESSION['partidas' . $_GET['rand']][$indice]]);
        // Definir las reglas de validación
        $reglas = [
            "cantidad$indice" => ['obligatorio' => true, 'tipo' => 'float', 'pattern' => '/^\d+(\.\d{1,3})?$/'],
        ];
        $form["cantidad$indice"] = $valor;
        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog("actualizaCantidadPartida', 'Error en la validacion global al querer actualizar la cantidad del producto para la partida $indice", ['Resultado de la validacion' => $resultadoValidacion]);
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            // Mostrar alerta con el error
            $this->alerta(
                "Invalido",
                $error,
                "error",
                $campo
            );
            // Refresca la tabla de partidas
            $this->tablaPartidasSinRepintarTabla();
            // Retornar la respuesta Jaxon
            return $this->response;
        } else {
            $_SESSION['partidas' . $_GET['rand']][$indice]['cantidad'] = $valor;
            registroLog("actualizaCantidadPartida', 'Cantidad del producto actualizado para la partida $indice", []);
        }
        // Refresca la tabla de partidas
        $this->tablaPartidasSinRepintarTabla();
        return $this->response;
    }

    function desactivarPartida($indice)
    {
        registroLog("desactivarPartida', 'desactivando partida $indice", ['indice' => $indice, 'Partida' => $_SESSION['partidas' . $_GET['rand']][$indice]]);
        $_SESSION['partidas' . $_GET['rand']][$indice]['estado'] = 'Inactivo';
        registroLog("desactivarPartida', 'Partida eliminada, indice: $indice", []);
        // Refresca la tabla de partidas
        $this->tablaPartidas();
        return $this->response;
    }

    function abrirNotaPartida($indiceDelArreglo)
    {
        registroLog("abrirNotaPartida', 'abriendo nota de la partida $indiceDelArreglo", ['indice' => $indiceDelArreglo, 'Partida' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]]);
        // Definir los campos para el formulario modal
        $campos = [
            [
                'id' => 'indiceDelArreglo',
                'label' => '',
                'type' => 'hidden',
                'value' => $indiceDelArreglo, // Valor por defecto
            ],
            [
                'id' => 'notas',
                'label' => '',
                'type' => 'textarea',
                'value' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['notas'], // Valor por defecto
            ],
        ];

        // Título del modal
        $titulo = 'Notas de la partida';

        // Callback que se ejecutará al guardar
        $funcionCallBack = 'JaxoncomprasRequisiciones.guardarNotaPartida'; // Nombre de la función

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardarNotaPartida($formModal)
    {
        registroLog("guardarNotaPartida', 'modificando notas para la partida {$formModal['indiceDelArreglo']}", ['indice' => $formModal['indiceDelArreglo'], 'Partida' => $_SESSION['partidas' . $_GET['rand']][$formModal['indiceDelArreglo']]]);
        $reglas = [
            "notas" => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 5000],
        ];
        $form["notas"] = $formModal['notas'];
        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog("guardarNotaPartida', 'Error en la validacion global al querer actualizar las notas de la partida {$formModal['indiceDelArreglo']}", ['Resultado de la validacion' => $resultadoValidacion]);
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            // Mostrar alerta con el error
            $this->alerta(
                "Invalido",
                $error,
                "error",
                $campo
            );
            // Refresca la tabla de partidas
            $this->abrirNotaPartida($formModal['indiceDelArreglo']);
            // Retornar la respuesta Jaxon
            return $this->response;
        } else {
            $_SESSION['partidas' . $_GET['rand']][$formModal['indiceDelArreglo']]['notas'] = $formModal['notas'];
            registroLog("guardarNotaPartida', 'Notas de la partida actualizada para la partida {$formModal['indiceDelArreglo']}", []);
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function validar($form, $accion)
    {
        registroLog('validar', 'Validando formulario', ['accion' => $accion]);

        // Definir las reglas de validación

        $reglas = [
            'iddepartamento'           => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'fecha'                    => ['obligatorio' => true, 'tipo' => 'datetime'],
            'fecha_vencimiento'        => ['obligatorio' => true, 'tipo' => 'datetime'],
            'documento'                => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'referencia'               => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'idsocio'                  => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idsubcuenta'              => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'iddireccion_entrega'      => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'detalleDireccion_entrega' => ['detalleDireccion_entrega' => true, 'tipo' => 'string', 'max' => 500],
            'notas'                    => ['obligatorio' => false, 'tipo' => 'string', 'max' => 500],
        ];


        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog('validar', 'Error en la validación del formulario', ['error' => $resultadoValidacion['error'], 'campo' => $resultadoValidacion['campo']]);
            $this->alerta(
                "Error en la validación",
                $resultadoValidacion['error'],
                "error",
                $resultadoValidacion['campo']
            );
            return $this->response;
        } else {
            global $database;

            if (empty($_SESSION['partidas' . $_GET['rand']])) {
                $this->alerta(
                    'Error',
                    'No se encontraron partidas para validar.',
                    'error'
                );
                return $this->response;
            }

            $i = 1;
            foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {
                // Saltar partidas inactivas
                if (isset($partida['estado']) && strtolower($partida['estado']) === 'inactiva') {
                    continue;
                }

                // Campos obligatorios (excepto 'iddb' y 'notas')
                $camposObligatorios = [
                    'idproducto',
                    'idc_claveunidad',
                    'codigo_barras',
                    'nombre',
                    'descripcion',
                    'lote_serie',
                    'cantidad',
                    'estado'
                ];

                foreach ($camposObligatorios as $campo) {
                    if (!isset($partida[$campo]) || empty($partida[$campo]) || $partida[$campo] === null) {
                        registroLog('validar', "Campo vacío: {$campo} en partida {$i}", $partida);
                        $this->alerta(
                            'Campo faltante',
                            "La partida #$i tiene un campo obligatorio vacío: <b>{$campo}</b>",
                            'error'
                        );
                        return $this->response;
                    }
                }

                // Validar que cantidad sea mayor a 0
                if (!is_numeric($partida['cantidad']) || $partida['cantidad'] <= 0) {
                    registroLog('validar', "Cantidad inválida en partida {$i}", $partida);
                    $this->alerta(
                        'Cantidad inválida',
                        "La partida #$i tiene una cantidad inválida.",
                        'error'
                    );
                    return $this->response;
                }
                $i++;
            }

            // Si todo pasó bien
            registroLog('validar', 'Todas las partidas válidas', $_SESSION['partidas' . $_GET['rand']]);
            $this->guardar($form, $accion);

        }
        return $this->response;
    }

    function guardar($form, $accion)
    {
        global $database;

        registroLog('guardar', 'Guardando requisicion', ['accion' => $accion, 'formulario' => $form]);

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            $idDocumento = isset($_GET['id']) ? (int) $_GET['id'] : 0;

            // Determinar el folio solo si es un nuevo registro
            if ($idDocumento == 0) {
                $folio = getConsecutivo('compras_requsiciones');
                registroLog('guardar', 'Nueva requsicion, folio generado', ['folio' => $folio]);
            } else {
                // Recuperar el folio actual desde la base de datos para mantenerlo
                $folio = $database->get('compras_requisiciones', 'folio', ['id' => $idDocumento]);
                registroLog('guardar', 'Requsicion existente, folio recuperado', ['folio' => $folio]);
            }

            // Preparar los datos para insertar o actualizar

            $data = [
                'idempresa'                 => isset($_SESSION['idempresa']) ? (int) $_SESSION['idempresa'] : 0,
                'idsucursal'                => isset($_SESSION['idsucursal']) ? (int) $_SESSION['idsucursal'] : 0,
                'idcreador'                 => isset($_SESSION['idusuario']) ? (int) $_SESSION['idusuario'] : 0,
                'iddepartamento'            => isset($form['iddepartamento']) ? (int) $form['iddepartamento'] : 0,
                'idsocio'                   => isset($form['idsocio']) ? (int) $form['idsocio'] : 0,
                'idsubcuenta'               => isset($form['idsubcuenta']) ? (int) $form['idsubcuenta'] : 0,
                'iddireccion_entrega'       => isset($form['iddireccion_entrega']) ? $form['iddireccion_entrega'] : 0,
                'folio'                     => isset($folio) ? $folio : '', // Aquí usamos el folio ya sea el nuevo o el recuperado
                'detalle_direccion_entrega' => isset($form['detalle_direccion_entrega']) ? $form['detalle_direccion_entrega'] : '',
                'fecha'                     => isset($form['fecha']) ? $form['fecha'] : '0000-00-00 00:00:00',
                'fecha_vencimiento'         => isset($form['fecha_vencimiento']) ? $form['fecha_vencimiento'] : '0000-00-00 00:00:00',
                'fecha_procesado'           => ($accion == 'procesar') ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
                'fecha_cancelacion'         => '0000-00-00 00:00:00',
                'referencia'                => isset($form['referencia']) ? $form['referencia'] : '',
                'documento'                 => isset($form['documento']) ? $form['documento'] : '',
                'estado'                    => ($accion == 'borrador') ? 'Borrador' : 'Activa', //////////////------FALTA VALIDAR SI LA EMPRESA MANEJA AUTORIZACIONES EN ESTE MODULO
                'estado_surtimiento'        => ($accion == 'borrador') ? 'Borrador' : 'Activa',
                'notas'                     => isset($form['notas']) ? $form['notas'] : ''
            ];


            if ($accion == 'borrador') {
                if ($idDocumento == 0) {
                    // Insertar nuevo borrador
                    $database->insert('compras_requisiciones', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Nuevo borrador insertado', ['id' => $insert_id]);
                    $this->alerta(
                        "Éxito",
                        "Requisición guardada como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar borrador existente
                    $database->update('compras_requisiciones', $data, ['id' => $idDocumento]);
                    registroLog('guardar', 'Borrador actualizado', ['id' => $idDocumento]);
                    $this->alerta(
                        "Éxito",
                        "Requsición actualizada como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                }
            } elseif ($accion == 'procesar') {
                if ($idDocumento == 0) {
                    // Insertar nuevo procesado
                    $database->insert('compras_requisiciones', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Documento procesado', ['id' => $insert_id]);
                    $this->alerta(
                        "Éxito",
                        "Requsición procesada correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar procesado existente
                    $database->update('compras_requisiciones', $data, ['id' => $idDocumento]);
                    registroLog('guardar', 'Documento actualizado y procesado', ['id' => $idDocumento]);
                    $this->alerta(
                        "Éxito",
                        "Requisición actualizada y procesada correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                }
            }

            // Confirmar la transacción
            $database->pdo->commit();
            registroLog('guardar', 'Transacción confirmada', ['id' => $idDocumento]);
        } catch (PDOException $e) {
            // Revertir la transacción en caso de error
            $database->pdo->rollBack();
            registroLog('guardar', 'Error en la transacción', ['error' => $e->getMessage()]);
            $this->alerta(
                "Error",
                "No se pudo completar la operación, por favor contacte al administrador.",
                "error"
            );
        }
        return $this->response;
    }

    function imprimir()
    {

        return $this->response;
    }

}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, comprasRequisiciones::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












