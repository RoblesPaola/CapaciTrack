<?php
header('Content-Type: application/json');
session_start();
require_once(__DIR__ . '/config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$curso_id = $_POST['curso_id'] ?? null;

if (!$curso_id) {
    echo json_encode(["success" => false, "error" => "Curso inválido"]);
    exit;
}

// 🔥 Verificar si ya existe certificado
$sql = "SELECT id FROM certificados 
        WHERE usuario_id = ? AND curso_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $curso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => true, "mensaje" => "Certificado ya existe"]);
    exit;
}

//  Generar código único
$codigo = strtoupper(bin2hex(random_bytes(4)));

//  Insertar certificado
$sql = "INSERT INTO certificados 
        (usuario_id, curso_id, codigo_verificacion) 
        VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $usuario_id, $curso_id, $codigo);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "codigo" => $codigo
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Error al guardar certificado"
    ]);
}
