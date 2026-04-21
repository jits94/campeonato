<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();
$stmtEquipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre ASC");
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-users text-info me-2"></i> Gestión de Jugadores</h2>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center">
            <label class="fw-bold me-2 mb-0 text-nowrap">Equipo:</label>
            <select class="form-select border-info" id="filtro_equipo_jugadores" style="min-width: 200px;">
                <option value="0">Todos los Equipos</option>
                <?php foreach($equipos as $eq): ?>
                    <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
        <button class="btn btn-info text-white shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalJugador" onclick="nuevoJugador()">
            <i class="fa-solid fa-user-plus me-1"></i> Nuevo Jugador
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaJugadores" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Jugador</th>
                        <th>CI / DNI</th>
                        <th>Dorsal</th>
                        <th>Equipo</th>
                        <th>Fecha Registro</th>
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

<!-- Modal Jugador -->
<div class="modal fade" id="modalJugador" tabindex="-1" aria-labelledby="modalJugadorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formJugador" enctype="multipart/form-data">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalJugadorLabel"><i class="fa-solid fa-user text-info me-2"></i> <span>Nuevo Jugador</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_jugador" name="id_jugador">
                    <input type="hidden" id="action" name="action" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-medium">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Lionel Messi">
                    </div>
                    
                    <div class="mb-3">
                        <label for="ci" class="form-label fw-medium">CI / DNI <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ci" name="ci" required placeholder="Nro de documento">
                    </div>

                    <div class="mb-3">
                        <label for="dorsal" class="form-label fw-medium">Nro de Camiseta (Dorsal)</label>
                        <input type="number" class="form-control" id="dorsal" name="dorsal" placeholder="Ej. 10">
                    </div>

                    <div class="mb-3">
                        <label for="equipo_id" class="form-label fw-medium">Equipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="equipo_id" name="equipo_id" required>
                            <option value="">-- Seleccione un equipo --</option>
                            <?php foreach($equipos as $eq): ?>
                                <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label fw-medium">Foto del Jugador</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                        <div class="form-text">Opcional. Formatos: JPG, PNG.</div>
                        <div id="vista-previa-foto" class="mt-2 text-center d-none">
                            <img src="" id="img-preview-foto" class="img-thumbnail rounded-circle" style="max-height: 100px; width: 100px; object-fit: cover;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Scripts Propios -->
<script>
    const userRole = '<?= $_SESSION['rol'] ?>';
</script>
<script src="js/jugadores.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
