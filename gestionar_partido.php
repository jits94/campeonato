<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

$id_partido = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

// Obtener detalles del partido
$stmt = $conn->prepare("SELECT p.*, 
                               l.nombre as local_nombre, l.logo as local_logo, 
                               v.nombre as visitante_nombre, v.logo as visitante_logo,
                               t.nombre as torneo_nombre
                        FROM partidos p 
                        INNER JOIN equipos l ON p.equipo_local_id = l.id
                        INNER JOIN equipos v ON p.equipo_visitante_id = v.id
                        INNER JOIN torneos t ON p.torneo_id = t.id
                        WHERE p.id = :id");
$stmt->bindParam(":id", $id_partido);
$stmt->execute();

$partido = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$partido) {
    echo "<div class='alert alert-danger'>Partido no encontrado.</div>";
    require_once 'includes/footer.php';
    exit;
}

$is_admin = ($_SESSION['rol'] === 'administrador');
$en_juego = ($partido['estado'] === 'en_juego');

// Obtener jugadores locales
$stmtL = $conn->prepare("SELECT id, nombre, dorsal FROM jugadores WHERE equipo_id = :eq_id ORDER BY nombre ASC");
$stmtL->bindParam(":eq_id", $partido['equipo_local_id']);
$stmtL->execute();
$jugadoresL = $stmtL->fetchAll(PDO::FETCH_ASSOC);

// Obtener jugadores visitantes
$stmtV = $conn->prepare("SELECT id, nombre, dorsal FROM jugadores WHERE equipo_id = :eq_id ORDER BY nombre ASC");
$stmtV->bindParam(":eq_id", $partido['equipo_visitante_id']);
$stmtV->execute();
$jugadoresV = $stmtV->fetchAll(PDO::FETCH_ASSOC);
// Si es partido de vuelta, obtener el resultado de la ida
$partido_ida = null;
if ($partido['llave'] && $partido['es_ida'] == 0) {
    $stmtIda = $conn->prepare("SELECT goles_local, goles_visitante, equipo_local_id FROM partidos WHERE llave = :llave AND es_ida = 1 AND torneo_id = :tid LIMIT 1");
    $stmtIda->execute([':llave' => $partido['llave'], ':tid' => $partido['torneo_id']]);
    $partido_ida = $stmtIda->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clipboard-list text-danger me-2"></i> Gestión de Partido</h2>
    <a href="javascript:void(0)" onclick="window.history.back();" class="btn btn-outline-secondary shadow-sm"><i class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<!-- Scoreboard -->
<div class="card shadow border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden">
    <div class="card-header bg-black text-center border-0 py-3">
        <h6 class="mb-0 text-uppercase fw-bold text-secondary">
            <?= htmlspecialchars($partido['torneo_nombre']) ?> - <?= htmlspecialchars($partido['fase']) ?> <span class="mx-2">|</span> <?= date('d/m/Y', strtotime($partido['fecha'])) ?> <?= date('H:i', strtotime($partido['hora'])) ?>
        </h6>
        <?php if($partido['estado'] == 'en_juego'): ?>
            <span class="badge bg-danger mt-2 timer-blink"><i class="fa-solid fa-circle text-white" style="font-size:8px;"></i> EN JUEGO</span>
        <?php elseif($partido['estado'] == 'finalizado'): ?>
            <span class="badge bg-secondary mt-2">FINALIZADO</span>
        <?php elseif($partido['estado'] == 'walkover'): ?>
            <span class="badge bg-warning text-dark mt-2">WALKOVER</span>
        <?php else: ?>
            <span class="badge bg-light text-dark mt-2">PROGRAMADO</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-4 p-md-5">
        <div class="row align-items-center justify-content-center">
            <!-- Local -->
            <div class="col-4 col-md-3 text-center">
                <img src="uploads/logos/<?= $partido['local_logo'] ?>" class="img-fluid rounded-circle bg-white p-1 mb-3 shadow" style="width: 120px; height: 120px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png';">
                <h4 class="fw-bold mb-0"><?= htmlspecialchars($partido['local_nombre']) ?></h4>
            </div>
            
            <!-- Marcador -->
            <div class="col-4 col-md-4 text-center">
                <div class="display-1 fw-bold bg-black rounded p-3 text-warning shadow-lg" style="font-family: monospace;">
                    <span id="score-local"><?= $partido['goles_local'] ?></span>
                    <span class="text-secondary mx-2">-</span>
                    <span id="score-visitante"><?= $partido['goles_visitante'] ?></span>
                </div>

                <?php if ($partido_ida): ?>
                <div class="mt-4 animate__animated animate__fadeInUp">
                    <?php 
                        // El local de la vuelta es el visitante de la ida
                        $goles_ida_este_equipo = ($partido['equipo_local_id'] == $partido_ida['equipo_local_id']) ? $partido_ida['goles_local'] : $partido_ida['goles_visitante'];
                        $goles_ida_rival = ($partido['equipo_local_id'] == $partido_ida['equipo_local_id']) ? $partido_ida['goles_visitante'] : $partido_ida['goles_local'];
                        
                        $global_local = intval($partido['goles_local']) + $goles_ida_este_equipo;
                        $global_visitante = intval($partido['goles_visitante']) + $goles_ida_rival;
                    ?>
                    <div class="badge bg-secondary px-3 py-2 mb-1" style="font-size: 0.9rem; letter-spacing: 1px;">
                        IDA: <?= $goles_ida_este_equipo ?> - <?= $goles_ida_rival ?>
                    </div><br>
                    <div class="badge bg-warning text-dark px-4 py-2 shadow-sm" style="font-size: 1.1rem; border: 2px solid #555;">
                        <i class="fa-solid fa-earth-americas me-1"></i> GLOBAL: <span id="global-local"><?= $global_local ?></span> - <span id="global-visitante"><?= $global_visitante ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Visitante -->
            <div class="col-4 col-md-3 text-center">
                <img src="uploads/logos/<?= $partido['visitante_logo'] ?>" class="img-fluid rounded-circle bg-white p-1 mb-3 shadow" style="width: 120px; height: 120px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png';">
                <h4 class="fw-bold mb-0"><?= htmlspecialchars($partido['visitante_nombre']) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Información de Impugnación (Se muestra dinámicamente) -->
<div id="protest-info-container" class="d-none animate__animated animate__fadeInDown">
    <div class="card shadow-sm border-0 border-start border-5 border-danger mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-danger mb-0">
                    <i class="fa-solid fa-scale-balanced me-2"></i> Estado de Impugnación: <span id="imp_estado_badge" class="badge bg-warning ms-2">PENDIENTE</span>
                </h5>
                <span id="imp_fecha" class="text-muted small">Registrado el 15/03/2026</span>
            </div>
            <div class="row">
                <div class="col-md-7">
                    <p class="mb-1 text-muted small fw-bold">EQUIPO DENUNCIANTE:</p>
                    <p id="imp_equipo_denunciante" class="fw-bold fs-5 mb-3 text-dark">Nombre del Equipo</p>
                    <p class="mb-1 text-muted small fw-bold">MOTIVO DE LA DENUNCIA:</p>
                    <p id="imp_motivo_texto" class="italic text-muted border-start ps-3 mb-0">El equipo denuncia que el jugador...</p>
                </div>
                <div class="col-md-5 border-start">
                    <div id="imp_resolucion_box">
                        <p class="mb-1 text-muted small fw-bold">RESOLUCIÓN OFICIAL:</p>
                        <p id="imp_resolucion_texto" class="fw-medium text-dark mb-3">Aún en proceso de evaluación por el comité.</p>
                        <div id="imp_sanciones_list">
                            <!-- Lista de sanciones dinámicas -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Panel de Eventos (Linea de Tiempo) -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-timeline text-primary me-2"></i> Eventos del Partido</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="cargarEventos(<?= $id_partido ?>)">Actualizar <i class="fa-solid fa-rotate-right"></i></button>
            </div>
            <div class="card-body" id="timeline-eventos" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br>Cargando eventos...</div>
            </div>
            <?php if($partido['observacion']): ?>
            <div class="card-footer bg-light p-3 border-top">
                <h6 class="fw-bold text-danger mb-1"><i class="fa-solid fa-triangle-exclamation"></i> Observaciones Oficiales:</h6>
                <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($partido['observacion'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Controles de Admin -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 border-top border-danger border-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-danger mb-0"><i class="fa-solid fa-gamepad me-2"></i> Controles del Partido</h5>
            </div>
            <div class="card-body">
                <?php if(!$is_admin): ?>
                    <p class="text-muted text-center my-4"><i class="fa-solid fa-lock fa-2x mb-2"></i><br>Modo lectura. No tiene permisos para registrar eventos.</p>
                <?php else: ?>
                    
                    <?php if($partido['estado'] === 'programado'): ?>
                        <div class="alert alert-secondary text-center">Debes <strong>iniciar el partido</strong> desde el fixture para habilitar el registro de goles y tarjetas.</div>
                    <?php elseif($en_juego): ?>
                        
                        <div class="d-grid gap-2 mb-4">
                            <button class="btn btn-dark text-white btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEvento">
                                <i class="fa-solid fa-plus-circle me-1"></i> Registrar Evento
                            </button>
                            <button class="btn btn-outline-warning" onclick="registrarWalkover(<?= $id_partido ?>)">
                                <i class="fa-solid fa-person-walking-arrow-right me-1"></i> Declarar Walkover (W/O)
                            </button>
                        </div>
                        
                        <hr>
                        
                        <?php if($partido['llave'] !== null && ($partido['es_ida'] == 0 || in_array($partido['fase'], ['Semifinal', 'Final', 'Tercer Puesto', 'Fase Final']))): ?>
                        <div class="alert alert-info py-2 px-3 small border-info shadow-sm">
                            <i class="fa-solid fa-futbol text-primary me-1"></i> <strong>Desempate Global (Penales)</strong><br>
                            Si el marcador global (Ida + Vuelta) está empatado, ingresa los penales:
                            <div class="row align-items-center mt-2 text-center">
                                <div class="col-5">
                                    <label class="small fw-bold text-truncate d-block mb-1"><?= htmlspecialchars($partido['local_nombre']) ?></label>
                                    <input type="number" min="0" class="form-control form-control-sm text-center fw-bold text-success border-success" id="val_penales_local" placeholder="Penales">
                                </div>
                                <div class="col-2 fw-bold text-muted px-0 mt-3">VS</div>
                                <div class="col-5">
                                    <label class="small fw-bold text-truncate d-block mb-1"><?= htmlspecialchars($partido['visitante_nombre']) ?></label>
                                    <input type="number" min="0" class="form-control form-control-sm text-center fw-bold text-danger border-danger" id="val_penales_visitante" placeholder="Penales">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button class="btn btn-danger btn-lg shadow" onclick="finalizarPartido(<?= $id_partido ?>)">
                                <i class="fa-solid fa-flag-checkered me-1"></i> Finalizar Partido
                            </button>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-success text-center fw-bold"><i class="fa-solid fa-check-circle"></i> Partido Finalizado</div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if($is_admin && $en_juego): ?>
<!-- Modal Registrar Evento -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEvento">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus me-2"></i> Registrar Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="registrar_evento">
                    <input type="hidden" name="partido_id" id="evento_partido_id" value="<?= $id_partido ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Evento</label>
                        <div class="d-flex justify-content-between text-center gap-2">
                            <input type="radio" class="btn-check" name="tipo" id="tipo_gol" value="gol" autocomplete="off" checked>
                            <label class="btn btn-outline-dark flex-fill" for="tipo_gol"><i class="fa-solid fa-futbol fs-4 d-block mb-1"></i> Gol</label>

                            <input type="radio" class="btn-check" name="tipo" id="tipo_amarilla" value="amarilla" autocomplete="off">
                            <label class="btn btn-outline-warning text-dark flex-fill" for="tipo_amarilla"><i class="fa-solid fa-clone fs-4 d-block mb-1"></i> Tarjeta Amarilla (-5 Bs)</label>

                            <input type="radio" class="btn-check" name="tipo" id="tipo_roja" value="roja" autocomplete="off">
                            <label class="btn btn-outline-danger flex-fill" for="tipo_roja"><i class="fa-solid fa-clone fs-4 d-block mb-1"></i> Tarjeta Roja (-10 Bs)</label>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Equipo</label>
                            <select class="form-select" id="evento_equipo_id" name="equipo_id" required>
                                <option value="">-- Seleccione --</option>
                                <option value="<?= $partido['equipo_local_id'] ?>">Local: <?= htmlspecialchars($partido['local_nombre']) ?></option>
                                <option value="<?= $partido['equipo_visitante_id'] ?>">Visitante: <?= htmlspecialchars($partido['visitante_nombre']) ?></option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Minuto</label>
                            <input type="number" class="form-control" name="minuto" min="1" max="130" placeholder="Ej. 45" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Jugador Impilicado</label>
                        <select class="form-select" id="evento_jugador_id" name="jugador_id" required disabled>
                            <option value="">-- Seleccione un equipo primero --</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark" id="btnGuardarEvento">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Variables a JS -->
<script>
    const partido_id = <?= $id_partido ?>;
    const jugadoresLocal = <?= json_encode($jugadoresL) ?>;
    const jugadoresVisitante = <?= json_encode($jugadoresV) ?>;
    const equipoLocalId = <?= $partido['equipo_local_id'] ?>;
    const equipoVisitanteId = <?= $partido['equipo_visitante_id'] ?>;
    const is_admin = <?= $is_admin ? 'true' : 'false' ?>;
    const faseActual = '<?= $partido['fase'] ?>';
    
    // Datos para el cálculo del Global en tiempo real
    const tieneIda = <?= $partido_ida ? 'true' : 'false' ?>;
    const golesIdaEsteEquipo = <?= $partido_ida ? $goles_ida_este_equipo : 0 ?>;
    const golesIdaRival = <?= $partido_ida ? $goles_ida_rival : 0 ?>;
</script>
<script src="js/gestionar_partido.js?v=<?= time() ?>"></script>

<style>
.timer-blink { animation: blinker 1s linear infinite; }
@keyframes blinker { 50% { opacity: 0; } }
.timeline-event { border-left: 3px solid #dee2e6; padding-left: 15px; position: relative; margin-bottom: 20px; }
.timeline-event::before { content: ''; position: absolute; left: -9px; top: 0; width: 15px; height: 15px; border-radius: 50%; background: #dee2e6; border: 3px solid white; }
.timeline-event.gol::before { background: #212529; }
.timeline-event.amarilla::before { background: #ffc107; }
.timeline-event.roja::before { background: #dc3545; }
</style>

<?php require_once 'includes/footer.php'; ?>
