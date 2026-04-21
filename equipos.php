<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-shield-halved text-primary me-2"></i> Gestión de Equipos</h2>
    <?php if($_SESSION['rol'] === 'administrador'): ?>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEquipo" onclick="nuevoEquipo()">
        <i class="fa-solid fa-plus me-1"></i> Nuevo Equipo
    </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaEquipos" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Nombre del Equipo</th>
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

<!-- Modal Equipo -->
<div class="modal fade" id="modalEquipo" tabindex="-1" aria-labelledby="modalEquipoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEquipo" enctype="multipart/form-data">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEquipoLabel"><i class="fa-solid fa-shield-halved text-primary me-2"></i> <span>Nuevo Equipo</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_equipo" name="id_equipo">
                    <input type="hidden" id="action" name="action" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-medium">Nombre del Equipo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Real Madrid FC">
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label fw-medium">Logo del Equipo <small class="text-muted">(Opcional)</small></label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <div class="form-text">Formatos válidos: JPG, PNG, GIF. Máximo 2MB.</div>
                        <div id="vista-previa" class="mt-2 text-center d-none">
                            <img src="" id="img-preview" class="img-thumbnail" style="max-height: 100px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
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
<script src="js/equipos.js"></script>

<?php require_once 'includes/footer.php'; ?>
