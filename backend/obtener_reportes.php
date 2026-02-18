<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require "config/db.php";

try {

    /* 1. CURSOS */
    $result_cursos = $conn->query("
        SELECT c.titulo, COUNT(DISTINCT p.usuario_id) as total
        FROM progreso p
        JOIN usuarios u ON u.id = p.usuario_id
        JOIN cursos c ON c.id = p.curso_id
        WHERE u.rol = 'usuario'
        GROUP BY c.id, c.titulo
    ");
    
    $cursos = [];
    if ($result_cursos && $result_cursos->num_rows > 0) {
        $cursos = $result_cursos->fetch_all(MYSQLI_ASSOC);
    }

    /* 2. DOCUMENTOS */
    $result_docs = $conn->query("
        SELECT 
            CASE 
                WHEN documentos IS NOT NULL AND documentos != '' THEN 'Con documentos'
                ELSE 'Sin documentos'
            END as tipo_documento,
            COUNT(*) as total
        FROM usuarios
        WHERE rol = 'usuario'
        GROUP BY tipo_documento
    ");
    
    $documentos = [];
    if ($result_docs && $result_docs->num_rows > 0) {
        while ($row = $result_docs->fetch_assoc()) {
            $documentos[] = [
                'tipo_documento' => $row['tipo_documento'],
                'total' => (int)$row['total']
            ];
        }
    }

    /* 3. PROGRESO */
    $result_progreso = $conn->query("
        SELECT
            SUM(CASE WHEN p.completado = 1 THEN 1 ELSE 0 END) as completados,
            SUM(CASE WHEN p.completado = 0 OR p.completado IS NULL THEN 1 ELSE 0 END) as en_curso
        FROM progreso p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        WHERE u.rol = 'usuario'
    ");
    
    $progreso = ['completados' => 0, 'en_curso' => 0];
    if ($result_progreso) {
        $row = $result_progreso->fetch_assoc();
        $progreso = [
            'completados' => (int)($row['completados'] ?? 0),
            'en_curso' => (int)($row['en_curso'] ?? 0)
        ];
    }

    /* 4. CERTIFICADOS */
    $result_certificados = $conn->query("
        SELECT 
            COUNT(DISTINCT c.usuario_id) as con_certificado,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario') - COUNT(DISTINCT c.usuario_id) as sin_certificado
        FROM certificados c
        INNER JOIN usuarios u ON u.id = c.usuario_id
        WHERE u.rol = 'usuario'
    ");

    $certificados = ['con_certificado' => 0, 'sin_certificado' => 0];

    if ($result_certificados) {
        $row = $result_certificados->fetch_assoc();
        $certificados = [
            'con_certificado' => (int)($row['con_certificado'] ?? 0),
            'sin_certificado' => (int)($row['sin_certificado'] ?? 0)
        ];
    }

    echo json_encode([
        "success" => true,
        "cursos" => $cursos,
        "documentos" => $documentos,
        "progreso" => $progreso,
        "certificados" => $certificados
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
