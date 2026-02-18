<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

$usuario_id = $_SESSION["user_id"];

$sql = "
SELECT 
  COUNT(*) AS total,
  SUM(completado = 1) AS completados
FROM progreso
WHERE usuario_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode([
  "success" => true,
  "total" => (int)$res["total"],
  "completados" => (int)$res["completados"]
]);
