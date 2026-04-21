<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmtTorneos = $conn->query("SELECT id, nombre, tipo FROM torneos ORDER BY id DESC");
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

$torneo_id = $_GET['torneo_id'] ?? ($torneos[0]['id'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-ranking-star text-warning me-2"></i> Tabla de Posiciones</h2>
    
    <div class="d-flex align-items-center">
        <label class="fw-bold me-2 mb-0">Seleccionar Torneo:</label>
        <select class="form-select border-warning shadow-sm" id="filtro_torneo_pos" style="min-width: 250px;">
            <option value="">-- Seleccione un Torneo --</option>
            <?php foreach($torneos as $t): ?>
                <option value="<?= $t['id'] ?>" data-tipo="<?= $t['tipo'] ?>" <?= ($t['id'] == $torneo_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div id="loader-posiciones" class="text-center py-5 d-none">
    <i class="fa-solid fa-spinner fa-spin fa-3x text-warning mb-3"></i>
    <h5>Calculando clasificaciones...</h5>
</div>

<ul class="nav nav-tabs nav-fill mb-4 bg-white shadow-sm rounded-top" id="posicionesTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold text-dark py-3" id="tab-tablas" data-bs-toggle="tab" data-bs-target="#panel-tablas" type="button" role="tab" aria-controls="panel-tablas" aria-selected="true">
        <i class="fa-solid fa-table-list text-warning me-2"></i> Fase Regular (Grupos / General)
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold text-dark py-3" id="tab-llaves" data-bs-toggle="tab" data-bs-target="#panel-llaves" type="button" role="tab" aria-controls="panel-llaves" aria-selected="false">
        <i class="fa-solid fa-sitemap text-success me-2"></i> Fase Eliminatoria (Llaves)
    </button>
  </li>
</ul>

<div class="tab-content" id="posicionesTabsContent">
    <div class="tab-pane fade show active" id="panel-tablas" role="tabpanel" aria-labelledby="tab-tablas">
        <div id="contenedor-posiciones">
            <!-- Se llenará vía AJAX con una tabla única o múltiples por grupo -->
        </div>
    </div>
    <div class="tab-pane fade" id="panel-llaves" role="tabpanel" aria-labelledby="tab-llaves">
        <div id="contenedor-llaves" class="p-4 bg-light rounded border border-top-0 d-flex gap-4" style="overflow-x: auto; min-height: 400px; padding-bottom: 2rem;">
            <!-- Se llenará vía AJAX con las llaves eliminatorias -->
        </div>
    </div>
</div>

<div id="empty-state" class="text-center py-5 d-none">
    <i class="fa-solid fa-folder-open fa-4x text-muted opacity-25 mb-3"></i>
    <h4 class="text-muted">Seleccione un torneo para ver las posiciones</h4>
</div>

<!-- Template para las tablas (se usará en JS) -->
<template id="tpl-tabla">
    <div class="card shadow-sm mb-4 border-top border-warning border-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-dark mb-0 titulo-tabla">Clasificación General</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 50px;">Pos</th>
                            <th class="text-start">Equipo</th>
                            <th title="Puntos" class="fw-bold text-dark">Pts</th>
                            <th title="Partidos Jugados">PJ</th>
                            <th title="Partidos Ganados">PG</th>
                            <th title="Partidos Empatados">PE</th>
                            <th title="Partidos Perdidos">PP</th>
                            <th title="Goles a Favor">GF</th>
                            <th title="Goles en Contra">GC</th>
                            <th title="Diferencia de Goles">DG</th>
                        </tr>
                    </thead>
                    <tbody class="cuerpo-tabla text-center">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<!-- Modal Historial de Equipo -->
<div class="modal fade" id="modalHistorialEquipo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Historial de Partidos: <span id="historial-equipo-nombre"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light" id="lista-historial-partidos" style="max-height: 70vh; overflow-y: auto;">
                <!-- Se llena vía JS con cards -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>const torneoContexto = <?= intval($torneo_id) ?>;</script>
<script src="js/posiciones.js"></script>

<style>
.table-hover tbody tr:hover { background-color: #fffbeb !important; }
.trophy-icon { color: #fbbf24; font-size: 1.2rem; margin-right: 5px; }
.pos-circle { width: 30px; height: 30px; line-height: 30px; border-radius: 50%; display: inline-block; background: #e2e8f0; font-weight: bold; }
.pos-1 { background: #fbbf24; color: #fff; box-shadow: 0 0 10px rgba(251, 191, 36, 0.5); }
.pos-2 { background: #94a3b8; color: #fff; }
.pos-3 { background: #b45309; color: #fff; }
.btn-link-team { color: #2563eb; cursor: pointer; transition: all 0.2s; }
.btn-link-team:hover { color: #1e40af; text-decoration: underline; }
.bg-primary-subtle { background-color: #dbeafe !important; }
.bg-success-subtle { background-color: #dcfce7 !important; }
.bg-warning-subtle { background-color: #fef9c3 !important; }
.bg-danger-subtle { background-color: #fee2e2 !important; }
.text-success { color: #16a34a !important; }
.text-warning { color: #d97706 !important; }
.text-danger { color: #dc2626 !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
