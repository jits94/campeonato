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
    $stmt = $conn->query("SELECT * FROM equipos ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear la fecha
    foreach($data as &$row) {
        $row['fecha_registro'] = date('d/m/Y H:i', strtotime($row['fecha_registro']));
    }
    
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear' || $action === 'editar') {
    $nombre = $_POST['nombre'] ?? '';
    $id = $_POST['id_equipo'] ?? '';
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        exit;
    }
    
    // Upload Logo
    $logo_name = "";
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $tmpName = $_FILES['logo']['tmp_name'];
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['logo']['name']));
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($tmpName, $destPath)) {
            $logo_name = $fileName;
        }
    }

    if ($action === 'crear') {
        $query = "INSERT INTO equipos (nombre" . ($logo_name ? ", logo" : "") . ") VALUES (:nombre" . ($logo_name ? ", :logo" : "") . ")";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        if ($logo_name) $stmt->bindParam(":logo", $logo_name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Equipo creado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear equipo']);
        }
    } else { // editar
        // Obtener logo actual por si queremos borrar (opcional)
        $update_query = "UPDATE equipos SET nombre = :nombre";
        if ($logo_name) {
            $update_query .= ", logo = :logo";
        }
        $update_query .= " WHERE id = :id";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":id", $id);
        if ($logo_name) $stmt->bindParam(":logo", $logo_name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Equipo actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar equipo']);
        }
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id_equipo'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }
    
    // Obtener info para borrar logo
    $stmtInfo = $conn->prepare("SELECT logo FROM equipos WHERE id = :id");
    $stmtInfo->bindParam(":id", $id);
    $stmtInfo->execute();
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("DELETE FROM equipos WHERE id = :id");
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        if ($info && $info['logo'] != 'default.png' && file_exists('../uploads/logos/' . $info['logo'])) {
            unlink('../uploads/logos/' . $info['logo']);
        }
        echo json_encode(['success' => true, 'message' => 'Equipo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar. Verifique que no esté siendo usado.']);
    }
    exit;
}

if ($action === 'perfil_completo') {
    $id = $_GET['id'] ?? 0;
    
    // 1. Datos básicos
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipo) {
        echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
        exit;
    }

    $stats = [];
    try {
        $stats['torneos'] = $conn->query("
            SELECT COUNT(DISTINCT torneo_id) FROM (
                SELECT torneo_id FROM inscripciones WHERE equipo_id = $id
                UNION
                SELECT torneo_id FROM partidos WHERE equipo_local_id = $id OR equipo_visitante_id = $id
            ) t")->fetchColumn();
        $stats['jugadores'] = $conn->query("SELECT COUNT(*) FROM jugadores WHERE equipo_id = $id")->fetchColumn();
        $stats['pj'] = $conn->query("SELECT COUNT(*) FROM partidos WHERE (equipo_local_id = $id OR equipo_visitante_id = $id) AND estado = 'finalizado'")->fetchColumn();
        $stats['gf'] = $conn->query("SELECT SUM(CASE WHEN equipo_local_id = $id THEN goles_local ELSE goles_visitante END) FROM partidos WHERE (equipo_local_id = $id OR equipo_visitante_id = $id) AND estado = 'finalizado'")->fetchColumn() ?: 0;
        $stats['gc'] = $conn->query("SELECT SUM(CASE WHEN equipo_local_id = $id THEN goles_visitante ELSE goles_local END) FROM partidos WHERE (equipo_local_id = $id OR equipo_visitante_id = $id) AND estado = 'finalizado'")->fetchColumn() ?: 0;

        $jugadores = $conn->query("SELECT id, nombre, dorsal, foto FROM jugadores WHERE equipo_id = $id ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        $partidos = $conn->query("SELECT p.*, t.nombre as torneo, el.nombre as local, ev.nombre as visitante, el.logo as local_logo, ev.logo as visitante_logo
                                  FROM partidos p
                                  JOIN torneos t ON p.torneo_id = t.id
                                  JOIN equipos el ON p.equipo_local_id = el.id
                                  JOIN equipos ev ON p.equipo_visitante_id = ev.id
                                  WHERE (p.equipo_local_id = $id OR p.equipo_visitante_id = $id) AND p.estado = 'finalizado'
                                  ORDER BY p.fecha DESC, p.hora DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

        $stmtT = $conn->query("SELECT id, nombre FROM torneos WHERE estado = 'finalizado' ORDER BY id DESC");
        $torneos = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $palmares = [];

    foreach ($torneos as $torneo) {
        $tid = $torneo['id'];

        $sql = "SELECT 
                    e.id,
                    SUM(CASE 
                        WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) 
                          OR (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) THEN 3
                        WHEN p.goles_local = p.goles_visitante THEN 1
                        ELSE 0 END) AS puntos,
                    SUM(CASE WHEN p.equipo_local_id = e.id THEN p.goles_local ELSE p.goles_visitante END) AS gf,
                    SUM(CASE WHEN p.equipo_local_id = e.id THEN p.goles_visitante ELSE p.goles_local END) AS gc
                FROM partidos p
                JOIN equipos e ON e.id = p.equipo_local_id OR e.id = p.equipo_visitante_id
                WHERE p.torneo_id = $tid AND p.estado = 'finalizado'
                GROUP BY e.id
                ORDER BY puntos DESC, (gf - gc) DESC
                LIMIT 2"; 
        
        $standings = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($standings) > 0 && $standings[0]['id'] == $id) {
            $palmares[] = ['torneo' => $torneo['nombre'], 'titulo' => 'Campeón', 'color' => 'warning', 'icon' => 'fa-crown'];
        } else if (count($standings) > 1 && $standings[1]['id'] == $id) {
            $palmares[] = ['torneo' => $torneo['nombre'], 'titulo' => 'Subcampeón', 'color' => 'secondary', 'icon' => 'fa-medal'];
        }
    }

    $historial_torneos = [];
    $stmtTInscrito = $conn->query("SELECT t.id, t.nombre FROM inscripciones i INNER JOIN torneos t ON i.torneo_id = t.id WHERE i.equipo_id = $id ORDER BY t.id DESC");
    $torneos_inscritos = $stmtTInscrito->fetchAll(PDO::FETCH_ASSOC);

    foreach($torneos_inscritos as $torneo) {
        $tid = $torneo['id'];
        
        $stmtM = $conn->query("SELECT p.fase, p.goles_local, p.goles_visitante, p.equipo_local_id, p.equipo_visitante_id, p.estado 
                               FROM partidos p 
                               WHERE p.torneo_id = $tid AND (p.equipo_local_id = $id OR p.equipo_visitante_id = $id) AND p.estado IN ('finalizado', 'walkover')");
        $matches = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        
        $fase_maxima = 'Fase de Grupos';
        $es_campeon = false;
        
        $fases_jugadas = array_column($matches, 'fase');
        
        if (in_array('Final', $fases_jugadas) || in_array('Fase Final', $fases_jugadas)) {
            $fase_maxima = 'Subcampeón';
            foreach($matches as $m) {
                if ($m['fase'] === 'Final' || $m['fase'] === 'Fase Final') {
                    $gl = intval($m['goles_local']); $gv = intval($m['goles_visitante']);
                    if ($m['equipo_local_id'] == $id && $gl > $gv) $es_campeon = true;
                    if ($m['equipo_visitante_id'] == $id && $gv > $gl) $es_campeon = true;
                }
            }
            if ($es_campeon) $fase_maxima = 'Campeón';
        } else if (in_array('Semifinal', $fases_jugadas)) {
            $fase_maxima = 'Semifinal';
        } else if (in_array('Cuartos de Final', $fases_jugadas)) {
            $fase_maxima = 'Cuartos de Final';
        } else if (in_array('Octavos de Final', $fases_jugadas)) {
            $fase_maxima = 'Octavos de Final';
        }
        
        $historial_torneos[] = [
            'torneo' => $torneo['nombre'],
            'fase_alcanzada' => $fase_maxima,
            'es_campeon' => $es_campeon
        ];
    }

    echo json_encode([
        'success' => true,
        'equipo' => $equipo,
        'stats' => $stats,
        'jugadores' => $jugadores,
        'partidos' => $partidos,
        'palmares' => $palmares,
        'historial_torneos' => $historial_torneos
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
