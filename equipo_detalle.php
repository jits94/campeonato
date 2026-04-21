<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$equipo_id = $_GET['id'] ?? 0;
if (!$equipo_id) {
    echo "<script>window.location='equipos.php';</script>";
    exit;
}
?>

<div class="mb-4">
    <a href="equipos.php" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Equipos
    </a>
    <div class="row align-items-end">
        <div class="col-auto">
            <div id="wrapper_foto" class="rounded-circle shadow overflow-hidden border border-4 border-white position-relative" style="width: 120px; height: 120px; background-color: #f8f9fa;">
                <img id="foto_equipo" src="assets/img/default_team.png" class="w-100 h-100" style="object-fit: cover; display: none;">
                <div id="foto_placeholder" class="w-100 h-100 d-flex align-items-center justify-content-center text-primary" style="font-size: 3.5rem;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <h1 class="fw-bold text-dark mb-1" id="nombre_equipo"> Cargando... </h1>
            <p class="text-muted mb-0">
                <i class="fa-solid fa-calendar-days me-1"></i> Fecha de Registro: <span id="fecha_registro_equipo">--</span>
            </p>
        </div>
    </div>
</div>

<div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm border-0 bg-secondary text-white h-100">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_torneos">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Torneos</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-info text-white h-100">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_jugadores">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Jugadores</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-primary text-white h-100">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_pj">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Partidos</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-success text-white h-100">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_gf">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Goles Fav.</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-danger text-white h-100">
            <div class="card-body py-4 text-center">
                <h2 class="fw-bold mb-0" id="stat_gc">0</h2>
                <p class="mb-0 text-uppercase small opacity-75 fw-bold">Goles Cnt.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Columna Izquierda: Palmares y Jugadores -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-trophy text-warning me-2"></i>Palmarés</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="lista_palmares">
                    <!-- JS -->
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Historial de Torneos</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="lista_historial_torneos">
                    <!-- JS -->
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-users text-primary me-2"></i>Plantilla Actual</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="lista_jugadores">
                    <!-- JS -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Partidos -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-check text-success me-2"></i>Últimos Partidos Jugados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tabla_partidos_equipo">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Torneo</th>
                                <th class="text-end">Local</th>
                                <th class="text-center">Resultado</th>
                                <th>Visitante</th>
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

<script>
    const EQUIPO_ID = <?= $equipo_id ?>;
</script>
<script src="js/equipo_detalle.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
