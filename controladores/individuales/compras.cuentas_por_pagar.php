<?php
// controlador.php
require_once(__DIR__ . '/../globales/funcionesJaxon.php');
use function Jaxon\jaxon;
use Jaxon\Jaxon;
use Medoo\Medoo;

class comprasCxp extends alkesGlobal
{
    function inializarFormulario()
    {
        $rand = $_GET['rand']; // Obtener el valor dinámico
        registroLog('inializarFormulario', 'Inicializando formulario', []);
        // Obtén la ruta actual dividida en segmentos
        $ruta = explode(DIRECTORY_SEPARATOR, getcwd());

        // Calcular nombres de módulos semidinámicamente
        $modulo = $ruta[(count($ruta) - 2)];
        $submodulo = $ruta[(count($ruta) - 1)];
        $subsubmodulo = null;

        if (validaPermisoEditarModulo($modulo, $submodulo, $subsubmodulo)) {
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-primary btn-sm' type='button' id='btnborrador' name='btnborrador' onclick='JaxoncomprasCxp.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"borrador\");'>
                    <i class='bi bi-floppy'></i> Borrador
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-success btn-sm' type='button' id='btnprocesar' name='btnprocesar' onclick='JaxoncomprasCxp.validar(jaxon.getFormValues(\"formulario{$rand}\"), \"procesar\");'>
                    <i class='bi bi-check2-circle'></i> Procesar
                </button>
            ");
            $this->response->append("botonera-contenedor", "innerHTML", "
                <button class='btn btn-danger btn-sm' type='button' id='btncancelar' name='btncancelar' onclick='JaxoncomprasCxp.modalConfirmacionCancelar();'>
                    <i class='bi bi-x-circle'></i> Cancelar
                </button>
            ");
        } else {
            $this->response->assign('addPartidas', 'disabled', 'disabled');
        }

        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-secondary btn-sm' type='button' id='btnimprimir' name='btnimprimir' onclick='JaxoncomprasCxp.imprimir();'>
                <i class='bi bi-printer'></i> Imprimir
            </button>
        ");

        if ($_GET['id'] == 0) {
            registroLog('inializarFormulario', 'Iniciando un registro nuevo', []);
            $this->response->assign('fecha', 'value', date('Y-m-d'));
            $this->response->script('
                $("#idc_moneda").val("102").trigger("change.select2");
            ');
            $this->response->assign('btncancelar', 'disabled', 'disabled');
            $this->response->assign('btnimprimir', 'disabled', 'disabled');
        } else {
            registroLog('inializarFormulario', 'Consultando un registro ya registrado', []);
            $this->response->script("document.getElementById('cardPartidas').classList.remove('d-none');");

            if (!validarEmpresaPorRegistro("cuentas_cargos", $_GET['id'])) {
                registroLog('inializarFormulario', 'Registro no pertenece a la empresa', []);
                $this->alerta(
                    "¡ERROR GRAVE!",
                    "Este registro no pertenece a esta empresa. Por favor, reporte este problema de inmediato y con la mayor discreción posible; usted será recompensado por ello. Mientras le damos respuesta, es importante que no abandone esta ventana",
                    "error"
                );
                return $this->response;
            } else {
                global $database;
                $cuenta_cargo = $database->get("cuentas_cargos", "*", ["id" => $_GET['id']]);
                $this->response->assign('folio', 'value', $cuenta_cargo['folio']);
                $this->response->assign('fecha', 'value', $cuenta_cargo['fecha']);
                $this->response->assign('fecha_vencimiento', 'value', $cuenta_cargo['fecha_vencimiento']);
                $this->response->assign('documento', 'value', $cuenta_cargo['documento']);
                $this->response->assign('importe', 'value', $cuenta_cargo['importe']);
                $this->response->assign('saldo', 'value', $cuenta_cargo['saldo']);
                $this->response->assign('idc_metodopago', 'value', $cuenta_cargo['idc_metodopago']);
                $this->response->assign('referencia', 'value', $cuenta_cargo['referencia']);
                $this->response->assign('banco', 'value', $cuenta_cargo['banco']);
                $this->response->assign('cuenta_bancaria', 'value', $cuenta_cargo['cuenta_bancaria']);
                $this->response->assign('notas', 'value', $cuenta_cargo['notas']);

                $this->response->script('
                    document.getElementById("idsocio").onchange = null;
                    document.getElementById("idsubcuenta").onchange = null;

                    $("#idsocio").val("' . $cuenta_cargo['idsocio'] . '").trigger("change.select2");
                    $("#idsubcuenta").val("' . $cuenta_cargo['idsubcuenta'] . '").trigger("change.select2");
                    $("#idconcepto").val("' . $cuenta_cargo['idconcepto'] . '").trigger("change.select2");
                    $("#idc_moneda").val("' . $cuenta_cargo['idc_moneda'] . '").trigger("change.select2");

                    document.getElementById("idsocio").onchange = function() {
                        JaxoncomprasCxp.cargarSocio({ seleccion: this.value });
                    };
                    document.getElementById("idsubcuenta").onchange = function() {
                        JaxoncomprasCxp.cargarSubcuenta({ seleccion: this.value }, document.getElementById(\'idsocio\').value);
                    };
                ');

                $this->response->assign("smallTitulos", "innerHTML", $cuenta_cargo['folio']);
                $this->tablaPartidas($_GET['id']);

                switch ($cuenta_cargo['estado']) {
                    case 'Borrador':
                        // No se debe permitir agregar partidas en estado Borrador
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;

                    case 'Activa':
                        // Solo addPartidas habilitado
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        break;

                    case 'Inactiva':
                    case 'Vencida':
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;

                    case 'Pagada':
                    case 'Cancelada':
                        $this->response->assign('btnborrador', 'disabled', 'disabled');
                        $this->response->assign('btnprocesar', 'disabled', 'disabled');
                        $this->response->assign('btncancelar', 'disabled', 'disabled');
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                        break;
                }

                // En todos los estados menos 'Borrador', se bloquea edición
                if ($cuenta_cargo['estado'] != 'Borrador') {
                    $this->response->assign('botonBuscarSocio', 'disabled', 'disabled');
                    $this->response->assign('botonBuscarSubcuenta', 'disabled', 'disabled');

                    // Solo desactiva addPartidas si el estado NO es 'Activa'
                    if ($cuenta_cargo['estado'] != 'Activa') {
                        $this->response->assign('addPartidas', 'disabled', 'disabled');
                    }

                    $this->response->assign('fecha', 'readOnly', 'readOnly');
                    $this->response->assign('fecha_vencimiento', 'readOnly', 'readOnly');
                    $this->response->assign('documento', 'readOnly', 'readOnly');
                    $this->response->assign('importe', 'readOnly', 'readOnly');
                    $this->response->assign('saldo', 'readOnly', 'readOnly');
                    $this->response->assign('referencia', 'readOnly', 'readOnly');
                    $this->response->assign('banco', 'readOnly', 'readOnly');
                    $this->response->assign('cuenta_bancaria', 'readOnly', 'readOnly');
                    $this->response->assign('notas', 'readOnly', 'readOnly');

                    $this->deshabilitaSelect('idsocio');
                    $this->deshabilitaSelect('idsubcuenta');
                    $this->deshabilitaSelect('idconcepto');
                    $this->deshabilitaSelect('idc_moneda');
                    $this->deshabilitaSelect('idc_metodopago');
                }
            }
        }
        return $this->response;
    }




    function modalSeleccionarSocio()
    {
        registroLog('modalSeleccionarSocio', 'Abriendo modal para seleccionar socio', []);
        $this->modalSeleccionServerSide('compras', 'proveedores', '', 0, 'Activos', 'Modal', 'JaxoncomprasCxp.cargarSocio', false, '', 'Selecciona Un Proveedor');
        return $this->response;
    }

    function cargarSocio($form)
    {
        registroLog('cargarSocio', 'Cargando socio seleccionado', ['socioSeleccionado' => $form['seleccion']]);
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsocio").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idsocio").val("' . $form['seleccion'] . '").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsocio").onchange = function() {
                JaxoncomprasCxp.cargarSocio(this.value);
            };
        ');
        $this->response->assign("idsubcuenta", "innerHTML", getSubcuentas($form['seleccion']));
        return $this->response;
    }

    function modalSeleccionarSubcuenta($idsocio)
    {
        global $database;

        registroLog('modalSeleccionarSubcuenta', 'Abriendo modal para seleccionar subcuenta', ['idsocio' => $idsocio]);

        // Validar que $idsocio sea un valor válido
        if (empty($idsocio) || $idsocio == 0) {
            registroLog('modalSeleccionarSubcuenta', 'Socio no válido', ['idsocio' => $idsocio]);
            $this->alerta("¡ERROR!", "Antes debe seleccionar un proveedor válido.", "error");
            return $this->response;
        }

        // Obtener las subcuentas del socio padre con los datos de la tabla socios
        $subcuentas = $database->select("socios_subcuentas", [
            "[>]socios" => ["idsocio_hijo" => "id"]
        ], [
            "socios_subcuentas.id",
            "socios.clave",
            "socios.nombre_comercial",
            "socios_subcuentas.fecha_vencimiento"
        ], [
            "socios_subcuentas.idsocio_padre" => $idsocio,
            "socios_subcuentas.estado" => 'Activo'
        ]);

        // Verificar si hay subcuentas
        if (empty($subcuentas)) {
            registroLog('modalSeleccionarSubcuenta', 'No hay subcuentas registradas', ['idsocio' => $idsocio]);
            $this->alerta("¡SIN SUBCUENTAS!", "Este proveedor no tiene subcuentas registradas.", "warning");
            return $this->response;
        }

        // Encabezados de la tabla (sin incluir ID)
        $columnas = ['Clave', 'Nombre Comercial', 'Estado', 'Fecha Vencimiento'];

        // Construcción de los datos
        $data = array();
        foreach ($subcuentas as $subcuenta) {
            $data[] = array(
                $subcuenta['id'], // ID en el índice 0
                $subcuenta['clave'],
                $subcuenta['nombre_comercial'],
                $subcuenta['estado'],
                date('Y-m-d', strtotime($subcuenta['fecha_vencimiento'])), // Solo la fecha
            );
        }

        // Llamar a la función modalSeleccion para mostrar la modal
        $this->modalSeleccion(
            "Seleccionar Subcuenta",
            $columnas,
            $data,
            "JaxoncomprasCxp.cargarSubcuenta",
            true,
            false,
            ", $idsocio"
        );

        return $this->response;
    }

    function cargarSubcuenta($form, $idsocioPrincipal)
    {
        registroLog('cargarSubcuenta', 'Cargando subcuenta seleccionada', ['subcuentaSeleccionada' => $form['seleccion'], 'idsocioPrincipal' => $idsocioPrincipal]);
        $this->response->script('
            // Desactivar temporalmente el evento onchange para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = null;

            // Asignar valores a los campos select2 sin disparar onchange
            $("#idsubcuenta").val("' . $form['seleccion'] . '").trigger("change.select2");

            // Restaurar el evento onchange original para los selects que tienen onchange y necesiten un change (select2)
            document.getElementById("idsubcuenta").onchange = function() {
                JaxoncomprasCxp.cargarSubcuenta(this.value);
            };
        ');
        return $this->response;
    }


    function modalAgregarAbono($form)
    {
        registroLog('modalAgregarAbono', 'Agregando un nuevo abono a la cuenta', []);
        // Definir los campos para el formulario modal
        $campos = [
            [
                'id' => 'fecha',
                'label' => 'Fecha de aplicacion de pago',
                'type' => 'date',
                'value' => date('Y-m-d'), // Valor por defecto
            ],
            [
                'id' => 'idconcepto',
                'label' => 'Concepto',
                'type' => 'select',
                'options' => getConceptosCuentas('Abono', 'CXP'),
                'value' => '21', // Valor por defecto
            ],
            [
                'id' => 'documento',
                'label' => 'Documento',
                'type' => 'text',
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'referencia',
                'label' => 'Referencia',
                'type' => 'text',
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'idmoneda',
                'label' => 'Moneda',
                'type' => 'select',
                'options' => getCfdiMoneda(),
                'value' => $form['idc_moneda'], // Valor por defecto
            ],
            [
                'id' => 'tipo_cambio',
                'label' => 'Tipo de cambio',
                'type' => 'float',
                'value' => '1', // Valor por defecto
            ],
            [
                'id' => 'monto',
                'label' => 'Monto en la moneda del abono',
                'type' => 'float',
                'value' => '0', // Valor por defecto
            ],
            [
                'id' => 'idformapago',
                'label' => 'Forma de pago',
                'type' => 'select',
                'options' => getCfdiFormaPago(),
                'value' => '', // Valor por defecto
            ],
            [
                'id' => 'banco',
                'label' => 'Banco',
                'type' => 'text',
                'value' => $form['banco'], // Valor por defecto
            ],
            [
                'id' => 'cuenta_bancaria',
                'label' => 'Cuenta bancaria',
                'type' => 'text',
                'value' => $form['cuenta_bancaria'], // Valor por defecto
            ],
            [
                'id' => 'notas',
                'label' => 'Notas adicionales',
                'type' => 'textarea',
                'value' => '', // Valor por defecto
            ],
        ];

        // Título del modal
        $titulo = 'Agregar nuevo abono (cuenta padre '.$form['folio'].')';

        $parametrosAdicionales = ", jaxon.getFormValues('formulario" . $_GET['rand'] . "')";
        // Callback que se ejecutará al guardar
        $funcionCallBack = 'JaxoncomprasCxp.validarAbono'; // Nombre de la función JavaScript

        // Llamar a la función modalFormulario
        $this->modalFormulario($campos, $titulo, $funcionCallBack, $parametrosAdicionales);
        return $this->response;
    }

    function validarAbono($formModal, $formGlobal)
    {
        registroLog('validarAbono', 'Validando si todos los campos del abono cumplen las condiciones', []);

        $reglas = [
            'fecha' => ['obligatorio' => true, 'tipo' => 'date'],
            'idconcepto' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'documento' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'referencia' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'idmoneda' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'tipo_cambio' => ['obligatorio' => true, 'tipo' => 'float', 'max_val' => 100.00, 'pattern' => '/^\d+(\.\d{1,2})?$/'],
            'monto' => ['obligatorio' => true, 'tipo' => 'float', 'pattern' => '/^\d+(\.\d{1,2})?$/'],
            'idformapago' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'banco' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'cuenta_bancaria' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 254],
            'notas' => ['obligatorio' => false, 'tipo' => 'string', 'max' => 500],
        ];

        $resultado = validar_global($formModal, $reglas);
        if($resultado !== true) {
            registroLog('validarAbono', 'Error en validación de campos: ' . $resultado['campo'], []);
            $this->alerta("Error de validación", $resultado['error'], "warning", $resultado['campo']);
        }
        elseif(strtotime($formModal['fecha']) > strtotime(date('Y-m-d'))) {
            registroLog('validarAbono', 'La fecha es mayor que hoy', []);
            $this->alerta("Fecha inválida", "La fecha no puede ser mayor a hoy", "warning", "fecha");
        }
        elseif (($formModal['tipo_cambio'] * $formModal['monto']) > $formGlobal['saldo']) {
            registroLog('validarAbono', 'El monto del abono supera el saldo de la cuenta padre', []);
            $this->alerta("Monto inválido", "El monto del abono no puede superar al saldo de la cuenta padre", "warning", "saldo");
        }
        else{
            registroLog('validarAbono', 'Validación exitosa', []);
            $this->registrarAbono($formModal);
        }
        return $this->response;
    }

    function registrarAbono($formModal)
    {
        global $database;

        registroLog('registrarAbono', 'Iniciando guardado del abono', []);

        $database->pdo->beginTransaction();

        try {

            // Preparar los datos del abono
            $data = [
                'idcuenta_cargo' => isset($_GET['id']) ? $_GET['id'] : 0,
                'idconcepto' => $formModal['idconcepto'],
                'idcreador' => isset($_SESSION['idusuario']) ? $_SESSION['idusuario'] : 0,
                'idreferencia' => 0,
                'idc_moneda' => $formModal['idmoneda'],
                'idc_formapago' => $formModal['idformapago'],
                'folio' => getConsecutivo('compras_cxp_abono'),
                'fecha' => $formModal['fecha'],
                'monto_abono' => $formModal['monto'],
                'tipo_cambio' => $formModal['tipo_cambio'],
                'banco' => $formModal['banco'],
                'cuenta_bancaria' => $formModal['cuenta_bancaria'],
                'estado' => 'Confirmado',
                'documento' => $formModal['documento'],
                'referencia' => $formModal['referencia'],
                'notas' => $formModal['notas'],
            ];

            // Insertar en la tabla de abonos
            $database->insert('cuentas_abonos', $data);
            $insert_id = $database->id();

            registroLog('registrarAbono', 'Abono registrado exitosamente', ['idabono' => $insert_id]);

            $this->alerta(
                "Éxito",
                "El abono ha sido registrado correctamente.",
                "success",
                null,
                true,
                false,
                $_SERVER['REQUEST_URI'] // Esto recarga la misma página
            );

            $database->pdo->commit();
        } catch (PDOException $e) {
            $database->pdo->rollBack();
            registroLog('registrarAbono', 'Error al guardar el abono', ['error' => $e->getMessage()]);
            $this->alerta("Error", "No se pudo guardar el abono. Intenta de nuevo o contacta al administrador del sistema.", "error");
        }

        return $this->response;
    }

    function tablaPartidas()
    {
        registroLog('tablaPartidas', 'Actualizando tabla de partidas', []);

        global $database;

        // Obtener los abonos de la cuenta padre desde la base de datos
        $abonos = $database->select("cuentas_abonos", [
            "[>]cuentas_conceptos" => ["idconcepto" => "id"],
            "[>]usuarios" => ["idcreador" => "id"],
            "[>]cfdi_moneda" => ["idc_moneda" => "id"],
            "[>]cfdi_formapago" => ["idc_formapago" => "id"]
        ], [
            "cuentas_abonos.id",
            "cuentas_abonos.idreferencia",
            "cuentas_conceptos.concepto",
            "usuarios.nombre",
            "cfdi_moneda.c_moneda",
            "cfdi_formapago.descripcion",
            "cuentas_abonos.fecha",
            "cuentas_abonos.documento",
            "cuentas_abonos.referencia",
            "cuentas_abonos.folio",
            "cuentas_abonos.monto_abono",
            "cuentas_abonos.estado",
            "cuentas_abonos.notas"
        ], [
            "cuentas_abonos.idcuenta_cargo" => $_GET['id'],
        ]);

        $script = "tablaPartidas.clear();"; // Limpiar la tabla
        $i = 1;

        foreach ($abonos as $index => $partida) {
            // Definir clase del estado con badge
            $claseEstado = '';
            switch (strtolower($partida['estado'])) {
                case 'pendiente':
                    $claseEstado = 'badge text-bg-warning'; // Amarillo
                    break;
                case 'confirmado':
                    $claseEstado = 'badge text-bg-success'; // Verde
                    break;
                case 'rechazado':
                    $claseEstado = 'badge text-bg-secondary'; // Gris
                    break;
                case 'cancelado':
                    $claseEstado = 'badge text-bg-danger'; // Rojo
                    break;
                default:
                    $claseEstado = 'badge text-bg-light'; // Por si llega otro estado inesperado
                    break;
            }

            $estadoConEstilo = "<span class='$claseEstado'>".htmlspecialchars($partida['estado'])."</span>";


            // Acciones (botones)
            $botones = "
                <button type='button' class='btn btn-sm btn-danger' title='Eliminar'
                    onclick='JaxoncomprasCxp.modalPreCancelarAbono({$partida['id']})'>
                    <i class='bi bi-x-circle'></i>
                </button>
                <button type='button' class='btn btn-sm btn-info' title='Agregar nota'
                    onclick='JaxoncomprasCxp.abrirNotaAbono({$partida['id']})'>
                    <i class='bi bi-sticky'></i>
                </button>";

            // Construcción de la fila según el orden de columnas de la tabla HTML
            $fila = [
                $i,
                htmlspecialchars($partida['folio']),
                $estadoConEstilo,
                htmlspecialchars($partida['concepto']),
                htmlspecialchars($partida['fecha']),
                htmlspecialchars($partida['nombre']),
                htmlspecialchars($partida['documento']),
                htmlspecialchars($partida['referencia']),
                htmlspecialchars($partida['c_moneda']),
                htmlspecialchars($partida['descripcion']),
                number_format($partida['monto_abono'], 2),
                $botones
            ];

            $filaJS = json_encode($fila);
            $script .= "tablaPartidas.row.add($filaJS);";
            $i++;
        }

        $script .= "tablaPartidas.draw();";
        $this->response->script($script);

        return $this->response;
    }

    function modalPreCancelarAbono($idabono)
    {
        global $database;

        registroLog('modalPreCancelarAbono', 'Validando si se puede cancelar el abono', ['idabono' => $idabono]);

        // Obtener el abono
        $abono = $database->get("cuentas_abonos", "*", [
            "id" => $idabono
        ]);

        if (!$abono) {
            registroLog('modalPreCancelarAbono', 'Abono no encontrado', ['idabono' => $idabono]);
            $this->alerta("Error", "El abono no fue encontrado. Por favor, contacta al administrador del sistema.", "error");
            return $this->response;
        }

        // Verificar si está relacionado a otro módulo
        if (!empty($abono['idreferencia']) && !empty($abono['referencia'])) {
            registroLog('modalPreCancelarAbono', 'El abono está vinculado a otro módulo. Cancelación no permitida desde aquí.', ['idabono' => $idabono]);
            $this->alerta(
                "Advertencia",
                "Este abono está vinculado a otro módulo y no puede ser cancelado desde aquí. Por favor, cancélalo en su módulo correspondiente.",
                "warning"
            );
            return $this->response;
        }

        // Si todo está bien, mostrar confirmación
        $this->alertaConfirmacion(
            "¿CANCELAR?",
            "Se cancelará el abono seleccionado, alterando el saldo con el proveedor. ¿Desea continuar?",
            "warning",
            "JaxoncomprasCxp.cancelarAbono($idabono);"
        );

        return $this->response;
    }

    function cancelarAbono($idabono)
    {
        global $database;

        registroLog('cancelarAbono', 'Intentando cancelar abono', ['idabono' => $idabono]);

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            // Verificar que el abono exista
            $abono = $database->get("cuentas_abonos", "*", [
                "id" => $idabono
            ]);

            if (!$abono) {
                registroLog('cancelarAbono', 'Abono no encontrado', ['idabono' => $idabono]);
                $this->alerta("Error", "El abono no fue encontrado, por favor contacta al administrador del sistema.", "error");
                return $this->response;
            }

            // Ya no se valida aquí si está relacionado a otro módulo, porque eso se hizo antes en modalPreCancelarAbono

            // Actualizar el estado del abono a Cancelado
            $database->update("cuentas_abonos", [
                "estado" => "Cancelado",
                "fecha_cancelacion" => date('Y-m-d H:i:s')
            ], [
                "id" => $idabono
            ]);

            registroLog('cancelarAbono', 'Abono cancelado exitosamente', ['idabono' => $idabono]);

            $this->alerta(
                "Éxito",
                "El abono ha sido cancelado correctamente.",
                "success",
                null,
                true,
                false,
                $_SERVER['REQUEST_URI']
            );

            $database->pdo->commit();

        } catch (PDOException $e) {
            $database->pdo->rollBack();
            registroLog('cancelarAbono', 'Error al cancelar el abono', ['error' => $e->getMessage()]);
            $this->alerta("Error", "No se pudo cancelar el abono. Intenta de nuevo o contacta al administrador del sistema.", "error");
        }

        return $this->response;
    }

    function abrirNotaAbono()
    {
        return $this->response;
    }


    function validar($form, $accion)
    {
        registroLog('validar', 'Validando formulario', ['accion' => $accion]);

        // Definir las reglas de validación
        $reglas = [
            'fecha' => ['obligatorio' => true, 'tipo' => 'date'],
            'fecha_vencimiento' => ['obligatorio' => true, 'tipo' => 'date'],
            'idsocio' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idsubcuenta' => ['obligatorio' => false, 'tipo' => 'int', 'min_val' => 1],
            'idconcepto' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_moneda' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'idc_metodopago' => ['obligatorio' => true, 'tipo' => 'int', 'min_val' => 1],
            'documento' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'referencia' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'importe' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 1, 'max_val' => 999999999999.99,],
            'saldo' => ['obligatorio' => true, 'tipo' => 'float', 'min_val' => 1, 'max_val' => 999999999999.99,],
            'banco' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'cuenta_bancaria' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
            'notas' => ['obligatorio' => false, 'tipo' => 'string', 'min' => 1, 'max' => 254],
        ];

        // Validar el formulario
        $resultadoValidacion = validar_global($form, $reglas);

        // Si hay un error en la validación
        if ($resultadoValidacion !== true) {
            registroLog('validar', 'Error en la validación del formulario', ['error' => $resultadoValidacion['error'], 'campo' => $resultadoValidacion['campo']]);
            $this->alerta(
                "Error en la validación",
                $resultadoValidacion['error'],
                "error",
                $resultadoValidacion['campo']
            );
            return $this->response;
        } else {
            if($form['importe']!=$form['saldo'])
            {
                $this->alerta(
                    "Error en la validación",
                    "El importe y el saldo deben de ser iguales",
                    "error",
                    "saldo"
                );
            }
            else
            {
                $this->modalPreGuardar($form, $accion);
            }
        }
        return $this->response;
    }

    function modalPreGuardar($form, $accion)
    {
        registroLog('modalPreGuardar', 'Entrando a la funcion', []);

        if ($accion == 'procesar') {
            registroLog('modalPreGuardar', 'Mostrando modal alerta de confirmación para procesar la cxp', []);

            // Codificar el array PHP como JSON para que pueda ser interpretado en JavaScript
            $formJson = json_encode($form);

            // Escapar comillas dobles para que no rompan el string en JavaScript
            $formJsonEscapado = str_replace('"', '\"', $formJson);

            // Mostrar el mensaje de confirmación
            $this->alertaConfirmacion(
                "¿PROCESAR?",
                "Se procesará la cuenta por pagar, alterando el saldo con el proveedor seleccionado. ¿Desea continuar?",
                "warning",
                "JaxoncomprasCxp.guardar(JSON.parse(\"$formJsonEscapado\"), \"$accion\");"
            );
        }
        return $this->response;
    }


    function guardar($form, $accion)
    {
        global $database;

        registroLog('guardar', 'Guardando cuenta por pagar', ['accion' => $accion, 'formulario' => $form]);

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            $idCxp = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            // Determinar el folio solo si es un nuevo registro
            if ($idCxp == 0) {
                $folio = getConsecutivo('compras_cxp_cargo');
                registroLog('guardar', 'Nueva cxp, folio generado', ['folio' => $folio]);
            } else {
                // Recuperar el folio actual desde la base de datos para mantenerlo
                $folio = $database->get('cuentas_cargos', 'folio', ['id' => $idCxp]);
                registroLog('guardar', 'CXP existente, folio recuperado', ['folio' => $folio]);
            }

            // Preparar los datos para insertar o actualizar

            $data = [
                'idempresa'         => isset($_SESSION['idempresa']) ? (int)$_SESSION['idempresa'] : 0,
                'idsucursal'        => isset($_SESSION['idsucursal']) ? (int)$_SESSION['idsucursal'] : 0,
                'idcreador'         => isset($_SESSION['idusuario']) ? (int)$_SESSION['idusuario'] : 0,
                'idc_moneda'        => isset($form['idc_moneda']) ? (int)$form['idc_moneda'] : 0,
                'idconcepto'        => isset($form['idconcepto']) ? (int)$form['idconcepto'] : 0,
                'idsocio'           => isset($form['idsocio']) ? (int)$form['idsocio'] : 0,
                'idsubcuenta'       => isset($form['idsubcuenta']) ? (int)$form['idsubcuenta'] : 0,
                'idc_metodopago'    => isset($form['idc_metodopago']) ? (int)$form['idc_metodopago'] : 0,
                'folio'             => isset($folio) ? $folio : '', // Aquí usamos el folio ya sea el nuevo o el recuperado
                'estado'            => ($accion == 'borrador') ? 'Borrador' : 'Activa',
                'fecha'             => isset($form['fecha']) ? $form['fecha'] : '0000-00-00',
                'fecha_vencimiento'=> isset($form['fecha_vencimiento']) ? $form['fecha_vencimiento'] : '0000-00-00',
                'documento'         => isset($form['documento']) ? $form['documento'] : '',
                'referencia'        => isset($form['referencia']) ? $form['referencia'] : '',
                'idtablaref'        => isset($form['idtablaref']) ? (int)$form['idtablaref'] : 0,
                'importe'           => isset($form['importe']) ? (float)$form['importe'] : 0.00,
                'saldo'             => isset($form['saldo']) ? (float)$form['saldo'] : 0.00,
                'banco'             => isset($form['banco']) ? $form['banco'] : '',
                'cuenta_bancaria'   => isset($form['cuenta_bancaria']) ? $form['cuenta_bancaria'] : '',
                'notas'             => isset($form['notas']) ? $form['notas'] : ''
            ];


            if ($accion == 'borrador') {
                if ($idCxp == 0) {
                    // Insertar nuevo borrador
                    $database->insert('cuentas_cargos', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Nuevo borrador insertado', ['idcxp' => $insert_id]);
                    $this->alerta(
                        "Éxito",
                        "Cuenta por pagar guardada como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar borrador existente
                    $database->update('cuentas_cargos', $data, ['id' => $idCxp]);
                    registroLog('guardar', 'Borrador actualizado', ['idcxp' => $idCxp]);
                    $this->alerta(
                        "Éxito",
                        "Cuenta por pagar actualizada como borrador correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                }
            } elseif ($accion == 'procesar') {
                if ($idCxp == 0) {
                    // Insertar nuevo procesado
                    $database->insert('cuentas_cargos', $data);
                    $insert_id = $database->id();
                    registroLog('guardar', 'Nueva CXP procesado', ['idcxp' => $insert_id]);
                    $this->alerta(
                        "Éxito",
                        "Cuenta por pagar procesada correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                } else {
                    // Actualizar procesado existente
                    $database->update('cuentas_cargos', $data, ['id' => $idCxp]);
                    registroLog('guardar', 'Cuenta por pagar actualizada y procesada', ['idcxp' => $idCxp]);
                    $this->alerta(
                        "Éxito",
                        "Cuenta por pagar actualizada y procesada correctamente.",
                        "success",
                        null,
                        true,
                        false,
                        "index.php"
                    );
                }
            }

            // Confirmar la transacción
            $database->pdo->commit();
            registroLog('guardar', 'Transacción confirmada', ['idcxp' => $idCxp]);
        } catch (PDOException $e) {
            // Revertir la transacción en caso de error
            $database->pdo->rollBack();
            registroLog('guardar', 'Error en la transacción', ['error' => $e->getMessage()]);
            $this->alerta(
                "Error",
                "No se pudo completar la operación, por favor contacte con el administrador.",
                "error"
            );
        }
        return $this->response;
    }

    function modalConfirmacionCancelar()
    {
        global $database;

        registroLog('modalConfirmacionCancelar', 'Validando si se puede cancelar la cuenta por pagar', ['idcargo' => $_GET['id']]);

        // Obtener el cargo
        $cargo = $database->get("cuentas_cargos", "*", [
            "id" => $_GET['id']
        ]);

        if (!$cargo) {
            registroLog('modalConfirmacionCancelar', 'Cargo no encontrado', ['idcargo' => $_GET['id']]);
            $this->alerta("Error", "La cuenta por pagar no fue encontrada. Por favor, contacta al administrador del sistema.", "error");
            return $this->response;
        }

        // Verificar si está relacionado a otro módulo
        if (!empty($cargo['idreferencia']) && !empty($cargo['referencia'])) {
            registroLog('modalConfirmacionCancelar', 'La cuenta por pagar está vinculada a otro módulo. Cancelación no permitida desde aquí.', ['idcargo' => $_GET['id']]);
            $this->alerta(
                "Advertencia",
                "Esta cuenta por pagar está vinculada a otro módulo y no puede ser cancelada desde aquí. Por favor, cancélala en su módulo correspondiente.",
                "warning"
            );
            return $this->response;
        }

        // Si todo está bien, mostrar confirmación
        $this->alertaConfirmacion(
            "¿CANCELAR?",
            "Se cancelará esta cuenta por pagar, alterando el saldo con el proveedor. ¿Desea continuar?",
            "warning",
            "JaxoncomprasCxp.cancelarCargo();"
        );

        return $this->response;
    }

    function cancelarCargo()
    {
        global $database;

        registroLog('cancelarCargo', 'Intentando cancelar cuenta por pagar', ['idcargo' => $_GET['id']]);

        // Iniciar la transacción
        $database->pdo->beginTransaction();

        try {
            // Verificar que el cargo exista
            $cargo = $database->get("cuentas_cargos", "*", [
                "id" => $_GET['id']
            ]);

            if (!$cargo) {
                registroLog('cancelarCargo', 'Cuenta por pagar no encontrada', ['idcargo' => $_GET['id']]);
                $this->alerta("Error", "La cuenta por pagar no fue encontrada, por favor contacta al administrador del sistema.", "error");
                return $this->response;
            }

            // Verificar que no esté ya cancelada
            if ($cargo['estado'] == 'Cancelada') {
                registroLog('cancelarCargo', 'La cuenta por pagar ya está cancelada', ['idcargo' => $_GET['id']]);
                $this->alerta("Advertencia", "Esta cuenta por pagar ya está cancelada.", "warning");
                return $this->response;
            }

            // Verificar que no tenga abonos confirmados (excepto si están cancelados)
            $abonosConfirmados = $database->count("cuentas_abonos", [
                "idcuenta_cargo" => $_GET['id'],
                "estado" => "Confirmado"
            ]);

            if ($abonosConfirmados > 0) {
                registroLog('cancelarCargo', 'La cuenta por pagar tiene abonos confirmados', ['idcargo' => $_GET['id']]);
                $this->alerta(
                    "Error",
                    "No se puede cancelar esta cuenta por pagar porque tiene abonos confirmados. Primero cancela los abonos asociados.",
                    "error"
                );
                return $this->response;
            }

            // Actualizar el estado del cargo a Cancelada
            $database->update("cuentas_cargos", [
                "estado" => "Cancelada",
                "fecha_cancelacion" => date('Y-m-d H:i:s')
            ], [
                "id" => $_GET['id']
            ]);

            registroLog('cancelarCargo', 'Cuenta por pagar cancelada exitosamente', ['idcargo' => $_GET['id']]);

            $this->alerta(
                "Éxito",
                "La cuenta por pagar ha sido cancelada correctamente.",
                "success",
                null,
                true,
                false,
                $_SERVER['REQUEST_URI']
            );

            $database->pdo->commit();

        } catch (PDOException $e) {
            $database->pdo->rollBack();
            registroLog('cancelarCargo', 'Error al cancelar la cuenta por pagar', ['error' => $e->getMessage()]);
            $this->alerta("Error", "No se pudo cancelar la cuenta por pagar. Intenta de nuevo o contacta al administrador del sistema.", "error");
        }

        return $this->response;
    }

}

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, comprasCxp::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












