<?php
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_GET["id"])) {
  echo json_encode(["success" => false, "error" => "ID no enviado"]);
  exit;
}

$id = intval($_GET["id"]);

/* ===== CURSO ===== */
$sqlCurso = "SELECT * FROM cursos WHERE id = $id";
$resCurso = $conn->query($sqlCurso);

if (!$resCurso) {
  echo json_encode([
    "success" => false,
    "error" => "Error en curso",
    "mysql" => $conn->error
  ]);
  exit;
}

$curso = $resCurso->fetch_assoc();

/* ===== MODULOS ===== */
$sqlModulos = "SELECT * FROM modulos WHERE curso_id = $id";
$resModulos = $conn->query($sqlModulos);

if (!$resModulos) {
  echo json_encode([
    "success" => false,
    "error" => "Error en módulos",
    "mysql" => $conn->error
  ]);
  exit;
}

$modulos = $resModulos->fetch_all(MYSQLI_ASSOC);

/* ===== EVALUACIONES ===== */
$sqlEval = "SELECT * FROM evaluaciones WHERE curso_id = $id";
$resEval = $conn->query($sqlEval);

if (!$resEval) {
  echo json_encode([
    "success" => false,
    "error" => "Error en evaluaciones",
    "mysql" => $conn->error
  ]);
  exit;
}

$evaluaciones = $resEval->fetch_all(MYSQLI_ASSOC);

/* ===== RESPUESTA ===== */
echo json_encode([
  "success" => true,
  "curso" => $curso,
  "modulos" => $modulos,
  "evaluaciones" => $evaluaciones
]);
