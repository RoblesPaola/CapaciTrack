<?php
session_start();
ob_start();
header("Content-Type: application/json");
error_reporting(0);
require_once __DIR__ . "/config/db.php";

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
    exit;
}
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$data               = json_decode(file_get_contents("php://input"), true);
$usuarioId          = $_SESSION["user_id"];
$cursoId            = intval($data["curso_id"] ?? 0);
$porcentaje         = intval($data["porcentaje"] ?? 0);
$notaEvaluacion     = isset($data["nota_evaluacion"]) ? intval($data["nota_evaluacion"]) : null;
$incrementarIntentos = !empty($data["incrementar_intentos"]);

if ($cursoId <= 0) {
    echo json_encode(["success" => false, "message" => "Curso inválido"]);
    exit;
}

$completado      = ($porcentaje >= 100);
$fechaCompletado = $completado ? date("Y-m-d H:i:s") : null;

try {
    // --- Tabla progreso ---
    $stmt = $conn->prepare("SELECT id, completado FROM progreso WHERE usuario_id = ? AND curso_id = ?");
    $stmt->execute([$usuarioId, $cursoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $yaCompletado = ($row["completado"] === 't' || $row["completado"] === true || $row["completado"] == 1);
        if ($yaCompletado) {
            // Si ya estaba completado solo actualizar porcentaje, no regresar el estado
            $conn->prepare("UPDATE progreso SET porcentaje = ? WHERE usuario_id = ? AND curso_id = ?")
                 ->execute([$porcentaje, $usuarioId, $cursoId]);
        } else {
            $conn->prepare("
                UPDATE progreso SET porcentaje = ?, completado = ?, fecha_completado = ?
                WHERE usuario_id = ? AND curso_id = ?
            ")->execute([$porcentaje, $completado, $fechaCompletado, $usuarioId, $cursoId]);
        }
    } else {
        $conn->prepare("
            INSERT INTO progreso (usuario_id, curso_id, porcentaje, completado, fecha_completado)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$usuarioId, $cursoId, $porcentaje, $completado, $fechaCompletado]);
    }

    // --- Tabla inscripciones ---
    $chkInsc = $conn->prepare("SELECT id, estado FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $chkInsc->execute([$usuarioId, $cursoId]);
    $insc = $chkInsc->fetch(PDO::FETCH_ASSOC);

    if ($insc) {
        $yaFinalizado = ($insc['estado'] === 'finalizado');

        $sets   = ["progreso = ?", "ultima_visita = NOW()"];
        $params = [$porcentaje];

        // Solo transicionar a finalizado, nunca regresar
        if (!$yaFinalizado && $completado) {
            $sets[]   = "estado = ?";
            $sets[]   = "fecha_finalizacion = ?";
            $params[] = 'finalizado';
            $params[] = $fechaCompletado;
        }

        if ($notaEvaluacion !== null) {
            $sets[]   = "nota_evaluacion = ?";
            $params[] = $notaEvaluacion;
        }

        if ($incrementarIntentos) {
            $sets[] = "intentos_evaluacion = intentos_evaluacion + 1";
        }

        $params[] = $usuarioId;
        $params[] = $cursoId;

        $conn->prepare("UPDATE inscripciones SET " . implode(", ", $sets) . " WHERE usuario_id = ? AND curso_id = ?")
             ->execute($params);
    }

    echo json_encode([
        "success"    => true,
        "message"    => "Progreso guardado correctamente",
        "completado" => $completado
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al guardar progreso: " . $e->getMessage()]);
}

ob_end_flush();
exit;
