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

if ($action === 'dashboard_stats') {
    $t_id = $_GET['torneo_id'] ?? 0;
    
    $where_t = ($t_id > 0) ? " AND torneo_id = $t_id" : "";
    $where_insc = ($t_id > 0) ? " AND i.torneo_id = $t_id" : "";
    
    // Ingresos: Inscripciones Pagadas
    $stmt1 = $conn->query("SELECT SUM(monto_cobrado) FROM inscripciones i WHERE i.estado = 'registrado' $where_insc");
    $i_insc = floatval($stmt1->fetchColumn());
    
    // Ingresos: Sanciones Pagadas
    $w_sanc = ($t_id > 0) ? " AND p.torneo_id = $t_id" : "";
    $stmt2 = $conn->query("SELECT SUM(s.monto) FROM sanciones s 
                           INNER JOIN eventos_partido ev ON s.evento_id = ev.id 
                           INNER JOIN partidos p ON ev.partido_id = p.id 
                           WHERE s.estado = 'pagado' $w_sanc");
    $i_sanc = floatval($stmt2->fetchColumn());
    
    // Ingresos: Cobros Partido Pagados
    $stmt3 = $conn->query("SELECT SUM(c.monto) FROM cobros_partido c
                           INNER JOIN partidos p ON c.partido_id = p.id
                           WHERE c.estado = 'pagado' $w_sanc");
    $i_cobro = floatval($stmt3->fetchColumn());
    
    // Ingresos: Transferencias (con torneo_id)
    // Para simplificar, si t_id = 0 suma todas, si t_id>0 suma las de ese torneo
    $stmt4 = $conn->query("SELECT SUM(monto) FROM transferencias WHERE 1=1 $where_t");
    $i_trans = floatval($stmt4->fetchColumn());
    
    // Ingresos: Impugnaciones
    $stmt5 = $conn->query("SELECT SUM(i.monto_rechazo) FROM impugnaciones i 
                           INNER JOIN partidos p ON i.partido_id = p.id 
                           WHERE i.estado = 'rechazada' AND i.pago_rechazo_estado = 'pagado' $w_sanc");
    $i_impug = floatval($stmt5->fetchColumn());
    
    $in_total = $i_insc + $i_sanc + $i_cobro + $i_trans + $i_impug;
    
    // Gastos Totales
    $stmtG = $conn->query("SELECT SUM(monto) FROM gastos WHERE 1=1 $where_t");
    $g_total = floatval($stmtG->fetchColumn());
    
    $balance = $in_total - $g_total;
    
    echo json_encode([
        'success' => true,
        'in_total' => $in_total,
        'inReq' => [
            'insc' => $i_insc,
            'sanc' => $i_sanc,
            'cobr' => $i_cobro,
            'transf' => $i_trans,
            'impug' => $i_impug
        ],
        'g_total' => $g_total,
        'balance' => $balance
    ]);
    exit;
}

if ($action === 'listar_gastos') {
    $t_id = $_GET['torneo_id'] ?? 0;
    $where = ($t_id > 0) ? "WHERE torneo_id = $t_id" : "";
    
    $stmt = $conn->query("SELECT * FROM gastos $where ORDER BY fecha DESC, id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($data as &$row) {
        $row['fecha'] = date('d/m/Y', strtotime($row['fecha']));
    }
    
    echo json_encode(['data' => $data]);
    exit;
}

if ($action === 'crear_gasto') {
    $t_id = $_POST['torneo_id'] ?? 0;
    $fecha = $_POST['fecha'] ?? '';
    $monto = $_POST['monto'] ?? 0;
    $cat = $_POST['categoria'] ?? '';
    $desc = $_POST['descripcion'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO gastos (torneo_id, categoria, descripcion, monto, fecha) VALUES (:tid, :cat, :desc, :monto, :fecha)");
    $nid = ($t_id == 0) ? null : $t_id;
    
    if($stmt->execute([':tid'=>$nid, ':cat'=>$cat, ':desc'=>$desc, ':monto'=>$monto, ':fecha'=>$fecha])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'eliminar_gasto') {
    $id = $_POST['id_gasto'] ?? 0;
    $conn->query("DELETE FROM gastos WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'listar_pendientes') {
    $t_id = $_GET['torneo_id'] ?? 0;
    $w_sanc = ($t_id > 0) ? " AND p.torneo_id = $t_id" : "";
    
    // Tarjetas
    $stmtT = $conn->query("SELECT s.id as id_sancion, s.monto, p.id as partido_id, p.fecha, ev.tipo, j.nombre as jugador_nombre, e.nombre as equipo_nombre
                           FROM sanciones s 
                           INNER JOIN eventos_partido ev ON s.evento_id = ev.id
                           INNER JOIN partidos p ON ev.partido_id = p.id
                           INNER JOIN jugadores j ON ev.jugador_id = j.id
                           INNER JOIN equipos e ON ev.equipo_id = e.id
                           WHERE s.estado = 'pendiente' $w_sanc ORDER BY p.fecha ASC");
    $tarjetas = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    foreach($tarjetas as &$v) $v['fecha'] = date('d/m', strtotime($v['fecha']));
    
    // Cobros
    $stmtC = $conn->query("SELECT c.id as id_cobro, c.monto, p.id as partido_id, p.fase, e.nombre as equipo_nombre
                           FROM cobros_partido c
                           INNER JOIN partidos p ON c.partido_id = p.id
                           INNER JOIN equipos e ON c.equipo_id = e.id
                           WHERE c.estado = 'pendiente' $w_sanc ORDER BY c.id ASC");
    $partidos = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'tarjetas' => $tarjetas, 'partidos' => $partidos]);
    exit;
}

if ($action === 'cobrar_sancion') {
    $id = $_POST['id_sancion'] ?? 0;
    $conn->query("UPDATE sanciones SET estado = 'pagado' WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'cobrar_partido_fee') {
    $id = $_POST['id_cobro'] ?? 0;
    $conn->query("UPDATE cobros_partido SET estado = 'pagado' WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'historial_torneos') {
    $stmt = $conn->query("SELECT id, nombre, estado FROM torneos ORDER BY id DESC");
    $torneos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = [];
    foreach($torneos as $t) {
        $tid = $t['id'];
        // in
        $i_insc = $conn->query("SELECT COALESCE(SUM(monto_cobrado),0) FROM inscripciones WHERE estado = 'registrado' AND torneo_id = $tid")->fetchColumn();
        $i_sanc = $conn->query("SELECT COALESCE(SUM(s.monto),0) FROM sanciones s JOIN eventos_partido ev ON s.evento_id = ev.id JOIN partidos p ON ev.partido_id = p.id WHERE s.estado = 'pagado' AND p.torneo_id = $tid")->fetchColumn();
        $i_cobro = $conn->query("SELECT COALESCE(SUM(c.monto),0) FROM cobros_partido c JOIN partidos p ON c.partido_id = p.id WHERE c.estado = 'pagado' AND p.torneo_id = $tid")->fetchColumn();
        $i_trans = $conn->query("SELECT COALESCE(SUM(monto),0) FROM transferencias WHERE torneo_id = $tid")->fetchColumn();
        $i_impug = $conn->query("SELECT COALESCE(SUM(i.monto_rechazo),0) FROM impugnaciones i JOIN partidos p ON i.partido_id = p.id WHERE i.estado = 'rechazada' AND i.pago_rechazo_estado = 'pagado' AND p.torneo_id = $tid")->fetchColumn();
        $in = $i_insc + $i_sanc + $i_cobro + $i_trans + $i_impug;
        
        $out = $conn->query("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE torneo_id = $tid")->fetchColumn();
        
        $data[] = [
            'torneo' => $t['nombre'],
            'estado' => $t['estado'],
            'in' => $in,
            'out' => $out,
            'bal' => $in - $out
        ];
    }
    echo json_encode(['data' => $data]);
    exit;
}
