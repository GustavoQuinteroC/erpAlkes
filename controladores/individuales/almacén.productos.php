<?php
session_start();
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenProductos extends alkesGlobal
{
    function inializarFormulario()
    {
        global $database;
        if ($_GET['id'] != 0) {
            if (!validarEmpresaPorRegistro("productos", $_GET['id'])) {
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            }

            //consultas a la bd para obtener los datos necesarios para la consulta del producto
            $producto = $database->get('productos', '*', ['id' => $_GET['id']]);
            $unidadSAT = $database->get('cfdi_claveprodserv', 'c_claveprodserv', ['id' => $producto['idc_claveprodserv']]);

            // Asignaciones a los campos
            $this->response->assign("idsubcategoria", "innerHTML", getSubcategorias($producto['idcategoria']));
            $this->response->assign("idsubsubcategoria", "innerHTML", getSubsubcategorias($producto['idsubcategoria']));
            $this->response->assign("smallTitulos", "innerHTML", $producto['nombre']);
            $this->response->assign("codigo_barras", "value", $producto['codigo_barras']);
            $this->response->assign("nombre", "value", $producto['nombre']);
            $this->response->assign("marca", "value", $producto['marca']);
            $this->response->assign("descripcion", "value", $producto['descripcion']);
            $this->response->assign("estado", "value", $producto['estado']);
            $this->response->assign("idtipo", "value", $producto['idtipo']);
            $this->response->assign("idcategoria", "value", $producto['idcategoria']);
            $this->response->assign("idsubcategoria", "value", $producto['idsubcategoria']);
            $this->response->assign("idsubsubcategoria", "value", $producto['idsubsubcategoria']);
            $this->response->assign("lote_serie", "value", $producto['lote_serie']);
            $this->response->assign("kit", "value", $producto['kit']);
            $this->response->assign("costo", "value", $producto['costo']);
            $this->response->assign("costo2", "value", $producto['costo2']);
            $this->response->assign("costo3", "value", $producto['costo3']);
            $this->response->assign("precio", "value", $producto['precio']);
            $this->response->assign("precio2", "value", $producto['precio2']);
            $this->response->assign("precio3", "value", $producto['precio3']);
            $this->response->assign("clave_producto_servicio", "value", $unidadSAT);

            // Actualizar select2
            $this->response->script('
                $("#idc_claveunidad").val("' . $producto['idc_claveunidad'] . '").trigger("change");
                $("#idc_moneda").val("' . $producto['idc_moneda'] . '").trigger("change");
                $("#clave_producto_servicio").trigger("change");
            ');

            $this->cargarImpuestosConsulta();
        }

        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' type='button' id='btnguardar' name='btnguardar' onclick='JaxonalmacenProductos.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                <i class='bi bi-save'></i> Guardar
            </button>
        ");

        return $this->response;
    }

    function actualizaSubCategorias($idcategoria)
    {
        $this->response->assign("idsubcategoria", "innerHTML", getSubcategorias($idcategoria));
        $this->response->assign("idsubsubcategoria", "innerHTML", "");
        return $this->response;
    }

    function actualizaSubSubCategorias($idsubcategoria)
    {
        $this->response->assign("idsubsubcategoria", "innerHTML", getSubsubcategorias($idsubcategoria));
        return $this->response;
    }

    function validarClaveSat($cadenaEscrita)
    {
        global $database;

        // Consulta usando Medoo para buscar la clave
        $registro = $database->get("cfdi_claveprodserv", ["descripcion", "palabras_similares"], [
            "c_claveprodserv" => $cadenaEscrita
        ]);

        // Verificar si se encontró un resultado
        if ($registro) {
            // La clave existe; construir el texto
            $descripcion = $registro['descripcion'];
            $palabras_similares = $registro['palabras_similares'];

            if (!empty($palabras_similares)) {
                // Si hay palabras similares, inclúyelas entre paréntesis
                $texto = "$descripcion ($palabras_similares)";
            } else {
                // Solo la descripción
                $texto = $descripcion;
            }
        } else {
            // La clave no existe
            $texto = "La clave ingresada no existe en el catálogo del SAT";
        }

        // Asignar el texto al campo de descripción usando Jaxon
        $this->response->assign("descripcion_producto_servicio", "value", $texto);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function modalAddImpuesto()
    {
        // Definir los campos para el formulario modal
        $campos = [
            [
                'id' => 'impuesto',
                'label' => 'Impuesto',
                'type' => 'select',
                'options' => getCfdiImpuesto(),
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'tipoImpuesto',
                'label' => 'Tipo de Impuesto',
                'type' => 'select',
                'options' => '
                    <option value="" selected disabled>Elije una opción...</option>
                    <option value="Traslado">Traslado</option>
                    <option value="Retencion">Retencion</option>',
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'tipoFactor',
                'label' => 'Tipo de factor',
                'type' => 'select',
                'options' => getCfdiTipoFactor(),
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'porcentaje',
                'label' => 'Porcentaje en entero (ej: 16)',
                'type' => 'number',
                'value' => '', // Valor por defecto
            ],
        ];

        // Título del modal
        $titulo = 'Agregar Impuesto';

        // Callback que se ejecutará al guardar
        $funcionCallBack = 'JaxonalmacenProductos.addImpuesto'; // Nombre de la función JavaScript

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function addImpuesto($form)
    {
        // Definir las reglas de validación
        $reglas = [
            'impuesto' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'tipoImpuesto' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'tipoFactor' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1],
            'porcentaje' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 0, 'pattern' => '/^\d+$/'],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);
        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            $this->modalAddImpuesto();
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

        // Si la validación es exitosa, agregar el impuesto a la sesión
        $_SESSION['partidas' . $_GET['rand']][] = [
            'iddb' => 0,
            'impuesto' => $form['impuesto'],
            'tipoImpuesto' => $form['tipoImpuesto'],
            'tipoFactor' => $form['tipoFactor'],
            'porcentaje' => $form['porcentaje'],
            'estado' => 'Activo',
        ];
        $this->tablaImpuestos();
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function tablaImpuestos()
    {
        $impuestos = $_SESSION['partidas' . $_GET['rand']] ?? [];
        global $database;
        // Verificar si hay impuestos para mostrar
        if (empty($impuestos)) {
            $html = '<p class="text-muted text-center">No hay impuestos registrados.</p>';
        } else {
            // Construir la tabla
            $html = '<div class="table-responsive">'; // Contenedor responsivo
            $html .= '<table class="table table-borderless table-striped table-hover">';
            $html .= '<thead class="text-bg-secondary">';
            $html .= '<tr>';
            $html .= '<th>CFDI Impuesto</th>';
            $html .= '<th>Tipo de Impuesto</th>';
            $html .= '<th>CFDI Tipo Factor</th>';
            $html .= '<th>Porcentaje</th>';
            $html .= '<th>Acciones</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($impuestos as $index => $impuesto) {
                if ($impuesto['estado'] == 'Activo') {
                    $cfdi_impuesto = $database->get('cfdi_impuesto', ['c_impuesto', 'descripcion'], ['id' => $impuesto['impuesto']]);
                    $cfdi_tipofactor = $database->get('cfdi_tipofactor', 'c_tipofactor', ['id' => $impuesto['tipoFactor']]);
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($cfdi_impuesto['c_impuesto'] . ' - ' . $cfdi_impuesto['descripcion']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($impuesto['tipoImpuesto']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($cfdi_tipofactor) . '</td>';
                    $html .= '<td>' . htmlspecialchars($impuesto['porcentaje']) . '%</td>';
                    $html .= '<td>';
                    $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="JaxonalmacenProductos.eliminarImpuesto(' . $index . ');">';
                    $html .= '<i class="bi bi-trash"></i>';
                    $html .= '</button>';
                    $html .= '<button type="button" class="btn btn-sm btn-primary" onclick="JaxonalmacenProductos.modalEditImpuesto(' . $index . ');">';
                    $html .= '<i class="bi bi-pencil-square"></i>';
                    $html .= '</button>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>'; // Cierre del contenedor responsivo
        }

        // Asignar el HTML generado al contenedor en el card-body
        $this->response->assign("tablaImpuestos", "innerHTML", $html);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function eliminarImpuesto($index)
    {
        // Verificar si el índice existe en la sesión
        if (isset($_SESSION['partidas' . $_GET['rand']][$index])) {
            // Cambiar el estado del impuesto a 'Inactivo'
            $_SESSION['partidas' . $_GET['rand']][$index]['estado'] = 'Inactivo';

            // Devolver una respuesta con éxito (esto puede ser útil para notificaciones o actualizaciones)
            $this->alerta(
                "Éxito",
                "El impuesto se ha marcado como inactivo.",
                "success",
                null,
                false,
                true
            );
        } else {
            // Si no se encuentra el índice, mostrar un mensaje de error
            $this->alerta(
                "Error",
                "No se encontró el impuesto.",
                "error",
            );
        }

        $this->tablaImpuestos();
        // Retornar la respuesta Jaxon
        return $this->response;
    }


    function modalEditImpuesto($index)
    {
        // Obtener la partida actual desde la sesión
        $impuestoActual = $_SESSION['partidas' . $_GET['rand']][$index] ?? null;

        if (!$impuestoActual) {
            // Si no se encuentra la partida, mostrar un mensaje de error
            return $this->alerta(
                'Error',
                'No se encontró la partida seleccionada.',
                'error'
            );
        }

        // Definir los campos para el formulario modal
        $campos = [
            [
                'id' => 'iddb',
                'label' => '',
                'type' => 'hidden',
                'value' => $impuestoActual['iddb'] ?? 0, // Valor actual
            ],
            [
                'id' => 'impuesto',
                'label' => 'Impuesto',
                'type' => 'select',
                'options' => getCfdiImpuesto(),
                'value' => $impuestoActual['impuesto'] ?? '', // Valor actual
            ],
            [
                'id' => 'tipoImpuesto',
                'label' => 'Tipo de Impuesto',
                'type' => 'select',
                'options' => '
                    <option value="" disabled>Elije una opción...</option>
                    <option value="Traslado" ' . ($impuestoActual['tipoImpuesto'] == 'Traslado' ? 'selected' : '') . '>Traslado</option>
                    <option value="Retencion" ' . ($impuestoActual['tipoImpuesto'] == 'Retencion' ? 'selected' : '') . '>Retención</option>',
                'value' => $impuestoActual['tipoImpuesto'] ?? '', // Valor actual
            ],
            [
                'id' => 'tipoFactor',
                'label' => 'Tipo de factor',
                'type' => 'select',
                'options' => getCfdiTipoFactor(),
                'value' => $impuestoActual['tipoFactor'] ?? '', // Valor actual
            ],
            [
                'id' => 'porcentaje',
                'label' => 'Porcentaje en entero (ej: 16)',
                'type' => 'number',
                'value' => $impuestoActual['porcentaje'] ?? '', // Valor actual
            ],
        ];

        // Título del modal
        $titulo = 'Editar Impuesto';

        // Callback que se ejecutará al guardar los cambios
        $funcionCallBack = 'JaxonalmacenProductos.editarImpuesto'; // Nombre de la función JavaScript

        // Agregar el índice como parámetro adicional
        $parametrosAdicionales = ', ' . $index;

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack, $parametrosAdicionales);

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function editarImpuesto($form, $index)
    {
        // Validar el índice
        if (!isset($_SESSION['partidas' . $_GET['rand']][$index])) {
            return $this->alerta('Error', 'No se encontró la partida a editar.', 'error');
        }

        // Validar los datos
        $reglas = [
            'impuesto' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'tipoImpuesto' => ['obligatorio' => true, 'tipo' => 'string'],
            'tipoFactor' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'porcentaje' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1, 'max_val' => 50],
        ];
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            $error = $resultadoValidacion['error'];
            $campo = $resultadoValidacion['campo'];

            $this->modalEditImpuesto($index);
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

        // Actualizar la partida en la sesión
        $_SESSION['partidas' . $_GET['rand']][$index] = [
            'iddb' => $form['iddb'],
            'impuesto' => $form['impuesto'],
            'tipoImpuesto' => $form['tipoImpuesto'],
            'tipoFactor' => $form['tipoFactor'],
            'porcentaje' => $form['porcentaje'],
            'estado' => 'Activo', // Mantener o cambiar estado según el caso
        ];

        // Mostrar un mensaje de éxito
        $this->alerta(
            "Éxito",
            "El impuesto se ha actualizado correctamente.",
            "success",
            null,
            false,
            true
        );

        // Actualizar la tabla en la vista
        $this->tablaImpuestos();

        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function validarUsoLoteSerie($form)
    {
        if ($form['kit'] == 'Sí') {
            // Mostrar un mensaje de éxito
            $this->alerta(
                "Invalido",
                "No se puede tener un producto de tipo kit con lotes.",
                "warning",
                "lote_serie",
                false,
                true
            );
            $this->response->assign("lote_serie", "value", "No");
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function validarUsoKit($form)
    {
        if ($form['lote_serie'] == 'Sí') {
            // Mostrar un mensaje de éxito
            $this->alerta(
                "Invalido",
                "No se puede tener un producto de tipo kit con lotes.",
                "warning",
                "kit",
                false,
                true
            );
            $this->response->assign("kit", "value", "No");
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function validar($form)
    {
        // Definir las reglas de validación
        $reglas = [
            'codigo_barras' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'nombre' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'marca' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'descripcion' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 5000],
            'estado' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'idtipo' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idcategoria' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idsubcategoria' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idsubsubcategoria' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'lote_serie' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 20],
            'kit' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'max' => 20],
            'costo' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0.0001, 'max_val' => 9999999999.9999],
            'costo2' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0, 'max_val' => 9999999999.9999],
            'costo3' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0, 'max_val' => 9999999999.9999],
            'precio' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0.0001, 'max_val' => 9999999999.9999],
            'precio2' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0, 'max_val' => 9999999999.9999],
            'precio3' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 0, 'max_val' => 9999999999.9999],
            'idc_claveunidad' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_moneda' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'clave_producto_servicio' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 8, 'max' => 8],
            'descripcion_producto_servicio' => ['obligatorio' => true, 'tipo' => 'string', 'min' => 1, 'pattern' => '/^(?!.*La clave ingresada no existe en el catálogo del SAT).*$/i'],
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
            $resultadoValidacionRepetidoCodigoBarras = verificaRegistroRepetido("empresa", "productos", "codigo_barras", $form['codigo_barras'], $_GET['id']);
            if ($resultadoValidacionRepetidoCodigoBarras) {
                // El registro está repetido, mostrar un error
                $this->alerta('Error', 'Ya existe existe un producto con este codigo de barras', 'error', 'codigo_barras', true, false);
                return $this->response;
            } else {
                $resultadoValidacionRepetidoNombre = verificaRegistroRepetido("empresa", "productos", "nombre", $form['nombre'], $_GET['id']);
                if ($resultadoValidacionRepetidoNombre) {
                    // El registro está repetido, mostrar un error
                    $this->alerta('Error', 'Ya existe existe un producto con este nombre', 'error', 'nombre', true, false);
                    return $this->response;
                } else {
                    $this->guardar($form);
                }
            }
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }

    function guardar($form)
    {
        global $database; // instancia de Medoo
        $this->response->assign("btnguardar", "disabled", "disabled"); //Deshabilitar boton de guardar para evitar que el usuario de click varias veces
        // Preparamos los datos a utilizar
        $data = [
            'idempresa' => isset($_SESSION['idempresa']) ? $_SESSION['idempresa'] : 0,
            'idc_moneda' => isset($form['idc_moneda']) ? $form['idc_moneda'] : 100,
            'idc_claveprodserv' => isset($form['idc_claveprodserv']) ? $form['idc_claveprodserv'] : 1,
            'idc_claveunidad' => isset($form['idc_claveunidad']) ? $form['idc_claveunidad'] : 1070,
            'idcategoria' => isset($form['idcategoria']) ? $form['idcategoria'] : 0,
            'idsubcategoria' => isset($form['idsubcategoria']) ? $form['idsubcategoria'] : 0,
            'idsubsubcategoria' => isset($form['idsubsubcategoria']) ? $form['idsubsubcategoria'] : 0,
            'idtipo' => isset($form['idtipo']) ? $form['idtipo'] : 0,
            'codigo_barras' => isset($form['codigo_barras']) ? $form['codigo_barras'] : null,
            'nombre' => isset($form['nombre']) ? $form['nombre'] : null,
            'descripcion' => isset($form['descripcion']) ? $form['descripcion'] : null,
            'precio' => isset($form['precio']) ? $form['precio'] : 0.0001,
            'precio2' => isset($form['precio2']) ? $form['precio2'] : 0.0000,
            'precio3' => isset($form['precio3']) ? $form['precio3'] : 0.0000,
            'costo' => isset($form['costo']) ? $form['costo'] : 0.0001,
            'costo2' => isset($form['costo2']) ? $form['costo2'] : 0.0000,
            'costo3' => isset($form['costo3']) ? $form['costo3'] : 0.0000,
            'estado' => isset($form['estado']) ? $form['estado'] : 'Activo',
            'marca' => isset($form['marca']) ? $form['marca'] : null,
            'lote_serie' => isset($form['lote_serie']) ? $form['lote_serie'] : 'No',
            'kit' => isset($form['kit']) ? $form['kit'] : 'No',
        ];

        // Si el 'id' de la URL es 0, realizamos una inserción
        if ($_GET['id'] == 0) {
            // Realizamos la inserción
            try {
                // Insertar un nuevo registro si no existe ID
                $database->insert('productos', $data);
                $insert_id = $database->id();
                // Llamamos a la función guardarPartidas y pasamos el id del nuevo producto
                $this->guardarPartidas($insert_id); // Aquí pasamos el ID del nuevo producto
                $this->alerta(
                    "¡GUARDADO!",
                    "El producto ha sido guardado con exito",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL GUARDAR!",
                    "El producto no se pudo guardar correctamente, por favor reporte este problema con el administrador del sistema",
                    "error"
                );
            }
        }
        // Si el 'id' no es 0, actualizamos el registro correspondiente
        else {
            // Realizamos la actualización

            try {
                // Insertar un nuevo registro si no existe ID
                $database->update('productos', $data, ['id' => $_GET['id']]);
                // Llamamos a la función guardarPartidas y pasamos el id del producto
                $this->guardarPartidas($_GET['id']); // Aquí pasamos el ID del producto actualizado
                $this->alerta(
                    "¡ACTUALIZADO!",
                    "El producto ha sido actualizado con exito.",
                    "success",
                    null,
                    true,
                    false,
                    "index.php"
                );
            } catch (PDOException $e) {
                $this->alerta(
                    "¡ERROR AL ACTUALIZAR!",
                    "El producto no se pudo actualizar correctamente, por favor reporte este problema con el administrador del sistema",
                    "error"
                );
            }
        }

        // Retornar la respuesta Jaxon
        return $this->response;
    }



    function guardarPartidas($idproducto)
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
                        'idproducto' => $idproducto, // ID del producto desde la URL
                        'idc_impuesto' => $partida['impuesto'],
                        'tipo' => $partida['tipoImpuesto'],
                        'idc_tipofactor' => $partida['tipoFactor'],
                        'porcentaje' => $partida['porcentaje'],
                        'estado' => $partida['estado'],
                    ];
                    // Realizamos la inserción
                    $database->insert('productos_impuestos', $data);
                } else {
                    // Si iddb no es 0, significa que la partida ya existe, por lo que actualizamos
                    $data = [
                        'idc_impuesto' => $partida['impuesto'],
                        'tipo' => $partida['tipoImpuesto'],
                        'idc_tipofactor' => $partida['tipoFactor'],
                        'porcentaje' => $partida['porcentaje'],
                        'estado' => $partida['estado'],
                    ];
                    // Realizamos la actualización
                    $database->update('productos_impuestos', $data, ['id' => $partida['iddb']]);
                }
            }
        }

        // Retornar la respuesta Jaxon
        return $this->response;
    }



    function cargarImpuestosConsulta()
    {
        global $database;
        //consultas a la bd para obtener los datos necesarios para la consulta de los impuestos
        $impuestos = $database->select("productos_impuestos", "*", ["idproducto" => $_GET['id']]);
        foreach ($impuestos as $impuesto) {
            $_SESSION['partidas' . $_GET['rand']][] = [
                'iddb' => $impuesto['id'],
                'impuesto' => $impuesto['idc_impuesto'],
                'tipoImpuesto' => $impuesto['tipo'],
                'tipoFactor' => $impuesto['idc_tipofactor'],
                'porcentaje' => $impuesto['porcentaje'],
                'estado' => $impuesto['estado'],
            ];
        }
        $this->tablaImpuestos();
        // Retornar la respuesta Jaxon
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenProductos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












