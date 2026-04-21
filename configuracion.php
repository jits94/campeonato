<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/database.php';

if ($_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>No tienes permisos para acceder a esta sección.</div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold text-dark"><i class="fa-solid fa-gears text-primary me-2"></i> Configuración del Sistema</h2>
        <p class="text-muted">Gestione los accesos de usuarios y la información de la mesa directiva.</p>
    </div>
</div>

<!-- Estilo para Tabs -->
<style>
    .nav-tabs .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        padding: 1rem 1.5rem;
    }
    .nav-tabs .nav-link.active {
        color: #3b82f6;
        background: transparent;
        border-bottom: 3px solid #3b82f6;
    }
    .card-settings {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>

<div class="card card-settings shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <ul class="nav nav-tabs card-header-tabs" id="configTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab" aria-selected="true">
                    <i class="fa-solid fa-users me-2"></i> Usuarios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="directiva-tab" data-bs-toggle="tab" data-bs-target="#directiva" type="button" role="tab" aria-selected="false">
                    <i class="fa-solid fa-users-tie me-2"></i> Mesa Directiva
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="configTabsContent">
            
            <!-- Pestaña Usuarios -->
            <div class="tab-pane fade show active" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Gestión de Accesos</h5>
                    <button class="btn btn-primary btn-sm" onclick="nuevoUsuario()">
                        <i class="fa-solid fa-user-plus me-1"></i> Nuevo Usuario
                    </button>
                </div>
                <table id="tablaUsuarios" class="table table-hover w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Pestaña Directiva -->
            <div class="tab-pane fade" id="directiva" role="tabpanel" aria-labelledby="directiva-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Miembros del Directorio</h5>
                    <button class="btn btn-primary btn-sm" onclick="nuevoDirectivo()">
                        <i class="fa-solid fa-user-tie me-1"></i> Registrar Miembro
                    </button>
                </div>
                <table id="tablaDirectiva" class="table table-hover w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Torneo / Gestión</th>
                            <th>Nombre Completo</th>
                            <th>Cargo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formUsuario">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Gestionar Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="u_id">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="u_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Usuario (Login) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="usuario" id="u_usuario" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Rol</label>
                        <select class="form-select" name="rol" id="u_rol">
                            <option value="administrador">Administrador</option>
                            <option value="veedor">Veedor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contraseña</label>
                        <input type="password" class="form-control" name="password" id="u_password" placeholder="Dejar en blanco para no cambiar">
                        <small class="text-muted" id="pass_help">Para nuevos usuarios es obligatoria.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Directiva -->
<div class="modal fade" id="modalDirectiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formDirectiva">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Miembro del Directorio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="id" id="d_id">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Torneo / Gestión <span class="text-danger">*</span></label>
                        <select class="form-select" name="torneo_id" id="d_torneo_id" required>
                            <option value="">Seleccione un Torneo...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="d_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Cargo Oficial <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cargo" id="d_cargo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Teléfono / Celular</label>
                        <input type="text" class="form-control" name="telefono" id="d_telefono">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark">Guardar Miembro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Tabla Usuarios
    var tablaUsuarios = $('#tablaUsuarios').DataTable({
        "ajax": { "url": "ajax/usuarios.php?action=listar", "dataSrc": "data" },
        "columns": [
            { "data": "nombre", "className": "fw-bold" },
            { "data": "usuario" },
            { 
                "data": "rol", 
                "render": function(data){ 
                    let color = data === 'administrador' ? 'primary' : 'secondary';
                    return `<span class="badge bg-${color}">${data.toUpperCase()}</span>`;
                } 
            },
            { 
                "data": "estado",
                "render": function(data){
                    let color = data === 'activo' ? 'success' : 'danger';
                    return `<span class="badge bg-${color}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                "data": "id",
                "className": "text-center",
                "render": function(data, type, row) {
                    let btnEstado = row.estado === 'activo' 
                        ? `<button class="btn btn-sm btn-outline-danger me-1" title="Desactivar" onclick="cambiarEstadoUsuario(${data}, 'inactivo')"><i class="fa-solid fa-user-slash"></i></button>`
                        : `<button class="btn btn-sm btn-outline-success me-1" title="Activar" onclick="cambiarEstadoUsuario(${data}, 'activo')"><i class="fa-solid fa-user-check"></i></button>`;
                    
                    return `<button class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick='editarUsuario(${JSON.stringify(row)})'><i class="fa-solid fa-pen"></i></button>
                            ${btnEstado}`;
                }
            }
        ],
        "language": {"url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"}
    });

    // 2. Tabla Directiva
    var tablaDirectiva = $('#tablaDirectiva').DataTable({
        "ajax": { "url": "ajax/directiva.php?action=listar", "dataSrc": "data" },
        "columns": [
            { "data": "torneo", "render": function(data){ return `<span class="badge bg-light text-dark border">${data || 'N/A'}</span>`; } },
            { "data": "nombre", "className": "fw-bold" },
            { "data": "cargo", "render": function(data){ return `<span class="badge bg-secondary">${data}</span>`; } },
            { "data": "telefono" },
            { 
                "data": "estado",
                "render": function(data){
                    let color = data === 'activo' ? 'success' : 'danger';
                    return `<span class="badge bg-${color}">${(data || 'ACTIVO').toUpperCase()}</span>`;
                }
            },
            { 
                "data": "id",
                "className": "text-center",
                "render": function(data, type, row) {
                    let btnEstado = row.estado === 'activo' 
                        ? `<button class="btn btn-sm btn-outline-danger me-1" title="Desactivar" onclick="cambiarEstadoDirectivo(${data}, 'inactivo')"><i class="fa-solid fa-user-slash"></i></button>`
                        : `<button class="btn btn-sm btn-outline-success me-1" title="Activar" onclick="cambiarEstadoDirectivo(${data}, 'activo')"><i class="fa-solid fa-user-check"></i></button>`;
                    
                    return `<button class="btn btn-sm btn-outline-primary me-1" onclick='editarDirectivo(${JSON.stringify(row)})'><i class="fa-solid fa-pen"></i></button>
                            ${btnEstado}`;
                }
            }
        ],
        "language": {"url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"}
    });

    // Formulario Usuario
    $('#formUsuario').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/usuarios.php?action=guardar', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(r){
                if(r.success) {
                    $('#modalUsuario').modal('hide');
                    Swal.fire('Éxito', 'Usuario guardado correctamente.', 'success');
                    tablaUsuarios.ajax.reload();
                } else Swal.fire('Error', r.message, 'error');
            }
        });
    });

    // Formulario Directiva
    $('#formDirectiva').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/directiva.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(r){
                if(r.success) {
                    $('#modalDirectiva').modal('hide');
                    Swal.fire('Éxito', 'Registro de directiva actualizado.', 'success');
                    tablaDirectiva.ajax.reload();
                } else Swal.fire('Error', r.message, 'error');
            }
        });
    });

    // Cargar Torneos para select
    function cargarTorneos() {
        $.ajax({
            url: 'ajax/torneos.php?action=listar', type: 'GET', dataType: 'json',
            success: function(r) {
                let options = '<option value="">Seleccione un Torneo...</option>';
                r.data.forEach(t => {
                    options += `<option value="${t.id}">${t.nombre}</option>`;
                });
                $('#d_torneo_id').html(options);
            }
        });
    }
    cargarTorneos();

    // Funciones Usuarios
    window.nuevoUsuario = function() {
        $('#formUsuario')[0].reset();
        $('#u_id').val('');
        $('#modalUsuario').modal('show');
    };

    window.editarUsuario = function(r) {
        $('#u_id').val(r.id);
        $('#u_nombre').val(r.nombre);
        $('#u_usuario').val(r.usuario);
        $('#u_rol').val(r.rol);
        $('#u_password').val('');
        $('#modalUsuario').modal('show');
    };

    window.cambiarEstadoUsuario = function(id, nuevoEstado) {
        let msg = nuevoEstado === 'inactivo' ? '¿Desactivar este usuario?' : '¿Activar este usuario?';
        let confirmBtn = nuevoEstado === 'inactivo' ? '#d33' : '#28a745';

        Swal.fire({
            title: msg,
            text: nuevoEstado === 'inactivo' ? "El usuario ya no podrá iniciar sesión." : "El usuario podrá acceder al sistema nuevamente.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmBtn,
            confirmButtonText: nuevoEstado === 'inactivo' ? 'Sí, desactivar' : 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if(res.isConfirmed) {
                $.ajax({
                    url: 'ajax/usuarios.php?action=cambiar_estado',
                    type: 'POST',
                    data: {id: id, estado: nuevoEstado},
                    dataType: 'json',
                    success: function(r) { 
                        if(r.success) {
                            Swal.fire('Actualizado', 'Estado de usuario actualizado.', 'success');
                            tablaUsuarios.ajax.reload();
                        } else Swal.fire('Error', r.message, 'error');
                    }
                });
            }
        });
    };

    // Funciones Directiva
    window.nuevoDirectivo = function() {
        $('#formDirectiva')[0].reset();
        $('#d_id').val('');
        $('#modalDirectiva').modal('show');
    };

    window.editarDirectivo = function(r) {
        $('#d_id').val(r.id);
        $('#d_torneo_id').val(r.torneo_id);
        $('#d_nombre').val(r.nombre);
        $('#d_cargo').val(r.cargo);
        $('#d_telefono').val(r.telefono);
        $('#modalDirectiva').modal('show');
    };

    window.cambiarEstadoDirectivo = function(id, nuevoEstado) {
        let msg = nuevoEstado === 'inactivo' ? '¿Desactivar este directivo?' : '¿Activar este directivo?';
        let confirmBtn = nuevoEstado === 'inactivo' ? '#d33' : '#28a745';

        Swal.fire({
            title: msg,
            text: nuevoEstado === 'inactivo' ? "El directivo quedará inactivo en el sistema." : "El directivo volverá a estar activo.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmBtn,
            confirmButtonText: nuevoEstado === 'inactivo' ? 'Sí, desactivar' : 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if(res.isConfirmed) {
                $.ajax({
                    url: 'ajax/directiva.php?action=cambiar_estado',
                    type: 'POST',
                    data: {id: id, estado: nuevoEstado},
                    dataType: 'json',
                    success: function(r) { 
                        if(r.success) {
                            Swal.fire('Actualizado', 'Estado actualizado.', 'success');
                            tablaDirectiva.ajax.reload();
                        } else Swal.fire('Error', r.message, 'error');
                    }
                });
            }
        });
    };

    window.eliminarDirectivo = function(id) {
        // Mantenemos por compatibilidad pero redirigimos a desactivar
        cambiarEstadoDirectivo(id, 'inactivo');
    };
});
</script>

<?php require_once 'includes/footer.php'; ?>
