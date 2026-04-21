<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if (in_array($action, ['sortear', 'borrar_sorteo']) && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'verificar') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    if(empty($torneo_id)) {
        echo json_encode(['success' => false, 'message' => 'Torneo no especificado']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, nombre_grupo FROM grupos WHERE torneo_id = :torneo");
    $stmt->bindParam(":torneo", $torneo_id);
    $stmt->execute();
    
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($grupos) > 0) {
        $resultado = [];
        foreach($grupos as $g) {
            $stmtEq = $conn->prepare("SELECT e.id, e.nombre FROM grupo_equipos ge INNER JOIN equipos e ON ge.equipo_id = e.id WHERE ge.grupo_id = :g_id");
            $stmtEq->bindParam(":g_id", $g['id']);
            $stmtEq->execute();
            $equipos = $stmtEq->fetchAll(PDO::FETCH_ASSOC);
            $resultado[$g['nombre_grupo']] = $equipos;
        }
        echo json_encode(['success' => true, 'tiene_grupos' => true, 'data' => $resultado]);
    } else {
        $stmtInsc = $conn->prepare("SELECT COUNT(*) FROM inscripciones WHERE torneo_id = :torneo");
        $stmtInsc->bindParam(":torneo", $torneo_id);
        $stmtInsc->execute();
        $total = $stmtInsc->fetchColumn();
        echo json_encode(['success' => true, 'tiene_grupos' => false, 'total_inscritos' => $total]);
    }
    exit;
}

if ($action === 'sortear') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    $num_grupos = intval($_POST['num_grupos'] ?? 0);
    
    if(empty($torneo_id) || $num_grupos < 2) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    // Obtener equipos inscritos
    $stmtInsc = $conn->prepare("SELECT equipo_id FROM inscripciones WHERE torneo_id = :torneo");
    $stmtInsc->bindParam(":torneo", $torneo_id);
    $stmtInsc->execute();
    $equipos = $stmtInsc->fetchAll(PDO::FETCH_COLUMN);
    
    if(count($equipos) < $num_grupos) {
        echo json_encode(['success' => false, 'message' => 'Hay menos equipos inscritos que grupos solicitados.']);
        exit;
    }
    
    // Mezclar equipos (Sorteo)
    shuffle($equipos);
    
    // Distribuir en grupos
    $letras = range('A', 'Z');
    $conn->beginTransaction();
    try {
        $grupos_ids = [];
        // Crear los N grupos en BD
        for($i=0; $i<$num_grupos; $i++) {
            $nombre_g = "Grupo " . $letras[$i];
            $stmtG = $conn->prepare("INSERT INTO grupos (torneo_id, nombre_grupo) VALUES (:torneo, :nombre)");
            $stmtG->bindParam(":torneo", $torneo_id);
            $stmtG->bindParam(":nombre", $nombre_g);
            $stmtG->execute();
            $grupos_ids[] = $conn->lastInsertId();
        }
        
        // Asignar equipos ronda robin a los grupos para que queden balanceados
        foreach($equipos as $index => $eq_id) {
            $grupo_idx = $index % $num_grupos;
            $g_id = $grupos_ids[$grupo_idx];
            
            $stmtGe = $conn->prepare("INSERT INTO grupo_equipos (grupo_id, equipo_id) VALUES (:g_id, :eq_id)");
            $stmtGe->bindParam(":g_id", $g_id);
            $stmtGe->bindParam(":eq_id", $eq_id);
            $stmtGe->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Grupos generados exitosamente.']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en la BD durante el sorteo.']);
    }
    exit;
}

if ($action === 'borrar_sorteo') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    if(empty($torneo_id)) {
        echo json_encode(['success' => false, 'message' => 'Torneo no especificado']);
        exit;
    }
    
    // El ON DELETE CASCADE se encarga de grupo_equipos si borramos el grupo
    $stmt = $conn->prepare("DELETE FROM grupos WHERE torneo_id = :torneo");
    $stmt->bindParam(":torneo", $torneo_id);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'El sorteo fue anulado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al anular.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
