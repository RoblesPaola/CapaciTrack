<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_FILES['archivo'])) {
    echo json_encode([
        "ok" => false,
        "error" => "No se recibió ningún archivo"
    ]);
    exit;
}

$dir = __DIR__ . "/uploads/contenidos/";

// crear carpeta si no existe
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$archivo = $_FILES['archivo'];

if ($archivo['error'] !== 0) {
    echo json_encode([
        "ok" => false,
        "error" => "Error al subir archivo",
        "code" => $archivo['error']
    ]);
    exit;
}

$nombreSeguro = time() . "_" . basename($archivo['name']);
$rutaFinal = $dir . $nombreSeguro;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
    echo json_encode([
        "ok" => false,
        "error" => "No se pudo mover el archivo"
    ]);
    exit;
}

echo json_encode([
    "ok" => true,
    "ruta" => "uploads/contenidos/" . $nombreSeguro
]);
