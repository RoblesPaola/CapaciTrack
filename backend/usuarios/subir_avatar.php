<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']);
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$file_type     = $_FILES['foto']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG o WEBP']);
    exit;
}

if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande. Máximo 5MB']);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/perfiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension    = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
$nuevo_nombre = 'perfil_' . $usuario_id . '_' . time() . '.' . $extension;
$ruta_destino = $upload_dir . $nuevo_nombre;
$ruta_relativa = 'uploads/perfiles/' . $nuevo_nombre;

if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$stmt = $conn->prepare("UPDATE usuarios SET perfil = ? WHERE id = ?");

try {
    $stmt->execute([$ruta_relativa, $usuario_id]);
    echo json_encode([
        'success' => true,
        'message' => 'Foto de perfil actualizada',
        'ruta'    => $ruta_relativa
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
}
?>
