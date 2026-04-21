$(document).ready(function() {
    var tabla = $('#tablaJugadores').DataTable({
        "ajax": {
            "url": "ajax/jugadores.php",
            "type": "GET",
            "data": function(d) {
                d.action = 'listar';
                d.equipo_id = $('#filtro_equipo_jugadores').val() || 0;
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { 
                "data": "nombre",
                "render": function(data, type, row) {
                    return `<a href="jugador_detalle.php?id=${row.id}" class="text-decoration-none d-flex align-items-center">
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2 p-1" style="width: 35px; height: 35px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <span class="fw-bold text-dark hover-primary">${data}</span>
                            </a>`;
                }
            },
            { "data": "ci" },
            { 
                "data": "dorsal",
                "render": function(data) {
                    return data ? `<span class="badge bg-dark rounded-pill">${data}</span>` : '<span class="text-muted">--</span>';
                }
            },
            { 
                "data": "equipo_nombre",
                "render": function(data, type, row) {
                    return `<span class="badge bg-primary px-2 py-1">${data}</span>`;
                }
            },
            { "data": "fecha_registro" },
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    if(userRole === 'administrador') {
                        return `<button class="btn btn-sm btn-outline-info me-1" onclick="editarJugador(${row.id}, '${row.nombre.replace(/'/g, "\\'")}', '${row.ci}', ${row.equipo_id}, '${row.dorsal || ''}', '${row.foto}')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarJugador(${row.id})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
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

    $('#filtro_equipo_jugadores').change(function() {
        tabla.ajax.reload();
    });

    // Vista previa foto jugador
    $('#foto').change(function(){
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#vista-previa-foto').removeClass('d-none');
                $('#img-preview-foto').attr('src', event.target.result);
            }
            reader.readAsDataURL(file);
        } else {
            $('#vista-previa-foto').addClass('d-none');
            $('#img-preview-foto').attr('src', '');
        }
    });

    $('#formJugador').on('submit', function(e){
        e.preventDefault();
        
        let formData = new FormData(this);
        
        $('#btnGuardar').html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/jugadores.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(resp) {
                if(resp.success) {
                    $('#modalJugador').modal('hide');
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

window.nuevoJugador = function() {
    $('#formJugador')[0].reset();
    $('#id_jugador').val('');
    $('#action').val('crear');
    $('#modalJugadorLabel span').text('Nuevo Jugador');
    $('#vista-previa-foto').addClass('d-none');
    $('#img-preview-foto').attr('src', '');
};

window.editarJugador = function(id, nombre, ci, equipo_id, dorsal, foto) {
    $('#formJugador')[0].reset();
    $('#id_jugador').val(id);
    $('#nombre').val(nombre);
    $('#ci').val(ci);
    $('#dorsal').val(dorsal);
    $('#equipo_id').val(equipo_id);
    $('#action').val('editar');
    $('#modalJugadorLabel span').text('Editar Jugador');
    
    if(foto && foto !== 'default_user.png') {
        $('#vista-previa-foto').removeClass('d-none');
        $('#img-preview-foto').attr('src', 'uploads/fotos/' + foto);
    } else {
        $('#vista-previa-foto').addClass('d-none');
        $('#img-preview-foto').attr('src', '');
    }
    
    $('#modalJugador').modal('show');
};

window.eliminarJugador = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "No podrás recuperar este registro.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/jugadores.php',
                type: 'POST',
                data: { action: 'eliminar', id_jugador: id },
                dataType: 'json',
                success: function(resp) {
                    if(resp.success) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        $('#tablaJugadores').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                }
            });
        }
    });
};
