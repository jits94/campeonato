$(document).ready(function() {
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const usuario = $('#usuario').val();
        const password = $('#password').val();
        
        const $btn = $('#btn-login');
        const originalText = $btn.html();
        
        $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Entrando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/auth.php',
            type: 'POST',
            data: {
                action: 'login',
                usuario: usuario,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de acceso',
                        text: response.message
                    });
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error En el Servidor',
                    text: 'Ocurrió un error al procesar la solicitud.'
                });
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
