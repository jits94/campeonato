<nav id="sidebar" class="sidebar bg-dark text-white">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-trophy text-warning"></i> CampPro</h3>
    </div>
    
    <ul class="list-unstyled components">
        <li class="active">
            <a href="dashboard.php"><i class="fa-solid fa-house me-2"></i> Dashboard</a>
        </li>
        <?php if($_SESSION['rol'] === 'administrador'): ?>
        <li>
            <a href="#torneosSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fa-solid fa-calendar-days me-2"></i> Torneos
            </a>
            <ul class="collapse list-unstyled mb-2" id="torneosSubmenu">
                <li><a href="torneos.php">Gestionar Torneos</a></li>
                <li><a href="inscripciones.php">Inscripciones</a></li>
                <li><a href="grupos.php">Sorteo Grupos</a></li>
            </ul>
        </li>
        <li>
            <a href="#equiposSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fa-solid fa-shield-halved me-2"></i> Equipos
            </a>
            <ul class="collapse list-unstyled mb-2" id="equiposSubmenu">
                <li><a href="equipos.php">Gestionar Equipos</a></li>
                <li><a href="jugadores.php">Jugadores</a></li>
                <li><a href="transferencias.php">Transferencias</a></li>
            </ul>
        </li>
        <?php endif; ?>
        <li>
            <a href="resultados.php"><i class="fa-solid fa-square-poll-vertical me-2"></i> Resultados</a>
        </li>
        <li>
            <a href="posiciones.php"><i class="fa-solid fa-ranking-star me-2"></i> Tabla Posiciones</a>
        </li>
        <li>
            <a href="reportes.php"><i class="fa-solid fa-chart-line me-2"></i> Reportes y Estadísticas</a>
        </li>
        <?php if($_SESSION['rol'] === 'administrador'): ?>
        <li>
            <a href="partidos.php"><i class="fa-solid fa-futbol me-2"></i> Programación</a>
        </li>
        <li>
            <a href="impugnaciones.php"><i class="fa-solid fa-gavel me-2"></i> Impugnaciones</a>
        </li>
        <li>
            <a href="finanzas.php"><i class="fa-solid fa-money-bill-trend-up me-2"></i> Finanzas</a>
        </li>
        <li>
            <a href="configuracion.php"><i class="fa-solid fa-users-gear me-2"></i> Usuarios y Directiva</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Contenido principal -->
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm mb-4">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-primary d-md-none">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 fw-medium">
                    <i class="fa-solid fa-user-circle fs-5 text-secondary align-middle me-1"></i> 
                    <?= htmlspecialchars($_SESSION['nombre']) ?> 
                    <span class="badge bg-<?= $_SESSION['rol'] == 'administrador' ? 'primary' : 'secondary' ?>"><?= ucfirst($_SESSION['rol']) ?></span>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid main-content px-4">
