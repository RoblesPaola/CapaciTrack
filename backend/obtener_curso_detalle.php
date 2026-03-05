<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$curso_id = $_GET['id'] ?? null;

if (!$curso_id || !is_numeric($curso_id)) {
    echo json_encode(["success" => false, "message" => "ID de curso inválido"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.titulo,
        c.descripcion,
        c.contenido,
        c.portada,
        c.creado_en,
        c.creado_por,
        u.nombre as creador_nombre
    FROM cursos c
    LEFT JOIN usuarios u ON c.creado_por = u.id
    WHERE c.id = ?
");
$stmt->execute([$curso_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
    exit;
}

$curso = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtModulos = $conn->prepare("
    SELECT id, titulo, descripcion, tipo_contenido, archivo
    FROM modulos
    WHERE curso_id = ?
    ORDER BY id ASC
");
$stmtModulos->execute([$curso_id]);
$modulos = $stmtModulos->fetchAll(PDO::FETCH_ASSOC);

$stmtEval = $conn->prepare("
    SELECT id, curso_id, tipo, pregunta, opciones, respuesta_correcta, puntos, imagen
    FROM evaluaciones
    WHERE curso_id = ?
    ORDER BY id ASC
");
$stmtEval->execute([$curso_id]);
$evaluaciones = $stmtEval->fetchAll(PDO::FETCH_ASSOC);

$curso['modulos']      = $modulos;
$curso['evaluaciones'] = $evaluaciones;

$conn = null;

echo json_encode([
    "success" => true,
    "curso"   => $curso
]);
?>
