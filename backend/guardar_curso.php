<?php
session_start();
header("Content-Type: application/json");
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

function subirArchivo($file, $carpeta) {
    $permitidos = [
        "image/jpeg", "image/png", "image/webp",
        "application/pdf",
        "video/mp4", "video/webm",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation"
    ];

    if (!in_array($file["type"], $permitidos)) {
        return "";
    }

    if (!is_dir("../uploads/$carpeta")) {
        mkdir("../uploads/$carpeta", 0777, true);
    }

    $nombre = time() . "_" . basename($file["name"]);
    $ruta   = "uploads/$carpeta/" . $nombre;

    move_uploaded_file($file["tmp_name"], "../" . $ruta);
    return $ruta;
}

$titulo      = $_POST["titulo"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";
$modulos     = json_decode($_POST["modulos"] ?? "[]", true);
$evaluaciones = json_decode($_POST["evaluaciones"] ?? "[]", true);

$portada = "";
if (!empty($_FILES["portada"]["name"])) {
    $portada = subirArchivo($_FILES["portada"], "portadas");
}

// CURSO
$stmt = $conn->prepare(
    "INSERT INTO cursos (titulo, descripcion, portada, creado_por)
     VALUES (?, ?, ?, ?)
     RETURNING id"
);
$stmt->execute([$titulo, $descripcion, $portada, $_SESSION["user_id"]]);
$curso_id = $stmt->fetchColumn();

// MÓDULOS CON ARCHIVOS
$stmtMod = $conn->prepare(
    "INSERT INTO modulos
    (curso_id, titulo, descripcion, tipo_contenido, archivo)
    VALUES (?, ?, ?, ?, ?)"
);

foreach ($modulos as $i => $m) {
    $archivo = "";
    if (isset($_FILES["mod_archivo_$i"])) {
        $archivo = subirArchivo($_FILES["mod_archivo_$i"], "modulos");
    }

    $tipo = $archivo ? "archivo" : "texto";

    $stmtMod->execute([
        $curso_id,
        $m["titulo"],
        $m["descripcion"],
        $tipo,
        $archivo
    ]);
}

// EVALUACIONES CON ARCHIVOS
$stmtEval = $conn->prepare(
    "INSERT INTO evaluaciones
    (curso_id, tipo, pregunta, opciones, respuesta_correcta, archivo)
    VALUES (?, ?, ?, ?, ?, ?)"
);

foreach ($evaluaciones as $i => $e) {
    $tipo     = $e["tipo"];
    $pregunta = $e["pregunta"];

    if ($tipo === "abierta") {
        $opciones = json_encode([]);
        $correcta = "";
    } else {
        $opciones = json_encode($e["opciones"] ?? []);
        $correcta = $e["correcta"] ?? "";
    }

    $archivo = "";
    if (isset($_FILES["eval_archivo_$i"])) {
        $archivo = subirArchivo($_FILES["eval_archivo_$i"], "evaluaciones");
    }

    $stmtEval->execute([$curso_id, $tipo, $pregunta, $opciones, $correcta, $archivo]);
}

echo json_encode([
    "success" => true,
    "message" => "Curso, módulos, evaluaciones y archivos guardados correctamente"
]);
