$(document).ready(function() {
    let chartTarjetas = null;

    cargarReportes();

    $('#filtro_torneo_reportes').change(function() {
        cargarReportes();
    });

    function cargarReportes() {
        let torneo_id = $('#filtro_torneo_reportes').val();
        cargarGoleadores(torneo_id);
        cargarGolesEquipos(torneo_id);
        cargarTarjetas(torneo_id);
        cargarFairPlay(torneo_id);
    }

    function cargarGoleadores(torneo_id) {
        $.ajax({
            url: 'ajax/reportes.php',
            data: { action: 'goleadores', torneo_id: torneo_id },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    let html = '';
                    let labels = [];
                    let values = [];

                    resp.data.forEach((item, index) => {
                        let playerImg = (item.jugador_foto && item.jugador_foto !== 'default_user.png')
                            ? `uploads/fotos/${item.jugador_foto}`
                            : `assets/img/default_user.png`;
                        let teamImg = (item.equipo_logo && item.equipo_logo !== 'default.png')
                            ? `uploads/logos/${item.equipo_logo}`
                            : `assets/img/default_team.png`;
                        html += `
                            <tr>
                                <td class="fw-bold">#${index + 1}</td>
                                <td>
                                    <a href="jugador_detalle.php?id=${item.jugador_id}" class="text-decoration-none text-dark d-flex align-items-center hover-primary">
                                        <img src="${playerImg}" class="rounded-circle me-2 border" style="width:32px;height:32px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_user.png'">
                                        <span class="fw-bold">${item.jugador}</span>
                                    </a>
                                </td>
                                <td>
                                    <img src="${teamImg}" class="rounded-circle me-1" style="width:20px;height:20px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                    <span class="small">${item.equipo}</span>
                                </td>
                                <td class="text-center"><span class="badge bg-primary fs-6">${item.goles}</span></td>
                            </tr>`;
                        
                        if (index < 5) {
                            labels.push(item.jugador);
                            values.push(item.goles);
                        }
                    });

                    if(html === '') html = '<tr><td colspan="4" class="text-center py-4 text-muted">No hay goles registrados.</td></tr>';
                    $('#tablaGoleadores tbody').html(html);
                }
            }
        });
    }

    function cargarGolesEquipos(torneo_id) {
        $.ajax({
            url: 'ajax/reportes.php',
            data: { action: 'goles_equipos', torneo_id: torneo_id },
            dataType: 'json',
            success: function(resp) {
                let lista = $('#rankingGolesEquipos');
                if (!resp.success || resp.data.length === 0) {
                    lista.html('<li class="list-group-item text-center text-muted py-4">Sin datos de goles.</li>');
                    return;
                }
                let maxGoles = resp.data[0].goles;
                let medals = ['🥇','🥈','🥉'];
                let html = '';
                resp.data.forEach((item, i) => {
                    let logo = (item.equipo_logo && item.equipo_logo !== 'default.png')
                        ? `uploads/logos/${item.equipo_logo}` : `assets/img/default_team.png`;
                    let pct = Math.round((item.goles / maxGoles) * 100);
                    let barColor = i === 0 ? 'bg-warning' : (i === 1 ? 'bg-secondary' : (i === 2 ? 'bg-danger' : 'bg-primary'));
                    let medal = medals[i] || `<span class="text-muted small">#${i+1}</span>`;
                    html += `
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center mb-1">
                                <span class="me-2" style="font-size:1.1rem;">${medal}</span>
                                <img src="${logo}" class="rounded-circle me-2 border" style="width:32px;height:32px;object-fit:cover;" onerror="this.onerror=null;this.src='assets/img/default_team.png'">
                                <span class="fw-bold flex-grow-1">${item.equipo}</span>
                                <span class="badge bg-dark ms-2">${item.goles} ⚽</span>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar ${barColor}" style="width:${pct}%;"></div>
                            </div>
                        </li>`;
                });
                lista.html(html);
            }
        });
    }

    function cargarTarjetas(torneo_id) {
        $.ajax({
            url: 'ajax/reportes.php',
            data: { action: 'tarjetas', torneo_id: torneo_id },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    let html = '';
                    let sumAmarillas = 0;
                    let sumRojas = 0;

                    resp.data.forEach((item) => {
                        let playerImg = (item.jugador_foto && item.jugador_foto !== 'default_user.png')
                            ? `uploads/fotos/${item.jugador_foto}`
                            : `assets/img/default_user.png`;
                        let teamImg = (item.equipo_logo && item.equipo_logo !== 'default.png')
                            ? `uploads/logos/${item.equipo_logo}`
                            : `assets/img/default_team.png`;
                        html += `
                            <tr>
                                <td>
                                    <a href="jugador_detalle.php?id=${item.jugador_id}" class="text-decoration-none text-dark d-flex align-items-center hover-primary">
                                        <img src="${playerImg}" class="rounded-circle me-2 border" style="width:32px;height:32px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_user.png'">
                                        <span class="fw-bold">${item.jugador}</span>
                                    </a>
                                </td>
                                <td>
                                    <img src="${teamImg}" class="rounded-circle me-1" style="width:20px;height:20px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                    <span class="small">${item.equipo}</span>
                                </td>
                                <td class="text-center text-warning fw-bold">${item.amarillas}</td>
                                <td class="text-center text-danger fw-bold">${item.rojas}</td>
                                <td class="text-center"><span class="badge bg-dark">${item.indice} pts</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info" onclick="verDetalleTarjetas(${item.jugador_id}, '${item.jugador.replace(/'/g, "\\'")}', '${item.equipo.replace(/'/g, "\\'")}', '${playerImg}')" title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>`;
                        sumAmarillas += parseInt(item.amarillas);
                        sumRojas += parseInt(item.rojas);
                    });

                    if(html === '') html = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay tarjetas registradas.</td></tr>';
                    $('#tablaTarjetas tbody').html(html);

                    renderChartTarjetas(sumAmarillas, sumRojas);
                }
            }
        });
    }

    function renderChartTarjetas(amarillas, rojas) {
        const ctx = document.getElementById('chartTarjetas').getContext('2d');
        if (chartTarjetas) chartTarjetas.destroy();

        if (amarillas === 0 && rojas === 0) {
            // Si no hay datos, no renderizar o mostrar algo vacío
            return;
        }

        chartTarjetas = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Amarillas', 'Rojas'],
                datasets: [{
                    data: [amarillas, rojas],
                    backgroundColor: ['#ffc107', '#dc3545'],
                    hoverOffset: 4
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function cargarFairPlay(torneo_id) {
        $.ajax({
            url: 'ajax/reportes.php',
            data: { action: 'fair_play', torneo_id: torneo_id },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    let html = '';
                    resp.data.forEach((item, index) => {
                        let badge = '';
                        if (index === 0 && item.puntos_castigo > 0) badge = '<span class="badge bg-success ms-2"><i class="fa-solid fa-award"></i> Líder</span>';
                        if (item.puntos_castigo == 0) badge = '<span class="badge bg-info ms-2">Limpio</span>';

                        html += `
                            <tr>
                                <td class="text-start fw-bold">
                                    <img src="uploads/logos/${item.equipo_logo || 'default.png'}" class="rounded-circle me-2" style="width:30px;height:30px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                    ${item.equipo} ${badge}
                                </td>
                                <td class="text-warning fw-bold">${item.amarillas || 0}</td>
                                <td class="text-danger fw-bold">${item.rojas || 0}</td>
                                <td class="fw-bold fs-5">${item.puntos_castigo || 0}</td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar ${item.puntos_castigo > 10 ? 'bg-danger' : (item.puntos_castigo > 5 ? 'bg-warning' : 'bg-success')}" 
                                             role="progressbar" style="width: ${Math.min(item.puntos_castigo * 5, 100)}%"></div>
                                    </div>
                                </td>
                            </tr>`;
                    });

                    if(html === '') html = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay datos de equipos.</td></tr>';
                    $('#tablaFairPlay tbody').html(html);
                }
            }
        });
    }
});

window.cargarCampeones = function() {
    let torneo_id = $('#filtro_torneo_reportes').val();
    let container = $('#listaCampeones');
    container.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Cargando campeones...</div>');

    $.ajax({
        url: 'ajax/reportes.php',
        data: { action: 'campeones', torneo_id: torneo_id },
        dataType: 'json',
        success: function(resp) {
            if (!resp.success || resp.data.length === 0) {
                container.html('<div class="text-center text-muted py-5"><i class="fa-solid fa-trophy fa-3x mb-3 opacity-25"></i><h5>No hay torneos finalizados aún.</h5><p class="small">Los campeones aparecerán aquí cuando un torneo sea marcado como "finalizado".</p></div>');
                return;
            }

            let html = '<div class="row g-4 p-3">';
            resp.data.forEach((item, i) => {
                let logo = (item.logo && item.logo !== 'default.png')
                    ? `uploads/logos/${item.logo}` : `assets/img/default_team.png`;
                let borderColor = i === 0 ? 'border-warning' : (i === 1 ? 'border-secondary' : 'border-danger');
                let badgeColor  = i === 0 ? 'bg-warning text-dark' : (i === 1 ? 'bg-secondary' : 'bg-danger');
                let crownColor  = i === 0 ? 'text-warning' : (i === 1 ? 'text-secondary' : 'text-danger');
                let edition     = i === 0 ? 'Último Campeón' : `Edición #${resp.data.length - i}`;

                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 ${borderColor} shadow-sm text-center position-relative overflow-hidden">
                            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background: radial-gradient(circle, gold 0%, transparent 70%); pointer-events:none;"></div>
                            <div class="card-body py-4">
                                <i class="fa-solid fa-crown fa-2x ${crownColor} mb-3"></i>
                                <img src="${logo}" class="rounded-circle border border-3 ${borderColor} shadow mb-3"
                                     style="width:90px;height:90px;object-fit:cover;" 
                                     onerror="this.onerror=null;this.src='assets/img/default_team.png'">
                                <h4 class="fw-bold mb-1">${item.equipo}</h4>
                                <p class="text-muted small mb-3">${item.torneo}</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <span class="badge ${badgeColor} px-3 py-2">
                                        <i class="fa-solid fa-star me-1"></i>${item.puntos} pts
                                    </span>
                                    <span class="badge bg-light text-dark border px-3 py-2">
                                        DG: ${item.diferencia > 0 ? '+' : ''}${item.diferencia}
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-light border-0">
                                <span class="badge ${badgeColor} small">${edition}</span>
                            </div>
                        </div>
                    </div>`;
            });
            html += '</div>';
            container.html(html);
        }
    });
};

window.verDetalleTarjetas = function(jugador_id, nombre, equipo, foto) {
    let torneo_id = $('#filtro_torneo_reportes').val();
    
    $('#modal_jugador_nombre').text(nombre);
    $('#modal_equipo_nombre').html(`<i class="fa-solid fa-shield-halved me-1"></i> ${equipo}`);
    $('#modal_jugador_foto').attr('src', foto);
    $('#tablaDetalleTarjetas tbody').html('<tr><td colspan="4" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted me-2"></i>Cargando detalles...</td></tr>');
    
    let modal = new bootstrap.Modal(document.getElementById('modalDetalleTarjetas'));
    modal.show();

    $.ajax({
        url: 'ajax/reportes.php',
        data: { action: 'detalles_tarjetas', torneo_id: torneo_id, jugador_id: jugador_id },
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                let html = '';
                resp.data.forEach(item => {
                    let color = item.tipo == 'amarilla' ? 'warning' : 'danger';
                    let partidoStr = `${item.local_nombre} vs ${item.visitante_nombre}`;
                    html += `
                        <tr>
                            <td>${new Date(item.fecha).toLocaleDateString()}</td>
                            <td>
                                <div class="fw-bold small text-dark">${partidoStr}</div>
                                <div class="text-muted" style="font-size:0.75rem;">${item.torneo}</div>
                            </td>
                            <td class="text-center"><i class="fa-solid fa-square text-${color} border shadow-sm fa-lg" title="${item.tipo.toUpperCase()}"></i></td>
                            <td class="text-center fw-bold">${item.minuto ? item.minuto + "'" : '--'}</td>
                        </tr>`;
                });
                if (html === '') html = '<tr><td colspan="4" class="text-center py-4 text-muted">No se encontraron detalles.</td></tr>';
                $('#tablaDetalleTarjetas tbody').html(html);
            } else {
                $('#tablaDetalleTarjetas tbody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Error al cargar datos.</td></tr>');
            }
        },
        error: function() {
            $('#tablaDetalleTarjetas tbody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Error de conexión.</td></tr>');
        }
    });
};
