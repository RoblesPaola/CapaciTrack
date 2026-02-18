<?php
session_start();
require_once __DIR__ . "/config/db.php";

header("Content-Type: application/json");

// 🔐 Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
    exit;
}

$usuario_id = intval($_SESSION['user_id']);

try {

    $sql = "
        SELECT 
            c.id,
            c.usuario_id,
            c.curso_id,
            c.codigo_verificacion,
            c.fecha_emision,
            cu.titulo AS curso_titulo
        FROM certificados c
        INNER JOIN cursos cu ON c.curso_id = cu.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha_emision DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $certificados = [];

    while ($row = $result->fetch_assoc()) {
        $certificados[] = $row;
    }

    echo json_encode([
        "success" => true,
        "certificados" => $certificados
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
    