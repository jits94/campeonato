<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$res = $conn->query("SELECT nombre FROM equipos");
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo $r['nombre'] . "\n";
}
?>
