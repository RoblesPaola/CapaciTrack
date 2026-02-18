<?php
session_start();
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false, "message" => "No autorizado"]);
  exit;
}

$curso_id = $_GET["curso_id"] ?? null;

if (!$curso_id) {
  echo json_encode(["success" => false, "message" => "Curso inválido"]);
  exit;
}

/* ===== CURSO ===== */
$stmt = $conn->prepare("
  SELECT id, titulo, descripcion, portada
  FROM cursos
  WHERE id = ?
");

if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Error en consulta curso"]);
  exit;
}

$stmt->bind_param("i", $curso_id);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();

if (!$curso) {
  echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
  exit;
}

/* ===== MODULOS ===== */
$stmt = $conn->prepare("
  SELECT id, titulo
  FROM modulos
  WHERE curso_id = ?
");

if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Error en consulta módulos"]);
  exit;
}

$stmt->bind_param("i", $curso_id);
$stmt->execute();
$modulos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ===== RESPUESTA FINAL ===== */
echo json_encode([
  "success" => true,
  "curso" => $curso,
  "modulos" => $modulos
]);
