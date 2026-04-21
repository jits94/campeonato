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
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-square-poll-vertical text-primary me-2"></i> Resultados de Partidos</h2>
    <a href="live.php" class="btn btn-danger shadow-sm timer-blink" target="_blank"><i class="fa-solid fa-tv me-1"></i> Marcador en Vivo</a>
</div>

<div class="card shadow-sm mb-4 border-top border-primary border-4">
    <div class="card-body bg-light rounded">
        <form id="formFiltros" class="row align-items-end">
            <div class="col-md-4 mb-3 mb-md-0">
                <label for="filtro_torneo" class="form-label fw-bold">Torneo:</label>
                <select class="form-select" id="filtro_torneo" name="torneo_id" required>
                    <option value="">-- Seleccione un Torneo --</option>
                    <?php foreach($torneos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="filtro_equipo" class="form-label fw-bold">Filtrar por Equipo:</label>
                <select class="form-select" id="filtro_equipo" name="equipo_id">
                    <option value="">-- Todos los equipos --</option>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="filtro_fecha" class="form-label fw-bold">Filtrar por Fecha:</label>
                <input type="date" class="form-control" id="filtro_fecha" name="fecha">
            </div>
            <div class="col-md-2 text-md-end text-center">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div id="contenedorResultados">
    <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-futbol fa-3x mb-3"></i>
        <h4>Seleccione un torneo para ver los resultados</h4>
    </div>
</div>

<template id="templateGrupo">
    <div class="mb-5 grupo-seccion">
        <div class="d-flex align-items-center mb-3">
            <h4 class="fw-bold text-dark mb-0 bg-white px-3 py-2 rounded shadow-sm border-start border-primary border-4">
                <i class="fa-solid fa-layer-group text-primary me-2"></i> <span class="nombre-grupo"></span>
            </h4>
            <div class="flex-grow-1 border-bottom ms-3" style="border-style: dashed !important; opacity: 0.3;"></div>
        </div>
        <div class="contenedor-fechas"></div>
    </div>
</template>

<template id="templateFecha">
    <div class="mb-4">
        <h6 class="text-secondary fw-bold text-uppercase mb-3"><i class="fa-regular fa-calendar-check me-1"></i> <span class="texto-fecha"></span></h6>
        <div class="row row-cols-1 row-cols-md-3 g-3 contenedor-partidos"></div>
    </div>
</template>

<template id="templatePartido">
    <div class="col">
        <div class="card h-100 shadow-sm border-0 match-card hover-lift overflow-hidden" style="cursor: pointer;">
            <div class="card-header bg-white border-0 py-2 d-flex justify-content-between align-items-center">
                <span class="badge bg-light text-dark border fase-nombre small"></span>
                <span class="small text-muted hora-partido"></span>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="row align-items-center text-center">
                    <div class="col-4">
                        <img src="" class="local-logo rounded-circle shadow-sm border mb-2" style="width: 55px; height: 55px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                        <div class="local-nombre fw-bold small"></div>
                        <div class="container-ganador-local"></div>
                    </div>
                    <div class="col-4">
                        <div class="marcador-container bg-dark text-white rounded-3 py-2 px-1 shadow">
                            <h3 class="marcador mb-0 fw-bold"></h3>
                        </div>
                        <div class="status-indicator mt-2">
                            <span class="badge status-badge p-1 px-2" style="font-size: 0.65rem;"></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <img src="" class="visitante-logo rounded-circle shadow-sm border mb-2" style="width: 55px; height: 55px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                        <div class="visitante-nombre fw-bold small"></div>
                        <div class="container-ganador-visitante"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Modal Detalle de Partido -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content overflow-hidden border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 bg-light text-dark p-4 d-block text-center position-relative">
                <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <h6 class="text-uppercase text-muted fw-bold mb-3 ls-1" id="det_torneo_fase"></h6>
                
                <div class="row align-items-center justify-content-center">
                    <div class="col-4 col-md-3">
                        <img id="det_local_logo" src="" class="img-fluid rounded-circle bg-white p-1 border mb-2 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                        <h5 class="fw-bold mb-0" id="det_local_nombre"></h5>
                    </div>
                    
                    <div class="col-4">
                        <div class="marcador-container-modal text-white">
                            <h1 class="display-4 fw-bold mb-0" id="det_marcador" style="font-family: 'Inter', sans-serif;"></h1>
                        </div>
                        <div id="det_estado" class="mt-2"></div>
                    </div>
                    
                    <div class="col-4 col-md-3">
                        <img id="det_visitante_logo" src="" class="img-fluid rounded-circle bg-white p-1 border mb-2 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                        <h5 class="fw-bold mb-0" id="det_visitante_nombre"></h5>
                    </div>
                </div>
            </div>
            
            <div class="modal-body p-4 bg-white">
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <div id="det_impugnacion" class="mb-4 d-none"></div>
                        
                        <h5 class="fw-bold mb-4 border-bottom pb-2">
                            <i class="fa-solid fa-timeline text-primary me-2"></i> Cronología del Encuentro
                        </h5>
                        <div id="det_timeline" class="timeline-container">
                            <!-- Los eventos se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-0 py-3 d-flex justify-content-between">
                <div class="text-muted small">
                    <i class="fa-solid fa-calendar me-1"></i> <span id="det_fecha"></span> <span class="mx-1">|</span> <i class="fa-solid fa-clock me-1"></i> <span id="det_hora"></span>
                </div>
                <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap');

@media (max-width: 576px) {
    #det_marcador { font-size: 2rem; }
    #det_local_logo, #det_visitante_logo { width: 50px !important; height: 50px !important; }
    #det_local_nombre, #det_visitante_nombre { font-size: 0.9rem; }
    .marcador-container-modal { padding: 0.5rem !important; }
    .display-4 { font-size: 2.2rem; }
}

.ls-1 { letter-spacing: 1px; }
.hover-lift { transition: all 0.3s cubic-bezier(.25,.8,.25,1); }
.hover-lift:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.12) !important; }
.match-card { border-radius: 16px; border: 1px solid rgba(0,0,0,0.05) !important; }
.marcador-container { background: linear-gradient(135deg, #1a1a1a 0%, #3e3e3e 100%); width: 100%; max-width: 90px; margin: 0 auto; }
.marcador-container-modal { background: #212529; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.marcador { font-family: 'Inter', sans-serif; letter-spacing: 1px; }

/* Timeline Estilizada */
.timeline-container { position: relative; padding-left: 20px; }
.timeline-container::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #e9ecef; border-radius: 3px; }

.timeline-item { position: relative; padding-bottom: 25px; }
.timeline-item::before { content: ''; position: absolute; left: -24px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #fff; border: 3px solid #0d6efd; z-index: 1; }
.timeline-item.gol::before { border-color: #212529; background: #212529; }
.timeline-item.amarilla::before { border-color: #ffc107; background: #ffc107; }
.timeline-item.roja::before { border-color: #dc3545; background: #dc3545; }

.timeline-min { font-weight: 800; font-size: 0.85rem; color: #6c757d; margin-right: 15px; display: inline-block; width: 35px; }
.timeline-icon { margin-right: 10px; width: 20px; text-align: center; }

.timer-blink { animation: blinker 1s linear infinite; }
@keyframes blinker { 50% { opacity: 0; } }

.badge.status-badge { font-weight: 700; border-radius: 4px; }
</style>

<script src="js/resultados.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
