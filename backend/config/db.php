<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "capacitrack1");

if ($conn->connect_error) {
  echo json_encode([
    "success" => false,
    "message" => "Error de conexión a la base de datos"
  ]);
  exit;
}

$conn->set_charset("utf8");

