<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenAlmacenes extends alkesGlobal
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
                $this->response->assign("nombre", "value", $almacen['nombre']);
                $this->response->assign("direccion", "value", $almacen['direccion']);
                $this->response->assign("capacidad", "value", $almacen['capacidad_m3']);
                $this->response->assign("estado", "value", $almacen['estado']);
                $this->response->assign("principal", "value", $almacen['principal']);
                $this->response->assign("consigna", "value", $almacen['consigna']);

                // Actualizar select2
                $this->response->script('
                    $("#idsucursal").val("' . $almacen['idsucursal'] . '").trigger("change");
                    $("#idusuario").val("' . $almacen['idusuario_encargado'] . '").trigger("change");
                ');

                $this->cargarProductosConsulta();
            }
        }
        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' type='button' id='btnguardar' name='btnguardar' onclick='JaxonalmacenAlmacenes.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                <i class='bi bi-save'></i> Guardar
            </button>
        ");
        return $this->response;
    }

    function cargarProductosConsulta()
    {
        global $database;
        // Consulta select que nos regresa toda la informacion de todos los oproductos asociados al almacén
        $productos = $database->select("almacenes_productos", "*", ["idalmacen" => $_GET['id']]);
        // Verificar si se obtuvieron productos
        if (empty($productos)) {
            $this->alerta("Advertencia", "No se encontraron productos asociados al almacén.", "info");
        } else {
            // Agrega los productos al array de sesión
            foreach ($productos as $idproducto) {
                $_SESSION['partidas' . $_GET['rand']][] = [
                    'iddb' => $idproducto['id'],
                    'idproducto' => $idproducto['idproducto'],
                    'existencia' => $idproducto['existencia'],
                    'ubicacion' => $idproducto['ubicacion'],
                    'estado' => $idproducto['estado'],
                ];
            }
            $this->tablaProductos();
        }
        return $this->response;
    }

    function asignarTodos()
    {
        global $database;

        // Obtén los IDs ya existentes en el array de sesión para excluirlos
        $excluir_ids = array_column($_SESSION['partidas' . $_GET['rand']], 'idproducto');

        // Condición base para la consulta
        $conditions = [
            "idempresa" => $_SESSION['idempresa']
        ];

        // Agrega la condición para excluir IDs solo si el array no está vacío
        if (!empty($excluir_ids)) {
            $conditions["id[!]"] = $excluir_ids;
        }

        // Ejecuta la consulta usando el método select
        $productos = $database->select("productos", "id", [
            "AND" => $conditions
        ]);

        // Agrega los nuevos productos al array de sesión
        foreach ($productos as $idproducto) {
            $_SESSION['partidas' . $_GET['rand']][] = [
                'iddb' => 0,
                'idproducto' => $idproducto,
                'existencia' => 0,
                'ubicacion' => '',
                'estado' => 'Activo',
            ];
        }
        $this->tablaProductos();
        return $this->response;
    }

    function modalAddProducto()
    {
        global $database;
        $this->modalSeleccionServerSide('almacén', 'productos', '', 'Principal', 'Modal', 'JaxonalmacenAlmacenes.addProductos', true, '', 'Seleccionar Productos');
        return $this->response;
    }

    function addProductos($form)
    {
        // Itera sobre los productos seleccionados
        $existian = false;
        foreach ($form['seleccion'] as $idproducto) {
            // Verifica si el producto ya existe en la sesión
            $existe = false;
            foreach ($_SESSION['partidas' . $_GET['rand']] as $producto) {
                if ($producto['idproducto'] == $idproducto) {
                    $existe = true;
                    $existian = true;
                    break;
                }
            }

            // Solo agrega el producto si no existe
            if (!$existe) {
                $_SESSION['partidas' . $_GET['rand']][] = [
                    'iddb' => 0,
                    'idproducto' => $idproducto,
                    'existencia' => 0,
                    'ubicacion' => '',
                    'estado' => 'Activo',
                ];
            }
        }
        if ($existian) {
            $this->alerta("Precaución", "Algunos elementos que se intentaron agregar ya existian y no fueron agregados", "warning");
        }
        $this->tablaProductos();
        return $this->response;
    }


    function tablaProductos()
    {
        $productos = $_SESSION['partidas' . $_GET['rand']] ?? [];
        global $database;

        // Verificar si hay productos para mostrar
        if (empty($productos)) {
            $html = '<p class="text-muted text-center">No hay productos registrados.</p>';
        } else {
            // Obtener IDs de los productos del array de sesión
            $idproductos = array_column($productos, 'idproducto');

            // Realizar la consulta con LEFT JOIN
            $infoProductos = $database->select("productos(p)", [
                "[>]categorias(c)" => ["p.idcategoria" => "id"],
                "[>]subcategorias(sc)" => ["p.idsubcategoria" => "id"],
                "[>]subsubcategorias(ssc)" => ["p.idsubsubcategoria" => "id"]
            ], [
                "p.codigo_barras",
                "p.nombre",
                "p.marca",
                "p.precio",
                "p.estado",
                "c.nombre(categoria)",
                "sc.nombre(subcategoria)",
                "ssc.nombre(subsubcategoria)",
                "p.id(idproducto)"
            ], [
                "p.id" => $idproductos
            ]);

            // Construir la tabla
            $html = '<div class="table-responsive">'; // Contenedor responsivo
            $html .= '<table class="table table-borderless table-striped table-hover">';
            $html .= '<thead class="text-bg-secondary">';
            $html .= '<tr>';
            $html .= '<th>Código de barras</th>';
            $html .= '<th>Nombre</th>';
            $html .= '<th>Marca</th>';
            $html .= '<th>Categoría</th>';
            $html .= '<th>Subcategoría</th>';
            $html .= '<th>Subsubcategoría</th>';
            $html .= '<th>Precio</th>';
            $html .= '<th>Estado</th>';
            $html .= '<th>Existencia</th>';
            $html .= '<th>Ubicación</th>';
            $html .= '<th>Acciones</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($productos as $index => $producto) {
                // Buscar los datos del producto en el resultado de la consulta
                $infoProducto = array_filter($infoProductos, function ($p) use ($producto) {
                    return $p['idproducto'] == $producto['idproducto'];
                });
                $infoProducto = reset($infoProducto); // Obtener el primer resultado coincidente

                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($infoProducto['codigo_barras']) . '</td>';
                $html .= '<td>' . htmlspecialchars($infoProducto['nombre']) . '</td>';
                $html .= '<td>' . htmlspecialchars($infoProducto['marca']) . '</td>';
                $html .= '<td>' . htmlspecialchars($infoProducto['categoria'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($infoProducto['subcategoria'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($infoProducto['subsubcategoria'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars(number_format($infoProducto['precio'], 2)) . '</td>';
                $html .= '<td>' . htmlspecialchars($producto['estado']) . '</td>';
                $html .= '<td>' . htmlspecialchars($producto['existencia']) . '</td>';
                $html .= '<td>';
                $html .= '<input type="text" class="form-control form-control-sm" value="' . $producto['ubicacion'] . '" ';
                $html .= 'onchange="JaxonalmacenAlmacenes.actualizarUbicacion(this.value, ' . $index . ');" />';
                $html .= '</td>';
                $html .= '<td>';
                // Botón según el estado del producto
                if ($producto['estado'] == 'Activo') {
                    // Mostrar X si está Activo
                    $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="JaxonalmacenAlmacenes.desactivarProducto(' . $index . ');">';
                    $html .= '<i class="bi bi-trash"></i>'; // X de eliminación
                    $html .= '</button>';
                } elseif ($producto['estado'] == 'Inactivo') {
                    // Mostrar palomita si está Inactivo
                    $html .= '<button type="button" class="btn btn-sm btn-success" onclick="JaxonalmacenAlmacenes.activarProducto(' . $index . ');">';
                    $html .= '<i class="bi bi-check"></i>'; // Palomita
                    $html .= '</button>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>'; // Cierre del contenedor responsivo
        }

        // Asignar el HTML generado al contenedor en el card-body
        $this->response->assign("tablaProductos", "innerHTML", $html);
        return $this->response;
    }

    function activarProducto($indice)
    {
        $_SESSION['partidas' . $_GET['rand']][$indice]["estado"] = "Activo";
        $this->tablaProductos();
        return $this->response;
    }

    function desactivarProducto($indice)
    {
        $_SESSION['partidas' . $_GET['rand']][$indice]["estado"] = "Inactivo";
        $this->tablaProductos();
        return $this->response;
    }

    function actualizarUbicacion($valor, $indice)
    {
        $_SESSION['partidas' . $_GET['rand']][$indice]["ubicacion"] = $valor;
        $this->tablaProductos();
        return $this->response;
    }

    function alertaCambioPrincipal($valor)
    {
        if ($valor == "Sí") {
            $this->alerta("Alerta importante", "Si deja este almacén como principal, el almacén principal actual perdera este atributo", "warning");
        }
        return $this->response;
    }

    function validar($form)
    {
        // Definir las reglas de validación
        $reglas = [
            'nombre' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'direccion' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'capacidad' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'estado' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 200],
            'idsucursal' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idusuario' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'principal' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 200],
            'consigna' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 200],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);
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
            if ($form['principal'] == 'Sí') {
                $rand = $_GET['rand']; // Obtener el valor dinámico
                $resultadoValidacionRepetidoRfc = verificaRegistroRepetido("sucursal", "almacenes", "nombre", $form['nombre'], $_GET['id']);
                if ($resultadoValidacionRepetidoRfc) {
                    // El registro está repetido, mostrar un error
                    $this->alerta('Error', 'Ya existe existe un almacen con este nombre en esta sucursal', 'error', 'nombre', true, false);
                    return $this->response;
                } else {
                    $this->alertaConfirmacion("Cambiar almacén principal", "¿Estas seguro que deseas cambiar el actual almacén principal por este?", "warning", "JaxonalmacenAlmacenes.guardar(jaxon.getFormValues(\"formulario{$rand}\"));");
                }
            } else {
                $resultadoValidacionRepetidoRfc = verificaRegistroRepetido("sucursal", "almacenes", "nombre", $form['nombre'], $_GET['id']);
                if ($resultadoValidacionRepetidoRfc) {
                    // El registro está repetido, mostrar un error
                    $this->alerta('Error', 'Ya existe existe un almacen con este nombre en esta sucursal', 'error', 'nombre', true, false);
                    return $this->response;
                } else {
                    $this->guardar($form);
                }
            }
        }
        return $this->response;
    }

    function guardar($form)
    {
        global $database;
        $this->response->assign("btnguardar", "disabled", "disabled"); //Deshabilitar boton de guardar para evitar que el usuario de click varias veces
        $data = [
            'idempresa' => isset($_SESSION['idempresa']) ? $_SESSION['idempresa'] : 0,
            'idsucursal' => isset($form['idsucursal']) ? $form['idsucursal'] : 0,
            'idusuario_encargado' => isset($form['idusuario']) ? $form['idusuario'] : 0,
            'nombre' => isset($form['nombre']) ? $form['nombre'] : '',
            'direccion' => isset($form['direccion']) ? $form['direccion'] : '',
            'capacidad_m3' => isset($form['capacidad']) ? $form['capacidad'] : 0,
            'estado' => isset($form['estado']) ? $form['estado'] : 'Activo',
            'principal' => isset($form['principal']) ? $form['principal'] : "No",
            'consigna' => isset($form['consigna']) ? $form['consigna'] : "No"
        ];

        // Si el 'id' de la URL es 0, realizamos una inserción
        if ($_GET['id'] == 0) {
            try {
                // Realizamos la inserción
                $database->insert('almacenes', $data);
                $insert_id = $database->id();
                // Llamamos a la función guardarPartidas y pasamos el id del nuevo Almacén
                $this->guardarPartidas($insert_id); // Aquí pasamos el ID del nuevo Almacén
                $this->alerta(
                    "Exito",
                    "Almacén registrado correctamente.",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "Error al guardar",
                    "No se pudo registrar el almacén, por favor intente nuevamente o contacte con el administrador.",
                    "error"
                );
            }
        }
        // Si el 'id' no es 0, actualizamos el registro correspondiente
        else {
            try {
                // Realizamos la actualización
                $database->update('almacenes', $data, ['id' => $_GET['id']]);
                // Llamamos a la función guardarPartidas y pasamos el id del Almacén
                $this->guardarPartidas($_GET['id']); // Aquí pasamos el ID del Almacén actualizado
                $this->alerta(
                    "Exito",
                    "Almacén actualizado correctamente.",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "Error al actualizar",
                    "No se pudo actualizar el almacén, por favor intente nuevamente o contacte con el administrador.",
                    "error"
                );
            }
        }
        return $this->response;
    }


    function guardarPartidas($idalmacen)
    {
        global $database; // instancia de Medoo

        // Verificamos si la sesión contiene las partidas
        if (isset($_SESSION['partidas' . $_GET['rand']]) && is_array($_SESSION['partidas' . $_GET['rand']])) {
            // Iteramos sobre las partidas
            foreach ($_SESSION['partidas' . $_GET['rand']] as $partida) {
                // Si la partida tiene iddb igual a 0, significa que es un nuevo registro
                if ($partida['iddb'] == 0) {
                    // Verificamos si el impuesto está marcado como Inactivo
                    if ($partida['estado'] == 'Inactivo') {
                        // Si el impuesto está Inactivo, no insertamos la partida
                        continue;
                    }
                    // Si el impuesto no está Inactivo, insertamos la nueva partida
                    $data = [
                        'idalmacen' => $idalmacen, // ID del almacén desde la URL
                        'idproducto' => $partida['idproducto'],
                        'existencia' => $partida['existencia'],
                        'estado' => $partida['estado'],
                        'ubicacion' => $partida['ubicacion'],
                    ];
                    // Realizamos la inserción
                    $database->insert('almacenes_productos', $data);
                } else {
                    // Si iddb no es 0, significa que la partida ya existe, por lo que actualizamos
                    $data = [
                        'estado' => $partida['estado'],
                        'ubicacion' => $partida['ubicacion'],
                    ];
                    // Realizamos la actualización
                    $database->update('almacenes_productos', $data, ['id' => $partida['iddb']]);
                }
            }
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenAlmacenes::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












