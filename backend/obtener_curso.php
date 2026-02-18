<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if(!isset($_SESSION["user_id"])){
  echo json_encode(["success"=>false,"message"=>"No autorizado"]);
  exit;
}

$cursoId = intval($_GET["id"] ?? 0);
if($cursoId <= 0){
  echo json_encode(["success"=>false,"message"=>"Curso inválido"]);
  exit;
}

/* CURSO */
$stmt = $conn->prepare("SELECT id,titulo,descripcion FROM cursos WHERE id=?");
$stmt->bind_param("i",$cursoId);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();
if(!$curso){
  echo json_encode(["success"=>false,"message"=>"Curso no encontrado"]);
  exit;
}

/* MODULOS */
$stmt = $conn->prepare("SELECT id,titulo,contenido FROM modulos WHERE curso_id=? ORDER BY id");
$stmt->bind_param("i",$cursoId);
$stmt->execute();
$curso["modulos"] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* EVALUACIONES */
$stmt = $conn->prepare("
  SELECT pregunta,opciones,respuesta_correcta,puntos
  FROM evaluaciones
  WHERE curso_id=?
  ORDER BY id
");
$stmt->bind_param("i",$cursoId);
$stmt->execute();
$curso["evaluaciones"] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(["success"=>true,"curso"=>$curso]);
