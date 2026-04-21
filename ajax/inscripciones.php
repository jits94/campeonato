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
    $torneo_id = $_GET['torneo_id'] ?? '';
    if(empty($torneo_id)) {
        echo json_encode(['data' => []]);
        exit;
    }
    
    $query = "SELECT i.*, e.nombre as equipo_nombre 
              FROM inscripciones i 
              INNER JOIN equipos e ON i.equipo_id = e.id 
              WHERE i.torneo_id = :torneo
              ORDER BY i.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":torneo", $torneo_id);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear' || $action === 'editar') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    // equipo_id can be passed on crear. On edit, select is disabled so it might be missing
    // if editing real value goes unchanged, or we rely on id_inscripcion.
    $monto = $_POST['monto'] ?? '';
    $estado = $_POST['estado'] ?? 'borrador';
    $id = $_POST['id_inscripcion'] ?? '';
    
    if (empty($torneo_id) || $monto === '') {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    if ($action === 'crear') {
        $equipos_ids = $_POST['equipo_id'] ?? [];
        if(empty($equipos_ids)) {
            echo json_encode(['success' => false, 'message' => 'Seleccione al menos un equipo']);
            exit;
        }

        $realizados = 0;
        $errores = 0;
        $ya_inscritos = 0;

        foreach ($equipos_ids as $equipo_id) {
            // Verificar si ya está inscrito
            $check = $conn->prepare("SELECT id FROM inscripciones WHERE torneo_id = :torneo_id AND equipo_id = :equipo_id");
            $check->execute([':torneo_id'=>$torneo_id, ':equipo_id'=>$equipo_id]);
            if($check->rowCount() > 0) {
                $ya_inscritos++;
                continue;
            }

            $query = "INSERT INTO inscripciones (torneo_id, equipo_id, monto_cobrado, estado) VALUES (:torneo_id, :equipo_id, :monto, :estado)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":torneo_id", $torneo_id);
            $stmt->bindParam(":equipo_id", $equipo_id);
            $stmt->bindParam(":monto", $monto);
            $stmt->bindParam(":estado", $estado);
            
            if ($stmt->execute()) {
                $realizados++;
            } else {
                $errores++;
            }
        }

        $msg = "Inscripciones procesadas: $realizados nuevas.";
        if ($ya_inscritos > 0) $msg .= " ($ya_inscritos ya estaban inscritos)";
        if ($errores > 0) $msg .= " Hubo $errores errores.";
        
        echo json_encode(['success' => true, 'message' => $msg]);
    } else { // Editar
        $query = "UPDATE inscripciones SET monto_cobrado = :monto, estado = :estado WHERE id = :id AND torneo_id = :torneo_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":monto", $monto);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":torneo_id", $torneo_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inscripción actualizada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar inscripción']);
        }
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id_inscripcion'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM inscripciones WHERE id = :id");
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Inscripción anulada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al anular.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
