<?php
require "db.php";

$sql = "INSERT INTO cursos (nombre, descripcion, creado_por)
        VALUES ('Curso prueba', 'Descripción prueba', 1)";

if ($pdo->exec($sql)) {
    echo "INSERT OK";
} else {
    echo "ERROR";
}
