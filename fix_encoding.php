<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Corregir Peñarol
$stmt = $conn->prepare("UPDATE equipos SET nombre = 'Peñarol' WHERE nombre LIKE 'Pe%' AND (nombre LIKE '%├▒%' OR nombre LIKE '%Ã±%')");
$stmt->execute();

if($stmt->rowCount() > 0) {
    echo "Se corrigió el nombre de Peñarol.\n";
} else {
    // Intento forzado si el like falla por el char especial
    $conn->query("UPDATE equipos SET nombre = 'Peñarol' WHERE id = 9"); 
    echo "Actualización forzada aplicada.\n";
}

// Verificar de nuevo
$res = $conn->query("SELECT nombre FROM equipos WHERE id = 9");
echo "Nombre final: " . $res->fetchColumn() . "\n";
?>
