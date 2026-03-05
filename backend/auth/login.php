<?php
session_start();
header("Content-Type: application/json");
error_reporting(0);

require_once __DIR__ . "/../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["email"]) || empty($data["password"])) {
  echo json_encode(["success" => false, "message" => "Datos incompletos"]);
  exit;
}

$email    = $data["email"];
$password = $data["password"];

$stmt = $conn->prepare(
  "SELECT id, nombre, password, rol FROM usuarios WHERE email = ?"
);
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
  echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
  exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($password, $user["password"])) {
  echo json_encode([
    "success" => false,
    "message" => "Contraseña incorrecta"
  ]);
  exit;
}

$_SESSION["user_id"] = $user["id"];
$_SESSION["rol"]     = $user["rol"];
$_SESSION["nombre"]  = $user["nombre"];

echo json_encode([
  "success" => true,
  "rol"     => $user["rol"]
]);
