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

    public function cambiarSucursal($id)
    {
        $_SESSION['sucursal'] = $id;
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
        $filtros = filtrosTablas($modulo, $submodulo, $subsubmodulo);
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
        $this->response->script("\n        if ($.fn.DataTable.isDataTable('#tablaListado')) {\n            $('#tablaListado').DataTable().destroy();\n        }\n        $('#tablaListado').DataTable({\n            ajax: '$ajaxUrl',\n            responsive: true,\n            processing: true,\n            serverSide: true,\n            pageLength: $pageLength,\n            lengthMenu: $lengthMenu,\n            info: true,  // Aquí desactivamos la visualización de la información de los registros\n            $dom\n            $botonera\n            language: {\n                url: \"/plugins/datatables/es-ES.json\"\n            },\n            order: [[" . $orden['indice'] . ", '" . $orden['orden'] . "']]\n        });\n    ");

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

    function listadosIndexInventario($modulo, $submodulo, $subsubmodulo, $filtroSeleccionado = '', $uso = '', $idalmacen = 0)
    {
        $filtros = filtrosTablas($modulo, $submodulo, $subsubmodulo);
        $html = '';

        // Construcción específica para la vista normal
        $html .= '<ul class="nav nav-tabs">';
        foreach ($filtros as $filtro) {
            $html .= '<li id="' . $filtro['nombre'] . '" class="nav-item">';
            $html .= '<a class="nav-link" href="#" title="' . $filtro['nombre'] . '" onclick="JaxonalkesGlobal.listadosIndexInventario(\'' . $modulo . '\', \'' . $submodulo . '\', \'' . $subsubmodulo . '\', \'' . $filtro['nombre'] . '\', \'' . $uso . '\', \'' . $idalmacen . '\');">' . $filtro['nombre'] . '</a>';
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
        $ajaxUrl = '/controladores/globales/tablas.php?modulo=' . $modulo . '&submodulo=' . $submodulo . '&subsubmodulo=' . $subsubmodulo . '&idalmacen=' . $idalmacen . '&filtro=' . $filtroSeleccionado . '&uso=' . $uso;
        $pageLength = 10;
        $lengthMenu = '[[10, 20, 50, 100, 500, 10000, 100000], [10, 20, 50, 100, 500, 10000, 100000]]';

        // Construir $dom y $botonera
        $dom = 'dom: "<\'top d-flex justify-content-between align-items-center\' <\'left\'l> <\'center\'B> <\'right\'f>>rtip",';
        $botonera = 'buttons: ["copy", "excel", "print", "colvis"],';

        // Script para inicializar DataTable
        $this->response->script("\n        if ($.fn.DataTable.isDataTable('#tablaListado')) {\n            $('#tablaListado').DataTable().destroy();\n        }\n        $('#tablaListado').DataTable({\n            ajax: '$ajaxUrl',\n            responsive: true,\n            processing: true,\n            serverSide: true,\n            pageLength: $pageLength,\n            lengthMenu: $lengthMenu,\n            info: true,  // Aquí desactivamos la visualización de la información de los registros\n            $dom\n            $botonera\n            language: {\n                url: \"/plugins/datatables/es-ES.json\"\n            },\n            order: [[" . $orden['indice'] . ", '" . $orden['orden'] . "']]\n        });\n    ");

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

    function modalSeleccionServerSide($modulo, $submodulo, $subsubmodulo, $idalmacen=0, $filtroSeleccionado = '', $uso = '', $funcionCallBack = '', $multiple = false, $parametrosAdicionales = '', $tituloModal = '')
    {
        $filtros = filtrosTablas($modulo, $submodulo, $subsubmodulo);
        $html = '';

        // Construcción específica para el modal
        $html .= '<div class="modal fade" id="modalSeleccion" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionLabel">';
        $html .= '<div class="modal-dialog modal-xl" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . '">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLabel">' . $tituloModal . '</h5>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<form id="formModalSeleccion">';
        $html .= '<table id="tablaModalSeleccion" class="table table-striped table-hover display" style="width:100%">';
        
        // Añadir las columnas de la tabla y cerrar etiquetas necesarias
        $columnas = columnasTablas($modulo, $submodulo, $subsubmodulo, $uso);
        $orden = ordenTablas($modulo, $submodulo, $subsubmodulo, $uso);
        $html .= '<thead><tr>' . $columnas . '</tr></thead></table>';

        $html .= '</form></div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$(\'#modalSeleccion\').modal(\'hide\').remove();">Cancelar</button>';
        $html .= '<button type="button" class="btn btn-primary" data-dismiss="modal" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalSeleccion\')' . $parametrosAdicionales . '); $(\'#modalSeleccion\').modal(\'hide\');" data-dismiss="modal">Aceptar</button>';
        $html .= '</div></div></div></div>';

        $this->response->remove('modalSeleccion');
        $this->response->append("modales", "innerHTML", $html);
        $this->response->script('$("#modalSeleccion").modal("show");');

        // Configuración de DataTable con parámetros comunes
        $ajaxUrl = '/controladores/globales/tablas.php?modulo=' . $modulo . '&submodulo=' . $submodulo . '&subsubmodulo=' . $subsubmodulo . '&idalmacen=' . $idalmacen . '&filtro=' . $filtroSeleccionado . '&uso=' . $uso;
        $tableId = '#tablaModalSeleccion';
        $pageLength = 7;
        $lengthMenu = '[[7, 10, 20], [7, 10, 20]]';

        $columnDefs = "
            columnDefs: [
                {
                    targets: -1,
                    render: function (data, type, row, meta) {
                        return " . ($multiple ? "'<input type=\"checkbox\" name=\"seleccion[]\" value=\"' + data + '\" />'" : "'<input type=\"radio\" name=\"seleccion\" value=\"' + data + '\" />'") . ";
                    }
                }
            ],
            initComplete: function () {
                var api = this.api();
                // Cambiar el título de la última columna a 'Selección'
                $(api.column(-1).header()).html('Selección');
            }
        ";

        // Script para inicializar DataTable
        $this->response->script("
            if ($.fn.DataTable.isDataTable('$tableId')) {
                $('$tableId').DataTable().destroy();
            }
            $('$tableId').DataTable({
                ajax: '$ajaxUrl',
                responsive: true,
                processing: true,
                serverSide: true,
                pageLength: $pageLength,
                lengthMenu: $lengthMenu,
                language: {
                    url: \"/plugins/datatables/es-ES.json\"
                },
                order: [[".$orden['indice'].", '".$orden['orden']."']],
                $columnDefs
            });
        ");
        // Agregar evento para eliminar la modal después de ocultarla
        $this->response->script('
        if (!$("#modalSeleccion").data("evento-registrado")) {
            $("#modalSeleccion").on("hidden.bs.modal", function () {
                $(this).remove();
            });
            $("#modalSeleccion").data("evento-registrado", true);
        }
        ');

        return $this->response;
    }
    

    public function modalFormulario($campos, $titulo, $funcionCallBack, $parametrosAdicionales = '')
    {
        // Crear el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalFormulario" tabindex="-1" aria-labelledby="modalFormularioLabel">';
        $tamanoModal = count($campos) > 6 ? 'modal-lg' : '';
        $html .= '<div class="modal-dialog ' . $tamanoModal . '">';

        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . '">';
        $html .= '<h5 class="modal-title" id="modalFormularioLabel">' . $titulo . '</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<!-- Aquí van los campos para la modal -->';
        $html .= '<form id="formModalFormulario">';

        $usarDosColumnas = count($campos) > 6;

        if ($usarDosColumnas) {
            $html .= '<div class="row">';
        }

        foreach ($campos as $campo) {
            $columna = $usarDosColumnas ? 'col-md-6' : '';
            $html .= '<div class="mb-3 ' . $columna . '">';
            $html .= '<label for="' . $campo['id'] . '" class="form-label">' . $campo['label'] . '</label>';

            $readOnly = isset($campo['readOnly']) && $campo['readOnly'] ? ' readonly' : '';
            $disabled = isset($campo['disabled']) && $campo['disabled'] ? ' disabled' : '';

            switch ($campo['type']) {
                case 'textarea':
                    $html .= '<textarea class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $readOnly . '>' . $campo['value'] . '</textarea>';
                    break;

                case 'select':
                    $html .= '<select class="form-select" id="' . $campo['id'] . '" name="' . $campo['id'] . '"' . $disabled . '>';

                    $options = $campo['options'];
                    $options = preg_replace_callback(
                        '/<option\s+value="([^"]+)"/',
                        function ($matches) use ($campo) {
                            return $matches[0] . ($matches[1] == $campo['value'] ? ' selected' : '');
                        },
                        $options
                    );

                    $html .= $options;
                    $html .= '</select>';
                    break;

                default:
                    $html .= '<input type="' . $campo['type'] . '" class="form-control" id="' . $campo['id'] . '" name="' . $campo['id'] . '" value="' . $campo['value'] . '"' . $readOnly . $disabled . '>';
                    break;
            }

            $html .= '</div>';
        }

        if ($usarDosColumnas) {
            $html .= '</div>'; // Cierre de .row
        }

        $html .= '</form>';
        $html .= '</div>'; // Cierre de modal-body
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
        $html .= '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalFormulario\')' . $parametrosAdicionales . ');">Guardar</button>';
        $html .= '</div>';
        $html .= '</div>'; // Cierre de modal-content
        $html .= '</div>'; // Cierre de modal-dialog
        $html .= '</div>'; // Cierre de modal

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
        // Obtener el tema actual (dark o light)
        $tema = getBackground();
        $backgroundColor = $tema == 'dark' ? '#212529' : '#ffffff'; // Fondo según tema
        $textColor = $tema == 'dark' ? '#ffffff' : '#000000';       // Texto según tema

        // Quitar el foco del elemento activo
        $this->response->script('$(document.activeElement).blur();');

        $mostrarTimer = $timer ? 'timer: 2000,' : '';
        $enfoqueScript = $elementoEnfocar !== null ? 'setTimeout(function() { $("#' . $elementoEnfocar . '").focus(); }, 2);' : '';
        $redireccionScript = $redireccion !== null ? 'window.location.href = "' . $redireccion . '";' : '';

        $script = 'Swal.fire({
            title: "' . $titulo . '",
            html: "' . $mensaje . '",  // Usar "html" en lugar de "text"
            showConfirmButton: ' . ($boton ? 'true' : 'false') . ',
            ' . $mostrarTimer . '
            icon: "' . $icono . '",
            background: "' . $backgroundColor . '",
            color: "' . $textColor . '"
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
        // Obtener el tema actual (dark o light)
        $tema = getBackground();
        $backgroundColor = $tema == 'dark' ? '#212529' : '#ffffff'; // Fondo según tema
        $textColor = $tema == 'dark' ? '#ffffff' : '#000000';       // Texto según tema
    
        // Quitar el foco del elemento activo
        $this->response->script('$(document.activeElement).blur();');
    
        // Construir el script de SweetAlert2 con los parámetros proporcionados
        $script = '
            Swal.fire({
                title: "' . $titulo . '",
                text: "' . $mensaje . '",
                icon: "' . $icono . '",
                showCancelButton: true,
                confirmButtonColor: "#28a745", // Color verde para el botón de confirmación
                cancelButtonColor: "#d33", // Color rojo para el botón de cancelación
                confirmButtonText: "Sí, confirmar", // Texto del botón de confirmación
                cancelButtonText: "Cancelar", // Texto del botón de cancelación
                reverseButtons: true, // Intercambiar posición de los botones
                background: "' . $backgroundColor . '", // Fondo según tema
                color: "' . $textColor . '" // Texto según tema
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamar a la función de confirmación proporcionada
                    ' . $confirmCallback . '
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: "Cancelado",
                        text: "No se han hecho cambios ;)",
                        icon: "error",
                        background: "' . $backgroundColor . '", // Fondo según tema
                        color: "' . $textColor . '" // Texto según tema
                    });
                }
            });
        ';
    
        // Agregar el script de SweetAlert2 a la response
        $this->response->script($script);
    
        // Devolver la response
        return $this->response;
    }
    


    public function modalSeleccion($titulo, $columnas, $data, $funcionCallBack, $seleccion=true, $multiple = false, $parametrosAdicionales = '')
    {
        // Construcción del HTML del modal
        $html = '<div class="modal fade" id="modalSeleccion" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionLabel">';
        $html .= '<div class="modal-dialog modal-xl" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header text-bg-' . getEnfasis() . '">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLabel">' . $titulo . '</h5>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<form id="formModalSeleccion">';
        $html .= '<table id="tablaModalSeleccion" class="table table-striped table-hover display" style="width:100%">';
        $html .= '<thead><tr>';

        // Agrega los encabezados de las columnas
    foreach ($columnas as $columna) {
        $html .= '<th>' . $columna . '</th>';
    }

        // Agrega una columna extra para la selección si $seleccion es true
        if ($seleccion) {
            $html .= '<th>Seleccionar</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Agrega los registros de datos
        foreach ($data as $row) {
            $html .= '<tr>';

            // Define el índice de inicio según la selección
            $i = ($seleccion) ? 1 : 0;
            for ($i; $i < count($row); $i++) {
                $html .= '<td style="white-space: nowrap;">' . $row[$i] . '</td>'; // Añadido nowrap para evitar ruptura de celdas
            }

            // Agrega el input de selección si $seleccion es true
            if ($seleccion) {
                $html .= '<td class="text-center">';
                if ($multiple) {
                    // Si es selección múltiple, el array se llamará "seleccion[]"
                    $html .= '<input type="checkbox" name="seleccion[]" value="' . $row[0] . '">';
                } else {
                    // Si es selección única, el array se llamará "idmodal"
                    $html .= '<input type="radio" name="seleccion" value="' . $row[0] . '">';
                }
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$(\'#modalSeleccion\').modal(\'hide\').remove();">Cancelar</button>';
        if ($seleccion)
	    {
        $html .= '<button type="button" class="btn btn-primary" data-dismiss="modal" onclick="' . $funcionCallBack . '(jaxon.getFormValues(\'formModalSeleccion\')' . $parametrosAdicionales . '); $(\'#modalSeleccion\').modal(\'hide\');">Aceptar</button>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Eliminar el modal anterior si existe
        $this->response->remove('modalSeleccion');
        $this->response->append("modales", "innerHTML", $html);
        $this->response->script('$("#modalSeleccion").modal("show");');

        // Configuración de DataTable
        $this->response->script('$(document).ready(function() {$("#tablaModalSeleccion").DataTable({ pageLength: 7,
        lengthMenu: [[7, 10, 20], [5, 10, 20]], "language": { "url": "/plugins/datatables/es-ES.json" } });});');

        // Agregar evento para eliminar la modal después de ocultarla
        $this->response->script('
            if (!$("#modalSeleccion").data("evento-registrado")) {
                $("#modalSeleccion").on("hidden.bs.modal", function () {
                    $(this).remove();
                });
                $("#modalSeleccion").data("evento-registrado", true);
            }
        ');

        return $this->response;
    }

    










































    



    public function modalSeleccion_OLD($titulo, $columnas, $data, $funcionCallBack, $multiple = false, $parametrosAdicionales = '')
    {
        // Crea el HTML de la ventana modal
        $html = '<div class="modal fade" id="modalSeleccion" tabindex="-1" role="dialog" aria-labelledby="modalSeleccionLabel">';
        $html .= '<div class="modal-dialog modal-lg" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header bg-secondary">';
        $html .= '<h5 class="modal-title" id="modalSeleccionLabel">' . $titulo . '</h5>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<!-- Aquí va la tabla para la modal -->';
        $html .= '<form id="formModalSeleccion">';
        $html .= '<table id="tablaModalSeleccion" class="table table-striped table-hover display" style="width:100%">';
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
        $this->response->append("modales", "innerHTML", $html);
        $this->response->script('$(document).ready(function() {$("#tablaModalSeleccion").DataTable({ "language": { "url": "/plugins/datatables/es-ES.json" } });});');
        $this->response->script('$( "#modalSeleccion" ).modal("show");');
        return $this->response;
    }

    
    public function deshabilitaSelect($nombreDelSelect)
    {
        $this->response->script("
            (function() {
                const select = $('#$nombreDelSelect'); // Usamos jQuery para compatibilidad con Select2

                // Verificar si el select está utilizando Select2
                if (select.hasClass('select2-hidden-accessible')) {
                    // Es un Select2
                    const select2Container = select.next('.select2-container');

                    // Deshabilitar la interacción con el Select2
                    select2Container.off('click'); // Evitar que se abra el dropdown
                    select2Container.css('pointer-events', 'none'); // Evitar cualquier interacción
                    select2Container.css('cursor', 'not-allowed'); // Cambiar el cursor a 'not-allowed'
                } else {
                    // Es un select normal
                    const nativeSelect = select.get(0); // Obtener el elemento nativo del select

                    // Guardar las funciones de los eventos en propiedades del select
                    nativeSelect.handleMousedown = function(e) {
                        e.preventDefault(); // Evita que se abra el dropdown
                        this.blur(); // Quita el foco del select
                    };

                    nativeSelect.handleKeydown = function(e) {
                        e.preventDefault(); // Evita que se cambie la selección con el teclado
                    };

                    // Agregar los eventos
                    nativeSelect.addEventListener('mousedown', nativeSelect.handleMousedown);
                    nativeSelect.addEventListener('keydown', nativeSelect.handleKeydown);

                    // Cambiar el cursor a 'not-allowed' para indicar que no es interactivo
                    nativeSelect.style.cursor = 'not-allowed';
                }
            })();
        ");
        return $this->response;
    }

    public function habilitaSelect($nombreDelSelect)
    {
        $this->response->script("
            (function() {
                const select = $('#$nombreDelSelect'); // Usamos jQuery para compatibilidad con Select2

                // Verificar si el select está utilizando Select2
                if (select.hasClass('select2-hidden-accessible')) {
                    // Es un Select2
                    const select2Container = select.next('.select2-container');

                    // Habilitar la interacción con el Select2
                    select2Container.on('click', function(e) {
                        select.select2('open'); // Abrir el dropdown al hacer clic
                    });
                    select2Container.css('pointer-events', 'auto'); // Restaurar interacción
                    select2Container.css('cursor', 'pointer'); // Restaurar cursor predeterminado
                } else {
                    // Es un select normal
                    const nativeSelect = select.get(0); // Obtener el elemento nativo del select

                    // Restaurar la interacción con el select
                    if (nativeSelect.handleMousedown) {
                        nativeSelect.removeEventListener('mousedown', nativeSelect.handleMousedown); // Eliminar evento personalizado
                    }
                    if (nativeSelect.handleKeydown) {
                        nativeSelect.removeEventListener('keydown', nativeSelect.handleKeydown); // Eliminar evento personalizado
                    }

                    // Restaurar el cursor predeterminado
                    nativeSelect.style.cursor = 'default';
                }
            })();
        ");

        return $this->response;
    }
    

}


$jaxon->register(Jaxon::CALLABLE_CLASS, alkesGlobal::class);
