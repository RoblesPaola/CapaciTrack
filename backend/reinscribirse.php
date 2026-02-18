<?php
session_start();
require "config/db.php";

header("Content-Type: application/json");

// 🔹 Verificar sesión
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

    // 🔹 Insertar o aumentar intento automáticamente
    $stmt = $conn->prepare("
        INSERT INTO progreso 
        (usuario_id, curso_id, porcentaje, completado, intento, fecha_actualizacion)
        VALUES (?, ?, 0, 0, 1, NOW())
        ON DUPLICATE KEY UPDATE
            intento = intento + 1,
            porcentaje = 0,
            completado = 0,
            fecha_actualizacion = NOW()
    ");

    if (!$stmt) {
        throw new Exception("Error prepare: " . $conn->error);
    }

    $stmt->bind_param("ii", $usuario_id, $curso_id);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando: " . $stmt->error);
    }

    $stmt->close();

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
