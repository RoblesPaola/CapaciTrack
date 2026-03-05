<?php
session_start();
header("Content-Type: application/json");
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

$data     = json_decode(file_get_contents("php://input"), true);
$curso_id = $data["curso_id"] ?? null;

if (!$curso_id) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ?");
$stmt->execute([$curso_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "message" => "Curso no encontrado"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");

try {
    $stmt->execute([$curso_id]);
    echo json_encode(["success" => true, "message" => "Curso eliminado"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al eliminar"]);
}

$conn = null;
