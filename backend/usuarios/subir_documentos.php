<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Verificar que se especificó el tipo de documento
if (!isset($_POST['tipo_documento'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de documento no especificado']);
    exit;
}

$tipo_documento = $_POST['tipo_documento']; // 'licencia_conducir' o 'acta_medica'

if (!in_array($tipo_documento, ['licencia_conducir', 'acta_medica'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de documento inválido']);
    exit;
}

// Verificar archivo
if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún documento']);
    exit;
}

// Validar tipo de archivo
$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
$file_type = $_FILES['documento']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo PDF, JPG o PNG']);
    exit;
}

// Validar tamaño (máximo 10MB)
if ($_FILES['documento']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'El documento es demasiado grande. Máximo 10MB']);
    exit;
}

// Configurar carpeta de destino
$upload_dir = __DIR__ . '/../../uploads/documentos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generar nombre único
$extension = pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION);
$nuevo_nombre = $tipo_documento . '_' . $usuario_id . '_' . time() . '.' . $extension;
$ruta_destino = $upload_dir . $nuevo_nombre;
$ruta_relativa = 'uploads/documentos/' . $nuevo_nombre;

// Mover el archivo
if (!move_uploaded_file($_FILES['documento']['tmp_name'], $ruta_destino)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el documento']);
    exit;
}

// Actualizar la base de datos
require_once __DIR__ . '/../config/db.php';

// Obtener documentos actuales
$sql = "SELECT documentos FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$documentos = [];
if ($row && $row['documentos']) {
    $documentos = json_decode($row['documentos'], true) ?? [];
}

// Actualizar el documento específico
$documentos[$tipo_documento] = $ruta_relativa;

// Guardar en la base de datos
$documentos_json = json_encode($documentos);
$sql_update = "UPDATE usuarios SET documentos = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $documentos_json, $usuario_id);

if ($stmt_update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Documento subido correctamente',
        'tipo' => $tipo_documento,
        'ruta' => $ruta_relativa
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos: ' . $stmt_update->error]);
}

$stmt->close();
$stmt_update->close();
?>