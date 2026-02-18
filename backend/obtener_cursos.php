<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

// Verificar autenticación
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Obtener todos los cursos
$sql = "SELECT 
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
ORDER BY c.creado_en DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Error al obtener cursos: " . $conn->error]);
    exit;
}

$cursos = [];
while ($row = $result->fetch_assoc()) {
    // Contar módulos
    $stmtModulos = $conn->prepare("SELECT COUNT(*) as total FROM modulos WHERE curso_id = ?");
    $stmtModulos->bind_param("i", $row['id']);
    $stmtModulos->execute();
    $modulosResult = $stmtModulos->get_result();
    $modulosCount = $modulosResult->fetch_assoc()['total'];
    
    // Contar evaluaciones
    $stmtEval = $conn->prepare("SELECT COUNT(*) as total FROM evaluaciones WHERE curso_id = ?");
    $stmtEval->bind_param("i", $row['id']);
    $stmtEval->execute();
    $evalResult = $stmtEval->get_result();
    $evalCount = $evalResult->fetch_assoc()['total'];
    
    $cursos[] = [
        'id' => $row['id'],
        'titulo' => $row['titulo'],
        'descripcion' => $row['descripcion'],
        'contenido' => $row['contenido'],
        'portada' => $row['portada'],
        'creado_en' => $row['creado_en'],
        'creador_nombre' => $row['creador_nombre'],
        'total_modulos' => $modulosCount,
        'total_evaluaciones' => $evalCount
    ];
}

$conn->close();

echo json_encode([
    "success" => true,
    "cursos" => $cursos
]);
?>