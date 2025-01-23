<?php
require('ssp.class.php');
require_once (__DIR__ .'/funcionesJaxon.php');
session_start();


global $database; // Usaremos esta variable para acceder a Medoo

// DB table to use
$modulo = $_GET['modulo'];
$submodulo = $_GET['submodulo'];
$subsubmodulo = $_GET['subsubmodulo'];
$filtro = $_GET['filtro'];
$uso = $_GET['uso'];

// Obtener la tabla según los parámetros proporcionados
$tabla = $database->select("tablas", "*", [
    "modulo" => $modulo,
    "submodulo" => $submodulo,
    "subsubmodulo" => $subsubmodulo
]);

if (empty($tabla)) {
    echo "<th>No hay datos disponibles</th>";
    return;
} else {
    $campos = $database->select("tablas_columnas", "columnas", [
        "uso" => $uso,
        "idtabla" => $tabla[0]['id']
    ]);

}

// Verificar si se encontraron los campos necesarios
if (empty($campos)) {
    echo "<th>No hay columnas disponibles</th>";
    return;
}

// Table's primary key
$primaryKey = 'id';

// Convertir la cadena de columnas en un array
$columnNames = explode(',', $campos[0]);

// Mapear las columnas al formato de DataTables
$columns = array();
foreach ($columnNames as $index => $column) {
    $columns[] = array('db' => $column, 'dt' => $index);
}

// Obtener el filtro de la tabla
$filtroData = $database->select("tablas_filtros", "condicion", [
    "idtabla" => $tabla[0]['id'],
    "nombre" => $filtro
]);

if (empty($filtroData)) {
    echo "<th>No hay filtro disponible</th>";
    return;
}

// Construir la condición WHERE correctamente
$baseWhere = $tabla[0]['condicion_principal'];

// Reemplazar variables en $baseWhere
$where = $baseWhere;
$where = str_replace('{empresa}', "idempresa=" . addslashes($_SESSION['idempresa']), $where);
$where = str_replace('{sucursal}', "idsucursal=" . addslashes($_SESSION['sucursal']), $where);
$where = str_replace('{almacen}', "idalmacen=" . addslashes($_SESSION['almacen']), $where);

// Añadir la condición del filtro
$where .= " AND " . $filtroData[0];


// Configuración de conexión para SSP
$sql_details = array(
    'user' => $_ENV['DB_USERNAME'],
    'pass' => $_ENV['DB_PASSWORD'],
    'db'   => $_ENV['DB_DATABASE'],
    'host' => $_ENV['DB_HOST'] . ':' . $_ENV['DB_PORT']
);

// Pasar la condición WHERE personalizada al método simple
echo json_encode(
    SSP::simple($_GET, $sql_details, $tabla[0]['vista'], $primaryKey, $columns, $where)
);