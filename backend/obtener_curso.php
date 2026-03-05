<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false, "message" => "No autorizado"]);
  exit;
}

$cursoId = intval($_GET["id"] ?? 0);
if ($cursoId <= 0) {
  echo json_encode(["success" => false, "message" => "Curso inválido"]);
  exit;
}

// CURSO
$stmt = $conn->prepare("SELECT id, titulo, descripcion FROM cursos WHERE id = ?");
$stmt->execute([$cursoId]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
  echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
  exit;
}

// MODULOS
$stmt = $conn->prepare("SELECT id, titulo, contenido FROM modulos WHERE curso_id = ? ORDER BY id");
$stmt->execute([$cursoId]);
$curso["modulos"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EVALUACIONES
$stmt = $conn->prepare("
  SELECT pregunta, opciones, respuesta_correcta, puntos
  FROM evaluaciones
  WHERE curso_id = ?
  ORDER BY id
");
$stmt->execute([$cursoId]);
$curso["evaluaciones"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "curso" => $curso]);
