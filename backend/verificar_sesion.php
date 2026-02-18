<?php
session_start();
header("Content-Type: application/json");

// Si existe sesión
if (isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => true,
        "user_id" => $_SESSION["user_id"],
        "nombre"  => $_SESSION["nombre"],
        "rol"     => $_SESSION["rol"]
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa"
    ]);
}
