<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$sql = "
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
ORDER BY c.creado_en DESC";

try {
    $result = $conn->query($sql);
    $cursos = [];

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $stmtModulos = $conn->prepare("SELECT COUNT(*) FROM modulos WHERE curso_id = ?");
        $stmtModulos->execute([$row['id']]);
        $modulosCount = $stmtModulos->fetchColumn();

        $stmtEval = $conn->prepare("SELECT COUNT(*) FROM evaluaciones WHERE curso_id = ?");
        $stmtEval->execute([$row['id']]);
        $evalCount = $stmtEval->fetchColumn();

        $cursos[] = [
            'id'                 => $row['id'],
            'titulo'             => $row['titulo'],
            'descripcion'        => $row['descripcion'],
            'contenido'          => $row['contenido'],
            'portada'            => $row['portada'],
            'creado_en'          => $row['creado_en'],
            'creador_nombre'     => $row['creador_nombre'],
            'total_modulos'      => (int)$modulosCount,
            'total_evaluaciones' => (int)$evalCount
        ];
    }

    $conn = null;

    echo json_encode([
        "success" => true,
        "cursos"  => $cursos
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al obtener cursos: " . $e->getMessage()]);
}
?>
