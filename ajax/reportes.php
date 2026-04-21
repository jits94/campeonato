<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$torneo_id = $_GET['torneo_id'] ?? 0;

$where_torneo = ($torneo_id > 0) ? " AND p.torneo_id = $torneo_id" : "";

if ($action === 'goleadores') {
    $query = "SELECT j.id as jugador_id, j.nombre as jugador, j.foto as jugador_foto, e.nombre as equipo, e.logo as equipo_logo, COUNT(*) as goles
              FROM eventos_partido ep
              JOIN jugadores j ON ep.jugador_id = j.id
              JOIN equipos e ON ep.equipo_id = e.id
              JOIN partidos p ON ep.partido_id = p.id
              WHERE ep.tipo = 'gol' $where_torneo
              GROUP BY j.id, e.id, e.logo
              ORDER BY goles DESC
              LIMIT 15";
    
    try {
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'tarjetas') {
    // Calculamos un índice de indisciplina: Roja = 3 pts, Amarilla = 1 pt
    $query = "SELECT j.id as jugador_id, j.nombre as jugador, j.foto as jugador_foto, e.nombre as equipo, e.logo as equipo_logo,
                     SUM(IF(ep.tipo = 'amarilla', 1, 0)) as amarillas,
                     SUM(IF(ep.tipo = 'roja', 1, 0)) as rojas,
                     (SUM(IF(ep.tipo = 'amarilla', 1, 0)) + SUM(IF(ep.tipo = 'roja', 1, 0)) * 3) as indice
              FROM eventos_partido ep
              JOIN jugadores j ON ep.jugador_id = j.id
              JOIN equipos e ON ep.equipo_id = e.id
              JOIN partidos p ON ep.partido_id = p.id
              WHERE ep.tipo IN ('amarilla', 'roja') $where_torneo
              GROUP BY j.id, e.id, e.logo
              ORDER BY indice DESC
              LIMIT 15";
              
    try {
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'detalles_tarjetas') {
    $jugador_id = (int)($_GET['jugador_id'] ?? 0);
    $query = "SELECT ep.tipo, ep.minuto, p.fecha, t.nombre as torneo,
                     el.nombre as local_nombre, ev.nombre as visitante_nombre
              FROM eventos_partido ep
              JOIN partidos p ON ep.partido_id = p.id
              JOIN torneos t ON p.torneo_id = t.id
              JOIN equipos el ON p.equipo_local_id = el.id
              JOIN equipos ev ON p.equipo_visitante_id = ev.id
              WHERE ep.jugador_id = $jugador_id AND ep.tipo IN ('amarilla', 'roja') $where_torneo
              ORDER BY p.fecha DESC";
              
    try {
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'fair_play') {
    // Equipos con menos tarjetas (puntos de castigo)
    $query = "SELECT e.nombre as equipo, e.logo as equipo_logo,
                     SUM(IF(ep.tipo = 'amarilla', 1, 0)) as amarillas,
                     SUM(IF(ep.tipo = 'roja', 1, 0)) as rojas,
                     (SUM(IF(ep.tipo = 'amarilla', 1, 0)) + SUM(IF(ep.tipo = 'roja', 1, 0)) * 3) as puntos_castigo
              FROM equipos e
              LEFT JOIN eventos_partido ep ON e.id = ep.equipo_id AND ep.tipo IN ('amarilla', 'roja')
              LEFT JOIN partidos p ON ep.partido_id = p.id $where_torneo
              GROUP BY e.id, e.logo
              ORDER BY puntos_castigo ASC";
              
    try {
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'goles_equipos') {
    $query = "SELECT e.id, e.nombre as equipo, e.logo as equipo_logo, COUNT(*) as goles
              FROM eventos_partido ep
              JOIN equipos e ON ep.equipo_id = e.id
              JOIN partidos p ON ep.partido_id = p.id
              WHERE ep.tipo = 'gol' $where_torneo
              GROUP BY e.id, e.logo
              ORDER BY goles DESC";
    try {
        $stmt = $conn->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'campeones') {
    // Obtener torneos finalizados
    $stmtT = $conn->query("SELECT id, nombre FROM torneos WHERE estado = 'finalizado' ORDER BY id DESC");
    $torneos = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    $campeones = [];

    foreach ($torneos as $torneo) {
        $tid = $torneo['id'];

        // Calcular puntos por equipo en ese torneo
        $sql = "SELECT 
                    e.id, e.nombre as equipo, e.logo,
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
                LIMIT 1";

        $stmtC = $conn->query($sql);
        $campeon = $stmtC->fetch(PDO::FETCH_ASSOC);

        if ($campeon) {
            $campeones[] = [
                'torneo'      => $torneo['nombre'],
                'equipo'      => $campeon['equipo'],
                'logo'        => $campeon['logo'],
                'puntos'      => $campeon['puntos'],
                'diferencia'  => $campeon['gf'] - $campeon['gc'],
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $campeones]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
