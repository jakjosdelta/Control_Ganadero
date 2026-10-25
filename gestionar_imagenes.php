<?php
require_once 'includes/functions.php';

$animalId = $_GET['animal_id'] ?? 0;
$animal = obtenerAnimal($animalId);

if (!$animal) {
    header('Location: index.php');
    exit;
}

$imagenes = obtenerImagenesAnimal($animalId);
$mensaje = '';
$errores = [];

// Agregar imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_imagenes'])) {
    try {
        if (!empty($_FILES['imagenes']['name'][0])) {
            $totalArchivos = count($_FILES['imagenes']['name']);
            $subidas = 0;
            
            for ($i = 0; $i < $totalArchivos; $i++) {
                if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['imagenes']['name'][$i],
                        'type' => $_FILES['imagenes']['type'][$i],
                        'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                        'error' => $_FILES['imagenes']['error'][$i],
                        'size' => $_FILES['imagenes']['size'][$i]
                    ];
                    
                    $descripcion = $_POST['descripciones'][$i] ?? null;
                    
                    try {
                        agregarImagenAnimal($animalId, $file, $descripcion);
                        $subidas++;
                    } catch (Exception $e) {
                        $errores[] = "Imagen " . ($i + 1) . ": " . $e->getMessage();
                    }
                }
            }
            
            if ($subidas > 0) {
                $mensaje = "✅ Se agregaron $subidas imagen(es) exitosamente";
                $imagenes = obtenerImagenesAnimal($animalId); // Recargar
            }
        } else {
            $errores[] = "Debe seleccionar al menos una imagen";
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

// AJAX: Establecer principal
if (isset($_POST['ajax_principal'])) {
    header('Content-Type: application/json');
    try {
        establecerImagenPrincipal($_POST['imagen_id'], $animalId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Eliminar imagen
if (isset($_POST['ajax_eliminar'])) {
    header('Content-Type: application/json');
    try {
        eliminarImagenAnimal($_POST['imagen_id'], $animalId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Actualizar descripción
if (isset($_POST['ajax_descripcion'])) {
    header('Content-Type: application/json');
    try {
        actualizarDescripcionImagen($_POST['imagen_id'], $animalId, $_POST['descripcion']);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Imágenes - <?php echo htmlspecialchars($animal['codigo']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
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
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-primary { background: #2e7d32; color: white; }
        .btn-secondary { background: #757575; color: white; }
        .mensaje {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .errores {
            background: #ffebee;
            border: 1px solid #ef5350;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .galeria {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .imagen-card {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .imagen-card.principal {
            border-color: #2e7d32;
            box-shadow: 0 0 20px rgba(46, 125, 50, 0.3);
        }
        .imagen-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }
        .imagen-info {
            padding: 15px;
            background: white;
        }
        .badge-principal {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #2e7d32;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .imagen-acciones {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .upload-section {
            border: 3px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-section:hover {
            border-color: #2e7d32;
            background: #e8f5e9;
        }
        .upload-section.drag-over {
            border-color: #2e7d32;
            background: #e8f5e9;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            background: white;
        }
        .preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .preview-item input {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        .preview-remove {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            font-weight: bold;
        }
        .descripcion-editable {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .empty-gallery {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>📷 Gestionar Imágenes - <?php echo htmlspecialchars($animal['codigo']); ?></h1>
            <p><?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?></p>
        </div>
    </header>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="ver_animal.php?id=<?php echo $animalId; ?>" class="btn btn-secondary">← Volver al Animal</a>
            <a href="editar_animal.php?id=<?php echo $animalId; ?>" class="btn btn-primary">✏️ Editar Datos</a>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errores)): ?>
            <div class="errores">
                <strong>⚠️ Errores:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="margin-bottom:20px;color:#2e7d32;">🖼️ Imágenes Actuales (<?php echo count($imagenes); ?>)</h2>
            
            <?php if (!empty($imagenes)): ?>
                <div class="galeria">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="imagen-card <?php echo $img['es_principal'] ? 'principal' : ''; ?>" id="img-<?php echo $img['id']; ?>">
                            <?php if ($img['es_principal']): ?>
                                <div class="badge-principal">⭐ PRINCIPAL</div>
                            <?php endif; ?>
                            
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['nombre_archivo']); ?>" alt="Imagen">
                            
                            <div class="imagen-info">
                                <input type="text" 
                                       class="descripcion-editable" 
                                       value="<?php echo htmlspecialchars($img['descripcion'] ?? ''); ?>"
                                       placeholder="Descripción..."
                                       onblur="actualizarDescripcion(<?php echo $img['id']; ?>, this.value)">
                                
                                <div class="imagen-acciones">
                                    <?php if (!$img['es_principal']): ?>
                                        <button onclick="establecerPrincipal(<?php echo $img['id']; ?>)" 
                                                class="btn btn-primary btn-small">
                                            ⭐ Hacer Principal
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#2e7d32;font-weight:600;">✓ Es la principal</span>
                                    <?php endif; ?>
                                    
                                    <button onclick="eliminarImagen(<?php echo $img['id']; ?>)" 
                                            class="btn btn-secondary btn-small"
                                            style="background:#d32f2f;">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                                
                                <div style="font-size:11px;color:#999;margin-top:8px;">
                                    Subida: <?php echo date('d/m/Y H:i', strtotime($img['fecha_subida'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-gallery">
                    <div style="font-size:64px;margin-bottom:15px;">📷</div>
                    <p style="font-size:18px;margin-bottom:5px;">No hay imágenes todavía</p>
                    <p style="font-size:14px;">Agrega algunas usando el formulario de abajo</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom:20px;color:#2e7d32;">➕ Agregar Nuevas Imágenes</h2>
            
            <form method="POST" enctype="multipart/form-data" id="formImagenes">
                <div class="upload-section" onclick="document.getElementById('input-imagenes').click()">
                    <div style="font-size:64px;margin-bottom:15px;">📁</div>
                    <h3 style="margin-bottom:10px;">Haz clic o arrastra imágenes aquí</h3>
                    <p style="color:#666;">Puedes seleccionar múltiples archivos a la vez</p>
                    <p style="font-size:12px;color:#999;margin-top:15px;">
                        Formatos: JPG, PNG, GIF | Tamaño máximo: 5MB por imagen
                    </p>
                </div>
                
                <input type="file" 
                       id="input-imagenes" 
                       name="imagenes[]" 
                       multiple 
                       accept="image/*" 
                       style="display:none;"
                       onchange="previsualizarImagenes(this.files)">
                
                <div id="preview-grid" class="preview-grid"></div>
                
                <div id="botones-envio" style="margin-top:20px;display:none;">
                    <button type="submit" name="agregar_imagenes" class="btn btn-primary">
                        💾 Subir <?php echo count($imagenes) == 0 ? '(La primera será principal)' : ''; ?>
                    </button>
                    <button type="button" onclick="limpiarSeleccion()" class="btn btn-secondary">
                        ❌ Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let archivosSeleccionados = [];
        
        // Previsualización
        function previsualizarImagenes(files) {
            const preview = document.getElementById('preview-grid');
            const botones = document.getElementById('botones-envio');
            
            archivosSeleccionados = Array.from(files);
            preview.innerHTML = '';
            
            if (archivosSeleccionados.length > 0) {
                botones.style.display = 'block';
                
                archivosSeleccionados.forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <button type="button" class="preview-remove" onclick="eliminarPreview(${index})">×</button>
                            <img src="${e.target.result}" alt="Preview">
                            <input type="text" 
                                   name="descripciones[]" 
                                   placeholder="Descripción opcional"
                                   maxlength="255">
                            <div style="font-size:11px;color:#666;margin-top:5px;text-align:center;">
                                ${(file.size / 1024 / 1024).toFixed(2)} MB
                            </div>
                        `;
                        preview.appendChild(div);
                    };
                    
                    reader.readAsDataURL(file);
                });
            } else {
                botones.style.display = 'none';
            }
        }
        
        function eliminarPreview(index) {
            archivosSeleccionados.splice(index, 1);
            const dt = new DataTransfer();
            archivosSeleccionados.forEach(file => dt.items.add(file));
            document.getElementById('input-imagenes').files = dt.files;
            previsualizarImagenes(archivosSeleccionados);
        }
        
        function limpiarSeleccion() {
            archivosSeleccionados = [];
            document.getElementById('input-imagenes').value = '';
            document.getElementById('preview-grid').innerHTML = '';
            document.getElementById('botones-envio').style.display = 'none';
        }
        
        // Drag & Drop
        const uploadSection = document.querySelector('.upload-section');
        
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('drag-over');
        });
        
        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('drag-over');
        });
        
        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            document.getElementById('input-imagenes').files = files;
            previsualizarImagenes(files);
        });
        
        // AJAX: Establecer principal
        function establecerPrincipal(imagenId) {
            if (!confirm('¿Establecer esta imagen como principal?')) return;
            
            fetch('gestionar_imagenes.php?animal_id=<?php echo $animalId; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_principal=1&imagen_id=' + imagenId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        // AJAX: Eliminar imagen
        function eliminarImagen(imagenId) {
            if (!confirm('¿Está seguro de eliminar esta imagen?')) return;
            
            fetch('gestionar_imagenes.php?animal_id=<?php echo $animalId; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_eliminar=1&imagen_id=' + imagenId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('img-' + imagenId).remove();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        // AJAX: Actualizar descripción
        function actualizarDescripcion(imagenId, descripcion) {
            fetch('gestionar_imagenes.php?animal_id=<?php echo $animalId; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_descripcion=1&imagen_id=' + imagenId + '&descripcion=' + encodeURIComponent(descripcion)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar descripción: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>