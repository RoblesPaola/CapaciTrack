<?php
// =====================================================
// CREAR_CURSO.PHP - VERSIÓN FINAL CORREGIDA
// =====================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$logFile = __DIR__ . '/debug_log.txt';
file_put_contents($logFile, "\n\n" . str_repeat("=", 60) . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - INICIO CREAR CURSO\n", FILE_APPEND);
file_put_contents($logFile, str_repeat("=", 60) . "\n", FILE_APPEND);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    require_once __DIR__ . "/config/db.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("No hay sesión activa.");
    }

    $creado_por = $_SESSION['user_id'];
    file_put_contents($logFile, "Usuario ID REAL: $creado_por\n", FILE_APPEND);

    $conn->beginTransaction();

    // =====================================================
    // OBTENER DATOS DEL FORMULARIO
    // =====================================================
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    file_put_contents($logFile, "Título: $titulo\n", FILE_APPEND);
    file_put_contents($logFile, "Descripción: " . substr($descripcion, 0, 100) . "\n", FILE_APPEND);

    if (empty($titulo) || empty($descripcion)) {
        throw new Exception("Título y descripción son obligatorios");
    }

    // =====================================================
    // PROCESAR PORTADA
    // =====================================================
    $portadaPath = null;

    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/portadas/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType     = $_FILES['portada']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Tipo de archivo no permitido para portada");
        }

        $extension    = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'portada_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaCompleta  = $uploadDir . $nombreArchivo;

        if (move_uploaded_file($_FILES['portada']['tmp_name'], $rutaCompleta)) {
            $portadaPath = 'uploads/portadas/' . $nombreArchivo;
            file_put_contents($logFile, "Portada guardada: $portadaPath\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "Error al guardar portada\n", FILE_APPEND);
        }
    }

    // =====================================================
    // PROCESAR MÓDULOS
    // =====================================================
    $modulosJSON = $_POST['modulos'] ?? '[]';
    $modulos     = json_decode($modulosJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar módulos: " . json_last_error_msg());
    }

    file_put_contents($logFile, "Módulos recibidos: " . count($modulos) . "\n", FILE_APPEND);

    $uploadDirModulos = __DIR__ . '/uploads/modulos/';
    if (!file_exists($uploadDirModulos)) {
        mkdir($uploadDirModulos, 0777, true);
    }

    foreach ($modulos as $index => &$modulo) {
        // Si el módulo es de tipo video, la URL de YouTube ya viene en $modulo['archivo']
        if (($modulo['tipo_contenido'] ?? '') === 'video') {
            file_put_contents($logFile, "Módulo $index es video YouTube: " . ($modulo['archivo'] ?? '') . "\n", FILE_APPEND);
            continue;
        }

        $fileKey = "modulo_archivo_$index";

        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $extension       = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $nombreArchivoMod = 'modulo_' . time() . '_' . uniqid() . '_' . $index . '.' . $extension;
            $rutaCompleta    = $uploadDirModulos . $nombreArchivoMod;

            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $rutaCompleta)) {
                $modulo['archivo'] = 'uploads/modulos/' . $nombreArchivoMod;
                file_put_contents($logFile, "Archivo módulo $index: " . $modulo['archivo'] . "\n", FILE_APPEND);
            }
        }
    }
    unset($modulo);

    // =====================================================
    // PROCESAR EVALUACIONES
    // =====================================================
    $evaluacionesJSON = $_POST['evaluaciones'] ?? '[]';
    $evaluaciones     = json_decode($evaluacionesJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar evaluaciones: " . json_last_error_msg());
    }

    file_put_contents($logFile, "Evaluaciones recibidas: " . count($evaluaciones) . "\n", FILE_APPEND);

    // =====================================================
    // CREAR JSON DE CONTENIDO
    // =====================================================
    $contenidoJSON = json_encode([
        'modulos'      => $modulos,
        'evaluaciones' => $evaluaciones
    ], JSON_UNESCAPED_UNICODE);

    // =====================================================
    // 1 INSERTAR CURSO
    // =====================================================
    $stmt = $conn->prepare("
        INSERT INTO cursos (titulo, descripcion, contenido, portada, creado_por, creado_en)
        VALUES (?, ?, ?, ?, ?, NOW())
        RETURNING id
    ");
    $stmt->execute([$titulo, $descripcion, $contenidoJSON, $portadaPath, $creado_por]);
    $cursoId = $stmt->fetchColumn();

    if (!$cursoId) {
        throw new Exception("No se obtuvo ID del curso");
    }

    file_put_contents($logFile, "Curso insertado - ID: $cursoId\n", FILE_APPEND);

    // =====================================================
    // 2 INSERTAR MÓDULOS EN TABLA MODULOS
    // =====================================================
    $modulosInsertados = 0;

    if (!empty($modulos)) {
        file_put_contents($logFile, "\n--- INSERTANDO MÓDULOS EN TABLA ---\n", FILE_APPEND);

        $stmtModulo = $conn->prepare("
            INSERT INTO modulos (curso_id, titulo, descripcion, tipo_contenido, archivo, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        foreach ($modulos as $index => $modulo) {
            $tituloMod      = $modulo['titulo'] ?? '';
            $descripcionMod = $modulo['descripcion'] ?? '';
            $tipoContenido  = $modulo['tipo_contenido'] ?? '';
            $archivo        = $modulo['archivo'] ?? '';

            file_put_contents($logFile, "Módulo $index: $tituloMod\n", FILE_APPEND);

            try {
                $stmtModulo->execute([$cursoId, $tituloMod, $descripcionMod, $tipoContenido, $archivo]);
                $modulosInsertados++;
                file_put_contents($logFile, "Módulo $index insertado\n", FILE_APPEND);
            } catch (PDOException $ex) {
                file_put_contents($logFile, "Error módulo $index: " . $ex->getMessage() . "\n", FILE_APPEND);
            }
        }
    }

    // =====================================================
    // 3 INSERTAR EVALUACIONES EN TABLA EVALUACIONES
    // =====================================================
    $evaluacionesInsertadas = 0;

    if (!empty($evaluaciones)) {
        file_put_contents($logFile, "\n--- INSERTANDO EVALUACIONES EN TABLA ---\n", FILE_APPEND);

        $uploadDirPreguntas = __DIR__ . '/uploads/preguntas/';
        if (!file_exists($uploadDirPreguntas)) {
            mkdir($uploadDirPreguntas, 0777, true);
        }

        $stmtEval = $conn->prepare("
            INSERT INTO evaluaciones
            (curso_id, tipo, pregunta, opciones, respuesta_correcta, puntos, imagen)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($evaluaciones as $index => $evaluacion) {
            $tipo             = $evaluacion['tipo'] ?? '';
            $pregunta         = $evaluacion['pregunta'] ?? '';
            $opciones         = '';
            $respuestaCorrecta = '';
            $puntos           = intval($evaluacion['puntos'] ?? 0);
            $imagen           = null;

            if ($tipo === 'opcion' && isset($evaluacion['opciones'])) {
                $opciones          = json_encode($evaluacion['opciones'], JSON_UNESCAPED_UNICODE);
                $respuestaCorrecta = $evaluacion['correcta'] ?? '';
            }

            $fileKey = "pregunta_imagen_$index";

            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileType     = $_FILES[$fileKey]['type'];

                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Formato de imagen no permitido en pregunta $index");
                }

                $extension    = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $nombreArchivo = 'pregunta_' . time() . '_' . uniqid() . '_' . $index . '.' . $extension;
                $rutaCompleta  = $uploadDirPreguntas . $nombreArchivo;

                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $rutaCompleta)) {
                    $imagen = 'uploads/preguntas/' . $nombreArchivo;
                    file_put_contents($logFile, "Imagen pregunta $index guardada: $imagen\n", FILE_APPEND);
                }
            }

            file_put_contents($logFile, "Evaluación $index: " . substr($pregunta, 0, 50) . "\n", FILE_APPEND);

            try {
                $stmtEval->execute([$cursoId, $tipo, $pregunta, $opciones, $respuestaCorrecta, $puntos, $imagen]);
                $evaluacionesInsertadas++;
                file_put_contents($logFile, "Evaluación $index insertada\n", FILE_APPEND);
            } catch (PDOException $ex) {
                file_put_contents($logFile, "Error eval $index: " . $ex->getMessage() . "\n", FILE_APPEND);
            }
        }
    }

    // =====================================================
    // COMMIT
    // =====================================================
    $conn->commit();

    file_put_contents($logFile, "\n" . str_repeat("=", 60) . "\n", FILE_APPEND);
    file_put_contents($logFile, "EXITO TOTAL\n", FILE_APPEND);
    file_put_contents($logFile, "Curso ID: $cursoId\n", FILE_APPEND);
    file_put_contents($logFile, "Módulos insertados: $modulosInsertados\n", FILE_APPEND);
    file_put_contents($logFile, "Evaluaciones insertadas: $evaluacionesInsertadas\n", FILE_APPEND);
    file_put_contents($logFile, str_repeat("=", 60) . "\n", FILE_APPEND);

    echo json_encode([
        "success"                 => true,
        "message"                 => "Curso creado exitosamente",
        "curso_id"                => $cursoId,
        "modulos_insertados"      => $modulosInsertados,
        "evaluaciones_insertadas" => $evaluacionesInsertadas
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $errorMsg = $e->getMessage();
    file_put_contents($logFile, "\nERROR\n", FILE_APPEND);
    file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
    file_put_contents($logFile, "Archivo: " . $e->getFile() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Línea: " . $e->getLine() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode([
        "success"  => false,
        "message"  => $errorMsg
    ]);
}

$conn = null;
?>
