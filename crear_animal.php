<?php
require_once 'includes/functions.php';

$errores = [];
$mensaje = '';

// Función para generar código automático
function generarCodigoAutomatico($tipo) {
    $db = getDB();
    $prefijo = '';
    
    switch($tipo) {
        case 'vaca':
            $prefijo = 'V';
            break;
        case 'toro':
            $prefijo = 'T';
            break;
        case 'ternero':
            $prefijo = 'TR';
            break;
    }
    
    // Obtener el último código de ese tipo
    $sql = "SELECT codigo FROM animales WHERE codigo LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefijo . '%']);
    $ultimo = $stmt->fetch();
    
    if ($ultimo) {
        // Extraer el número y sumar 1
        $numero = (int)preg_replace('/[^0-9]/', '', $ultimo['codigo']);
        $numero++;
    } else {
        $numero = 1;
    }
    
    return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generar código automático basado en el tipo
        $codigoGenerado = generarCodigoAutomatico($_POST['tipo']);
        $_POST['codigo'] = $codigoGenerado;
        // Validaciones
        if (empty($_POST['tipo'])) {
            $errores[] = "El tipo es obligatorio";
        }
        if (empty($_POST['sexo'])) {
            $errores[] = "El sexo es obligatorio";
        }
        if (empty($_POST['fecha_nacimiento'])) {
            $errores[] = "La fecha de nacimiento es obligatoria";
        }
        
        // Validar imagen si se subió
        if (!empty($_FILES['imagen']['name'])) {
            $erroresImagen = validarImagen($_FILES['imagen']);
            $errores = array_merge($errores, $erroresImagen);
        }
        
        if (empty($errores)) {
            // Crear animal primero
            $id = crearAnimal($_POST, null); // No pasamos imagen aquí
            
            // Subir imágenes si se seleccionaron
            if (!empty($_FILES['imagenes']['name'][0])) {
                $totalArchivos = count($_FILES['imagenes']['name']);
                
                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['imagenes']['name'][$i],
                            'type' => $_FILES['imagenes']['type'][$i],
                            'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                            'error' => $_FILES['imagenes']['error'][$i],
                            'size' => $_FILES['imagenes']['size'][$i]
                        ];
                        
                        try {
                            agregarImagenAnimal($id, $file, null);
                        } catch (Exception $e) {
                            // Continuar aunque falle una imagen
                        }
                    }
                }
            }
            
            header('Location: ver_animal.php?id=' . $id . '&mensaje=creado');
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
    <title>Crear Animal - Control Ganadero</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: linear-gradient(135deg, #2e7d32, #43a047);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header h1 {
            font-size: 2em;
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
        .errores ul {
            margin-left: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
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
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2e7d32;
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
        .btn-primary {
            background: #2e7d32;
            color: white;
        }
        .btn-primary:hover {
            background: #1b5e20;
        }
        .btn-secondary {
            background: #757575;
            color: white;
        }
        .btn-secondary:hover {
            background: #616161;
        }
        .section-title {
            font-size: 1.3em;
            color: #2e7d32;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2e7d32;
        }
        .file-input-wrapper {
            position: relative;
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
        input[type="file"] {
            display: none;
        }
        .preview-imagen {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 8px;
        }
    </style>
    <script>
        function previsualizarImagen(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function toggleCamposVenta() {
            const estado = document.getElementById('estado_venta').value;
            const camposVenta = document.getElementById('campos-venta');
            camposVenta.style.display = (estado === 'vendido') ? 'contents' : 'none';
        }
    </script>
    <script>
        function previsualizarImagen(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previsualizarImagenes(input) {
            const container = document.getElementById('preview-container');
            container.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.style.cssText = 'position:relative;border:2px solid #ddd;border-radius:8px;overflow:hidden;';
                        div.innerHTML = `
                            <img src="${e.target.result}" style="width:100%;height:150px;object-fit:cover;">
                            <div style="padding:5px;background:#f5f5f5;font-size:11px;text-align:center;">
                                ${index === 0 ? '⭐ Principal' : 'Imagen ' + (index + 1)}
                            </div>
                        `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</head>
<body>
    <header>
        <div class="container">
            <h1>➕ Crear Nuevo Animal</h1>
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
                <h3 class="section-title">📋 Información Básica</h3>
                
               
                    
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" placeholder="Ej: Estrella">
                    </div>

                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="tipo" required>
                            <option value="">Seleccione...</option>
                            <option value="vaca" <?php echo ($_POST['tipo'] ?? '') === 'vaca' ? 'selected' : ''; ?>>Vaca</option>
                            <option value="ternero" <?php echo ($_POST['tipo'] ?? '') === 'ternero' ? 'selected' : ''; ?>>Ternero</option>
                            <option value="toro" <?php echo ($_POST['tipo'] ?? '') === 'toro' ? 'selected' : ''; ?>>Toro</option>
                        </select>
                    </div>
                        
                                        
                    <div class="form-group">
                        <label>Sexo *</label>
                        <select name="sexo" required>
                            <option value="">Seleccione...</option>
                            <option value="hembra" <?php echo ($_POST['sexo'] ?? '') === 'hembra' ? 'selected' : ''; ?>>Hembra</option>
                            <option value="macho" <?php echo ($_POST['sexo'] ?? '') === 'macho' ? 'selected' : ''; ?>>Macho</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" required value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Peso Actual (kg)</label>
                        <input type="number" step="0.01" name="peso_actual" value="<?php echo htmlspecialchars($_POST['peso_actual'] ?? ''); ?>" placeholder="Ej: 450.50">
                    </div>
                    
                    <div class="form-group">
                        <label>Raza</label>
                        <input type="text" name="raza" value="<?php echo htmlspecialchars($_POST['raza'] ?? ''); ?>" placeholder="Ej: Holstein">
                    </div>
                    
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>" placeholder="Ej: Negro con blanco">
                    </div>
                </div>
                
                <h3 class="section-title">👪 Genealogía</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Madre</label>
                        <select name="madre_id">
                            <option value="">Sin madre registrada</option>
                            <?php foreach ($vacas as $vaca): ?>
                                <option value="<?php echo $vaca['id']; ?>" <?php echo ($_POST['madre_id'] ?? '') == $vaca['id'] ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $toro['id']; ?>" <?php echo ($_POST['padre_id'] ?? '') == $toro['id'] ? 'selected' : ''; ?>>
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
                            <option value="disponible" <?php echo ($_POST['estado_venta'] ?? 'disponible') === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                            <option value="reservado" <?php echo ($_POST['estado_venta'] ?? '') === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                            <option value="vendido" <?php echo ($_POST['estado_venta'] ?? '') === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Precio de Venta ($)</label>
                        <input type="number" step="0.01" name="precio_venta" value="<?php echo htmlspecialchars($_POST['precio_venta'] ?? ''); ?>">
                    </div>
                </div>
                
                <div id="campos-venta" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha de Venta</label>
                            <input type="date" name="fecha_venta" value="<?php echo htmlspecialchars($_POST['fecha_venta'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Comprador</label>
                            <input type="text" name="comprador" value="<?php echo htmlspecialchars($_POST['comprador'] ?? ''); ?>" placeholder="Nombre del comprador">
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">📷 Imagen</h3>
                
                <div class="form-group">
                    <div class="file-input-wrapper">
                        <label for="imagenes" class="file-input-label">
                            📁 Seleccionar imágenes del animal (puedes seleccionar varias)
                        </label>
                        <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" onchange="previsualizarImagenes(this)">
                    </div>
                    <div id="preview-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:15px;"></div>
                    <p style="font-size:12px;color:#666;margin-top:10px;">La primera imagen será la principal. Podrás agregar más después.</p>
                </div>
                
                <h3 class="section-title">📝 Observaciones</h3>
                
                <div class="form-group full">
                    <textarea name="observaciones" placeholder="Notas adicionales sobre el animal..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Animal</button>
                    <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Inicializar campos de venta al cargar
        toggleCamposVenta();
    </script>
</body>
</html>