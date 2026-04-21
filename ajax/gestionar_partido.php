<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action === 'obtener_datos_partido') {
    $id = $_POST['partido_id'] ?? 0;

    // Asegurar que las tablas necesarias existan
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
    
    // Traer score actual y metadata
    $stmt = $conn->prepare("SELECT goles_local, goles_visitante, estado FROM partidos WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $partido = $stmt->fetch(PDO::FETCH_ASSOC);

    // Traer datos de impugnación si existen
    $stmtImp = $conn->prepare("SELECT i.*, e.nombre as equipo_denunciante, j.nombre as jugador_sancionado_nombre 
                               FROM impugnaciones i 
                               INNER JOIN equipos e ON i.equipo_que_impugna_id = e.id
                               LEFT JOIN jugadores j ON i.jugador_castigo_id = j.id
                               WHERE i.partido_id = :id");
    $stmtImp->bindParam(":id", $id);
    $stmtImp->execute();
    $impugnacion = $stmtImp->fetch(PDO::FETCH_ASSOC);
    
    // Traer todos los eventos ordenados por minuto
    $stmtEv = $conn->prepare("SELECT ev.*, j.nombre as jugador_nombre, e.nombre as equipo_nombre
                              FROM eventos_partido ev
                              INNER JOIN jugadores j ON ev.jugador_id = j.id
                              INNER JOIN equipos e ON ev.equipo_id = e.id
                              WHERE ev.partido_id = :id
                              ORDER BY ev.minuto ASC, ev.id ASC");
    $stmtEv->bindParam(":id", $id);
    $stmtEv->execute();
    $eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'partido' => $partido, 'eventos' => $eventos, 'impugnacion' => $impugnacion]);
    exit;
}

// SOLO ADMIN A PARTIR DE AQUI
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'registrar_evento') {
    $partido_id = $_POST['partido_id'] ?? '';
    $equipo_id = $_POST['equipo_id'] ?? '';
    $jugador_id = $_POST['jugador_id'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $minuto = intval($_POST['minuto'] ?? 0);
    
    if (empty($partido_id) || empty($equipo_id) || empty($jugador_id) || empty($tipo) || $minuto <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $conn->beginTransaction();
    try {
        $stmtEv = $conn->prepare("INSERT INTO eventos_partido (partido_id, equipo_id, jugador_id, tipo, minuto) VALUES (:pid, :eid, :jid, :tipo, :minuto)");
        $stmtEv->execute([':pid'=>$partido_id, ':eid'=>$equipo_id, ':jid'=>$jugador_id, ':tipo'=>$tipo, ':minuto'=>$minuto]);
        $evento_id = $conn->lastInsertId();
        
        // Si es gol, sumar al partido
        if ($tipo === 'gol') {
            // Verificar si es local o visitante
            $stmtEqs = $conn->prepare("SELECT equipo_local_id FROM partidos WHERE id = :id");
            $stmtEqs->execute([':id'=>$partido_id]);
            $local = $stmtEqs->fetchColumn();
            
            if ($local == $equipo_id) {
                $conn->query("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = $partido_id");
            } else {
                $conn->query("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = $partido_id");
            }
        }
        
        // Si es tarjeta, crear sancion
        if ($tipo === 'amarilla' || $tipo === 'roja') {
            $monto = ($tipo === 'amarilla') ? 5.00 : 10.00;
            $stmtSancion = $conn->prepare("INSERT INTO sanciones (evento_id, monto, estado) VALUES (:ev_id, :monto, 'pendiente')");
            $stmtSancion->execute([':ev_id'=>$evento_id, ':monto'=>$monto]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error de BD']);
    }
    exit;
}

if ($action === 'eliminar_evento') {
    $id_evento = $_POST['id_evento'] ?? '';
    $partido_id = $_POST['partido_id'] ?? '';
    
    // Obtener info del evento para reversar gol
    $stmtInfo = $conn->prepare("SELECT tipo, equipo_id FROM eventos_partido WHERE id = :id");
    $stmtInfo->execute([':id'=>$id_evento]);
    $ev = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    if(!$ev) { echo json_encode(['success'=>false, 'message'=>'Evento no existe']); exit; }

    $conn->beginTransaction();
    try {
        if($ev['tipo'] === 'gol') {
            $stmtEqs = $conn->prepare("SELECT equipo_local_id FROM partidos WHERE id = :id");
            $stmtEqs->execute([':id'=>$partido_id]);
            $local = $stmtEqs->fetchColumn();
            if ($local == $ev['equipo_id']) {
                $conn->query("UPDATE partidos SET goles_local = goles_local - 1 WHERE id = $partido_id");
            } else {
                $conn->query("UPDATE partidos SET goles_visitante = goles_visitante - 1 WHERE id = $partido_id");
            }
        }
        
        // El CASCADE eliminará la sancion si es tarjeta
        $stmtD = $conn->prepare("DELETE FROM eventos_partido WHERE id = :id");
        $stmtD->execute([':id'=>$id_evento]);
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al borrar evento']);
    }
    exit;
}

if ($action === 'finalizar_partido') {
    $id = $_POST['id_partido'] ?? 0;
    $obs = $_POST['observacion'] ?? '';
    $costo = floatval($_POST['costo'] ?? 50.0);
    $impugnado = intval($_POST['impugnado'] ?? 0);
    $pen_l = (isset($_POST['penales_local']) && $_POST['penales_local'] !== '') ? intval($_POST['penales_local']) : null;
    $pen_v = (isset($_POST['penales_visitante']) && $_POST['penales_visitante'] !== '') ? intval($_POST['penales_visitante']) : null;

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("UPDATE partidos SET estado = 'finalizado', observacion = :obs, penales_local = :pl, penales_visitante = :pv WHERE id = :id AND estado = 'en_juego'");
        $stmt->execute([':obs'=>$obs, ':pl'=>$pen_l, ':pv'=>$pen_v, ':id'=>$id]);

        if($stmt->rowCount() > 0) {
            // Generar cobro por partido jugado
            $stmtP = $conn->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = :id");
            $stmtP->execute([':id'=>$id]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);

            if($costo > 0) {
                // Cobro Local
                $stmtCobro = $conn->prepare("INSERT INTO cobros_partido (partido_id, equipo_id, monto, estado) VALUES (:pid, :eid, :monto, 'pendiente')");
                $stmtCobro->execute([':pid'=>$id, ':eid'=>$p['equipo_local_id'], ':monto'=>$costo]);
                
                // Cobro Visitante
                $stmtCobro->execute([':pid'=>$id, ':eid'=>$p['equipo_visitante_id'], ':monto'=>$costo]);
            }

            // Registrar Impugnación si aplica
            if($impugnado === 1) {
                $imp_equipo_id = $_POST['imp_equipo_id'] ?? 0;
                $imp_motivo = $_POST['imp_motivo'] ?? '';
                
                if($imp_equipo_id && !empty($imp_motivo)) {
                    $stmtImp = $conn->prepare("INSERT INTO impugnaciones (partido_id, equipo_que_impugna_id, motivo, estado) VALUES (:pid, :eid, :motivo, 'pendiente')");
                    $stmtImp->execute([':pid'=>$id, ':eid'=>$imp_equipo_id, ':motivo'=>$imp_motivo]);
                }
            }
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Partido cerrado.' . ($impugnado ? ' Impugnación registrada.' : '')]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al finalizar: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'walkover') {
    $id = $_POST['id_partido'] ?? 0;
    $ganador_id = $_POST['equipo_ganador_id'] ?? 0;
    $obs = $_POST['observacion'] ?? 'W/O';

    $stmtP = $conn->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = :id");
    $stmtP->execute([':id'=>$id]);
    $p = $stmtP->fetch(PDO::FETCH_ASSOC);

    $goles_loc = ($ganador_id == $p['equipo_local_id']) ? 3 : 0;
    $goles_vis = ($ganador_id == $p['equipo_visitante_id']) ? 3 : 0;

    $stmt = $conn->prepare("UPDATE partidos SET estado = 'walkover', goles_local = :gl, goles_visitante = :gv, observacion = :obs WHERE id = :id");
    if($stmt->execute([':gl'=>$goles_loc, ':gv'=>$goles_vis, ':obs'=>$obs, ':id'=>$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error BD']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción inactiva']);
