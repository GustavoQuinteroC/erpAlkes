<?php
session_start();
// controlador.php
require_once(__DIR__ .'/../globales/funcionesJaxon.php');
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
            }
        }
        $rand = $_GET['rand']; // Obtener el valor dinámico
        $this->response->append("botonera-contenedor", "innerHTML", "
            <button class='btn btn-primary btn-sm' type='button' value='Guardar' onclick='JaxonalmacenAlmacenes.validar(jaxon.getFormValues(\"formulario{$rand}\"));'>
                <i class='bi bi-save'></i> Guardar
            </button>
        ");
        return $this->response;
    }

    function asignarTodos()
    {
        global $database;

        // Obtén los IDs ya existentes en el array de sesión para excluirlos
        $excluir_ids = array_column($_SESSION['partidas'.$_GET['rand']], 'idproducto');

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
            $_SESSION['partidas'.$_GET['rand']][] = [
                'iddb' => 0,
                'idproducto' => $idproducto,
                'existencia' => 0,
                'estado' => 'Activo',
            ];
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
                // Botón según el estado del producto
                if ($infoProducto['estado'] === 'Activo') {
                    // Mostrar X si está Activo
                    $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="JaxonalmacenAlmacenes.eliminarImpuesto(' . $index . ');">';
                    $html .= '<i class="bi bi-trash"></i>'; // X de eliminación
                    $html .= '</button>';
                } elseif ($infoProducto['estado'] === 'Inactivo') {
                    // Mostrar palomita si está Inactivo
                    $html .= '<button type="button" class="btn btn-sm btn-success" onclick="JaxonalmacenAlmacenes.modalEditImpuesto(' . $index . ');">';
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




    function modalAddProducto()
    {
        global $database;
        $this->modalSeleccionServerSide('almacén', 'productos', '', 'Principal', 'Modal', '', false, '', 'Seleccionar Productos');
        return $this->response;
    }

}


$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_CLASS, almacenAlmacenes::class);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}












