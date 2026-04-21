<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_REQUEST['action'] ?? '';

if($action === 'listar') {
    $stmt = $conn->query("SELECT d.*, t.nombre as torneo 
                          FROM directiva d 
                          LEFT JOIN torneos t ON d.torneo_id = t.id 
                          ORDER BY d.id ASC");
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if($action === 'cambiar_estado') {
    $id = $_POST['id'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';

    $stmt = $conn->prepare("UPDATE directiva SET estado = :estado WHERE id = :id");
    if ($stmt->execute([':estado' => $estado, ':id' => $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
    exit;
}

if($action === 'guardar') {
    $id = $_POST['id'] ?? '';
    $torneo_id = $_POST['torneo_id'] ?? '';
    $nom = $_POST['nombre'] ?? '';
    $car = $_POST['cargo'] ?? '';
    $tel = $_POST['telefono'] ?? '';
    
    if(empty($torneo_id) || empty($nom) || empty($car)) {
        echo json_encode(['success' => false, 'message' => 'Torneo, nombre y cargo son obligatorios']);
        exit;
    }

    if(empty($id)) {
        $stmt = $conn->prepare("INSERT INTO directiva (torneo_id, nombre, cargo, telefono) VALUES (:tid, :nom, :car, :tel)");
    } else {
        $stmt = $conn->prepare("UPDATE directiva SET torneo_id=:tid, nombre=:nom, cargo=:car, telefono=:tel WHERE id=:id");
        $stmt->bindParam(':id', $id);
    }
    $stmt->bindParam(':tid', $torneo_id);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':car', $car);
    $stmt->bindParam(':tel', $tel);
    
    if($stmt->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        $error = $stmt->errorInfo();
        echo json_encode(['success'=>false, 'message'=>'Error de BD: ' . $error[2]]);
    }
    exit;
}

if($action === 'eliminar') {
    // Redirigir a cambio de estado para evitar borrado físico accidental
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("UPDATE directiva SET estado = 'inactivo' WHERE id = :id");
    if($stmt->execute([':id' => $id])) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Error al desactivar']);
    }
    exit;
}
