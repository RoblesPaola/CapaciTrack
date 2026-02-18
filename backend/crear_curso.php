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

    // Configuración de BD
    require_once __DIR__ . "/config/db.php";

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Error de conexión a BD: " . ($conn->connect_error ?? 'conn no definida'));
    }

    // Sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
    throw new Exception("No hay sesión activa.");
}

$creado_por = $_SESSION['user_id'];

file_put_contents($logFile, "Usuario ID REAL: $creado_por\n", FILE_APPEND);


    // Iniciar transacción
    $conn->begin_transaction();

    // =====================================================
    // OBTENER DATOS DEL FORMULARIO
    // =====================================================
    $titulo = trim($_POST['titulo'] ?? '');
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
        $fileType = $_FILES['portada']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Tipo de archivo no permitido para portada");
        }
        
        $extension = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'portada_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaCompleta = $uploadDir . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['portada']['tmp_name'], $rutaCompleta)) {
            $portadaPath = 'uploads/portadas/' . $nombreArchivo;
            file_put_contents($logFile, "✅ Portada guardada: $portadaPath\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "❌ Error al guardar portada\n", FILE_APPEND);
        }
    }

    // =====================================================
    // PROCESAR MÓDULOS
    // =====================================================
    $modulosJSON = $_POST['modulos'] ?? '[]';
    $modulos = json_decode($modulosJSON, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar módulos: " . json_last_error_msg());
    }
    
    file_put_contents($logFile, "Módulos recibidos: " . count($modulos) . "\n", FILE_APPEND);
    
    // Procesar archivos de módulos
    $uploadDirModulos = __DIR__ . '/uploads/modulos/';
    if (!file_exists($uploadDirModulos)) {
        mkdir($uploadDirModulos, 0777, true);
    }
    
    foreach ($modulos as $index => &$modulo) {
        $fileKey = "modulo_archivo_$index";
        
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $nombreArchivoMod = 'modulo_' . time() . '_' . uniqid() . '_' . $index . '.' . $extension;
            $rutaCompleta = $uploadDirModulos . $nombreArchivoMod;
            
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $rutaCompleta)) {
                $modulo['archivo'] = 'uploads/modulos/' . $nombreArchivoMod;
                file_put_contents($logFile, "✅ Archivo módulo $index: " . $modulo['archivo'] . "\n", FILE_APPEND);
            }
        }
    }
    unset($modulo);

    // =====================================================
    // PROCESAR EVALUACIONES
    // =====================================================
    $evaluacionesJSON = $_POST['evaluaciones'] ?? '[]';
    $evaluaciones = json_decode($evaluacionesJSON, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar evaluaciones: " . json_last_error_msg());
    }
    
    file_put_contents($logFile, "Evaluaciones recibidas: " . count($evaluaciones) . "\n", FILE_APPEND);

    // =====================================================
    // CREAR JSON DE CONTENIDO
    // =====================================================
    $contenidoJSON = json_encode([
        'modulos' => $modulos,
        'evaluaciones' => $evaluaciones
    ], JSON_UNESCAPED_UNICODE);

    // =====================================================
    // 1️⃣ INSERTAR CURSO
    // =====================================================
    $stmt = $conn->prepare("
        INSERT INTO cursos (titulo, descripcion, contenido, portada, creado_por, creado_en)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        throw new Exception("Error prepare curso: " . $conn->error);
    }
    
    $stmt->bind_param("ssssi", $titulo, $descripcion, $contenidoJSON, $portadaPath, $creado_por);
    
    if (!$stmt->execute()) {
        throw new Exception("Error execute curso: " . $stmt->error);
    }

    $cursoId = $conn->insert_id;
    
    if ($cursoId === 0) {
        throw new Exception("No se obtuvo ID del curso");
    }
    
    file_put_contents($logFile, "✅ Curso insertado - ID: $cursoId\n", FILE_APPEND);
    $stmt->close();

    // =====================================================
    // 2️⃣ INSERTAR MÓDULOS EN TABLA MODULOS
    // =====================================================
    $modulosInsertados = 0;
    
    if (!empty($modulos)) {
        file_put_contents($logFile, "\n--- INSERTANDO MÓDULOS EN TABLA ---\n", FILE_APPEND);
        
        $stmtModulo = $conn->prepare("
            INSERT INTO modulos (curso_id, titulo, descripcion, tipo_contenido, archivo, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmtModulo) {
            throw new Exception("Error prepare módulos: " . $conn->error);
        }
        
        foreach ($modulos as $index => $modulo) {
            $tituloMod = $modulo['titulo'] ?? '';
            $descripcionMod = $modulo['descripcion'] ?? '';
            $tipoContenido = $modulo['tipo_contenido'] ?? '';
            $archivo = $modulo['archivo'] ?? '';
            
            file_put_contents($logFile, "Módulo $index: $tituloMod\n", FILE_APPEND);
            
            $stmtModulo->bind_param(
                "issss",
                $cursoId,
                $tituloMod,
                $descripcionMod,
                $tipoContenido,
                $archivo
            );
            
            if (!$stmtModulo->execute()) {
                file_put_contents($logFile, "❌ Error módulo $index: " . $stmtModulo->error . "\n", FILE_APPEND);
            } else {
                $modulosInsertados++;
                file_put_contents($logFile, "✅ Módulo $index insertado - ID: " . $conn->insert_id . "\n", FILE_APPEND);
            }
        }
        
        $stmtModulo->close();
    }

    // =====================================================
    // 3️⃣ INSERTAR EVALUACIONES EN TABLA EVALUACIONES
    // =====================================================
    $evaluacionesInsertadas = 0;
    
    if (!empty($evaluaciones)) {
        file_put_contents($logFile, "\n--- INSERTANDO EVALUACIONES EN TABLA ---\n", FILE_APPEND);
        
        $stmtEval = $conn->prepare("
            INSERT INTO evaluaciones 
        (curso_id, tipo, pregunta, opciones, respuesta_correcta, puntos, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmtEval) {
            throw new Exception("Error prepare evaluaciones: " . $conn->error);
        }
        
        foreach ($evaluaciones as $index => $evaluacion) {
            $tipo = $evaluacion['tipo'] ?? '';
            $pregunta = $evaluacion['pregunta'] ?? '';
            $opciones = '';
            $respuestaCorrecta = '';
            $puntos = intval($evaluacion['puntos'] ?? 0);
            
            if ($tipo === 'opcion' && isset($evaluacion['opciones'])) {
                $opciones = json_encode($evaluacion['opciones'], JSON_UNESCAPED_UNICODE);
                $respuestaCorrecta = $evaluacion['correcta'] ?? '';
            }
            
            file_put_contents($logFile, "Evaluación $index: " . substr($pregunta, 0, 50) . "\n", FILE_APPEND);
            
            $stmtEval->bind_param(
                "issssi",
                $cursoId,
                $tipo,
                $pregunta,
                $opciones,
                $respuestaCorrecta,
                $puntos
            );
            
            if (!$stmtEval->execute()) {
                file_put_contents($logFile, "❌ Error eval $index: " . $stmtEval->error . "\n", FILE_APPEND);
            } else {
                $evaluacionesInsertadas++;
                file_put_contents($logFile, "✅ Evaluación $index insertada - ID: " . $conn->insert_id . "\n", FILE_APPEND);
            }
        }
        
        $stmtEval->close();
    }

    // =====================================================
    // COMMIT
    // =====================================================
    $conn->commit();
    
    file_put_contents($logFile, "\n" . str_repeat("=", 60) . "\n", FILE_APPEND);
    file_put_contents($logFile, "✅ ✅ ✅ ÉXITO TOTAL ✅ ✅ ✅\n", FILE_APPEND);
    file_put_contents($logFile, "Curso ID: $cursoId\n", FILE_APPEND);
    file_put_contents($logFile, "Módulos insertados: $modulosInsertados\n", FILE_APPEND);
    file_put_contents($logFile, "Evaluaciones insertadas: $evaluacionesInsertadas\n", FILE_APPEND);
    file_put_contents($logFile, str_repeat("=", 60) . "\n", FILE_APPEND);

    echo json_encode([
        "success" => true,
        "message" => "Curso creado exitosamente",
        "curso_id" => $cursoId,
        "modulos_insertados" => $modulosInsertados,
        "evaluaciones_insertadas" => $evaluacionesInsertadas
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    $errorMsg = $e->getMessage();
    file_put_contents($logFile, "\n❌❌❌ ERROR ❌❌❌\n", FILE_APPEND);
    file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
    file_put_contents($logFile, "Archivo: " . $e->getFile() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Línea: " . $e->getLine() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $errorMsg
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>