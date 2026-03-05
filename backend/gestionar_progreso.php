<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
session_start();

require_once(__DIR__ . '/config/db.php');

try {

    $accion = $_GET['accion'] ?? '';

    if ($accion === 'obtener') {

        $usuario_id = $_GET['usuario_id'] ?? 0;
        $curso_id   = $_GET['curso_id'] ?? 0;

        if (!$usuario_id || !$curso_id) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $stmt = $conn->prepare("SELECT porcentaje, completado FROM progreso WHERE usuario_id = ? AND curso_id = ?");
        $stmt->execute([$usuario_id, $curso_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => true, 'data' => null]);
        }

        exit;
    }

    if ($accion === 'actualizar') {

        $data = json_decode(file_get_contents("php://input"), true);

        $usuario_id = $data['usuario_id'] ?? 0;
        $curso_id   = $data['curso_id'] ?? 0;
        $porcentaje = $data['porcentaje'] ?? 0;

        if (!$usuario_id || !$curso_id) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO progreso (usuario_id, curso_id, porcentaje)
            VALUES (?, ?, ?)
            ON CONFLICT (usuario_id, curso_id) DO UPDATE SET porcentaje = EXCLUDED.porcentaje
        ");
        $stmt->execute([$usuario_id, $curso_id, $porcentaje]);

        echo json_encode([
            'success'   => true,
            'completado' => ($porcentaje == 100 ? 1 : 0)
        ]);
        exit;
    }

    if ($accion === 'completar') {

        $data = json_decode(file_get_contents("php://input"), true);

        $usuario_id = $data['usuario_id'];
        $curso_id   = $data['curso_id'];

        $stmt = $conn->prepare("
            UPDATE progreso
            SET completado = 1, porcentaje = 100
            WHERE usuario_id = ? AND curso_id = ?
        ");
        $stmt->execute([$usuario_id, $curso_id]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}
