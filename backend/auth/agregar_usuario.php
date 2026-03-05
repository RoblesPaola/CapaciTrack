<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

//CODIGO FUNCIONANDO NO MOVER

$data = json_decode(file_get_contents("php://input"), true);

$nombre   = trim($data["nombre"] ?? "");
$email    = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$rol      = $data["rol"] ?? "";

if ($nombre === "" || $email === "" || $password === "" || $rol === "") {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El correo ya está registrado"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)"
);

try {
    $stmt->execute([$nombre, $email, $passwordHash, $rol]);
    echo json_encode([
        "success" => true,
        "message" => "Usuario agregado correctamente"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar usuario"
    ]);
}
