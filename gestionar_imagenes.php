<?php
require_once 'includes/functions.php';

// Verificar CSRF en POST (incluyendo AJAX)
\Core\Security::requireCsrf();

$animalId = $_GET['animal_id'] ?? 0;
$animal = obtenerAnimal($animalId);

if (!$animal) {
    header('Location: index.php');
    exit;
}

$imagenes = obtenerImagenesAnimal($animalId);
$mensaje = [];
$errores = [];

// Agregar imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_imagenes'])) {
    \Core\Security::saveOld($_POST);

    try {
        $rules = [
            'imagenes' => 'file_mimes:image/jpeg,image/png,image/gif|file_max:5120',
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        }

        if (empty($errores)) {
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
                        \Core\Security::clearOld();
                        \Core\Security::setFlash('success', "Se agregaron $subidas imagen(es) exitosamente");
                        header('Location: gestionar_imagenes.php?animal_id=' . $animalId);
                        exit;
                    } else {
                        $errores[] = "Debe seleccionar al menos una imagen";
                    }
                }
            } catch (Exception $e) {
                $errores[] = $e->getMessage();
            }
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

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <h1><i class="fas fa-images"></i> Gestionar Imágenes - <?php echo htmlspecialchars($animal['codigo']); ?></h1>
            <p><?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?></p>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link"><i class="fas fa-list"></i> Inventario</a>
                <a href="crear_animal.php" class="nav-link"><i class="fas fa-plus-circle"></i> Nuevo Animal</a>
                <a href="prenez.php" class="nav-link"><i class="fas fa-heartbeat"></i> Preñez</a>
                <a href="reportes.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reportes</a>
                <a href="galeria.php" class="nav-link"><i class="fas fa-images"></i> Galería</a>
            </nav>
        </div>
    </header>

    <main class="container" style="padding-top: var(--space-6); padding-bottom: var(--space-10);">
        <!-- Navegación -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-body" style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                <a href="ver_animal.php?id=<?php echo $animalId; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Animal
                </a>
                <a href="editar_animal.php?id=<?php echo $animalId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar Datos
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php
        $flashes = \Core\Security::getFlashes();
        if (!empty($flashes['success'])): ?>
            <?php foreach ($flashes['success'] as $msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div class="alert-content"><?php echo htmlspecialchars($msg); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($flashes['error'])): ?>
            <?php foreach ($flashes['error'] as $msg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div class="alert-content"><?php echo htmlspecialchars($msg); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div class="alert-content">
                    <strong>⚠️ Errores:</strong>
                    <ul style="margin: var(--space-2) 0 0 var(--space-4);">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Galería de imágenes actuales -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-photo-video"></i> Imágenes Actuales (<?php echo count($imagenes); ?>)</h3>
            </div>
            <div class="card-body">

                <?php if (!empty($imagenes)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-4);">
                        <?php foreach ($imagenes as $img): ?>
                        <article class="card" id="img-<?php echo $img['id']; ?>" style="<?php echo $img['es_principal'] ? 'border: 2px solid var(--color-primary); box-shadow: 0 0 20px rgba(46, 125, 50, 0.2);' : ''; ?>">
                            <?php if ($img['es_principal']): ?>
                                <div style="position: absolute; top: var(--space-3); right: var(--space-3); background: var(--color-primary); color: white; padding: var(--space-1) var(--space-2); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: 600; display: flex; align-items: center; gap: var(--space-1); z-index: 10;">
                                    <i class="fas fa-star"></i> PRINCIPAL
                                </div>
                            <?php endif; ?>

                            <div style="position: relative; aspect-ratio: 4/3; overflow: hidden; background: var(--color-gray-100);">
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['nombre_archivo']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>

                            <div class="card-body" style="padding: var(--space-4);">
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($img['descripcion'] ?? ''); ?>" placeholder="Descripción..." onblur="actualizarDescripcion(<?php echo $img['id']; ?>, this.value)">

                                <div style="display: flex; gap: var(--space-2); margin-top: var(--space-3); flex-wrap: wrap;">
                                    <?php if (!$img['es_principal']): ?>
                                        <button onclick="establecerPrincipal(<?php echo $img['id']; ?>)" class="btn btn-sm btn-primary flex-1">
                                            <i class="fas fa-star"></i> Hacer Principal
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: var(--text-sm); color: var(--color-primary); font-weight: 600; display: flex; align-items: center; justify-content: center; flex-1;">
                                            <i class="fas fa-check-circle"></i> Es la principal
                                        </span>
                                    <?php endif; ?>

                                    <button onclick="eliminarImagen(<?php echo $img['id']; ?>)" class="btn btn-sm btn-danger" style="background: var(--color-danger);">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>

                                <div style="font-size: var(--text-xs); color: var(--color-gray-400); margin-top: var(--space-2); text-align: center;">
                                    Subida: <?php echo date('d/m/Y H:i', strtotime($img['fecha_subida'])); ?>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="text-align: center; padding: var(--space-16);">
                        <div class="empty-state-icon" style="font-size: 4rem;">📷</div>
                        <h3 class="empty-state-title">No hay imágenes todavía</h3>
                        <p class="empty-state-text">Agrega algunas usando el formulario de abajo</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario agregar nuevas imágenes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Agregar Nuevas Imágenes</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="formImagenes">
                    <?= \Core\Security::csrfField() ?>
                    <input type="hidden" name="agregar_imagenes" value="1">

                    <div class="file-input-wrapper" style="cursor: pointer;" onclick="document.getElementById('input-imagenes').click()">
                        <label for="input-imagenes" class="file-input-label" style="cursor: pointer;">
                            <div style="font-size: 4rem; margin-bottom: var(--space-3);">📁</div>
                            <h3 style="margin-bottom: var(--space-1);">Haz clic o arrastra imágenes aquí</h3>
                            <p style="color: var(--color-gray-500);">Puedes seleccionar múltiples archivos a la vez</p>
                            <p style="font-size: var(--text-sm); color: var(--color-gray-400); margin-top: var(--space-3);">
                                Formatos: JPG, PNG, GIF | Tamaño máximo: 5MB por imagen
                            </p>
                        </label>
                        <input type="file" id="input-imagenes" name="imagenes[]" multiple accept="image/*" style="display:none;" onchange="previsualizarImagenes(this.files)">
                    </div>

                    <div id="preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--space-3); margin-top: var(--space-4);"></div>

                    <div id="botones-envio" style="margin-top: var(--space-4); display: none;">
                        <button type="submit" name="agregar_imagenes" class="btn btn-primary">
                            <i class="fas fa-save"></i> Subir <?php echo count($imagenes) == 0 ? '(La primera será principal)' : ''; ?>
                        </button>
                        <button type="button" onclick="limpiarSeleccion()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const csrfToken = '<?= \Core\Security::getCsrfToken() ?>';
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
                        div.style.cssText = 'position:relative;border:1px solid var(--color-gray-200);border-radius:var(--radius-lg);padding:var(--space-2);background:white;';
                        div.innerHTML = `
                            <button type="button" class="preview-remove" onclick="eliminarPreview(${index})" style="position:absolute;top:var(--space-2);right:var(--space-2);background:var(--color-danger);color:white;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:1.25rem;line-height:1;font-weight:bold;z-index:10;">×</button>
                            <img src="${e.target.result}" alt="Preview" style="width:100%;height:150px;object-fit:cover;border-radius:var(--radius-md);margin-bottom:var(--space-2);">
                            <input type="text" name="descripciones[]" placeholder="Descripción opcional" maxlength="255" style="width:100%;padding:var(--space-1);border:1px solid var(--color-gray-200);border-radius:var(--radius-sm);font-size:var(--text-sm);">
                            <div style="font-size:var(--text-xs);color:var(--color-gray-500);margin-top:var(--space-1);text-align:center;">
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
        const uploadSection = document.querySelector('.file-input-wrapper');

        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('drag-over');
            uploadSection.style.borderColor = 'var(--color-primary)';
            uploadSection.style.background = 'var(--color-primary-light)';
        });

        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('drag-over');
            uploadSection.style.borderColor = '';
            uploadSection.style.background = '';
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('drag-over');
            uploadSection.style.borderColor = '';
            uploadSection.style.background = '';

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
                body: 'ajax_principal=1&imagen_id=' + imagenId + '&_csrf=' + encodeURIComponent(csrfToken)
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
                body: 'ajax_eliminar=1&imagen_id=' + imagenId + '&_csrf=' + encodeURIComponent(csrfToken)
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
                body: 'ajax_descripcion=1&imagen_id=' + imagenId + '&descripcion=' + encodeURIComponent(descripcion) + '&_csrf=' + encodeURIComponent(csrfToken)
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