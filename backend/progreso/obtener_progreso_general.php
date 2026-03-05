<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

$usuario_id = $_SESSION["user_id"];

$sql = "
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN completado = 1 THEN 1 ELSE 0 END) AS completados
FROM progreso
WHERE usuario_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  "success"    => true,
  "total"      => (int)$res["total"],
  "completados" => (int)$res["completados"]
]);
