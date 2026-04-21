$(document).ready(function() {
    var tabla = $('#tablaPartidos').DataTable({
        "ajax": {
            "url": "ajax/partidos.php?action=listar",
            "data": function(d) {
                d.torneo_id = $('#filtro_torneo').val();
                d.fecha = $('#filtro_fecha').val();
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `<span class="fw-bold"><i class="fa-regular fa-calendar me-1"></i> ${row.fecha_formateada}</span><br>
                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> ${row.hora}</small>`;
                }
            },
            { 
                "data": "nombre_grupo",
                "defaultContent": "General",
                "render": function(data) {
                    return `<span class="badge bg-info text-dark shadow-sm px-3">${data || 'General'}</span>`;
                }
            },
            { "data": "fase" },
            { 
                "data": "local_nombre",
                "className": "text-end fw-bold",
                "render": function(data, type, row) {
                    let logo = row.local_logo === 'default.png' ? `<div class="d-inline-block bg-secondary text-white rounded-circle text-center ms-2" style="width:25px;height:25px;line-height:25px;font-size:12px;">${data.charAt(0)}</div>` : `<img src="uploads/logos/${row.local_logo}" class="rounded-circle ms-2" style="width:25px;height:25px;object-fit:cover;">`;
                    return data + logo;
                }
            },
            { 
                "data": null,
                "className": "text-center fs-5 fw-bold bg-light",
                "render": function(data, type, row) {
                    if(row.estado === 'programado') return '- : -';
                    if(row.estado === 'walkover') return 'W/O';
                    return `${row.goles_local} - ${row.goles_visitante}`;
                }
            },
            { 
                "data": "visitante_nombre",
                "className": "fw-bold",
                "render": function(data, type, row) {
                    let logo = row.visitante_logo === 'default.png' ? `<div class="d-inline-block bg-secondary text-white rounded-circle text-center me-2" style="width:25px;height:25px;line-height:25px;font-size:12px;">${data.charAt(0)}</div>` : `<img src="uploads/logos/${row.visitante_logo}" class="rounded-circle me-2" style="width:25px;height:25px;object-fit:cover;">`;
                    return logo + data;
                }
            },
            { 
                "data": "estado",
                "render": function(data) {
                    if(data === 'programado') return '<span class="badge bg-secondary">Programado</span>';
                    if(data === 'en_juego') return '<span class="badge bg-danger shadow-sm timer-blink"><i class="fa-solid fa-circle text-white" style="font-size: 8px;"></i> En Juego</span>';
                    if(data === 'finalizado') return '<span class="badge bg-success">Finalizado</span>';
                    if(data === 'walkover') return '<span class="badge bg-dark">Walkover</span>';
                    return data;
                }
            },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    if(userRole === 'administrador') {
                        let btns = '';
                        if(row.estado === 'programado') {
                            btns += `<button class="btn btn-sm btn-outline-success me-1" onclick="iniciarPartido(${row.id})" title="Iniciar Partido"><i class="fa-solid fa-play"></i></button>`;
                            btns += `<button class="btn btn-sm btn-outline-danger me-1" onclick="eliminarPartido(${row.id})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                        } else if(row.estado === 'en_juego') {
                            btns += `<a href="gestionar_partido.php?id=${row.id}" class="btn btn-sm btn-danger me-1 shadow-sm" title="Gestionar Eventos"><i class="fa-solid fa-clipboard-list"></i> Gestionar</a>`;
                        } else {
                            btns += `<a href="gestionar_partido.php?id=${row.id}" class="btn btn-sm btn-secondary me-1" title="Ver Detalles"><i class="fa-solid fa-eye"></i> Detalles</a>`;
                        }
                        return btns;
                    }
                    return `<a href="gestionar_partido.php?id=${row.id}" class="btn btn-sm btn-outline-secondary" title="Ver Detalles"><i class="fa-solid fa-eye"></i></a>`;
                }
            }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "order": [[2, "asc"], [1, "asc"]],
        "drawCallback": function (settings) {
            var api = this.api();
            var rows = api.rows({ page: 'current' }).nodes();
            var last = null;

            api.column(2, { page: 'current' }).data().each(function (group, i) {
                let groupName = group ? group.toString() : 'General';
                if (last !== groupName) {
                    $(rows).eq(i).before(
                        '<tr class="group text-white" style="background-color: #2c3e50;"><td colspan="9" class="fw-bold py-2 px-3"><i class="fa-solid fa-layer-group me-2 text-info"></i>' + groupName.toUpperCase() + '</td></tr>'
                    );
                    last = groupName;
                }
            });
        },
        "initComplete": function(settings, json) {
            if (json && json.hasEliminatoria && !json.incompleteTies) {
                $('#btnAvanzarFase').prop('disabled', false).removeClass('d-none');
            } else {
                $('#btnAvanzarFase').prop('disabled', true).addClass('d-none');
            }
            console.log("DataTable de Partidos inicializada.");
        }
    });

    $('#filtro_torneo').change(function() {
        let torneo_id = $(this).val();
        if (torneo_id && torneo_id !== 'general') {
            $('#btnNuevoPartido').prop('disabled', false);
            $('#btnProgramacionEliminatoria, #btnProgramacionMasiva').prop('disabled', false);
        } else {
            $('#btnNuevoPartido').prop('disabled', true);
            $('#btnProgramacionEliminatoria, #btnProgramacionMasiva, #btnAvanzarFase').prop('disabled', true).addClass('d-none');
        }
        tabla.ajax.reload(function(json) {
            if (json.hasEliminatoria && !json.incompleteTies) {
                $('#btnAvanzarFase').prop('disabled', false).removeClass('d-none');
            } else {
                $('#btnAvanzarFase').prop('disabled', true).addClass('d-none');
            }
        });
    });

    $('#filtro_fecha').change(function() {
        tabla.ajax.reload();
    });

    // --- Lógica Programación Masiva ---
    $('#btnGenerarPreview').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        let jornada = $('#masivo_fecha_num').val();
        let fecha_incio = $('#masivo_fecha_inicio').val();
        let hora_inicio = $('#masivo_hora_inicio').val();
        let intervalo = $('#masivo_intervalo').val();

        if (!fecha_incio || !hora_inicio) {
            Swal.fire('Atención', 'Por favor complete la fecha y hora de inicio.', 'warning');
            return;
        }
        if (!torneo_id) {
            Swal.fire('Atención', 'Debe seleccionar un torneo.', 'warning');
            return;
        }
        if (!jornada) {
            Swal.fire('Atención', 'Debe especificar un número de jornada/fecha.', 'warning');
            return;
        }


        $.ajax({
            url: 'ajax/partidos.php',
            method: 'POST',
            data: { action: 'generar_preview_fecha', torneo_id: torneo_id, jornada: jornada },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    let tbody = $('#tablaPreviewMasivo tbody');
                    tbody.empty();
                    
                    let currentDate = new Date(fecha_incio + 'T' + hora_inicio);

                    resp.data.forEach((p, idx) => {
                        let dateStr = currentDate.toISOString().split('T')[0];
                        let timeStr = currentDate.toTimeString().substring(0, 5);

                        let row = `
                            <tr>
                                <td><span class="badge bg-secondary">${p.grupo_nombre}</span></td>
                                <td class="text-end fw-bold">${p.local_nombre}</td>
                                <td class="text-center text-muted">vs</td>
                                <td class="fw-bold">${p.visitante_nombre}</td>
                                <td><input type="date" class="form-control form-control-sm masivo-fecha" value="${dateStr}"></td>
                                <td><input type="time" class="form-control form-control-sm masivo-hora" value="${timeStr}"></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger border-0" onclick="$(this).closest('tr').remove()">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <input type="hidden" class="masivo-local-id" value="${p.local_id}">
                                    <input type="hidden" class="masivo-visitante-id" value="${p.visitante_id}">
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                        
                        // Incrementar tiempo para el siguiente
                        currentDate.setMinutes(currentDate.getMinutes() + parseInt(intervalo));
                    });

                    $('#contenedor_preview_masivo').removeClass('d-none');
                    $('#btnConfirmarMasivo').removeClass('d-none');
                } else {
                    Swal.fire('Error', resp.message, 'error');
                    $('#contenedor_preview_masivo').addClass('d-none');
                    $('#btnConfirmarMasivo').addClass('d-none');
                }
            },
            error: function() {
                Swal.fire('Error', 'Problema al generar la vista previa.', 'error');
                $('#contenedor_preview_masivo').addClass('d-none');
                $('#btnConfirmarMasivo').addClass('d-none');
            }
        });
    });

    $('#btnConfirmarMasivo').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        let fecha_num = $('#masivo_fecha_num').val();
        let fase = "Fecha " + fecha_num;
        let partidos = [];

        $('#tablaPreviewMasivo tbody tr').each(function() {
            partidos.push({
                local_id: $(this).find('.masivo-local-id').val(),
                visitante_id: $(this).find('.masivo-visitante-id').val(),
                fecha: $(this).find('.masivo-fecha').val(),
                hora: $(this).find('.masivo-hora').val()
            });
        });

        if (partidos.length === 0) {
            Swal.fire('Atención', 'No hay partidos para registrar.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Confirmar Registro Masivo?',
            text: `Se registrarán ${partidos.length} partidos para la ${fase}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

                $.ajax({
                    url: 'ajax/partidos.php',
                    method: 'POST',
                    data: { 
                        action: 'guardar_partidos_masivo', 
                        torneo_id: torneo_id,
                        fase: fase,
                        partidos: JSON.stringify(partidos)
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Éxito', resp.message, 'success');
                            // Assuming 'bootstrap' is available for modal handling
                            let modalMasivo = bootstrap.Modal.getInstance(document.getElementById('modalMasivo'));
                            if (modalMasivo) modalMasivo.hide();
                            tabla.ajax.reload(null, false); // Reload the main DataTable
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Problema con el servidor al guardar los partidos.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Todos');
                    }
                });
            }
        });
    });

    // --- Lógica Fase Eliminatoria ---
    $('#btnGenerarPreviewEliminatoria').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        let fase_size = $('#eliminatoria_fase').val();
        let fecha_inicio = $('#eliminatoria_fecha_inicio').val();
        let hora_inicio = $('#eliminatoria_hora_inicio').val();
        let intervalo = $('#eliminatoria_intervalo').val();

        if (!fecha_inicio || !hora_inicio) {
            Swal.fire('Atención', 'Por favor complete la fecha y hora de inicio.', 'warning');
            return;
        }
        if (!torneo_id) {
            Swal.fire('Atención', 'Debe seleccionar un torneo.', 'warning');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Calculando...');

        $.ajax({
            url: 'ajax/partidos.php',
            method: 'POST',
            data: { action: 'preview_eliminatoria', torneo_id: torneo_id, fase_size: fase_size },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    let tbody = $('#tablaPreviewEliminatoria tbody');
                    tbody.empty();
                    
                    let currentDate = new Date(fecha_inicio + 'T' + hora_inicio);

                    resp.data.forEach((p, idx) => {
                        let tempDate = new Date(currentDate);
                        if (p.es_ida === 0 && (fase_size == 16 || fase_size == 8)) {
                            // Si es vuelta, sumamos 7 días por defecto
                            tempDate.setDate(tempDate.getDate() + 7);
                        }

                        let dateStr = tempDate.toISOString().split('T')[0];
                        let timeStr = tempDate.toTimeString().substring(0, 5);
                        let subLlaveText = p.es_ida ? " (Ida)" : (fase_size == 16 || fase_size == 8 ? " (Vuelta)" : " (Único)");

                        let row = `
                            <tr>
                                <td><span class="badge bg-secondary">${p.llave}${subLlaveText}</span></td>
                                <td class="text-end fw-bold text-success">${p.local_nombre} <small class="text-muted fw-normal">(Pos: ${p.local_pos})</small></td>
                                <td class="text-center text-muted">vs</td>
                                <td class="fw-bold text-danger"><small class="text-muted fw-normal">(Pos: ${p.visitante_pos})</small> ${p.visitante_nombre}</td>
                                <td>
                                    <div class="d-flex">
                                        <input type="date" class="form-control form-control-sm eliminatoria-fecha" value="${dateStr}">
                                        <input type="time" class="form-control form-control-sm eliminatoria-hora ms-1" value="${timeStr}">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-trash-can"></i></button>
                                    <input type="hidden" class="eliminatoria-local-id" value="${p.local_id}">
                                    <input type="hidden" class="eliminatoria-visitante-id" value="${p.visitante_id}">
                                    <input type="hidden" class="eliminatoria-llave" value="${p.llave}">
                                    <input type="hidden" class="eliminatoria-es-ida" value="${p.es_ida}">
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                        
                        // Solo incrementamos el tiempo del cursor base si NO es vuelta (para que las vueltas de una misma tanda salgan a la misma hora pero otro día)
                        // O mejor, incrementamos siempre para mantener el orden, pero las vueltas ya tienen su +7 aplicado arriba.
                        currentDate.setMinutes(currentDate.getMinutes() + parseInt(intervalo));
                    });

                    $('#contenedor_preview_eliminatoria').removeClass('d-none');
                    $('#btnConfirmarEliminatoria').removeClass('d-none');
                } else {
                    Swal.fire('Error', resp.message, 'error');
                    $('#contenedor_preview_eliminatoria').addClass('d-none');
                    $('#btnConfirmarEliminatoria').addClass('d-none');
                }
            },
            error: function() {
                Swal.fire('Error', 'Problema al generar las llaves eliminatorias.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass-chart me-1"></i> Calcular y Generar Llaves');
            }
        });
    });

    $('#btnConfirmarEliminatoria').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        let fase_size = parseInt($('#eliminatoria_fase').val());
        let faseNombre = "Fase Final";
        
        if(fase_size == 16) faseNombre = "Octavos de Final";
        else if(fase_size == 8) faseNombre = "Cuartos de Final";
        else if(fase_size == 4) faseNombre = "Semifinal";
        else if(fase_size == 2) faseNombre = "Final";

        let partidos = [];
        $('#tablaPreviewEliminatoria tbody tr').each(function() {
            partidos.push({
                local_id: $(this).find('.eliminatoria-local-id').val(),
                visitante_id: $(this).find('.eliminatoria-visitante-id').val(),
                fecha: $(this).find('.eliminatoria-fecha').val(),
                hora: $(this).find('.eliminatoria-hora').val(),
                llave: $(this).find('.eliminatoria-llave').val(),
                es_ida: parseInt($(this).find('.eliminatoria-es-ida').val())
            });
        });

        if (partidos.length === 0) {
            Swal.fire('Atención', 'No hay llaves para registrar.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Confirmar Llaves?',
            text: `Se registrarán ${partidos.length} partidos para la fase de ${faseNombre}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

                $.ajax({
                    url: 'ajax/partidos.php',
                    method: 'POST',
                    data: { 
                        action: 'guardar_partidos_masivo', 
                        torneo_id: torneo_id,
                        fase: faseNombre,
                        partidos: JSON.stringify(partidos)
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Éxito', resp.message, 'success');
                            let m = bootstrap.Modal.getInstance(document.getElementById('modalEliminatoria'));
                            if (m) m.hide();
                            tabla.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Problema con el servidor al guardar la eliminatoria.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Llaves');
                    }
                });
            }
        });
    });

    // Avanzar a Siguiente Ronda Logica
    $('#btnGenerarPreviewAvanzar').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        let fase_origen = $('#avanzar_fase_origen').val();
        let fecha = $('#avanzar_fecha_inicio').val();
        let hora = $('#avanzar_hora_inicio').val();
        let intervalo = $('#avanzar_intervalo').val() || 60;

        if (!fecha || !hora) {
            Swal.fire('Atención', 'Debe seleccionar fecha y hora de inicio', 'warning');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Calculando...');

        $.ajax({
            url: 'ajax/partidos.php',
            data: { action: 'avanzar_fase', torneo_id: torneo_id, fase_origen: fase_origen },
            dataType: 'json',
            success: function(resp) {
                $('#btnGenerarPreviewAvanzar').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass-chart me-1"></i> Calcular Ganadores y Mostrar Llaves');
                if(resp.success && resp.data.length > 0) {
                    $('#tablaPreviewAvanzar tbody').empty();
                    
                    let currentDate = new Date(`${fecha}T${hora}`);

                    resp.data.forEach((p, idx) => {
                        let tempDate = new Date(currentDate);
                        if (p.es_ida === 0 && (p.llave.includes('Octavos') || p.llave.includes('Cuartos'))) {
                             // Si es vuelta, sumamos 7 días
                             tempDate.setDate(tempDate.getDate() + 7);
                        }

                        let dateStr = tempDate.toISOString().split('T')[0];
                        let timeStr = tempDate.toTimeString().substring(0, 5);
                        let subLlaveText = p.es_ida ? " (Ida)" : (p.llave.includes('S') || p.llave.includes('Fi') || p.llave.includes('Te') ? " (Único)" : " (Vuelta)");

                        let isTercer = p.llave.includes('Tercer');

                        let row = `
                            <tr data-fase="${isTercer ? 'Tercer Puesto' : resp.siguiente_fase}">
                                <td><span class="badge ${isTercer?'bg-warning text-dark':'bg-secondary'}">${p.llave}${subLlaveText}</span></td>
                                <td class="text-end fw-bold text-success">${p.local_nombre}</td>
                                <td class="text-center text-muted">vs</td>
                                <td class="fw-bold text-danger">${p.visitante_nombre}</td>
                                <td>
                                    <div class="d-flex">
                                        <input type="date" class="form-control form-control-sm avanzar-fecha" value="${dateStr}">
                                        <input type="time" class="form-control form-control-sm avanzar-hora ms-1" value="${timeStr}">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-trash-can"></i></button>
                                    <input type="hidden" class="avanzar-local-id" value="${p.local_id}">
                                    <input type="hidden" class="avanzar-visitante-id" value="${p.visitante_id}">
                                    <input type="hidden" class="avanzar-llave" value="${p.llave}">
                                    <input type="hidden" class="avanzar-es-ida" value="${p.es_ida}">
                                </td>
                            </tr>
                        `;
                        $('#tablaPreviewAvanzar tbody').append(row);

                        currentDate.setMinutes(currentDate.getMinutes() + parseInt(intervalo));
                    });

                    $('#contenedor_preview_avanzar').removeClass('d-none');
                    $('#btnConfirmarAvanzar').removeClass('d-none');
                } else {
                    Swal.fire('Atención', resp.message || 'No se encontraron cruces finalizados suficientes en esta fase origen para avanzar.', 'error');
                    $('#contenedor_preview_avanzar').addClass('d-none');
                    $('#btnConfirmarAvanzar').addClass('d-none');
                }
            }
        });
    });

    $('#btnConfirmarAvanzar').click(function() {
        let torneo_id = $('#filtro_torneo').val();
        
        let allFases = {};

        $('#tablaPreviewAvanzar tbody tr').each(function() {
            let f = $(this).data('fase');
            if(!allFases[f]) allFases[f] = [];

            allFases[f].push({
                local_id: $(this).find('.avanzar-local-id').val(),
                visitante_id: $(this).find('.avanzar-visitante-id').val(),
                fecha: $(this).find('.avanzar-fecha').val(),
                hora: $(this).find('.avanzar-hora').val(),
                llave: $(this).find('.avanzar-llave').val(),
                es_ida: parseInt($(this).find('.avanzar-es-ida').val())
            });
        });

        if (Object.keys(allFases).length === 0) {
            Swal.fire('Atención', 'No hay partidos para generar.', 'warning');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');

        let totalPromises = [];

        Object.keys(allFases).forEach(fase => {
            if(allFases[fase].length > 0) {
                totalPromises.push(
                    $.ajax({
                        url: 'ajax/partidos.php',
                        type: 'POST',
                        data: {
                            action: 'guardar_partidos_masivo',
                            torneo_id: torneo_id,
                            fase: fase,
                            partidos: JSON.stringify(allFases[fase])
                        },
                        dataType: 'json'
                    })
                );
            }
        });

        Promise.all(totalPromises).then(results => {
            let allSuccess = true;
            results.forEach(r => { if(!r.success) allSuccess = false; });
            
            if(allSuccess) {
                Swal.fire('Éxito', 'La nueva ronda se ha registrado con éxito.', 'success');
                $('#modalAvanzarFase').modal('hide');
                tabla.ajax.reload(null, false);
            } else {
                Swal.fire('Error', 'Ocurrieron errores al guardar la ronda.', 'error');
            }
            btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-up me-1"></i> Confirmar y Registrar Ronda');
        });
    });

    $('#btnFiltrar').click(function() {
        tabla.ajax.reload();
    });

    $('#formPartido').on('submit', function(e){
        e.preventDefault();
        
        if($('#equipo_local_id').val() === $('#equipo_visitante_id').val()) {
            Swal.fire('Atención', 'El equipo local y visitante no pueden ser el mismo.', 'warning');
            return;
        }

        let formData = $(this).serialize();
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/partidos.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalPartido').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: resp.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    tabla.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
                $('#btnGuardar').html('Guardar Partido').prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Problema con el servidor', 'error');
                $('#btnGuardar').html('Guardar Partido').prop('disabled', false);
            }
        });
    });
});

window.nuevoPartido = function() {
    $('#formPartido')[0].reset();
    $('#id_partido').val('');
    $('#partido_torneo_id').val($('#filtro_torneo').val());
    $('#action').val('crear');
    
    // Cargar equipos del torneo
    cargarEquiposDelTorneo($('#filtro_torneo').val());
};

window.cargarEquiposDelTorneo = function(torneo_id) {
    $.ajax({
        url: 'ajax/partidos.php',
        type: 'POST',
        data: { action: 'listar_equipos_torneo', torneo_id: torneo_id },
        dataType: 'json',
        success: function(resp) {
            let allTeams = resp.data;
            let tipo = resp.tipo_torneo;
            
            if(tipo === 'fase_grupos') {
                $('#wrapper_grupo').removeClass('d-none');
                
                // Extraer grupos únicos
                let grupos = [...new Set(allTeams.map(t => t.nombre_grupo))];
                let grupoOptions = '<option value="">-- Todos los Grupos --</option>';
                grupos.forEach(g => {
                    grupoOptions += `<option value="${g}">${g}</option>`;
                });
                $('#id_grupo_filtro').html(grupoOptions);
                
                // Escuchar cambio de grupo
                $('#id_grupo_filtro').off('change').on('change', function() {
                    let gSelected = $(this).val();
                    let filtered = gSelected === '' ? [] : allTeams.filter(t => t.nombre_grupo === gSelected);
                    renderEquiposPartidos(filtered);
                });

                renderEquiposPartidos([]); // Inicialmente vacío hasta que seleccione grupo
            } else {
                $('#wrapper_grupo').addClass('d-none');
                renderEquiposPartidos(allTeams);
            }
        }
    });
};

function renderEquiposPartidos(equipos) {
    let options = '<option value="">-- Seleccione --</option>';
    equipos.forEach(function(e) {
        options += `<option value="${e.id}">${e.nombre}</option>`;
    });
    $('#equipo_local_id').html(options);
    $('#equipo_visitante_id').html(options);
}

window.eliminarPartido = function(id) {
    Swal.fire({
        title: '¿Eliminar Partido?',
        text: "Se borrará la programación de este encuentro.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/partidos.php',
                type: 'POST',
                data: { action: 'eliminar', id_partido: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        $('#tablaPartidos').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};

window.iniciarPartido = function(id) {
    Swal.fire({
        title: '¿Iniciar Partido?',
        text: "El partido cambiará su estado a 'En Juego' y estará visible en los resultados en vivo.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, INICIAR',
        cancelButtonText: 'No, aún no'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/partidos.php',
                type: 'POST',
                data: { action: 'cambiar_estado', id_partido: id, estado: 'en_juego' },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        window.location.href = 'gestionar_partido.php?id=' + id;
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};
