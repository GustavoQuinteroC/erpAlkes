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

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenMovimientos::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












