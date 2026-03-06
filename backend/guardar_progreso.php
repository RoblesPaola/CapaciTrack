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

$data = json_decode(file_get_contents("php://input"), true);

$usuarioId  = $_SESSION["user_id"];
$cursoId    = intval($data["curso_id"] ?? 0);
$porcentaje = intval($data["porcentaje"] ?? 0);

if ($cursoId <= 0) {
    echo json_encode(["success" => false, "message" => "Curso inválido"]);
    exit;
}

$completado      = ($porcentaje >= 100) ? true : false;
$fechaCompletado = $completado ? date("Y-m-d H:i:s") : null;

try {
    $stmt = $conn->prepare("
        SELECT id, completado
        FROM progreso
        WHERE usuario_id = ? AND curso_id = ?
    ");
    $stmt->execute([$usuarioId, $cursoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $yaCompletado = ($row["completado"] === 't' || $row["completado"] === true || $row["completado"] == 1);
        if ($yaCompletado) {
            $stmtUpd = $conn->prepare("
                UPDATE progreso
                SET porcentaje = ?
                WHERE usuario_id = ? AND curso_id = ?
            ");
            $stmtUpd->execute([$porcentaje, $usuarioId, $cursoId]);
        } else {
            $stmtUpd = $conn->prepare("
                UPDATE progreso
                SET porcentaje = ?,
                    completado = ?,
                    fecha_completado = ?
                WHERE usuario_id = ? AND curso_id = ?
            ");
            $stmtUpd->execute([$porcentaje, $completado, $fechaCompletado, $usuarioId, $cursoId]);
        }
    } else {
        $stmtIns = $conn->prepare("
            INSERT INTO progreso
            (usuario_id, curso_id, porcentaje, completado, fecha_completado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtIns->execute([$usuarioId, $cursoId, $porcentaje, $completado, $fechaCompletado]);
    }

    echo json_encode([
        "success"    => true,
        "message"    => "Progreso guardado correctamente",
        "completado" => $completado
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success"  => false,
        "message"  => "Error al guardar progreso: " . $e->getMessage()
    ]);
}

ob_end_flush();
exit;
