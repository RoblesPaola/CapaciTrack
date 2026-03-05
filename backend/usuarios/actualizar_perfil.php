<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false, "message" => "No autorizado"]);
  exit;
}

$data    = json_decode(file_get_contents("php://input"), true);
$telefono = trim($data["telefono"] ?? "");
$razon   = trim($data["razon_social"] ?? "");
$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare(
  "UPDATE usuarios SET telefono = ?, razon_social = ? WHERE id = ?"
);

try {
  $stmt->execute([$telefono, $razon, $user_id]);
  echo json_encode(["success" => true]);
} catch (PDOException $e) {
  echo json_encode(["success" => false, "message" => "Error al guardar"]);
}
