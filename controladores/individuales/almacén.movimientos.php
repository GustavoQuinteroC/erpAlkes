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
        $rand = $_GET['rand']; // Obtener el valor dinámico
        registroLog('inializarFormulario', 'Inicializando formulario', []);
        // Obtén la ruta actual dividida en segmentos
        $ruta = explode(DIRECTORY_SEPARATOR, getcwd());

        //calcular nombres de modulos semidinamicamente
        $modulo = $ruta[(count($ruta) - 2)];
        $submodulo = $ruta[(count($ruta) - 1)];
        $subsubmodulo = null;
        if(validaPermisoEditarModulo($modulo, $submodulo, $subsubmodulo))
        {
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-primary btn-sm' type='button' id='btnborrador' name='btnborrador' onclick='JaxonalmacenMovimientos.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"borrador\");'>
                    <i class='bi bi-floppy'></i> Borrador
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-success btn-sm' type='button' id='btnprocesar' name='btnprocesar' onclick='JaxonalmacenMovimientos.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"procesar\");'>
                    <i class='bi bi-check2-circle'></i> Procesar
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-danger btn-sm' type='button' id='btncancelar' name='btncancelar' onclick='JaxonalmacenMovimientos.modalConfirmacionCancelar();'>
                    <i class='bi bi-x-circle'></i> Cancelar
                </button>
            ");
        }
        
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-secondary btn-sm' type='button' id='btnimprimir' name='btnimprimir' onclick='JaxonalmacenMovimientos.imprimir();'>
                <i class='bi bi-printer'></i> Imprimir
            </button>
        ");
        if($_GET['id']==0)
        {
            registroLog('inializarFormulario', 'Iniciando un registro nuevo', []);
            $this->response->assign('fechahora', 'value', date('Y-m-d\TH:i'));
            $this->response->assign('btncancelar', 'disabled', 'disabled');
            $this->response->assign('btnimprimir', 'disabled', 'disabled');
        }
        else
        {
            registroLog('inializarFormulario', 'Consultando un movimiento ya registrado', []);
            if (!validarEmpresaPorRegistro("almacenes_movimientos", $_GET['id'])) {
                registroLog('inializarFormulario', 'Registro no pertenece a la empresa', []);
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            } else {
                //llenar todo lo relacionado a consulta
                global $database;
                $movimiento = $database->get("almacenes_movimientos", "*", ["id" => $_GET['id']]);
                $this->response->assign('folio', 'value', $movimiento['folio']);
                $this->response->assign('idalmacen', 'value', $movimiento['idalmacen']);
                $this->response->assign('fechahora', 'value', $movimiento['fecha']);
                $this->response->assign('documento', 'value', $movimiento['documento']);
                $this->response->assign('referencia', 'value', $movimiento['referencia']);
                $this->response->assign('iddireccion_origen', 'value', $movimiento['iddireccion_origen']);
                $this->response->assign('iddireccion_destino', 'value', $movimiento['iddireccion_destino']);
                $this->response->assign('notas', 'value', $movimiento['notas']);

                $this->response->script('
                    // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
                    document.getElementById("idsocio").onchange = null;
                    document.getElementById("idsubcuenta").onchange = null;
                    document.getElementById("idconcepto").onchange = null;

                    // Asignar valores a los campos select2 sin disparar onchange
                    $("#idsocio").val("' . $movimiento['idsocio'] . '").trigger("change.select2");
                    $("#idsubcuenta").val("' . $movimiento['idsubcuenta'] . '").trigger("change.select2");
                    $("#idconcepto").val("' . $movimiento['idconcepto'] . '").trigger("change.select2");

                    // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
                    document.getElementById("idsocio").onchange = function() {
                        JaxonalmacenMovimientos.cargarSocio(this.value);
                    };
                    document.getElementById("idsubcuenta").onchange = function() {
                        JaxonalmacenMovimientos.cargarSubcuenta({ seleccion: this.value }, document.getElementById(\'idsocio\').value);
                    };
                    document.getElementById("idconcepto").onchange = function() {
                        JaxonalmacenMovimientos.deshabilitaConcepto();
                    };
                ');
                $this->response->assign("smallTitulos", "innerHTML", $movimiento['folio']);
                $this->cambiarDireccion($movimiento['iddireccion_origen'], 'origen');
                $this->cambiarDireccion($movimiento['iddireccion_destino'], 'destino');
                $this->cargarPartidasConsulta($_GET['id']);
                $this->deshabilitaAlmacen();
                $this->deshabilitaConcepto();
                if($movimiento['estado']!='Borrador')
                {
                    $this->response->assign('botonBuscarSocio', 'disabled', 'disabled');
                    $this->response->assign('botonBuscarSubcuenta', 'disabled', 'disabled');
                    $this->response->assign('cambiarConcepto', 'disabled', 'disabled');
                    $this->response->assign('cambiarAlmacen', 'disabled', 'disabled');
                    $this->response->assign('addPartidas', 'disabled', 'disabled');
                    $this->response->assign('btnborrador', 'disabled', 'disabled');
                    $this->response->assign('btnprocesar', 'disabled', 'disabled');
                    
                    $this->response->assign('fechahora', 'readOnly', 'readOnly');
                    $this->response->assign('documento', 'readOnly', 'readOnly');
                    $this->response->assign('referencia', 'readOnly', 'readOnly');
                    $this->response->assign('notas', 'readOnly', 'readOnly');

                    $this->deshabilitaSelect('idsocio');
                    $this->deshabilitaSelect('idsubcuenta');
                    $this->deshabilitaSelect('iddireccion_origen');
                    $this->deshabilitaSelect('iddireccion_destino');
                }
                if($movimiento['estado']=='Cancelado')
                {
                    $this->response->assign('btncancelar', 'disabled', 'disabled');
                }
            }
        }
        return $this->response;
    }

    function cargarPartidasConsulta($idmovimiento)
    {
        global $database;
        registroLog('cargarPartidasConsulta', 'Entrando a la función', ['idmovimiento' => $idmovimiento]);

        // Verificar si el movimiento existe
        $movimiento = $database->get("almacenes_movimientos", "*", ["id" => $idmovimiento]);
        if (!$movimiento) {
            registroLog('cargarPartidasConsulta', 'Movimiento no encontrado', ['idmovimiento' => $idmovimiento]);
            $this->alerta("Error", "El movimiento no existe.", "error");
            return $this->response;
        }
        registroLog('cargarPartidasConsulta', 'Movimiento encontrado', ['idmovimiento' => $idmovimiento, 'folio' => $movimiento['folio']]);

        // Obtener la naturaleza del movimiento (Entrada o Salida)
        $naturalezaMovimiento = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $movimiento['idconcepto']]);
        registroLog('cargarPartidasConsulta', 'Naturaleza del movimiento obtenida', ['naturaleza' => $naturalezaMovimiento]);

        // Obtener las partidas asociadas al movimiento
        $partidas = $database->select("almacenes_movimientos_productos", "*", ["idmovimiento" => $idmovimiento]);
        registroLog('cargarPartidasConsulta', 'Partidas obtenidas', ['num_partidas' => count($partidas)]);

        foreach ($partidas as $partida) {
            registroLog('cargarPartidasConsulta', 'Procesando partida', ['idpartida' => $partida['id'], 'idproducto' => $partida['idproducto']]);

            // Crear la estructura de la partida
            $partidaSesion = [
                'iddb'                  => $partida['id'],
                'idalmacenes_productos' => $partida['idalmacenes_productos'], // Cambiado de idalmacenes_productos a idproducto
                'cantidad'              => $partida['cantidad'],
                'estado'                => $partida['estado'],
                'notas'                 => $partida['notas'],
                'lotes'                 => []
            ];

            // Obtener los lotes asociados a la partida
            $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", ["idmovimiento_producto" => $partida['id']]);
            registroLog('cargarPartidasConsulta', 'Lotes obtenidos para la partida', ['idpartida' => $partida['id'], 'num_lotes' => count($lotes)]);

            foreach ($lotes as $lote) {
                registroLog('cargarPartidasConsulta', 'Procesando lote', ['idlote' => $lote['id'], 'idpartida' => $partida['id']]);

                if ($naturalezaMovimiento == 'Entrada') {
                    $partidaSesion['lotes'][] = [
                        'iddb'        => $lote['id'],
                        'lote'        => $lote['lote'],
                        'serie'       => $lote['serie'],
                        'pedimento'   => $lote['pedimento'],
                        'fabricacion' => $lote['fabricacion'],
                        'caducidad'   => $lote['caducidad'],
                        'cantidad'    => $lote['cantidad'],
                        'estado'      => $lote['estado']
                    ];
                    registroLog('cargarPartidasConsulta', 'Lote agregado (Entrada)', ['idlote' => $lote['id'], 'lote' => $lote['lote'], 'serie' => $lote['serie']]);
                } elseif ($naturalezaMovimiento == 'Salida') {
                    $partidaSesion['lotes'][] = [
                        'iddb'    => $lote['id'],
                        'iddbapl' => $lote['idalmacen_producto_lote'],
                        'cantidad' => $lote['cantidad'],
                        'estado'  => $lote['estado']
                    ];
                    registroLog('cargarPartidasConsulta', 'Lote agregado (Salida)', ['idlote' => $lote['id'], 'iddbapl' => $lote['idalmacen_producto_lote']]);
                }
            }

            // Agregar la partida a la sesión
            $_SESSION['partidas' . $_GET['rand']][] = $partidaSesion;
            registroLog('cargarPartidasConsulta', 'Partida agregada a la sesión', ['idpartida' => $partida['id'], 'num_lotes' => count($partidaSesion['lotes'])]);
        }

        // Registrar el éxito en el log
        registroLog('cargarPartidasConsulta', 'Arreglo de consulta generado exitosamente', ['idmovimiento' => $idmovimiento]);
        // Muestra la tabla de partidas actualizada
        registroLog('cargarPartidasConsulta', 'Llamando a la funcion tablaPartidas', ['idmovimiento' => $idmovimiento]);
        $this->tablaPartidas();
        return $this->response;
    }

    function modalSeleccionarSocio()
    {
        registroLog('modalSeleccionarSocio', 'Abriendo modal para seleccionar socio', []);
        $this->modalSeleccionServerSide('global', 'socios', 'ambos', 0, 'Activos', 'Modal', 'JaxonalmacenMovimientos.cargarSocio', false, '', 'Selecciona Un Socio');
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
                JaxonalmacenMovimientos.cargarSocio(this.value);
            };
        ');
        $this->response->assign("iddireccion_origen", "value", "");
        $this->response->assign("iddireccion_destino", "value", "");
        $this->response->assign("detalleDireccion_origen", "value", "");
        $this->response->assign("detalleDireccion_destino", "value", "");
        $this->response->assign("idsubcuenta", "innerHTML", getSubcuentas($form['seleccion']));
        $this->response->assign("iddireccion_origen", "innerHTML", getDirecciones($form['seleccion']));
        $this->response->assign("iddireccion_destino", "innerHTML", getDirecciones($form['seleccion']));
        return $this->response;
    }

    function modalSeleccionarSubcuenta($idsocio)
    {
        global $database;

        registroLog('modalSeleccionarSubcuenta', 'Abriendo modal para seleccionar subcuenta', ['idsocio' => $idsocio]);

        // Validar que $idsocio sea un valor válido
        if (empty($idsocio) || $idsocio == 0) {
            registroLog('modalSeleccionarSubcuenta', 'Socio no válido', ['idsocio' => $idsocio]);
            $this->alerta("¡ERROR!", "Antes debe seleccionar un socio válido.", "error");
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
            $this->alerta("¡SIN SUBCUENTAS!", "Este socio no tiene subcuentas registradas.", "warning");
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
            "JaxonalmacenMovimientos.cargarSubcuenta",
            true,
            false,
            ", $idsocio"
        );

        return $this->response;
    }

    function cargarSubcuenta($form, $idsocioPrincipal)
    {
        registroLog('cargarSubcuenta', 'Cargando subcuenta seleccionada', ['subcuentaSeleccionada' => $form['seleccion'],'idsocioPrincipal' => $idsocioPrincipal]);
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idsubcuenta").val("' . $form['seleccion'] . '").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = function() {
                JaxonalmacenMovimientos.cargarSubcuenta(this.value);
            };
        ');
        $this->response->assign("iddireccion_origen", "value", "");
        $this->response->assign("iddireccion_destino", "value", "");
        $this->response->assign("detalleDireccion_origen", "value", "");
        $this->response->assign("detalleDireccion_destino", "value", "");
        $this->response->assign("iddireccion_origen", "innerHTML", getDirecciones($idsocioPrincipal, $form['seleccion']));
        $this->response->assign("iddireccion_destino", "innerHTML", getDirecciones($idsocioPrincipal, $form['seleccion']));
        return $this->response;
    }

    function cambiarDireccion($idDireccion, $tipoDireccion)
    {
        global $database;
        $direccion = $database->get("direcciones", "*", ["id" => $idDireccion]);
        $colonia = $database->get("cfdi_colonia", "*", ["id" => $direccion['idc_colonia']]);
        $municipio = $database->get("cfdi_municipio", "*", ["id" => $direccion['idc_municipio']]);
        $estado = $database->get("cfdi_estado", "*", ["id" => $direccion['idc_estado']]);

        registroLog('cambiarDireccion', 'Cambiando dirección', ['idDireccion' => $idDireccion,'tipoDireccion' => $tipoDireccion]);

        $html=$direccion['calle']
                ." ".$direccion['no_exterior']
                ." ".$direccion['no_interior']
                .", ".$colonia['nombre']
                .", ".$direccion['cp']
                .", ".$municipio['descripcion']
                .", ".$estado['nombre_estado'];
        $this->response->assign("detalleDireccion_$tipoDireccion", "value", $html);
        return $this->response;
    }

    function deshabilitaAlmacen()
    {
        registroLog('deshabilitaAlmacen', 'Deshabilitando almacén', []);
        $this->deshabilitaSelect('idalmacen');
        $this->response->assign("cambiarAlmacen", "disabled", "");
        return $this->response;
    }

    function deshabilitaConcepto()
    {
        registroLog('deshabilitaConcepto', 'Deshabilitando concepto', []);
        $this->response->assign("cambiarConcepto", "disabled", "");
        $this->deshabilitaSelect('idconcepto');
        return $this->response;
    }

    function modalConfirmacionHabilitaAlmacen()
    {
        registroLog('modalConfirmacionHabilitaAlmacen', 'Mostrando modal de confirmación para habilitar almacén', []);
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de almacén se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonalmacenMovimientos.habilitaAlmacen();");
        return $this->response;
    }

    function modalConfirmacionHabilitaConcepto()
    {
        registroLog('modalConfirmacionHabilitaConcepto', 'Mostrando modal de confirmación para habilitar concepto', []);
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de concepto se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonalmacenMovimientos.habilitaConcepto();");
        return $this->response;
    }

    function modalConfirmacionCancelar()
    {
        registroLog('modalConfirmacionCancelar', 'Mostrando modal de confirmación para cancelar el movimiento', ["getId" => $_GET['id']]);
        $this->alertaConfirmacion(
            "¿CANCELAR?", 
            "Se cancelará el movimiento y, en caso de estar activo, se creará un movimiento de cancelación. ¿Desea continuar?", 
            "warning", 
            "JaxonalmacenMovimientos.cancelarMovimiento(jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"));"
        );
        return $this->response;
    }

    function habilitaAlmacen()
    {
        registroLog('habilitaAlmacen', 'Habilitando almacén', []);
        $this->habilitaSelect('idalmacen');
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idalmacen").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idalmacen").val("").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idalmacen").onchange = function() {
                JaxonalmacenMovimientos.deshabilitaAlmacen();
            };
        ');
        $this->response->assign("cambiarAlmacen", "disabled", "disabled");
        $this->desactivarTodasLasPartidas();
        return $this->response;
    }

    function habilitaConcepto()
    {
        registroLog('habilitaConcepto', 'Habilitando concepto', []);
        $this->habilitaSelect('idconcepto');
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idconcepto").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idconcepto").val("").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idconcepto").onchange = function() {
                JaxonalmacenMovimientos.deshabilitaConcepto();
            };
        ');
        $this->response->assign("cambiarConcepto", "disabled", "disabled");
        $this->desactivarTodasLasPartidas();
        return $this->response;
    }

    function desactivarTodasLasPartidas()
    {
        registroLog('desactivarTodasLasPartidas', 'Desactivando todas las partidas', []);

        // Verifica si existe la sesión de partidas
        if (isset($_SESSION['partidas' . $_GET['rand']])) {
            // Recorre todas las partidas y cambia su estado a 'Inactivo'
            foreach ($_SESSION['partidas' . $_GET['rand']] as &$partida) {
                $partida['estado'] = 'Inactivo';
            }
        }

        // Actualiza la tabla para reflejar los cambios
        $this->tablaPartidas();

        return $this->response;
    }

    function modalSeleccionarProductos($form)
    {
        registroLog('modalSeleccionarProductos', 'Abriendo modal para seleccionar productos', ['idconcepto' => $form['idconcepto'],'idalmacen' => $form['idalmacen']]);

        if(empty($form['idconcepto']))
        {
            registroLog('modalSeleccionarProductos', 'Concepto no seleccionado', []);
            $this->alerta("Invalido", "Primero elije un concepto", "error", "idconcepto");
        }
        elseif(empty($form['idalmacen']))
        {
            registroLog('modalSeleccionarProductos', 'Almacén no seleccionado', []);
            $this->alerta("Invalido", "Primero elije un almacén", "error", "idalmacen");
        }
        else
        {
            global $database;
            $naturaleza = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            if($naturaleza=='Entrada')
            {
                $this->modalSeleccionServerSide('almacén', 'inventario', '', $form['idalmacen'], 'Principal', 'Modal', 'JaxonalmacenMovimientos.addProductos', true, '', 'Seleccionar Productos');
            }
            elseif($naturaleza=='Salida')
            {
                $filtro= getParametro('inventario_negativo')?'Principal':'Con Existencia';
                $this->modalSeleccionServerSide('almacén', 'inventario', '', $form['idalmacen'], $filtro, 'Modal', 'JaxonalmacenMovimientos.addProductos', true, '', 'Seleccionar Productos');
            }
        }
        return $this->response;
    }

    function addProductos($form)
    {
        registroLog('addProductos', 'Agregando productos seleccionados', ['productosSeleccionados' => $form['seleccion']]);

        $productosDuplicados = []; // Almacena los nombres de los productos duplicados
        $productosAgregados = false;

        // Verifica si el array 'seleccion' existe y no está vacío
        if (isset($form['seleccion']) && is_array($form['seleccion'])) {
            foreach ($form['seleccion'] as $idalmacenes_productos) {
                // Verifica si el producto ya existe en las partidas con estado "Activo"
                $existe = false;
                foreach ($_SESSION['partidas' . $_GET['rand']] as $partida) {
                    if ($partida['idalmacenes_productos'] == $idalmacenes_productos && $partida['estado'] === 'Activo') {
                        $existe = true;
                        break;
                    }
                }

                if ($existe) {
                    global $database;
                    // Obtener el nombre del producto para el mensaje de duplicado
                    $almacenes_producto = $database->get("almacenes_productos", "idproducto", ["id" => $idalmacenes_productos]);
                    $producto = $database->get("productos", "nombre", ["id" => $almacenes_producto]);
                    $productosDuplicados[] = $producto ?: "Producto desconocido"; // Evitar nombres vacíos
                } else {
                    // Agrega el nuevo producto si no es duplicado
                    $_SESSION['partidas' . $_GET['rand']][] = [
                        'iddb' => 0,
                        'idalmacenes_productos' => $idalmacenes_productos,
                        'cantidad' => 0,
                        'estado' => 'Activo',
                        'notas' => '',
                        'lotes' => []
                    ];
                    $productosAgregados = true;
                }
            }
        }

        // Muestra la tabla de partidas actualizada
        $this->tablaPartidas();

        // Generar alertas según el resultado de las validaciones
        if (!empty($productosDuplicados)) {
            registroLog('addProductos', 'Productos duplicados encontrados', ['productosDuplicados' => $productosDuplicados]);
            $mensaje = "Algunos productos ya existían en las partidas y no se agregaron:<br>" . implode("<br>", $productosDuplicados);
            $this->alerta("Productos duplicados", $mensaje, "warning");
        } elseif ($productosAgregados) {
            registroLog('addProductos', 'Productos agregados exitosamente', []);
        } else {
            registroLog('addProductos', 'No se agregó ningún producto', []);
            $this->alerta("Sin cambios", "No se agregó ningún producto.", "info");
        }

        return $this->response;
    }

    function tablaPartidas()
    {
        registroLog('tablaPartidas', 'Actualizando tabla de partidas', []);

        $script = "tablaPartidas.clear();"; // Limpiar la tabla
        $i = 1;

        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {
            if ($partida['estado'] == 'Activo') {
                global $database;

                // Obtener datos necesarios de la base de datos
                $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $partida['idalmacenes_productos']]);
                $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
                $cfdi_claveunidad = $database->get("cfdi_claveunidad", "*", ["id" => $producto['idc_claveunidad']]);
                $editable = ($_GET['id'] == 0) ? true : ($database->get("almacenes_movimientos", "estado", ["id" => $_GET['id']]) == 'Borrador');

                // Determinar contenido del input y botones según editable
                if ($editable) {
                    $inputCantidad = "<input type='number' 
                        id='cantidad[".$index."]'
                        name='cantidad[".$index."]'
                        class='form-control cantidad-input' 
                        value='" . htmlspecialchars($partida['cantidad']) . "' 
                        data-indice='".$index."'
                        min='0' 
                        onfocus='JaxonalmacenMovimientos.validaSiTieneLote($index, jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"))' 
                        onchange='JaxonalmacenMovimientos.validaCantidadPartida($index, jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"), this.value)'>";
                    
                    $botones = "
                        <button type='button' class='btn btn-sm btn-danger' title='Eliminar' 
                            onclick='JaxonalmacenMovimientos.desactivarPartida($index)'>
                            <i class='bi bi-x-circle'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-info' title='Agregar nota' 
                            onclick='JaxonalmacenMovimientos.abrirNotaPartida($index)'>
                            <i class='bi bi-sticky'></i>
                        </button>";
                } else {
                    $inputCantidad = htmlspecialchars($partida['cantidad']);
                    
                    $botones = "
                        <button type='button' class='btn btn-sm btn-info' title='Agregar nota' 
                            onclick='JaxonalmacenMovimientos.abrirNotaPartida($index)'>
                            <i class='bi bi-sticky'></i>
                        </button>";
                }

                // Construcción de la fila de la tabla
                $fila = [
                    $i,
                    htmlspecialchars($producto['codigo_barras']),
                    htmlspecialchars($producto['nombre']),
                    htmlspecialchars($producto['descripcion']),
                    htmlspecialchars($cfdi_claveunidad['nombre']),
                    htmlspecialchars($almacenes_producto['existencia']),
                    $inputCantidad,
                    $botones
                ];

                // Convertir la fila a formato JavaScript
                $filaJS = json_encode($fila);
                $script .= "tablaPartidas.row.add($filaJS);";
                $i++;
            }
        }

        $script .= "tablaPartidas.draw();";
        $this->response->script($script);

        return $this->response;
    }

    function abrirNotaPartida($indiceDelArreglo)
    {
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
        $funcionCallBack = 'JaxonalmacenMovimientos.guardarNotaPartida'; // Nombre de la función JavaScript

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardarNotaPartida($form)
    {
        $_SESSION['partidas' . $_GET['rand']][$form['indiceDelArreglo']]['notas']=$form['notas'];
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function validaCantidadPartida($indiceDelArreglo, $form, $cantidadIntentada)
    {
        registroLog('validaCantidadPartida', 'Validando cantidad de partida', [
            'indice' => $indiceDelArreglo,
            'cantidadIntentada' => $cantidadIntentada
        ]);

        // Referencia directa a la partida en sesión
        $partida = &$_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo];

        if (preg_match('/^\d{1,12}(\.\d{1,4})?$/', $cantidadIntentada)) {
            // La cantidad es válida
            global $database;
            $naturalezaConcepto = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            
            if ($naturalezaConcepto == 'Salida') {
                $existenciaRealActual = $database->get("almacenes_productos", "existencia", ["id" => $partida['idalmacenes_productos']]);
                
                // Validar que no exceda la existencia del almacén seleccionado
                if ($cantidadIntentada > $existenciaRealActual and !getParametro('inventario_negativo')) {
                    registroLog('validaCantidadPartida', 'Cantidad excede existencia en almacén', [
                        'idalmacenes_productos' => $partida['idalmacenes_productos'],
                        'cantidadIntentada' => $cantidadIntentada,
                        'existenciaRealActual' => $existenciaRealActual
                    ]);
                    
                    $this->resetCantidadPartidaYLotes($indiceDelArreglo);
                    $mensajeLotes = getParametro('lotes') ? ", incluyendo sus lotes o series (en caso de tenerlos)" : "";
                    
                    $this->alerta(
                        "Cantidad inválida",
                        "La cantidad ingresada excede la existencia actual en el almacén seleccionado. Todas las cantidades de esta partida$mensajeLotes, han sido reiniciadas a 0.",
                        "error"
                    );
                    
                    $this->generarTablaLotes($indiceDelArreglo);
                    $this->generarTablaLotesConsulta($indiceDelArreglo);
                    
                    // Actualizar solo el input específico usando el índice
                    $this->response->script("
                        $('input[data-indice=\"".$indiceDelArreglo."\"]').val('0');
                    ");
                    
                    return $this->response;
                }
            }
            
            // Actualizar la cantidad en sesión
            $partida['cantidad'] = $cantidadIntentada;
            
            // Actualizar solo el input específico usando el índice
            $this->response->script("
                $('input[data-indice=\"".$indiceDelArreglo."\"]').val('".$cantidadIntentada."');
            ");
        } else {
            // La cantidad es inválida (no cumple con el formato de 12 dígitos enteros y 4 decimales)
            registroLog('validaCantidadPartida', 'Formato de cantidad inválido', [
                'idalmacenes_productos' => $partida['idalmacenes_productos'],
                'cantidadIntentada' => $cantidadIntentada
            ]);
            
            $this->resetCantidadPartidaYLotes($indiceDelArreglo);
            $mensajeLotes = getParametro('lotes') ? ", incluyendo sus lotes o series (en caso de tenerlos)" : "";
            
            $this->alerta(
                "Formato de cantidad inválido",
                "La cantidad ingresada no es válida. Solo se permiten hasta 12 dígitos enteros y 4 decimales. Todas las cantidades de esta partida$mensajeLotes, han sido reiniciadas a 0.",
                "error"
            );
            
            // Actualizar solo el input específico usando el índice
            $this->response->script("
                $('input[data-indice=\"".$indiceDelArreglo."\"]').val('0');
            ");
        }
        
        return $this->response;
    }

    function resetCantidadPartidaYLotes($indiceDelArreglo)
    {
        registroLog('resetCantidadPartidaYLotes', 'Reiniciando cantidad de partida y lotes', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Poner la cantidad de la partida en 0
        $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['cantidad'] = 0;
        
        // Poner las cantidades de todos los lotes o series en 0
        if (isset($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes']) && is_array($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'])) {
            foreach ($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] as &$lote) {
                $lote['cantidad'] = 0;
            }
        }
    }

    function desactivarPartida($indice)
    {
        registroLog('desactivarPartida', 'Desactivando partida', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indice]['idalmacenes_productos']]);

        // Verifica si existe la sesión de partidas y si el índice es válido
        if (isset($_SESSION['partidas' . $_GET['rand']][$indice])) {
            // Cambia el estado de la partida específica a 'Inactivo'
            $_SESSION['partidas' . $_GET['rand']][$indice]['estado'] = 'Inactivo';
        }

        // Actualiza la tabla para reflejar los cambios
        $this->tablaPartidas();

        return $this->response;
    }

    function validaSiTieneLote($indiceDelArreglo, $form)
    {
        global $database;
        $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);
        $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
        if($producto['lote_serie']=='Sí')
        {
            registroLog('validaSiTieneLote', 'Producto requiere selección de lotes', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos'],'idProducto' => $producto['id']]);

            $naturalezaConcepto = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            if($naturalezaConcepto=='Salida')
            {
                $this->modalSeleccionLotesConsulta($indiceDelArreglo);
            }
            elseif($naturalezaConcepto=='Entrada')
            {
                $this->modalSeleccionLotes($indiceDelArreglo);
            }
        }
        return $this->response;
    }

    function modalSeleccionLotesConsulta($indiceDelArreglo)
    {
        registroLog('modalSeleccionLotesConsulta', 'Abriendo modal para selección de lotes (consulta)', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Crear el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalSeleccionLotesConsulta" tabindex="-1" aria-labelledby="modalSeleccionLotesConsultaLabel">';
        $html .= '<div class="modal-dialog modal-xl" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . ' d-flex justify-content-between align-items-center">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLotesConsultaLabel">Selección de Lotes (Salida)</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-borderless" id="tablaLotesConsulta">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>No. de Lote</th>';
        $html .= '<th>No. de Serie</th>';
        $html .= '<th>No. de Pedimento</th>';
        $html .= '<th>Fecha de Fabricación</th>';
        $html .= '<th>Fecha de Caducidad</th>';
        $html .= '<th>Existencia</th>';
        $html .= '<th>Cantidad a Salir</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody id="tablaLotesConsultaBody">';
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Remover cualquier modal previo
        $this->response->remove('modalSeleccionLotesConsulta');

        // Insertar el nuevo modal
        $this->response->append("modales", "innerHTML", $html);

        // Generación del contenido de la tabla de lotes
        $this->generarTablaLotesConsulta($indiceDelArreglo);

        // Mostrar el modal con Bootstrap 5
        $this->response->script('new bootstrap.Modal(document.getElementById("modalSeleccionLotesConsulta")).show();');

        // Eliminar el elemento si es que se cierra y ejecutar la función validarLotesConsultaAlCerrarModal
        $this->response->script('
            if (!$("#modalSeleccionLotesConsulta").data("evento-registrado")) {
                $("#modalSeleccionLotesConsulta").on("hidden.bs.modal", function () {
                    $(this).remove();
                });
                $("#modalSeleccionLotesConsulta").data("evento-registrado", true);
            }
        ');
        return $this->response;
    }

    function modalSeleccionLotes($indiceDelArreglo)
    {
        registroLog('modalSeleccionLotes', 'Abriendo modal para selección de lotes', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Crear el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalSeleccionLotes" tabindex="-1" aria-labelledby="modalSeleccionLotesLabel">';
        $html .= '<div class="modal-dialog modal-xl" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . ' d-flex justify-content-between align-items-center">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLotesLabel">Selección de Lotes</h5>';
        $html .= '<div class="d-flex align-items-center gap-2">';
        $html .= '<button tabindex="400" id="addLote" name="addLote" class="btn btn-sm border ' . getTextColor() . ' bg-transparent" onclick="JaxonalmacenMovimientos.addFilaLote(' . $indiceDelArreglo . ');" type="button">';
        $html .= '<span class="bi bi-plus-lg me-1"></span> Agregar';
        $html .= '</button>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-borderless" id="tablaLotes">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>No. de Lote</th>';
        $html .= '<th>No. de Serie</th>';
        $html .= '<th>No. de Pedimento</th>';
        $html .= '<th>Fecha de Fabricación</th>';
        $html .= '<th>Fecha de Caducidad</th>';
        $html .= '<th>Cantidad</th>';
        $html .= '<th>Acciones</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody id="tablaLotesBody">';
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Remover cualquier modal previo
        $this->response->remove('modalSeleccionLotes');

        // Insertar el nuevo modal
        $this->response->append("modales", "innerHTML", $html);

        // Generación del contenido de la tabla de lotes
        $this->generarTablaLotes($indiceDelArreglo);

        // Mostrar el modal con Bootstrap 5
        $this->response->script('new bootstrap.Modal(document.getElementById("modalSeleccionLotes")).show();');

        // Eliminar el elemento si es que se cierra y ejecutar la función validarLotesAlCerrarModal
        $this->response->script('
            if (!$("#modalSeleccionLotes").data("evento-registrado")) {
                $("#modalSeleccionLotes").on("hidden.bs.modal", function () {
                    JaxonalmacenMovimientos.validarLotesAlCerrarModal(' . $indiceDelArreglo . ');
                    $(this).remove();
                });
                $("#modalSeleccionLotes").data("evento-registrado", true);
            }
        ');

        return $this->response;
    }

    function generarTablaLotesConsulta($indiceDelArreglo)
    {
        global $database;

        registroLog('generarTablaLotesConsulta', 'Generando tabla de lotes (consulta)', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Obtener el producto y almacén de la partida actual
        $almacenProducto = $database->get("almacenes_productos", "*", ["id" => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Construir la consulta para obtener los lotes existentes
        $criteriosConsulta = [
            "idproducto" => $almacenProducto['idproducto'],
            "idalmacen"  => $almacenProducto['idalmacen']
        ];
        // Determinar si se permite inventario negativo
        if (!getParametro('inventario_negativo')) {
            $criteriosConsulta["existencia[>]"] = 0; // Solo lotes con existencia mayor a 0 si no se permite inventario negativo
        }

        // Obtener los lotes existentes para el producto y almacén
        $lotesExistentes = $database->select("almacenes_productos_lotes", "*", $criteriosConsulta);

        $html = '';
        foreach ($lotesExistentes as $lote) {
            // Buscar la cantidad en el array de la sesión
            $cantidadLote = 0;
            foreach ($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] as $loteSesion) {
                if ($loteSesion['iddbapl'] == $lote['id']) {
                    $cantidadLote = $loteSesion['cantidad']; // Usar la cantidad almacenada en el array de la sesión
                    break;
                }
            }

            // Generar la fila de la tabla con la cantidad prellenada
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($lote['lote']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lote['serie']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lote['pedimento']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lote['fabricacion']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lote['caducidad']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lote['existencia']) . '</td>';
            $html .= '<td><input type="number" class="form-control cantidad-salida" min="0" value="' . htmlspecialchars($cantidadLote) . '" onchange="JaxonalmacenMovimientos.guardaLoteConsulta(' . $indiceDelArreglo . ', this.value, ' . $lote['id'] . ')"></td>';
            $html .= '</tr>';
        }

        $this->response->assign("tablaLotesConsultaBody", "innerHTML", $html);
        return $this->response;
    }

    function generarTablaLotes($indiceDelArreglo)
    {
        registroLog('generarTablaLotes', 'Generando tabla de lotes', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Obtener los lotes activos de la sesión
        $lotes = $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] ?? [];
        $lotesActivos = array_filter($lotes, function ($lote) {
            return $lote['estado'] == 'Activo';
        });

        $html = '';
        foreach ($lotesActivos as $index => $lote) {
            $html .= '<tr>';
            $html .= '<td><input type="text" class="form-control" id="lote_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['lote'] ?? '') . '" onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'lote\')"></td>';
            $html .= '<td><input type="text" class="form-control" id="serie_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['serie'] ?? '') . '" onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'serie\')"></td>';
            $html .= '<td><input type="text" class="form-control" id="pedimento_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['pedimento'] ?? '') . '" onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'pedimento\')"></td>';
            $html .= '<td><input type="date" class="form-control" id="fabricacion_' . $indiceDelArreglo . '_' . $index . '" value="' . (!empty($lote['fabricacion']) ? date('Y-m-d', strtotime($lote['fabricacion'])) : '') . '" onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'fabricacion\')"></td>';
            $html .= '<td><input type="date" class="form-control" id="caducidad_' . $indiceDelArreglo . '_' . $index . '" value="' . (!empty($lote['caducidad']) ? date('Y-m-d', strtotime($lote['caducidad'])) : '') . '"onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'caducidad\')"></td>';
            $html .= '<td><input type="number" class="form-control" id="cantidad_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['cantidad'] ?? 0) . '"onchange="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'cantidad\')"></td>';
            $html .= '<td><button type="button" class="btn btn-danger" onclick="JaxonalmacenMovimientos.eliminarFilaLotes(' . $indiceDelArreglo . ', ' . $index . ')"><i class="bi bi-x-circle"></i></button></td>';
            $html .= '</tr>';
        }
        $this->response->assign("tablaLotesBody", "innerHTML", $html);
        return $this->response;
    }

    function guardaLoteConsulta($indicePartida, $cantidadIntentada, $idLote)
    {
        registroLog('guardaLoteConsulta', 'Guardando lote (consulta)', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'cantidadIntentada' => $cantidadIntentada, 'idLote' => $idLote]);

        // Validar que la cantidad no exceda la existencia del lote
        global $database;
        $lote = $database->get("almacenes_productos_lotes", "*", ["id" => $idLote]);

        if ($cantidadIntentada > $lote['existencia'] and !getParametro('inventario_negativo')) {
            registroLog('guardaLoteConsulta', 'Cantidad excede existencia del lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'cantidadIntentada' => $cantidadIntentada, 'existenciaLote' => $lote['existencia']]);
            $this->alerta("Error", "La cantidad no puede exceder la existencia del lote.", "error");
            $this->generarTablaLotesConsulta($indicePartida);
            return $this->response;
        }

        // Buscar el lote en la sesión o agregarlo si no existe
        $loteIndex = array_search($idLote, array_column($_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'], 'iddbapl'));
        if ($loteIndex === false) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][] = [
                'iddb' => 0,
                'iddbapl' => $idLote,
                'cantidad' => $cantidadIntentada,
                'estado' => 'Activo'
            ];
        } else {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$loteIndex]['cantidad'] = $cantidadIntentada;
        }

        // Redibujar solo el cuerpo de la tabla de lotes
        $this->generarTablaLotesConsulta($indicePartida);
        // Sumar la cantidad de lotes y asignárselo al elemento del array partidas
        $this->ajusteCantidad($indicePartida);
        // Redibujar solo el cuerpo de la tabla de partidas
        $this->tablaPartidas();
        return $this->response;
    }

    function guardaDatoLotes($valor, $indicePartida, $indiceLote, $campo)
    {
        registroLog('guardaDatoLotes', 'Guardando dato de lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote, 'campo' => $campo, 'valor' => $valor]);

        // Validar que el índice de la partida y el lote existan en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote])) {
            // Actualizar el campo correspondiente en el array de la sesión
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote][$campo] = $valor;

            // Llamar a validaDatosLote para realizar las verificaciones adicionales
            $resultadoValidacion = $this->validaDatosLote($indicePartida, $indiceLote);

            if ($resultadoValidacion['error']) {
                registroLog('guardaDatoLotes', 'Error en validación de lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote, 'mensaje' => $resultadoValidacion['mensaje']]);
                $this->alerta("Error en la validación", $resultadoValidacion['mensaje'], "error");
            } else {
                if ($campo == 'lote' or $campo == 'serie') {
                    global $database;
                    $almacenes_producto = $database->get("almacenes_productos", "*", [
                        "id" => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos']
                    ]);

                    $almacenes_productos_lote = $database->get("almacenes_productos_lotes", "*", [
                        "idproducto" => $almacenes_producto['idproducto'],
                        "idalmacen"  => $almacenes_producto['idalmacen'],
                        "lote"       => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['lote'],
                        "serie"      => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['serie']
                    ]);

                    if ($almacenes_productos_lote) {
                        $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote] = [
                            'iddb'        => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['iddb'],  // El ID real del lote en la base de datos
                            'lote'        => $almacenes_productos_lote['lote'],
                            'serie'       => $almacenes_productos_lote['serie'],
                            'pedimento'   => $almacenes_productos_lote['pedimento'],
                            'fabricacion' => $almacenes_productos_lote['fabricacion'],
                            'caducidad'   => $almacenes_productos_lote['caducidad'],
                            'cantidad'    => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['cantidad'],
                            'estado'      => 'Activo'  // Mantiene el estado como "Activo"
                        ];
                        $this->alerta("Lote y/o serie encontrados", "Se han llenado automáticamente los campos restantes", "success", null, false, true);
                    }
                }
            }

            // Redibujar la tabla solo con los lotes actualizados
            $this->generarTablaLotes($indicePartida);
        } else {
            registroLog('guardaDatoLotes', 'Lote no encontrado en la sesión', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            $this->alerta("Error interno", "El lote especificado no existe, favor de comunicar este error con el administrador del sistema.", "error");
        }

        return $this->response;
    }

    function validaDatosLote($indicePartida, $indiceLote)
    {
        registroLog('validaDatosLote', 'Validando datos del lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);

        $loteActual = $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote];
        $loteActualId = "{$loteActual['lote']}|{$loteActual['serie']}|{$loteActual['fabricacion']}|{$loteActual['caducidad']}";

        // Verificar si la combinación lote-serie-fabricación-caducidad ya existe en otros lotes
        foreach ($_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'] as $index => $lote) {
            if ($index !== $indiceLote) {
                $loteId = "{$lote['lote']}|{$lote['serie']}|{$lote['fabricacion']}|{$lote['caducidad']}";
                if ($loteId === $loteActualId) {
                    // Si se encuentra una combinación repetida, limpiar los campos conflictivos
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['lote'] = '';
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['serie'] = '';
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['pedimento'] = '';
                    
                    registroLog('validaDatosLote', 'Combinación de lote-serie-fabricación-caducidad repetida', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
                    return [
                        'error' => true,
                        'mensaje' => 'La combinación de Lote, Serie, Fabricación y Caducidad ya existe. Los campos han sido limpiados.'
                    ];
                }
            }
        }

        // Verificar que la cantidad no sea 0 o negativa
        if (isset($loteActual['cantidad']) && $loteActual['cantidad'] <= 0) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['cantidad'] = 1;
            registroLog('validaDatosLote', 'Cantidad ajustada a 1', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return [
                'error' => true,
                'mensaje' => 'La cantidad no puede ser 0 o negativa. Se ha ajustado a 1.'
            ];
        }

        // Verificar que la fecha de fabricación no sea posterior al día de hoy
        if (!empty($loteActual['fabricacion']) && strtotime($loteActual['fabricacion']) > time()) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = date('Y-m-d');
            registroLog('validaDatosLote', 'Fecha de fabricación ajustada a hoy', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return [
                'error' => true,
                'mensaje' => 'La fecha de fabricación no puede ser posterior al día de hoy. Se ha ajustado a la fecha actual.'
            ];
        }

        // Verificar que el campo "lote" no exceda 254 caracteres
        if (strlen($loteActual['lote']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['lote'] = substr($loteActual['lote'], 0, 254);
            registroLog('validaDatosLote', 'Campo Lote ajustado a 254 caracteres', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'El campo Lote no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que el campo "serie" no exceda 254 caracteres
        if (strlen($loteActual['serie']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['serie'] = substr($loteActual['serie'], 0, 254);
            registroLog('validaDatosLote', 'Campo Serie ajustado a 254 caracteres', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'El campo Serie no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que el campo "pedimento" no exceda 254 caracteres
        if (strlen($loteActual['pedimento']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['pedimento'] = substr($loteActual['pedimento'], 0, 254);
            registroLog('validaDatosLote', 'Campo Pedimento ajustado a 254 caracteres', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'El campo Pedimento no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que la fecha de fabricación sea válida (formato: YYYY-MM-DD)
        if (!empty($loteActual['fabricacion']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loteActual['fabricacion'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = '0000-00-00';
            registroLog('validaDatosLote', 'Fecha de fabricación ajustada a 0000-00-00', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'La fecha de fabricación no es válida. Se ha ajustado a "0000-00-00".'];
        }

        // Verificar que la fecha de caducidad sea válida (formato: YYYY-MM-DD)
        if (!empty($loteActual['caducidad']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loteActual['caducidad'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['caducidad'] = '0000-00-00';
            registroLog('validaDatosLote', 'Fecha de caducidad ajustada a 0000-00-00', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'La fecha de caducidad no es válida. Se ha ajustado a "0000-00-00".'];
        }

        // Verificar que la cantidad sea un número válido (hasta 12 dígitos y 4 decimales)
        if (!preg_match('/^\d{1,12}(\.\d{1,4})?$/', $loteActual['cantidad'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['cantidad'] = 1;
            registroLog('validaDatosLote', 'Cantidad ajustada a 1', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indicePartida]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);
            return ['error' => true, 'mensaje' => 'La cantidad no es válida. Se ha ajustado a 1.'];
        }

        return ['error' => false];
    }

    function eliminarFilaLotes($indiceDelArreglo, $indiceLote)
    {
        registroLog('eliminarFilaLotes', 'Eliminando fila de lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos'], 'indiceLote' => $indiceLote]);

        // Verificar que el índice del lote exista
        if (isset($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'][$indiceLote])) {
            // Marcar el lote como 'Inactivo'
            $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'][$indiceLote]['estado'] = 'Inactivo';
        }

        // Redibujar solo el cuerpo de la tabla de lotes
        $this->generarTablaLotes($indiceDelArreglo);

        return $this->response;
    }

    function addFilaLote($indiceDelArreglo)
    {
        registroLog('addFilaLote', 'Agregando nueva fila de lote', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Crear un nuevo lote con valores vacíos o predefinidos
        $nuevoLote = [
            'iddb' => 0,
            'lote' => '',
            'serie' => '',
            'pedimento' => '',
            'fabricacion' => '',
            'caducidad' => '',
            'cantidad' => 1,
            'estado' => 'Activo'
        ];

        // Agregar el nuevo lote al arreglo de lotes de la sesión
        $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'][] = $nuevoLote;

        // Redibujar solo el cuerpo de la tabla de lotes
        $this->generarTablaLotes($indiceDelArreglo);

        return $this->response;
    }

    function validarLotesAlCerrarModal($indiceDelArreglo)
    {
        registroLog('validarLotesAlCerrarModal', 'Validando lotes al cerrar modal', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        $errores = [];  // Array para acumular mensajes de alerta
        $i = 0;
        $j = 0;
        foreach ($_SESSION['partidas' . $_GET['rand']] as $indicePartida => $partida) {
            if ($partida['estado'] !== 'Activo') {
                continue; // Solo validar lotes con estado "Activo"
            }
            $i++;
            foreach ($partida['lotes'] as $indiceLote => $lote) {
                if ($lote['estado'] !== 'Activo') {
                    continue; // Solo validar lotes con estado "Activo"
                }
                $j++;
                // Verificar que al menos uno de los campos "lote" o "serie" esté registrado
                if (empty($lote['lote']) && empty($lote['serie'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['estado'] = 'Inactivo';
                    $errores[] = "El lote $j de la partida $i no tiene Lote ni Serie registrados. Se ha eliminado.";
                    continue;
                }

                // Setear fechas a "0000-00-00" si están vacías
                if (empty($lote['fabricacion'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = '0000-00-00';
                }
                if (empty($lote['caducidad'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['caducidad'] = '0000-00-00';
                }

                // Verificar que el campo "lote" no exceda 254 caracteres
                if (strlen($lote['lote']) > 254) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['lote'] = substr($lote['lote'], 0, 254);
                    $errores[] = "El campo Lote del lote $j de la partida $i excedía 254 caracteres. Se ha ajustado.";
                }

                // Verificar que el campo "serie" no exceda 254 caracteres
                if (strlen($lote['serie']) > 254) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['serie'] = substr($lote['serie'], 0, 254);
                    $errores[] = "El campo Serie del lote $j de la partida $i excedía 254 caracteres. Se ha ajustado.";
                }

                // Verificar que el campo "pedimento" no exceda 254 caracteres
                if (strlen($lote['pedimento']) > 254) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['pedimento'] = substr($lote['pedimento'], 0, 254);
                    $errores[] = "El campo Pedimento del lote $j de la partida $i excedía 254 caracteres. Se ha ajustado.";
                }

                // Verificar que la fecha de fabricación sea válida (formato: YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lote['fabricacion'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = '0000-00-00';
                    $errores[] = "La fecha de fabricación del lote $j de la partida $i no era válida. Se ha ajustado a '0000-00-00'.";
                }

                // Verificar que la fecha de caducidad sea válida (formato: YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lote['caducidad'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['caducidad'] = '0000-00-00';
                    $errores[] = "La fecha de caducidad del lote $j de la partida $i no era válida. Se ha ajustado a '0000-00-00'.";
                }

                // Verificar que la cantidad sea un número válido (hasta 12 dígitos y 4 decimales)
                if (!preg_match('/^\d{1,12}(\.\d{1,4})?$/', $lote['cantidad'])) {
                    $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['cantidad'] = 1;
                    $errores[] = "La cantidad del lote $j de la partida $i no era válida. Se ha ajustado a 1.";
                }
            }
        }

        // Mostrar todas las alertas juntas
        if (!empty($errores)) {
            registroLog('validarLotesAlCerrarModal', 'Errores de validación encontrados', ['errores' => $errores]);
            $this->alerta(
                "Errores de validación", 
                implode("<br>", $errores), 
                "error"
            );
        }

        $this->ajusteCantidad($indiceDelArreglo);
        $this->tablaPartidas();
        return $this->response;
    }

    function ajusteCantidad($indiceDelArreglo)
    {
        registroLog('ajusteCantidad', 'Ajustando cantidad de partida', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);

        // Verificar que la partida exista en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo])) {
            $lotes = $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] ?? [];

            // Sumar las cantidades de todos los lotes activos
            $cantidadTotal = array_reduce($lotes, function ($carry, $lote) {
                return $carry + ($lote['estado'] == 'Activo' ? (int)$lote['cantidad'] : 0);
            }, 0);

            registroLog('ajusteCantidad', 'Cantidad total calculada', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos'], 'cantidadTotal' => $cantidadTotal]);

            // Validar que la cantidad cumpla con todos los requisitos
            $this->response->script("JaxonalmacenMovimientos.validaCantidadPartida($indiceDelArreglo, jaxon.getFormValues('formulario" . $_GET['rand'] . "'), $cantidadTotal);");
        } else {
            registroLog('ajusteCantidad', 'Partida no encontrada en la sesión', ['idalmacenes_productos' => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);
            $this->alerta("Error interno", "La partida especificada no existe. Por favor, comuníquese con el administrador del sistema.", "error");
        }
    }

    function validar($form, $accion)
    {
        registroLog('validar', 'Validando formulario', ['accion' => $accion]);

        // Definir las reglas de validación
        $reglas = [
            'idconcepto' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idalmacen' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idsocio' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idsubcuenta' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'fechahora' => ['obligatorio' => true, 'tipo' => 'datetime'],
            'documento' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'referencia' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'iddireccion_origen' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'iddireccion_destino' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'notas' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 500],
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
            $naturaleza = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);

            if ($naturaleza == 'Entrada') {
                registroLog('validar', 'Naturaleza del movimiento: Entrada', ['idconcepto' => $form['idconcepto']]);
                $this->modalPreGuardar($form, $accion);
            } elseif ($naturaleza == 'Salida') {
                registroLog('validar', 'Naturaleza del movimiento: Salida', ['idconcepto' => $form['idconcepto']]);
                if (getParametro('inventario_negativo')) {
                    registroLog('validar', 'Inventario negativo permitido', []);
                    $this->modalPreGuardar($form, $accion);
                } else {
                    $validacionInventario = validarExistenciaSalida($_SESSION['partidas' . $_GET['rand']]);

                    // Verificar si hubo un error en la validación del inventario
                    if (isset($validacionInventario['error'])) {
                        registroLog('validar', 'Error en la validación de existencia', ['error' => $validacionInventario['error']]);
                        $this->alerta(
                            "Error en la validación de existencia",
                            $validacionInventario['error'],
                            "error"
                        );
                        return $this->response;
                    }

                    // Si pasa la validación del inventario, continuar con el guardado
                    registroLog('validar', 'Validación de existencia exitosa', []);
                    $this->modalPreGuardar($form, $accion);
                }
            }
        }
        return $this->response;
    }

    function modalPreGuardar($form, $accion)
    {
        registroLog('modalPreGuardar', 'Entrando a la funcion', []);

        if ($accion == 'procesar') {
            registroLog('modalPreGuardar', 'Mostrando modal alerta de confirmación para procesar el movimiento', []);

            // Codificar el array PHP como JSON para que pueda ser interpretado en JavaScript
            $formJson = json_encode($form);

            // Escapar comillas dobles para que no rompan el string en JavaScript
            $formJsonEscapado = str_replace('"', '\"', $formJson);

            // Mostrar el mensaje de confirmación
            $this->alertaConfirmacion(
                "¿PROCESAR?",
                "Se procesará el movimiento actual, alterando el inventario del almacén y los productos seleccionados. ¿Desea continuar?",
                "warning",
                "JaxonalmacenMovimientos.guardar(JSON.parse(\"$formJsonEscapado\"), \"$accion\");"
            );
        }

        return $this->response;
    }



    function guardar($formulario, $accion)
    {
        global $database;

        registroLog('guardar', 'Guardando movimiento', ['accion' => $accion]);

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            $idMovimiento = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            // Determinar el folio solo si es un nuevo registro
            if ($idMovimiento == 0) {
                $folio = getConsecutivo('almacen_movimientos');
                registroLog('guardar', 'Nuevo movimiento, folio generado', ['folio' => $folio]);
            } else {
                // Recuperar el folio actual desde la base de datos para mantenerlo
                $folio = $database->get('almacenes_movimientos', 'folio', ['id' => $idMovimiento]);
                registroLog('guardar', 'Movimiento existente, folio recuperado', ['folio' => $folio]);
            }

            // Preparar los datos para insertar o actualizar
            $data = [
                'idempresa' => isset($_SESSION['idempresa']) ? $_SESSION['idempresa'] : 0,
                'idsucursal' => isset($_SESSION['idsucursal']) ? $_SESSION['idsucursal'] : 0,
                'idconcepto' => isset($formulario['idconcepto']) ? $formulario['idconcepto'] : 0,
                'idalmacen' => isset($formulario['idalmacen']) ? $formulario['idalmacen'] : 0,
                'iddireccion_origen' => isset($formulario['iddireccion_origen']) ? $formulario['iddireccion_origen'] : 0,
                'iddireccion_destino' => isset($formulario['iddireccion_destino']) ? $formulario['iddireccion_destino'] : 0,
                'idsocio' => isset($formulario['idsocio']) ? $formulario['idsocio'] : 0,
                'idsubcuenta' => isset($formulario['idsubcuenta']) ? $formulario['idsubcuenta'] : 0,
                'idcreador' => isset($_SESSION['idusuario']) ? $_SESSION['idusuario'] : 0,
                'folio' => $folio, // Aquí usamos el folio ya sea el nuevo o el recuperado
                'estado' => ($accion == 'borrador') ? 'Borrador' : 'Activo',
                'fecha' => isset($formulario['fechahora']) ? $formulario['fechahora'] : '0000-00-00 00:00:00',
                'documento' => isset($formulario['documento']) ? $formulario['documento'] : '',
                'referencia' => isset($formulario['referencia']) ? $formulario['referencia'] : '',
                'notas' => isset($formulario['notas']) ? $formulario['notas'] : '',
                'fecha_procesado' => ($accion == 'procesar') ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
                'fecha_cancelacion' => '0000-00-00 00:00:00',
            ];

            if ($accion == 'borrador') {
                if ($idMovimiento == 0) {
                    // Insertar nuevo borrador
                    $database->insert('almacenes_movimientos', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Nuevo borrador insertado', ['idmovimiento' => $insert_id]);
                    $this->guardarPartidas($formulario, $insert_id, $accion);
                    $this->alerta(
                        "Éxito",
                        "Movimiento guardado como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar borrador existente
                    $database->update('almacenes_movimientos', $data, ['id' => $idMovimiento]);
                    registroLog('guardar', 'Borrador actualizado', ['idmovimiento' => $idMovimiento]);
                    $this->guardarPartidas($formulario, $idMovimiento, $accion);
                    $this->alerta(
                        "Éxito",
                        "Movimiento actualizado como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                }
            } elseif ($accion == 'procesar') {
                if ($idMovimiento == 0) {
                    // Insertar nuevo procesado
                    $database->insert('almacenes_movimientos', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Nuevo movimiento procesado', ['idmovimiento' => $insert_id]);
                    $this->guardarPartidas($formulario, $insert_id, $accion);
                    $this->alerta(
                        "Éxito",
                        "Movimiento procesado correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar procesado existente
                    $database->update('almacenes_movimientos', $data, ['id' => $idMovimiento]);
                    registroLog('guardar', 'Movimiento actualizado y procesado', ['idmovimiento' => $idMovimiento]);
                    $this->guardarPartidas($formulario, $idMovimiento, $accion);
                    $this->alerta(
                        "Éxito",
                        "Movimiento actualizado y procesado correctamente.",
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
            registroLog('guardar', 'Transacción confirmada', ['idmovimiento' => $idMovimiento]);
        } catch (PDOException $e) {
            // Revertir la transacción en caso de error
            $database->pdo->rollBack();
            registroLog('guardar', 'Error en la transacción', ['error' => $e->getMessage()]);
            $this->alerta(
                "Error",
                "No se pudo completar la operación, por favor contacte con el administrador.",
                "error"
            );
        }

        return $this->response;
    }

    function guardarPartidas($formulario, $idmovimiento, $accion)
    {
        global $database;

        registroLog('guardarPartidas', 'Guardando partidas del movimiento', ['idmovimiento' => $idmovimiento, 'accion' => $accion]);

        if (isset($_SESSION['partidas' . $_GET['rand']]) && is_array($_SESSION['partidas' . $_GET['rand']])) {
            foreach ($_SESSION['partidas' . $_GET['rand']] as $indicePartida => $partida) {

                if ($partida['iddb'] == 0 && ($partida['cantidad'] == 0 || $partida['estado'] == 'Inactivo')) {
                    continue;
                }

                $producto = $database->get("almacenes_productos", "*", ["id" => $partida['idalmacenes_productos']]);
                $data = [
                    'idmovimiento' => $idmovimiento,
                    'idproducto' => $producto['idproducto'],
                    'idalmacenes_productos' => $partida['idalmacenes_productos'],
                    'cantidad' => $partida['cantidad'],
                    'estado' => $partida['estado'],
                    'notas' => $partida['notas'],
                    'idtablaref' => 0,
                ];

                if ($partida['iddb'] == 0) {
                    $database->insert('almacenes_movimientos_productos', $data);
                    $idpartida = $database->id();
                    registroLog('guardarPartidas', 'Nueva partida insertada', ['idpartida' => $idpartida]);
                } else {
                    $database->update('almacenes_movimientos_productos', $data, ['id' => $partida['iddb']]);
                    $idpartida = $partida['iddb'];
                    registroLog('guardarPartidas', 'Partida actualizada', ['idpartida' => $idpartida]);
                }

                $tieneLotes = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);

                if ($tieneLotes == 'Sí') {
                    $naturalezaMovimiento = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $formulario['idconcepto']]);
                    if (isset($partida['lotes']) && is_array($partida['lotes'])) {
                        registroLog('guardarPartidas', 'Guardando lotes de la partida', ['idpartida' => $idpartida]);
                        $this->guardarLotes($formulario, $idmovimiento, $idpartida, $producto, $partida['lotes'], $naturalezaMovimiento, $accion);
                    }
                }
            }
        }
        return $this->response;
    }

    function guardarLotes($formulario, $idmovimiento, $idpartida, $producto, $lotes, $naturalezaMovimiento, $accion)
    {
        global $database;

        registroLog('guardarLotes', 'Guardando lotes de la partida', ['idpartida' => $idpartida, 'naturalezaMovimiento' => $naturalezaMovimiento]);

        foreach ($lotes as $lote) {

            if ($lote['iddb'] == 0 && ($lote['cantidad'] == 0 || $lote['estado'] == 'Inactivo')) {
                continue;
            }

            if ($naturalezaMovimiento == 'Salida') {
                $loteDB = $database->get("almacenes_productos_lotes", "*", ["id" => $lote['iddbapl']]);
                $loteData = [
                    'idmovimiento' => $idmovimiento,
                    'idmovimiento_producto' => $idpartida,
                    'idalmacen_producto_lote' => $lote['iddbapl'],
                    'idproducto' => $producto['idproducto'],
                    'cantidad' => $lote['cantidad'],
                    'estado' => $lote['estado'],
                    'lote' => $loteDB['lote'],
                    'serie' => $loteDB['serie'],
                    'pedimento' => $loteDB['pedimento'],
                    'caducidad' => $loteDB['caducidad'],
                    'fabricacion' => $loteDB['fabricacion'],
                    'idtablaref' => 0,
                ];
            } elseif ($naturalezaMovimiento == 'Entrada') {
                if ($accion == "procesar") {
                    $idalmacene_producto_lote = $this->registrarLote($producto, $formulario, $lote);
                    $almacenes_productos_lote = $database->get("almacenes_productos_lotes", "*", ["id" => $idalmacene_producto_lote]);
                    $loteData = [
                        'idmovimiento' => $idmovimiento,
                        'idmovimiento_producto' => $idpartida,
                        'idalmacen_producto_lote' => $idalmacene_producto_lote,
                        'idproducto' => $almacenes_productos_lote['idproducto'],
                        'cantidad' => $lote['cantidad'],
                        'estado' => $lote['estado'],
                        'lote' => $almacenes_productos_lote['lote'],
                        'serie' => $almacenes_productos_lote['serie'],
                        'pedimento' => $almacenes_productos_lote['pedimento'],
                        'caducidad' => $almacenes_productos_lote['caducidad'],
                        'fabricacion' => $almacenes_productos_lote['fabricacion'],
                        'idtablaref' => 0,
                    ];
                } elseif ($accion == "borrador") {
                    $loteData = [
                        'idmovimiento' => $idmovimiento,
                        'idmovimiento_producto' => $idpartida,
                        'idalmacen_producto_lote' => 0,
                        'idproducto' => $producto['idproducto'],
                        'cantidad' => $lote['cantidad'],
                        'estado' => $lote['estado'],
                        'lote' => $lote['lote'],
                        'serie' => $lote['serie'],
                        'pedimento' => $lote['pedimento'],
                        'caducidad' => $lote['caducidad'],
                        'fabricacion' => $lote['fabricacion'],
                        'idtablaref' => 0,
                    ];
                }
            }

            if ($lote['iddb'] == 0) {
                $database->insert('almacenes_movimientos_productos_lotes', $loteData);
                registroLog('guardarLotes', 'Nuevo lote insertado', ['idpartida' => $idpartida]);
            } else {
                $database->update('almacenes_movimientos_productos_lotes', $loteData, ['id' => $lote['iddb']]);
                registroLog('guardarLotes', 'Lote actualizado', ['idpartida' => $idpartida]);
            }
        }
    }

    function registrarLote($producto, $formulario, $lote)
    {
        global $database;

        registroLog('registrarLote', 'Registrando nuevo lote', ['idproducto' => $producto['idproducto'], 'idalmacen' => $formulario['idalmacen']]);

        $almacenes_productos_lote = $database->get("almacenes_productos_lotes", "*", [
            "idproducto" => $producto['idproducto'],
            "idalmacen" => $formulario['idalmacen'],
            "lote" => $lote['lote'],
            "serie" => $lote['serie']
        ]);

        if ($almacenes_productos_lote) {
            registroLog('registrarLote', 'Lote existente encontrado', ['idalmacenes_productos_lote' => $almacenes_productos_lote['id']]);
            return $almacenes_productos_lote['id'];
        } else {
            $loteData = [
                'idproducto' => $producto['idproducto'],
                'idalmacen' => $formulario['idalmacen'],
                'lote' => $lote['lote'],
                'serie' => $lote['serie'],
                'pedimento' => $lote['pedimento'],
                'caducidad' => $lote['caducidad'],
                'fabricacion' => $lote['fabricacion'],
                'existencia' => 0
            ];
            $database->insert('almacenes_productos_lotes', $loteData);
            $idLote = $database->id();
            registroLog('registrarLote', 'Nuevo lote registrado', ['idalmacenes_productos_lote' => $idLote]);
            return $idLote;
        }
    }

    function cancelarMovimiento($form)
    {
        global $database;

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            registroLog('cancelarMovimiento', 'Iniciando cancelación de movimiento', ['idMovimiento' => $_GET['id']]);

            $idMovimiento = intval($_GET['id']);

            // Obtener el movimiento original
            $movimiento = $database->get("almacenes_movimientos", "*", ["id" => $idMovimiento]);

            if (!$movimiento) {
                registroLog('cancelarMovimiento', 'Movimiento no encontrado', ['idMovimiento' => $idMovimiento]);
                $this->alerta("Error", "Movimiento no encontrado, por favor comuniquese con el administrador del sistema", "error");
                return $this->response;
            }

            if ($movimiento['estado'] == 'Borrador') {
                registroLog('cancelarMovimiento', 'Cancelando movimiento en estado Borrador', ['idMovimiento' => $idMovimiento]);

                // Si el movimiento está en borrador, simplemente se cancela
                $database->update('almacenes_movimientos', [
                    "estado" => "Cancelado",
                    "fecha_cancelacion" => date('Y-m-d H:i:s')
                ], ['id' => $idMovimiento]);

                // Confirmar la transacción
                $database->pdo->commit();
                registroLog('cancelarMovimiento', 'Movimiento en borrador cancelado correctamente', ['idMovimiento' => $idMovimiento]);
                $this->alerta("Exito", "Movimiento en borrador cancelado correctamente", "success", null, true, false, "index.php");
                return $this->response;
            }

            if ($movimiento['estado'] == 'Activo') {
                registroLog('cancelarMovimiento', 'Cancelando movimiento en estado Activo', ['idMovimiento' => $idMovimiento]);

                $concepto = $database->get("almacenes_movimientos_conceptos", "*", ["id" => $movimiento['idconcepto']]);

                if ($concepto['naturaleza'] == "Salida") {
                    registroLog('cancelarMovimiento', 'Creando movimiento de entrada para revertir salida', ['idMovimiento' => $idMovimiento]);

                    // Crear un nuevo movimiento de "Entrada" para revertir la salida
                    $database->insert("almacenes_movimientos", [
                        'idempresa' => $movimiento['idempresa'],
                        'idsucursal' => $movimiento['idsucursal'],
                        'idconcepto' => 30, // Concepto de "Entrada por cancelación"
                        'idalmacen' => $movimiento['idalmacen'],
                        'iddireccion_origen' => 0,
                        'iddireccion_destino' => 0,
                        'idsocio' => $movimiento['idsocio'],
                        'idsubcuenta' => $movimiento['idsubcuenta'],
                        'idcreador' => $_SESSION['idusuario'],
                        'folio' => getConsecutivo('almacen_movimientos'),
                        'estado' => 'Activo',
                        'fecha' => date('Y-m-d H:i:s'),
                        'documento' => $movimiento['folio'],
                        'referencia' => $movimiento['folio'],
                        'notas' => 'Movimiento de entrada generado automaticamente para cancelar la salida ' . $movimiento['folio'],
                        'fecha_procesado' => date('Y-m-d H:i:s'),
                        'fecha_cancelacion' => '0000-00-00 00:00:00',
                    ]);
                    $idNuevoMovimiento = $database->id(); // Obtiene el último ID insertado

                    registroLog('cancelarMovimiento', 'Nuevo movimiento de entrada creado', ['idNuevoMovimiento' => $idNuevoMovimiento]);

                    // Obtener los productos del movimiento original
                    $productos = $database->select("almacenes_movimientos_productos", "*", ["idmovimiento" => $idMovimiento, "estado" => "Activo"]);

                    foreach ($productos as $producto) {
                        // Insertar el mismo producto en la entrada pero con cantidad positiva
                        $database->insert("almacenes_movimientos_productos", [
                            'idmovimiento' => $idNuevoMovimiento,
                            'idproducto' => $producto['idproducto'],
                            'idalmacenes_productos' => $producto['idalmacenes_productos'],
                            'cantidad' => $producto['cantidad'], // Se mantiene la cantidad original
                            'estado' => 'Activo',
                            'idtablaref' => $producto['id'],
                            'notas' => 'Entrada por cancelación de movimiento ' . $movimiento['folio'],
                        ]);
                        $idNuevoProducto = $database->id(); // Obtiene el último ID insertado

                        registroLog('cancelarMovimiento', 'Producto agregado al nuevo movimiento de entrada', ['idNuevoProducto' => $idNuevoProducto]);

                        $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                        if ($productoLoteSerie == "Sí") {
                            // Obtener los lotes asociados al producto original
                            $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", ["idmovimiento_producto" => $producto['id'], "estado" => "Activo"]);

                            foreach ($lotes as $lote) {
                                // Insertar el mismo lote en la entrada
                                $database->insert("almacenes_movimientos_productos_lotes", [
                                    'idmovimiento' => $idNuevoMovimiento,
                                    'idmovimiento_producto' => $idNuevoProducto,
                                    'idalmacen_producto_lote' => $lote['idalmacen_producto_lote'],
                                    'idproducto' => $lote['idproducto'],
                                    'cantidad' => $lote['cantidad'], // Se mantiene la cantidad original
                                    'estado' => 'Activo',
                                    'lote' => $lote['lote'],
                                    'serie' => $lote['serie'],
                                    'pedimento' => $lote['pedimento'],
                                    'caducidad' => $lote['caducidad'],
                                    'fabricacion' => $lote['fabricacion'],
                                    'idtablaref' => $lote['idtablaref'],
                                ]);

                                registroLog('cancelarMovimiento', 'Lote agregado al nuevo movimiento de entrada', ['idLote' => $lote['id']]);
                            }
                        }
                    }

                    // Marcar el movimiento original como cancelado
                    $database->update('almacenes_movimientos', [
                        "estado" => "Cancelado",
                        "fecha_cancelacion" => date('Y-m-d H:i:s')
                    ], ['id' => $idMovimiento]);

                    registroLog('cancelarMovimiento', 'Movimiento original marcado como cancelado', ['idMovimiento' => $idMovimiento]);
                }

                if ($concepto['naturaleza'] == "Entrada") {
                    registroLog('cancelarMovimiento', 'Creando movimiento de salida para cancelar entrada', ['idMovimiento' => $idMovimiento]);

                    // Obtener los productos del movimiento original
                    $productos = $database->select("almacenes_movimientos_productos", "*", [
                        "idmovimiento" => $idMovimiento,
                        "estado" => "Activo"
                    ]);

                    // Validar si la empresa puede manejar inventarios negativos
                    if (!getParametro('inventario_negativo')) {
                        registroLog('cancelarMovimiento', 'Validando existencia de productos para cancelar entrada', ['idMovimiento' => $idMovimiento]);

                        // Preparar partidas para la validación
                        $partidas = [];
                        foreach ($productos as $producto) {
                            $partida = [
                                'idalmacenes_productos' => $producto['idalmacenes_productos'],
                                'cantidad' => $producto['cantidad'],
                                'estado' => $producto['estado'],
                                'lotes' => []
                            ];

                            // Verificar si el producto maneja lotes/series
                            $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                            if ($productoLoteSerie == "Sí") {
                                $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", [
                                    "idmovimiento_producto" => $producto['id'],
                                    "estado" => "Activo"
                                ]);

                                foreach ($lotes as $lote) {
                                    $partida['lotes'][] = [
                                        'iddbapl' => $lote['idalmacen_producto_lote'],
                                        'cantidad' => $lote['cantidad']
                                    ];
                                }
                            }

                            $partidas[] = $partida;
                        }

                        // Validar existencia antes de generar la salida
                        $validacionInventario = validarExistenciaSalida($partidas);

                        if (isset($validacionInventario['error'])) {
                            registroLog('cancelarMovimiento', 'Error en la validación de existencia', ['error' => $validacionInventario['error']]);
                            $this->alerta("Error en la validación de existencia", $validacionInventario['error'], "error");
                            return $this->response;
                        }
                    }

                    // Crear un nuevo movimiento de "Salida" para cancelar la entrada
                    $database->insert("almacenes_movimientos", [
                        'idempresa' => $movimiento['idempresa'],
                        'idsucursal' => $movimiento['idsucursal'],
                        'idconcepto' => 19, // Concepto de "Salida por cancelación"
                        'idalmacen' => $movimiento['idalmacen'],
                        'iddireccion_origen' => 0,
                        'iddireccion_destino' => 0,
                        'idsocio' => $movimiento['idsocio'],
                        'idsubcuenta' => $movimiento['idsubcuenta'],
                        'idcreador' => $_SESSION['idusuario'],
                        'folio' => getConsecutivo('almacen_movimientos'),
                        'estado' => 'Activo',
                        'fecha' => date('Y-m-d H:i:s'),
                        'documento' => $movimiento['folio'],
                        'referencia' => $movimiento['folio'],
                        'notas' => 'Movimiento de salida generado automáticamente para cancelar la entrada ' . $movimiento['folio'],
                        'fecha_procesado' => date('Y-m-d H:i:s'),
                        'fecha_cancelacion' => '0000-00-00 00:00:00',
                    ]);
                    $idNuevoMovimiento = $database->id(); // Obtiene el último ID insertado

                    registroLog('cancelarMovimiento', 'Nuevo movimiento de salida creado', ['idNuevoMovimiento' => $idNuevoMovimiento]);

                    foreach ($productos as $producto) {
                        $database->insert("almacenes_movimientos_productos", [
                            'idmovimiento' => $idNuevoMovimiento,
                            'idproducto' => $producto['idproducto'],
                            'idalmacenes_productos' => $producto['idalmacenes_productos'],
                            'cantidad' => $producto['cantidad'], // Se mantiene la cantidad original
                            'estado' => 'Activo',
                            'idtablaref' => $producto['id'],
                            'notas' => 'Salida por cancelación de movimiento ' . $movimiento['folio'],
                        ]);
                        $idNuevoProducto = $database->id(); // Obtiene el último ID insertado

                        registroLog('cancelarMovimiento', 'Producto agregado al nuevo movimiento de salida', ['idNuevoProducto' => $idNuevoProducto]);

                        // Verificar si el producto maneja lotes/series
                        $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                        if ($productoLoteSerie == "Sí") {
                            // Obtener los lotes asociados al producto original
                            $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", ["idmovimiento_producto" => $producto['id'], "estado" => "Activo"]);
                            foreach ($lotes as $lote) {
                                $database->insert("almacenes_movimientos_productos_lotes", [
                                    'idmovimiento' => $idNuevoMovimiento,
                                    'idmovimiento_producto' => $idNuevoProducto,
                                    'idalmacen_producto_lote' => $lote['idalmacen_producto_lote'],
                                    'idproducto' => $producto['idproducto'],
                                    'cantidad' => $lote['cantidad'], // Se mantiene la cantidad original
                                    'estado' => 'Activo',
                                    'lote' => $lote['lote'],
                                    'serie' => $lote['serie'],
                                    'pedimento' => $lote['pedimento'],
                                    'caducidad' => $lote['caducidad'],
                                    'fabricacion' => $lote['fabricacion'],
                                    'idtablaref' => $lote['id'],
                                ]);

                                registroLog('cancelarMovimiento', 'Lote agregado al nuevo movimiento de salida', ['idLote' => $lote['id']]);
                            }
                        }
                    }

                    // Marcar el movimiento original como cancelado
                    $database->update('almacenes_movimientos', [
                        "estado" => "Cancelado",
                        "fecha_cancelacion" => date('Y-m-d H:i:s')
                    ], ['id' => $idMovimiento]);

                    registroLog('cancelarMovimiento', 'Movimiento original marcado como cancelado', ['idMovimiento' => $idMovimiento]);
                }
            }

            // Confirmar la transacción
            $database->pdo->commit();
            registroLog('cancelarMovimiento', 'Transacción confirmada', ['idMovimiento' => $idMovimiento]);
            $this->alerta("Exito", "Movimiento cancelado", "success", null, true, false, "index.php");
            return $this->response;

        } catch (PDOException $e) {
            // Revertir la transacción en caso de error
            $database->pdo->rollBack();
            registroLog('cancelarMovimiento', 'Error en la transacción', ['error' => $e->getMessage()]);
            $this->alerta(
                "Error",
                "No se pudo completar la operación, por favor contacte con el administrador del sistema.",
                "error"
            );
            return $this->response;
        }
    }



    /*function cancelarMovimiento($form)
    {
        registroLog('validarCancelar', 'Validando formulario para la cancelacion', ['getId' => $_GET['id']]);

        global $database;

        $idMovimiento = intval($_GET['id']);

        // Obtener el movimiento original
        $movimiento = $database->get("almacenes_movimientos", "*", ["id" => $idMovimiento]);

        if (!$movimiento) {
            return ["error" => "El movimiento no existe"];
        }

        if ($movimiento['estado'] == 'Borrador') {
            // Si el movimiento está en borrador, simplemente se cancela
            $database->update('almacenes_movimientos', [
                "estado" => "Cancelado",
                "fecha_cancelacion" => date('Y-m-d H:i:s')
            ], ['id' => $idMovimiento]);
            
            return ["mensaje" => "Movimiento en borrador cancelado correctamente"];
        }

        if ($movimiento['estado'] == 'Activo') {
            $concepto = $database->get("almacenes_movimientos_conceptos", "*", ["id" => $movimiento['idconcepto']]);

            if ($concepto['naturaleza'] == "Salida") {
                // Crear un nuevo movimiento de "Entrada" para revertir la salida
                $idNuevoMovimiento = $database->insert("almacenes_movimientos", [
                    'idempresa' => $movimiento['idempresa'],
                    'idsucursal' => $movimiento['idsucursal'],
                    'idconcepto' => 30, // Concepto de "Entrada por cancelación"
                    'idalmacen' => $movimiento['idalmacen'],
                    'iddireccion_origen' => 0,
                    'iddireccion_destino' => 0,
                    'idsocio' => $movimiento['idsocio'],
                    'idsubcuenta' => $movimiento['idsubcuenta'],
                    'idcreador' => $_SESSION['idusuario'],
                    'folio' => getConsecutivo('almacen_movimientos'),
                    'estado' => 'Activo',
                    'fecha' => date('Y-m-d H:i:s'),
                    'documento' => $movimiento['folio'],
                    'referencia' => $movimiento['folio'],
                    'notas' => 'Movimiento de entrada generado automaticamente para cancelar la salida ' . $movimiento['folio'],
                    'fecha_procesado' => date('Y-m-d H:i:s'),
                    'fecha_cancelacion' => '0000-00-00 00:00:00',
                ]);
                // Obtener los productos del movimiento original
                $productos = $database->select("almacenes_movimientos_productos", "*", ["idmovimiento" => $idMovimiento, "estado" => "Activo"]);

                foreach ($productos as $producto) {
                    // Insertar el mismo producto en la entrada pero con cantidad positiva
                    $idNuevoProducto = $database->insert("almacenes_movimientos_productos", [
                        'idmovimiento' => $idNuevoMovimiento,
                        'idproducto' => $producto['idproducto'],
                        'idalmacenes_productos' => $producto['idalmacenes_productos'],
                        'cantidad' => $producto['cantidad'], // Se mantiene la cantidad original
                        'estado' => 'Activo',
                        'idtablaref' => $producto['id'],
                        'notas' => 'Entrada por cancelación de movimiento ' . $movimiento['folio'],
                    ]);

                    $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                    if($productoLoteSerie=="Sí")
                    {
                        // Obtener los lotes asociados al producto original
                        $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", ["idmovimiento_producto" => $producto['id']]);

                        foreach ($lotes as $lote) {
                            // Insertar el mismo lote en la entrada
                            $database->insert("almacenes_movimientos_productos_lotes", [
                                'idmovimiento' => $idNuevoMovimiento,
                                'idmovimiento_producto' => $idNuevoProducto,
                                'idalmacen_producto_lote' => $lote['idalmacen_producto_lote'],
                                'idproducto' => $lote['idproducto'],
                                'cantidad' => $lote['cantidad'], // Se mantiene la cantidad original
                                'estado' => 'Activo',
                                'lote' => $lote['lote'],
                                'serie' => $lote['serie'],
                                'pedimento' => $lote['pedimento'],
                                'caducidad' => $lote['caducidad'],
                                'fabricacion' => $lote['fabricacion'],
                                'idtablaref' => $lote['idtablaref'],
                            ]);
                        }
                    }
                }

                // Marcar el movimiento original como cancelado
                $database->update('almacenes_movimientos', [
                    "estado" => "Cancelado",
                    "fecha_cancelacion" => date('Y-m-d H:i:s')
                ], ['id' => $idMovimiento]);
            }
            if ($concepto['naturaleza'] == "Entrada") {
                // Obtener los productos del movimiento original
                $productos = $database->select("almacenes_movimientos_productos", "*", [
                    "idmovimiento" => $idMovimiento, 
                    "estado" => "Activo"
                ]);
            
                //validar si la empresa puede manejar inventarios negativos
                if(!getParametro('inventario_negativo'))
                {
                    // Preparar partidas para la validación
                    $partidas = [];
                    foreach ($productos as $producto) {
                        $partida = [
                            'idalmacenes_productos' => $producto['idalmacenes_productos'],
                            'cantidad' => $producto['cantidad'],
                            'estado' => $producto['estado'],
                            'lotes' => []
                        ];

                        // Verificar si el producto maneja lotes/series
                        $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                        if ($productoLoteSerie == "Sí") {
                            $lotes = $database->select("almacenes_movimientos_productos_lotes", "*", [
                                "idmovimiento_producto" => $producto['id']
                            ]);

                            foreach ($lotes as $lote) {
                                $partida['lotes'][] = [
                                    'iddbapl' => $lote['idalmacen_producto_lote'],
                                    'cantidad' => $lote['cantidad']
                                ];
                            }
                        }

                        $partidas[] = $partida;
                    }

                    // Validar existencia antes de generar la salida
                    $validacionInventario = validarExistenciaSalida($partidas);

                    if (isset($validacionInventario['error'])) {
                        registroLog('validar', 'Error en la validación de existencia', ['error' => $validacionInventario['error']]);
                        $this->alerta("Error en la validación de existencia", $validacionInventario['error'], "error");
                        return $this->response;
                    }
                }
                
                // Crear un nuevo movimiento de "Salida" para cancelar la entrada
                $idNuevoMovimiento = $database->insert("almacenes_movimientos", [
                    'idempresa' => $movimiento['idempresa'],
                    'idsucursal' => $movimiento['idsucursal'],
                    'idconcepto' => 19, // Concepto de "Salida por cancelación"
                    'idalmacen' => $movimiento['idalmacen'],
                    'iddireccion_origen' => 0,
                    'iddireccion_destino' => 0,
                    'idsocio' => $movimiento['idsocio'],
                    'idsubcuenta' => $movimiento['idsubcuenta'],
                    'idcreador' => $_SESSION['idusuario'],
                    'folio' => getConsecutivo('almacen_movimientos'),
                    'estado' => 'Activo',
                    'fecha' => date('Y-m-d H:i:s'),
                    'documento' => $movimiento['folio'],
                    'referencia' => $movimiento['folio'],
                    'notas' => 'Movimiento de salida generado automáticamente para cancelar la entrada ' . $movimiento['folio'],
                    'fecha_procesado' => date('Y-m-d H:i:s'),
                    'fecha_cancelacion' => '0000-00-00 00:00:00',
                ]);
            
                foreach ($productos as $producto) {
                    $idNuevoProducto = $database->insert("almacenes_movimientos_productos", [
                        'idmovimiento' => $idNuevoMovimiento,
                        'idproducto' => $producto['idproducto'],
                        'idalmacenes_productos' => $producto['idalmacenes_productos'],
                        'cantidad' => $producto['cantidad'], // Se mantiene la cantidad original
                        'estado' => 'Activo',
                        'idtablaref' => $producto['id'],
                        'notas' => 'Salida por cancelación de movimiento ' . $movimiento['folio'],
                    ]);
                    // Verificar si el producto maneja lotes/series
                    $productoLoteSerie = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);
                    if ($productoLoteSerie == "Sí") {
                        foreach ($partida['lotes'] as $lote) {
                            $database->insert("almacenes_movimientos_productos_lotes", [
                                'idmovimiento' => $idNuevoMovimiento,
                                'idmovimiento_producto' => $idNuevoProducto,
                                'idalmacen_producto_lote' => $lote['iddbapl'],
                                'idproducto' => $producto['idproducto'],
                                'cantidad' => $lote['cantidad'], // Se mantiene la cantidad original
                                'estado' => 'Activo',
                                'lote' => $lote['lote'],
                                'serie' => $lote['serie'],
                                'pedimento' => $lote['pedimento'],
                                'caducidad' => $lote['caducidad'],
                                'fabricacion' => $lote['fabricacion'],
                                'idtablaref' => $lote['idtablaref'],
                            ]);
                        }
                    }
                }
            
                // Marcar el movimiento original como cancelado
                $database->update('almacenes_movimientos', [
                    "estado" => "Cancelado",
                    "fecha_cancelacion" => date('Y-m-d H:i:s')
                ], ['id' => $idMovimiento]);
            }
            
        }
        return $this->response;
    }

    function guardarPartidas($formulario, $idmovimiento, $accion)
    {
        global $database;
        // Verificar si hay partidas en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']]) && is_array($_SESSION['partidas' . $_GET['rand']])) {
            foreach ($_SESSION['partidas' . $_GET['rand']] as $indicePartida => $partida) {
                
                // Omitir si la partida es nueva (iddb == 0) y no tiene cantidad o está inactiva
                if ($partida['iddb'] == 0 && ($partida['cantidad'] == 0 || $partida['estado'] == 'Inactivo')) {
                    continue;
                }
                $producto = $database->get("almacenes_productos", "*", ["id" => $partida['idalmacenes_productos']]);
                $data = [
                    'idmovimiento' => $idmovimiento,
                    'idproducto' => $producto['idproducto'],
                    'cantidad' => $partida['cantidad'],
                    'estado' => $partida['estado'], // Se conserva el estado
                    'idtablaref' => 0, // Referencia de tabla en caso necesario
                ];

                // Insertar o actualizar partida
                if ($partida['iddb'] == 0) {
                    $database->insert('almacenes_movimientos_productos', $data);
                    $idpartida = $database->id();
                } else {
                    $database->update('almacenes_movimientos_productos', $data, ['id' => $partida['iddb']]);
                    $idpartida = $partida['iddb'];
                }

                // Verificar si el producto usa lotes
                $tieneLotes = $database->get("productos", "lote_serie", ["id" => $producto['idproducto']]);

                if ($tieneLotes == 'Sí') {
                    $naturalezaMovimiento = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $formulario['idconcepto']]);
                    if (isset($partida['lotes']) && is_array($partida['lotes'])) {
                        if ($naturalezaMovimiento == 'Salida') {
                            // Procesar los lotes utilizados en la salida
                            foreach ($partida['lotes'] as $lote) {
                                // Omitir si el lote es nuevo y no tiene cantidad o está inactivo
                                if ($lote['iddb'] == 0 && ($lote['cantidad'] == 0 || $lote['estado'] == 'Inactivo')) {
                                    continue;
                                }
                                $loteDB = $database->get("almacenes_productos_lotes", "*", ["id" => $lote['iddbapl']]);
                                $loteData = [
                                    'idmovimiento' => $idmovimiento,
                                    'idmovimiento_producto' => $idpartida,
                                    'idalmacen_producto_lote' => $lote['iddbapl'],
                                    'idproducto' => $producto['idproducto'],
                                    'cantidad' => $lote['cantidad'],
                                    'estado' => $lote['estado'],
                                    'lote' => $loteDB['lote'],
                                    'serie' => $loteDB['serie'],
                                    'pedimento' => $loteDB['pedimento'],
                                    'caducidad' => $loteDB['caducidad'],
                                    'fabricacion' => $loteDB['fabricacion'],
                                    'idtablaref' => 0, // Referencia de tabla en caso necesario
                                ];
                                // Si el lote no tiene id en la BD, hacer un INSERT; si ya existe, hacer un UPDATE
                                if ($lote['iddb'] == 0) {
                                    $database->insert('almacenes_movimientos_productos_lotes', $loteData);
                                } else {
                                    $database->update('almacenes_movimientos_productos_lotes', $loteData, ['id' => $lote['iddb']]);
                                }
                            }
                        }
                        elseif($naturalezaMovimiento == 'Entrada')
                        {
                            if($accion=="procesar")
                            {
                                // Procesar los lotes utilizados en la entrada
                                foreach ($partida['lotes'] as $lote) {
                                    // Omitir si el lote es nuevo y no tiene cantidad o está inactivo
                                    if ($lote['iddb'] == 0 && ($lote['cantidad'] == 0 || $lote['estado'] == 'Inactivo')) {
                                        continue;
                                    }
                                    $almacenes_productos_lote = $database->get("almacenes_productos_lotes", "*", [
                                        "idproducto" => $producto['idproducto'],
                                        "idalmacen"  => $formulario['idalmacen'],
                                        "lote"       => $lote['lote'],
                                        "serie"      => $lote['serie']
                                    ]);
                                    if ($almacenes_productos_lote) {
                                        //si existe el lote, por lo que se se obtiene el idlote desde la base de datos
                                        $idalmacene_producto_lote = $almacenes_productos_lote['id'];
                                    }
                                    else
                                    {
                                        //se registra el lote en la tabla
                                        $loteData = [
                                            'idproducto' => $producto['idproducto'],
                                            'idalmacen' => $formulario['idalmacen'],
                                            'lote' => $lote['lote'],
                                            'serie' => $lote['serie'],
                                            'pedimento' => $lote['pedimento'],
                                            'caducidad' => $lote['caducidad'],
                                            'fabricacion' => $lote['fabricacion'],
                                            'existencia' => 0
                                        ];
                                        $database->insert('almacenes_movimientos_productos_lotes', $loteData);
                                        $idalmacene_producto_lote = $database->id();
                                        $almacenes_productos_lote = $database->get("almacenes_productos_lotes", "*", ["id" => $idalmacene_producto_lote]);
                                    }
                                    $loteDB = $database->get("almacenes_productos_lotes", "*", ["id" => $lote['iddbapl']]);
                                    $loteData = [
                                        'idmovimiento' => $idmovimiento,
                                        'idmovimiento_producto' => $idpartida,
                                        'idalmacen_producto_lote' => $idalmacene_producto_lote,
                                        'idproducto' => $almacenes_productos_lote['idproducto'],
                                        'cantidad' => $lote['cantidad'],
                                        'estado' => $lote['estado'],
                                        'lote' => $almacenes_productos_lote['lote'],
                                        'serie' => $almacenes_productos_lote['serie'],
                                        'pedimento' => $almacenes_productos_lote['pedimento'],
                                        'caducidad' => $almacenes_productos_lote['caducidad'],
                                        'fabricacion' => $almacenes_productos_lote['fabricacion'],
                                        'idtablaref' => 0, // Referencia de tabla en caso necesario
                                    ];
                                    // Si el lote no tiene id en la BD, hacer un INSERT; si ya existe, hacer un UPDATE
                                    if ($lote['iddb'] == 0) {
                                        $database->insert('almacenes_movimientos_productos_lotes', $loteData);
                                    } else {
                                        $database->update('almacenes_movimientos_productos_lotes', $loteData, ['id' => $lote['iddb']]);
                                    }
                                }
                            }
                            elseif($accion=="borrador")
                            {
                                // Procesar los lotes utilizados en la entrada
                                foreach ($partida['lotes'] as $lote) {
                                    // Omitir si el lote es nuevo y no tiene cantidad o está inactivo
                                    if ($lote['iddb'] == 0 && ($lote['cantidad'] == 0 || $lote['estado'] == 'Inactivo')) {
                                        continue;
                                    }
                                    $loteDB = $database->get("almacenes_productos_lotes", "*", ["id" => $lote['iddbapl']]);
                                    $loteData = [
                                        'idmovimiento' => $idmovimiento,
                                        'idmovimiento_producto' => $idpartida,
                                        'idalmacen_producto_lote' => 0,
                                        'idproducto' => $producto['idproducto'],
                                        'cantidad' => $lote['cantidad'],
                                        'estado' => $lote['estado'],
                                        'lote' => $lote['lote'],
                                        'serie' => $lote['serie'],
                                        'pedimento' => $lote['pedimento'],
                                        'caducidad' => $lote['caducidad'],
                                        'fabricacion' => $lote['fabricacion'],
                                        'idtablaref' => 0, // Referencia de tabla en caso necesario
                                    ];
                                    // Si el lote no tiene id en la BD, hacer un INSERT; si ya existe, hacer un UPDATE
                                    if ($lote['iddb'] == 0) {
                                        $database->insert('almacenes_movimientos_productos_lotes', $loteData);
                                    } else {
                                        $database->update('almacenes_movimientos_productos_lotes', $loteData, ['id' => $lote['iddb']]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->response;
    }*/
    
}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












