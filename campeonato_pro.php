<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TorneoPro | La Plataforma Definitiva para Gestión de Campeonatos</title>
    
    <!-- Meta Tags para SEO -->
    <meta name="description" content="Lleva tu liga o torneo al siguiente nivel con TorneoPro. Gestión de partidos, tablas de posiciones en tiempo real, finanzas y más.">
    <meta name="keywords" content="gestión deportiva, software fútbol, torneos, campeonatos, liga de fútbol, software para deportes">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS (Animate on Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #10b981;
            --secondary: #3b82f6;
            --dark: #0f172a;
            --light: #f8fafc;
            --accent: #f59e0b;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            overflow-x: hidden;
        }

        /* Hero Section Custom Styles */
        .hero-section {
            padding: 120px 0 80px;
            background: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 40%);
            position: relative;
        }

        .hero-title {
            font-weight: 800;
            font-size: 4rem;
            line-height: 1.1;
            background: linear-gradient(to right, #ffffff, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-weight: 300;
            font-size: 1.25rem;
            color: #cbd5e1;
            margin-bottom: 2.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .feature-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(10px);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-title {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .highlight-text {
            color: var(--primary);
            font-weight: 600;
        }

        .screenshot-container {
            position: relative;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 8px solid rgba(255, 255, 255, 0.03);
            margin-bottom: 50px;
        }

        .screenshot-container img {
            width: 100%;
            display: block;
            transition: transform 0.6s ease;
        }

        .screenshot-container:hover img {
            transform: scale(1.02);
        }

        .overlay-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, var(--dark), transparent 30%);
            pointer-events: none;
        }

        .stats-badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        footer {
            padding: 80px 0 40px;
            border-top: 1px solid var(--glass-border);
            margin-top: 100px;
        }

        .nav-glass {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--glass-border);
        }

        .text-muted {
            color: #cbd5e1 !important; /* Color mucho más claro para mejor lectura */
        }

        .feature-card p {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top nav-glass py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#"><i class="fa-solid fa-trophy text-success me-2"></i>TorneoPro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="#beneficios">Beneficios</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#caracteristicas">Características</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#vistas">Vista Previa</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-gradient" href="#">Obtener Ahora</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center text-lg-start">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                    <div class="stats-badge"><i class="fa-solid fa-bolt me-1"></i> El sistema líder en gestión deportiva</div>
                    <h1 class="hero-title">Organiza Torneos como un Profesional</h1>
                    <p class="hero-subtitle">Automatiza el registro de partidos, controla las finanzas de tu liga y mantén a tus jugadores conectados con resultados en tiempo real.</p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="#" class="btn btn-gradient btn-lg">Empezar Ahora</a>
                        <a href="#" class="btn btn-outline-light btn-lg border-2" style="border-radius: 50px;"><i class="fa-solid fa-play me-2"></i> Ver Demo</a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0" data-aos="zoom-in" data-aos-duration="1200">
                    <div class="screenshot-container">
                        <img src="assets/img/actual_dashboard.png" alt="Sistema Dashboard Real">
                        <div class="overlay-gradient"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Beneficios Section -->
    <section id="beneficios" class="py-5">
        <div class="container py-5">
            <h2 class="section-title">¿Por qué elegir <span class="highlight-text">TorneoPro</span>?</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card text-center">
                        <i class="fa-solid fa-list-ol feature-icon"></i>
                        <h4 class="fw-bold mb-3">Posiciones en Vivo</h4>
                        <p class="text-muted">Despídete del papel y Excel. Las tablas de posiciones se actualizan automáticamente tras cada partido finalizado para todos los equipos.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card text-center">
                        <i class="fa-solid fa-file-invoice-dollar feature-icon"></i>
                        <h4 class="fw-bold mb-3">Finanzas y Deudas</h4>
                        <p class="text-muted">Ten total visibilidad sobre cobros de tarjetas, arbitrajes e inscripciones. Mantén las cuentas de tu torneo siempre claras y al día.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card text-center">
                        <i class="fa-solid fa-chart-line feature-icon"></i>
                        <h4 class="fw-bold mb-3">Historial y Rendimiento</h4>
                        <p class="text-muted">Consulta fácilmente los goleadores, amonestados y la trayectoria detallada de cada jugador y equipo a lo largo de todo el campeonato.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- UI Feature Section -->
    <section id="caracteristicas" class="py-5 bg-dark">
        <div class="container py-5">
            <div class="row align-items-center mb-5">
                <div class="col-lg-5 order-2 order-lg-1" data-aos="fade-right">
                    <h3 class="fw-bold fs-2 mb-4">Registro en Tiempo Real</h3>
                    <p class="text-muted fs-5 mb-4">Nunca antes fue tan fácil registrar goles, tarjetas y eventos. Nuestra interfaz intuitiva permite a los veedores actualizar resultados en vivo.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-3 fs-5"></i> Registro dinámico de eventos por minuto</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-3 fs-5"></i> Gestión automática de sanciones económicas</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-3 fs-5"></i> Soporte para sistemas de penales en llaves finales</li>
                    </ul>
                </div>
                <div class="col-lg-7 order-1 order-lg-2 mb-5 mb-lg-0" data-aos="fade-left">
                    <div class="screenshot-container">
                        <img src="assets/img/actual_matches.png" alt="Gestión de Partidos Real">
                    </div>
                </div>
            </div>

            <div class="row align-items-center">
                <div class="col-lg-7 mb-5 mb-lg-0" data-aos="fade-right">
                    <div class="screenshot-container">
                        <img src="assets/img/actual_standings.png" alt="Tablas de Posiciones Reales">
                    </div>
                </div>
                <div class="col-lg-5 ps-lg-5" data-aos="fade-left">
                    <h3 class="fw-bold fs-2 mb-4">Estadísticas y Tablas</h3>
                    <p class="text-muted fs-5 mb-4">El sistema calcula automáticamente la diferencia de goles, puntos y posiciones basándose en los resultados de cada partido del torneo.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-circle-dot text-primary me-3"></i> Soporte para "Todos contra Todos" y "Fase de Grupos"</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-circle-dot text-primary me-3"></i> Sistema de desempate configurable</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fa-solid fa-circle-dot text-primary me-3"></i> Reportes PDF instantáneos para delegados</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTSection -->
    <section class="py-5">
        <div class="container py-5 text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="zoom-in">
                    <div class="feature-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(59, 130, 246, 0.2)); border-color: var(--primary);">
                        <h2 class="fw-bold mb-4">¿Listo para transformar tu organización deportiva?</h2>
                        <p class="text-light fs-5 mb-5 opacity-75">Únete a cientos de administradores que ya están optimizando sus torneos con nuestra solución.</p>
                        <a href="mailto:hola@torneopro.com" class="btn btn-gradient btn-lg px-5">Contactar Ventas</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <div class="mb-4">
                <a class="navbar-brand fw-bold fs-2" href="#"><i class="fa-solid fa-trophy text-success me-2"></i>TorneoPro</a>
            </div>
            <p class="text-muted small">© 2026 TorneoPro Systems. Todos los derechos reservados. Diseñado para la excelencia deportiva.</p>
            <div class="mt-4">
                <a href="#" class="text-light mx-2"><i class="fa-brands fa-twitter fs-4"></i></a>
                <a href="#" class="text-light mx-2"><i class="fa-brands fa-facebook fs-4"></i></a>
                <a href="#" class="text-light mx-2"><i class="fa-brands fa-instagram fs-4"></i></a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            mirror: false
        });
    </script>
</body>
</html>
