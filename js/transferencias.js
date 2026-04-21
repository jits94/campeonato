$(document).ready(function() {
    var tabla = $('#tablaTransferencias').DataTable({
        "ajax": {
            "url": "ajax/transferencias.php?action=listar",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { "data": "fecha" },
            { "data": "jugador_nombre", "className": "fw-bold" },
            { 
                "data": "origen_nombre",
                "render": function(data) {
                    return `<span class="badge bg-secondary">${data}</span>`;
                }
            },
            { 
                "data": "destino_nombre",
                "render": function(data) {
                    return `<span class="badge bg-primary">${data}</span>`;
                }
            },
            { 
                "data": "monto",
                "render": function(data) {
                    return `<span class="text-success fw-bold">Bs. ${parseFloat(data).toFixed(2)}</span>`;
                }
            },
            { "data": "torneo_nombre" }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "order": [[0, "desc"]]
    });

    // Inicializar Select2
    $('#jugador_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalTransferencia'),
        placeholder: '-- Buscar Jugador --',
        allowClear: true
    });

    // Autocompletar origen al seleccionar jugador con Select2
    $('#jugador_id').on('select2:select', function(e) {
        let data = e.params.data;
        let opt = $(data.element);
        let eq_id = opt.data('equipo');
        let eq_nombre = opt.data('nombre-equipo');
        
        $('#equipo_origen_id').val(eq_id);
        $('#equipo_origen_nombre').val(eq_nombre);
    });

    // Limpiar origen si se borra la selección de Select2
    $('#jugador_id').on('select2:unselect', function() {
        $('#equipo_origen_id').val('');
        $('#equipo_origen_nombre').val('');
    });

    $('#formTransferencia').on('submit', function(e){
        e.preventDefault();
        
        // Validación extra: no origen = destino
        if($('#equipo_origen_id').val() === $('#equipo_destino_id').val()) {
            Swal.fire('Atención', 'El equipo de destino no puede ser igual al de origen.', 'warning');
            return;
        }

        let formData = $(this).serialize();
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/transferencias.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalTransferencia').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Transferencia Exitosa',
                        text: resp.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload(); // Recargar para actualizar los selects
                    });
                } else {
                    Swal.fire('Error', resp.message, 'error');
                    $('#btnGuardar').html('Guardar Transferencia').prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Problema con el servidor', 'error');
                $('#btnGuardar').html('Guardar Transferencia').prop('disabled', false);
            }
        });
    });
});

window.nuevaTransferencia = function() {
    $('#formTransferencia')[0].reset();
    $('#jugador_id').val('').trigger('change'); // Reset Select2
    $('#equipo_origen_id').val('');
    $('#equipo_origen_nombre').val('');
};
