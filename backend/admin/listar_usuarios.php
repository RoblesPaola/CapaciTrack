<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

$sql = "
SELECT
  u.id,
  u.nombre,
  u.email,
  u.telefono,
  u.razon_social,
  u.rol,
  COUNT(i.curso_id) AS total_cursos,
  STRING_AGG(c.titulo, ', ') AS cursos,
  ROUND(AVG(p.porcentaje), 0) AS progreso
FROM usuarios u
LEFT JOIN inscripciones i ON u.id = i.usuario_id
LEFT JOIN cursos c ON i.curso_id = c.id
LEFT JOIN progreso p ON u.id = p.usuario_id AND c.id = p.curso_id
GROUP BY u.id, u.nombre, u.email, u.telefono, u.razon_social, u.rol
";

$result   = $conn->query($sql);
$usuarios = $result->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($usuarios);
