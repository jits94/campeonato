<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

if($_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>No tienes permisos.</div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-users-tie text-primary me-2"></i> Directorio del Campeonato</h2>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDirectiva" onclick="nuevoFamiliar()">
        <i class="fa-solid fa-plus me-1"></i> Registrar Miembro
    </button>
</div>

<div class="card shadow-sm border-top border-primary border-4 mb-4">
    <div class="card-body bg-light">
        <p class="mb-0 text-muted">Añada aquí a las personas que conforman la mesa directiva oficial del campeonato.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table id="tablaDirectiva" class="table table-hover w-100 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nombre Completo</th>
                    <th>Cargo</th>
                    <th>Teléfono</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalDirectiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formDirectiva">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Miembro del Directorio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="guardar">
                    <input type="hidden" name="id" id="id_directivo">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Cargo Oficial <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cargo" id="cargo" required placeholder="Ej. Presidente, Secretario...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Teléfono / Celular</label>
                        <input type="text" class="form-control" name="telefono" id="telefono">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var tabla = $('#tablaDirectiva').DataTable({
        "ajax": { "url": "ajax/directiva.php?action=listar", "dataSrc": "data" },
        "columns": [
            { "data": "nombre", "className": "fw-bold" },
            { "data": "cargo", "render": function(data){ return `<span class="badge bg-secondary">${data}</span>`; } },
            { "data": "telefono" },
            { 
                "data": "id",
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-primary me-1" onclick='editar(${JSON.stringify(row)})'><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminar(${data})"><i class="fa-solid fa-trash"></i></button>`;
                }
            }
        ],
        "language": {"url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"}
    });

    $('#formDirectiva').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/directiva.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(r){
                if(r.success) {
                    $('#modalDirectiva').modal('hide');
                    Swal.fire('Registrado', 'Miembro guardado.', 'success');
                    tabla.ajax.reload();
                } else Swal.fire('Error', r.message, 'error');
            }
        });
    });

    window.nuevoFamiliar = function() {
        $('#formDirectiva')[0].reset();
        $('#id_directivo').val('');
    };

    window.editar = function(r) {
        $('#id_directivo').val(r.id);
        $('#nombre').val(r.nombre);
        $('#cargo').val(r.cargo);
        $('#telefono').val(r.telefono);
        $('#modalDirectiva').modal('show');
    };

    window.eliminar = function(id) {
        Swal.fire({
            title:'Borrar Miembro', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'
        }).then((res)=>{
            if(res.isConfirmed) {
                $.ajax({
                    url: 'ajax/directiva.php', type: 'POST', data: {action:'eliminar', id:id}, dataType:'json',
                    success: function() { tabla.ajax.reload(); }
                });
            }
        });
    };
});
</script>

<?php require_once 'includes/footer.php'; ?>
