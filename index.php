<?php
session_start();
if(isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campeonatos Pro</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/login.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <div class="login-left d-none d-lg-flex">
        <div class="overlay"></div>
        <div class="content">
            <h1>Gestión de<br><span>Campeonatos Pro</span></h1>
            <p>La plataforma definitiva para el control total de torneos, partidos, equipos y finanzas.</p>
        </div>
    </div>
    <div class="login-right">
        <div class="form-wrapper">
            <h2 class="text-center mb-4"><i class="fa-solid fa-futbol text-primary"></i> Iniciar Sesión</h2>
            <form id="loginForm">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="usuario" placeholder="Usuario" required>
                    <label for="usuario"><i class="fa-solid fa-user me-2"></i>Usuario</label>
                </div>
                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="password" placeholder="Contraseña" required>
                    <label for="password"><i class="fa-solid fa-lock me-2"></i>Contraseña</label>
                    <span class="position-absolute cursor-pointer" id="togglePassword" style="right: 15px; top: 15px; z-index: 10; cursor: pointer;">
                        <i class="fa-regular fa-eye text-muted"></i>
                    </span>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3" id="btn-login">
                    Entrar <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>
            <div class="mt-4 text-center text-muted small">
                &copy; <?php echo date('Y'); ?> Sistema de Campeonatos Pro. Todos los derechos reservados.<br>
                <small>Credenciales por defecto: admin / 123456</small>
            </div>
        </div>
    </div>
</div>

<!-- jQuery, Bootstrap JS, SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Logic JS -->
<script src="js/login.js"></script>
</body>
</html>
