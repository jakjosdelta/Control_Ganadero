<?php
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;
$animal = obtenerAnimal($id);

if (!$animal) {
    header('Location: index.php');
    exit;
}

$hijos = obtenerHijos($id);
$pesajes = obtenerPesajes($id);
$imagenes = obtenerImagenesAnimal($id);

$mensaje = '';
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] === 'creado') {
        $mensaje = '✅ Animal creado exitosamente';
    } elseif ($_GET['mensaje'] === 'actualizado') {
        $mensaje = '✅ Animal actualizado exitosamente';
    } elseif ($_GET['mensaje'] === 'pesaje_registrado') {
        $mensaje = '✅ Pesaje registrado exitosamente';
    } elseif ($_GET['mensaje'] === 'imagen_agregada') {
        $mensaje = '✅ Imagen agregada exitosamente';
    } elseif ($_GET['mensaje'] === 'imagen_eliminada') {
        $mensaje = '✅ Imagen eliminada exitosamente';
    } elseif ($_GET['mensaje'] === 'imagen_principal') {
        $mensaje = '✅ Imagen principal actualizada';
    }
}

// Registrar pesaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pesaje'])) {
    try {
        registrarPesaje(
            $id,
            $_POST['fecha_pesaje'],
            $_POST['peso'],
            $_POST['observaciones_pesaje'] ?? null
        );
        header('Location: ver_animal.php?id=' . $id . '&mensaje=pesaje_registrado');
        exit;
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
    }
}

// Agregar imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_imagenes'])) {
    try {
        $errores = [];
        $subidas = 0;
        
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
                    
                    $descripcion = $_POST['descripciones'][$i] ?? null;
                    
                    try {
                        agregarImagenAnimal($id, $file, $descripcion);
                        $subidas++;
                    } catch (Exception $e) {
                        $errores[] = "Archivo " . ($i + 1) . ": " . $e->getMessage();
                    }
                }
            }
            
            if ($subidas > 0) {
                header('Location: ver_animal.php?id=' . $id . '&mensaje=imagen_agregada');
                exit;
            } elseif (!empty($errores)) {
                $mensaje = '❌ Errores: ' . implode(', ', $errores);
            }
        } else {
            $mensaje = '❌ Debe seleccionar al menos una imagen';
        }
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
    }
}

// Establecer imagen principal
if (isset($_GET['accion']) && $_GET['accion'] === 'principal' && isset($_GET['imagen_id'])) {
    try {
        establecerImagenPrincipal($_GET['imagen_id'], $id);
        header('Location: ver_animal.php?id=' . $id . '&mensaje=imagen_principal');
        exit;
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
    }
}

// Eliminar imagen
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar_img' && isset($_GET['imagen_id'])) {
    try {
        eliminarImagenAnimal($_GET['imagen_id'], $id);
        header('Location: ver_animal.php?id=' . $id . '&mensaje=imagen_eliminada');
        exit;
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Animal - <?php echo htmlspecialchars($animal['codigo']); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        /* Estilos responsivos para ver_animal.php */
        .grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }
        
        
    </style>
    <style>
        /* ==========================================================================
   Estilos base y reset
   ========================================================================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: #f7f9f8;
    color: #2d2d2d;
    line-height: 1.45;
}

/* ==========================================================================
   Layout principal
   ========================================================================== */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}

.grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

/* Imagen principal + info básica a la izquierda en pantallas grandes */
@media (min-width: 992px) {
    .grid {
        grid-template-columns: 380px 1fr;
        gap: 2rem;
    }
}

/* ==========================================================================
   Tarjetas y secciones
   ========================================================================== */
.card-custom {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.section-title {
    color: #1b5e20;
    font-size: 1.35rem;
    margin: 1.8rem 0 1rem;
    padding-bottom: 0.6rem;
    border-bottom: 2px solid #4caf50;
    font-weight: 600;
}

/* ==========================================================================
   Galería - muy importante en móvil
   ========================================================================== */
.galeria {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin: 1.2rem 0;
}

.imagen-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #eee;
    aspect-ratio: 1;
}

.imagen-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.imagen-item.principal {
    border: 3px solid #4caf50;
    box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2);
}

/* Overlay más limpio en móvil */
.imagen-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.75), transparent 60%);
    color: white;
    opacity: 0;
    transition: opacity 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 0.8rem;
}

.imagen-item:hover .imagen-overlay,
.imagen-item:focus-within .imagen-overlay {
    opacity: 1;
}

/* Botones pequeños en overlay */
.imagen-acciones {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.btn-icon {
    background: rgba(255,255,255,0.9);
    color: #333;
    border: none;
    padding: 0.35rem 0.7rem;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
}

.btn-icon:hover {
    background: #4caf50;
    color: white;
}

/* ==========================================================================
   Información en grid - más legible en móvil
   ========================================================================== */
.info-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem 1.2rem;
}

@media (min-width: 576px) {
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.info-group {
    font-size: 0.95rem;
}

.info-label {
    color: #555;
    font-size: 0.82rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 500;
    color: #111;
}

/* ==========================================================================
   Tablas responsivas
   ========================================================================== */
.tabla-container {
    overflow-x: auto;
    border-radius: 10px;
    margin: 1rem 0;
}

table {
    width: 100%;
    min-width: 480px;          /* evita que se achique demasiado */
    border-collapse: collapse;
}

th {
    background: #2e7d32;
    color: white;
    padding: 0.9rem 1rem;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
}

td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #eee;
}

/* ==========================================================================
   Formularios y modales en móvil
   ========================================================================== */
.modal-content {
    width: 92%;
    max-width: 480px;
    margin: 10vh auto;
    padding: 1.8rem;
    border-radius: 12px;
}

.form-group {
    margin-bottom: 1.3rem;
}

.form-control {
    padding: 0.75rem;
    font-size: 1rem;
    border-radius: 8px;
}

/* ==========================================================================
   Área de subida
   ========================================================================== */
.upload-area {
    border: 2.5px dashed #bbb;
    border-radius: 12px;
    padding: 2.5rem 1rem;
    text-align: center;
    background: #fafafa;
    transition: all 0.25s ease;
}

.upload-area:hover,
.upload-area.drag-over {
    border-color: #4caf50;
    background: #e8f5e9;
}

/* Tamaños de texto más amigables en móvil */
@media (max-width: 576px) {
    h1 { font-size: 1.6rem; }
    .section-title { font-size: 1.2rem; }
    .info-value { font-size: 0.98rem; }
    .btn { 
        padding: 0.7rem 1.1rem;
        font-size: 0.92rem;
    }
    .acciones {
        flex-direction: column;
        gap: 0.8rem;
    }
    .acciones .btn {
        width: 100%;
    }
}

.carousel-item img {
    width: 100%;
    height: auto;
    max-height: 500px;          /* ajusta según te guste */
    object-fit: cover;
    border-radius: 10px;
}

.carousel-caption {
    background: rgba(0,0,0,0.5);
    border-radius: 8px;
    padding: 8px 15px;
}

/* Previsualización - versión mejorada */
#imagenes-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
    margin-top: 1.2rem;
    padding: 0.5rem;
}

.preview-item {
    position: relative;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    padding: 6px;
}

.preview-item img {
    width: 100%;
    height: 110px;              /* altura fija razonable */
    object-fit: cover;
    border-radius: 6px;
    display: block;
}

.preview-item input {
    margin-top: 6px;
    font-size: 0.8rem;
    padding: 4px;
}

/* En móviles muy pequeños */
@media (max-width: 480px) {
    #imagenes-preview {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .preview-item img {
        height: 90px;
    }
}
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1><i class="fas fa-cow"></i> <?php echo htmlspecialchars($animal['codigo']); ?> - <?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?></h1>
        </div>
    </header>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <div class="acciones mb-3">
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
        
        <div class="grid">
            <div>
                <div class="card-custom">
                    <h2 class="section-title"><i class="fas fa-images"></i> Galería</h2>
                    
                    <?php if (!empty($imagenes)): ?>
                        <div id="animalCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                            <!-- Indicators (puntos abajo) - opcional pero recomendado -->
                            <div class="carousel-indicators">
                                <?php foreach ($imagenes as $index => $img): ?>
                                    <button type="button" 
                                            data-bs-target="#animalCarousel" 
                                            data-bs-slide-to="<?php echo $index; ?>" 
                                            <?php echo $index === 0 ? 'class="active"' : ''; ?> 
                                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                            aria-label="Slide <?php echo $index + 1; ?>">
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- Slides -->
                            <div class="carousel-inner">
                                <?php foreach ($imagenes as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="position-relative">
                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['nombre_archivo']); ?>" 
                                                class="d-block w-100 rounded" 
                                                alt="<?php echo htmlspecialchars($img['descripcion'] ?: 'Imagen del animal'); ?>"
                                                style="max-height: 450px; object-fit: cover;">

                                            <?php if ($img['es_principal']): ?>
                                                <span class="badge-principal position-absolute top-0 end-0 m-3">⭐ PRINCIPAL</span>
                                            <?php endif; ?>

                                            <!-- Overlay con descripción y botones -->
                                            <div class="carousel-caption d-none d-md-block">
                                                <?php if ($img['descripcion']): ?>
                                                    <p class="bg-dark bg-opacity-50 p-2 rounded mb-0">
                                                        <?php echo htmlspecialchars($img['descripcion']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Botones de acción (solo en desktop o con cuidado en móvil) -->
                                            <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-center gap-2" 
                                                style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                                                <?php if (!$img['es_principal']): ?>
                                                    <button onclick="establecerPrincipal(<?php echo $img['id']; ?>)" 
                                                            class="btn btn-sm btn-light">⭐ Principal</button>
                                                <?php endif; ?>
                                                <button onclick="eliminarImagen(<?php echo $img['id']; ?>)" 
                                                        class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Controles anterior/siguiente -->
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
                        <div class="empty-state text-center py-5">
                            <p class="fs-4">📷 No hay imágenes aún</p>
                            <p class="text-muted">Agrega algunas abajo</p>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="margin-top:30px;margin-bottom:15px;color:#2e7d32;">➕ Agregar Nuevas Imágenes</h3>
                    
                    <form method="POST" enctype="multipart/form-data" id="formImagenes">
                        <div class="upload-area" onclick="document.getElementById('input-imagenes').click()">
                            <div style="font-size:48px;margin-bottom:10px;">📁</div>
                            <p style="font-size:16px;font-weight:600;margin-bottom:5px;">Haz clic para seleccionar imágenes</p>
                            <p style="font-size:12px;color:#666;">o arrastra y suelta aquí</p>
                            <p style="font-size:11px;color:#999;margin-top:10px;">JPG, PNG, GIF - Máximo 5MB por imagen</p>
                        </div>
                        
                        <input type="file" 
                               id="input-imagenes" 
                               name="imagenes[]" 
                               multiple 
                               accept="image/*" 
                               style="display:none;"
                               onchange="previsualizarImagenes(this.files)">
                        
                        <div id="imagenes-preview"></div>
                        
                        <div style="margin-top:20px;" id="btn-subir" style="display:none;">
                            <button type="submit" name="agregar_imagenes" class="btn btn-primary">
                                💾 Subir Imágenes
                            </button>
                            <button type="button" onclick="limpiarPrevisualizacion()" class="btn btn-secondary">
                                ❌ Cancelar
                            </button>
                        </div>
                    </form>
                    
                    <div class="info-group" style="margin-top:30px;">
                        <div class="info-label">Código</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($animal['codigo']); ?></strong></div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Tipo</div>
                        <div class="info-value">
                            <span class="badge badge-<?php echo $animal['tipo']; ?>">
                                <?php echo ucfirst($animal['tipo']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <span class="badge badge-<?php echo $animal['estado_venta']; ?>">
                                <?php echo ucfirst($animal['estado_venta']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($animal['precio_venta']): ?>
                    <div class="info-group">
                        <div class="info-label">Precio</div>
                        <div class="info-value">$<?php echo number_format($animal['precio_venta'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <div class="card-custom">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> Información General</h2>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div class="info-group">
                            <div class="info-label">Nombre</div>
                            <div class="info-value"><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Sexo</div>
                            <div class="info-value"><?php echo ucfirst($animal['sexo']); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Fecha de Nacimiento</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($animal['fecha_nacimiento'])); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Edad</div>
                            <div class="info-value"><?php echo calcularEdad($animal['fecha_nacimiento']); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Peso Actual</div>
                            <div class="info-value"><?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) . ' kg' : '-'; ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Raza</div>
                            <div class="info-value"><?php echo htmlspecialchars($animal['raza'] ?: '-'); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Color</div>
                            <div class="info-value"><?php echo htmlspecialchars($animal['color'] ?: '-'); ?></div>
                        </div>
                    </div>
                    
                    <h2 class="section-title"><i class="fas fa-users"></i> Genealogía</h2>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div class="info-group">
                            <div class="info-label">Madre</div>
                            <div class="info-value">
                                <?php if ($animal['madre_id']): ?>
                                    <a href="ver_animal.php?id=<?php echo $animal['madre_id']; ?>" style="color:#2e7d32;">
                                        <?php echo htmlspecialchars($animal['codigo_madre'] . ' - ' . $animal['nombre_madre']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">Padre</div>
                            <div class="info-value">
                                <?php if ($animal['padre_id']): ?>
                                    <a href="ver_animal.php?id=<?php echo $animal['padre_id']; ?>" style="color:#2e7d32;">
                                        <?php echo htmlspecialchars($animal['codigo_padre'] . ' - ' . $animal['nombre_padre']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($hijos)): ?>
                    <h2 class="section-title"><i class="fas fa-baby"></i> Hijos</h2>
                    <div class="tabla-container">
                        <table>
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
                                        <a href="ver_animal.php?id=<?php echo $hijo['id']; ?>" class="btn btn-primary" style="font-size:12px;">Ver</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($animal['estado_venta'] === 'vendido'): ?>
                    <h2 class="section-title"><i class="fas fa-dollar-sign"></i> Información de Venta</h2>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div class="info-group">
                            <div class="info-label">Fecha de Venta</div>
                            <div class="info-value"><?php echo $animal['fecha_venta'] ? date('d/m/Y', strtotime($animal['fecha_venta'])) : '-'; ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Comprador</div>
                            <div class="info-value"><?php echo htmlspecialchars($animal['comprador'] ?: '-'); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($animal['observaciones']): ?>
                    <h2 class="section-title"><i class="fas fa-sticky-note"></i> Observaciones</h2>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($animal['observaciones'])); ?></div>
                    <?php endif; ?>
                    
                    <h2 class="section-title"><i class="fas fa-weight"></i> Historial de Pesajes</h2>
                    <?php if (!empty($pesajes)): ?>
                    <div class="tabla-container">
                        <table>
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
                    <div class="empty-state">
                        <p>No hay pesajes registrados</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Pesaje -->
    <div id="modalPesaje" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom:20px;"><i class="fas fa-weight"></i> Registrar Nuevo Pesaje</h2>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Fecha *</label>
                    <input type="date" name="fecha_pesaje" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-weight-hanging"></i> Peso (kg) *</label>
                    <input type="number" step="0.01" name="peso" required placeholder="Ej: 450.50" class="form-control">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Observaciones</label>
                    <textarea name="observaciones_pesaje" rows="3" class="form-control"></textarea>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" name="registrar_pesaje" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModalPesaje()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
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
                        div.innerHTML = `
                            <button type="button" class="preview-remove" onclick="eliminarPreview(${index})">×</button>
                            <img src="${e.target.result}" alt="Preview">
                            <input type="text" 
                                   name="descripciones[]" 
                                   placeholder="Descripción (opcional)"
                                   maxlength="255">
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
        const uploadArea = document.querySelector('.upload-area');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            document.getElementById('input-imagenes').files = files;
            previsualizarImagenes(files);
        });
        
        function establecerPrincipal(imagenId) {
            if (confirm('¿Establecer esta imagen como principal?')) {
                window.location.href = 'ver_animal.php?id=<?php echo $id; ?>&accion=principal&imagen_id=' + imagenId;
            }
        }
        
        function eliminarImagen(imagenId) {
            if (confirm('¿Está seguro de eliminar esta imagen?')) {
                window.location.href = 'ver_animal.php?id=<?php echo $id; ?>&accion=eliminar_img&imagen_id=' + imagenId;
            }
        }
        
        function mostrarModalPesaje() {
            document.getElementById('modalPesaje').style.display = 'block';
        }
        
        function cerrarModalPesaje() {
            document.getElementById('modalPesaje').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalPesaje');
            if (event.target === modal) {
                cerrarModalPesaje();
            }
        }
    </script>
</body>
</html>