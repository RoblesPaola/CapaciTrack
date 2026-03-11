<?php
header('Content-Type: application/json');
session_start();
require_once(__DIR__ . '/config/db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$curso_id   = $_POST['curso_id'] ?? null;

if (!$curso_id) {
    echo json_encode(["success" => false, "error" => "Curso inválido"]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id FROM certificados WHERE usuario_id = ? AND curso_id = ?"
);
$stmt->execute([$usuario_id, $curso_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => true, "mensaje" => "Certificado ya existe"]);
    exit;
}

$codigo = strtoupper(bin2hex(random_bytes(4)));

$stmt = $conn->prepare(
    "INSERT INTO certificados (usuario_id, curso_id, codigo_verificacion) VALUES (?, ?, ?)"
);

try {
    $stmt->execute([$usuario_id, $curso_id, $codigo]);
    echo json_encode([
        "success" => true,
        "codigo"  => $codigo
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error"   => "Error al guardar certificado"
    ]);
}
