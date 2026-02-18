<?php
$ruta = $_GET['ruta'] ?? '';

$base = realpath(__DIR__ . '/uploads/contenido/');
$archivo = realpath(__DIR__ . '/uploads/contenido/' . $ruta);

if (!$archivo || strpos($archivo, $base) !== 0 || !file_exists($archivo)) {
  http_response_code(404);
  exit('Archivo no encontrado');
}

$mime = mime_content_type($archivo);
header("Content-Type: $mime");
header("Content-Length: " . filesize($archivo));

readfile($archivo);
exit;
