<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'listar') {
    $torneo_id = $_GET['torneo_id'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    $equipo_id = $_GET['equipo_id'] ?? '';
    
    $query = "SELECT p.*, 
                     l.nombre as local_nombre, l.logo as local_logo,
                     v.nombre as visitante_nombre, v.logo as visitante_logo,
                     IF(p.fase IN ('Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'), 'Fase Eliminatoria', COALESCE(g.nombre_grupo, 'General')) as nombre_grupo,
                     (SELECT COUNT(*) FROM impugnaciones i WHERE i.partido_id = p.id) as tiene_impugnacion,
                     (SELECT i2.estado FROM impugnaciones i2 WHERE i2.partido_id = p.id LIMIT 1) as estado_impugnacion
              FROM partidos p
              INNER JOIN equipos l ON p.equipo_local_id = l.id
              INNER JOIN equipos v ON p.equipo_visitante_id = v.id
              LEFT JOIN grupos g ON p.torneo_id = g.torneo_id 
                   AND EXISTS (SELECT 1 FROM grupo_equipos ge WHERE ge.grupo_id = g.id AND ge.equipo_id = p.equipo_local_id)
              WHERE p.estado IN ('programado', 'en_juego', 'finalizado', 'walkover')";
              
    $params = [];
    if (!empty($torneo_id)) {
        $query .= " AND p.torneo_id = :torneo";
        $params[':torneo'] = $torneo_id;
    }
    if (!empty($fecha)) {
        $query .= " AND p.fecha = :fecha";
        $params[':fecha'] = $fecha;
    }
    if (!empty($equipo_id)) {
        $query .= " AND (p.equipo_local_id = :eq1 OR p.equipo_visitante_id = :eq2)";
        $params[':eq1'] = $equipo_id;
        $params[':eq2'] = $equipo_id;
    }
    
    $query .= " ORDER BY p.fecha DESC, p.hora DESC, nombre_grupo ASC";
    
    $stmt = $conn->prepare($query);
    foreach($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($data as &$row) {
        $row['fecha_formateada'] = date('d/m/Y', strtotime($row['fecha']));
        $row['hora_formateada'] = date('H:i', strtotime($row['hora']));

        // Si es partido de vuelta en fase eliminatoria, buscar IDA
        if ($row['llave'] !== null && in_array($row['fase'], ['Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'])) {
            if ($row['es_ida'] == 0) {
                $stmtI = $conn->prepare("SELECT goles_local, goles_visitante, equipo_local_id FROM partidos WHERE llave = :llave AND es_ida = 1 AND torneo_id = :tid LIMIT 1");
                $stmtI->execute([':llave' => $row['llave'], ':tid' => $row['torneo_id']]);
                $ida = $stmtI->fetch(PDO::FETCH_ASSOC);
                
                if ($ida) {
                    // El local de la vuelta es el visitante de la ida
                    $g_ida_este = ($row['equipo_local_id'] == $ida['equipo_local_id']) ? $ida['goles_local'] : $ida['goles_visitante'];
                    $g_ida_riva = ($row['equipo_local_id'] == $ida['equipo_local_id']) ? $ida['goles_visitante'] : $ida['goles_local'];
                    
                    $row['ida_info'] = [
                        'goles_este' => $g_ida_este,
                        'goles_riva' => $g_ida_riva
                    ];
                    $row['global_este'] = intval($row['goles_local']) + $g_ida_este;
                    $row['global_riva'] = intval($row['goles_visitante']) + $g_ida_riva;
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'listar_filtros') {
    $torneo_id = $_GET['torneo_id'] ?? '';
    
    // Obtener equipos del torneo
    $stmtE = $conn->prepare("SELECT e.id, e.nombre FROM equipos e INNER JOIN inscripciones i ON e.id = i.equipo_id WHERE i.torneo_id = :tid ORDER BY e.nombre ASC");
    $stmtE->execute([':tid' => $torneo_id]);
    $equipos = $stmtE->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'equipos' => $equipos]);
    exit;
}

if ($action === 'detalle_partido') {
    $id = $_GET['id'] ?? '';
    
    // Detalles básicos y marcador
    $stmt = $conn->prepare("SELECT p.*, 
                                   l.nombre as local_nombre, l.logo as local_logo,
                                   v.nombre as visitante_nombre, v.logo as visitante_logo,
                                   t.nombre as torneo_nombre
                            FROM partidos p
                            INNER JOIN equipos l ON p.equipo_local_id = l.id
                            INNER JOIN equipos v ON p.equipo_visitante_id = v.id
                            INNER JOIN torneos t ON p.torneo_id = t.id
                            WHERE p.id = :id");
    $stmt->execute([':id' => $id]);
    $partido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partido) {
        echo json_encode(['success' => false, 'message' => 'Partido no encontrado']);
        exit;
    }

    // Datos de Ida y Global
    $partido['ida_info'] = null;
    if ($partido['llave'] !== null && in_array($partido['fase'], ['Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'])) {
        if ($partido['es_ida'] == 0) {
            $stmtI = $conn->prepare("SELECT goles_local, goles_visitante, equipo_local_id FROM partidos WHERE llave = :llave AND es_ida = 1 AND torneo_id = :tid LIMIT 1");
            $stmtI->execute([':llave' => $partido['llave'], ':tid' => $partido['torneo_id']]);
            $ida = $stmtI->fetch(PDO::FETCH_ASSOC);
            
            if ($ida) {
                // El local de la vuelta es el visitante de la ida
                $g_ida_este = ($partido['equipo_local_id'] == $ida['equipo_local_id']) ? $ida['goles_local'] : $ida['goles_visitante'];
                $g_ida_riva = ($partido['equipo_local_id'] == $ida['equipo_local_id']) ? $ida['goles_visitante'] : $ida['goles_local'];
                
                $partido['ida_info'] = [
                    'goles_este' => $g_ida_este,
                    'goles_riva' => $g_ida_riva,
                    'global_este' => intval($partido['goles_local']) + $g_ida_este,
                    'global_riva' => intval($partido['goles_visitante']) + $g_ida_riva
                ];
            }
        }
    }

    // Eventos (Goles y Tarjetas)
    $stmtE = $conn->prepare("SELECT ep.*, j.nombre as jugador_nombre 
                             FROM eventos_partido ep 
                             INNER JOIN jugadores j ON ep.jugador_id = j.id 
                             WHERE ep.partido_id = :pid 
                             ORDER BY ep.minuto ASC");
    $stmtE->execute([':pid' => $id]);
    $eventos = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    // Datos de Impugnación si existe
    $stmtI = $conn->prepare("SELECT i.*, e.nombre as equipo_denunciante, j.nombre as jugador_castigo_nombre 
                             FROM impugnaciones i 
                             INNER JOIN equipos e ON i.equipo_que_impugna_id = e.id
                             LEFT JOIN jugadores j ON i.jugador_castigo_id = j.id
                             WHERE i.partido_id = :pid");
    $stmtI->execute([':pid' => $id]);
    $impugnacion = $stmtI->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'partido' => $partido, 
        'eventos' => $eventos,
        'impugnacion' => $impugnacion ?: null
    ]);
    exit;
}
?>
