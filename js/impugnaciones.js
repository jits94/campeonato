$(document).ready(function() {
    var tabla = $('#tablaImpugnaciones').DataTable({
        "ajax": {
            "url": "ajax/impugnaciones.php?action=listar",
            "data": function(d) {
                d.estado = $('#filtro_estado').val();
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { "data": "fecha_registro" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `<strong>${row.local_nombre} vs ${row.visitante_nombre}</strong><br><small class="text-muted">ID Partido: ${row.partido_id}</small>`;
                }
            },
            { "data": "equipo_denunciante" },
            { 
                "data": "motivo",
                "render": function(data) {
                    return `<span title="${data}">${data.length > 50 ? data.substring(0, 50) + '...' : data}</span>`;
                }
            },
            { 
                "data": "estado",
                "render": function(data) {
                    if(data === 'pendiente') return '<span class="badge bg-warning text-dark px-3 shadow-sm">Pendiente</span>';
                    if(data === 'aceptada') return '<span class="badge bg-success px-3 shadow-sm">Aceptada</span>';
                    if(data === 'rechazada') return '<span class="badge bg-danger px-3 shadow-sm">Rechazada</span>';
                    return data;
                }
            },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    let btns = '';
                    if(row.estado === 'pendiente') {
                        btns += `<button class="btn btn-sm btn-primary shadow-sm" onclick="resolverImpugnacion(${row.id})"><i class="fa-solid fa-gavel"></i> Resolver</button>`;
                    } else if(row.estado === 'rechazada') {
                        let btnPago = (row.pago_rechazo_estado === 'pendiente') 
                            ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="marcarPagado(${row.id})"><i class="fa-solid fa-money-bill-transfer"></i> Pagar Multa (Bs ${row.monto_rechazo})</button>`
                            : `<span class="badge bg-light text-success border ms-1"><i class="fa-solid fa-check"></i> Pagado</span>`;
                        btns += `<button class="btn btn-sm btn-outline-secondary" onclick="verDetalle(${row.id})"><i class="fa-solid fa-eye"></i></button>${btnPago}`;
                    } else {
                        btns += `<button class="btn btn-sm btn-outline-secondary" onclick="verDetalle(${row.id})"><i class="fa-solid fa-eye"></i> Ver</button>`;
                    }
                    return btns;
                }
            }
        ],
        "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "order": [[0, "desc"]]
    });

    $('#filtro_estado').change(function() { tabla.ajax.reload(); });

    // Cambios en Radio Buttons del Modal
    $('input[name="estado"]').change(function() {
        if(this.value === 'aceptada') {
            $('#panel_aceptada').removeClass('d-none');
            $('#panel_rechazada').addClass('d-none');
        } else {
            $('#panel_aceptada').addClass('d-none');
            $('#panel_rechazada').removeClass('d-none');
        }
    });

    $('#formResolver').submit(function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        $('#btnGuardarResolucion').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: 'ajax/impugnaciones.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalResolver').modal('hide');
                    Swal.fire('Éxito', resp.message, 'success');
                    tabla.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
                $('#btnGuardarResolucion').prop('disabled', false).html('Guardar Resolución');
            }
        });
    });
});

window.resolverImpugnacion = function(id) {
    $.ajax({
        url: 'ajax/impugnaciones.php',
        type: 'POST',
        data: { action: 'obtener', id: id },
        dataType: 'json',
        success: function(resp) {
            if(resp.success) {
                let d = resp.data;
                $('#resolver_id').val(d.id);
                $('#info_partido').text(`${d.local_nombre} vs ${d.visitante_nombre}`);
                $('#info_equipo').text(d.equipo_denunciante);
                $('#info_obs_partido').text(d.partido_observacion || 'Sin observaciones registradas.');
                $('#info_motivo').text(d.motivo);
                
                $('#formResolver')[0].reset();
                $('#panel_aceptada, #panel_rechazada').addClass('d-none');
                $('#wrapper_castigo_jugador').addClass('d-none');
                
                // Cargar jugadores del equipo contrario al que impugna
                cargarJugadoresPartido(d.partido_id, d.equipo_que_impugna_id);
                
                $('#modalResolver').modal('show');
            }
        }
    });
};

function cargarJugadoresPartido(partidoId, equipoQueImpugnaId) {
    let select = $('#sel_jugador_castigo');
    select.html('<option value="">-- Cargando jugadores... --</option>');
    
    $.ajax({
        url: 'ajax/impugnaciones.php',
        type: 'POST',
        data: { 
            action: 'listar_jugadores_partido', 
            partido_id: partidoId,
            equipo_que_impugna_id: equipoQueImpugnaId 
        },
        dataType: 'json',
        success: function(resp) {
            if(resp.success) {
                let options = '<option value="">-- Seleccione un Jugador --</option>';
                let equipoActual = "";
                resp.data.forEach(function(j) {
                    if (equipoActual !== j.equipo_nombre) {
                        if (equipoActual !== "") options += '</optgroup>';
                        options += `<optgroup label="${j.equipo_nombre}">`;
                        equipoActual = j.equipo_nombre;
                    }
                    options += `<option value="${j.id}">${j.nombre}</option>`;
                });
                if (equipoActual !== "") options += '</optgroup>';
                select.html(options);
            } else {
                select.html('<option value="">Error al cargar jugadores</option>');
            }
        }
    });
}

window.togglePanelJugador = function(checked) {
    if(checked) {
        $('#wrapper_castigo_jugador').removeClass('d-none').hide().fadeIn();
    } else {
        $('#wrapper_castigo_jugador').fadeOut(function() {
            $(this).addClass('d-none');
            $('#sel_jugador_castigo').val('');
            $('textarea[name="jugador_castigo_detalle"]').val('');
        });
    }
};

window.marcarPagado = function(id) {
    Swal.fire({
        title: '¿Confirmar Pago?',
        text: "Marcar la multa de impugnación como pagada.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Pagado'
    }).then((res) => {
        if(res.isConfirmed) {
            $.ajax({
                url: 'ajax/impugnaciones.php',
                type: 'POST',
                data: { action: 'marcar_pagado', id: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        $('#tablaImpugnaciones').DataTable().ajax.reload(null, false);
                        Swal.fire('Registrado', 'Pago marcado correctamente.', 'success');
                    }
                }
            });
        }
    });
};

window.verDetalle = function(id) {
    $.ajax({
        url: 'ajax/impugnaciones.php',
        type: 'POST',
        data: { action: 'obtener', id: id },
        dataType: 'json',
        success: function(resp) {
            if(resp.success) {
                let d = resp.data;
                let body = `
                    <div class="text-start">
                        <p class="mb-2"><strong>Estado:</strong> <span class="badge ${d.estado === 'aceptada' ? 'bg-success' : 'bg-danger'}">${d.estado.toUpperCase()}</span></p>
                        <p class="mb-2"><strong>Resolución:</strong> ${d.resolucion || 'N/A'}</p>
                        <hr>
                        ${d.estado === 'aceptada' ? `
                            <h6 class="fw-bold">Sanciones Aplicadas:</h6>
                            <ul class="small">
                                ${d.puntos_castigo > 0 ? `<li>Deducción de ${d.puntos_castigo} puntos al oponente.</li>` : ''}
                                ${d.monto_castigo > 0 ? `<li>Multa económica de Bs ${d.monto_castigo} al oponente.</li>` : ''}
                                ${d.jugador_castigo_id ? `<li><strong>Castigo a Jugador:</strong> ${d.jugador_castigo_nombre} - ${d.jugador_castigo_detalle}</li>` : ''}
                                ${(!d.puntos_castigo && !d.monto_castigo && !d.jugador_castigo_id) ? '<li>Ninguna sanción adicional registrada.</li>' : ''}
                            </ul>
                        ` : `
                            <p><strong>Multa por rechazo:</strong> Bs ${d.monto_rechazo} (${d.pago_rechazo_estado})</p>
                        `}
                    </div>
                `;
                Swal.fire({ title: 'Detalle de Resolución', html: body, icon: 'info' });
            }
        }
    });
};
