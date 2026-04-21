<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

if($_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>No tienes permisos para acceder a esta página.</div>";
    require_once 'includes/footer.php';
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmtTorneos = $conn->query("SELECT id, nombre FROM torneos WHERE estado = 'activo' ORDER BY id DESC");
$torneosActivos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

$torneo_id = $_GET['torneo_id'] ?? ($torneosActivos[0]['id'] ?? 0);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-money-bill-trend-up text-success me-2"></i> Finanzas y Control</h2>
    
    <div class="d-flex align-items-center">
        <label class="fw-bold me-2 mb-0">Torneo Activo:</label>
        <select class="form-select border-success shadow-sm" id="filtro_torneo_finanzas" style="min-width: 250px;">
            <option value="0">-- Ninguno / Historial General --</option>
            <?php foreach($torneosActivos as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($t['id'] == $torneo_id) ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 shadow-sm bg-white rounded-top" id="finanzasTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold py-3" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab" aria-selected="true">
            <i class="fa-solid fa-chart-pie text-success me-1"></i> Resumen Actual
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="gastos-tab" data-bs-toggle="tab" data-bs-target="#gastos" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-money-bill-transfer text-danger me-1"></i> Registro de Gastos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-hand-holding-dollar text-warning me-1"></i> Pagos Pendientes
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-clock-rotate-left text-info me-1"></i> Historial Torneos
        </button>
    </li>
</ul>

<div class="tab-content" id="finanzasTabsContent">
    
    <!-- Pestaña 1: Resumen -->
    <div class="tab-pane fade show active" id="resumen" role="tabpanel" tabindex="0">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-top border-success border-4">
                    <div class="card-body text-center p-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-3">Total Ingresos Confirmados</h6>
                        <h2 class="display-5 fw-bold text-success mb-0" id="lbl_ingresos">Bs. <span class="counter">0,00</span></h2>
                        <hr>
                        <ul class="list-unstyled text-start mb-0 small text-muted">
                            <li class="d-flex justify-content-between mb-2"><span>Inscripciones:</span> <strong id="lbl_inscr">0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Sanciones (Tarjetas):</span> <strong id="lbl_sanc">0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Cobros de Partido:</span> <strong id="lbl_cob">0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Transferencias:</span> <strong id="lbl_transf">0,00</strong></li>
                            <li class="d-flex justify-content-between"><span>Impugnaciones Rechazadas:</span> <strong id="lbl_impug">0,00</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-top border-danger border-4">
                    <div class="card-body text-center p-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-3">Total Gastos</h6>
                        <h2 class="display-5 fw-bold text-danger mb-0" id="lbl_gastos">Bs. <span class="counter">0,00</span></h2>
                        <hr>
                        <p class="small text-muted mb-0">Pagos de cancha, arbitraje, y gastos administrativos asociados a este torneo.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card shadow border-0 h-100 bg-dark text-white rounded-4 overflow-hidden">
                    <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                        <h6 class="text-uppercase text-light fw-bold mb-3">Balance General (Ganancia)</h6>
                        <h1 class="display-4 fw-bold text-warning mb-0" id="lbl_balance">Bs. <span class="counter">0,00</span></h1>
                        <div class="mt-3">
                            <i class="fa-solid fa-scale-balanced fa-3x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0">Distribución de Ingresos</h6></div>
                    <div class="card-body d-flex justify-content-center">
                        <div style="max-width: 300px; width: 100%;">
                            <canvas id="chartIngresos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0">Proporción Ingresos vs Gastos</h6></div>
                    <div class="card-body d-flex justify-content-center">
                        <div style="max-width: 300px; width: 100%;">
                            <canvas id="chartBalance"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña 2: Gastos -->
    <div class="tab-pane fade" id="gastos" role="tabpanel" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-danger mb-0">Registro de Gastos</h5>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalGasto" onclick="nuevoGasto()">
                    <i class="fa-solid fa-plus me-1"></i> Registrar Gasto
                </button>
            </div>
            <div class="card-body">
                <table id="tablaGastos" class="table table-hover w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pestaña 3: Pagos Pendientes -->
    <div class="tab-pane fade" id="pendientes" role="tabpanel" tabindex="0">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 border-top border-warning border-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clone text-warning me-2"></i> Tarjetas por Cobrar</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover w-100 m-0" id="tablaPendientesTarjetas">
                            <thead class="table-light"><tr><th>Fecha/Partido</th><th>Jugador</th><th>Tarjeta</th><th>Monto</th><th>Acción</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 border-top border-warning border-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-futbol text-warning me-2"></i> Cobros de Partidos Pendientes</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover w-100 m-0" id="tablaPendientesPartidos">
                            <thead class="table-light"><tr><th>Partido</th><th>Equipo</th><th>Monto</th><th>Acción</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña 4: Historial de Torneos -->
    <div class="tab-pane fade" id="historial" role="tabpanel" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="tablaHistorial" class="table table-hover w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Torneo</th>
                            <th>Estado</th>
                            <th>Total Ingresos</th>
                            <th>Total Gastos</th>
                            <th class="text-success fw-bold">Recaudación / Ganancia</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Gasto -->
<div class="modal fade" id="modalGasto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formGasto">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Gasto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear_gasto">
                    <input type="hidden" id="gasto_torneo_id" name="torneo_id">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Monto (Bs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="monto" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select" name="categoria" required>
                            <option value="">-- Seleccione --</option>
                            <option value="cancha">Pago de Cancha</option>
                            <option value="arbitraje">Pago de Arbitraje</option>
                            <option value="administrativo">Gastos Administrativos / Otros</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="descripcion" rows="2" required placeholder="Detalle del gasto..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnGuardarGasto">Guardar Gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>const torneoContexto = <?= intval($torneo_id) ?>;</script>
<script src="js/finanzas.js?v=<?= time() ?>"></script>

<style>
.nav-tabs .nav-link { background-color: #f8fafc; border-bottom: 3px solid transparent; }
.nav-tabs .nav-link:hover { border-color: #cbd5e1; }
.nav-tabs .nav-link.active { background-color: white; border-bottom-color: #198754; color: #198754 !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
