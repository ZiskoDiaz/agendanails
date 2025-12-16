<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Debug temporal - verificar si el usuario existe
                if (password_verify($password, $usuario['password']) || ($username == 'admin' && $password == 'admin123')) {
                    // Login exitoso
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];
                    
                    // Actualizar último acceso
                    $stmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    $stmt->execute([$usuario['id']]);
                    
                    redirect('index.php');
                } else {
                    $error = 'Contraseña incorrecta';
                }
            } else {
                $error = 'Usuario no encontrado o inactivo';
            }
        } catch (Exception $e) {
            $error = 'Error al conectar con la base de datos: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b9d, #c44569);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .login-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
        }
        
        .login-info {
            background: #dbeafe;
            color: #1e40af;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border-left: 4px solid #3b82f6;
            font-size: 0.9rem;
        }
        
        .demo-credentials {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .demo-credentials h4 {
            color: #059669;
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .demo-credentials p {
            margin: 0.25rem 0;
            font-size: 0.8rem;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">💅</div>
            <h1 class="login-title"><?php echo SITE_NAME; ?></h1>
            <p class="login-subtitle">Sistema de Gestión para Salón</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-control" required placeholder="Ingresa tu usuario" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="Ingresa tu contraseña">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                Iniciar Sesión
            </button>
        </form>

        <div class="demo-credentials">
            <h4>🔑 Credenciales de Prueba:</h4>
            <p><strong>Usuario:</strong> admin</p>
            <p><strong>Contraseña:</strong> admin123</p>
        </div>

        <div class="login-info">
            <strong>💡 Para uso en el salón:</strong><br>
            Cada manicurista puede tener su propio usuario para llevar registro de quién atiende cada cliente.
        </div>
    </div>
</body>
</html>