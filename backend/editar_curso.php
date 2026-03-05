<?php
header("Content-Type: application/json");
require_once __DIR__ . "/config/db.php";

$data        = json_decode(file_get_contents("php://input"), true);
$id          = $data["id"] ?? null;
$titulo      = trim($data["titulo"] ?? "");
$descripcion = trim($data["descripcion"] ?? "");

if (!$id || !$titulo || !$descripcion) {
  echo json_encode(["success" => false, "message" => "Datos incompletos"]);
  exit;
}

$stmt = $conn->prepare("UPDATE cursos SET titulo = ?, descripcion = ? WHERE id = ?");

try {
  $stmt->execute([$titulo, $descripcion, $id]);
  echo json_encode(["success" => true]);
} catch (PDOException $e) {
  echo json_encode(["success" => false, "message" => "Error al actualizar"]);
}
