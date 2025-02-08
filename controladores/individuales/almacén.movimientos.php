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
            $mensaje = "Algunos productos ya existían en las partidas y no se agregaron:<br>" . implode("<br>", $productosDuplicados);
            $this->alerta("Productos duplicados", $mensaje, "warning");
        } elseif ($productosAgregados) {
            
        } else {
            $this->alerta("Sin cambios", "No se agregó ningún producto.", "info");
        }

        return $this->response;
    }

    function tablaPartidas()
    {
        $script = "tablaPartidas.clear();"; // Limpiar la tabla
        $i = 1;

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
                        onfocus='JaxonalmacenMovimientos.validaSiTieneLote($index, jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"))' 
                        onblur='JaxonalmacenMovimientos.validaCantidadPartida($index, jaxon.getFormValues(\"formulario" . htmlspecialchars($_GET['rand']) . "\"), this.value)'>",
                    "<button type='button' class='btn btn-sm btn-danger' title='Eliminar' 
                        onclick='JaxonalmacenMovimientos.desactivarPartida($index)'>
                        <i class='bi bi-trash'></i>
                    </button>"
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

    function validaCantidadPartida($indiceDelArreglo, $form, $cantidadIntentada)
    {
        if (preg_match('/^\d{1,12}(\.\d{1,4})?$/', $cantidadIntentada)) {
            // La cantidad es válida
            global $database;
            $naturalezaConcepto = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            
            if ($naturalezaConcepto == 'Salida') {
                $existenciaRealActual = $database->get("almacenes_productos", "existencia", ["id" => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);
                
                // Validar que no exceda la existencia del almacén seleccionado
                if ($cantidadIntentada > $existenciaRealActual) {
                    $this->resetCantidadPartidaYLotes($indiceDelArreglo);
                    $this->alerta(
                        "Cantidad inválida",
                        "La cantidad ingresada excede la existencia actual en el almacén seleccionado. Todas las cantidades de esta partida, incluyendo sus lotes o series, han sido reiniciadas a 0.",
                        "error"
                    );
                }
                else
                {
                    $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['cantidad']=$cantidadIntentada;
                }
            }
            else
                {
                    $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['cantidad']=$cantidadIntentada;
                }
        } else {
            // La cantidad es inválida (no cumple con el formato de 12 dígitos enteros y 4 decimales)
            $this->resetCantidadPartidaYLotes($indiceDelArreglo);
            $this->alerta(
                "Formato de cantidad inválido",
                "La cantidad ingresada no es válida. Solo se permiten hasta 12 dígitos enteros y 4 decimales. Todas las cantidades de esta partida, incluyendo sus lotes o series, han sido reiniciadas a 0.",
                "error"
            );
        }
        // Muestra la tabla de partidas actualizada
        $this->tablaPartidas();
        return $this->response;
    }

    function resetCantidadPartidaYLotes($indiceDelArreglo)
    {
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

    function modalSeleccionLotes($indiceDelArreglo)
    {
        // Crear el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalSeleccionLotes" tabindex="-1" aria-labelledby="modalSeleccionLotesLabel">';
        $html .= '<div class="modal-dialog modal-xl" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . ' d-flex justify-content-between align-items-center">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLotesLabel">Selección de Lotes</h5>';
        $html .= '<div class="d-flex align-items-center gap-2">';
        $html .= '<button tabindex="400" id="addLote" name="addLote" class="btn btn-sm border ' . getTextColor() . ' bg-transparent" onclick="JaxonalmacenMovimientos.agregarFilaLotes(' . $indiceDelArreglo . ');" type="button">';
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

    function generarTablaLotes($indiceDelArreglo)
    {
        // Obtener los lotes activos de la sesión
        $lotes = $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] ?? [];
        $lotesActivos = array_filter($lotes, function ($lote) {
            return $lote['estado'] == 'Activo';
        });

        $html = '';
        foreach ($lotesActivos as $index => $lote) {
            $html .= '<tr>';
            $html .= '<td><input type="text" class="form-control" id="lote_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['lote'] ?? '') . '" onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'lote\')"></td>';
            $html .= '<td><input type="text" class="form-control" id="serie_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['serie'] ?? '') . '" onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'serie\')"></td>';
            $html .= '<td><input type="text" class="form-control" id="pedimento_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['pedimento'] ?? '') . '" onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'pedimento\')"></td>';
            $html .= '<td><input type="date" class="form-control" id="fabricacion_' . $indiceDelArreglo . '_' . $index . '" value="' . (!empty($lote['fabricacion']) ? date('Y-m-d', strtotime($lote['fabricacion'])) : '') . '" onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'fabricacion\')"></td>';
            $html .= '<td><input type="date" class="form-control" id="caducidad_' . $indiceDelArreglo . '_' . $index . '" value="' . (!empty($lote['caducidad']) ? date('Y-m-d', strtotime($lote['caducidad'])) : '') . '"onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'caducidad\')"></td>';
            $html .= '<td><input type="number" class="form-control" id="cantidad_' . $indiceDelArreglo . '_' . $index . '" value="' . htmlspecialchars($lote['cantidad'] ?? 0) . '"onblur="JaxonalmacenMovimientos.guardaDatoLotes(this.value, ' . $indiceDelArreglo . ', ' . $index . ', \'cantidad\')"></td>';
            $html .= '<td><button type="button" class="btn btn-danger" onclick="JaxonalmacenMovimientos.eliminarFilaLotes(' . $indiceDelArreglo . ', ' . $index . ')"><i class="bi bi-trash"></i></button></td>';
            $html .= '</tr>';
        }
        $this->response->assign("tablaLotesBody", "innerHTML", $html);
        return $this->response;
    }

    function validaDatosLote($indicePartida, $indiceLote)
    {
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
            return [
                'error' => true,
                'mensaje' => 'La cantidad no puede ser 0 o negativa. Se ha ajustado a 1.'
            ];
        }

        // Verificar que la fecha de fabricación no sea posterior al día de hoy
        if (!empty($loteActual['fabricacion']) && strtotime($loteActual['fabricacion']) > time()) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = date('Y-m-d');
            return [
                'error' => true,
                'mensaje' => 'La fecha de fabricación no puede ser posterior al día de hoy. Se ha ajustado a la fecha actual.'
            ];
        }

        // Verificar que el campo "lote" no exceda 254 caracteres
        if (strlen($loteActual['lote']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['lote'] = substr($loteActual['lote'], 0, 254);
            return ['error' => true, 'mensaje' => 'El campo Lote no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que el campo "serie" no exceda 254 caracteres
        if (strlen($loteActual['serie']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['serie'] = substr($loteActual['serie'], 0, 254);
            return ['error' => true, 'mensaje' => 'El campo Serie no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que el campo "pedimento" no exceda 254 caracteres
        if (strlen($loteActual['pedimento']) > 254) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['pedimento'] = substr($loteActual['pedimento'], 0, 254);
            return ['error' => true, 'mensaje' => 'El campo Pedimento no puede exceder 254 caracteres, se ha ajustado a 254 caracteres.'];
        }

        // Verificar que la fecha de fabricación sea válida (formato: YYYY-MM-DD)
        if (!empty($loteActual['fabricacion']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loteActual['fabricacion'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['fabricacion'] = '0000-00-00';
            return ['error' => true, 'mensaje' => 'La fecha de fabricación no es válida. Se ha ajustado a "0000-00-00".'];
        }

        // Verificar que la fecha de caducidad sea válida (formato: YYYY-MM-DD)
        if (!empty($loteActual['caducidad']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loteActual['caducidad'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['caducidad'] = '0000-00-00';
            return ['error' => true, 'mensaje' => 'La fecha de caducidad no es válida. Se ha ajustado a "0000-00-00".'];
        }

        // Verificar que la cantidad sea un número válido (hasta 12 dígitos y 4 decimales)
        if (!preg_match('/^\d{1,12}(\.\d{1,4})?$/', $loteActual['cantidad'])) {
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote]['cantidad'] = 1;
            return ['error' => true, 'mensaje' => 'La cantidad no es válida. Se ha ajustado a 1.'];
        }

        return ['error' => false];
    }

    function guardaDatoLotes($valor, $indicePartida, $indiceLote, $campo)
    {
        // Validar que el índice de la partida y el lote existan en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote])) {
            // Actualizar el campo correspondiente en el array de la sesión
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote][$campo] = $valor;

            // Llamar a validaDatosLote para realizar las verificaciones adicionales
            $resultadoValidacion = $this->validaDatosLote($indicePartida, $indiceLote);

            if ($resultadoValidacion['error']) {
                // Si hay un error, mostrar un mensaje de alerta
                $this->alerta("Error en la validación", $resultadoValidacion['mensaje'], "error");
            }
            else
            {
                if($campo=='lote' or $campo=='serie')
                {
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
                        $this->alerta("Lote y/o serie encontrados", "Se han llenado automaticamente los campos restantes", "success", null, false, true);
                    }
                }
            }

            // Redibujar la tabla solo con los lotes actualizados
            $this->generarTablaLotes($indicePartida);
        } else {
            // En caso de que el lote no exista, devolver un mensaje de error
            $this->alerta("Error interno", "El lote especificado no existe, favor de comunicar este error con el administrador del sistema.", "error");
        }

        return $this->response;
    }

    function eliminarFilaLotes($indiceDelArreglo, $indiceLote)
    {
        // Verificar que el índice del lote exista
        if (isset($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'][$indiceLote])) {
            // Marcar el lote como 'Inactivo'
            $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'][$indiceLote]['estado'] = 'Inactivo';
        }

        // Redibujar solo el cuerpo de la tabla de lotes
        $this->generarTablaLotes($indiceDelArreglo);

        return $this->response;
    }

    function agregarFilaLotes($indiceDelArreglo)
    {
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
        $errores = [];  // Array para acumular mensajes de alerta
        $i=0;
        $j=0;
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
        // Verificar que la partida exista en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo])) {
            $lotes = $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['lotes'] ?? [];

            // Sumar las cantidades de todos los lotes activos
            $cantidadTotal = array_reduce($lotes, function ($carry, $lote) {
                return $carry + ($lote['estado'] == 'Activo' ? (int)$lote['cantidad'] : 0);
            }, 0);

            // Actualizar la cantidad en la partida
            $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['cantidad'] = $cantidadTotal;

            // Redibujar la tabla para reflejar el cambio si es necesario
            $this->generarTablaLotes($indiceDelArreglo);

            //validar que la cantidad cumpla con todos los requisitos
            $this->response->script("JaxonalmacenMovimientos.validaCantidadPartida($indiceDelArreglo, jaxon.getFormValues('formulario" . $_GET['rand'] . "'), $cantidadTotal);");
        } else {
            // En caso de que la partida no exista, devolver un mensaje de error
            $this->alerta("Error interno", "La partida especificada no existe. Por favor, comuníquese con el administrador del sistema.", "error");
        }
    }

}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












