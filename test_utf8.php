<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Verificar el nombre de Peñarol
$res = $conn->query("SELECT nombre FROM equipos WHERE nombre LIKE '%Peñarol%' OR nombre LIKE '%Peñ%' LIMIT 1");
$equipo = $res->fetch(PDO::FETCH_ASSOC);

if($equipo) {
    echo "Nombre en BD: " . $equipo['nombre'] . "\n";
    echo "JSON Encode: " . json_encode($equipo) . "\n";
} else {
    echo "No se encontró el equipo Peñarol para probar.\n";
}
?>
