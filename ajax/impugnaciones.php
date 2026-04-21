<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Asegurar que la tabla exista (Auto-migración)
$conn->exec("CREATE TABLE IF NOT EXISTS impugnaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_que_impugna_id INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
    resolucion TEXT,
    tipo_castigo ENUM('puntos', 'economico', 'ambos', 'ninguno') DEFAULT 'ninguno',
    puntos_castigo INT DEFAULT 0,
    monto_castigo DECIMAL(10,2) DEFAULT 0.00,
    monto_rechazo DECIMAL(10,2) DEFAULT 0.00,
    pago_rechazo_estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    jugador_castigo_id INT NULL,
    jugador_castigo_detalle TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (equipo_que_impugna_id) REFERENCES equipos(id),
    FOREIGN KEY (jugador_castigo_id) REFERENCES jugadores(id)
)");

// Asegurar columnas para instalaciones previas
try {
    $conn->exec("ALTER TABLE impugnaciones ADD COLUMN IF NOT EXISTS jugador_castigo_id INT NULL AFTER monto_rechazo");
    $conn->exec("ALTER TABLE impugnaciones ADD COLUMN IF NOT EXISTS jugador_castigo_detalle TEXT NULL AFTER jugador_castigo_id");
} catch(Exception $e) {}

$conn->exec("CREATE TABLE IF NOT EXISTS cobros_partido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
)");

if ($action === 'listar') {
    $estado = $_GET['estado'] ?? '';
    
    $query = "SELECT i.*, 
                     l.nombre as local_nombre, v.nombre as visitante_nombre, 
                     e.nombre as equipo_denunciante
              FROM impugnaciones i
              INNER JOIN partidos p ON i.partido_id = p.id
              INNER JOIN equipos l ON p.equipo_local_id = l.id
              INNER JOIN equipos v ON p.equipo_visitante_id = v.id
              INNER JOIN equipos e ON i.equipo_que_impugna_id = e.id
              WHERE 1=1";
    
    if (!empty($estado)) {
        $query .= " AND i.estado = :estado";
    }
    
    $query .= " ORDER BY i.id DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($estado)) $stmt->bindParam(":estado", $estado);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'obtener') {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("SELECT i.*, l.nombre as local_nombre, v.nombre as visitante_nombre, e.nombre as equipo_denunciante,
                            p.equipo_local_id, p.equipo_visitante_id, p.observacion as partido_observacion,
                            j.nombre as jugador_castigo_nombre, j.dorsal as jugador_castigo_dorsal
                            FROM impugnaciones i
                            INNER JOIN partidos p ON i.partido_id = p.id
                            INNER JOIN equipos l ON p.equipo_local_id = l.id
                            INNER JOIN equipos v ON p.equipo_visitante_id = v.id
                            INNER JOIN equipos e ON i.equipo_que_impugna_id = e.id
                            LEFT JOIN jugadores j ON i.jugador_castigo_id = j.id
                            WHERE i.id = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'listar_jugadores_partido') {
    $partido_id = $_POST['partido_id'] ?? 0;
    
    // Obtener los IDs de los equipos del partido
    $stmtP = $conn->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = :pid");
    $stmtP->execute([':pid' => $partido_id]);
    $p = $stmtP->fetch(PDO::FETCH_ASSOC);
    
    if (!$p) {
        echo json_encode(['success' => false, 'message' => 'Partido no encontrado']);
        exit;
    }

    // Determinar cuál es el equipo oponente (el impugnado)
    $equipo_que_impugna = $_POST['equipo_que_impugna_id'] ?? 0;
    $equipo_oponente = ($equipo_que_impugna == $p['equipo_local_id']) ? $p['equipo_visitante_id'] : $p['equipo_local_id'];

    $stmtJ = $conn->prepare("SELECT j.id, j.nombre, j.dorsal, e.nombre as equipo_nombre 
                             FROM jugadores j 
                             INNER JOIN equipos e ON j.equipo_id = e.id
                             WHERE j.equipo_id = :opp 
                             ORDER BY j.nombre ASC");
    $stmtJ->execute([':opp' => $equipo_oponente]);
    $jugadores = $stmtJ->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $jugadores]);
    exit;
}

if ($action === 'resolver') {
    $id = $_POST['id'] ?? 0;
    $estado = $_POST['estado'] ?? '';
    $resolucion = $_POST['resolucion'] ?? '';
    $puntos = intval($_POST['puntos_castigo'] ?? 0);
    $monto_c = floatval($_POST['monto_castigo'] ?? 0);
    $monto_r = floatval($_POST['monto_rechazo'] ?? 0);
    $jugador_id = $_POST['jugador_castigo_id'] ?? null;
    $jugador_detalle = $_POST['jugador_castigo_detalle'] ?? null;

    $query = "UPDATE impugnaciones SET 
                estado = :est, 
                resolucion = :res, 
                puntos_castigo = :pts, 
                monto_castigo = :mc, 
                monto_rechazo = :mr, 
                tipo_castigo = :tipo,
                jugador_castigo_id = :jid,
                jugador_castigo_detalle = :jdet
              WHERE id = :id";
    
    $tipo = 'ninguno';
    if ($estado === 'aceptada') {
        if ($puntos > 0 && $monto_c > 0) $tipo = 'ambos';
        else if ($puntos > 0) $tipo = 'puntos';
        else if ($monto_c > 0) $tipo = 'economico';
    }

    $stmt = $conn->prepare($query);
    $res = $stmt->execute([
        ':est' => $estado,
        ':res' => $resolucion,
        ':pts' => $puntos,
        ':mc' => $monto_c,
        ':mr' => ($estado === 'rechazada' ? $monto_r : 0),
        ':tipo' => $tipo,
        ':jid' => $jugador_id,
        ':jdet' => $jugador_detalle,
        ':id' => $id
    ]);

    if ($res) {
        echo json_encode(['success' => true, 'message' => 'Impugnación resuelta.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar resolución.']);
    }
    exit;
}

if ($action === 'marcar_pagado') {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("UPDATE impugnaciones SET pago_rechazo_estado = 'pagado' WHERE id = :id");
    if($stmt->execute([':id'=>$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
