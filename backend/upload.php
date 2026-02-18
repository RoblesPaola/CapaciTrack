<?php
$tipo = $_POST['tipo']; // imagenes | pdf | videos
$basePath = realpath(__DIR__ . '/../uploads/contenidos');
$targetDir = $basePath . '/' . $tipo;

if (!file_exists($targetDir)) {
  mkdir($targetDir, 0777, true);
}

$file = $_FILES['archivo'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$nombre = uniqid() . '.' . $ext;

move_uploaded_file($file['tmp_name'], $targetDir . '/' . $nombre);

echo json_encode([
  "url" => "http://localhost/capacitrack/backend/uploads/contenidos$tipo/$nombre"
]);
