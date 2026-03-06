<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode([]);
  exit;
}

$usuario_id = $_SESSION["user_id"];

$sql = "
SELECT c.*
FROM cursos c
INNER JOIN inscripciones i ON c.id = i.curso_id
LEFT JOIN progreso p
    ON p.curso_id = c.id
    AND p.usuario_id = i.usuario_id
WHERE i.usuario_id = ?
AND p.completado IS NOT TRUE
ORDER BY i.fecha_inscripcion DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);

$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cursos);
