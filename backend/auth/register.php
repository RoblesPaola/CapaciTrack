<?php
header("Content-Type: application/json");

//CODIGO FUNCIONANDO NO MOVER
//  Conexión
require_once __DIR__ . "/../config/db.php";


//  Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

//  Validar datos
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

//  Validar rol permitido
$rolesPermitidos = ["admin", "moderador", "usuario"];
if (!in_array($rol, $rolesPermitidos)) {
  $rol = "usuario";
}

//  Verificar correo duplicado
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

//  Encriptar contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

//  Insertar usuario
$stmt = $conn->prepare(
  "INSERT INTO usuarios (nombre, email, password, rol)
   VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("ssss", $nombre, $email, $hash, $rol);

if ($stmt->execute()) {
  echo json_encode([
    "success" => true,
    "message" => "Usuario registrado correctamente"
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "Error al registrar usuario"
  ]);
}
