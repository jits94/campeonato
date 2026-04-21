<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json; charset=utf-8');

$torneo_id = intval($_GET['torneo_id'] ?? 0);
$tipo = $_GET['tipo'] ?? 'todos_contra_todos';

if($torneo_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Torneo inválido.']);
    exit;
}

if(isset($_GET['action']) && $_GET['action'] === 'obtener_llaves') {
    // Retornar la estructura de llaves
    $torneo_id = intval($_GET['torneo_id'] ?? 0);
    $stmt = $conn->prepare("SELECT p.*, 
                                   l.nombre as local_nombre, l.logo as local_logo,
                                   v.nombre as visitante_nombre, v.logo as visitante_logo
                            FROM partidos p
                            INNER JOIN equipos l ON p.equipo_local_id = l.id
                            INNER JOIN equipos v ON p.equipo_visitante_id = v.id
                            WHERE p.torneo_id = :tid 
                            AND (
                                p.fase LIKE '%Octavos%' OR 
                                p.fase LIKE '%Cuartos%' OR 
                                p.fase LIKE '%Semifinal%' OR 
                                p.fase LIKE '%Tercer%' OR 
                                p.fase LIKE '%Final%'
                            )
                            AND p.llave IS NOT NULL
                            ORDER BY 
                                CASE p.fase 
                                    WHEN 'Octavos de Final' THEN 1 
                                    WHEN 'Cuartos de Final' THEN 2 
                                    WHEN 'Semifinal' THEN 3
                                    WHEN 'Tercer Puesto' THEN 4
                                    WHEN 'Final' THEN 5
                                    WHEN 'Fase Final' THEN 6
                                    ELSE 99 
                                END ASC, 
                                p.llave ASC, p.es_ida DESC");
    $stmt->execute([':tid' => $torneo_id]);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bracket = [];
    foreach($partidos as $p) {
        $fase = $p['fase'];
        $ll = $p['llave'];
        if(!isset($bracket[$fase])) $bracket[$fase] = [];
        if(!isset($bracket[$fase][$ll])) $bracket[$fase][$ll] = [];
        $bracket[$fase][$ll][] = $p;
    }

    echo json_encode(['success' => true, 'data' => $bracket]);
    exit;
}

// 1. Obtener la lista de equipos correspondientes según el tipo
$tabla = [];

if($tipo === 'todos_contra_todos') {
    $stmt = $conn->prepare("SELECT e.id, e.nombre, e.logo FROM inscripciones i INNER JOIN equipos e ON i.equipo_id = e.id WHERE i.torneo_id = :tid");
    $stmt->execute([':tid' => $torneo_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ranking = calcularPuntos($conn, $torneo_id, $equipos);
    echo json_encode(['success' => true, 'data' => $ranking]);

} else if($tipo === 'fase_grupos') {
    // Listar grupos de ese torneo
    $stmtG = $conn->prepare("SELECT id, nombre_grupo FROM grupos WHERE torneo_id = :tid ORDER BY nombre_grupo ASC");
    $stmtG->execute([':tid' => $torneo_id]);
    $gruposBD = $stmtG->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($gruposBD) === 0) {
        echo json_encode(['success' => false, 'message' => 'No se han generado los grupos para este torneo. Vaya al menú "Grupos".']);
        exit;
    }
    
    $resultadoGrupos = [];
    
    // Novedad: Primero generamos la Clasificación Global con todos los equipos del torneo
    $stmtT = $conn->prepare("SELECT e.id, e.nombre, e.logo FROM inscripciones i INNER JOIN equipos e ON i.equipo_id = e.id WHERE i.torneo_id = :tid");
    $stmtT->execute([':tid' => $torneo_id]);
    $todosEquipos = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    $rankingGlobal = calcularPuntos($conn, $torneo_id, $todosEquipos);
    $resultadoGrupos['Clasificación General'] = $rankingGlobal;

    // Luego iteramos los grupos existentes para generar sus tablas específicas
    foreach($gruposBD as $g) {
        $gid = $g['id'];
        $stmtEq = $conn->prepare("SELECT e.id, e.nombre, e.logo FROM grupo_equipos ge INNER JOIN equipos e ON ge.equipo_id = e.id WHERE ge.grupo_id = :gid");
        $stmtEq->execute([':gid' => $gid]);
        $equiposGrupo = $stmtEq->fetchAll(PDO::FETCH_ASSOC);
        
        // CUIDADO: Solo procesamos los partidos que les correspondan en la BD
        // Como todos los partidos se guardan en`partidos` con el torneo_id, 
        // pasamos solo los equipos del grupo a la funcion, esta calculará sus stats.
        $ranking = calcularPuntos($conn, $torneo_id, $equiposGrupo);
        $resultadoGrupos[$g['nombre_grupo']] = $ranking;
    }
    
    echo json_encode(['success' => true, 'data' => $resultadoGrupos]);
}

function calcularPuntos($conn, $torneo_id, $equipos) {
    // Array para almacenar stats por equipo_id
    $stats = [];
    foreach($equipos as $e) {
        $stats[$e['id']] = [
            'id' => $e['id'],
            'nombre' => $e['nombre'],
            'logo' => $e['logo'],
            'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
        ];
    }
    
    // Obtener los partidos jugados (finalizado o walkover) de este torneo y que NO sean de fase eliminatoria
    $stmtP = $conn->prepare("SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, estado 
                             FROM partidos 
                             WHERE torneo_id = :tid 
                             AND estado IN ('finalizado', 'walkover')
                             AND fase NOT IN ('Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final')");
    $stmtP->execute([':tid' => $torneo_id]);
    $partidos = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar cada partido
    foreach($partidos as $p) {
        $loc = $p['equipo_local_id'];
        $vis = $p['equipo_visitante_id'];
        $gl = intval($p['goles_local']);
        $gv = intval($p['goles_visitante']);
        
        // Asegurar que el equipo está en la lista (para fase de grupos)
        if(isset($stats[$loc])) {
            $stats[$loc]['pj']++;
            $stats[$loc]['gf'] += $gl;
            $stats[$loc]['gc'] += $gv;
            
            if($gl > $gv) { $stats[$loc]['pg']++; $stats[$loc]['pts'] += 3; }
            elseif($gl === $gv) { $stats[$loc]['pe']++; $stats[$loc]['pts'] += 1; }
            else { $stats[$loc]['pp']++; }
        }
        
        if(isset($stats[$vis])) {
            $stats[$vis]['pj']++;
            $stats[$vis]['gf'] += $gv;
            $stats[$vis]['gc'] += $gl;
            
            if($gv > $gl) { $stats[$vis]['pg']++; $stats[$vis]['pts'] += 3; }
            elseif($gv === $gl) { $stats[$vis]['pe']++; $stats[$vis]['pts'] += 1; }
            else { $stats[$vis]['pp']++; }
        }
    }

    // --- AJUSTES POR IMPUGNACIONES ---
    $stmtImp = $conn->prepare("SELECT i.equipo_que_impugna_id, i.puntos_castigo, 
                                      p.equipo_local_id, p.equipo_visitante_id,
                                      p.goles_local, p.goles_visitante
                               FROM impugnaciones i
                               INNER JOIN partidos p ON i.partido_id = p.id
                               WHERE p.torneo_id = :tid AND i.estado = 'aceptada'");
    $stmtImp->execute([':tid' => $torneo_id]);
    $ajustes = $stmtImp->fetchAll(PDO::FETCH_ASSOC);

    foreach($ajustes as $aj) {
        $denunciante = $aj['equipo_que_impugna_id'];
        $puntos_castigo = min(3, intval($aj['puntos_castigo'])); // Cap a 3 puntos
        $oponente = ($denunciante == $aj['equipo_local_id']) ? $aj['equipo_visitante_id'] : $aj['equipo_local_id'];
        
        $gl = intval($aj['goles_local']);
        $gv = intval($aj['goles_visitante']);

        // 1. Calcular puntos a otorgar al denunciante para que llegue a 3
        if(isset($stats[$denunciante])) {
            $pts_actuales = 0;
            if ($denunciante == $aj['equipo_local_id']) {
                if ($gl > $gv) $pts_actuales = 3;
                elseif ($gl == $gv) $pts_actuales = 1;
            } else {
                if ($gv > $gl) $pts_actuales = 3;
                elseif ($gv == $gl) $pts_actuales = 1;
            }
            
            // Si perdio (0) -> +3. Si empato (1) -> +2. Si gano (3) -> +0.
            $pts_a_sumar = max(0, 3 - $pts_actuales);
            $stats[$denunciante]['pts'] += $pts_a_sumar;
        }

        // 2. Quitar puntos de castigo al equipo oponente (máximo 3)
        if(isset($stats[$oponente])) {
            $stats[$oponente]['pts'] -= $puntos_castigo;
        }
    }
    // ---------------------------------
    
    // Calcular Diferencia de Goles y preparar array final
    $listaRanking = [];
    foreach($stats as $id => $s) {
        $s['dg'] = $s['gf'] - $s['gc'];
        $listaRanking[] = $s;
    }
    
    // Ordenar: Pts DESC, DG DESC, GF DESC
    usort($listaRanking, function($a, $b) {
        if($a['pts'] !== $b['pts']) return $b['pts'] <=> $a['pts']; // Mayor puntos
        if($a['dg'] !== $b['dg'])  return $b['dg'] <=> $a['dg'];    // Mayor DG
        if($a['gf'] !== $b['gf'])  return $b['gf'] <=> $a['gf'];    // Mayor GF
        return strcmp($a['nombre'], $b['nombre']);                  // Alfabético
    });
    
    return $listaRanking;
}
