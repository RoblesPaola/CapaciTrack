<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";
//NO MOVER//
$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"] ?? 0;

if (!$id) {
  echo json_encode(["success" => false]);
  exit;
}

$conn->query("DELETE FROM progreso WHERE usuario_id = $id");
$conn->query("DELETE FROM inscripciones WHERE usuario_id = $id");
$conn->query("DELETE FROM usuarios WHERE id = $id");

echo json_encode(["success" => true]);
