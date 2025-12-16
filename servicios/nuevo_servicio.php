<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Nuevo Servicio';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO servicios (nombre, descripcion, precio, duracion_minutos) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['duracion_minutos']
        ]);
        
        showAlert('Servicio creado exitosamente', 'success');
        redirect('../servicios/index.php');
        
    } catch (Exception $e) {
        showAlert('Error al crear servicio: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nuevo Servicio</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-servicio');" id="form-servicio">
            <div class="form-group">
                <label class="form-label">Nombre del Servicio *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" placeholder="Ej: Esmaltado semipermanente">
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción detallada del servicio..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Precio *</label>
                        <input type="number" name="precio" class="form-control" required min="0" step="100" placeholder="15000">
                        <small style="color: #666;">Precio en pesos chilenos</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Duración (minutos) *</label>
                        <input type="number" name="duracion_minutos" class="form-control" required min="5" max="300" placeholder="60">
                        <small style="color: #666;">Tiempo estimado del servicio</small>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Servicio</button>
            </div>
        </form>
    </div>

    <!-- Servicios predefinidos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Servicios Predefinidos</h2>
        </div>
        
        <p style="margin-bottom: 1.5rem; color: #666;">
            Puedes usar estos servicios como referencia para crear los tuyos propios:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Esmaltado Común</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Incluye: retiro, limado, corte cutícula, hidratación y esmaltado común</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$15.000</span>
                    <span>60 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Esmaltado Semi-permanente</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Incluye: retiro, limado, corte cutícula, hidratación y esmaltado semipermanente</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$22.000</span>
                    <span>75 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Manicura Spa</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Incluye: retiro, limado, corte cutícula, exfoliación e hidratación profunda</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$18.000</span>
                    <span>70 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Capping/Refuerzo</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Incluye: preparación completa, gel reforzador e hidratación</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$28.000</span>
                    <span>90 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Extensiones de Uñas</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Incluye: preparación completa, colocación de extensiones, limado e hidratación</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$35.000</span>
                    <span>120 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Manicura Express</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Servicio rápido: limado básico, corte cutícula e hidratación (sin esmalte)</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$8.000</span>
                    <span>30 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Decoración Nail Art</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Diseños personalizados sobre base de esmalte (adicional a servicio base)</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$8.000</span>
                    <span>30 min</span>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 0.5rem;">Reparación de Uña</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Reparación de uña quebrada o dañada con gel o fibra</p>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888;">
                    <span>$5.000</span>
                    <span>20 min</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>