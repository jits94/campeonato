<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Traer conteos para el dashboard
$stmtEquipos = $conn->query("SELECT COUNT(*) FROM equipos");
$totEquipos = $stmtEquipos->fetchColumn();

$stmtTorneos = $conn->query("SELECT COUNT(*) FROM torneos WHERE estado = 'activo'");
$totTorneos = $stmtTorneos->fetchColumn();

$stmtJugadores = $conn->query("SELECT COUNT(*) FROM jugadores");
$totJugadores = $stmtJugadores->fetchColumn();

// 1. Ingresos Totales (Suma de inscripciones + cobros_partido pagados + sanciones pagadas + transferencias)
$stmtIngresos = $conn->query("SELECT 
    (SELECT COALESCE(SUM(monto_cobrado), 0) FROM inscripciones) + 
    (SELECT COALESCE(SUM(monto), 0) FROM cobros_partido WHERE estado = 'pagado') + 
    (SELECT COALESCE(SUM(monto), 0) FROM sanciones WHERE estado = 'pagado') +
    (SELECT COALESCE(SUM(monto), 0) FROM transferencias) as total");
$totIngresos = $stmtIngresos->fetchColumn();

// 2. Partidos de Hoy
$stmtHoy = $conn->query("SELECT p.*, l.nombre as local_nombre, l.logo as local_logo, 
                                v.nombre as visitante_nombre, v.logo as visitante_logo
                         FROM partidos p
                         JOIN equipos l ON p.equipo_local_id = l.id
                         JOIN equipos v ON p.equipo_visitante_id = v.id
                         WHERE p.fecha = CURDATE()
                         ORDER BY p.hora ASC");
$partidosHoy = $stmtHoy->fetchAll(PDO::FETCH_ASSOC);

// 3. Tarjetas Pendientes
$stmtSanciones = $conn->query("SELECT s.*, j.nombre as jugador_nombre, e.tipo as tipo_tarjeta, eq.nombre as equipo_nombre,
                                      p.fecha, el.nombre as local_nombre, ev.nombre as visitante_nombre
                               FROM sanciones s
                               JOIN eventos_partido e ON s.evento_id = e.id
                               JOIN jugadores j ON e.jugador_id = j.id
                               JOIN equipos eq ON j.equipo_id = eq.id
                               JOIN partidos p ON e.partido_id = p.id
                               JOIN equipos el ON p.equipo_local_id = el.id
                               JOIN equipos ev ON p.equipo_visitante_id = ev.id
                               WHERE s.estado = 'pendiente'
                               ORDER BY s.id DESC LIMIT 5");
$sancionesPendientes = $stmtSanciones->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold text-dark">Dashboard <span class="fs-5 fw-normal text-muted">| Resumen del Campeonato</span></h2>
    </div>
</div>

<div class="row">
    <!-- Total Equipos -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 bg-primary text-white stat-card">
            <div class="card-body">
                <div class="inner">
                    <h3><?= $totEquipos ?></h3>
                    <p class="fs-5 mb-0">Equipos Registrados</p>
                </div>
                <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
            </div>
            <a href="equipos.php" class="card-footer text-white text-decoration-none d-flex align-items-center justify-content-between">
                <span>Ver detalles</span>
                <i class="fa-solid fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Torneos Activos -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 bg-success text-white stat-card">
            <div class="card-body">
                <div class="inner">
                    <h3><?= $totTorneos ?></h3>
                    <p class="fs-5 mb-0">Torneos Activos</p>
                </div>
                <div class="icon"><i class="fa-solid fa-trophy"></i></div>
            </div>
            <a href="torneos.php" class="card-footer text-white text-decoration-none d-flex align-items-center justify-content-between">
                <span>Ver detalles</span>
                <i class="fa-solid fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Jugadores -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 bg-info text-white stat-card">
            <div class="card-body">
                <div class="inner">
                    <h3><?= $totJugadores ?></h3>
                    <p class="fs-5 mb-0">Jugadores</p>
                </div>
                <div class="icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <a href="jugadores.php" class="card-footer text-white text-decoration-none d-flex align-items-center justify-content-between">
                <span>Ver detalles</span>
                <i class="fa-solid fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Ingresos -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 bg-dark text-white stat-card">
            <div class="card-body">
                <div class="inner">
                    <h3 class="text-warning">Bs. <?= number_format($totIngresos, 2) ?></h3>
                    <p class="fs-5 mb-0">Ingresos Totales</p>
                </div>
                <div class="icon"><i class="fa-solid fa-wallet text-warning"></i></div>
            </div>
            <a href="finanzas.php" class="card-footer text-white text-decoration-none d-flex align-items-center justify-content-between">
                <span>Ver finanzas</span>
                <i class="fa-solid fa-arrow-circle-right text-warning"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <a href="live.php" class="text-decoration-none h5 mb-0 text-primary fw-bold">
                    <i class="fa-solid fa-tv me-2"></i> Partidos de Hoy en Vivo
                </a>
            </div>
            <div class="card-body p-0">
                <?php if(empty($partidosHoy)): ?>
                    <div class="text-center p-5">
                        <i class="fa-regular fa-futbol fa-4x text-muted mb-3 opacity-25"></i>
                        <h4 class="text-muted">No hay partidos programados para hoy</h4>
                        <p class="text-secondary">Visite el módulo de partidos para configurar las próximas fechas.</p>
                        <?php if($_SESSION['rol'] === 'administrador'): ?>
                        <a href="partidos.php" class="btn btn-primary mt-2">Ir a Programación</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Hora</th>
                                    <th class="text-end">Local</th>
                                    <th class="text-center">Resultado</th>
                                    <th>Visitante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($partidosHoy as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= date('H:i', strtotime($p['hora'])) ?></td>
                                    <td class="text-end">
                                        <?= htmlspecialchars($p['local_nombre']) ?>
                                        <img src="uploads/logos/<?= $p['local_logo'] ?>" class="rounded-circle ms-2" style="width:25px;height:25px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                    </td>
                                    <td class="text-center">
                                        <div class="bg-dark text-white rounded px-2 py-1 d-inline-block fw-bold" style="min-width: 60px;">
                                            <?= $p['estado'] == 'programado' ? '- : -' : $p['goles_local'].' - '.$p['goles_visitante'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="uploads/logos/<?= $p['visitante_logo'] ?>" class="rounded-circle me-2" style="width:25px;height:25px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                        <?= htmlspecialchars($p['visitante_nombre']) ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'bg-secondary';
                                        if($p['estado'] == 'finalizado') $badgeClass = 'bg-success';
                                        if($p['estado'] == 'walkover') $badgeClass = 'bg-dark';
                                        
                                        if($p['estado'] == 'en_juego'): ?>
                                            <span class="badge bg-danger timer-blink">EN VIVO</span>
                                        <?php else: ?>
                                            <span class="badge <?= $badgeClass ?> text-uppercase"><?= $p['estado'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3">
                <h5 class="mb-0 text-danger fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Tarjetas Pendientes</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if(empty($sancionesPendientes)): ?>
                        <li class="list-group-item text-muted text-center py-4">
                            No hay tarjetas pendientes de pago.
                        </li>
                    <?php else: ?>
                        <?php foreach($sancionesPendientes as $s): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-clone text-<?= $s['tipo_tarjeta'] == 'amarilla' ? 'warning' : 'danger' ?> me-3 fs-4"></i>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($s['jugador_nombre']) ?></div>
                                    <small class="text-muted d-block"><?= htmlspecialchars($s['equipo_nombre']) ?></small>
                                    <small class="text-secondary" style="font-size: 0.75rem;"><i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($s['fecha'])) ?> | <?= htmlspecialchars($s['local_nombre']) ?> vs <?= htmlspecialchars($s['visitante_nombre']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-danger">Bs. <?= number_format($s['monto'], 2) ?></div>
                                <small class="badge bg-light text-dark border">Pendiente</small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <!-- <li class="list-group-item bg-light text-center">
                            <a href="finanzas.php" class="btn btn-sm btn-link text-decoration-none text-dark fw-bold">Ver todas las finanzas</a>
                        </li> -->
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
