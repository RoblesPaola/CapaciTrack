<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

//CODIGO FUNCIONANDO NO MOVER

// Leer JSON enviado desde fetch
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos
$nombre = trim($data["nombre"] ?? "");
$email  = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$rol = $data["rol"] ?? "";

if ($nombre === "" || $email === "" || $password === "" || $rol === "") {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

// Encriptar contraseña
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Verificar si el correo ya existe
$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El correo ya está registrado"
    ]);
    exit;
}

// Insertar usuario
$stmt = $conn->prepare(
    "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $nombre, $email, $passwordHash, $rol);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Usuario agregado correctamente"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar usuario"
    ]);
}
