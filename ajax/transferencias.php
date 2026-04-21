<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if (in_array($action, ['crear']) && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'listar') {
    $query = "SELECT t.*, 
                     j.nombre as jugador_nombre, 
                     eo.nombre as origen_nombre, 
                     ed.nombre as destino_nombre, 
                     COALESCE(trn.nombre, 'General') as torneo_nombre 
              FROM transferencias t
              INNER JOIN jugadores j ON t.jugador_id = j.id
              INNER JOIN equipos eo ON t.equipo_origen_id = eo.id
              INNER JOIN equipos ed ON t.equipo_destino_id = ed.id
              LEFT JOIN torneos trn ON t.torneo_id = trn.id
              ORDER BY t.id DESC";
              
    $stmt = $conn->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($data as &$row) {
        $row['fecha'] = date('d/m/Y H:i', strtotime($row['fecha']));
    }
    
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear') {
    $jugador_id = $_POST['jugador_id'] ?? '';
    $origen_id = $_POST['equipo_origen_id'] ?? '';
    $destino_id = $_POST['equipo_destino_id'] ?? '';
    $monto = $_POST['monto'] ?? '';
    $torneo_id = !empty($_POST['torneo_id']) ? $_POST['torneo_id'] : null;
    
    if (empty($jugador_id) || empty($origen_id) || empty($destino_id) || $monto === '') {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }
    
    if ($origen_id == $destino_id) {
        echo json_encode(['success' => false, 'message' => 'El destino no puede ser igual al origen.']);
        exit;
    }

    $conn->beginTransaction();
    try {
        // 1. Registrar transferencia
        $queryT = "INSERT INTO transferencias (jugador_id, equipo_origen_id, equipo_destino_id, torneo_id, monto) VALUES (:j_id, :eo_id, :ed_id, :t_id, :monto)";
        $stmtT = $conn->prepare($queryT);
        $stmtT->bindParam(":j_id", $jugador_id);
        $stmtT->bindParam(":eo_id", $origen_id);
        $stmtT->bindParam(":ed_id", $destino_id);
        $stmtT->bindParam(":t_id", $torneo_id);
        $stmtT->bindParam(":monto", $monto);
        $stmtT->execute();
        
        // 2. Actualizar jugador
        $queryJ = "UPDATE jugadores SET equipo_id = :ed_id WHERE id = :j_id";
        $stmtJ = $conn->prepare($queryJ);
        $stmtJ->bindParam(":ed_id", $destino_id);
        $stmtJ->bindParam(":j_id", $jugador_id);
        $stmtJ->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transferencia registrada correctamente']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al procesar transferencia: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
