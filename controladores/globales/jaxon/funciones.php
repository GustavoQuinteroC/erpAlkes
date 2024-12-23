<?php
##########################################################################
#CODIGO FUENTE DE FUNCIONES JAXON GLOBALES PARA EL SISTEMA
#INICIO DE VERSION 07/JUNIO/2024
#GUSTAVO QUINTERO
#ALKES - 
##########################################################################
require_once(__DIR__ ."/../../../vendor/autoload.php");
require_once(__DIR__ ."/../php/funciones.php");


use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

// Inicializa Jaxon
$url_base = $_SERVER['HTTP_X_FORWARDED_PROTO']."://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$jaxon = jaxon();
$jaxon->setOption('core.request.mode', 'asynchronous');
$jaxon->setOption('core.request.method', 'POST');
$jaxon->setOption('core.request.uri', $url_base);
//$jaxon->setOption('core.prefix.function', 'JaxonalkesGlobal.');

class alkesGlobal
{
    public $response;
    public function __construct()
    {
        $this->response = jaxon()->newResponse();
    }

    function cambiarEntidad($id)
    {
        $_SESSION['entidad'] = $id;
        $this->response->script('location.reload();');
        return $this->response;
    }

    function cambiarBackgrounds($tema)
    {
        global $database;

        // Validar el tema recibido por parámetro.
        if (!in_array($tema, ['dark', 'light'])) {
            return $this->response->alert('Tema inválido.');
        }

        // Actualizar el tema en la base de datos.
        $database->update('usuarios', ['backgrounds' => $tema], ['id' => $_SESSION['idusuario']]);

        // Cambiar el atributo data-bs-theme y el icono del tema dinámicamente.
        $nuevoIcono = $tema === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        $this->response->script("document.documentElement.setAttribute('data-bs-theme', '$tema');");
        $this->response->script("document.getElementById('iconoTema').className = '$nuevoIcono';");

        return $this->response;
    }



    function cambiarCards($color)
    {
        $database = new Medoo();
        $database->update('usuarios', ['cards' => $color], ['id' => $_SESSION['idusuario']]);
        $this->response->script('location.reload();');
        return $this->response;
    }


    public function modalFormulario($campos, $titulo, $funcionCallBack, $parametrosAdicionales = '')
    {
        // Crea el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalFormulario" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">';
        $html .= '<div class="modal-dialog" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header bg-secondary">';
        $html .= '<h5 class="modal-title" id="exampleModalLabel">' . $titulo . '</h5>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<!-- Aquí van los campos para la modal -->';
        $html .= '<form id="formModalFormulario">';
        foreach ($campos as $campo) {
            $html .= '<div class="form-group">';
            $html .= '<label for="' . $campo['id'] . '">' . $campo['label'] . '</label>';

            $readOnly = isset($campo['readOnly']) && $campo['readOnly'] ? ' readonly' : '';
            $disabled = isset($campo['disabled']) && $campo['disabled'] ? ' disabled' : '';

            switch ($campo['type']) {
                case 'textarea':
                    $html .= '<textarea class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $readOnly . '>' . $campo['value'] . '</textarea>';
                    break;
                case 'select':
                    $html .= '<select class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $disabled . '>';
                    $html .= $campo['options'];
                    $html .= '</select>';
                    break;
                default:
                    $html .= '<input type="' . $campo['type'] . '" class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '" value="' . $campo['value'] . '"' . $readOnly . '>';
                    break;
            }

            $html .= '</div>';
        }
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>';
        $html .= '<button type="button" class="btn btn-primary" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalFormulario\')' . $parametrosAdicionales . ');" data-dismiss="modal">Guardar</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $this->response->remove('modalFormulario');
        $this->response->append("footer", "innerHTML", $html);
        $this->response->script('$( "#modalFormulario" ).modal("show");');
        return $this->response;
    }


    public function modalSeleccion($titulo, $columnas, $data, $funcionCallBack, $multiple = false, $parametrosAdicionales = '')
    {
        // Crea el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalSeleccion" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionLabel" aria-hidden="true">';
        $html .= '<div class="modal-dialog modal-lg" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header bg-secondary">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLabel">' . $titulo . '</h5>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<!-- Aquí va la tabla para la modal -->';
        $html .= '<form id="formModalSeleccion">';
        $html .= '<table id="tablaModalSeleccion" class="table table-striped table-bordered" style="width:100%">';
        $html .= '<thead>';
        $html .= '<tr>';

        // Agrega los encabezados de las columnas
        foreach ($columnas as $columna) {
            $html .= '<th>' . $columna . '</th>';
        }

        // Agrega una columna extra para la selección
        $html .= '<th>Seleccionar</th>';

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Agrega los registros de datos
        foreach ($data as $row) {
            $html .= '<tr>';

            // Agrega cada columna de la fila
            for ($i = 1; $i < count($row); $i++) {
                $html .= '<td>' . $row[$i] . '</td>';
            }

            // Agrega el input de selección
            $html .= '<td class="text-center">';

            if ($multiple) {
                $html .= '<input type="checkbox" name="seleccion[]" value="' . $row[0] . '">';
            } else {
                $html .= '<input type="radio" name="seleccion" value="' . $row[0] . '">';
            }

            $html .= '</td>';

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>';
        $html .= '<button type="button" class="btn btn-primary" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalSeleccion\')' . $parametrosAdicionales . ');" data-dismiss="modal">Aceptar</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $this->response->remove('modalSeleccion');
        $this->response->append("footer", "innerHTML", $html);
        $this->response->script('$(document).ready(function() {$("#tablaModalSeleccion").DataTable({ "language": { "url": "/plugins/datatables/es-ES.json" } });});');
        $this->response->script('$( "#modalSeleccion" ).modal("show");');
        return $this->response;
    }

    public function alerta($titulo, $mensaje, $icono, $elementoEnfocar = null, $boton = true, $timer = false, $redireccion = null)
    {
        //quitar el foco de todo el documento
        $this->response->script('$(document.activeElement).blur();');
        $mostrarTimer = $timer ? 'timer: 2000,' : '';
        $enfoqueScript = $elementoEnfocar !== null ? 'setTimeout(function() { $("#' . $elementoEnfocar . '").focus(); }, 2);' : '';
        $redireccionScript = $redireccion !== null ? 'window.location.href = "' . $redireccion . '";' : '';

        $script = 'Swal.fire({
        title: "' . $titulo . '",
        text: "' . $mensaje . '",
        showConfirmButton: "' . $boton . '",
        ' . $mostrarTimer . '
        icon: "' . $icono . '"
        }).then((result) => {
            ' . $enfoqueScript . '
            ' . $redireccionScript . '
        });';

        // Agregar el script de SweetAlert2 a la response
        $this->response->script($script);

        // Devolver la response a Jaxon
        return $this->response;
    }


    public function alertaConfirmacion($titulo, $mensaje, $icono, $confirmCallback)
    {
        // Quital el foco de todo el documento
        $this->response->script('$(document.activeElement).blur();');

        // Construir el script de SweetAlert2 con los parámetros proporcionados
        $script = '
                Swal.fire({
                    title: "' . $titulo . '",
                    text: "' . $mensaje . '",
                    icon: "' . $icono . '",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745", // Color verde para el botón de confirmación
                    cancelButtonColor: "#d33", // Color del botón de cancelación
                    confirmButtonText: "Sí, confirmar", // Texto del botón de confirmación
                    cancelButtonText: "Cancelar", // Texto del botón de cancelación
                    reverseButtons: true // Intercambiar posición de los botones
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Llamar a la función de confirmación proporcionada
                        ' . $confirmCallback . '
                    } else if (
                        result.dismiss === Swal.DismissReason.cancel
                    ) {
                        Swal.fire({
                            title: "Cancelado",
                            text: "No se han hecho cambios ;)",
                            icon: "error"
                        });
                    }
                });
            ';
        // Agregar el script de SweetAlert2 a la response
        $this->response->script($script);

        // Devolver la response
        return $this->response;
    }

}


$jaxon->register(Jaxon::CALLABLE_CLASS, alkesGlobal::class);
