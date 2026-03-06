<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
            i.id AS inscripcion_id,
            p.completado
        FROM cursos c
        LEFT JOIN inscripciones i ON c.id = i.curso_id AND i.usuario_id = ?
        LEFT JOIN progreso p      ON c.id = p.curso_id AND p.usuario_id = ?
        ORDER BY c.creado_en DESC
    ");
    $stmt->execute([$usuario_id, $usuario_id]);

    $cursos = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contenido = json_decode($row['contenido'], true);

        if (is_null($row['inscripcion_id'])) {
            $estado = "no_inscrito";
        } elseif (!is_null($row['completado']) && $row['completado'] !== 'f' && $row['completado'] !== false && $row['completado'] != 0) {
            $estado = "completado";
        } else {
            $estado = "en_progreso";
        }

        $cursos[] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['titulo'],
            'titulo'      => $row['titulo'],
            'descripcion' => $row['descripcion'] ?? 'Sin descripción',
            'imagen'      => $row['portada'],
            'portada'     => $row['portada'],
            'archivo'     => $row['portada'],
            'modulos'     => $contenido['modulos'] ?? [],
            'evaluaciones' => $contenido['evaluaciones'] ?? [],
            'estado'      => $estado,
            'creado_por'  => (int)$row['creado_por'],
            'creado_en'   => $row['creado_en']
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

$conn = null;
?>
