<?php
session_start();
require_once __DIR__ . "/../config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$usuario_id = intval($_SESSION['user_id']);

try {

    $sql = "
        SELECT 
            c.id AS curso_id,
            c.titulo AS curso_nombre,
            p.porcentaje,
            p.completado
        FROM progreso p
        INNER JOIN cursos c ON p.curso_id = c.id
        WHERE p.usuario_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cursos = [];
    $suma_porcentaje = 0;
    $cursos_activos = 0;

    while ($row = $result->fetch_assoc()) {

        $cursos[] = $row;

        // Solo contar cursos NO completados para progreso general
       $suma_porcentaje += intval($row['porcentaje']);
$cursos_activos++;
    }

    $porcentaje_general = 0;

    if ($cursos_activos > 0) {
        $porcentaje_general = round($suma_porcentaje / $cursos_activos);
    }

    echo json_encode([
        "success" => true,
        "cursos" => $cursos,
        "total_modulos" => 0,
        "modulos_completados" => 0,
        "porcentaje" => $porcentaje_general
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
