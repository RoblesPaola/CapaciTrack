<?php
include "config/db.php";

$curso_id = $_POST['curso_id'];
$modulos  = json_decode($_POST['modulos'], true);

foreach ($modulos as $i => $mod) {
    $titulo        = $mod['titulo'];
    $descripcion   = $mod['descripcion'];
    $tipo_contenido = $mod['tipo_contenido'];
    $archivoPath   = null;

    if (isset($_FILES["archivo_$i"])) {
        $ext         = pathinfo($_FILES["archivo_$i"]["name"], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . "." . $ext;

        move_uploaded_file(
            $_FILES["archivo_$i"]["tmp_name"],
            "../uploads/contenido/" . $nombreArchivo
        );

        $archivoPath = "uploads/contenido/" . $nombreArchivo;
    }

    $stmt = $conn->prepare(
        "INSERT INTO modulos
        (curso_id, titulo, descripcion, tipo_contenido, archivo)
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $curso_id,
        $titulo,
        $descripcion,
        $tipo_contenido,
        $archivoPath
    ]);
}

echo json_encode(["success" => true]);
