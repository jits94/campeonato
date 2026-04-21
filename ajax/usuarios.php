<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'listar') {
    $stmt = $conn->query("SELECT id, nombre, usuario, rol, estado FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $usuarios]);
    exit;
}

if ($action === 'guardar') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $rol = $_POST['rol'] ?? 'veedor';
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($usuario)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y usuario son requeridos']);
        exit;
    }

    try {
        if (empty($id)) {
            // INSERT
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'La contraseña es requerida para nuevos usuarios']);
                exit;
            }
            $passHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (:nombre, :usuario, :pass, :rol)");
            $stmt->execute([':nombre' => $nombre, ':usuario' => $usuario, ':pass' => $passHash, ':rol' => $rol]);
        } else {
            // UPDATE
            if (!empty($password)) {
                $passHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, password = :pass, rol = :rol WHERE id = :id");
                $stmt->execute([':nombre' => $nombre, ':usuario' => $usuario, ':pass' => $passHash, ':rol' => $rol, ':id' => $id]);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, rol = :rol WHERE id = :id");
                $stmt->execute([':nombre' => $nombre, ':usuario' => $usuario, ':rol' => $rol, ':id' => $id]);
            }
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
        }
    }
    exit;
}

if ($action === 'cambiar_estado') {
    $id = $_POST['id'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';

    $stmt = $conn->prepare("UPDATE usuarios SET estado = :estado WHERE id = :id");
    if ($stmt->execute([':estado' => $estado, ':id' => $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
    exit;
}

if ($action === 'eliminar') {
    $id = $_POST['id'] ?? '';
    if ($id == $_SESSION['id_usuario']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminarte a ti mismo']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
    if ($stmt->execute([':id' => $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
