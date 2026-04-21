$(document).ready(function() {
    cargarPerfil();

    function cargarPerfil() {
        $.ajax({
            url: 'ajax/jugadores.php',
            data: { action: 'perfil_completo', id: JUGADOR_ID },
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    let j = resp.jugador;
                    
                    // Datos básicos
                    $('#nombre_jugador').text(j.nombre);
                    $('#ci_jugador').text(j.ci);
                    $('#dorsal_jugador').text(j.dorsal || '--');

                    if (j.foto && j.foto !== 'default_user.png') {
                        $('#foto_jugador').attr('src', 'uploads/fotos/' + j.foto).show();
                        $('#foto_placeholder').hide();
                    } else {
                        $('#foto_jugador').hide();
                        $('#foto_placeholder').show();
                    }
                    
                    let eqLogo = j.equipo_logo && j.equipo_logo !== 'default.png' ? 'uploads/logos/' + j.equipo_logo : 'assets/img/default_team.png';

                    $('#equipo_actual_container').html(`
                        <div class="d-flex align-items-center justify-content-end">
                            <div class="text-end me-3">
                                <p class="text-muted mb-0 small text-uppercase fw-bold">Equipo Actual</p>
                                <h4 class="fw-bold mb-0 text-primary">${j.equipo_actual}</h4>
                            </div>
                            <img src="${eqLogo}" class="rounded-circle shadow-sm border" style="width: 65px; height: 65px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                        </div>
                    `);

                    // Stats
                    $('#stat_goles').text(resp.stats.goles);
                    $('#stat_amarillas').text(resp.stats.amarillas);
                    $('#stat_rojas').text(resp.stats.rojas);
                    $('#stat_transf').text(resp.stats.transf);

                    // Equipos
                    let htmlEq = '';
                    resp.equipos.forEach(eq => {
                        let logo = eq.logo && eq.logo !== 'default.png' ? 'uploads/logos/' + eq.logo : 'assets/img/default_team.png';
                        htmlEq += `
                            <li class="list-group-item d-flex align-items-center py-3">
                                <img src="${logo}" class="rounded-circle me-3 border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">
                                <span class="fw-bold text-dark">${eq.nombre}</span>
                            </li>`;
                    });
                    $('#lista_equipos').html(htmlEq || '<li class="list-group-item text-muted text-center p-4">Sin registro de clubes</li>');

                    // Transferencias
                    let htmlTr = '';
                    resp.transferencias.forEach(t => {
                        htmlTr += `
                            <div class="transfer-item mb-4 border-start border-3 border-primary ps-3 pb-2 position-relative">
                                <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -7.5px; top: 0;"></div>
                                <div class="small fw-bold text-muted">${new Date(t.fecha).toLocaleDateString()}</div>
                                <div class="fw-bold d-flex align-items-center mt-1">
                                    <span class="text-danger">${t.origen}</span>
                                    <i class="fa-solid fa-arrow-right mx-2 text-muted small"></i>
                                    <span class="text-success">${t.destino}</span>
                                </div>
                                <div class="small text-muted fst-italic">${t.torneo || 'Torneo General'}</div>
                                <div class="mt-1"><span class="badge bg-light text-dark border">Bs. ${t.monto}</span></div>
                            </div>`;
                    });
                    $('#transferencias_log').html(htmlTr || '<p class="text-muted text-center py-4 mb-0">No se registran transferencias.</p>');

                    // Goles
                    let htmlG = '';
                    resp.goles.forEach(g => {
                        htmlG += `
                            <tr>
                                <td>${new Date(g.fecha).toLocaleDateString()}</td>
                                <td><small class="badge bg-light text-dark border">${g.torneo}</small></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span class="small">${g.local}</span>
                                        <span class="badge bg-dark mx-2">${g.goles_local} - ${g.goles_visitante}</span>
                                        <span class="small">${g.visitante}</span>
                                    </div>
                                </td>
                                <td class="text-center fw-bold"><i class="fa-solid fa-futbol text-primary me-1"></i> ${g.minuto}'</td>
                            </tr>`;
                    });
                    $('#tabla_goles_jugador tbody').html(htmlG || '<tr><td colspan="4" class="text-center py-4 text-muted">Aún no ha marcado goles.</td></tr>');

                    // Sanciones
                    let htmlS = '';
                    resp.sanciones.forEach(s => {
                        let color = s.tipo == 'amarilla' ? 'warning' : 'danger';
                        let badgePago = s.pago == 'pagado' ? '<span class="badge bg-success">Pagado</span>' : '<span class="badge bg-danger">Pendiente</span>';
                        let partidoStr = `${s.local_nombre} vs ${s.visitante_nombre}`;
                        htmlS += `
                            <tr>
                                <td>${new Date(s.fecha).toLocaleDateString()}</td>
                                <td><small class="fw-bold text-muted">${partidoStr}</small></td>
                                <td><i class="fa-solid fa-square text-${color} border shadow-sm"></i> <span class="text-uppercase small fw-bold">${s.tipo}</span> ${s.minuto ? ' - '+s.minuto+"'" : ''}</td>
                                <td><small>${s.torneo}</small></td>
                                <td class="fw-bold">Bs. ${s.monto || '0.00'}</td>
                                <td>${badgePago}</td>
                            </tr>`;
                    });
                    $('#tabla_sanciones_jugador tbody').html(htmlS || '<tr><td colspan="6" class="text-center py-4 text-muted">No tiene sanciones registradas.</td></tr>');
                }
            }
        });
    }
});

window.triggerFileUpload = function() {
    $('#input_foto').click();
};

window.uploadFoto = function(input) {
    if (input.files && input.files[0]) {
        let formData = new FormData();
        formData.append('foto', input.files[0]);
        formData.append('id_jugador', JUGADOR_ID);
        formData.append('action', 'actualizar_foto');

        Swal.fire({
            title: 'Subiendo...',
            text: 'Cargando nueva foto del jugador',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'ajax/jugadores.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Listo!',
                        text: resp.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Actualizar imagen en la UI
                    $('#foto_jugador').attr('src', 'uploads/fotos/' + resp.foto).show();
                    $('#foto_placeholder').addClass('d-none');
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo subir la imagen', 'error');
            }
        });
    }
};
