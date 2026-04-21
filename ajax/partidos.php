<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if (in_array($action, ['crear', 'eliminar', 'cambiar_estado', 'guardar_partidos_masivo']) && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

if ($action === 'listar') {
    $torneo_id = $_GET['torneo_id'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    
    $query = "SELECT p.*, 
                     l.nombre as local_nombre, l.logo as local_logo,
                     v.nombre as visitante_nombre, v.logo as visitante_logo,
                     IF(p.fase IN ('Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'), 'Fase Eliminatoria', COALESCE(g.nombre_grupo, 'General')) as nombre_grupo
              FROM partidos p
              INNER JOIN equipos l ON p.equipo_local_id = l.id
              INNER JOIN equipos v ON p.equipo_visitante_id = v.id
              LEFT JOIN grupos g ON p.torneo_id = g.torneo_id 
                   AND EXISTS (SELECT 1 FROM grupo_equipos ge WHERE ge.grupo_id = g.id AND ge.equipo_id = p.equipo_local_id)
              WHERE 1=1";
              
    $params = [];
    if (!empty($torneo_id)) {
        $query .= " AND p.torneo_id = :torneo";
        $params[':torneo'] = $torneo_id;
    }
    if (!empty($fecha)) {
        $query .= " AND p.fecha = :fecha";
        $params[':fecha'] = $fecha;
    }
    
    $query .= " ORDER BY p.fecha DESC, p.hora DESC, nombre_grupo ASC";
    
    $stmt = $conn->prepare($query);
    foreach($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEliminatoria = false;
    $incompleteTies = false; // Flag para rastrear si hay llaves con partidos pendientes
    $tiesCount = []; // Para contar partidos por llave

    foreach($data as &$row) {
        $row['fecha_formateada'] = date('d/m/Y', strtotime($row['fecha']));
        $row['hora_formateada'] = date('H:i', strtotime($row['hora'])); 
        
        if ($row['llave'] !== null && in_array($row['fase'], ['Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'])) {
            $hasEliminatoria = true;
            
            // Rastrear estado de la llave
            if (!isset($tiesCount[$row['llave']])) {
                $tiesCount[$row['llave']] = ['total' => 0, 'finalizados' => 0];
            }
            $tiesCount[$row['llave']]['total']++;
            if (in_array($row['estado'], ['finalizado', 'walkover'])) {
                $tiesCount[$row['llave']]['finalizados']++;
            }

            // Si es vuelta (es_ida = 0), buscar los goles de la ida
            if ($row['es_ida'] == 0) {
                $stmtI = $conn->prepare("SELECT goles_local, goles_visitante, equipo_local_id FROM partidos WHERE llave = :llave AND es_ida = 1 AND torneo_id = :tid LIMIT 1");
                $stmtI->execute([':llave' => $row['llave'], ':tid' => $row['torneo_id']]);
                $ida = $stmtI->fetch(PDO::FETCH_ASSOC);
                
                if ($ida) {
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

    // Verificar si hay llaves incompletas
    foreach ($tiesCount as $llave => $counts) {
        // En octavos y cuartos suelen ser 2 partidos. En semis/final puede ser 1 o 2.
        // Lo importante es que todos los que existan estén finalizados.
        if ($counts['total'] > $counts['finalizados']) {
            $incompleteTies = true;
            break;
        }
    }
    
    echo json_encode(['data' => $data, 'hasEliminatoria' => $hasEliminatoria, 'incompleteTies' => $incompleteTies]);
    exit;
}

if ($action === 'listar_equipos_torneo') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    
    // Obtener tipo de torneo
    $stmtT = $conn->prepare("SELECT tipo FROM torneos WHERE id = :id");
    $stmtT->execute([':id' => $torneo_id]);
    $torneo = $stmtT->fetch(PDO::FETCH_ASSOC);
    
    if ($torneo && $torneo['tipo'] === 'fase_grupos') {
        // Obtener equipos con su respectivo grupo
        $query = "SELECT e.id, e.nombre, g.nombre_grupo, g.id as grupo_id 
                  FROM grupo_equipos ge 
                  INNER JOIN equipos e ON ge.equipo_id = e.id 
                  INNER JOIN grupos g ON ge.grupo_id = g.id 
                  WHERE g.torneo_id = :torneo 
                  ORDER BY g.nombre_grupo ASC, e.nombre ASC";
    } else {
        // Torneo de liga (todos contra todos)
        $query = "SELECT e.id, e.nombre, 'General' as nombre_grupo, 0 as grupo_id 
                  FROM inscripciones i 
                  INNER JOIN equipos e ON i.equipo_id = e.id 
                  WHERE i.torneo_id = :torneo 
                  ORDER BY e.nombre ASC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":torneo", $torneo_id);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data, 'tipo_torneo' => $torneo['tipo'] ?? '']);
    exit;
}

if ($action === 'crear') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $fase = $_POST['fase'] ?? '';
    $local_id = $_POST['equipo_local_id'] ?? '';
    $visitante_id = $_POST['equipo_visitante_id'] ?? '';
    
    if (empty($torneo_id) || empty($fecha) || empty($hora) || empty($fase) || empty($local_id) || empty($visitante_id)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    // Validación de grupo si es fase de grupos
    $stmtT = $conn->prepare("SELECT tipo FROM torneos WHERE id = :id");
    $stmtT->execute([':id' => $torneo_id]);
    $torneo = $stmtT->fetch(PDO::FETCH_ASSOC);

    if ($torneo['tipo'] === 'fase_grupos') {
        $stmtG1 = $conn->prepare("SELECT grupo_id FROM grupo_equipos ge INNER JOIN grupos g ON ge.grupo_id = g.id WHERE ge.equipo_id = :eid AND g.torneo_id = :tid");
        $stmtG1->execute([':eid' => $local_id, ':tid' => $torneo_id]);
        $g1 = $stmtG1->fetchColumn();

        $stmtG2 = $conn->prepare("SELECT grupo_id FROM grupo_equipos ge INNER JOIN grupos g ON ge.grupo_id = g.id WHERE ge.equipo_id = :eid AND g.torneo_id = :tid");
        $stmtG2->execute([':eid' => $visitante_id, ':tid' => $torneo_id]);
        $g2 = $stmtG2->fetchColumn();

        if (!$g1 || !$g2 || $g1 !== $g2) {
            echo json_encode(['success' => false, 'message' => 'Los equipos deben pertenecer al mismo grupo en este torneo.']);
            exit;
        }
    }

    $query = "INSERT INTO partidos (torneo_id, equipo_local_id, equipo_visitante_id, fecha, hora, fase, estado) 
              VALUES (:torneo, :local, :vis, :fecha, :hora, :fase, 'programado')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":torneo", $torneo_id);
    $stmt->bindParam(":local", $local_id);
    $stmt->bindParam(":vis", $visitante_id);
    $stmt->bindParam(":fecha", $fecha);
    $stmt->bindParam(":hora", $hora);
    $stmt->bindParam(":fase", $fase);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Partido programado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al programar partido']);
    }
    exit;
}

if ($action === 'generar_preview_fecha') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    $jornada = intval($_POST['jornada'] ?? 1);
    
    // Obtener grupos del torneo
    $stmtG = $conn->prepare("SELECT id, nombre_grupo FROM grupos WHERE torneo_id = :tid");
    $stmtG->execute([':tid' => $torneo_id]);
    $grupos = $stmtG->fetchAll(PDO::FETCH_ASSOC);
    
    $preview = [];
    
    if (count($grupos) > 0) {
        foreach ($grupos as $g) {
            $stmtE = $conn->prepare("SELECT e.id, e.nombre FROM grupo_equipos ge INNER JOIN equipos e ON ge.equipo_id = e.id WHERE ge.grupo_id = :gid");
            $stmtE->execute([':gid' => $g['id']]);
            $equipos = $stmtE->fetchAll(PDO::FETCH_ASSOC);
            $preview = array_merge($preview, generarEmparejamientos($conn, $torneo_id, $equipos, $jornada, $g['id'], $g['nombre_grupo']));
        }
    } else {
        $stmtE = $conn->prepare("SELECT e.id, e.nombre FROM inscripciones i INNER JOIN equipos e ON i.equipo_id = e.id WHERE i.torneo_id = :tid");
        $stmtE->execute([':tid' => $torneo_id]);
        $equipos = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        $preview = generarEmparejamientos($conn, $torneo_id, $equipos, $jornada, null, 'General');
    }
    
    echo json_encode(['success' => true, 'data' => $preview]);
    exit;
}

if ($action === 'preview_eliminatoria') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    $fase_size = intval($_POST['fase_size'] ?? 0);

    // 1. Obtener todos los equipos del torneo
    $stmtE = $conn->prepare("SELECT e.id, e.nombre, e.logo FROM inscripciones i INNER JOIN equipos e ON i.equipo_id = e.id WHERE i.torneo_id = :tid");
    $stmtE->execute([':tid' => $torneo_id]);
    $equipos = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    if (count($equipos) < $fase_size) {
        echo json_encode(['success' => false, 'message' => "No hay suficientes equipos inscritos en el torneo ({$fase_size} requeridos, " . count($equipos) . " encontrados)."]);
        exit;
    }

    // 2. Calcular tabla global
    $ranking = calcularClasificacionGlobal($conn, $torneo_id, $equipos);

    // 3. Tomar los primeros N (los mejor clasificados)
    $clasificados = array_slice($ranking, 0, $fase_size);

    $preview = [];
    $matches_count = $fase_size / 2;
    for ($i = 0; $i < $matches_count; $i++) {
        $mejor = $clasificados[$i];
        $peor = $clasificados[$fase_size - 1 - $i];
        $llave_id = $fase_size == 16 ? 'Octavos-L' : ($fase_size == 8 ? 'Cuartos-L' : ($fase_size == 4 ? 'Semis-L' : 'Final-L'));
        $llave = $llave_id . ($i + 1);

        if ($fase_size == 16 || $fase_size == 8) {
            // Ida: El peor clasificado es Local
            $preview[] = [
                'es_ida' => 1,
                'llave' => $llave,
                'local_id' => $peor['id'], 'local_nombre' => $peor['nombre'], 'local_pos' => $fase_size - $i,
                'visitante_id' => $mejor['id'], 'visitante_nombre' => $mejor['nombre'], 'visitante_pos' => $i + 1
            ];
            // Vuelta: El mejor clasificado es Local
            $preview[] = [
                'es_ida' => 0,
                'llave' => $llave,
                'local_id' => $mejor['id'], 'local_nombre' => $mejor['nombre'], 'local_pos' => $i + 1,
                'visitante_id' => $peor['id'], 'visitante_nombre' => $peor['nombre'], 'visitante_pos' => $fase_size - $i
            ];
        } else {
            // Partido Único: El mejor clasificado es Local
            $preview[] = [
                'es_ida' => 0,
                'llave' => $llave,
                'local_id' => $mejor['id'], 'local_nombre' => $mejor['nombre'], 'local_pos' => $i + 1,
                'visitante_id' => $peor['id'], 'visitante_nombre' => $peor['nombre'], 'visitante_pos' => $fase_size - $i
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $preview]);
    exit;
}

if ($action === 'guardar_partidos_masivo') {
    $torneo_id = $_POST['torneo_id'] ?? '';
    $fase = $_POST['fase'] ?? '';
    $partidos = json_decode($_POST['partidos'], true);
    
    if (empty($partidos)) {
        echo json_encode(['success' => false, 'message' => 'No hay partidos para guardar']);
        exit;
    }
    
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO partidos (torneo_id, equipo_local_id, equipo_visitante_id, fecha, hora, fase, estado, llave, es_ida) 
                                VALUES (:tid, :loc, :vis, :fec, :hor, :fas, 'programado', :llave, :esida)");
        foreach ($partidos as $p) {
            $llave = isset($p['llave']) ? $p['llave'] : null;
            $es_ida = isset($p['es_ida']) ? $p['es_ida'] : 0;
            $stmt->execute([
                ':tid' => $torneo_id, ':loc' => $p['local_id'], ':vis' => $p['visitante_id'],
                ':fec' => $p['fecha'], ':hor' => $p['hora'], ':fas' => $fase,
                ':llave' => $llave, ':esida' => $es_ida
            ]);
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => count($partidos) . ' partidos programados correctamente.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

function hanJugadoAntes($conn, $torneo_id, $id1, $id2) {
    if (!$id1 || !$id2) return false;
    $stmt = $conn->prepare("SELECT id FROM partidos WHERE torneo_id = :tid AND ((equipo_local_id = :id1 AND equipo_visitante_id = :id2) OR (equipo_local_id = :id2 AND equipo_visitante_id = :id1))");
    $stmt->execute([':tid' => $torneo_id, ':id1' => $id1, ':id2' => $id2]);
    return $stmt->rowCount() > 0;
}

function generarEmparejamientos($conn, $torneo_id, $equipos, $jornada, $grupo_id, $grupo_nombre) {
    $n = count($equipos);
    if ($n < 2) return [];
    
    $originalEquipos = $equipos;
    $hasBye = ($n % 2 != 0);
    if ($hasBye) { 
        $equipos[] = ['id' => null, 'nombre' => 'DESCANSA']; 
        $n++; 
    }
    
    $rounds = $n - 1;
    $actual_round = ($jornada - 1) % $rounds;
    $matches = [];
    $half = $n / 2;
    $pivot = $equipos[0];
    $others = array_slice($equipos, 1);
    
    for ($i = 0; $i < $actual_round; $i++) { array_unshift($others, array_pop($others)); }
    
    $restingTeam = null;

    for ($i = 0; $i < $half; $i++) {
        $local = ($i == 0) ? $pivot : $others[$i - 1];
        $visitante = $others[count($others) - $i - 1];
        
        if ($actual_round % 2 != 0 && $i == 0) { $tmp = $local; $local = $visitante; $visitante = $tmp; }
        
        if ($local['id'] !== null && $visitante['id'] !== null) {
            // Validar si ya jugaron antes
            if (!hanJugadoAntes($conn, $torneo_id, $local['id'], $visitante['id'])) {
                $matches[] = [
                    'grupo_id' => $grupo_id, 'grupo_nombre' => $grupo_nombre,
                    'local_id' => $local['id'], 'local_nombre' => $local['nombre'],
                    'visitante_id' => $visitante['id'], 'visitante_nombre' => $visitante['nombre']
                ];
            }
        } else {
            // Identificar quién "descansaba"
            $restingTeam = ($local['id'] === null) ? $visitante : $local;
        }
    }

    // Si había un equipo descansando, buscarle un rival entre los que YA juegan en esta jornada
    if ($hasBye && $restingTeam) {
        // Intentar emparejarlo con alguien que no haya jugado contra él
        foreach ($originalEquipos as $rival) {
            if ($rival['id'] !== $restingTeam['id']) {
                if (!hanJugadoAntes($conn, $torneo_id, $restingTeam['id'], $rival['id'])) {
                    // Check if they are already in matches as local or visitante to maintain "double match" context
                    // For UI simplicity, we just add the match.
                    $matches[] = [
                        'grupo_id' => $grupo_id, 'grupo_nombre' => $grupo_nombre,
                        'local_id' => $restingTeam['id'], 'local_nombre' => $restingTeam['nombre'],
                        'visitante_id' => $rival['id'], 'visitante_nombre' => $rival['nombre'],
                        'nota' => 'Partido Extra (Equipos Impares)'
                    ];
                    break; // Solo un partido extra por jornada para el que descansaba
                }
            }
        }
    }
    
    return $matches;
}

if ($action === 'avanzar_fase') {
    $torneo_id = $_GET['torneo_id'] ?? 0;
    $fase_origen = $_GET['fase_origen'] ?? '';

    // 1. Determinar fase destino
    $fase_destino = "Fase Final";
    if ($fase_origen === 'Octavos de Final') $fase_destino = "Cuartos de Final";
    elseif ($fase_origen === 'Cuartos de Final') $fase_destino = "Semifinal";
    elseif (in_array($fase_origen, ['Semifinal', 'Semifinales'])) $fase_destino = "Final";

    // 2. Obtener partidos de la fase origen
    $stmt = $conn->prepare("SELECT p.*, l.nombre as local_nombre, v.nombre as visitante_nombre 
                            FROM partidos p 
                            INNER JOIN equipos l ON p.equipo_local_id = l.id
                            INNER JOIN equipos v ON p.equipo_visitante_id = v.id
                            WHERE p.torneo_id = :tid AND p.fase = :fase");
    $stmt->execute([':tid' => $torneo_id, ':fase' => $fase_origen]);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidos)) {
        echo json_encode(['success' => false, 'message' => "No se encontraron partidos en la fase $fase_origen."]);
        exit;
    }

    // 3. Agrupar por llaves para calcular ganadores
    $llaves = [];
    foreach ($partidos as $p) {
        $ll = $p['llave'];
        if (!isset($llaves[$ll])) $llaves[$ll] = [];
        $llaves[$ll][] = $p;
    }

    $ganadores = [];
    $perdedores_semis = []; // Para el Tercer Puesto

    ksort($llaves); // Mantener orden L1, L2, L3...

    foreach ($llaves as $nombre_llave => $lista) {
        $eq1 = ['id' => $lista[0]['equipo_local_id'], 'nombre' => $lista[0]['local_nombre'], 'goles' => 0, 'penales' => 0];
        $eq2 = ['id' => $lista[0]['equipo_visitante_id'], 'nombre' => $lista[0]['visitante_nombre'], 'goles' => 0, 'penales' => 0];

        foreach ($lista as $p) {
            if ($p['equipo_local_id'] == $eq1['id']) {
                $eq1['goles'] += intval($p['goles_local']);
                $eq2['goles'] += intval($p['goles_visitante']);
                if ($p['penales_local'] !== null) {
                    $eq1['penales'] = intval($p['penales_local']);
                    $eq2['penales'] = intval($p['penales_visitante']);
                }
            } else {
                $eq2['goles'] += intval($p['goles_local']);
                $eq1['goles'] += intval($p['goles_visitante']);
                if ($p['penales_local'] !== null) {
                    $eq2['penales'] = intval($p['penales_local']);
                    $eq1['penales'] = intval($p['penales_visitante']);
                }
            }
        }

        $ganador = null;
        $perdedor = null;
        if ($eq1['goles'] > $eq2['goles']) { $ganador = $eq1; $perdedor = $eq2; }
        elseif ($eq2['goles'] > $eq1['goles']) { $ganador = $eq2; $perdedor = $eq1; }
        else {
            // Empate global -> Penales
            if ($eq1['penales'] > $eq2['penales']) { $ganador = $eq1; $perdedor = $eq2; }
            elseif ($eq2['penales'] > $eq1['penales']) { $ganador = $eq2; $perdedor = $eq1; }
        }

        if ($ganador) {
            $ganadores[] = $ganador;
            if (in_array($fase_origen, ['Semifinal', 'Semifinales'])) $perdedores_semis[] = $perdedor;
        }
    }

    // 4. Generar nuevas llaves (Cruces: Ganador L1 vs Ganador L2, etc.)
    $preview = [];
    $num_ganadores = count($ganadores);
    
    // Para Semis/Final suele ser directo: L1 vs L2, L3 vs L4...
    for ($i = 0; $i < $num_ganadores; $i += 2) {
        if (isset($ganadores[$i+1])) {
            $g1 = $ganadores[$i];
            $g2 = $ganadores[$i+1];

            $prefijo = ($fase_destino === 'Cuartos de Final') ? 'Cuartos-L' : (($fase_destino === 'Semifinal') ? 'Semis-L' : 'Final-L');
            $nueva_llave = $prefijo . (($i / 2) + 1);

            if ($fase_destino === 'Cuartos de Final') {
                // Ida y Vuelta
                $preview[] = ['es_ida' => 1, 'llave' => $nueva_llave, 'local_id' => $g2['id'], 'local_nombre' => $g2['nombre'], 'visitante_id' => $g1['id'], 'visitante_nombre' => $g1['nombre']];
                $preview[] = ['es_ida' => 0, 'llave' => $nueva_llave, 'local_id' => $g1['id'], 'local_nombre' => $g1['nombre'], 'visitante_id' => $g2['id'], 'visitante_nombre' => $g2['nombre']];
            } else {
                // Único
                $preview[] = ['es_ida' => 0, 'llave' => $nueva_llave, 'local_id' => $g1['id'], 'local_nombre' => $g1['nombre'], 'visitante_id' => $g2['id'], 'visitante_nombre' => $g2['nombre']];
            }
        }
    }

    // 5. Caso especial: Tercer Puesto (Perdedores de Semifinales)
    if ($fase_origen === 'Semifinal' && count($perdedores_semis) >= 2) {
        $preview[] = [
            'es_ida' => 0,
            'llave' => 'Tercer Puesto',
            'local_id' => $perdedores_semis[0]['id'], 'local_nombre' => $perdedores_semis[0]['nombre'],
            'visitante_id' => $perdedores_semis[1]['id'], 'visitante_nombre' => $perdedores_semis[1]['nombre']
        ];
    }

    echo json_encode(['success' => true, 'data' => $preview, 'siguiente_fase' => $fase_destino]);
    exit;
}

function calcularClasificacionGlobal($conn, $torneo_id, $equipos) {
    $stats = [];
    foreach($equipos as $e) {
        $stats[$e['id']] = [
            'id' => $e['id'], 'nombre' => $e['nombre'],
            'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
        ];
    }
    
    $stmtP = $conn->prepare("SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante 
                             FROM partidos WHERE torneo_id = :tid AND estado IN ('finalizado', 'walkover')");
    $stmtP->execute([':tid' => $torneo_id]);
    $partidos = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($partidos as $p) {
        $loc = $p['equipo_local_id'];
        $vis = $p['equipo_visitante_id'];
        $gl = intval($p['goles_local']);
        $gv = intval($p['goles_visitante']);
        
        if(isset($stats[$loc])) {
            $stats[$loc]['pj']++; $stats[$loc]['gf'] += $gl; $stats[$loc]['gc'] += $gv;
            if($gl > $gv) { $stats[$loc]['pts'] += 3; } elseif($gl === $gv) { $stats[$loc]['pts'] += 1; }
        }
        if(isset($stats[$vis])) {
            $stats[$vis]['pj']++; $stats[$vis]['gf'] += $gv; $stats[$vis]['gc'] += $gl;
            if($gv > $gl) { $stats[$vis]['pts'] += 3; } elseif($gv === $gl) { $stats[$vis]['pts'] += 1; }
        }
    }

    $stmtImp = $conn->prepare("SELECT i.equipo_que_impugna_id, i.puntos_castigo, p.equipo_local_id, p.equipo_visitante_id, p.goles_local, p.goles_visitante
                               FROM impugnaciones i INNER JOIN partidos p ON i.partido_id = p.id
                               WHERE p.torneo_id = :tid AND i.estado = 'aceptada'");
    $stmtImp->execute([':tid' => $torneo_id]);
    $ajustes = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

    foreach($ajustes as $aj) {
        $denunciante = $aj['equipo_que_impugna_id'];
        $puntos_castigo = min(3, intval($aj['puntos_castigo']));
        $oponente = ($denunciante == $aj['equipo_local_id']) ? $aj['equipo_visitante_id'] : $aj['equipo_local_id'];
        $gl = intval($aj['goles_local']); $gv = intval($aj['goles_visitante']);

        if(isset($stats[$denunciante])) {
            $pts_actuales = 0;
            if ($denunciante == $aj['equipo_local_id']) $pts_actuales = ($gl > $gv) ? 3 : (($gl == $gv) ? 1 : 0);
            else $pts_actuales = ($gv > $gl) ? 3 : (($gv == $gl) ? 1 : 0);
            $stats[$denunciante]['pts'] += max(0, 3 - $pts_actuales);
        }
        if(isset($stats[$oponente])) $stats[$oponente]['pts'] -= $puntos_castigo;
    }
    
    $listaRanking = [];
    foreach($stats as $id => $s) {
        $s['dg'] = $s['gf'] - $s['gc'];
        $listaRanking[] = $s;
    }
    
    usort($listaRanking, function($a, $b) {
        if($a['pts'] !== $b['pts']) return $b['pts'] <=> $a['pts'];
        if($a['dg'] !== $b['dg'])  return $b['dg'] <=> $a['dg'];
        if($a['gf'] !== $b['gf'])  return $b['gf'] <=> $a['gf'];
        return strcmp($a['nombre'], $b['nombre']);
    });
    
    return $listaRanking;
}

if ($action === 'cambiar_estado') {
    $id = $_POST['id_partido'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    $query = "UPDATE partidos SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":estado", $estado);
    $stmt->bindParam(":id", $id);
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado.']);
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id_partido'] ?? '';
    
    $stmt = $conn->prepare("DELETE FROM partidos WHERE id = :id");
    $stmt->bindParam(":id", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Partido eliminado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
