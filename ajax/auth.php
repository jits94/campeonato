<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Llene todos los campos']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, nombre, password, rol, estado FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['estado'] !== 'activo') {
            echo json_encode(['success' => false, 'message' => 'El usuario se encuentra inactivo. contacte al administrador.']);
            exit;
        }
        
        // Verifica la contraseña encriptada por password_hash (por defecto 123456)
        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['rol'] = $row['rol'];
            
            echo json_encode(['success' => true, 'message' => 'Ingreso exitoso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario y/o contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición inválida']);
}
