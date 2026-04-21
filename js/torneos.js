$(document).ready(function() {
    var tabla = $('#tablaTorneos').DataTable({
        "ajax": {
            "url": "ajax/torneos.php?action=listar",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { 
                "data": "nombre",
                "className": "fw-bold text-primary"
            },
            { 
                "data": "tipo",
                "render": function(data) {
                    if(data === 'todos_contra_todos') {
                        return '<span class="badge bg-info text-dark"><i class="fa-solid fa-list me-1"></i> Todos contra Todos</span>';
                    } else {
                        return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-layer-group me-1"></i> Fase de Grupos</span>';
                    }
                }
            },
            { 
                "data": "estado",
                "render": function(data) {
                    if(data === 'activo') return '<span class="badge bg-success">Activo</span>';
                    return '<span class="badge bg-secondary">Finalizado</span>';
                }
            },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-success me-1" onclick="editarTorneo(${row.id}, '${row.nombre.replace(/'/g, "\\'")}', '${row.tipo}', '${row.estado}')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarTorneo(${row.id})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                }
            }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "order": [[0, "desc"]]
    });

    $('#formTorneo').on('submit', function(e){
        e.preventDefault();
        
        let formData = $(this).serialize();
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/torneos.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalTorneo').modal('hide');
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
                $('#btnGuardar').html('Guardar Torneo').prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Problema con el servidor', 'error');
                $('#btnGuardar').html('Guardar Torneo').prop('disabled', false);
            }
        });
    });
});

window.nuevoTorneo = function() {
    $('#formTorneo')[0].reset();
    $('#id_torneo').val('');
    $('#action').val('crear');
    $('#modalTorneoLabel span').text('Nuevo Torneo');
};

window.editarTorneo = function(id, nombre, tipo, estado) {
    $('#formTorneo')[0].reset();
    $('#id_torneo').val(id);
    $('#nombre').val(nombre);
    $('#tipo').val(tipo);
    $('#estado').val(estado);
    $('#action').val('editar');
    $('#modalTorneoLabel span').text('Editar Torneo');
    $('#modalTorneo').modal('show');
};

window.eliminarTorneo = function(id) {
    Swal.fire({
        title: 'Atención: ¿Eliminar Torneo?',
        text: "Al eliminar el torneo, se borrarán todos sus partidos, inscripciones, grupos, etc. Es una acción irreversible.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar todo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/torneos.php',
                type: 'POST',
                data: { action: 'eliminar', id_torneo: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        $('#tablaTorneos').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};
