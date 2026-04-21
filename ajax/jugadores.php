<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// Verificar rol para acciones que modifican
if (in_array($action, ['crear', 'editar', 'eliminar']) && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'listar') {
    $equipo_id = isset($_GET['equipo_id']) ? (int)$_GET['equipo_id'] : 0;
    
    $where = "";
    if ($equipo_id > 0) {
        $where = "WHERE j.equipo_id = $equipo_id";
    }

    $query = "SELECT j.*, e.nombre as equipo_nombre, e.logo as equipo_logo
              FROM jugadores j 
              INNER JOIN equipos e ON j.equipo_id = e.id 
              $where
              ORDER BY j.id DESC";
    $stmt = $conn->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($data as &$row) {
        $row['fecha_registro'] = date('d/m/Y', strtotime($row['fecha_registro']));
    }
    
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear' || $action === 'editar') {
    $nombre = $_POST['nombre'] ?? '';
    $ci = $_POST['ci'] ?? '';
    $equipo_id = $_POST['equipo_id'] ?? '';
    $dorsal = $_POST['dorsal'] ?? null;
    $id = $_POST['id_jugador'] ?? '';
    
    if (empty($nombre) || empty($ci) || empty($equipo_id)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos marcados con * son requeridos']);
        exit;
    }

    // Upload Foto
    $foto_name = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/fotos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $tmpName = $_FILES['foto']['tmp_name'];
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['foto']['name']));
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($tmpName, $destPath)) {
            $foto_name = $fileName;
        }
    }

    if ($action === 'crear') {
        $query = "INSERT INTO jugadores (nombre, ci, equipo_id, dorsal" . ($foto_name ? ", foto" : "") . ") VALUES (:nombre, :ci, :equipo_id, :dorsal" . ($foto_name ? ", :foto" : "") . ")";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":ci", $ci);
        $stmt->bindParam(":equipo_id", $equipo_id);
        $stmt->bindParam(":dorsal", $dorsal);
        if ($foto_name) $stmt->bindParam(":foto", $foto_name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Jugador registrado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar jugador']);
        }
    } else {
        $query = "UPDATE jugadores SET nombre = :nombre, ci = :ci, equipo_id = :equipo_id, dorsal = :dorsal";
        if ($foto_name) $query .= ", foto = :foto";
        $query .= " WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":ci", $ci);
        $stmt->bindParam(":equipo_id", $equipo_id);
        $stmt->bindParam(":dorsal", $dorsal);
        $stmt->bindParam(":id", $id);
        if ($foto_name) $stmt->bindParam(":foto", $foto_name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Jugador actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar jugador']);
        }
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id_jugador'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM jugadores WHERE id = :id");
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Jugador eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar jugador.']);
    }
    exit;
}

if ($action === 'actualizar_foto') {
    $id = $_POST['id_jugador'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID de jugador requerido']);
        exit;
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/fotos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $tmpName = $_FILES['foto']['tmp_name'];
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['foto']['name']));
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($tmpName, $destPath)) {
            $stmt = $conn->prepare("UPDATE jugadores SET foto = :foto WHERE id = :id");
            $stmt->bindParam(":foto", $fileName);
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Foto actualizada correctamente', 'foto' => $fileName]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en subida: ' . ($_FILES['foto']['error'] ?? 'desconocido')]);
    }
    exit;
}

if ($action === 'perfil_completo') {
    $id = $_GET['id'] ?? 0;
    
    // 1. Datos básicos
    $stmt = $conn->prepare("SELECT j.*, e.nombre as equipo_actual, e.logo as equipo_logo 
                           FROM jugadores j 
                           JOIN equipos e ON j.equipo_id = e.id 
                           WHERE j.id = :id");
    $stmt->execute([':id' => $id]);
    $jugador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jugador) {
        echo json_encode(['success' => false, 'message' => 'Jugador no encontrado']);
        exit;
    }

    // 2. Estadísticas Generales
    $stats = [];
    $stats['goles'] = $conn->query("SELECT COUNT(*) FROM eventos_partido WHERE jugador_id = $id AND tipo = 'gol'")->fetchColumn();
    $stats['amarillas'] = $conn->query("SELECT COUNT(*) FROM eventos_partido WHERE jugador_id = $id AND tipo = 'amarilla'")->fetchColumn();
    $stats['rojas'] = $conn->query("SELECT COUNT(*) FROM eventos_partido WHERE jugador_id = $id AND tipo = 'roja'")->fetchColumn();
    $stats['transf'] = $conn->query("SELECT COUNT(*) FROM transferencias WHERE jugador_id = $id")->fetchColumn();
    $stats['partidos'] = $conn->query("SELECT COUNT(DISTINCT partido_id) FROM eventos_partido WHERE jugador_id = $id")->fetchColumn();

    // 3. Historial de Goles
    $stmtG = $conn->query("SELECT ep.minuto, p.fecha, p.goles_local, p.goles_visitante,
                                 el.nombre as local, ev.nombre as visitante, t.nombre as torneo
                          FROM eventos_partido ep
                          JOIN partidos p ON ep.partido_id = p.id
                          JOIN torneos t ON p.torneo_id = t.id
                          JOIN equipos el ON p.equipo_local_id = el.id
                          JOIN equipos ev ON p.equipo_visitante_id = ev.id
                          WHERE ep.jugador_id = $id AND ep.tipo = 'gol'
                          ORDER BY p.fecha DESC");
    $goles = $stmtG->fetchAll(PDO::FETCH_ASSOC);

    // 4. Historial de Sanciones
    $stmtS = $conn->query("SELECT ep.tipo, ep.minuto, s.monto, s.estado as pago, p.fecha, t.nombre as torneo,
                                  el.nombre as local_nombre, ev.nombre as visitante_nombre
                          FROM eventos_partido ep
                          LEFT JOIN sanciones s ON s.evento_id = ep.id
                          JOIN partidos p ON ep.partido_id = p.id
                          JOIN torneos t ON p.torneo_id = t.id
                          JOIN equipos el ON p.equipo_local_id = el.id
                          JOIN equipos ev ON p.equipo_visitante_id = ev.id
                          WHERE ep.jugador_id = $id AND ep.tipo IN ('amarilla', 'roja')
                          ORDER BY p.fecha DESC");
    $sanciones = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    // 5. Historial de Transferencias
    $stmtT = $conn->query("SELECT t.*, eo.nombre as origen, ed.nombre as destino, tor.nombre as torneo
                          FROM transferencias t
                          JOIN equipos eo ON t.equipo_origen_id = eo.id
                          JOIN equipos ed ON t.equipo_destino_id = ed.id
                          LEFT JOIN torneos tor ON t.torneo_id = tor.id
                          WHERE t.jugador_id = $id
                          ORDER BY t.fecha DESC");
    $transferencias = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    // 6. Historial de Equipos (basado en eventos o transferencias)
    // Para simplificar, mostramos el equipo actual y los previos de transferencias
    $equipos_hist = $conn->query("SELECT DISTINCT e.nombre, e.logo 
                                 FROM equipos e 
                                 WHERE e.id = {$jugador['equipo_id']}
                                 OR e.id IN (SELECT equipo_origen_id FROM transferencias WHERE jugador_id = $id)")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'jugador' => $jugador,
        'stats' => $stats,
        'goles' => $goles,
        'sanciones' => $sanciones,
        'transferencias' => $transferencias,
        'equipos' => $equipos_hist
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
