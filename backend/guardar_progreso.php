<?php
session_start();

// ⚡ Evitar cualquier salida antes de JSON
ob_start();
header("Content-Type: application/json");

// ⚠️ Opcional: ocultar warnings/notices que rompen JSON
error_reporting(0);

// =========================
// CONFIGURACIÓN BASE DE DATOS
// =========================
require_once __DIR__ . "/config/db.php";

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
    exit;
}

// =========================
// VALIDACIÓN DE SESIÓN
// =========================
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// =========================
// LEER DATOS DEL FRONTEND
// =========================
$data = json_decode(file_get_contents("php://input"), true);

$usuarioId = $_SESSION["user_id"];
$cursoId   = intval($data["curso_id"] ?? 0);
$porcentaje = intval($data["porcentaje"] ?? 0);

if ($cursoId <= 0) {
    echo json_encode(["success" => false, "message" => "Curso inválido"]);
    exit;
}

// =========================
// DETERMINAR COMPLETADO
// =========================
$completado = ($porcentaje >= 100) ? 1 : 0;
$fechaCompletado = ($completado === 1) ? date("Y-m-d H:i:s") : null;

// =========================
// VERIFICAR SI YA EXISTE PROGRESO
// =========================
$stmt = $conn->prepare("
    SELECT id, completado 
    FROM progreso 
    WHERE usuario_id = ? AND curso_id = ?
");
$stmt->bind_param("ii", $usuarioId, $cursoId);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    echo json_encode(["success" => false, "message" => "Error al consultar progreso"]);
    exit;
}

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    if ($row["completado"] == 1) {
        // Ya completado → solo actualizar porcentaje
        $stmt = $conn->prepare("
            UPDATE progreso
            SET porcentaje = ?
            WHERE usuario_id = ? AND curso_id = ?
        ");
        $stmt->bind_param("iii", $porcentaje, $usuarioId, $cursoId);
    } else {
        // No completado → actualizar porcentaje y completado
        $stmt = $conn->prepare("
            UPDATE progreso
            SET porcentaje = ?, 
                completado = ?, 
                fecha_completado = ?
            WHERE usuario_id = ? AND curso_id = ?
        ");
        $stmt->bind_param(
            "iissi",
            $porcentaje,
            $completado,
            $fechaCompletado,
            $usuarioId,
            $cursoId
        );
    }

    $stmt->execute();

} else {
    // No existe → insertar nuevo registro
    $stmt = $conn->prepare("
        INSERT INTO progreso 
        (usuario_id, curso_id, porcentaje, completado, fecha_completado)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiiis",
        $usuarioId,
        $cursoId,
        $porcentaje,
        $completado,
        $fechaCompletado
    );
    $stmt->execute();
}

// =========================
// RESPUESTA JSON
// =========================
echo json_encode([
    "success" => true,
    "message" => "Progreso guardado correctamente",
    "completado" => $completado
]);

// ⚡ Enviar solo JSON y limpiar buffer
ob_end_flush();
exit;
