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

    function modalSeleccionarSocio()
    {
        $this->modalSeleccionServerSide('global', 'socios', '', 0, 'Activos', 'Modal', 'JaxonalmacenMovimientos.cargarSocio', false, '', 'Selecciona Un Socio');
        return $this->response;
    }

    function cargarSocio($form)
    {
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

        $this->response->assign("idsocio2", "value", $form['seleccion']);
        $this->response->assign("iddireccion_origen", "value", "");
        $this->response->assign("iddireccion_destino", "value", "");
        $this->response->assign("detalleDireccionOrigen", "value", "");
        $this->response->assign("detalleDireccionDestino", "value", "");
        $this->response->assign("iddireccion_origen", "innerHTML", getDirecciones($form['seleccion']));
        $this->response->assign("iddireccion_destino", "innerHTML", getDirecciones($form['seleccion']));
        return $this->response;
    }

    function cambiarDireccion($idDireccion, $tipoDireccion)
    {
        global $database;
        $direccion = $database->get("direcciones", "*", ["id" => $idDireccion]);
        $colonia = $database->get("cfdi_colonia", "*", ["id" => $direccion['idc_colonia']]);
        $municipio = $database->get("cfdi_municipio", "*", ["id" => $direccion['idc_municipio']]);
        $estado = $database->get("cfdi_estado", "*", ["id" => $direccion['idc_estado']]);

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
        $this->deshabilitaSelect('idalmacen');
        $this->response->assign("cambiarAlmacen", "disabled", "");
        return $this->response;
    }

    function deshabilitaConcepto()
    {
        $this->response->assign("cambiarConcepto", "disabled", "");
        $this->deshabilitaSelect('idconcepto');
        return $this->response;
    }

    function modalConfirmacionHabilitaAlmacen()
    {
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de almacén se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonalmacenMovimientos.habilitaAlmacen();");
        return $this->response;
    }

    function modalConfirmacionHabilitaConcepto()
    {
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de concepto se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonalmacenMovimientos.habilitaConcepto();");
        return $this->response;
    }

    function habilitaAlmacen()
    {
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
        $this->habilitaSelect('idconcepto');
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idconcepto").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idconcepto").val("").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idconcepto").onchange = function() {
                JaxonalmacenMovimientos.deshabilitaAlmacen();
            };
        ');
        $this->response->assign("cambiarConcepto", "disabled", "disabled");
        $this->desactivarTodasLasPartidas();
        return $this->response;
    }

    function desactivarTodasLasPartidas()
    {
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
        if(empty($form['idconcepto']))
        {
            $this->alerta("Invalido", "Primero elije un concepto", "error", "idconcepto");
        }
        elseif(empty($form['idalmacen']))
        {
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
                $this->modalSeleccionServerSide('almacén', 'inventario', '', $form['idalmacen'], 'Con Existencia', 'Modal', 'JaxonalmacenMovimientos.addProductos', true, '', 'Seleccionar Productos');
            }
        }
        return $this->response;
    }

    function addProductos($form)
    {
        // Verifica si el array 'seleccion' existe y no está vacío
        if (isset($form['seleccion']) && is_array($form['seleccion'])) {
            foreach ($form['seleccion'] as $idalmacenes_productos) {
                // Agrega un nuevo array a la sesión con el idalmacenes_productos correspondiente
                $_SESSION['partidas' . $_GET['rand']][] = [
                    'iddb' => 0,
                    'idalmacenes_productos' => $idalmacenes_productos,
                    'cantidad' => 0,
                    'estado' => 'Activo',
                    'lotes' => [
                        // Ejemplo de lote predefinido, puedes agregar más lotes o dejar este array vacío según sea necesario
                        [
                            'iddb' => 0,
                            'lote' => 'a',
                            'serie' => 'b',
                            'pedimento' => 'c',
                            'fabricacion' => '2024-02-13',
                            'caducidad' => '2027-06-30',
                            'cantidad' => 0,
                            'estado' => 'Activo'
                        ],
                        [
                            'iddb' => 0,
                            'lote' => 'b',
                            'serie' => 'b',
                            'pedimento' => 'c',
                            'fabricacion' => '2024-02-13',
                            'caducidad' => '2027-06-30',
                            'cantidad' => 0,
                            'estado' => 'Activo'
                        ],
                        [
                            'iddb' => 0,
                            'lote' => 'c',
                            'serie' => 'b',
                            'pedimento' => 'c',
                            'fabricacion' => '2024-02-13',
                            'caducidad' => '2027-06-30',
                            'cantidad' => 0,
                            'estado' => 'Activo'
                        ]
                    ]
                ];
            }
        }
        $this->tablaPartidas();
        return $this->response;
    }

    function tablaPartidas()
    {
        // Iniciar la respuesta de script
        $script = "tablaPartidas.clear();"; // Limpiar la tabla
        $i = 1;

        // Verificar si el índice de sesión existe para evitar errores
        if (!isset($_SESSION['partidas' . $_GET['rand']])) {
            $this->response->script("console.error('No se encontraron partidas.');");
            return $this->response;
        }

        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {
            if ($partida['estado'] == 'Activo') {
                global $database;
                
                // Obtener datos necesarios de la base de datos
                $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $partida['idalmacenes_productos']]);
                $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
                $cfdi_claveunidad = $database->get("cfdi_claveunidad", "*", ["id" => $producto['idc_claveunidad']]);

                // Construcción de la fila de la tabla
                $fila = [
                    $i,
                    htmlspecialchars($producto['codigo_barras']),
                    htmlspecialchars($producto['nombre']),
                    htmlspecialchars($producto['descripcion']),
                    htmlspecialchars($cfdi_claveunidad['nombre']),
                    htmlspecialchars($almacenes_producto['existencia']),
                    "<input type='number' class='form-control cantidad-input' value='" . htmlspecialchars($partida['cantidad']) . "' 
                        data-idpartida='" . htmlspecialchars($partida['iddb']) . "' min='0' 
                        onfocus='JaxonalmacenMovimientos.validaSiTieneLote($index, jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"))'>",
                    "<button type='button' class='btn btn-sm btn-danger' title='Eliminar' 
                        onclick='JaxonalmacenMovimientos.desactivarPartida($index)'>
                        <i class='bi bi-trash'></i>
                    </button>"
                ];

                // Convertir la fila a formato JavaScript
                $filaJS = json_encode($fila);
                $script .= "tablaPartidas.row.add($filaJS);";  // Agregar la fila
                $i++;
            }
        }

        // Dibujar la tabla con los nuevos datos
        $script .= "tablaPartidas.draw();";

        // Agregar el script a la respuesta de Jaxon
        $this->response->script($script);

        return $this->response;
    }

    function validaSiTieneLote($indiceDelArreglo, $form)
    {
        global $database;
        $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);
        $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
        if($producto['lote_serie']=='Sí')
        {
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
}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












