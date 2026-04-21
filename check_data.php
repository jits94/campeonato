<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die("Error de conexión");
}

$res = $conn->query("SELECT COUNT(*) FROM equipos");
$equipos = $res ? $res->fetchColumn() : "Error en query equipos";

$res2 = $conn->query("SELECT COUNT(*) FROM jugadores");
$jugadores = $res2 ? $res2->fetchColumn() : "Error en query jugadores";

echo "Equipos: " . $equipos . "\n";
echo "Jugadores: " . $jugadores . "\n";
?>
