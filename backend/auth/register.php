<?php
header("Content-Type: application/json");

//CODIGO FUNCIONANDO NO MOVER
require_once __DIR__ . "/../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$nombre   = trim($data["nombre"] ?? "");
$email    = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$rol      = $data["rol"] ?? "usuario";

if ($nombre === "" || $email === "" || $password === "") {
  echo json_encode([
    "success" => false,
    "message" => "Datos incompletos"
  ]);
  exit;
}

$rolesPermitidos = ["admin", "moderador", "usuario"];
if (!in_array($rol, $rolesPermitidos)) {
  $rol = "usuario";
}

$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
  echo json_encode([
    "success" => false,
    "message" => "El correo ya está registrado"
  ]);
  exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
  "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)"
);

try {
  $stmt->execute([$nombre, $email, $hash, $rol]);
  echo json_encode([
    "success" => true,
    "message" => "Usuario registrado correctamente"
  ]);
} catch (PDOException $e) {
  echo json_encode([
    "success" => false,
    "message" => "Error al registrar usuario"
  ]);
}
