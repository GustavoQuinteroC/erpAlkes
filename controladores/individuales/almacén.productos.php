<?php
session_start();
// controlador.php
require_once(__DIR__ .'/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class almacenProductos extends alkesGlobal
{
    function inializarFormulario()
    {
        if($_GET['id']!=0)
        {
            if(!validarEmpresaPorRegistro("productos", $_GET['id']))
            {
                $this->alerta(
                    "¡ERROR GRABE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            }
        }
        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' type='button' value='Guardar' onclick='JaxonalmacenProductos.validar(jaxon.getFormValues(\"formProducto{$rand}\"));'>
                <i class='bi bi-save'></i> Guardar
            </button>
        ");

        


        return $this->response;
    }

    function actualizaSubCategorias($idcategoria)
    {
        $this->response->assign("idsubcategoria", "innerHTML", getSubcategorias($idcategoria));
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
        $_SESSION['partidasImpuestos'.$_GET['rand']][] = [
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
        $impuestos = $_SESSION['partidasImpuestos' . $_GET['rand']] ?? [];

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
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($impuesto['impuesto']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($impuesto['tipoImpuesto']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($impuesto['tipoFactor']) . '</td>';
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
        if (isset($_SESSION['partidasImpuestos'.$_GET['rand']][$index])) {
            // Cambiar el estado del impuesto a 'Inactivo'
            $_SESSION['partidasImpuestos'.$_GET['rand']][$index]['estado'] = 'Inactivo';

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
        $impuestoActual = $_SESSION['partidasImpuestos' . $_GET['rand']][$index] ?? null;

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
        if (!isset($_SESSION['partidasImpuestos' . $_GET['rand']][$index])) {
            return $this->alerta('Error', 'No se encontró la partida a editar.', 'error');
        }
    
        // Validar los datos
        $reglas = [
            'impuesto' => ['obligatorio' => true, 'tipo' => 'string'],
            'tipoImpuesto' => ['obligatorio' => true, 'tipo' => 'string'],
            'tipoFactor' => ['obligatorio' => true, 'tipo' => 'string'],
            'porcentaje' => ['obligatorio' => true, 'tipo' => 'int'],
        ];
        $resultadoValidacion = validar_global($form, $reglas);
    
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
        $_SESSION['partidasImpuestos' . $_GET['rand']][$index] = [
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
        if($form['kit']=='Sí')
        {
            // Mostrar un mensaje de éxito
            $this->alerta(
                "Invalido",
                "No se puede tener un producto de tipo kit con lotes.",
                "warning",
                "lote_serie",
                false,
                true
            );
            $this->response->assign("lote_serie","value","No");
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }
    
    function validarUsoKit($form)
    {
        if($form['lote_serie']=='Sí')
        {
            // Mostrar un mensaje de éxito
            $this->alerta(
                "Invalido",
                "No se puede tener un producto de tipo kit con lotes.",
                "warning",
                "kit",
                false,
                true
            );
            $this->response->assign("kit","value","No");
        }
        // Retornar la respuesta Jaxon
        return $this->response;
    }




}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenProductos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












