<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En Vivo - Campeonatos Pro</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a; /* Slate 900 */
            color: #f8fafc;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        .header-live {
            background: linear-gradient(90deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        .header-live h1 {
            margin: 0;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .board-container {
            flex-grow: 1;
            padding: 2rem 0;
            background: url('https://images.unsplash.com/photo-1518091043644-c1d4457512ca?q=80&w=1470&auto=format&fit=crop') center/cover no-repeat;
            background-blend-mode: overlay;
            background-color: rgba(15, 23, 42, 0.9); /* Oculta un poco la imagen */
        }

        .match-card {
            background: rgba(30, 41, 59, 0.85); /* Glass effect */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            transition: transform 0.3s;
            overflow: hidden;
        }

        .match-card:hover {
            transform: scale(1.02);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .score-box {
            background: #000;
            color: #fbbf24; /* Amber 400 */
            font-family: monospace;
            font-size: 2.5rem;
            font-weight: 800;
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            box-shadow: inset 0 0 15px rgba(251, 191, 36, 0.2);
            line-height: 1;
            display: inline-block;
            min-width: 110px;
        }

        .team-logo {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            background: white;
            padding: 3px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.4);
        }

        .team-name {
            font-size: 1.3rem;
            font-weight: 800;
            margin-top: 0.8rem;
            text-transform: uppercase;
        }

        .match-header {
            background: rgba(0, 0, 0, 0.4);
            padding: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .timer-blink {
            animation: blinker 1s linear infinite;
        }
        
        @keyframes blinker {
            50% { opacity: 0; }
        }

        .live-badge {
            background: #ef4444; /* Red 500 */
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 1rem;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 1px;
            display: inline-block;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }

        .status-badge {
            background: #475569; /* Slate 600 */
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .team-logo {
                width: 50px;
                height: 50px;
            }
            .team-name {
                font-size: 0.75rem;
                margin-top: 0.5rem;
            }
            .score-box {
                font-size: 1.4rem;
                min-width: 85px;
                padding: 0.3rem 0.5rem;
                white-space: nowrap;
            }
            .match-header {
                font-size: 0.8rem;
                padding: 0.5rem;
            }
            .card-body {
                padding: 1.5rem 1rem !important;
            }
            .live-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }
        }

        /* Estilos Modal y Timeline */
        .modal-content {
            background: #0f172a;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
        }
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .timeline {
            position: relative;
            padding: 20px 0;
            list-style: none;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            margin-left: -1.5px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .timeline > li {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline > li:before, .timeline > li:after {
            content: " ";
            display: table;
        }
        .timeline > li:after {
            clear: both;
        }
        .timeline-badge {
            position: absolute;
            top: 0;
            left: 50%;
            width: 30px;
            height: 30px;
            margin-left: -15px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background-color: #1e293b;
            z-index: 100;
            border: 2px solid #3b82f6;
        }
        .timeline-panel {
            position: relative;
            width: 45%;
            padding: 10px 15px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .timeline > li > .timeline-panel {
            float: left;
        }
        .timeline > li.timeline-inverted > .timeline-panel {
            float: right;
        }
        .match-card { cursor: pointer; }

    </style>
</head>
<body>

    <header class="header-live text-center">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="d-flex align-items-center mb-0">
                <i class="fa-solid fa-satellite-dish fa-beat me-3 text-white"></i> 
                RESULTADOS EN VIVO
            </h1>
            <div id="reloj" class="fs-4 fw-bold font-monospace"></div>
        </div>
    </header>

    <div class="board-container">
        <div class="container-fluid px-4 px-md-5">
            
            <div id="loader" class="text-center py-5">
                <i class="fa-solid fa-circle-notch fa-spin fa-4x text-primary mb-3"></i>
                <h3 class="fw-light">Sincronizando con el estadio...</h3>
            </div>

            <div class="row align-items-stretch" id="matches-container">
                <!-- Llenado por AJAX -->
            </div>

            <div id="empty-state" class="text-center py-5 d-none">
                <i class="fa-regular fa-futbol fa-5x text-secondary mb-4 opacity-50"></i>
                <h2 class="fw-bold text-secondary">No hay partidos programados para el día de hoy.</h2>
            </div>
            
        </div>
    </div>

    <!-- Modal Detalles Partido -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <div id="modal-loading" class="text-center py-5">
                        <i class="fa-solid fa-spinner fa-spin fa-3x text-primary"></i>
                    </div>
                    <div id="modal-content-area" class="d-none">
                        <div class="text-center mb-5">
                            <h4 id="md-torneo-fase" class="text-uppercase text-primary fw-bold mb-4" style="letter-spacing: 2px;"></h4>
                            <div class="d-flex justify-content-center align-items-center gap-2 gap-md-4 flex-nowrap">
                                <div class="text-center" style="width: 30%;">
                                    <img id="md-logo-local" src="" class="team-logo mb-3" style="width:80px; height:80px;">
                                    <h5 id="md-nombre-local" class="fw-bold mb-0"></h5>
                                </div>
                                <div class="text-center" style="width: 40%;">
                                    <div class="display-6 fw-bold font-monospace" id="md-score" style="white-space: nowrap;"></div>
                                    <div id="md-estado-badge"></div>
                                </div>
                                <div class="text-center" style="width: 30%;">
                                    <img id="md-logo-visitante" src="" class="team-logo mb-3" style="width:80px; height:80px;">
                                    <h5 id="md-nombre-visitante" class="fw-bold mb-0"></h5>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="opacity-10">
                        
                        <div class="timeline-container">
                            <h6 class="text-center text-secondary text-uppercase fw-bold mb-4" style="letter-spacing: 3px;">Cronología del encuentro</h6>
                            <ul class="timeline" id="match-timeline">
                                <!-- Poblado por JS -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/live.js?v=<?= time() ?>"></script>
</body>
</html>
