<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if ($_SESSION['rol'] !== 'administrador') {
    header('Location: index.php');
    exit;
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-gavel text-danger me-2"></i> Gestión de Impugnaciones</h2>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light rounded">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Filtrar por Estado</label>
                <select class="form-select border-0 shadow-sm" id="filtro_estado">
                    <option value="">-- Todos los Estados --</option>
                    <option value="pendiente" selected>Pendientes</option>
                    <option value="aceptada">Aceptadas</option>
                    <option value="rechazada">Rechazadas</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaImpugnaciones" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha Registro</th>
                        <th>Partido</th>
                        <th>Equipo Denunciante</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Resolver Impugnación -->
<div class="modal fade" id="modalResolver" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="formResolver">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-scale-balanced me-2"></i> Resolver Impugnación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="resolver">
                    <input type="hidden" name="id" id="resolver_id">

                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <div class="row">
                            <div class="col-md-6"><strong>Partido:</strong> <span id="info_partido"></span></div>
                            <div class="col-md-6"><strong>Denunciante:</strong> <span id="info_equipo"></span></div>
                            <div class="col-12 mt-2"><strong>Observaciones del Partido:</strong> <p id="info_obs_partido" class="mb-2 text-primary small fw-bold"></p></div>
                            <div class="col-12 mt-1"><strong>Motivo de Impugnación:</strong> <p id="info_motivo" class="mb-0 italic small"></p></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Decisión Final</label>
                        <div class="d-flex gap-3">
                            <input type="radio" class="btn-check" name="estado" id="res_aceptada" value="aceptada" required>
                            <label class="btn btn-outline-success flex-fill p-3" for="res_aceptada">
                                <i class="fa-solid fa-check-circle d-block fs-4 mb-2"></i> ACEPTAR IMPUGNACIÓN
                            </label>

                            <input type="radio" class="btn-check" name="estado" id="res_rechazada" value="rechazada" required>
                            <label class="btn btn-outline-danger flex-fill p-3" for="res_rechazada">
                                <i class="fa-solid fa-times-circle d-block fs-4 mb-2"></i> RECHAZAR DENUNCIA
                            </label>
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <label class="form-label fw-bold">Resolución Base / Justificación</label>
                        <textarea class="form-control" name="resolucion" rows="3" placeholder="Detalle la base de la decisión para aceptar o rechazar la denuncia..." required></textarea>
                    </div>

                    <div id="panel_aceptada" class="d-none animate__animated animate__fadeIn">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Castigo de Puntos (al oponente)</label>
                                <input type="number" class="form-control" name="puntos_castigo" value="0" min="0" max="3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sanción Económica (al oponente)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs.</span>
                                    <input type="number" class="form-control" name="monto_castigo" value="0.00" step="0.50">
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="check_castigo_jugador" onchange="togglePanelJugador(this.checked)">
                                <label class="form-check-label fw-bold text-dark" for="check_castigo_jugador">Sancionar a un Jugador Específico</label>
                            </div>
                            
                            <div id="wrapper_castigo_jugador" class="d-none bg-light p-3 rounded border">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Jugador a Sancionar:</label>
                                    <select class="form-select" name="jugador_castigo_id" id="sel_jugador_castigo">
                                        <option value="">-- Seleccione un Jugador --</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-bold small">Detalle del Castigo/Sanción:</label>
                                    <textarea class="form-control" name="jugador_castigo_detalle" rows="2" placeholder="Ej: Suspensión de 2 partidos, amonestación formal, etc."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="panel_rechazada" class="d-none animate__animated animate__fadeIn">
                        <div class="alert alert-warning border-0">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Se cobrará una multa al equipo denunciante por impugnación infundada.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Monto de Multa por Rechazo</label>
                            <div class="input-group">
                                <span class="input-group-text">Bs.</span>
                                <input type="number" class="form-control" name="monto_rechazo" value="50.00" step="0.50">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnGuardarResolucion">Guardar Resolución</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="js/impugnaciones.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
