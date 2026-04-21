$(document).ready(function() {
    // Reloj
    setInterval(function() {
        const now = new Date();
        $('#reloj').text(now.toLocaleTimeString());
    }, 1000);

    // Cargar Partidos
    cargarPartidos();

    // Actualizar cada 10 segundos
    setInterval(cargarPartidos, 10000);
});

function cargarPartidos() {
    $.ajax({
        url: 'ajax/live.php',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            $('#loader').addClass('d-none');
            
            if(resp.data.length === 0) {
                $('#empty-state').removeClass('d-none');
                $('#matches-container').html('');
                return;
            }

            $('#empty-state').addClass('d-none');
            
            let grouped = {};
            resp.data.forEach(p => {
                let key = p.torneo_nombre + (p.nombre_grupo ? ' - ' + p.nombre_grupo : '');
                if(!grouped[key]) grouped[key] = [];
                grouped[key].push(p);
            });

            let html = '';
            for (let key in grouped) {
                // Header del grupo/torneo
                html += `
                <div class="col-12 mt-4 mb-2">
                    <h2 class="fw-bold border-start border-4 border-primary ps-3 text-white" style="letter-spacing: 1px;">
                        ${key.toUpperCase()}
                    </h2>
                </div>`;

                grouped[key].forEach(p => {
                    let statusHtml = '';
                    if(p.estado === 'en_juego') {
                        statusHtml = `<div class="live-badge timer-blink mb-3"><i class="fa-solid fa-circle" style="font-size:8px;"></i> EN JUEGO</div>`;
                    } else if(p.estado === 'programado') {
                        statusHtml = `<div class="status-badge mb-3"><i class="fa-regular fa-clock"></i> ${p.hora_formateada}</div>`;
                    } else if(p.estado === 'walkover') {
                        statusHtml = `<div class="status-badge bg-dark mb-3">WALKOVER</div>`;
                    } else {
                        statusHtml = `<div class="status-badge bg-success mb-3">FINALIZADO</div>`;
                    }

                    let scoreTxt = (p.estado === 'programado') ? '- : -' : `${p.goles_local} - ${p.goles_visitante}`;
                    if(p.estado === 'walkover') scoreTxt = 'W - O';

                    let logoLocal = p.local_logo === 'default.png' ? 'https://ui-avatars.com/api/?name='+p.local_nombre+'&background=random&color=fff&size=200' : 'uploads/logos/'+p.local_logo;
                    let logoVis = p.visitante_logo === 'default.png' ? 'https://ui-avatars.com/api/?name='+p.visitante_nombre+'&background=random&color=fff&size=200' : 'uploads/logos/'+p.visitante_logo;

                    html += `
                    <div class="col-xl-6 col-xxl-4 mb-4">
                        <div class="match-card h-100" 
                             data-id="${p.id}" 
                             data-torneo="${p.torneo_nombre}" 
                             data-fase="${p.fase}"
                             data-local="${p.local_nombre}"
                             data-visitante="${p.visitante_nombre}"
                             data-logo-l="${logoLocal}"
                             data-logo-v="${logoVis}">
                            <div class="match-header text-center">
                                ${p.fase}
                            </div>
                            <div class="card-body p-4 p-md-5 text-center">
                                
                                ${statusHtml}

                                <div class="d-flex justify-content-between align-items-center flex-nowrap">
                                    <!-- Local -->
                                    <div class="text-center px-1" style="width: 33%;">
                                        <img src="${logoLocal}" class="team-logo" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                        <div class="team-name text-truncate text-white" title="${p.local_nombre}">${p.local_nombre}</div>
                                    </div>
                                    
                                    <!-- Score -->
                                    <div class="text-center px-1" style="width: 34%;">
                                        <div class="score-box">
                                            ${p.penales_local && p.penales_visitante ? `${p.goles_local} - ${p.goles_visitante} <small style="font-size: 0.5em; display:block; margin-top: -5px;">(${p.penales_local}-${p.penales_visitante} P)</small>` : scoreTxt}
                                        </div>
                                        ${p.ida_info ? `
                                            <div class="mt-2 text-white-50 fw-bold" style="font-size: 0.65rem;">
                                                IDA: ${p.ida_info.goles_este} - ${p.ida_info.goles_riva}
                                            </div>
                                            <div class="text-warning fw-bold" style="font-size: 0.7rem;">
                                                GLB: ${p.global_este} - ${p.global_riva}
                                            </div>
                                        ` : ''}
                                    </div>
                                    
                                    <!-- Visitante -->
                                    <div class="text-center px-1" style="width: 33%;">
                                        <img src="${logoVis}" class="team-logo" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                        <div class="team-name text-truncate text-white" title="${p.visitante_nombre}">${p.visitante_nombre}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
            }
            
            $('#matches-container').html(html);

            // Evento click para detalles
            $('.match-card').on('click', function() {
                verDetalle($(this));
            });
        },
        error: function() {
            console.error('Error al sincronizar resultados');
        }
    });
}

function verDetalle(card) {
    const id = card.data('id');
    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
    
    // Reset modal
    $('#modal-loading').removeClass('d-none');
    $('#modal-content-area').addClass('d-none');
    $('#match-timeline').html('');
    
    // Info básica desde el dataset
    $('#md-torneo-fase').text(`${card.data('torneo')} | ${card.data('fase')}`);
    $('#md-nombre-local').text(card.data('local'));
    $('#md-nombre-visitante').text(card.data('visitante'));
    $('#md-logo-local').attr('src', card.data('logo-l'));
    $('#md-logo-visitante').attr('src', card.data('logo-v'));

    modal.show();

    $.ajax({
        url: 'ajax/gestionar_partido.php',
        type: 'POST',
        data: { action: 'obtener_datos_partido', partido_id: id },
        success: function(resp) {
            if (resp.success) {
                $('#md-score').text(`${resp.partido.goles_local} - ${resp.partido.goles_visitante}`);
                
                let badgeClass = 'bg-secondary';
                if(resp.partido.estado === 'en_juego') badgeClass = 'bg-danger timer-blink';
                if(resp.partido.estado === 'finalizado') badgeClass = 'bg-success';
                
                $('#md-estado-badge').html(`<span class="badge ${badgeClass} text-uppercase">${resp.partido.estado}</span>`);

                // Timeline
                let timelineHtml = '';
                resp.eventos.forEach(ev => {
                    let isInverted = (ev.equipo_nombre === card.data('visitante'));
                    let icon = 'fa-futbol';
                    let color = 'text-white';
                    
                    if(ev.tipo === 'amarilla') { icon = 'fa-clone'; color = 'text-warning'; }
                    if(ev.tipo === 'roja') { icon = 'fa-clone'; color = 'text-danger'; }

                    timelineHtml += `
                    <li class="${isInverted ? 'timeline-inverted' : ''}">
                        <div class="timeline-badge"><i class="fa-solid ${icon} ${color}" style="font-size: 12px;"></i></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-white fw-bold">${ev.jugador_nombre}</h6>
                                <span class="badge bg-dark">${ev.minuto}'</span>
                            </div>
                            <div class="timeline-body mt-1">
                                <small class="text-secondary text-uppercase" style="font-size: 10px;">${ev.tipo}</small>
                            </div>
                        </div>
                    </li>`;
                });

                if(resp.eventos.length === 0) {
                    timelineHtml = '<li class="text-center text-muted py-3">No se han registrado eventos en este encuentro.</li>';
                }

                $('#match-timeline').html(timelineHtml);
                $('#modal-loading').addClass('d-none');
                $('#modal-content-area').removeClass('d-none');
            }
        }
    });
}
