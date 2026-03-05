<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require "config/db.php";

try {

    /* 1. CURSOS */
    $stmtCursos = $conn->query("
        SELECT c.titulo, COUNT(DISTINCT p.usuario_id) as total
        FROM progreso p
        JOIN usuarios u ON u.id = p.usuario_id
        JOIN cursos c ON c.id = p.curso_id
        WHERE u.rol = 'usuario'
        GROUP BY c.id, c.titulo
    ");
    $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

    /* 2. DOCUMENTOS */
    $stmtDocs = $conn->query("
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
    while ($row = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
        $documentos[] = [
            'tipo_documento' => $row['tipo_documento'],
            'total'          => (int)$row['total']
        ];
    }

    /* 3. PROGRESO */
    $stmtProgreso = $conn->query("
        SELECT
            SUM(CASE WHEN p.completado = 1 THEN 1 ELSE 0 END) as completados,
            SUM(CASE WHEN p.completado = 0 OR p.completado IS NULL THEN 1 ELSE 0 END) as en_curso
        FROM progreso p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        WHERE u.rol = 'usuario'
    ");

    $progreso = ['completados' => 0, 'en_curso' => 0];
    $row = $stmtProgreso->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $progreso = [
            'completados' => (int)($row['completados'] ?? 0),
            'en_curso'    => (int)($row['en_curso'] ?? 0)
        ];
    }

    /* 4. CERTIFICADOS */
    $stmtCert = $conn->query("
        SELECT
            COUNT(DISTINCT c.usuario_id) as con_certificado,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario') - COUNT(DISTINCT c.usuario_id) as sin_certificado
        FROM certificados c
        INNER JOIN usuarios u ON u.id = c.usuario_id
        WHERE u.rol = 'usuario'
    ");

    $certificados = ['con_certificado' => 0, 'sin_certificado' => 0];
    $row = $stmtCert->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $certificados = [
            'con_certificado' => (int)($row['con_certificado'] ?? 0),
            'sin_certificado' => (int)($row['sin_certificado'] ?? 0)
        ];
    }

    echo json_encode([
        "success"      => true,
        "cursos"       => $cursos,
        "documentos"   => $documentos,
        "progreso"     => $progreso,
        "certificados" => $certificados
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
}

$conn = null;
?>
