<?php
session_start();
header("Content-Type: application/json");
//NO MOVER CODIGO//
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION["user_id"])) {
  echo json_encode([
    "success" => false,
    "message" => "No se pudo identificar el usuario. Por favor, inicia sesión nuevamente."
  ]);
  exit;
}

$usuario_id = $_SESSION["user_id"];
$curso_id   = $_POST["curso_id"] ?? null;

if (!$curso_id) {
  echo json_encode([
    "success" => false,
    "message" => "ID de curso no recibido"
  ]);
  exit;
}

$check = $conn->prepare(
  "SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?"
);
$check->execute([$usuario_id, $curso_id]);

if ($check->rowCount() > 0) {
  echo json_encode([
    "success" => false,
    "message" => "Ya estás inscrito en este curso"
  ]);
  exit;
}

$stmt = $conn->prepare(
  "INSERT INTO inscripciones (usuario_id, curso_id) VALUES (?, ?)"
);

try {
  $stmt->execute([$usuario_id, $curso_id]);
  echo json_encode([
    "success" => true,
    "message" => "Inscripción realizada correctamente"
  ]);
} catch (PDOException $e) {
  echo json_encode([
    "success" => false,
    "message" => "Error al inscribirse"
  ]);
}

$conn = null;
