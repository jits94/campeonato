<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmtTorneos = $conn->query("SELECT id, nombre, tipo FROM torneos ORDER BY id DESC");
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

$is_admin = ($_SESSION['rol'] === 'administrador');

if (!$is_admin) {
    header('Location: resultados.php');
    exit;
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-futbol text-danger me-2"></i> Programación de Partidos</h2>
    <?php if($is_admin): ?>
    <div>
        <button class="btn btn-outline-success shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalAvanzarFase" id="btnAvanzarFase" disabled>
            <i class="fa-solid fa-forward-step me-1"></i> Avanzar Siguiente Ronda
        </button>
        <button class="btn btn-outline-dark shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalEliminatoria" id="btnProgramacionEliminatoria" disabled>
            <i class="fa-solid fa-sitemap me-1"></i> Generar Fase Eliminatoria
        </button>
        <button class="btn btn-outline-danger shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalMasivo" id="btnProgramacionMasiva" disabled>
            <i class="fa-solid fa-layer-group me-1"></i> Programar Fecha Completa
        </button>
        <button class="btn btn-danger shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#modalPartido" onclick="nuevoPartido()" id="btnNuevoPartido" disabled>
            <i class="fa-solid fa-calendar-plus me-1"></i> Programar Partido
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4 border-top border-danger border-4">
    <div class="card-body bg-light rounded">
        <div class="row align-items-center">
            <div class="col-md-5 mb-3 mb-md-0">
                <label for="filtro_torneo" class="form-label fw-bold">Torneo:</label>
                <select class="form-select" id="filtro_torneo">
                    <option value="">-- Seleccione un Torneo --</option>
                    <?php foreach($torneos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="filtro_fecha" class="form-label fw-bold">Fecha (Opcional):</label>
                <input type="date" class="form-control" id="filtro_fecha">
            </div>
            <div class="col-md-4 mt-4 text-md-end text-center d-flex justify-content-md-end align-items-center">
                <button class="btn btn-outline-secondary me-2" id="btnFiltrar"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="live.php" class="btn btn-primary" target="_blank"><i class="fa-solid fa-tv me-1"></i> Pantalla en Vivo</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaPartidos" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha / Hora</th>
                        <th>Grupo</th>
                        <th>Fase</th>
                        <th class="text-end">Local</th>
                        <th class="text-center">Resultado</th>
                        <th>Visitante</th>
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

<?php if($is_admin): ?>
<!-- Modal Programar Partido -->
<div class="modal fade" id="modalPartido" tabindex="-1" aria-labelledby="modalPartidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formPartido">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="modalPartidoLabel"><i class="fa-solid fa-futbol me-2"></i> <span>Programar Partido</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="action" name="action" value="crear">
                    <input type="hidden" id="id_partido" name="id_partido">
                    <input type="hidden" id="partido_torneo_id" name="torneo_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label fw-medium">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hora" class="form-label fw-medium">Hora <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="hora" name="hora" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fase" class="form-label fw-medium">Fase / Jornada <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fase" name="fase" required placeholder="Ej. Fecha 1, Semifinal, Grupo A">
                    </div>

                    <div id="wrapper_grupo" class="mb-3 d-none">
                        <label for="id_grupo_filtro" class="form-label fw-medium">Filtrar por Grupo <small class="text-muted">(Solo equipos de este grupo podrán jugar entre sí)</small></label>
                        <select class="form-select border-info" id="id_grupo_filtro">
                            <option value="">-- Seleccione un Grupo --</option>
                        </select>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-md-5">
                            <label for="equipo_local_id" class="form-label fw-medium">Equipo Local <span class="text-danger">*</span></label>
                            <select class="form-select" id="equipo_local_id" name="equipo_local_id" required>
                                <!-- Cargado dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-2 text-center">
                            <h4 class="text-muted fw-bold mt-4">VS</h4>
                        </div>
                        <div class="col-md-5">
                            <label for="equipo_visitante_id" class="form-label fw-medium">Equipo Visitante <span class="text-danger">*</span></label>
                            <select class="form-select" id="equipo_visitante_id" name="equipo_visitante_id" required>
                                <!-- Cargado dinámicamente -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnGuardar">Guardar Partido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Programación Masiva -->
<div class="modal fade" id="modalMasivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-layer-group me-2"></i> Programación Masiva de Fecha</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4 bg-light p-3 rounded border">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Jornada / Fecha</label>
                        <input type="number" class="form-control" id="masivo_fecha_num" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Fecha de inicio</label>
                        <input type="date" class="form-control" id="masivo_fecha_inicio">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Hora primer partido</label>
                        <input type="time" class="form-control" id="masivo_hora_inicio">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Intervalo (min)</label>
                        <input type="number" class="form-control" id="masivo_intervalo" value="60" step="5">
                    </div>
                    <div class="col-12 mt-3 text-center">
                        <button class="btn btn-primary px-4 shadow-sm" id="btnGenerarPreview">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generar Vista Previa
                        </button>
                    </div>
                </div>

                <div id="contenedor_preview_masivo" class="d-none">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-primary"><i class="fa-solid fa-list-check me-2"></i> Vista Previa de Emparejamientos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle border" id="tablaPreviewMasivo">
                            <thead class="table-dark">
                                <tr>
                                    <th>Grupo</th>
                                    <th class="text-end">Local</th>
                                    <th class="text-center">VS</th>
                                    <th>Visitante</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Llenado dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info py-2 small shadow-sm border-0 bg-opacity-10 bg-info">
                        <i class="fa-solid fa-circle-info me-1"></i> Puedes ajustar la fecha y hora de cada partido individualmente. Solo se registrarán las filas visibles.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 d-none shadow-sm" id="btnConfirmarMasivo">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Todos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Programación Fase Eliminatoria -->
<div class="modal fade" id="modalEliminatoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-sitemap me-2"></i> Generar Fase Eliminatoria</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary small shadow-sm border-0">
                    <i class="fa-solid fa-circle-info me-1"></i> Se generará una tabla de clasificación global con todos los equipos del torneo evaluando Puntos, Diferencia de Goles y Goles a Favor. Luego, se crearán las llaves cruzando al 1ro con el último (1 vs N, 2 vs N-1, etc.).
                </div>
                <div class="row mb-4 bg-light p-3 rounded border align-items-end">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Fase a Generar</label>
                        <select class="form-select" id="eliminatoria_fase">
                            <option value="16">Octavos de Final (Top 16 equipos)</option>
                            <option value="8">Cuartos de Final (Top 8 equipos)</option>
                            <option value="4">Semifinal (Top 4 equipos)</option>
                            <option value="2">Final (Top 2 equipos)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Fecha de inicio</label>
                        <input type="date" class="form-control" id="eliminatoria_fecha_inicio">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Hora primer partido</label>
                        <input type="time" class="form-control" id="eliminatoria_hora_inicio">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Intervalo (min)</label>
                        <input type="number" class="form-control" id="eliminatoria_intervalo" value="60" step="5">
                    </div>
                    <div class="col-12 mt-4 text-center">
                        <button class="btn btn-dark px-4 shadow-sm" id="btnGenerarPreviewEliminatoria">
                            <i class="fa-solid fa-magnifying-glass-chart me-1"></i> Calcular y Generar Llaves
                        </button>
                    </div>
                </div>

                <div id="contenedor_preview_eliminatoria" class="d-none">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-primary"><i class="fa-solid fa-list-check me-2"></i> Vista Previa de Llaves Generadas</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle border" id="tablaPreviewEliminatoria">
                            <thead class="table-dark">
                                <tr>
                                    <th>Llave</th>
                                    <th class="text-end">Equipo Local (Mejor posicionado)</th>
                                    <th class="text-center">VS</th>
                                    <th>Equipo Visitante (Peor posicionado)</th>
                                    <th>Fecha y Hora</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Llenado dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 d-none shadow-sm" id="btnConfirmarEliminatoria">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Llaves
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Avanzar Fase Eliminatoria -->
<div class="modal fade" id="modalAvanzarFase" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-forward-step me-2"></i> Avanzar a Siguiente Ronda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary small shadow-sm border-0">
                    <i class="fa-solid fa-circle-info me-1"></i> El sistema evaluará los ganadores de la fase de origen elegida (tomando en cuenta el marcador global e ignorando empates, recurriendo a penales en el juego de vuelta) y emparejará automáticamente la siguiente ronda.
                </div>
                <div class="row mb-4 bg-light p-3 rounded border align-items-end">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Fase de Origen Terminada</label>
                        <select class="form-select border-success shadow-sm" id="avanzar_fase_origen">
                            <option value="Octavos de Final">Octavos de Final</option>
                            <option value="Cuartos de Final">Cuartos de Final</option>
                            <option value="Semifinal">Semifinal (Pasa a Final)</option>
                            
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Fecha de inicio</label>
                        <input type="date" class="form-control" id="avanzar_fecha_inicio">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Hora</label>
                        <input type="time" class="form-control" id="avanzar_hora_inicio">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label fw-bold small text-muted">Intervalo (m)</label>
                        <input type="number" class="form-control" id="avanzar_intervalo" value="60" step="5">
                    </div>
                    <div class="col-12 mt-4 text-center">
                        <button class="btn btn-success px-4 shadow-sm" id="btnGenerarPreviewAvanzar">
                            <i class="fa-solid fa-magnifying-glass-chart me-1"></i> Calcular Ganadores y Mostrar Llaves
                        </button>
                    </div>
                </div>

                <div id="contenedor_preview_avanzar" class="d-none">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-success"><i class="fa-solid fa-list-check me-2"></i> Llaves de la Siguiente Ronda</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle border" id="tablaPreviewAvanzar">
                            <thead class="table-success">
                                <tr>
                                    <th>Llave</th>
                                    <th class="text-end">Equipo Local (Clasificado L)</th>
                                    <th class="text-center">VS</th>
                                    <th>Equipo Visitante (Clasificado R)</th>
                                    <th>Fecha y Hora</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Llenado por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 d-none shadow-sm" id="btnConfirmarAvanzar">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Ronda
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- DataTables JS & Scripts -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>const userRole = '<?= $_SESSION['rol'] ?>';</script>
<script src="js/partidos.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
