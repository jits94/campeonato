$(document).ready(function() {
    var tabla = $('#tablaInscripciones').DataTable({
        "ajax": {
            "url": "ajax/inscripciones.php?action=listar",
            "data": function(d) {
                d.torneo_id = $('#filtro_torneo').val();
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { 
                "data": "equipo_nombre",
                "className": "fw-bold"
            },
            { 
                "data": "monto_cobrado",
                "render": function(data) {
                    return `<span class="text-success fw-bold">Bs. ${parseFloat(data).toFixed(2)}</span>`;
                }
            },
            { 
                "data": "estado",
                "render": function(data) {
                    if(data === 'registrado') return '<span class="badge bg-success">Pagado / Registrado</span>';
                    return '<span class="badge bg-warning text-dark">Pendiente / Borrador</span>';
                }
            },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-secondary me-1" onclick="editarInscripcion(${row.id}, ${row.equipo_id}, ${row.monto_cobrado}, '${row.estado}')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarInscripcion(${row.id})" title="Anular Inscripción"><i class="fa-solid fa-xmark"></i></button>`;
                }
            }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "order": [[0, "desc"]]
    });

    $('#filtro_torneo').change(function() {
        if($(this).val() !== '') {
            $('#btnNuevaInscripcion').prop('disabled', false);
        } else {
            $('#btnNuevaInscripcion').prop('disabled', true);
        }
        tabla.ajax.reload();
    });

    $('#formInscripcion').on('submit', function(e){
        e.preventDefault();
        
        let formData = $(this).serialize();
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/inscripciones.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalInscripcion').modal('hide');
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
                $('#btnGuardar').html('Guardar Inscripción').prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Problema con el servidor', 'error');
                $('#btnGuardar').html('Guardar Inscripción').prop('disabled', false);
            }
        });
    });
});

window.nuevaInscripcion = function() {
    $('#formInscripcion')[0].reset();
    $('#id_inscripcion').val('');
    $('#torneo_modal_id').val($('#filtro_torneo').val());
    $('#action').val('crear');
    $('#equipo_id').prop('disabled', false); // permitir elegir equipo en creacion
    $('#modalInscripcionLabel span').text('Inscribir Equipo');
};

window.editarInscripcion = function(id, equipo_id, monto, estado) {
    $('#formInscripcion')[0].reset();
    $('#id_inscripcion').val(id);
    $('#torneo_modal_id').val($('#filtro_torneo').val());
    
    // Para select múltiple, pasamos un array con el ID único
    $('#equipo_id').val([equipo_id]).prop('disabled', true); 
    
    $('#monto').val(monto);
    $('#estado').val(estado);
    $('#action').val('editar');
    $('#modalInscripcionLabel span').text('Editar Inscripción');
    $('#modalInscripcion').modal('show');
};

window.eliminarInscripcion = function(id) {
    Swal.fire({
        title: '¿Anular Inscripción?',
        text: "El equipo será removido del torneo.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/inscripciones.php',
                type: 'POST',
                data: { action: 'eliminar', id_inscripcion: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Anulado', resp.message, 'success');
                        $('#tablaInscripciones').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};
