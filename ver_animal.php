<?php
require_once 'includes/functions.php';

// Verificar CSRF en POST
\Core\Security::requireCsrf();

$id = $_GET['id'] ?? 0;
$animal = obtenerAnimal($id);

if (!$animal) {
    header('Location: index.php');
    exit;
}

$hijos = obtenerHijos($id);
$pesajes = obtenerPesajes($id);
$imagenes = obtenerImagenesAnimal($id);

$mensaje = [];
$errores = [];

// Registrar pesaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pesaje'])) {
    \Core\Security::saveOld($_POST);

    try {
        $rules = [
            'fecha_pesaje' => 'required|date',
            'peso' => 'required|decimal|min:0|max:9999.99',
            'observaciones_pesaje' => 'max_len:500',
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        }

        if (empty($errores)) {
            registrarPesaje(
                $id,
                $_POST['fecha_pesaje'],
                $_POST['peso'],
                $_POST['observaciones_pesaje'] ?? null
            );
            \Core\Security::clearOld();
            \Core\Security::setFlash('success', 'Pesaje registrado exitosamente');
            header('Location: ver_animal.php?id=' . $id);
            exit;
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

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
            $subidas = 0;
            if (!empty($_FILES['imagenes']['name'][0])) {
                $totalArchivos = count($_FILES['imagenes']['name']);

                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['imagenes']['name'][$i],
                            'type' => $_FILES['imagenes']['type'][$i],
                            'tmp_name' => $_FILES['imagenis']['tmp_name'][$i],
                            'error' => $_FILES['imagenes']['error'][$i],
                            'size' => $_FILES['imagenes']['size'][$i]
                        ];

                        $descripcion = $_POST['descripciones'][$i] ?? null;

                        try {
                            agregarImagenAnimal($id, $file, $descripcion);
                            $subidas++;
                        } catch (Exception $e) {
                            error_log("Error subiendo imagen: " . $e->getMessage());
                        }
                    }
                }
            }

            if ($subidas > 0) {
                \Core\Security::clearOld();
                \Core\Security::setFlash('success', 'Imagen(es) agregada(s) exitosamente');
                header('Location: ver_animal.php?id=' . $id);
                exit;
            } else {
                $errores[] = 'Debe seleccionar al menos una imagen';
            }
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

// Establecer imagen principal (convertir a POST para CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'principal' && isset($_POST['imagen_id'])) {
    try {
        establecerImagenPrincipal($_POST['imagen_id'], $id);
        \Core\Security::setFlash('success', 'Imagen principal actualizada');
        header('Location: ver_animal.php?id=' . $id);
        exit;
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

// Eliminar imagen (convertir a POST para CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_img' && isset($_POST['imagen_id'])) {
    try {
        eliminarImagenAnimal($_POST['imagen_id'], $id);
        \Core\Security::setFlash('success', 'Imagen eliminada exitosamente');
        header('Location: ver_animal.php?id=' . $id);
        exit;
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Animal - <?php echo htmlspecialchars($animal['codigo']); ?></title>

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
            <h1><i class="fas fa-cow"></i> <?php echo htmlspecialchars($animal['codigo']); ?> - <?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?></h1>
        </div>
    </header>

    <main class="container" style="padding-top: var(--space-6); padding-bottom: var(--space-10);">
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

        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-body" style="display: flex; gap: var(--space-3); justify-content: flex-start; flex-wrap: wrap;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="editar_animal.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="gestionar_imagenes.php?animal_id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-images"></i> Imágenes
                </a>
                <button onclick="mostrarModalPesaje()" class="btn btn-success">
                    <i class="fas fa-weight"></i> Pesaje
                </button>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr; gap: var(--space-6);">
            @media (min-width: 992px) {
                .grid {
                    grid-template-columns: 380px 1fr;
                }
            }
            <style>
                @media (min-width: 992px) {
                    .grid { grid-template-columns: 380px 1fr; gap: var(--space-6); }
                }
            </style>

            <!-- Columna Izquierda: Galería + Info Básica -->
            <div>
                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-images"></i> Galería</h3>
                    </div>
                    <div class="card-body">

                        <?php if (!empty($imagenes)): ?>
                            <!-- Carousel principal -->
                            <div class="carousel slide" data-bs-ride="carousel" id="animalCarousel" style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: var(--space-4);">
                                <div class="carousel-indicators">
                                    <?php foreach ($imagenes as $index => $img): ?>
                                        <button type="button" data-bs-target="#animalCarousel" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active"' : ''; ?> aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="carousel-inner">
                                    <?php foreach ($imagenes as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="position-relative">
                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['nombre_archivo']); ?>" class="d-block w-100 rounded" alt="<?php echo htmlspecialchars($img['descripcion'] ?: 'Imagen del animal'); ?>" style="max-height: 400px; object-fit: cover; width: 100%;">

                                            <?php if ($img['es_principal']): ?>
                                                <span class="badge badge-success position-absolute top-0 end-0 m-3" style="z-index: 10;">⭐ PRINCIPAL</span>
                                            <?php endif; ?>

                                            <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.6); border-radius: var(--radius-md); padding: var(--space-2) var(--space-3);">
                                                <?php if ($img['descripcion']): ?>
                                                    <p class="mb-0"><?php echo htmlspecialchars($img['descripcion']); ?></p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-center gap-2" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); z-index: 5;">
                                                <?php if (!$img['es_principal']): ?>
                                                    <button onclick="establecerPrincipal(<?php echo $img['id']; ?>)" class="btn btn-sm btn-light">
                                                        <i class="fas fa-star"></i> Principal
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="eliminarImagen(<?php echo $img['id']; ?>)" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <button class="carousel-control-prev" type="button" data-bs-target="#animalCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#animalCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>

                        <?php else: ?>
                            <div class="empty-state" style="text-align: center; padding: var(--space-10);">
                                <div class="empty-state-icon">📷</div>
                                <h3 class="empty-state-title">No hay imágenes aún</h3>
                                <p class="empty-state-text">Agrega algunas abajo</p>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario agregar imágenes -->
                        <h3 style="margin-top: var(--space-6); margin-bottom: var(--space-4); color: var(--color-primary);"><i class="fas fa-plus-circle"></i> Agregar Nuevas Imágenes</h3>

                        <form method="POST" enctype="multipart/form-data" id="formImagenes">
                            <?= \Core\Security::csrfField() ?>
                            <input type="hidden" name="agregar_imagenes" value="1">
                            <div class="file-input-wrapper" style="cursor: pointer;" onclick="document.getElementById('input-imagenes').click()">
                                <label for="input-imagenes" class="file-input-label" style="cursor: pointer;">
                                    <div style="font-size: 3rem; margin-bottom: var(--space-2);">📁</div>
                                    <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: var(--space-1);">Haz clic para seleccionar imágenes</p>
                                    <p style="font-size: 0.875rem; color: var(--color-gray-500);">o arrastra y suelta aquí</p>
                                    <p style="font-size: 0.75rem; color: var(--color-gray-400); margin-top: var(--space-3);">JPG, PNG, GIF - Máximo 5MB por imagen</p>
                                </label>
                                <input type="file" id="input-imagenes" name="imagenes[]" multiple accept="image/*" style="display:none;" onchange="previsualizarImagenes(this.files)">
                            </div>

                            <div id="imagenes-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: var(--space-3); margin-top: var(--space-4); padding: var(--space-2);"></div>

                            <div id="btn-subir" style="margin-top: var(--space-4); display: none;">
                                <button type="submit" name="agregar_imagenes" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Subir Imágenes
                                </button>
                                <button type="button" onclick="limpiarPrevisualizacion()" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </form>

                        <!-- Info básica del animal en la sidebar -->
                        <hr style="margin: var(--space-6) 0;">

                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-hashtag"></i> Código</div>
                                <div class="info-value"><strong><?php echo htmlspecialchars($animal['codigo']); ?></strong></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-cow"></i> Tipo</div>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo $animal['tipo']; ?>">
                                        <?php echo ucfirst($animal['tipo']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-dollar-sign"></i> Estado</div>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo $animal['estado_venta']; ?>">
                                        <?php echo ucfirst($animal['estado_venta']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($animal['precio_venta']): ?>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-money-bill"></i> Precio</div>
                                <div class="info-value">$<?php echo number_format($animal['precio_venta'], 2); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Información detallada -->
            <div>
                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información General</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-tag"></i> Nombre</div>
                                <div class="info-value"><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-venus-mars"></i> Sexo</div>
                                <div class="info-value"><?php echo ucfirst($animal['sexo']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-birthday-cake"></i> Fecha de Nacimiento</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($animal['fecha_nacimiento'])); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-calendar-alt"></i> Edad</div>
                                <div class="info-value"><?php echo calcularEdad($animal['fecha_nacimiento']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-weight"></i> Peso Actual</div>
                                <div class="info-value"><?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) . ' kg' : '-'; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-dna"></i> Raza</div>
                                <div class="info-value"><?php echo htmlspecialchars($animal['raza'] ?: '-'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-palette"></i> Color</div>
                                <div class="info-value"><?php echo htmlspecialchars($animal['color'] ?: '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> Genealogía</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-female"></i> Madre</div>
                                <div class="info-value">
                                    <?php if ($animal['madre_id']): ?>
                                        <a href="ver_animal.php?id=<?php echo $animal['madre_id']; ?>" style="color: var(--color-primary);">
                                            <?php echo htmlspecialchars($animal['codigo_madre'] . ' - ' . $animal['nombre_madre']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-male"></i> Padre</div>
                                <div class="info-value">
                                    <?php if ($animal['padre_id']): ?>
                                        <a href="ver_animal.php?id=<?php echo $animal['padre_id']; ?>" style="color: var(--color-primary);">
                                            <?php echo htmlspecialchars($animal['codigo_padre'] . ' - ' . $animal['nombre_padre']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($hijos)): ?>
                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-baby"></i> Hijos</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Fecha Nac.</th>
                                        <th>Edad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hijos as $hijo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($hijo['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($hijo['nombre'] ?: '-'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($hijo['fecha_nacimiento'])); ?></td>
                                        <td><?php echo calcularEdad($hijo['fecha_nacimiento']); ?></td>
                                        <td>
                                            <a href="ver_animal.php?id=<?php echo $hijo['id']; ?>" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($animal['estado_venta'] === 'vendido'): ?>
                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Información de Venta</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-calendar"></i> Fecha de Venta</div>
                                <div class="info-value"><?php echo $animal['fecha_venta'] ? date('d/m/Y', strtotime($animal['fecha_venta'])) : '-'; ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label"><i class="fas fa-user"></i> Comprador</div>
                                <div class="info-value"><?php echo htmlspecialchars($animal['comprador'] ?: '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($animal['observaciones']): ?>
                <div class="card" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note"></i> Observaciones</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-value" style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($animal['observaciones'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-weight"></i> Historial de Pesajes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pesajes)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Peso (kg)</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesajes as $pesaje): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($pesaje['fecha_pesaje'])); ?></td>
                                        <td><strong><?php echo number_format($pesaje['peso'], 2); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pesaje['observaciones'] ?: '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state" style="text-align: center; padding: var(--space-8);">
                            <div class="empty-state-icon">⚖️</div>
                            <h3 class="empty-state-title">No hay pesajes registrados</h3>
                            <p class="empty-state-text">Usa el botón "Pesaje" para registrar el primero</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Pesaje -->
    <div class="modal" id="modalPesaje" style="display: none;">
        <div class="modal-backdrop" onclick="cerrarModalPesaje()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-weight"></i> Registrar Nuevo Pesaje</h2>
                <button class="modal-close" onclick="cerrarModalPesaje()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <?= \Core\Security::csrfField() ?>
                <input type="hidden" name="registrar_pesaje" value="1">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-input" name="fecha_pesaje" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-weight-hanging"></i> Peso (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-input" name="peso" required placeholder="Ej: 450.50">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-comment"></i> Observaciones</label>
                        <textarea class="form-input" name="observaciones_pesaje" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModalPesaje()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formularios ocultos para acciones de imágenes (CSRF) -->
    <form id="formImagenPrincipal" method="POST" style="display:none;">
        <?= \Core\Security::csrfField() ?>
        <input type="hidden" name="accion" value="principal">
        <input type="hidden" name="imagen_id" id="imagen_id_principal">
    </form>
    <form id="formEliminarImagen" method="POST" style="display:none;">
        <?= \Core\Security::csrfField() ?>
        <input type="hidden" name="accion" value="eliminar_img">
        <input type="hidden" name="imagen_id" id="imagen_id_eliminar">
    </form>

    <!-- Bootstrap JS (para carousel y modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let archivosSeleccionados = [];

        function previsualizarImagenes(files) {
            const preview = document.getElementById('imagenes-preview');
            const btnSubir = document.getElementById('btn-subir');

            archivosSeleccionados = Array.from(files);
            preview.innerHTML = '';

            if (archivosSeleccionados.length > 0) {
                btnSubir.style.display = 'block';

                archivosSeleccionados.forEach((file, index) => {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.style.cssText = 'position:relative;background:var(--color-gray-50);border:1px solid var(--color-gray-200);border-radius:var(--radius-lg);overflow:hidden;padding:var(--space-2);';
                        div.innerHTML = `
                            <button type="button" class="preview-remove" onclick="eliminarPreview(${index})" style="position:absolute;top:var(--space-2);right:var(--space-2);background:var(--color-danger);color:white;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:1rem;line-height:1;font-weight:bold;z-index:10;">×</button>
                            <img src="${e.target.result}" alt="Preview" style="width:100%;height:110px;object-fit:cover;border-radius:var(--radius-md);display:block;">
                            <input type="text" name="descripciones[]" placeholder="Descripción (opcional)" maxlength="255" style="margin-top:var(--space-2);font-size:0.75rem;padding:var(--space-1);border:1px solid var(--color-gray-200);border-radius:var(--radius-sm);width:100%;">
                        `;
                        preview.appendChild(div);
                    };

                    reader.readAsDataURL(file);
                });
            } else {
                btnSubir.style.display = 'none';
            }
        }

        function eliminarPreview(index) {
            archivosSeleccionados.splice(index, 1);

            // Recrear el input file con los archivos restantes
            const dt = new DataTransfer();
            archivosSeleccionados.forEach(file => dt.items.add(file));
            document.getElementById('input-imagenes').files = dt.files;

            previsualizarImagenes(archivosSeleccionados);
        }

        function limpiarPrevisualizacion() {
            archivosSeleccionados = [];
            document.getElementById('input-imagenes').value = '';
            document.getElementById('imagenes-preview').innerHTML = '';
            document.getElementById('btn-subir').style.display = 'none';
        }

        // Drag and drop
        const uploadArea = document.querySelector('.file-input-wrapper');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
            uploadArea.style.borderColor = 'var(--color-primary)';
            uploadArea.style.background = 'var(--color-primary-light)';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
            uploadArea.style.borderColor = '';
            uploadArea.style.background = '';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            uploadArea.style.borderColor = '';
            uploadArea.style.background = '';

            const files = e.dataTransfer.files;
            document.getElementById('input-imagenes').files = files;
            previsualizarImagenes(files);
        });

        function establecerPrincipal(imagenId) {
            if (confirm('¿Establecer esta imagen como principal?')) {
                document.getElementById('imagen_id_principal').value = imagenId;
                document.getElementById('formImagenPrincipal').submit();
            }
        }

        function eliminarImagen(imagenId) {
            if (confirm('¿Está seguro de eliminar esta imagen?')) {
                document.getElementById('imagen_id_eliminar').value = imagenId;
                document.getElementById('formEliminarImagen').submit();
            }
        }

        function mostrarModalPesaje() {
            document.getElementById('modalPesaje').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalPesaje() {
            document.getElementById('modalPesaje').style.display = 'none';
            document.body.style.overflow = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalPesaje');
            if (event.target === modal) {
                cerrarModalPesaje();
            }
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalPesaje();
            }
        });
    </script>
</body>
</html>