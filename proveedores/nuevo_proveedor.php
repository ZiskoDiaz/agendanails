<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Nuevo Proveedor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['contacto'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['direccion']
        ]);
        
        showAlert('Proveedor creado exitosamente', 'success');
        redirect('../proveedores/index.php');
        
    } catch (Exception $e) {
        showAlert('Error al crear proveedor: ' . $e->getMessage(), 'error');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nuevo Proveedor</h2>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <form method="POST" onsubmit="return validarFormulario('form-proveedor');" id="form-proveedor">
            <div class="form-group">
                <label class="form-label">Nombre del Proveedor *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" placeholder="Ej: Distribuidora Beauty Pro">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" maxlength="100" placeholder="Nombre del representante">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" maxlength="20" placeholder="+56 9 XXXX XXXX">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="100" placeholder="contacto@proveedor.com">
            </div>

            <div class="form-group">
                <label class="form-label">Dirección</label>
                <textarea name="direccion" class="form-control" rows="2" placeholder="Dirección completa del proveedor..."></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Proveedor</button>
            </div>
        </form>
    </div>

    <!-- Tipos de proveedores sugeridos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tipos de Proveedores para Salón de Manicura</h2>
        </div>
        
        <p style="margin-bottom: 1.5rem; color: #666;">
            Algunos tipos de proveedores que podrías necesitar para tu salón:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">🎨 Productos de Belleza</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Esmaltes (OPI, Essie, Sally Hansen)</li>
                    <li>Geles y productos semipermanentes</li>
                    <li>Base coat y top coat</li>
                    <li>Removedores y acetona</li>
                    <li>Productos para nail art</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">🛠️ Herramientas y Equipos</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Limas, cortacutículas, alicates</li>
                    <li>Lámparas UV/LED</li>
                    <li>Torno de uñas</li>
                    <li>Aspiradores de polvo</li>
                    <li>Sillones y mesas de manicura</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">🧴 Productos de Cuidado</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Aceites para cutículas</li>
                    <li>Cremas hidratantes</li>
                    <li>Exfoliantes para manos</li>
                    <li>Tratamientos fortalecedores</li>
                    <li>Productos antifúngicos</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">📦 Suministros Generales</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Algodón y gasas</li>
                    <li>Papel aluminio</li>
                    <li>Guantes desechables</li>
                    <li>Toallas desechables</li>
                    <li>Productos de limpieza y desinfección</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">✨ Decoración y Accesorios</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Strass y piedras decorativas</li>
                    <li>Glitter y polvos</li>
                    <li>Stickers y calcomanías</li>
                    <li>Cintas y foils</li>
                    <li>Tips y formas para extensiones</li>
                </ul>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-left: 3px solid #ff6b9d;">
                <h4 style="color: #ff6b9d; margin-bottom: 1rem;">🏪 Distribuidoras Locales</h4>
                <ul style="color: #666; font-size: 0.9rem; margin: 0; padding-left: 1.5rem;">
                    <li>Casa Bruschi (Santiago)</li>
                    <li>Beauty Supply (Varias ciudades)</li>
                    <li>Distribuidoras regionales</li>
                    <li>Importadores directos</li>
                    <li>Tiendas especializadas online</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>