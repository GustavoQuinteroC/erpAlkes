<?php
// controlador.php
require_once (__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class ventasFacturas extends alkesGlobal
{
    function inializarFormulario()
    {
        $this->response->assign('fechahora', 'value', date('Y-m-d\TH:i'));
        return $this->response;
    }

    function modalSeleccionarSocio()
    {
        $this->modalSeleccionServerSide('global', 'socios', '', 0, 'Activos', 'Modal', 'JaxonventasFacturas.cargarSocio', false, '', 'Selecciona Un Socio');
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
                JaxonventasFacturas.cargarSocio(this.value);
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
                $this->modalSeleccionServerSide('almacén', 'inventario', '', $form['idalmacen'], 'Principal', 'Modal', 'JaxonventasFacturas.addProductos', true, '', 'Seleccionar Productos');
            }
            elseif($naturaleza=='Salida')
            {
                $this->modalSeleccionServerSide('almacén', 'inventario', '', $form['idalmacen'], 'Con Existencia', 'Modal', 'JaxonventasFacturas.addProductos', true, '', 'Seleccionar Productos');
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
                    'lotes' => array()
                ];
            }
        }
        $this->tablaPartidas();
        return $this->response;
    }

    function tablaPartidas()
    {
        // Asignar los datos a la tabla usando JavaScript
        $script = "
            tablaPartidas.clear(); // Limpiar la tabla
        ";
        $i = 1;
        foreach ($_SESSION['partidas' . $_GET['rand']] as $index => $partida) {
            if ($partida['estado'] == 'Activo') {
                global $database;
                $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $partida['idalmacenes_productos']]);
                $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
                $cfdi_claveunidad = $database->get("cfdi_claveunidad", "*", ["id" => $producto['idc_claveunidad']]);

                $script .= "
                    tablaPartidas.row.add([
                        " . $i . ", // Número iterador
                        '" . $producto['codigo_barras'] . "',
                        '" . $producto['nombre'] . "',
                        '" . $producto['descripcion'] . "',
                        '" . $cfdi_claveunidad['nombre'] . "',
                        '" . $almacenes_producto['existencia'] . "',
                        `<input type='number' class='form-control cantidad-input' value='" . $partida['cantidad'] . "' data-idpartida='" . $partida['iddb'] . "' min='0' onfocus='JaxonventasFacturas.validaLoteSerieKit($index, jaxon.getFormValues('formulario".$_GET['rand']."'))'>`,
                        `<button type='button' class='btn btn-sm btn-danger' title='Eliminar' onclick='JaxonventasFacturas.desactivarPartida($index)'>
                            <i class='bi bi-trash'></i>
                        </button>`
                    ]);
                ";
                $i++;
            }
        }
        $script .= "
            tablaPartidas.draw(); // Dibujar la tabla con los nuevos datos
        ";

        // Agregar el script a la respuesta de Jaxon
        $this->response->script($script);

        return $this->response;
    }

    function validaLoteSerieKit($indiceDelArreglo, $form)
    {
        global $database;
        $almacenes_producto = $database->get("almacenes_productos", "*", ["id" => $_SESSION['partidas' . $_GET['rand']][$indiceDelArreglo]['idalmacenes_productos']]);
        $producto = $database->get("productos", "*", ["id" => $almacenes_producto['idproducto']]);
        if($producto['lote_serie']=='Sí')
        {
            $naturalezaConcepto = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            if($naturalezaConcepto=='Salida')
            {

            }
            elseif($naturalezaConcepto=='Entrada')
            {

            }
        }
        elseif($producto['kit']=='Sí')
        {
            $naturalezaConcepto = $database->get("almacenes_movimientos_conceptos", "naturaleza", ["id" => $form['idconcepto']]);
            if($naturalezaConcepto=='Salida')
            {

            }
            elseif($naturalezaConcepto=='Entrada')
            {
                
            }
        }
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
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de almacén se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonventasFacturas.habilitaAlmacen();");
        return $this->response;
    }

    function modalConfirmacionHabilitaConcepto()
    {
        $this->alertaConfirmacion("¡CUIDADO!", "Si habilita el cambio de concepto se borraran todas las partidas ya agregadas ¿Desea continuar?", "warning", "JaxonventasFacturas.habilitaConcepto();");
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
                JaxonventasFacturas.deshabilitaAlmacen();
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
                JaxonventasFacturas.deshabilitaAlmacen();
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

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, ventasFacturas::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












