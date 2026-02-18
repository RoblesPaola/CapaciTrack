<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode([]);
    exit;
}

$usuario_id = $_SESSION["user_id"];

try {

    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.titulo,
            c.descripcion, 
            c.contenido,
            c.portada,
            c.creado_por,
            c.creado_en,
            p.completado
        FROM cursos c
        LEFT JOIN progreso p 
            ON c.id = p.curso_id 
            AND p.usuario_id = ?
        ORDER BY c.creado_en DESC
    ");

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $queryResult = $stmt->get_result();

    $cursos = [];

    while ($row = $queryResult->fetch_assoc()) {

        $contenido = json_decode($row['contenido'], true);

        if (is_null($row['completado'])) {
            $estado = "no_inscrito";
        } elseif ($row['completado'] == 0) {
            $estado = "en_progreso";
        } else {
            $estado = "completado";
        }

        $cursos[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['titulo'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'] ?? 'Sin descripción',
            'imagen' => $row['portada'],
            'portada' => $row['portada'],
            'archivo' => $row['portada'],
            'modulos' => $contenido['modulos'] ?? [],
            'evaluaciones' => $contenido['evaluaciones'] ?? [],
            'estado' => $estado,
            'creado_por' => (int)$row['creado_por'],
            'creado_en' => $row['creado_en']
        ];
    }

    echo json_encode($cursos, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener cursos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
