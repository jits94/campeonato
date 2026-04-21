$(document).ready(function() {
    // Cargar eventos iniciales
    cargarEventos(partido_id);

    // Actualizar score periodico
    setInterval(function(){
        cargarEventos(partido_id, true); // true = silent update
    }, 10000); // 10 segundos

    if(is_admin) {
        // Manejar combos
        $('#evento_equipo_id').change(function() {
            let eqSelect = $(this).val();
            let opciones = '<option value="">-- Seleccione Jugador --</option>';
            
            if(eqSelect == equipoLocalId) {
                jugadoresLocal.forEach(j => { opciones += `<option value="${j.id}">${j.nombre}</option>`; });
                $('#evento_jugador_id').html(opciones).prop('disabled', false);
            } else if(eqSelect == equipoVisitanteId) {
                jugadoresVisitante.forEach(j => { opciones += `<option value="${j.id}">${j.nombre}</option>`; });
                $('#evento_jugador_id').html(opciones).prop('disabled', false);
            } else {
                $('#evento_jugador_id').html('<option value="">-- Seleccione un equipo primero --</option>').prop('disabled', true);
            }
        });

        $('#formEvento').on('submit', function(e){
            e.preventDefault();
            let formData = $(this).serialize();
            $('#btnGuardarEvento').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');

            $.ajax({
                url: 'ajax/gestionar_partido.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        $('#modalEvento').modal('hide');
                        $('#formEvento')[0].reset();
                        $('#evento_jugador_id').prop('disabled', true).html('<option value="">-- Seleccione --</option>');
                        cargarEventos(partido_id);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Registrado',
                            text: 'Evento guardado correctamente.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                    $('#btnGuardarEvento').prop('disabled', false).html('Guardar Evento');
                }
            });
        });
    }
});

window.cargarEventos = function(id, silent = false) {
    if(!silent) $('#timeline-eventos').html('<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>');
    
    $.ajax({
        url: 'ajax/gestionar_partido.php',
        type: 'POST',
        data: { action: 'obtener_datos_partido', partido_id: id },
        dataType: 'json',
        success: function(resp) {
            if(resp.success) {
                // Actualizar Marcador
                $('#score-local').text(resp.partido.goles_local);
                $('#score-visitante').text(resp.partido.goles_visitante);

                // Si hay Ida, actualizar Global en tiempo real
                if (typeof tieneIda !== 'undefined' && tieneIda) {
                    let g_local_actual = parseInt(resp.partido.goles_local || 0);
                    let g_visit_actual = parseInt(resp.partido.goles_visitante || 0);
                    $('#global-local').text(g_local_actual + golesIdaEsteEquipo);
                    $('#global-visitante').text(g_visit_actual + golesIdaRival);
                }
                
                // Pintar Timeline
                let html = '';
                
                // Manejar Información de Impugnación
                if(resp.impugnacion) {
                    let imp = resp.impugnacion;
                    $('#protest-info-container').removeClass('d-none');
                    $('#imp_estado_badge').text(imp.estado.toUpperCase())
                                          .attr('class', 'badge ms-2 ' + (imp.estado === 'pendiente' ? 'bg-warning text-dark' : (imp.estado === 'aceptada' ? 'bg-success' : 'bg-danger')));
                    $('#imp_fecha').text('Registrada el ' + new Date(imp.fecha_registro).toLocaleString());
                    $('#imp_equipo_denunciante').text(imp.equipo_denunciante);
                    $('#imp_motivo_texto').text(imp.motivo);
                    
                    if(imp.estado !== 'pendiente') {
                        $('#imp_resolucion_texto').text(imp.resolucion || 'Sin texto de resolución.');
                        let sancHtml = '<ul class="ps-3 mb-0 small text-danger fw-bold">';
                        if(imp.estado === 'aceptada') {
                            if(imp.puntos_castigo > 0) sancHtml += `<li>Deducción de ${imp.puntos_castigo} puntos.</li>`;
                            if(imp.monto_castigo > 0) sancHtml += `<li>Multa de Bs ${imp.monto_castigo}.</li>`;
                            if(imp.jugador_castigo_id) sancHtml += `<li>Sanción a Jugador: ${imp.jugador_sancionado_nombre} (${imp.jugador_castigo_detalle})</li>`;
                        } else {
                            sancHtml += `<li>Multa por impugnación infundada: Bs ${imp.monto_rechazo} (${imp.pago_rechazo_estado})</li>`;
                        }
                        sancHtml += '</ul>';
                        $('#imp_sanciones_list').html(sancHtml);
                    } else {
                        $('#imp_resolucion_texto').html('<span class="text-muted italic">Aún en proceso de evaluación por el comité administrador.</span>');
                        $('#imp_sanciones_list').html('');
                    }
                } else {
                    $('#protest-info-container').addClass('d-none');
                }

                if(resp.eventos.length === 0) {
                    html = '<div class="text-center text-muted py-5"><i class="fa-solid fa-clock-rotate-left fa-3x opacity-25 mb-3"></i><br>No hay eventos registrados en este partido.</div>';
                } else {
                    resp.eventos.forEach(ev => {
                        let icono = '';
                        let claseColor = '';
                        if(ev.tipo === 'gol') { icono = '<i class="fa-solid fa-futbol text-dark fa-lg me-2"></i>'; claseColor = 'gol'; }
                        else if(ev.tipo === 'amarilla') { icono = '<i class="fa-solid fa-square text-warning fa-lg me-2 border border-dark rounded-1"></i>'; claseColor = 'amarilla'; }
                        else if(ev.tipo === 'roja') { icono = '<i class="fa-solid fa-square text-danger fa-lg me-2 rounded-1"></i>'; claseColor = 'roja'; }
                        
                        let eqRef = (ev.equipo_id == equipoLocalId) ? '<span class="badge bg-light text-dark border me-2">L</span>' : '<span class="badge bg-dark text-white me-2">V</span>';
                        let delBtn = '';
                        if(is_admin && resp.partido.estado === 'en_juego') {
                            delBtn = `<button class="btn btn-sm btn-link text-danger p-0 ms-auto shadow-none" onclick="eliminarEvento(${ev.id})" title="Borrar evento"><i class="fa-solid fa-xmark"></i></button>`;
                        }

                        html += `
                        <div class="timeline-event ${claseColor} d-flex align-items-center">
                            <span class="fw-bold fs-5 text-secondary me-3" style="min-width: 40px;">${ev.minuto}'</span>
                            <div>
                                <div class="d-flex align-items-center mb-1">
                                    ${icono}
                                    <span class="fw-bold fs-5">${ev.jugador_nombre}</span>
                                </div>
                                <div>${eqRef} <small class="text-muted fw-medium">${ev.equipo_nombre}</small></div>
                            </div>
                            ${delBtn}
                        </div>`;
                    });
                }
                
                $('#timeline-eventos').html(html);
            }
        }
    });
};

window.eliminarEvento = function(id_evento) {
    Swal.fire({
        title: 'Borrar Evento',
        text: '¿Deseas eliminar este evento? También se borrará la sanción económica si es una tarjeta.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, borrar'
    }).then((res) => {
        if(res.isConfirmed) {
            $.ajax({
                url: 'ajax/gestionar_partido.php',
                type: 'POST',
                data: { action: 'eliminar_evento', id_evento: id_evento, partido_id: partido_id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        cargarEventos(partido_id);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};

window.finalizarPartido = function(id) {
    Swal.fire({
        title: 'Finalizar Partido',
        width: '600px',
        html: `
            <div class="text-start">
                <p class="mb-3 text-muted">Confirme el fin del partido. Se generarán los cobros por partido para ambos equipos.</p>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Observaciones Generales:</label>
                    <textarea class="form-control" id="obs" placeholder="Incidencias del partido..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Costo del Partido (por equipo):</label>
                    <div class="input-group">
                        <span class="input-group-text">Bs.</span>
                        <input type="number" class="form-control" id="costo_partido" value="50.00">
                    </div>
                </div>

                <div class="form-check form-switch p-3 bg-light rounded border mb-3">
                    <input class="form-check-input ms-0" type="checkbox" id="check_impugnar" onchange="toggleImpugnacion(this.checked)">
                    <label class="form-check-label fw-bold text-danger ms-2" for="check_impugnar">¿Este partido ha sido IMPUGNADO?</label>
                </div>

                <div id="wrapper_impugnacion" class="d-none animate__animated animate__fadeIn p-3 border rounded border-danger bg-danger bg-opacity-10">
                    <h6 class="text-danger fw-bold mb-3"><i class="fa-solid fa-gavel"></i> Datos de la Impugnación</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">EQUIPO QUE IMPUGNA:</label>
                        <select id="imp_equipo_id" class="form-select border-danger">
                            <option value="${equipoLocalId}">Local: ${jugadoresLocal[0]?.equipo_nombre || 'Local'}</option>
                            <option value="${equipoVisitanteId}">Visitante: ${jugadoresVisitante[0]?.equipo_nombre || 'Visitante'}</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">MOTIVO DE LA IMPUGNACIÓN:</label>
                        <textarea id="imp_motivo" class="form-control border-danger" rows="3" placeholder="Ej: Jugador inhabilitado, suplantación de identidad, etc."></textarea>
                    </div>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-flag-checkered me-1"></i> Finalizar y Guardar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            window.toggleImpugnacion = (checked) => {
                const wrapper = document.getElementById('wrapper_impugnacion');
                if(checked) wrapper.classList.remove('d-none');
                else wrapper.classList.add('d-none');
            };
        },
        preConfirm: () => {
            const impChecked = document.getElementById('check_impugnar').checked;
            if(impChecked && !document.getElementById('imp_motivo').value.trim()) {
                Swal.showValidationMessage('Debe ingresar el motivo de la impugnación');
                return false;
            }

            // Validar Ganador si es Semifinal, Final o Tercer Puesto (Partido único)
            const esFaseFinalUnica = ['Semifinal', 'Final', 'Tercer Puesto', 'Fase Final'].includes(faseActual);
            const gLoc = parseInt($('#score-local').text()) || 0;
            const gVis = parseInt($('#score-visitante').text()) || 0;

            if (esFaseFinalUnica || tieneIda) {
                let debeValidarPenales = false;
                let mensajeError = "";

                if (tieneIda) {
                    const globLoc = gLoc + golesIdaEsteEquipo;
                    const globVis = gVis + golesIdaRival;
                    if (globLoc === globVis) {
                        debeValidarPenales = true;
                        mensajeError = 'El marcador global está empatado. Debe ingresar los resultados de los penales.';
                    }
                } else if (esFaseFinalUnica && gLoc === gVis) {
                    debeValidarPenales = true;
                    mensajeError = 'En estas fases debe haber un ganador. El partido está empatado, debe ingresar los penales.';
                }

                if (debeValidarPenales) {
                    const pl = $('#val_penales_local').val();
                    const pv = $('#val_penales_visitante').val();
                    if (pl === '' || pv === '') {
                        Swal.showValidationMessage(mensajeError);
                        return false;
                    }
                    if (parseInt(pl) === parseInt(pv)) {
                        Swal.showValidationMessage('Los penales no pueden terminar en empate.');
                        return false;
                    }
                }
            }

            return {
                obs: document.getElementById('obs').value,
                costo: document.getElementById('costo_partido').value,
                impugnado: impChecked,
                imp_equipo_id: document.getElementById('imp_equipo_id').value,
                imp_motivo: document.getElementById('imp_motivo').value,
                penales_local: $('#val_penales_local').length ? $('#val_penales_local').val() : '',
                penales_visitante: $('#val_penales_visitante').length ? $('#val_penales_visitante').val() : ''
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/gestionar_partido.php',
                type: 'POST',
                data: { 
                    action: 'finalizar_partido', 
                    id_partido: id, 
                    observacion: result.value.obs, 
                    costo: result.value.costo,
                    impugnado: result.value.impugnado ? 1 : 0,
                    imp_equipo_id: result.value.imp_equipo_id,
                    imp_motivo: result.value.imp_motivo,
                    penales_local: result.value.penales_local,
                    penales_visitante: result.value.penales_visitante
                },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Partido Finalizado', resp.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};

window.registrarWalkover = function(id) {
    Swal.fire({
        title: 'Walkover (W/O)',
        html: `Seleccione el equipo ganador por WO.<br><br>
               <select id="ganador_wo" class="form-select mb-3">
                    <option value="${equipoLocalId}">Local Ganador</option>
                    <option value="${equipoVisitanteId}">Visitante Ganador</option>
               </select>
               <textarea class="form-control" id="obs_wo" placeholder="Detalles de W/O"></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Declarar WO'
    }).then((result) => {
        if (result.isConfirmed) {
            let win_id = $('#ganador_wo').val();
            let obs = $('#obs_wo').val();

            $.ajax({
                url: 'ajax/gestionar_partido.php',
                type: 'POST',
                data: { action: 'walkover', id_partido: id, equipo_ganador_id: win_id, observacion: obs },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('W/O Registrado', 'El partido terminó por Walkover.', 'success').then(() => {
                            window.location.reload();
                        });
                    }
                }
            });
        }
    });
};
