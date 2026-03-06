<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$usuario_id = (int)$_SESSION['user_id'];
$curso_id   = (int)($_GET['curso_id'] ?? 0);

if ($curso_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de curso inválido"]);
    exit;
}

try {
    // Módulos completados para este curso
    $stmt = $conn->prepare("
        SELECT pm.modulo_id
        FROM progreso_modulos pm
        INNER JOIN modulos m ON pm.modulo_id = m.id
        WHERE pm.usuario_id = ? AND m.curso_id = ? AND pm.completado = TRUE
    ");
    $stmt->execute([$usuario_id, $curso_id]);
    $modulosCompletados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'modulo_id');

    // Datos de la inscripción (estado, nota, intentos, última visita)
    $stmtInsc = $conn->prepare("
        SELECT estado, nota_evaluacion, intentos_evaluacion, fecha_finalizacion, progreso, ultima_visita
        FROM inscripciones
        WHERE usuario_id = ? AND curso_id = ?
    ");
    $stmtInsc->execute([$usuario_id, $curso_id]);
    $inscripcion = $stmtInsc->fetch(PDO::FETCH_ASSOC);

    // Actualizar última visita
    $conn->prepare("
        UPDATE inscripciones SET ultima_visita = NOW()
        WHERE usuario_id = ? AND curso_id = ?
    ")->execute([$usuario_id, $curso_id]);

    echo json_encode([
        "success"             => true,
        "modulos_completados" => array_map('intval', $modulosCompletados),
        "inscripcion"         => $inscripcion
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
