$(document).ready(function () {
    $('#filtro_torneo').change(function () {
        let torneo_id = $(this).val();
        if (torneo_id) {
            cargarEquiposFiltro(torneo_id);
            cargarResultados();
        } else {
            $('#filtro_equipo').html('<option value="">-- Todos los equipos --</option>');
            $('#contenedorResultados').html('<div class="text-center py-5 text-muted"><i class="fa-solid fa-futbol fa-3x mb-3"></i><h4>Seleccione un torneo para ver los resultados</h4></div>');
        }
    });

    $('#formFiltros').submit(function (e) {
        e.preventDefault();
        cargarResultados();
    });

    function cargarEquiposFiltro(torneo_id) {
        $.ajax({
            url: 'ajax/resultados.php',
            data: { action: 'listar_filtros', torneo_id: torneo_id },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    let options = '<option value="">-- Todos los equipos --</option>';
                    resp.equipos.forEach(e => {
                        options += `<option value="${e.id}">${e.nombre}</option>`;
                    });
                    $('#filtro_equipo').html(options);
                }
            }
        });
    }

    function cargarResultados() {
        let container = $('#contenedorResultados');
        container.html('<div class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-3x text-primary"></i><br>Cargando resultados...</div>');

        let formData = $('#formFiltros').serialize() + '&action=listar';

        $.ajax({
            url: 'ajax/resultados.php',
            data: formData,
            dataType: 'json',
            success: function (resp) {
                if (resp.success && resp.data.length > 0) {
                    renderizarResultados(resp.data);
                } else {
                    container.html('<div class="alert alert-info text-center py-4"><i class="fa-solid fa-circle-info me-2"></i> No se encontraron partidos jugados o programados para los filtros seleccionados.</div>');
                }
            }
        });
    }

    function renderizarResultados(data) {
        let container = $('#contenedorResultados');
        container.empty();

        // Agrupar por Grupo
        let grupos = {};
        data.forEach(p => {
            if (!grupos[p.nombre_grupo]) grupos[p.nombre_grupo] = [];
            grupos[p.nombre_grupo].push(p);
        });

        // Para cada Grupo
        Object.keys(grupos).sort().forEach(nombreGrupo => {
            let matches = grupos[nombreGrupo];
            let templateG = document.getElementById('templateGrupo').content.cloneNode(true);
            templateG.querySelector('.nombre-grupo').textContent = nombreGrupo;
            let contenedorFechas = templateG.querySelector('.contenedor-fechas');

            // Agrupar por Fecha dentro del grupo
            let fechas = {};
            matches.forEach(p => {
                if (!fechas[p.fecha_formateada]) fechas[p.fecha_formateada] = [];
                fechas[p.fecha_formateada].push(p);
            });

            // Para cada Fecha
            Object.keys(fechas).sort((a, b) => {
                // Ordenar por fecha real para que sea cronológico descendente
                return new Date(fechas[b][0].fecha) - new Date(fechas[a][0].fecha);
            }).forEach(textoFecha => {
                let partidosFecha = fechas[textoFecha];
                let templateF = document.getElementById('templateFecha').content.cloneNode(true);
                templateF.querySelector('.texto-fecha').textContent = textoFecha;
                let contenedorPartidos = templateF.querySelector('.contenedor-partidos');

                // Para cada Partido
                partidosFecha.forEach(p => {
                    let templateP = document.getElementById('templatePartido').content.cloneNode(true);
                    let card = templateP.querySelector('.match-card');

                    card.onclick = () => { abrirModalDetalle(p.id); };

                    templateP.querySelector('.local-nombre').textContent = p.local_nombre;
                    templateP.querySelector('.local-logo').src = (p.local_logo && p.local_logo !== 'default.png') ? `uploads/logos/${p.local_logo}` : 'assets/img/default_team.png';
                    templateP.querySelector('.visitante-nombre').textContent = p.visitante_nombre;
                    templateP.querySelector('.visitante-logo').src = (p.visitante_logo && p.visitante_logo !== 'default.png') ? `uploads/logos/${p.visitante_logo}` : 'assets/img/default_team.png';

                    let marcadorText = 'vs';
                    if (p.estado === 'walkover') {
                        marcadorText = 'W/O';
                        let badgeGanador = '<span class="badge bg-success mt-1 small" style="font-size: 0.6rem;"><i class="fa-solid fa-trophy"></i> GANADOR</span>';
                        if (p.goles_local > p.goles_visitante) {
                            templateP.querySelector('.container-ganador-local').innerHTML = badgeGanador;
                        } else {
                            templateP.querySelector('.container-ganador-visitante').innerHTML = badgeGanador;
                        }
                    } else if (p.estado !== 'programado') {
                        marcadorText = `${p.goles_local} - ${p.goles_visitante}`;
                        // Mostrar penales si existen
                        if (p.penales_local !== null && p.penales_local !== "" && p.penales_visitante !== null && p.penales_visitante !== "") {
                            // Cambiado a un span pequeño para que no rompa la línea
                            marcadorText += ` <span style="font-size: 0.5em;">(${p.penales_local}-${p.penales_visitante} P)</span>`;
                        }
                    }

                    templateP.querySelector('.marcador').innerHTML = marcadorText;
                    templateP.querySelector('.hora-partido').innerHTML = `<i class="fa-regular fa-clock me-1"></i> ${p.hora_formateada}`;
                    templateP.querySelector('.fase-nombre').textContent = p.fase;

                    // Si tiene info de IDA, agregarla al marcador
                    if (p.ida_info) {
                        let globalHtml = `
                            <div class="mt-2 border-top pt-2" style="font-size: 0.7rem; opacity: 0.9;">
                                <div class="text-secondary fw-bold">IDA: ${p.ida_info.goles_este} - ${p.ida_info.goles_riva}</div>
                                <div class="text-warning fw-bold mt-1" style="font-size: 0.75rem;">GLB: ${p.global_este} - ${p.global_riva}</div>
                            </div>
                        `;
                        templateP.querySelector('.marcador-container').insertAdjacentHTML('beforeend', globalHtml);
                    }

                    let statusBadge = templateP.querySelector('.status-badge');
                    if (p.estado === 'en_juego') {
                        statusBadge.className = 'badge bg-danger shadow-sm timer-blink';
                        statusBadge.innerHTML = '<i class="fa-solid fa-circle text-white me-1" style="font-size: 8px;"></i> EN JUEGO';
                    } else if (p.estado === 'finalizado') {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'FINALIZADO';
                    } else if (p.estado === 'walkover') {
                        statusBadge.className = 'badge bg-dark';
                        statusBadge.textContent = 'WALKOVER';
                    } else if (p.estado === 'programado') {
                        statusBadge.className = 'badge bg-secondary opacity-75';
                        statusBadge.textContent = 'PROGRAMADO';
                    }

                    // Indicador de Impugnación
                    if (p.tiene_impugnacion > 0) {
                        let colorImp = p.estado_impugnacion === 'pendiente' ? 'text-warning' : (p.estado_impugnacion === 'aceptada' ? 'text-success' : 'text-danger');
                        let htmlImp = `<div class="mt-2 small fw-bold ${colorImp}"><i class="fa-solid fa-gavel"></i> IMPUGNADO (${p.estado_impugnacion.toUpperCase()})</div>`;
                        statusBadge.parentElement.insertAdjacentHTML('beforeend', htmlImp);
                    }

                    contenedorPartidos.appendChild(templateP);
                });

                contenedorFechas.appendChild(templateF);
            });

            container.append(templateG);
        });
    }

    window.abrirModalDetalle = function (partidoId) {
        const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));

        // Mostrar cargador en el modal
        $('#det_timeline').html('<div class="text-center py-5"><i class="fa-solid fa-sync fa-spin fa-2x text-muted"></i></div>');
        $('.aggregate-info').remove(); // Limpiar badges previos si existen

        $.ajax({
            url: 'ajax/resultados.php',
            data: { action: 'detalle_partido', id: partidoId },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    let p = resp.partido;

                    $('#det_torneo_fase').text(`${p.torneo_nombre} - ${p.fase}`);
                    $('#det_local_nombre').text(p.local_nombre);
                    $('#det_visitante_nombre').text(p.visitante_nombre);
                    $('#det_local_logo').attr('src', (p.local_logo && p.local_logo !== 'default.png') ? `uploads/logos/${p.local_logo}` : 'assets/img/default_team.png');
                    $('#det_visitante_logo').attr('src', (p.visitante_logo && p.visitante_logo !== 'default.png') ? `uploads/logos/${p.visitante_logo}` : 'assets/img/default_team.png');

                    let marcador = `${p.goles_local} - ${p.goles_visitante}`;
                    if (p.estado === 'walkover') marcador = 'W/O';
                    else if (p.estado !== 'programado') {
                        // Penales en modal con fuente más pequeña
                        if (p.penales_local !== null && p.penales_local !== "" && p.penales_visitante !== null && p.penales_visitante !== "") {
                            marcador += ` <span style="font-size: 0.5em; vertical-align: middle; margin-left: 10px;">(${p.penales_local}-${p.penales_visitante} P)</span>`;
                        }
                    }
                    $('#det_marcador').html(marcador);

                    // Si tiene info de IDA / GLOBAL, mostrar bajo el marcador
                    let aggregateHtml = '';
                    if (p.ida_info) {
                        aggregateHtml = `
                            <div class="mt-2 animate__animated animate__fadeIn aggregate-info">
                                <span class="badge bg-secondary me-1">IDA: ${p.ida_info.goles_este}-${p.ida_info.goles_riva}</span>
                                <span class="badge bg-primary" >GLB: ${p.ida_info.global_este}-${p.ida_info.global_riva}</span>
                            </div>
                        `;
                    }
                    if (aggregateHtml) {
                        $('#det_marcador').after(aggregateHtml);
                    }

                    if (p.estado === 'en_juego') estadoHtml = '<span class="badge bg-danger timer-blink">EN JUEGO</span>';
                    else if (p.estado === 'finalizado') estadoHtml = '<span class="badge bg-success">FINALIZADO</span>';
                    else if (p.estado === 'walkover') estadoHtml = '<span class="badge bg-dark">WALKOVER</span>';
                    else if (p.estado === 'programado') estadoHtml = '<span class="badge bg-secondary opacity-75">PROGRAMADO</span>';
                    $('#det_estado').html(estadoHtml);

                    $('#det_fecha').text(new Date(p.fecha).toLocaleDateString());
                    $('#det_hora').text(p.hora.substring(0, 5));

                    // Línea de tiempo
                    let timeline = $('#det_timeline');
                    timeline.empty();

                    if (resp.eventos.length > 0) {
                        resp.eventos.forEach(ev => {
                            let icon = '<i class="fa-solid fa-futbol text-dark"></i>';
                            if (ev.tipo === 'amarilla') icon = '<i class="fa-solid fa-clone text-warning"></i>';
                            if (ev.tipo === 'roja') icon = '<i class="fa-solid fa-clone text-danger"></i>';

                            let alignClass = (ev.equipo_id == p.equipo_local_id) ? 'text-start' : 'text-end';
                            let item = `
                                <div class="timeline-item ${ev.tipo}">
                                    <div class="d-flex align-items-center ${ev.equipo_id == p.equipo_visitante_id ? 'flex-row-reverse' : ''}">
                                        <div class="timeline-min">${ev.minuto}'</div>
                                        <div class="timeline-icon">${icon}</div>
                                        <div class="flex-grow-1 ${ev.equipo_id == p.equipo_visitante_id ? 'text-end me-3' : 'ms-3'}">
                                            <div class="fw-bold small">${ev.jugador_nombre}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">${ev.tipo.toUpperCase()} - ${ev.equipo_id == p.equipo_local_id ? p.local_nombre : p.visitante_nombre}</div>
                                        </div>
                                    </div>
                                </div>`;
                            timeline.append(item);
                        });
                    } else {
                        timeline.html('<div class="text-center text-muted py-4 small">No hay eventos registrados para este partido.</div>');
                    }

                    // Mostrar Información de Impugnación si existe
                    let panelImp = $('#det_impugnacion');
                    if (resp.impugnacion) {
                        let imp = resp.impugnacion;
                        let color = imp.estado === 'pendiente' ? 'warning' : (imp.estado === 'aceptada' ? 'success' : 'danger');
                        let badgeColor = imp.estado === 'pendiente' ? 'bg-warning text-dark' : (imp.estado === 'aceptada' ? 'bg-success' : 'bg-danger');

                        let htmlImp = `
                            <div class="alert alert-${color} border-0 shadow-sm mb-0">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-gavel me-1"></i> PARTIDO IMPUGNADO <span class="badge ${badgeColor} ms-1">${imp.estado.toUpperCase()}</span></h6>
                                <p class="small mb-2"><strong>Denunciante:</strong> ${imp.equipo_denunciante}</p>
                                <p class="small mb-2"><strong>Motivo:</strong> ${imp.motivo}</p>
                                ${imp.estado !== 'pendiente' ? `
                                    <hr class="my-2">
                                    <p class="small mb-1"><strong>Resolución:</strong> ${imp.resolucion || 'N/A'}</p>
                                    <ul class="ps-3 mb-0 small fw-bold">
                                        ${imp.estado === 'aceptada' ? `
                                            ${imp.puntos_castigo > 0 ? `<li>Deducción de ${imp.puntos_castigo} puntos al oponente.</li>` : ''}
                                            ${imp.monto_castigo > 0 ? `<li>Sanción económica de Bs ${imp.monto_castigo} al oponente.</li>` : ''}
                                            ${imp.jugador_castigo_id ? `<li>Castigo a Jugador: ${imp.jugador_castigo_nombre} (${imp.jugador_castigo_detalle})</li>` : ''}
                                        ` : `
                                            <li>Impugnación rechazada. Multa aplicada al denunciante.</li>
                                        `}
                                    </ul>
                                ` : '<p class="small italic text-muted mb-0">En evaluación por el comité.</p>'}
                            </div>`;
                        panelImp.html(htmlImp).removeClass('d-none');
                    } else {
                        panelImp.addClass('d-none').html('');
                    }

                    modal.show();
                }
            }
        });
    }
});
