$(document).ready(function() {
    cargarPerfilEquipo();

    function cargarPerfilEquipo() {
        $.ajax({
            url: 'ajax/equipos.php',
            data: { action: 'perfil_completo', id: EQUIPO_ID },
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    let eq = resp.equipo;
                    
                    // Datos básicos
                    $('#nombre_equipo').text(eq.nombre);
                    $('#fecha_registro_equipo').text(new Date(eq.fecha_registro).toLocaleDateString());

                    if (eq.logo && eq.logo !== 'default.png') {
                        $('#foto_equipo').attr('src', 'uploads/logos/' + eq.logo).show();
                        $('#foto_placeholder').hide();
                    } else {
                        $('#foto_equipo').hide();
                        $('#foto_placeholder').show();
                    }

                    // Stats
                    $('#stat_torneos').text(resp.stats.torneos);
                    $('#stat_jugadores').text(resp.stats.jugadores);
                    $('#stat_pj').text(resp.stats.pj);
                    $('#stat_gf').text(resp.stats.gf);
                    $('#stat_gc').text(resp.stats.gc);

                    // Palmarés
                    let htmlP = '';
                    resp.palmares.forEach(p => {
                        htmlP += `
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <i class="fa-solid ${p.icon} text-${p.color} me-2 fs-5"></i>
                                    <span class="fw-bold">${p.titulo}</span>
                                </div>
                                <span class="badge bg-light text-dark border">${p.torneo}</span>
                            </li>`;
                    });
                    $('#lista_palmares').html(htmlP || '<li class="list-group-item text-muted text-center p-4">No tiene títulos o medallas registrados.</li>');

                    // Historial de Torneos
                    let htmlH = '';
                    if (resp.historial_torneos && resp.historial_torneos.length > 0) {
                        resp.historial_torneos.forEach(h => {
                            let badgeColor = h.es_campeon ? 'bg-warning text-dark' : 'bg-secondary';
                            let icon = h.es_campeon ? '<i class="fa-solid fa-crown text-warning me-2"></i>' : '<i class="fa-solid fa-futbol text-muted me-2"></i>';
                            
                            htmlH += `
                                <li class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold fs-6">${icon}${h.torneo}</span>
                                    </div>
                                    <div class="small text-muted ms-4">
                                        Fase máxima alcanzada: <span class="badge ${badgeColor}">${h.fase_alcanzada}</span>
                                    </div>
                                </li>`;
                        });
                    }
                    $('#lista_historial_torneos').html(htmlH || '<li class="list-group-item text-muted text-center p-4">Aún no hay historial de participación.</li>');

                    // Plantilla
                    let htmlJ = '';
                    resp.jugadores.forEach(j => {
                        let f = (j.foto && j.foto !== 'default_user.png') ? `<img src="uploads/fotos/${j.foto}" class="rounded-circle me-2 object-fit-cover shadow-sm border" style="width:30px;height:30px;">` : `<div class="bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:30px;height:30px;"><i class="fa-solid fa-user small"></i></div>`;
                        let d = j.dorsal ? `<span class="badge bg-dark rounded-pill">${j.dorsal}</span>` : '';
                        htmlJ += `
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <a href="jugador_detalle.php?id=${j.id}" class="text-decoration-none d-flex align-items-center text-dark hover-primary flex-grow-1">
                                    ${f} <span class="fw-bold">${j.nombre}</span>
                                </a>
                                ${d}
                            </li>`;
                    });
                    $('#lista_jugadores').html(htmlJ || '<li class="list-group-item text-muted text-center p-4">El equipo no tiene jugadores registrados.</li>');

                    // Partidos
                    let htmlPartidos = '';
                    resp.partidos.forEach(p => {
                        let lLogo = p.local_logo && p.local_logo !== 'default.png' ? 'uploads/logos/' + p.local_logo : 'assets/img/default_team.png';
                        let vLogo = p.visitante_logo && p.visitante_logo !== 'default.png' ? 'uploads/logos/' + p.visitante_logo : 'assets/img/default_team.png';
                        
                        let isWin = false;
                        let isDraw = false;
                        if (p.goles_local == p.goles_visitante) {
                            isDraw = true;
                        } else if ((p.equipo_local_id == EQUIPO_ID && p.goles_local > p.goles_visitante) || 
                                   (p.equipo_visitante_id == EQUIPO_ID && p.goles_visitante > p.goles_local)) {
                            isWin = true;
                        }

                        let resColor = isWin ? 'success' : (isDraw ? 'secondary' : 'danger');

                        htmlPartidos += `
                            <tr>
                                <td><small>${new Date(p.fecha).toLocaleDateString()}</small></td>
                                <td><span class="badge bg-light text-dark border">${p.torneo}</span></td>
                                <td class="text-end fw-bold ${p.equipo_local_id == EQUIPO_ID ? 'text-primary' : ''}">
                                    ${p.local} <img src="${lLogo}" class="rounded-circle ms-1" style="width:20px;height:20px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-${resColor} px-2 py-1 fs-6">${p.goles_local} - ${p.goles_visitante}</span>
                                </td>
                                <td class="fw-bold ${p.equipo_visitante_id == EQUIPO_ID ? 'text-primary' : ''}">
                                    <img src="${vLogo}" class="rounded-circle me-1" style="width:20px;height:20px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'"> ${p.visitante}
                                </td>
                            </tr>`;
                    });
                    $('#tabla_partidos_equipo tbody').html(htmlPartidos || '<tr><td colspan="5" class="text-center py-4 text-muted">Aún no ha jugado partidos.</td></tr>');
                }
            }
        });
    }
});
