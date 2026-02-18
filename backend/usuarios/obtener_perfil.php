<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false]);
  exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT nombre, telefono, razon_social, documentos, perfil
        FROM usuarios
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

  // Convertir documentos JSON a array
  $documentos = [];
  if (!empty($row["documentos"])) {
    $documentos = json_decode($row["documentos"], true);
  }

  echo json_encode([
    "success" => true,
    "nombre" => $row["nombre"],
    "telefono" => $row["telefono"],
    "razon_social" => $row["razon_social"],
    "perfil" => $row["perfil"],
    "documentos" => $documentos
  ]);

} else {
  echo json_encode(["success" => false]);
}
