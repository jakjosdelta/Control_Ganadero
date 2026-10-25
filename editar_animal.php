<?php
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;
$animal = obtenerAnimal($id);

if (!$animal) {
    header('Location: index.php');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validaciones
        if (empty($_POST['codigo'])) {
            $errores[] = "El código es obligatorio";
        }
        if (empty($_POST['tipo'])) {
            $errores[] = "El tipo es obligatorio";
        }
        if (empty($_POST['sexo'])) {
            $errores[] = "El sexo es obligatorio";
        }
        
        // Validar imagen si se subió
        if (!empty($_FILES['imagen']['name'])) {
            $erroresImagen = validarImagen($_FILES['imagen']);
            $errores = array_merge($errores, $erroresImagen);
        }
        
        if (empty($errores)) {
            // Actualizar animal (sin manejar imágenes aquí)
            actualizarAnimal($id, $_POST, null);
            header('Location: ver_animal.php?id=' . $id . '&mensaje=actualizado');
            exit;
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

$vacas = obtenerVacas();
$toros = obtenerToros();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Animal - <?php echo htmlspecialchars($animal['codigo']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        header {
            background: linear-gradient(135deg, #2e7d32, #43a047);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .errores {
            background: #ffebee;
            border: 1px solid #ef5350;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-primary { background: #2e7d32; color: white; }
        .btn-secondary { background: #757575; color: white; }
        .btn-danger { background: #d32f2f; color: white; }
        .section-title {
            font-size: 1.3em;
            color: #2e7d32;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2e7d32;
        }
        .imagen-actual {
            max-width: 200px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .file-input-label {
            display: block;
            padding: 12px;
            background: #f5f5f5;
            border: 2px dashed #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input-label:hover {
            border-color: #2e7d32;
            background: #e8f5e9;
        }
        input[type="file"] { display: none; }
    </style>
    <script>
        function previsualizarImagen(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                    if (document.getElementById('imagen-actual')) {
                        document.getElementById('imagen-actual').style.display = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function eliminarImagenActual() {
            if (confirm('¿Está seguro de eliminar la imagen actual?')) {
                document.getElementById('eliminar_imagen').value = '1';
                document.getElementById('imagen-actual').style.display = 'none';
            }
        }
        
        function toggleCamposVenta() {
            const estado = document.getElementById('estado_venta').value;
            const camposVenta = document.getElementById('campos-venta');
            camposVenta.style.display = (estado === 'vendido') ? 'contents' : 'none';
        }
    </script>
</head>
<body>
    <header>
        <div class="container">
            <h1>✏️ Editar Animal - <?php echo htmlspecialchars($animal['codigo']); ?></h1>
        </div>
    </header>
    
    <div class="container">
        <div class="card">
            <?php if (!empty($errores)): ?>
            <div class="errores">
                <strong>⚠️ Errores encontrados:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">
                
                <h3 class="section-title">📋 Información Básica</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Código *</label>
                        <input type="text" name="codigo" required value="<?php echo htmlspecialchars($animal['codigo']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($animal['nombre']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="tipo" required>
                            <option value="vaca" <?php echo $animal['tipo'] === 'vaca' ? 'selected' : ''; ?>>Vaca</option>
                            <option value="ternero" <?php echo $animal['tipo'] === 'ternero' ? 'selected' : ''; ?>>Ternero</option>
                            <option value="toro" <?php echo $animal['tipo'] === 'toro' ? 'selected' : ''; ?>>Toro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Sexo *</label>
                        <select name="sexo" required>
                            <option value="hembra" <?php echo $animal['sexo'] === 'hembra' ? 'selected' : ''; ?>>Hembra</option>
                            <option value="macho" <?php echo $animal['sexo'] === 'macho' ? 'selected' : ''; ?>>Macho</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" required value="<?php echo $animal['fecha_nacimiento']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Peso Actual (kg)</label>
                        <input type="number" step="0.01" name="peso_actual" value="<?php echo $animal['peso_actual']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Raza</label>
                        <input type="text" name="raza" value="<?php echo htmlspecialchars($animal['raza']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="<?php echo htmlspecialchars($animal['color']); ?>">
                    </div>
                </div>
                
                <h3 class="section-title">👪 Genealogía</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Madre</label>
                        <select name="madre_id">
                            <option value="">Sin madre registrada</option>
                            <?php foreach ($vacas as $vaca): ?>
                                <option value="<?php echo $vaca['id']; ?>" <?php echo $animal['madre_id'] == $vaca['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vaca['codigo'] . ' - ' . $vaca['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Padre</label>
                        <select name="padre_id">
                            <option value="">Sin padre registrado</option>
                            <?php foreach ($toros as $toro): ?>
                                <option value="<?php echo $toro['id']; ?>" <?php echo $animal['padre_id'] == $toro['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($toro['codigo'] . ' - ' . $toro['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <h3 class="section-title">💰 Estado de Venta</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado_venta" id="estado_venta" onchange="toggleCamposVenta()">
                            <option value="disponible" <?php echo $animal['estado_venta'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                            <option value="reservado" <?php echo $animal['estado_venta'] === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                            <option value="vendido" <?php echo $animal['estado_venta'] === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Precio de Venta ($)</label>
                        <input type="number" step="0.01" name="precio_venta" value="<?php echo $animal['precio_venta']; ?>">
                    </div>
                </div>
                
                <div id="campos-venta" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha de Venta</label>
                            <input type="date" name="fecha_venta" value="<?php echo $animal['fecha_venta']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Comprador</label>
                            <input type="text" name="comprador" value="<?php echo htmlspecialchars($animal['comprador']); ?>">
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">📷 Imágenes</h3>
                
                <div style="background:#e8f5e9;border:1px solid #4caf50;border-radius:8px;padding:20px;text-align:center;">
                    <p style="margin-bottom:15px;color:#2e7d32;">
                        <strong>Las imágenes ahora se gestionan desde una página dedicada</strong>
                    </p>
                    <a href="gestionar_imagenes.php?animal_id=<?php echo $id; ?>" class="btn btn-primary">
                        📷 Ir a Gestionar Imágenes
                    </a>
                    <p style="font-size:12px;color:#666;margin-top:10px;">
                        Podrás agregar múltiples imágenes, establecer la principal y eliminar las que no necesites
                    </p>
                </div>
                
                <h3 class="section-title">📝 Observaciones</h3>
                
                <div class="form-group full">
                    <textarea name="observaciones"><?php echo htmlspecialchars($animal['observaciones']); ?></textarea>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                    <a href="ver_animal.php?id=<?php echo $id; ?>" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        toggleCamposVenta();
    </script>
</body>
</html>