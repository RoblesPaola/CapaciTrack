<?php
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_GET["id"])) {
  echo json_encode(["success" => false, "error" => "ID no enviado"]);
  exit;
}

$id = intval($_GET["id"]);

try {

    // CURSO
    $stmtCurso = $conn->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmtCurso->execute([$id]);
    $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        echo json_encode(["success" => false, "error" => "Curso no encontrado"]);
        exit;
    }

    // MODULOS
    $stmtModulos = $conn->prepare("SELECT * FROM modulos WHERE curso_id = ?");
    $stmtModulos->execute([$id]);
    $modulos = $stmtModulos->fetchAll(PDO::FETCH_ASSOC);

    // EVALUACIONES
    $stmtEval = $conn->prepare("SELECT * FROM evaluaciones WHERE curso_id = ?");
    $stmtEval->execute([$id]);
    $evaluaciones = $stmtEval->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"      => true,
        "curso"        => $curso,
        "modulos"      => $modulos,
        "evaluaciones" => $evaluaciones
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
