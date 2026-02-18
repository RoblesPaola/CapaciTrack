<?php
// test_guardar_curso.php
// Script para probar el guardado de cursos y ver errores detallados

session_start();

// Simular sesión de usuario (QUITAR ESTO EN PRODUCCIÓN)
if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1; // Cambiar por un ID válido de tu tabla usuarios
}

echo "<h2>Test de Guardado de Curso</h2>";
echo "<hr>";

require "config/db.php";

// Verificar conexión
echo "<h3>1. Conexión a BD</h3>";
if ($conn->connect_error) {
    echo "❌ Error: " . $conn->connect_error . "<br>";
    exit;
}
echo "✅ Conectado correctamente<br>";
echo "Base de datos: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br><br>";

// Verificar estructura de cursos
echo "<h3>2. Estructura de tabla 'cursos'</h3>";
$result = $conn->query("DESCRIBE cursos");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td></tr>";
    }
    echo "</table><br>";
}

// Probar inserción simple
echo "<h3>3. Prueba de Inserción</h3>";

$titulo = "Curso de Prueba " . date('Y-m-d H:i:s');
$descripcion = "Esta es una descripción de prueba";
$contenido = "";
$portada = "";
$user_id = $_SESSION["user_id"];

echo "Datos a insertar:<br>";
echo "- Título: $titulo<br>";
echo "- Descripción: $descripcion<br>";
echo "- User ID: $user_id<br><br>";

$stmt = $conn->prepare("INSERT INTO cursos (titulo, descripcion, contenido, creado_por, portada, creado_en) VALUES (?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo "❌ Error en prepare: " . $conn->error . "<br>";
    echo "Código de error: " . $conn->errno . "<br>";
    exit;
}

echo "✅ Prepare exitoso<br>";

$stmt->bind_param("sssis", $titulo, $descripcion, $contenido, $user_id, $portada);

if (!$stmt->execute()) {
    echo "❌ Error en execute: " . $stmt->error . "<br>";
    echo "Código de error: " . $stmt->errno . "<br>";
    exit;
}

$curso_id = $conn->insert_id;
echo "✅ <strong>Curso insertado con ID: $curso_id</strong><br><br>";

// Verificar que se guardó
echo "<h3>4. Verificación</h3>";
$result = $conn->query("SELECT * FROM cursos WHERE id = $curso_id");
if ($result && $result->num_rows > 0) {
    $curso = $result->fetch_assoc();
    echo "✅ Curso encontrado en la base de datos:<br>";
    echo "<pre>" . print_r($curso, true) . "</pre>";
} else {
    echo "❌ No se encontró el curso<br>";
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<h3>5. Conclusión</h3>";
echo "Si ves el curso insertado arriba, significa que el problema está en el formulario HTML o en cómo se envían los datos.<br>";
echo "Si hay errores, el problema está en la base de datos o permisos.<br>";
?>