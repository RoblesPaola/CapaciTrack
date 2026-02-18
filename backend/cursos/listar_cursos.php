<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$result = $conn->query(
  "SELECT id, titulo, descripcion FROM cursos ORDER BY created_at DESC"
);

$cursos = [];
while ($row = $result->fetch_assoc()) {
  $cursos[] = $row;
}

echo json_encode($cursos);
