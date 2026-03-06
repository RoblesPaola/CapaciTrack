<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

if (!isset($_POST['tipo_documento'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de documento no especificado']);
    exit;
}

$tipo_documento = $_POST['tipo_documento'];

if (!in_array($tipo_documento, ['licencia_conducir', 'acta_medica'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de documento inválido']);
    exit;
}

if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún documento']);
    exit;
}

$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
$file_type     = $_FILES['documento']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo PDF, JPG o PNG']);
    exit;
}

if ($_FILES['documento']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'El documento es demasiado grande. Máximo 10MB']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Leer documentos actuales ANTES de subir el nuevo
$stmt = $conn->prepare("SELECT documentos FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$documentos = [];
if ($row && $row['documentos']) {
    $documentos = json_decode($row['documentos'], true) ?? [];
}

// Borrar archivo anterior si existe
if (!empty($documentos[$tipo_documento])) {
    $rutaVieja = __DIR__ . '/../../' . $documentos[$tipo_documento];
    if (file_exists($rutaVieja)) {
        unlink($rutaVieja);
    }
}

$upload_dir = __DIR__ . '/../../uploads/documentos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension     = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
$nuevo_nombre  = $tipo_documento . '_' . $usuario_id . '_' . time() . '.' . $extension;
$ruta_destino  = $upload_dir . $nuevo_nombre;
$ruta_relativa = 'uploads/documentos/' . $nuevo_nombre;

if (!move_uploaded_file($_FILES['documento']['tmp_name'], $ruta_destino)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el documento']);
    exit;
}

$documentos[$tipo_documento] = $ruta_relativa;

$stmt_update = $conn->prepare("UPDATE usuarios SET documentos = ? WHERE id = ?");

try {
    $stmt_update->execute([json_encode($documentos), $usuario_id]);
    echo json_encode([
        'success'    => true,
        'message'    => 'Documento actualizado correctamente',
        'tipo'       => $tipo_documento,
        'ruta'       => $ruta_relativa,
        'extension'  => $extension
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
}
?>
