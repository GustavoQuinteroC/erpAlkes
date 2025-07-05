<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../globales/funcionesJaxon.php');

$q = strtolower($_GET["term"] ?? '');
$columna = $_GET["columna"] ?? '';

if (!$q || !$columna) {
    echo json_encode([]);
    exit;
}

global $database;

$registros = $database->select('productos', ['id', 'codigo_barras', 'nombre', 'descripcion'], [
    "AND" => [
        "estado" => "Activo",
        "idempresa" => $_SESSION['idempresa'],
        "OR" => [
            "codigo_barras[~]" => $q,
            "nombre[~]" => $q,
            "descripcion[~]" => $q
        ]
    ],
    "LIMIT" => 10
]);

$items = [];

if ($columna == 'codigo_barras') {
    foreach ($registros as $r) {
        $items[] = [
            "label" => $r['codigo_barras'] . " - " . $r['nombre'] . " (" . $r['descripcion'] . ")",
            "value" => $r['nombre'],
            "id" => $r['id']
        ];
    }
}

if ($columna == 'nombre') {
    foreach ($registros as $r) {
        $items[] = [
            "label" => $r['nombre'] . " - " . $r['codigo_barras'] . " (" . $r['descripcion'] . ")",
            "value" => $r['codigo_barras'],
            "id" => $r['id']
        ];
    }
}

if ($columna == 'descripcion') {
    foreach ($registros as $r) {
        $items[] = [
            "label" => $r['descripcion'] . " - " . $r['nombre'] . " (" . $r['codigo_barras'] . ")",
            "value" => $r['descripcion'],
            "id" => $r['id']
        ];
    }
}

echo json_encode($items);
