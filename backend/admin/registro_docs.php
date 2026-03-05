<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$sql = "SELECT id, nombre, email, rol, telefono, razon_social, documentos
FROM usuarios";

$result   = $conn->query($sql);
$usuarios = $result->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($usuarios);
