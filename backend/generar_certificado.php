<?php
require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/../fpdf/fpdf.php');

session_start();

$usuario_id = $_SESSION['user_id'] ?? $_GET['usuario_id'] ?? null;
$curso_id   = $_GET['curso_id'] ?? null;

if (!$usuario_id || !$curso_id) {
    die("Parámetros inválidos");
}

try {

    $stmt = $conn->prepare("
        SELECT
            u.nombre AS usuario_nombre,
            c.titulo AS curso_nombre,
            p.fecha_completado,
            p.porcentaje
        FROM progreso p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        INNER JOIN cursos c ON p.curso_id = c.id
        WHERE p.usuario_id = ? AND p.curso_id = ? AND p.completado = TRUE
        LIMIT 1
    ");
    $stmt->execute([$usuario_id, $curso_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        die("No se encontró certificado para este curso o el usuario no lo ha completado");
    }

    $checkCert = $conn->prepare("SELECT codigo_verificacion FROM certificados WHERE usuario_id = ? AND curso_id = ?");
    $checkCert->execute([$usuario_id, $curso_id]);
    $certExistente = $checkCert->fetch(PDO::FETCH_ASSOC);

    if ($certExistente) {
        $codigo_verificacion = $certExistente['codigo_verificacion'];
    } else {
        $codigo_verificacion = 'CERT-' . strtoupper(uniqid());
        $insert = $conn->prepare("
            INSERT INTO certificados (usuario_id, curso_id, codigo_verificacion)
            VALUES (?, ?, ?)
        ");
        $insert->execute([$usuario_id, $curso_id, $codigo_verificacion]);
    }

    /* =====================================================
       CREAR PDF
    ====================================================== */

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    $pdf->SetDrawColor(46, 125, 50);
    $pdf->SetLineWidth(3);
    $pdf->Rect(10, 10, 277, 190);
    $pdf->SetLineWidth(1);
    $pdf->Rect(15, 15, 267, 180);

    if (file_exists('../frontend/assets/logo.png')) {
        $pdf->Image('../frontend/assets/logo.png', 20, 20, 40);
    }
    if (file_exists('../frontend/assets/Grupo-Gusi.png')) {
        $pdf->Image('../frontend/assets/Grupo-Gusi.png', 220, 20, 55);
    }

    $pdf->SetFont('Arial', 'B', 36);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->SetY(60);
    $pdf->Cell(0, 15, utf8_decode('CERTIFICADO DE FINALIZACION'), 0, 1, 'C');

    $pdf->SetFont('Arial', 'I', 14);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 10, 'Se otorga el presente certificado a:', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 15, utf8_decode($datos['usuario_nombre']), 0, 1, 'C');

    $pdf->SetFont('Arial', '', 14);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 8, utf8_decode('Por haber completado satisfactoriamente el curso:'), 0, 'C');

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 12, utf8_decode($datos['curso_nombre']), 0, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 8, 'Con una calificacion de: ' . $datos['porcentaje'] . '%', 0, 1, 'C');

    date_default_timezone_set('America/Mexico_City');
    $fecha_obj = new DateTime($datos['fecha_completado']);
    $fecha     = $fecha_obj->format('d \d\e F \d\e Y');

    $meses = [
        'January' => 'Enero', 'February' => 'Febrero', 'March'     => 'Marzo',
        'April'   => 'Abril', 'May'      => 'Mayo',    'June'      => 'Junio',
        'July'    => 'Julio', 'August'   => 'Agosto',  'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    $fecha = str_replace(array_keys($meses), array_values($meses), $fecha);

    $pdf->SetY(160);
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 6, 'Expedido el ' . utf8_decode($fecha), 0, 1, 'C');

    $pdf->SetY(168);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Codigo de verificacion: ' . $codigo_verificacion, 0, 1, 'C');

    if (file_exists('../frontend/assets/sello.png')) {
        $pdf->Image('../frontend/assets/sello.png', 40, 120, 45);
    }

    $pdf->SetY(175);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, '_______________________________', 0, 1, 'C');
    $pdf->Cell(0, 5, 'GRUPO GUSI SPR DE RL DE CV', 0, 1, 'C');

    $pdf->Output('D', 'Certificado_' . preg_replace('/[^A-Za-z0-9]/', '_', $datos['curso_nombre']) . '.pdf');

} catch (Exception $e) {
    die("Error al generar certificado: " . $e->getMessage());
}
