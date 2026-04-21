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
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

$stmtEquipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre ASC");
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

// Jugadores con su equipo actual para el select
$stmtJugadores = $conn->query("SELECT j.id, j.nombre, j.equipo_id, e.nombre as equipo_nombre FROM jugadores j INNER JOIN equipos e ON j.equipo_id = e.id ORDER BY j.nombre ASC");
$jugadores = $stmtJugadores->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .select2-container { width: 100% !important; }
    .select2-selection { height: 38px !important; border: 1px solid #dee2e6 !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-right-left text-primary me-2"></i> Transferencias de Jugadores</h2>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransferencia" onclick="nuevaTransferencia()">
        <i class="fa-solid fa-exchange-alt me-1"></i> Nueva Transferencia
    </button>
</div>

<div class="card shadow-sm mb-4 border-top border-primary border-4">
    <div class="card-body bg-light rounded">
        <h5 class="card-title text-primary fw-bold"><i class="fa-solid fa-circle-info"></i> Historial</h5>
        <p class="card-text text-muted mb-0">Listado histórico de las transferencias realizadas de jugadores entre equipos, y sus respectivos cobros.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaTransferencias" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Jugador</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Monto (Bs.)</th>
                        <th>Torneo Asociado</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado por AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Transferencia -->
<div class="modal fade" id="modalTransferencia" tabindex="-1" aria-labelledby="modalTransferenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formTransferencia">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalTransferenciaLabel"><i class="fa-solid fa-exchange-alt me-2"></i> Registrar Transferencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="action" name="action" value="crear">
                    
                    <div class="mb-3">
                        <label for="jugador_id" class="form-label fw-medium">Seleccionar Jugador <span class="text-danger">*</span></label>
                        <select class="form-select" id="jugador_id" name="jugador_id" required>
                            <option value="">-- Buscar Jugador --</option>
                            <?php foreach($jugadores as $j): ?>
                                <option value="<?= $j['id'] ?>" data-equipo="<?= $j['equipo_id'] ?>" data-nombre-equipo="<?= htmlspecialchars($j['equipo_nombre']) ?>">
                                    <?= htmlspecialchars($j['nombre']) ?> (Actualmente en: <?= htmlspecialchars($j['equipo_nombre']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Equipo Origen</label>
                        <input type="text" class="form-control" id="equipo_origen_nombre" readonly placeholder="Se autocompleta al elegir jugador">
                        <input type="hidden" id="equipo_origen_id" name="equipo_origen_id">
                    </div>

                    <div class="mb-3">
                        <label for="equipo_destino_id" class="form-label fw-medium">Equipo Destino <span class="text-danger">*</span></label>
                        <select class="form-select" id="equipo_destino_id" name="equipo_destino_id" required>
                            <option value="">-- Seleccione Nuevo Equipo --</option>
                            <?php foreach($equipos as $eq): ?>
                                <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="monto" class="form-label fw-medium">Monto a Cobrar (Bs.) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="monto" name="monto" required placeholder="0.00" value="50.00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="torneo_id" class="form-label fw-medium">Torneo Asociado <small class="text-muted">(Opcional)</small></label>
                        <select class="form-select" id="torneo_id" name="torneo_id">
                            <option value="">-- Ninguno / General --</option>
                            <?php foreach($torneos as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si la transferencia se hace en el marco de un torneo activo, seleccionalo para reflejarlo en las finanzas del torneo.</div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar Transferencia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/transferencias.js"></script>

<?php require_once 'includes/footer.php'; ?>
