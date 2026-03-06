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

    // Resetear tabla progreso (completado es BOOLEAN en PostgreSQL)
    $stmt = $conn->prepare("
        INSERT INTO progreso
        (usuario_id, curso_id, porcentaje, completado, intento, fecha_actualizacion)
        VALUES (?, ?, 0, FALSE, 1, NOW())
        ON CONFLICT (usuario_id, curso_id) DO UPDATE SET
            intento             = progreso.intento + 1,
            porcentaje          = 0,
            completado          = FALSE,
            fecha_completado    = NULL,
            fecha_actualizacion = NOW()
    ");
    $stmt->execute([$usuario_id, $curso_id]);

    // Resetear inscripción a en_progreso y limpiar datos de la sesión anterior
    $conn->prepare("
        UPDATE inscripciones SET
            estado                = 'en_progreso',
            progreso              = 0,
            nota_evaluacion       = NULL,
            intentos_evaluacion   = 0,
            certificado_generado  = FALSE,
            codigo_certificado    = NULL,
            fecha_finalizacion    = NULL,
            ultima_visita         = NOW()
        WHERE usuario_id = ? AND curso_id = ?
    ")->execute([$usuario_id, $curso_id]);

    // Limpiar módulos completados de la sesión anterior
    $conn->prepare("
        DELETE FROM progreso_modulos
        WHERE usuario_id = ? AND modulo_id IN (
            SELECT id FROM modulos WHERE curso_id = ?
        )
    ")->execute([$usuario_id, $curso_id]);

    echo json_encode([
        "success" => true,
        "message" => "Reinscripción realizada correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
