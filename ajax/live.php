<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

// Obtener partidos que son de HOY, o que están EN JUEGO ahora mismo sin importar la fecha (por si se retrasó)
$query = "SELECT p.*, 
                 l.nombre as local_nombre, l.logo as local_logo,
                 v.nombre as visitante_nombre, v.logo as visitante_logo,
                 t.nombre as torneo_nombre,
                 COALESCE(g.nombre_grupo, '') as nombre_grupo
          FROM partidos p
          INNER JOIN equipos l ON p.equipo_local_id = l.id
          INNER JOIN equipos v ON p.equipo_visitante_id = v.id
          INNER JOIN torneos t ON p.torneo_id = t.id
          LEFT JOIN grupo_equipos ge ON l.id = ge.equipo_id
          LEFT JOIN grupos g ON ge.grupo_id = g.id AND t.id = g.torneo_id
          WHERE p.fecha = CURDATE() OR p.estado = 'en_juego'
          ORDER BY t.id, g.id, p.estado = 'en_juego' DESC, p.hora ASC";

$stmt = $conn->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($data as &$row) {
    if (!$row) continue;
    $row['hora_formateada'] = date('H:i', strtotime($row['hora']));

    // Si es partido de vuelta en fase eliminatoria, buscar IDA
    if (isset($row['llave']) && $row['llave'] !== null && in_array($row['fase'], ['Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Tercer Puesto', 'Final', 'Fase Final'])) {
        if (isset($row['es_ida']) && $row['es_ida'] == 0) {
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
?>
