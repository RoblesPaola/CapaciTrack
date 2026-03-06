<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$data       = json_decode(file_get_contents("php://input"), true);
$usuario_id = (int)$_SESSION['user_id'];
$modulo_id  = (int)($data['modulo_id'] ?? 0);
$completado = isset($data['completado']) ? (bool)$data['completado'] : true;

if ($modulo_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de módulo inválido"]);
    exit;
}

try {
    $fecha = $completado ? date("Y-m-d H:i:s") : null;

    $stmt = $conn->prepare("
        INSERT INTO progreso_modulos (usuario_id, modulo_id, completado, fecha_completado)
        VALUES (?, ?, ?, ?)
        ON CONFLICT (usuario_id, modulo_id)
        DO UPDATE SET completado = EXCLUDED.completado,
                      fecha_completado = EXCLUDED.fecha_completado
    ");
    $stmt->execute([$usuario_id, $modulo_id, $completado, $fecha]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
