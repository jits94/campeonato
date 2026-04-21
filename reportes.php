<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmtTorneos = $conn->query("SELECT id, nombre FROM torneos ORDER BY id DESC");
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-column text-primary me-2"></i> Reportes y Estadísticas</h2>
    <div class="d-flex align-items-center">
        <label class="fw-bold me-2 mb-0">Filtrar por Torneo:</label>
        <select class="form-select" id="filtro_torneo_reportes" style="min-width: 200px;">
            <option value="0">-- Todos los Torneos --</option>
            <?php foreach($torneos as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="pills-goleadores-tab" data-bs-toggle="pill" data-bs-target="#pills-goleadores" type="button" role="tab"><i class="fa-solid fa-futbol me-1"></i> Goleadores</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="pills-tarjetas-tab" data-bs-toggle="pill" data-bs-target="#pills-tarjetas" type="button" role="tab"><i class="fa-solid fa-clone me-1"></i> Tarjetas</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="pills-fairplay-tab" data-bs-toggle="pill" data-bs-target="#pills-fairplay" type="button" role="tab"><i class="fa-solid fa-handshake-angle me-1"></i> Fair Play</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="pills-campeones-tab" data-bs-toggle="pill" data-bs-target="#pills-campeones" type="button" role="tab" onclick="cargarCampeones()"><i class="fa-solid fa-crown me-1"></i> Historial de Campeones</button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    <!-- Goleadores -->
    <div class="tab-pane fade show active" id="pills-goleadores" role="tabpanel">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold text-primary">Ranking de Goleadores</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaGoleadores">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pos</th>
                                        <th>Jugador</th>
                                        <th>Equipo</th>
                                        <th class="text-center">Goles</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-trophy text-warning me-2"></i>Equipos más Goleadores</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="rankingGolesEquipos">
                            <li class="list-group-item text-center text-muted py-4">Cargando...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas -->
    <div class="tab-pane fade" id="pills-tarjetas" role="tabpanel">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="fa-solid fa-circle-info fs-4 me-3 text-primary"></i>
            <div>
                <h6 class="fw-bold mb-1">¿Qué es el Índice de Indisciplina?</h6>
                <p class="small mb-0 text-muted">Es un sistema de puntos para medir la conducta deportiva: <strong class="text-dark">Amarilla = 1 pt</strong> y <strong class="text-dark">Roja = 3 pts</strong>. Se usa para clasificar a los jugadores y equipos según su nivel de sanciones acumuladas.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold text-danger">Jugadores con más Sanciones</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaTarjetas">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jugador</th>
                                        <th>Equipo</th>
                                        <th class="text-center">Amarillas</th>
                                        <th class="text-center">Rojas</th>
                                        <th class="text-center">Índice</th>
                                        <th class="text-center">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Distribución de Tarjetas</h5></div>
                    <div class="card-body">
                        <canvas id="chartTarjetas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fair Play -->
    <div class="tab-pane fade" id="pills-fairplay" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-medal me-2"></i>Premio Fair Play (Equipos más limpios)</h5>
                <span class="badge bg-light text-dark border align-self-center">Cálculo: Suma de puntos de índice de todos los jugadores</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaFairPlay">
                        <thead class="table-light text-center">
                            <tr>
                                <th class="text-start">Equipo</th>
                                <th>Amarillas</th>
                                <th>Rojas</th>
                                <th>Puntos de Castigo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody class="text-center"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Campeones -->
    <div class="tab-pane fade" id="pills-campeones" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center">
                <i class="fa-solid fa-crown text-warning fs-4 me-2"></i>
                <h5 class="mb-0 fw-bold">Historial de Campeones por Torneo</h5>
            </div>
            <div class="card-body p-0">
                <div id="listaCampeones" class="p-3">
                    <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Selecciona la pestaña para cargar...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Tarjetas -->
<div class="modal fade" id="modalDetalleTarjetas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-clone me-2"></i>Detalle de Tarjetas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex align-items-center mb-4 bg-white p-3 rounded shadow-sm border">
                    <img id="modal_jugador_foto" src="assets/img/default_user.png" class="rounded-circle me-3 border" style="width: 60px; height: 60px; object-fit: cover;">
                    <div>
                        <h4 class="mb-0 fw-bold text-dark" id="modal_jugador_nombre">Jugador</h4>
                        <p class="mb-0 text-muted" id="modal_equipo_nombre"><i class="fa-solid fa-shield-halved me-1"></i> Equipo</p>
                    </div>
                </div>
                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table table-hover align-middle mb-0" id="tablaDetalleTarjetas">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Partido</th>
                                <th class="text-center">Tarjeta</th>
                                <th class="text-center">Minuto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/reportes.js"></script>

<?php require_once 'includes/footer.php'; ?>
