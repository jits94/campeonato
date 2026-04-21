$(document).ready(function() {
    $('#panel-vacio').removeClass('d-none'); // Mostrar por defecto
    
    $('#filtro_torneo').change(function() {
        const torneo_id = $(this).val();
        if(torneo_id) {
            $('#panel-vacio').addClass('d-none');
            verificarGrupos(torneo_id);
        } else {
            $('#panel-vacio').removeClass('d-none');
            $('#panel-sortear').addClass('d-none');
            $('#panel-resultados').addClass('d-none');
        }
    });

    $('#btnSortear').click(function() {
        const torneo_id = $('#filtro_torneo').val();
        const num_grupos = $('#num_grupos').val();

        if(!torneo_id || num_grupos < 2) {
            Swal.fire('Atención', 'Seleccione un torneo y al menos 2 grupos.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Generar Sorteo Aleatorio?',
            text: "Se asignarán todos los equipos inscritos a los " + num_grupos + " grupos de forma aleatoria.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, Sortear',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).html('<i class="fa-solid fa-spinner fa-spin"></i> Sorteando...').prop('disabled', true);
                
                $.ajax({
                    url: 'ajax/grupos.php',
                    type: 'POST',
                    data: { action: 'sortear', torneo_id: torneo_id, num_grupos: num_grupos },
                    dataType: 'json',
                    success: function(resp) {
                        if(resp.success) {
                            Swal.fire('¡Sorteo Realizado!', resp.message, 'success');
                            verificarGrupos(torneo_id);
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                        $('#btnSortear').html('¡Realizar Sorteo! <i class="fa-solid fa-dice ms-1"></i>').prop('disabled', false);
                    },
                    error: function() {
                        Swal.fire('Error', 'Problema con el servidor', 'error');
                        $('#btnSortear').html('¡Realizar Sorteo! <i class="fa-solid fa-dice ms-1"></i>').prop('disabled', false);
                    }
                });
            }
        });
    });

    $('#btnBorrarSorteo').click(function() {
        const torneo_id = $('#filtro_torneo').val();
        Swal.fire({
            title: '¿Rehacer Sorteo?',
            text: "Se borrarán los grupos actuales y los partidos programados de esta fase. ¡Esta acción no se puede deshacer!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, borrar todo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/grupos.php',
                    type: 'POST',
                    data: { action: 'borrar_sorteo', torneo_id: torneo_id },
                    dataType: 'json',
                    success: function(resp) {
                        if(resp.success) {
                            Swal.fire('Borrado', resp.message, 'success');
                            verificarGrupos(torneo_id);
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                    }
                });
            }
        });
    });
});

function verificarGrupos(torneo_id) {
    $.ajax({
        url: 'ajax/grupos.php',
        type: 'POST',
        data: { action: 'verificar', torneo_id: torneo_id },
        dataType: 'json',
        success: function(resp) {
            if(resp.success) {
                if(resp.tiene_grupos) {
                    $('#panel-sortear').addClass('d-none');
                    $('#panel-resultados').removeClass('d-none');
                    renderizarGrupos(resp.data);
                } else {
                    $('#panel-sortear').removeClass('d-none');
                    $('#panel-resultados').addClass('d-none');
                    $('#info-equipos').text('Equipos inscritos en este torneo: ' + resp.total_inscritos);
                }
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        }
    });
}

function renderizarGrupos(grupos) {
    let html = '';
    
    // grupos es un objeto: { "Grupo A": [equipos], "Grupo B": [equipos] }
    for(const [nombreGrupo, equipos] of Object.entries(grupos)) {
        html += `
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card shadow-sm h-100 border-top border-primary border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold text-primary mb-0 text-center">${nombreGrupo}</h5>
                </div>
                <ul class="list-group list-group-flush">
        `;
        
        if(equipos.length === 0) {
            html += `<li class="list-group-item text-center text-muted py-3">Sin equipos</li>`;
        } else {
            equipos.forEach(eq => {
                html += `<li class="list-group-item d-flex align-items-center py-3">
                            <i class="fa-solid fa-shield-halved text-secondary me-2"></i> 
                            <span class="fw-medium">${eq.nombre}</span>
                         </li>`;
            });
        }
        
        html += `
                </ul>
            </div>
        </div>
        `;
    }
    
    $('#contenedor-grupos').html(html);
}
