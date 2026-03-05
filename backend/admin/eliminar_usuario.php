<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";
//NO MOVER//

$data = json_decode(file_get_contents("php://input"), true);
$id   = $data["id"] ?? 0;

if (!$id) {
  echo json_encode(["success" => false]);
  exit;
}

try {
    $conn->prepare("DELETE FROM progreso WHERE usuario_id = ?")->execute([$id]);
    $conn->prepare("DELETE FROM inscripciones WHERE usuario_id = ?")->execute([$id]);
    $conn->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
