<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Script temporal para crear/actualizar usuario admin

$database = new Database();
$db = $database->getConnection();

try {
    // Generar hash para la contraseña
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Verificar si el usuario admin ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_exists) {
        // Actualizar la contraseña del admin existente
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
        $stmt->execute([$password_hash]);
        echo "✅ Usuario admin actualizado correctamente<br>";
    } else {
        // Crear nuevo usuario admin
        $stmt = $db->prepare("
            INSERT INTO usuarios (nombre, username, password, rol, activo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['Administrador', 'admin', $password_hash, 'admin', 1]);
        echo "✅ Usuario admin creado correctamente<br>";
    }
    
    echo "<br><strong>Credenciales de acceso:</strong><br>";
    echo "Usuario: <strong>admin</strong><br>";
    echo "Contraseña: <strong>admin123</strong><br>";
    echo "<br>Hash generado: <code>$password_hash</code><br>";
    echo "<br><a href='login.php'>🔑 Ir al Login</a><br>";
    echo "<br><em>⚠️ Elimina este archivo después de usarlo por seguridad</em>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>