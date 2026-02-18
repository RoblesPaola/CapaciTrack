<?php
session_start();
header("Content-Type: application/json");
//NO MOVER//
// Verificar si hay sesión activa
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
    exit;
}

// Conexión a la BD
require_once __DIR__ . "/../config/db.php";

$userId = $_SESSION["user_id"];

// Obtener datos del usuario
$sql = "SELECT id, nombre, email FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);
    exit;
}

$usuario = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "id" => $usuario["id"],
    "nombre" => $usuario["nombre"],
    "email" => $usuario["email"]
]);
