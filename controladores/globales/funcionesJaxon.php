<?php
##########################################################################
#CODIGO FUENTE DE FUNCIONES JAXON GLOBALES PARA EL SISTEMA
#INICIO DE VERSION 07/JUNIO/2024
#GUSTAVO QUINTERO
#ALKES - 
##########################################################################
require_once(__DIR__ ."/../../vendor/autoload.php");
require_once(__DIR__ ."/funcionesPhp.php");


use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

// Inicializa Jaxon
$url_base = $_SERVER['HTTP_X_FORWARDED_PROTO']."://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$jaxon = jaxon();
$jaxon->setOption('core.request.mode', 'asynchronous');
$jaxon->setOption('core.request.method', 'POST');
$jaxon->setOption('core.request.uri', $url_base);


class alkesGlobal
{
    public $response;
    public function __construct()
    {
        $this->response = jaxon()->newResponse();
    }
    public function unionCerrarSesion()
    {
        cerrarSesion(false); // Llama a cerrarSesion pero evita la redirección interna
        $this->response->script('window.location.href = "/"'); // Redirige desde el frontend
        return $this->response;
    }

    public function cambiarEntidad($id)
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

    function cambiarEnfasis($color)
    {
        global $database;

        // Validar el color recibido por parámetro
        if (!in_array($color, ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'dark', 'light'])) {
            return $this->response->alert('Color de énfasis inválido.');
        }

        // Actualizar el color de énfasis en la base de datos
        $database->update('usuarios', ['enfasis' => $color], ['id' => $_SESSION['idusuario']]);

        // Hacer un reload parcial de la página usando Jaxon
        // Esto hará que se recargue todo el contenido que depende del color de énfasis
        $this->response->script('location.reload();');

        return $this->response;
    }

    function listadosIndex($modulo, $submodulo, $subsubmodulo, $filtroSeleccionado = '', $uso = '')
    {
        $filtros = filtrosTablas($modulo, $submodulo, $subsubmodulo, $uso);
        $html = '';
    
        // Construcción específica para la vista normal
        $html .= '<ul class="nav nav-tabs">';
        foreach ($filtros as $filtro) {
            $html .= '<li id="' . $filtro['nombre'] . '" class="nav-item">';
            $html .= '<a class="nav-link" href="#" title="' . $filtro['nombre'] . '" onclick="JaxonalkesGlobal.listadosIndex(\'' . $modulo . '\', \'' . $submodulo . '\', \'' . $subsubmodulo . '\', \'' . $filtro['nombre'] . '\', \'' . $uso . '\');">' . $filtro['nombre'] . '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $this->response->assign("filtros", "innerHTML", $html);
    
        $html = '<table id="tablaListado" class="table table-striped table-hover display" style="width:100%">';
    
        // Añadir las columnas de la tabla y cerrar etiquetas necesarias
        $columnas = columnasTablas($modulo, $submodulo, $subsubmodulo, $uso);
        $orden = ordenTablas($modulo, $submodulo, $subsubmodulo, $uso);
        $html .= '<thead><tr>' . $columnas . '</tr></thead></table>';
    
        $this->response->assign("tabla", "innerHTML", $html);
    
        // Configuración de DataTable con parámetros comunes
        $ajaxUrl = '/controladores/globales/tablas.php?modulo=' . $modulo . '&submodulo=' . $submodulo . '&subsubmodulo=' . $subsubmodulo . '&filtro=' . $filtroSeleccionado . '&uso=' . $uso;
        $pageLength = 10;
        $lengthMenu = '[[10, 20, 50, 100, 500, 10000, 100000], [10, 20, 50, 100, 500, 10000, 100000]]';
    
        // Construir $dom y $botonera
        $dom = 'dom: "<\'top d-flex justify-content-between align-items-center\' <\'left\'l> <\'center\'B> <\'right\'f>>rtip",';
        $botonera = 'buttons: ["copy", "excel", "print", "colvis"],';
    
        // Script para inicializar DataTable
        $this->response->script("\n        if ($.fn.DataTable.isDataTable('#tablaListado')) {\n            $('#tablaListado').DataTable().destroy();\n        }\n        $('#tablaListado').DataTable({\n            ajax: '$ajaxUrl',\n            responsive: true,\n            processing: true,\n            serverSide: true,\n            pageLength: $pageLength,\n            lengthMenu: $lengthMenu,\n            info: false,  // Aquí desactivamos la visualización de la información de los registros\n            $dom\n            $botonera\n            language: {\n                url: \"/plugins/datatables/es-ES.json\"\n            },\n            order: [[" . $orden['indice'] . ", '" . $orden['orden'] . "']]\n        });\n    ");

        // Aplicar o remover clases 'active' en pestañas
        foreach ($filtros as $filtro) {
            $nombre = $filtro['nombre'];
            if ($nombre == $filtroSeleccionado) {
                $this->response->script('$("li[id=\"" + "' . $nombre . '" + "\"]").addClass("active"); $("li[id=\"" + "' . $nombre . '" + "\"] a").addClass("active");');
            } else {
                $this->response->script('$("li[id=\"" + "' . $nombre . '" + "\"]").removeClass("active"); $("li[id=\"" + "' . $nombre . '" + "\"] a").removeClass("active");');
            }
        }
    
        return $this->response;
    }

    public function modalFormulario($campos, $titulo, $funcionCallBack, $parametrosAdicionales = '')
    {
        // Crear el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalFormulario" tabindex="-1" aria-labelledby="modalFormularioLabel" aria-hidden="true">';
        $html .= '<div class="modal-dialog">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-'.getEnfasis().'">';
        $html .= '<h5 class="modal-title" id="modalFormularioLabel">' . $titulo . '</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<!-- Aquí van los campos para la modal -->';
        $html .= '<form id="formModalFormulario">';

        foreach ($campos as $campo) {
            $html .= '<div class="mb-3">';
            $html .= '<label for="' . $campo['id'] . '" class="form-label">' . $campo['label'] . '</label>';

            $readOnly = isset($campo['readOnly']) && $campo['readOnly'] ? ' readonly' : '';
            $disabled = isset($campo['disabled']) && $campo['disabled'] ? ' disabled' : '';

            switch ($campo['type']) {
                case 'textarea':
                    $html .= '<textarea class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $readOnly . '>' . $campo['value'] . '</textarea>';
                    break;
                case 'select':
                    $html .= '<select class="form-select" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $disabled . '>';
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
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
        $html .= '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalFormulario\')' . $parametrosAdicionales . ');">Guardar</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Remover cualquier modal previo
        $this->response->remove('modalFormulario');

        // Insertar el nuevo modal
        $this->response->append("modales", "innerHTML", $html);

        // Mostrar el modal con Bootstrap 5
        $this->response->script('new bootstrap.Modal(document.getElementById("modalFormulario")).show();');

        //Eliminar el elemento si es que se cierra
        $this->response->script('
        if (!$("#modalFormulario").data("evento-registrado")) {
            $("#modalFormulario").on("hidden.bs.modal", function () {
                $(this).remove();
            });
            $("#modalFormulario").data("evento-registrado", true);
        }
        ');

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






















    


































    

    function pruebaSmall()
    {
        // Actualizar el contenido del <small> con el ID especificado
        $this->response->assign('smallTitulos', 'innerHTML', 'Prueba1');

        return $this->response;
    }






























    function cambiarCards($color)
    {
        $database = new Medoo();
        $database->update('usuarios', ['cards' => $color], ['id' => $_SESSION['idusuario']]);
        $this->response->script('location.reload();');
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
