$(document).ready(function() {
    // Inicializar DataTable
    var tabla = $('#tablaEquipos').DataTable({
        "ajax": {
            "url": "ajax/equipos.php?action=listar",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { 
                "data": "logo",
                "render": function(data, type, row) {
                    var img = (data && data !== 'default.png') ? 'uploads/logos/' + data : 'assets/img/default_team.png';
                    return `<img src="${img}" alt="Logo" class="rounded-circle shadow-sm border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='assets/img/default_team.png'">`;
                }
            },
            { 
                "data": "nombre",
                "render": function(data, type, row) {
                    return `<a href="equipo_detalle.php?id=${row.id}" class="text-decoration-none fw-bold text-dark hover-primary">${data}</a>`;
                }
            },
            { "data": "fecha_registro" },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    if(userRole === 'administrador') {
                        return `<button class="btn btn-sm btn-outline-primary me-1" onclick="editarEquipo(${row.id}, '${row.nombre.replace(/'/g, "\\'")}', '${row.logo}')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarEquipo(${row.id})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                    } else {
                        return `<span class="badge bg-secondary">Solo lectura</span>`;
                    }
                }
            }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "order": [[0, "desc"]]
    });

    // Vista previa imagen
    $('#logo').change(function(){
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#vista-previa').removeClass('d-none');
                $('#img-preview').attr('src', event.target.result);
            }
            reader.readAsDataURL(file);
        } else {
            $('#vista-previa').addClass('d-none');
            $('#img-preview').attr('src', '');
        }
    });

    // Submit FORM
    $('#formEquipo').on('submit', function(e){
        e.preventDefault();
        let formData = new FormData(this);
        
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/equipos.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    $('#modalEquipo').modal('hide');
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
                $('#btnGuardar').html('Guardar').prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Problema con el servidor', 'error');
                $('#btnGuardar').html('Guardar').prop('disabled', false);
            }
        });
    });
});

window.nuevoEquipo = function() {
    $('#formEquipo')[0].reset();
    $('#id_equipo').val('');
    $('#action').val('crear');
    $('#modalEquipoLabel span').text('Nuevo Equipo');
    $('#vista-previa').addClass('d-none');
    $('#img-preview').attr('src', '');
};

window.editarEquipo = function(id, nombre, logo) {
    $('#formEquipo')[0].reset();
    $('#id_equipo').val(id);
    $('#nombre').val(nombre);
    $('#action').val('editar');
    $('#modalEquipoLabel span').text('Editar Equipo');
    
    if(logo && logo !== 'default.png') {
        $('#vista-previa').removeClass('d-none');
        $('#img-preview').attr('src', 'uploads/logos/' + logo);
    } else {
        $('#vista-previa').addClass('d-none');
        $('#img-preview').attr('src', '');
    }
    
    $('#modalEquipo').modal('show');
};

window.eliminarEquipo = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer y borrará los jugadores asociados.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/equipos.php',
                type: 'POST',
                data: { action: 'eliminar', id_equipo: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        $('#tablaEquipos').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};
