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
// Solo torneos que sean fase_grupos
$stmtTorneos = $conn->query("SELECT id, nombre FROM torneos WHERE estado = 'activo' AND tipo = 'fase_grupos' ORDER BY id DESC");
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-layer-group text-warning me-2"></i> Sorteo de Grupos</h2>
</div>

<div class="card shadow-sm mb-4 border-top border-warning border-4">
    <div class="card-body bg-light rounded">
        <div class="row align-items-center">
            <div class="col-md-8">
                <label for="filtro_torneo" class="form-label fw-bold">Seleccione un Torneo (Fase de Grupos):</label>
                <select class="form-select form-select-lg" id="filtro_torneo">
                    <option value="">-- Seleccione un Torneo --</option>
                    <?php foreach($torneos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div id="panel-sortear" class="d-none mb-4">
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i> Generar Sorteo Automático</h4>
            <p class="text-muted">No se han generado grupos para este torneo. Defina la cantidad de grupos y proceda con el sorteo aleatorio de los equipos inscritos.</p>
            
            <div class="row justify-content-center mt-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white">Cantidad de Grupos</span>
                        <input type="number" class="form-control" id="num_grupos" min="2" max="16" value="2">
                        <button class="btn btn-warning fw-bold text-dark w-100 mt-3 rounded" id="btnSortear">
                            ¡Realizar Sorteo! <i class="fa-solid fa-dice ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-secondary small" id="info-equipos"></div>
        </div>
    </div>
</div>

<div id="panel-resultados" class="d-none">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-secondary mb-0"><i class="fa-solid fa-sitemap me-2"></i> Grupos Conformados</h4>
        <button class="btn btn-sm btn-outline-danger" id="btnBorrarSorteo" title="Eliminar este sorteo y rehacer">
            <i class="fa-solid fa-rotate-left me-1"></i> Rehacer Sorteo
        </button>
    </div>
    <div class="row" id="contenedor-grupos">
        <!-- Generado por AJAX -->
    </div>
</div>

<div id="panel-vacio" class="text-center p-5 d-none">
    <i class="fa-solid fa-hand-pointer fa-4x text-muted mb-3 opacity-25"></i>
    <h4 class="text-muted">Seleccione un torneo para continuar</h4>
</div>

<!-- Scripts Propios -->
<script src="js/grupos.js"></script>

<?php require_once 'includes/footer.php'; ?>
