<?php
session_start();
header("Content-Type: application/json");
require_once "config/db.php";

// Validar sesión
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);
$curso_id = $data["curso_id"] ?? null;

if (!$curso_id) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

// (Opcional) verificar que el curso exista
$stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ?");
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
    exit;
}
$stmt->close();

// Eliminar curso
$stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
$stmt->bind_param("i", $curso_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Curso eliminado"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar"]);
}

$stmt->close();
$conn->close();
