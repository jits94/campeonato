$(document).ready(function() {
    let t_id = $('#filtro_torneo_pos').val();
    
    if(t_id) {
        cargarPosiciones(t_id);
    } else {
        $('#empty-state').removeClass('d-none');
    }

    $('#filtro_torneo_pos').change(function() {
        let val = $(this).val();
        if(val) {
            cargarPosiciones(val);
        } else {
            $('#contenedor-posiciones').html('');
            $('#empty-state').removeClass('d-none');
        }
    });

    function cargarPosiciones(id) {
        $('#empty-state').addClass('d-none');
        $('#contenedor-posiciones').html('');
        $('#loader-posiciones').removeClass('d-none');
        
        // Obtener el tipo de torneo del option seleccionado
        let tipo = $('#filtro_torneo_pos option:selected').data('tipo');

        $.ajax({
            url: 'ajax/posiciones.php',
            type: 'GET',
            data: { torneo_id: id, tipo: tipo },
            dataType: 'json',
            success: function(resp) {
                $('#loader-posiciones').addClass('d-none');
                
                if(!resp.success) {
                    $('#contenedor-posiciones').html(`<div class="alert alert-warning">${resp.message}</div>`);
                    return;
                }

                if(tipo === 'todos_contra_todos') {
                    // Solo una tabla general
                    renderTabla('Clasificación General', resp.data);
                } else if(tipo === 'fase_grupos') {
                    // Viene un objeto con las tablas de cada grupo
                    let grupos = resp.data;
                    for (const [nombreGrupo, equipos] of Object.entries(grupos)) {
                        renderTabla(nombreGrupo, equipos);
                    }
                }
            },
            error: function() {
                $('#loader-posiciones').addClass('d-none');
                $('#contenedor-posiciones').html(`<div class="alert alert-danger">Error al cargar posiciones.</div>`);
            }
        });

        cargarLlavesEliminatorias(id);
    }

    function cargarLlavesEliminatorias(id) {
        $('#contenedor-llaves').html('<div class="w-100 text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x"></i> Construyendo llaves...</div>');
        
        $.ajax({
            url: 'ajax/posiciones.php',
            type: 'GET',
            data: { action: 'obtener_llaves', torneo_id: id },
            dataType: 'json',
            success: function(resp) {
                if(!resp.success || !resp.data || Object.keys(resp.data).length === 0) {
                    $('#contenedor-llaves').html('<div class="w-100 text-center py-5 text-muted"><i class="fa-solid fa-sitemap fa-3x opacity-25 mb-3"></i><br>Aún no se ha generado la fase eliminatoria en este torneo o los partidos no tienen asignada una "llave".</div>');
                    return;
                }

                let bracketData = resp.data;
                let html = '';

                // Crear columnas flex por fase
                for (const [fase, llavesObj] of Object.entries(bracketData)) {
                    html += `<div class="bracket-col me-4" style="min-width: 320px;">
                                <h5 class="text-center text-uppercase fw-bold text-success mb-4 p-2 bg-success bg-opacity-10 border border-success rounded">${fase}</h5>`;
                    
                    for (const [nombreLlave, partidos] of Object.entries(llavesObj)) {
                        // Renderizar tarjeta de llave global
                        // Equipos implicados
                        let eq1Info = { id: partidos[0].equipo_local_id, nombre: partidos[0].local_nombre, logo: partidos[0].local_logo, gl: 0, pen: 0 };
                        let eq2Info = { id: partidos[0].equipo_visitante_id, nombre: partidos[0].visitante_nombre, logo: partidos[0].visitante_logo, gl: 0, pen: 0 };
                        
                        let estaFinalizado = true;

                        partidos.forEach(p => {
                            if(p.estado !== 'finalizado' && p.estado !== 'walkover') estaFinalizado = false;
                            
                            // Acumular Goles Directos
                            if(p.equipo_local_id == eq1Info.id) eq1Info.gl += parseInt(p.goles_local||0);
                            else eq2Info.gl += parseInt(p.goles_local||0);

                            if(p.equipo_visitante_id == eq1Info.id) eq1Info.gl += parseInt(p.goles_visitante||0);
                            else eq2Info.gl += parseInt(p.goles_visitante||0);

                            // IMPORTANTE: Capturar penales del partido donde ocurrieron (usualmente la vuelta)
                            if (p.penales_local !== null && p.penales_local !== "" && p.penales_visitante !== null && p.penales_visitante !== "") {
                                if (p.equipo_local_id == eq1Info.id) {
                                    eq1Info.pen = parseInt(p.penales_local);
                                    eq2Info.pen = parseInt(p.penales_visitante);
                                } else {
                                    eq1Info.pen = parseInt(p.penales_visitante);
                                    eq2Info.pen = parseInt(p.penales_local);
                                }
                            }
                        });

                        let eq1Score = eq1Info.gl;
                        let eq2Score = eq2Info.gl;
                        let penalesText = '';
                        let eq1WinnerCls = ''; 
                        let eq2WinnerCls = '';

                        // Marcador detallado de IDA y VUELTA
                        let detallesPartidos = '';
                        partidos.forEach((p, idx) => {
                            let label = (p.es_ida == 1) ? 'IDA' : 'VTA';
                            let g_este = (p.equipo_local_id == eq1Info.id) ? p.goles_local : p.goles_visitante;
                            let g_riva = (p.equipo_local_id == eq1Info.id) ? p.goles_visitante : p.goles_local;
                            detallesPartidos += `<span class="badge bg-light text-dark border me-1" style="font-size: 0.65rem;">${label}: ${g_este}-${g_riva}</span>`;
                        });

                        // Solo marcamos ganadores y penales si TODOS los partidos de la llave están terminados
                        if (estaFinalizado) {
                            if(eq1Info.gl > eq2Info.gl) eq1WinnerCls = 'fw-bold text-success';
                            else if(eq2Info.gl > eq1Info.gl) eq2WinnerCls = 'fw-bold text-success';
                            else {
                                // Penales
                                if(eq1Info.pen > eq2Info.pen) { eq1WinnerCls = 'fw-bold text-success'; penalesText = `<div class="small text-muted text-center mt-1 border-top pt-1">Gana Local por Penales (${eq1Info.pen}-${eq2Info.pen})</div>`; }
                                else if(eq2Info.pen > eq1Info.pen) { eq2WinnerCls = 'fw-bold text-success'; penalesText = `<div class="small text-muted text-center mt-1 border-top pt-1">Gana Visitante por Penales (${eq1Info.pen}-${eq2Info.pen})</div>`; }
                            }
                        }

                        let logo1 = eq1Info.logo !== 'default.png' ? 'uploads/logos/' + eq1Info.logo : 'assets/img/default_team.png';
                        let logo2 = eq2Info.logo !== 'default.png' ? 'uploads/logos/' + eq2Info.logo : 'assets/img/default_team.png';

                        html += `
                        <div class="card shadow-sm border-2 mb-4">
                            <div class="card-header bg-dark text-white p-1 d-flex justify-content-between align-items-center px-2">
                                <span class="small fw-bold">${nombreLlave}</span>
                                <div class="detalles-llave">${detallesPartidos}</div>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2 px-1 ${eq1WinnerCls}">
                                    <div class="d-flex align-items-center">
                                        <img src="${logo1}" class="rounded-circle me-2 border bg-white" style="width:25px;height:25px;object-fit:cover;" onerror="this.src='assets/img/default_team.png'">
                                        <span class="text-truncate" style="max-width: 200px;">${eq1Info.nombre}</span>
                                    </div>
                                    <div class="bg-light border rounded px-2 py-1 fs-5 ${eq1WinnerCls}">${eq1Score}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center px-1 border-top pt-2 ${eq2WinnerCls}">
                                    <div class="d-flex align-items-center">
                                        <img src="${logo2}" class="rounded-circle me-2 border bg-white" style="width:25px;height:25px;object-fit:cover;" onerror="this.src='assets/img/default_team.png'">
                                        <span class="text-truncate" style="max-width: 200px;">${eq2Info.nombre}</span>
                                    </div>
                                    <div class="bg-light border rounded px-2 py-1 fs-5 ${eq2WinnerCls}">${eq2Score}</div>
                                </div>
                                ${penalesText}
                            </div>
                        </div>
                        `;
                    }
                    html += `</div>`; // Cierra Columna
                }

                $('#contenedor-llaves').html(html);
            }
        });
    }

    function renderTabla(titulo, equipos) {
        let tpl = document.getElementById('tpl-tabla').content.cloneNode(true);
        tpl.querySelector('.titulo-tabla').textContent = titulo;
        
        let tbody = tpl.querySelector('.cuerpo-tabla');
        let html = '';
        
        if(equipos.length === 0) {
            html = `<tr><td colspan="10" class="text-muted p-4">No hay equipos registrados o el grupo está vacío.</td></tr>`;
        } else {
            equipos.forEach((e, idx) => {
                let pos = idx + 1;
                let clasePos = (pos <= 3) ? `pos-${pos}` : '';
                let trofeo = (pos === 1) ? '<i class="fa-solid fa-trophy trophy-icon"></i>' : '';
                
                let logoPath = (e.logo && e.logo !== 'default.png') ? 'uploads/logos/' + e.logo : 'assets/img/default_team.png';
                let logo = `<img src="${logoPath}" class="rounded-circle me-2 shadow-sm align-middle border" style="width:35px;height:35px;object-fit:cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">`;

                html += `
                <tr>
                    <td><span class="pos-circle ${clasePos}">${pos}</span></td>
                    <td class="text-start fs-6">
                        ${trofeo}${logo} 
                        <span class="btn-link-team fw-bold align-middle" data-id="${e.id}" data-nombre="${e.nombre.replace(/"/g, '&quot;')}">
                            ${e.nombre}
                        </span>
                    </td>
                    <td class="fw-bold fs-5 text-primary bg-light border-start border-end">${e.pts}</td>
                    <td>${e.pj}</td>
                    <td>${e.pg}</td>
                    <td>${e.pe}</td>
                    <td>${e.pp}</td>
                    <td>${e.gf}</td>
                    <td>${e.gc}</td>
                    <td class="fw-bold ${e.dg > 0 ? 'text-success' : (e.dg < 0 ? 'text-danger' : 'text-muted')}">${e.dg > 0 ? '+'+e.dg : e.dg}</td>
                </tr>
                `;
            });
        }
        
        tbody.innerHTML = html;
        document.getElementById('contenedor-posiciones').appendChild(tpl);
    }

    // Evento delegado para abrir historial
    $(document).on('click', '.btn-link-team', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        verHistorial(id, nombre);
    });

    function verHistorial(equipo_id, nombre) {
        let torneo_id = $('#filtro_torneo_pos').val();
        $('#historial-equipo-nombre').text(nombre);
        $('#lista-historial-partidos').html('<tr><td colspan="4" class="text-center p-4"><div class="spinner-border text-primary"></div></td></tr>');
        
        const modalEl = document.getElementById('modalHistorialEquipo');
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) {
            modal = new bootstrap.Modal(modalEl);
        }
        modal.show();

        $.ajax({
            url: 'ajax/resultados.php',
            type: 'GET',
            data: { action: 'listar', torneo_id: torneo_id, equipo_id: equipo_id },
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    let html = '';
                    if(resp.data.length === 0) {
                        html = '<div class="text-center p-5 text-muted"><i class="fa-solid fa-calendar-xmark fa-3x opacity-25 mb-3"></i><br>No hay partidos registrados para este equipo.</div>';
                    } else {
                        resp.data.forEach(p => {
                            let esLocal = (p.equipo_local_id == equipo_id);
                            let resultado = '-';
                            let claseResultado = 'text-muted';
                            
                            if(p.estado === 'finalizado' || p.estado === 'walkover') {
                                resultado = `${p.goles_local} - ${p.goles_visitante}`;
                                // Determinar si ganó, perdió o empató
                                let gLocal = intVal(p.goles_local);
                                let gVis = intVal(p.goles_visitante);
                                if(gLocal === gVis) claseResultado = 'text-warning'; // Empate
                                else if((esLocal && gLocal > gVis) || (!esLocal && gVis > gLocal)) claseResultado = 'text-success'; // Ganó
                                else claseResultado = 'text-danger'; // Perdió
                            } else {
                                resultado = p.estado.toUpperCase();
                            }

                            let impHTML = '';
                            if(p.tiene_impugnacion > 0) {
                                let color = p.estado_impugnacion === 'pendiente' ? 'warning' : (p.estado_impugnacion === 'aceptada' ? 'success' : 'danger');
                                impHTML = `
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="badge bg-${color}-subtle text-${color} border border-${color} w-100">
                                            <i class="fa-solid fa-gavel me-1"></i> IMPUGNACIÓN ${p.estado_impugnacion.toUpperCase()}
                                        </span>
                                    </div>`;
                            }

                            html += `
                            <div class="card border-0 shadow-sm mb-3 overflow-hidden match-card-history" style="cursor: pointer" onclick="window.location.href='gestionar_partido.php?id=${p.id}'">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted fw-bold"><i class="fa-solid fa-calendar-day me-1"></i> ${p.fecha_formateada} - ${p.hora_formateada}</span>
                                        <span class="badge bg-light text-dark border small">${p.nombre_grupo}</span>
                                    </div>
                                    <div class="row align-items-center g-0">
                                        <div class="col-5 text-end pe-2">
                                            <span class="fw-bold ${esLocal ? 'text-primary' : 'text-dark'}">${p.local_nombre}</span>
                                        </div>
                                        <div class="col-2 text-center">
                                            <div class="bg-dark text-white rounded px-2 py-1 fw-bold ${claseResultado}" style="font-size: 0.9rem;">
                                                ${resultado}
                                            </div>
                                        </div>
                                        <div class="col-5 text-start ps-2">
                                            <span class="fw-bold ${!esLocal ? 'text-primary' : 'text-dark'}">${p.visitante_nombre}</span>
                                        </div>
                                    </div>
                                    ${impHTML}
                                </div>
                            </div>
                            `;
                        });
                    }
                    $('#lista-historial-partidos').html(html);
                }
            }
        });
    }
    
    function intVal(v) {
        return typeof v === 'string' ? v.replace(/[\$,]/g, '') * 1 : typeof v === 'number' ? v : 0;
    }
});
