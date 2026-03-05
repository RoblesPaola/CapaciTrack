<?php
session_start();
header("Content-Type: application/json");
//NO MOVER//

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
    exit;
}

require_once __DIR__ . "/../config/db.php";

$userId = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);
    exit;
}

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "id"      => $usuario["id"],
    "nombre"  => $usuario["nombre"],
    "email"   => $usuario["email"]
]);
