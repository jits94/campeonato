<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Solo el admin puede gestionar los torneos de esta forma
if($_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>No tienes permisos para acceder a esta página.</div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calendar-days text-success me-2"></i> Gestión de Torneos</h2>
    <button class="btn btn-success shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#modalTorneo" onclick="nuevoTorneo()">
        <i class="fa-solid fa-plus me-1"></i> Crear Torneo
    </button>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light rounded border-start border-success border-4">
        <h5 class="card-title text-success fw-bold"><i class="fa-solid fa-circle-info"></i> Información</h5>
        <p class="card-text text-muted mb-0">Al crear un torneo, defina claramente el tipo. Posteriormente, vaya al módulo de Inscripciones para registrar a los equipos participantes.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaTorneos" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Torneo</th>
                        <th>Tipo de Competencia</th>
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

<!-- Modal Torneo -->
<div class="modal fade" id="modalTorneo" tabindex="-1" aria-labelledby="modalTorneoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formTorneo">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalTorneoLabel"><i class="fa-solid fa-trophy me-2"></i> <span>Nuevo Torneo</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_torneo" name="id_torneo">
                    <input type="hidden" id="action" name="action" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-medium">Nombre del Torneo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Campeonato Verano 2026">
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label fw-medium">Tipo de Competencia <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">-- Seleccione una opción --</option>
                            <option value="todos_contra_todos">Todos contra Todos (Liga Única)</option>
                            <option value="fase_grupos">Fase de Grupos + Eliminatorias</option>
                        </select>
                        <div class="form-text text-muted">Afecta cómo se generan los partidos y la tabla de posiciones (Liga vs Grupos).</div>
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label fw-medium">Estado <span class="text-danger">*</span></label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="activo">Activo (En curso)</option>
                            <option value="finalizado">Finalizado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardar">Guardar Torneo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Scripts Propios -->
<script src="js/torneos.js"></script>

<?php require_once 'includes/footer.php'; ?>
