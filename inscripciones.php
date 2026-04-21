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
$stmtTorneos = $conn->query("SELECT id, nombre, tipo FROM torneos WHERE estado = 'activo' ORDER BY id DESC");
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

$stmtEquipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre ASC");
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-signature text-secondary me-2"></i> Inscripciones de Equipos</h2>
</div>

<div class="card shadow-sm mb-4 border-top border-secondary border-4">
    <div class="card-body bg-light rounded">
        <div class="row align-items-center">
            <div class="col-md-8">
                <label for="filtro_torneo" class="form-label fw-bold">Seleccione un Torneo Activo:</label>
                <select class="form-select form-select-lg" id="filtro_torneo">
                    <option value="">-- Seleccione un Torneo --</option>
                    <?php foreach($torneos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= $t['tipo'] === 'todos_contra_todos' ? 'Liga' : 'Grupos' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 text-end mt-4 mt-md-0">
                <button class="btn btn-secondary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalInscripcion" onclick="nuevaInscripcion()" id="btnNuevaInscripcion" disabled>
                    <i class="fa-solid fa-plus-circle me-1"></i> Inscribir Equipo
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-secondary">Equipos Inscritos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaInscripciones" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Equipo</th>
                        <th>Monto Cobrado (Bs.)</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado por AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Inscripción -->
<div class="modal fade" id="modalInscripcion" tabindex="-1" aria-labelledby="modalInscripcionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formInscripcion">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold" id="modalInscripcionLabel"><i class="fa-solid fa-file-signature me-2"></i> <span>Inscribir Equipo</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_inscripcion" name="id_inscripcion">
                    <input type="hidden" id="action" name="action" value="crear">
                    <input type="hidden" id="torneo_modal_id" name="torneo_id">
                    
                    <div class="mb-3">
                        <label for="equipo_id" class="form-label fw-medium">Seleccionar Equipo(s) <span class="text-danger">*</span></label>
                        <select class="form-select" id="equipo_id" name="equipo_id[]" multiple style="height: 200px;" required>
                            <?php foreach($equipos as $eq): ?>
                                <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Mantenga presionado Ctrl (o Cmd en Mac) para seleccionar varios equipos.</div>
                    </div>

                    <div class="mb-3">
                        <label for="monto" class="form-label fw-medium">Monto de Inscripción (Bs.) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="monto" name="monto" required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label fw-medium">Estado <span class="text-danger">*</span></label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="borrador">Borrador (Pago Pendiente)</option>
                            <option value="registrado">Registrado y Pagado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary" id="btnGuardar">Guardar Inscripción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS & Scripts -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="js/inscripciones.js"></script>

<?php require_once 'includes/footer.php'; ?>
