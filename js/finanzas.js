$(document).ready(function () {
    let t_id = $('#filtro_torneo_finanzas').val() || torneoContexto;
    $('#filtro_torneo_finanzas').val(t_id); // Set the select back

    let chartIngresosObj = null;
    let chartBalanceObj = null;

    cargarResumen(t_id);

    // Inicializar estilo de la pestaña activa
    $('#finanzasTabs .nav-link.active').addClass('border-bottom border-success border-3 text-dark').removeClass('text-muted');
    $('#finanzasTabs .nav-link:not(.active)').addClass('text-muted');
    function formatMonto(val) {
        if (val === null || val === undefined || isNaN(val)) return "0,00";
        return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(val));
    }

    // Tablas DataTables
    var tablaGastos = $('#tablaGastos').DataTable({
        "ajax": { "url": "ajax/finanzas.php?action=listar_gastos", "data": function (d) { d.torneo_id = t_id; }, "dataSrc": "data" },
        "columns": [
            { "data": "fecha" },
            { "data": "monto", "render": function (data) { return `<span class="text-danger fw-bold">Bs. ${formatMonto(data)}</span>`; } },
            { "data": "categoria", "render": function (data) { return `<span class="badge bg-secondary text-uppercase">${data}</span>`; } },
            { "data": "descripcion" },
            {
                "data": "id",
                "className": "text-center",
                "render": function (data) {
                    return `<button class="btn btn-sm btn-outline-danger" onclick="eliminarGasto(${data})"><i class="fa-solid fa-trash"></i></button>`;
                }
            }
        ],
        "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "order": [[0, "desc"]]
    });

    // Pendientes sin Datatables complejo (solo render html en un tbody o DT basico)
    cargarPendientes(t_id);

    // Historial
    var tablaHistorial = $('#tablaHistorial').DataTable({
        "ajax": { "url": "ajax/finanzas.php?action=historial_torneos", "dataSrc": "data" },
        "columns": [
            { "data": "torneo", "className": "fw-bold" },
            { "data": "estado", "render": function (data) { return data == 'activo' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Finalizado</span>'; } },
            { "data": "in", "render": function (data) { return `Bs. ${formatMonto(data)}`; } },
            { "data": "out", "render": function (data) { return `Bs. ${formatMonto(data)}`; } },
            { "data": "bal", "className": "fw-bold text-success", "render": function (data) { return `Bs. ${formatMonto(data)}`; } }
        ],
        "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "paging": false, "info": false, "searching": false
    });

    // on change filtro
    $('#filtro_torneo_finanzas').change(function () {
        t_id = $(this).val();
        cargarResumen(t_id);
        tablaGastos.ajax.reload();
        cargarPendientes(t_id);
    });

    // Form Gasto
    $('#formGasto').submit(function (e) {
        e.preventDefault();
        $('#gasto_torneo_id').val(t_id);

        let fd = $(this).serialize();
        $.ajax({
            url: 'ajax/finanzas.php',
            type: 'POST',
            data: fd,
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    $('#modalGasto').modal('hide');
                    Swal.fire('Registrado', 'El gasto fue registrado', 'success');
                    tablaGastos.ajax.reload(null, false);
                    tablaHistorial.ajax.reload(null, false);
                    cargarResumen(t_id);
                }
            }
        });
    });

    // Control de estilos visuales de pestañas
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $('.nav-link').removeClass('active border-bottom border-success border-3 text-dark').addClass('text-muted');
        $(e.target).addClass('active border-bottom border-success border-3 text-dark').removeClass('text-muted');
    });

    function cargarPendientes(torneo_id) {
        $.ajax({
            url: 'ajax/finanzas.php', type: 'GET', data: { action: 'listar_pendientes', torneo_id: torneo_id }, dataType: 'json',
            success: function (resp) {
                // Tarjetas
                let htmlT = '';
                resp.tarjetas.forEach(t => {
                    let c = t.tipo == 'amarilla' ? 'text-warning' : 'text-danger';
                    htmlT += `<tr>
                        <td>${t.fecha} <br><small>P-${t.partido_id}</small></td>
                        <td>${t.jugador_nombre}<br><small>${t.equipo_nombre}</small></td>
                        <td><i class="fa-solid fa-square ${c} border fw-bold"></i> ${t.tipo}</td>
                        <td class="fw-bold">Bs. ${formatMonto(t.monto)}</td>
                        <td><button class="btn btn-sm btn-success" onclick="cobrarSancion(${t.id_sancion})"><i class="fa-solid fa-check"></i> Pagar</button></td>
                    </tr>`;
                });
                if (htmlT === '') htmlT = `<tr><td colspan="5" class="text-center text-muted">No hay tarjetas pendientes.</td></tr>`;
                $('#tablaPendientesTarjetas tbody').html(htmlT);

                // Partidos
                let htmlP = '';
                resp.partidos.forEach(p => {
                    htmlP += `<tr>
                        <td>P-${p.partido_id} - ${p.fase}</td>
                        <td>${p.equipo_nombre}</td>
                        <td class="fw-bold">Bs. ${formatMonto(p.monto)}</td>
                        <td><button class="btn btn-sm btn-success" onclick="cobrarPartidoFee(${p.id_cobro})"><i class="fa-solid fa-check"></i> Pagar</button></td>
                    </tr>`;
                });
                if (htmlP === '') htmlP = `<tr><td colspan="4" class="text-center text-muted">No hay cobros de partidos pendientes.</td></tr>`;
                $('#tablaPendientesPartidos tbody').html(htmlP);
            }
        });
    }

    window.cobrarSancion = function (id) {
        execPagar('cobrar_sancion', { id_sancion: id });
    };

    window.cobrarPartidoFee = function (id) {
        execPagar('cobrar_partido_fee', { id_cobro: id });
    };

    function execPagar(action, data) {
        data.action = action;
        $.ajax({
            url: 'ajax/finanzas.php', type: 'POST', data: data, dataType: 'json',
            success: function (r) {
                if (r.success) {
                    Swal.fire({ icon: 'success', title: 'Pagado', timer: 1000, showConfirmButton: false });
                    cargarPendientes(t_id);
                    tablaHistorial.ajax.reload(null, false);
                    cargarResumen(t_id); // Actualizar dash
                }
            }
        });
    }

    window.eliminarGasto = function (id) {
        Swal.fire({
            title: 'Borrar Gasto', icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar', confirmButtonColor: '#d33',
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: 'ajax/finanzas.php', type: 'POST', data: { action: 'eliminar_gasto', id_gasto: id }, dataType: 'json',
                    success: function () {
                        tablaGastos.ajax.reload(null, false);
                        tablaHistorial.ajax.reload(null, false);
                        cargarResumen(t_id);
                    }
                });
            }
        });
    };

    function cargarResumen(torneo_id) {
        $.ajax({
            url: 'ajax/finanzas.php',
            type: 'GET',
            data: { action: 'dashboard_stats', torneo_id: torneo_id },
            dataType: 'json',
            success: function (resp) {
                $('#lbl_ingresos span').text(formatMonto(resp.in_total));
                $('#lbl_inscr').text(formatMonto(resp.inReq.insc));
                $('#lbl_sanc').text(formatMonto(resp.inReq.sanc));
                $('#lbl_cob').text(formatMonto(resp.inReq.cobr));
                $('#lbl_transf').text(formatMonto(resp.inReq.transf));
                $('#lbl_impug').text(formatMonto(resp.inReq.impug));

                $('#lbl_gastos span').text(formatMonto(resp.g_total));
                $('#lbl_balance span').text(formatMonto(resp.balance));

                renderCharts(resp);
            }
        });
    }

    function renderCharts(data) {
        const ctxIngreso = document.getElementById('chartIngresos').getContext('2d');
        if (chartIngresosObj) chartIngresosObj.destroy();
        chartIngresosObj = new Chart(ctxIngreso, {
            type: 'doughnut',
            data: {
                labels: ['Inscripciones', 'Sanciones', 'Partidos', 'Transferencias', 'Impugnaciones'],
                datasets: [{
                    data: [data.inReq.insc, data.inReq.sanc, data.inReq.cobr, data.inReq.transf, data.inReq.impug],
                    backgroundColor: ['#0dcaf0', '#ffc107', '#20c997', '#6f42c1', '#fd7e14']
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        const ctxBal = document.getElementById('chartBalance').getContext('2d');
        if (chartBalanceObj) chartBalanceObj.destroy();
        chartBalanceObj = new Chart(ctxBal, {
            type: 'pie',
            data: {
                labels: ['Ingresos Netos', 'Gastos Totales'],
                datasets: [{
                    data: [data.in_total, data.g_total],
                    backgroundColor: ['#198754', '#dc3545']
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }

    window.nuevoGasto = function () {
        $('#formGasto')[0].reset();
    };
});
