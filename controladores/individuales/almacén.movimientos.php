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

    function modalSeleccionLotes($indiceDelArreglo)
    {
        global $database;

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

        // Eliminar el elemento si es que se cierra y ejecutar la función validarLotes
        $this->response->script('
            if (!$("#modalSeleccionLotes").data("evento-registrado")) {
                $("#modalSeleccionLotes").on("hidden.bs.modal", function () {
                    JaxonalmacenMovimientos.validarLotes(' . $indiceDelArreglo . ');
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

    function guardaDatoLotes($valor, $indicePartida, $indiceLote, $campo)
    {
        // Validar que el índice de la partida y el lote existan en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote])) {
            // Actualizar el campo correspondiente en el array de la sesión
            $_SESSION['partidas' . $_GET['rand']][$indicePartida]['lotes'][$indiceLote][$campo] = $valor;

            // Llamar a validaLote para realizar las verificaciones adicionales
            $resultadoValidacion = $this->validaLote($indicePartida, $indiceLote);

            if ($resultadoValidacion['error']) {
                // Si hay un error, mostrar un mensaje de alerta
                $this->alerta("Error en la validación de Lote", $resultadoValidacion['mensaje'], "error");
            }

            // Redibujar la tabla solo con los lotes actualizados
            $this->generarTablaLotes($indicePartida);
        } else {
            // En caso de que el lote no exista, devolver un mensaje de error
            $this->alerta("Error interno", "El lote especificado no existe, favor de comunicar este error con el administrador del sistema.", "error");
        }

        return $this->response;
    }
}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












