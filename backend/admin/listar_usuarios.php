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
  GROUP_CONCAT(c.titulo SEPARATOR ', ') AS cursos,
  ROUND(AVG(p.porcentaje), 0) AS progreso
FROM usuarios u
LEFT JOIN inscripciones i ON u.id = i.usuario_id
LEFT JOIN cursos c ON i.curso_id = c.id
LEFT JOIN progreso p ON u.id = p.usuario_id AND c.id = p.curso_id
GROUP BY u.id
";

$result = $conn->query($sql);

$usuarios = [];

while ($row = $result->fetch_assoc()) {
  $usuarios[] = $row;
}

echo json_encode($usuarios);
