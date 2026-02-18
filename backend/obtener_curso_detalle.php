<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

// Verificar autenticación
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Obtener ID del curso
$curso_id = $_GET['id'] ?? null;

if (!$curso_id || !is_numeric($curso_id)) {
    echo json_encode(["success" => false, "message" => "ID de curso inválido"]);
    exit;
}

// Obtener información del curso
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

$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
    exit;
}

$curso = $result->fetch_assoc();

// Obtener módulos del curso
$stmtModulos = $conn->prepare("
    SELECT id, titulo, descripcion, tipo_contenido, archivo
    FROM modulos
    WHERE curso_id = ?
    ORDER BY id ASC
");

$stmtModulos->bind_param("i", $curso_id);
$stmtModulos->execute();
$resultModulos = $stmtModulos->get_result();

$modulos = [];
while ($mod = $resultModulos->fetch_assoc()) {
    $modulos[] = $mod;
}

// Obtener evaluaciones del curso
$stmtEval = $conn->prepare("
    SELECT id, tipo, pregunta, opciones, respuesta_correcta
    FROM evaluaciones
    WHERE curso_id = ?
    ORDER BY id ASC
");

$stmtEval->bind_param("i", $curso_id);
$stmtEval->execute();
$resultEval = $stmtEval->get_result();

$evaluaciones = [];
while ($eval = $resultEval->fetch_assoc()) {
    $evaluaciones[] = $eval;
}

// Agregar módulos y evaluaciones al curso
$curso['modulos'] = $modulos;
$curso['evaluaciones'] = $evaluaciones;

$stmt->close();
$stmtModulos->close();
$stmtEval->close();
$conn->close();

echo json_encode([
    "success" => true,
    "curso" => $curso
]);
?>