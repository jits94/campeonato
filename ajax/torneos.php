<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if (in_array($action, ['crear', 'editar', 'eliminar']) && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'listar') {
    $stmt = $conn->query("SELECT * FROM torneos ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear' || $action === 'editar') {
    $nombre = $_POST['nombre'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    $id = $_POST['id_torneo'] ?? '';
    
    if (empty($nombre) || empty($tipo)) {
        echo json_encode(['success' => false, 'message' => 'El nombre y el tipo son requeridos']);
        exit;
    }

    if ($action === 'crear') {
        $query = "INSERT INTO torneos (nombre, tipo, estado) VALUES (:nombre, :tipo, :estado)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":estado", $estado);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Torneo creado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el torneo']);
        }
    } else {
        $query = "UPDATE torneos SET nombre = :nombre, tipo = :tipo, estado = :estado WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Torneo actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar torneo']);
        }
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id_torneo'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM torneos WHERE id = :id");
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Torneo y su información asociada fueron eliminados']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar torneo.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
