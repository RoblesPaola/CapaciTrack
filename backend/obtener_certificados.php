<?php
session_start();
require_once __DIR__ . "/config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
    exit;
}

$usuario_id = intval($_SESSION['user_id']);

try {

    $stmt = $conn->prepare("
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
    ");
    $stmt->execute([$usuario_id]);

    $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"       => true,
        "certificados"  => $certificados
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
