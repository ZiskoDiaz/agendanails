<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Nueva Cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO clientas (nombre, telefono, email, notas) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['notas']
        ]);
        
        showAlert('Cliente creada exitosamente', 'success');
        redirect('../clientas/index.php');
        
    } catch (Exception $e) {
        showAlert('Error al crear cliente: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nueva Cliente</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-cliente');" id="form-cliente">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required maxlength="100">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" maxlength="20">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="100">
            </div>

            <div class="form-group">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="3" placeholder="Información adicional sobre la cliente..."></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>