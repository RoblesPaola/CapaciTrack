<?php
session_start();
require_once __DIR__ . "/../config/db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$usuario_id = intval($_SESSION['user_id']);

try {
    // Join inscripciones + progreso para obtener datos completos
    $sql = "
        SELECT
            c.id                        AS curso_id,
            c.titulo                    AS curso_nombre,
            c.portada,
            COALESCE(p.porcentaje, i.progreso, 0) AS porcentaje,
            COALESCE(p.completado, FALSE)          AS completado,
            i.estado,
            i.nota_evaluacion,
            i.intentos_evaluacion,
            i.ultima_visita,
            i.fecha_finalizacion,
            i.fecha_inscripcion
        FROM inscripciones i
        INNER JOIN cursos c ON i.curso_id = c.id
        LEFT  JOIN progreso p ON p.usuario_id = i.usuario_id AND p.curso_id = i.curso_id
        WHERE i.usuario_id = ?
        ORDER BY
            CASE WHEN i.estado = 'en_progreso' THEN 0 ELSE 1 END,
            i.ultima_visita DESC NULLS LAST,
            i.fecha_inscripcion DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario_id]);

    $cursos      = [];
    $completados = 0;
    $en_progreso = 0;
    $suma_pct    = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Normalizar boolean de PostgreSQL
        $row['completado'] = ($row['completado'] === 't' || $row['completado'] === true || $row['completado'] == 1);
        // Normalizar estado: si completado pero estado no actualizado
        if ($row['completado'] && $row['estado'] !== 'finalizado') {
            $row['estado'] = 'finalizado';
        }

        $cursos[] = $row;
        $suma_pct += intval($row['porcentaje']);

        if ($row['estado'] === 'finalizado') {
            $completados++;
        } else {
            $en_progreso++;
        }
    }

    $total = count($cursos);

    echo json_encode([
        "success" => true,
        "cursos"  => $cursos,
        "stats"   => [
            "total"              => $total,
            "completados"        => $completados,
            "en_progreso"        => $en_progreso,
            "porcentaje_general" => $total > 0 ? round($suma_pct / $total) : 0
        ],
        // Compatibilidad con código anterior
        "total_modulos"       => 0,
        "modulos_completados" => 0,
        "porcentaje"          => $total > 0 ? round($suma_pct / $total) : 0
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
