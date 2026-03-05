<?php
session_start();
require "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
    exit;
}

$usuario_id = intval($_SESSION['user_id']);
$curso_id   = isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0;

if ($curso_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Curso inválido"
    ]);
    exit;
}

try {

    $stmt = $conn->prepare("
        INSERT INTO progreso
        (usuario_id, curso_id, porcentaje, completado, intento, fecha_actualizacion)
        VALUES (?, ?, 0, 0, 1, NOW())
        ON CONFLICT (usuario_id, curso_id) DO UPDATE SET
            intento           = progreso.intento + 1,
            porcentaje        = 0,
            completado        = 0,
            fecha_actualizacion = NOW()
    ");

    $stmt->execute([$usuario_id, $curso_id]);

    echo json_encode([
        "success" => true,
        "message" => "Intento actualizado correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
