<?php
session_start();
require_once __DIR__ . "/config/db.php"; // ajustar ruta
echo json_encode(["ok" => true]);
