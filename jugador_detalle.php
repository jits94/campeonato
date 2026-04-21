<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$jugador_id = $_GET['id'] ?? 0;
if (!$jugador_id) {
    echo "<script>window.location='jugadores.php';</script>";
    exit;
}
?>

<div class="mb-4">
    <a href="jugadores.php" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Jugadores
    </a>
    <div class="row align-items-end">
        <div class="col-auto">
            <div id="wrapper_foto" class="rounded-circle shadow overflow-hidden border border-4 border-white position-relative" style="width: 120px; height: 120px; background-color: #f8f9fa;">
                <img id="foto_jugador" src="assets/img/default_user.png" class="w-100 h-100" style="object-fit: cover; display: none;">
                <div id="foto_placeholder" class="w-100 h-100 d-flex align-items-center justify-content-center text-primary" style="font-size: 3.5rem;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <!-- Botón Editar Foto -->
                <div class="position-absolute bottom-0 end-0 p-1">
                    <button class="btn btn-sm btn-info text-white rounded-circle shadow-sm" onclick="triggerFileUpload()" title="Cambiar Foto">
                        <i class="fa-solid fa-camera fa-xs"></i>
                    </button>
                    <input type="file" id="input_foto" class="d-none" accept="image/*" onchange="uploadFoto(this)">
                </div>
            </div>
        </div>
        <div class="col">
            <h1 class="fw-bold text-dark mb-1" id="nombre_jugador"> Cargando... </h1>
            <p class="text-muted mb-0">
                <i class="fa-solid fa-id-card me-1"></i> CI: <span id="ci_jugador">--</span> | 
                <i class="fa-solid fa-shirt me-1"></i> Dorsal: <span id="dorsal_jugador">--</span>
            </p>
        </div>
        <div class="col-auto text-end" id="equipo_actual_container">
            <!-- Renderizado via JS -->
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white mb-3">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_goles">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Goles Totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-dark mb-3">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_amarillas">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Tarjetas Amarillas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white mb-3">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_rojas">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Tarjetas Rojas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-dark text-white mb-3">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_transf">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Transferencias</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Columna Izquierda: Historial de Clubes y Transferencias -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Historial de Clubes</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="lista_equipos">
                    <!-- JS -->
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-right-left me-2 text-dark"></i>Historial de Transferencias</h5>
            </div>
            <div class="card-body p-0">
                <div id="transferencias_log" class="p-3">
                    <!-- JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Goles y Sanciones Detalladas -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="goles-tab" data-bs-toggle="tab" data-bs-target="#goles-pane" type="button">Log de Goles</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold text-danger" id="sanciones-tab" data-bs-toggle="tab" data-bs-target="#sanciones-pane" type="button">Sanciones Recibidas</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="goles-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tabla_goles_jugador">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Torneo</th>
                                        <th>Partido</th>
                                        <th class="text-center">Min.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="sanciones-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tabla_sanciones_jugador">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Partido</th>
                                        <th>Tarjeta</th>
                                        <th>Torneo</th>
                                        <th>Multa</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const JUGADOR_ID = <?= $jugador_id ?>;
</script>
<script src="js/jugador_detalle.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
