<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false]);
  exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare(
  "SELECT nombre, telefono, razon_social, documentos, perfil
   FROM usuarios
   WHERE id = ?"
);
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
  $documentos = [];
  if (!empty($row["documentos"])) {
    $documentos = json_decode($row["documentos"], true);
  }

  echo json_encode([
    "success"      => true,
    "nombre"       => $row["nombre"],
    "telefono"     => $row["telefono"],
    "razon_social" => $row["razon_social"],
    "perfil"       => $row["perfil"],
    "documentos"   => $documentos
  ]);
} else {
  echo json_encode(["success" => false]);
}
