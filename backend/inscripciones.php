<?php
session_start();
header("Content-Type: application/json");
//NO MOVER CODIGO//
require_once __DIR__ . '/config/db.php';

// ✅ Validar sesión
if (!isset($_SESSION["user_id"])) {
  echo json_encode([
    "success" => false,
    "message" => "No se pudo identificar el usuario. Por favor, inicia sesión nuevamente."
  ]);
  exit;
}

// ✅ USAR EL ID DE LA SESIÓN
$usuario_id = $_SESSION["user_id"];
$curso_id   = $_POST["curso_id"] ?? null;

if (!$curso_id) {
  echo json_encode([
    "success" => false,
    "message" => "ID de curso no recibido"
  ]);
  exit;
}

// 🔍 Verificar si ya está inscrito
$check = $conn->prepare(
  "SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?"
);
$check->bind_param("ii", $usuario_id, $curso_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo json_encode([
    "success" => false,
    "message" => "Ya estás inscrito en este curso"
  ]);
  exit;
}

// ✅ Insertar inscripción
$stmt = $conn->prepare(
  "INSERT INTO inscripciones (usuario_id, curso_id) VALUES (?, ?)"
);
$stmt->bind_param("ii", $usuario_id, $curso_id);

if ($stmt->execute()) {
  echo json_encode([
    "success" => true,
    "message" => "Inscripción realizada correctamente"
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "Error al inscribirse"
  ]);
}

$stmt->close();
$conn->close();
