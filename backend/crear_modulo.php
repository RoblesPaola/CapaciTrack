<?php
include "config/db.php";

$curso_id = $_POST['curso_id'];
$modulos = json_decode($_POST['modulos'], true);

foreach ($modulos as $i => $mod) {

    $titulo = $mod['titulo'];
    $descripcion = $mod['descripcion'];
    $tipo_contenido = $mod['tipo_contenido'];
    $archivoPath = null;

    // Si hay archivo
    if (isset($_FILES["archivo_$i"])) {
        $ext = pathinfo($_FILES["archivo_$i"]["name"], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . "." . $ext;

        move_uploaded_file(
            $_FILES["archivo_$i"]["tmp_name"],
            "../uploads/contenido/" . $nombreArchivo
        );

        $archivoPath = "uploads/contenido/" . $nombreArchivo;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO modulos
        (curso_id, titulo, descripcion, tipo_contenido, archivo)
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "issss",
        $curso_id,
        $titulo,
        $descripcion,
        $tipo_contenido,
        $archivoPath
    );

    $stmt->execute();
}

echo json_encode(["success" => true]);
