<?php
require_once 'includes/functions.php';

// Verificar CSRF en POST
\Core\Security::requireCsrf();

$errores = [];
$mensaje = [];

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
    // Guardar old input para repoblar formulario
    \Core\Security::saveOld($_POST);

    try {
        // Validación con nueva clase Validator
        $rules = [
            'tipo' => 'required|in:vaca,ternero,toro',
            'sexo' => 'required|in:hembra,macho',
            'fecha_nacimiento' => 'required|date|date_before:' . date('Y-m-d'),
            'peso_actual' => 'decimal|min:0|max:9999.99',
            'precio_venta' => 'decimal|min:0|max:999999.99',
            'fecha_venta' => 'date',
            'imagenes' => 'file_mimes:image/jpeg,image/png,image/gif|file_max:5120', // 5MB
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        } else {
            // Generar código automático basado en el tipo
            $codigoGenerado = generarCodigoAutomatico($_POST['tipo']);
            $_POST['codigo'] = $codigoGenerado;

            // Validar unicidad de código (por si acaso)
            if (!validarCodigo($codigoGenerado)) {
                $errores[] = "El código generado ya existe. Intente de nuevo.";
            }
        }

        if (empty($errores)) {
            // Crear animal primero
            $id = crearAnimal($_POST, null);

            // Subir imágenes si se seleccionaron
            if (!empty($_FILES['imagenes']['name'][0])) {
                $totalArchivos = count($_FILES['imagenes']['name']);

                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['imagenes']['name'][$i],
                            'type' => $_FILES['imagenis']['type'][$i],
                            'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                            'error' => $_FILES['imagenes']['error'][$i],
                            'size' => $_FILES['imagenes']['size'][$i]
                        ];

                        try {
                            agregarImagenAnimal($id, $file, null);
                        } catch (Exception $e) {
                            // Log error pero continuar
                            error_log("Error subiendo imagen: " . $e->getMessage());
                        }
                    }
                }
            }

            \Core\Security::clearOld();
            \Core\Security::setFlash('success', 'Animal creado exitosamente');
            header('Location: ver_animal.php?id=' . $id);
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

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        function previsualizarImagenes(input) {
            const container = document.getElementById('preview-container');
            container.innerHTML = '';

            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.style.cssText = 'position:relative;border:2px solid var(--color-gray-200);border-radius:var(--radius-lg);overflow:hidden;';
                        div.innerHTML = `
                            <img src="${e.target.result}" style="width:100%;height:150px;object-fit:cover;">
                            <div style="padding:5px;background:var(--color-gray-50);font-size:11px;text-align:center;">
                                ${index === 0 ? '⭐ Principal' : 'Imagen ' + (index + 1)}
                            </div>
                        `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        function toggleCamposVenta() {
            const estado = document.getElementById('estado_venta').value;
            const camposVenta = document.getElementById('campos-venta');
            camposVenta.style.display = (estado === 'vendido') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div>
                <h1><i class="fas fa-plus-circle"></i> Crear Nuevo Animal</h1>
            </div>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link"><i class="fas fa-list"></i> Inventario</a>
                <a href="crear_animal.php" class="nav-link active"><i class="fas fa-plus-circle"></i> Nuevo Animal</a>
                <a href="prenez.php" class="nav-link"><i class="fas fa-heartbeat"></i> Preñez</a>
                <a href="reportes.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reportes</a>
                <a href="galeria.php" class="nav-link"><i class="fas fa-images"></i> Galería</a>
            </nav>
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
                    <strong>⚠️ Errores encontrados:</strong>
                    <ul style="margin: var(--space-2) 0 0 var(--space-4);">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= \Core\Security::csrfField() ?>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Información Básica</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-tag"></i> Nombre</label>
                            <input type="text" class="form-input" name="nombre" value="<?php echo htmlspecialchars(\Core\Security::old('nombre')); ?>" placeholder="Ej: Estrella">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-cow"></i> Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="vaca" <?php echo \Core\Security::old('tipo') === 'vaca' ? 'selected' : ''; ?>>Vaca</option>
                                <option value="ternero" <?php echo \Core\Security::old('tipo') === 'ternero' ? 'selected' : ''; ?>>Ternero</option>
                                <option value="toro" <?php echo \Core\Security::old('tipo') === 'toro' ? 'selected' : ''; ?>>Toro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span></label>
                            <select name="sexo" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="hembra" <?php echo \Core\Security::old('sexo') === 'hembra' ? 'selected' : ''; ?>>Hembra</option>
                                <option value="macho" <?php echo \Core\Security::old('sexo') === 'macho' ? 'selected' : ''; ?>>Macho</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-birthday-cake"></i> Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" class="form-input" name="fecha_nacimiento" required value="<?php echo htmlspecialchars(\Core\Security::old('fecha_nacimiento')); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-weight"></i> Peso Actual (kg)</label>
                            <input type="number" step="0.01" class="form-input" name="peso_actual" value="<?php echo htmlspecialchars(\Core\Security::old('peso_actual')); ?>" placeholder="Ej: 450.50">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-dna"></i> Raza</label>
                            <input type="text" class="form-input" name="raza" value="<?php echo htmlspecialchars(\Core\Security::old('raza')); ?>" placeholder="Ej: Holstein">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-palette"></i> Color</label>
                            <input type="text" class="form-input" name="color" value="<?php echo htmlspecialchars(\Core\Security::old('color')); ?>" placeholder="Ej: Negro con blanco">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap"></i> Genealogía</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-female"></i> Madre</label>
                            <select name="madre_id" class="form-select">
                                <option value="">Sin madre registrada</option>
                                <?php foreach ($vacas as $vaca): ?>
                                    <option value="<?php echo $vaca['id']; ?>" <?php echo \Core\Security::old('madre_id') == $vaca['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vaca['codigo'] . ' - ' . $vaca['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-male"></i> Padre</label>
                            <select name="padre_id" class="form-select">
                                <option value="">Sin padre registrado</option>
                                <?php foreach ($toros as $toro): ?>
                                    <option value="<?php echo $toro['id']; ?>" <?php echo \Core\Security::old('padre_id') == $toro['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($toro['codigo'] . ' - ' . $toro['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Estado de Venta</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado_venta" id="estado_venta" class="form-select" onchange="toggleCamposVenta()">
                                <option value="disponible" <?php echo \Core\Security::old('estado_venta', 'disponible') === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="reservado" <?php echo \Core\Security::old('estado_venta') === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                                <option value="vendido" <?php echo \Core\Security::old('estado_venta') === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-dollar-sign"></i> Precio de Venta ($)</label>
                            <input type="number" step="0.01" class="form-input" name="precio_venta" value="<?php echo htmlspecialchars(\Core\Security::old('precio_venta')); ?>">
                        </div>
                    </div>

                    <div id="campos-venta" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-calendar"></i> Fecha de Venta</label>
                                <input type="date" class="form-input" name="fecha_venta" value="<?php echo htmlspecialchars(\Core\Security::old('fecha_venta')); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Comprador</label>
                                <input type="text" class="form-input" name="comprador" value="<?php echo htmlspecialchars(\Core\Security::old('comprador')); ?>" placeholder="Nombre del comprador">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-images"></i> Imágenes</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="file-input-wrapper">
                            <label for="imagenes" class="file-input-label">
                                <i class="fas fa-upload" style="font-size: 2rem; margin-bottom: var(--space-2); display: block;"></i>
                                Seleccionar imágenes del animal (puedes seleccionar varias)
                            </label>
                            <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" onchange="previsualizarImagenes(this)">
                        </div>
                        <div id="preview-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:var(--space-3);margin-top:var(--space-4);"></div>
                        <p class="form-hint">La primera imagen será la principal. Podrás agregar más después.</p>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note"></i> Observaciones</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-input" name="observaciones" rows="4" placeholder="Notas adicionales sobre el animal..."><?php echo htmlspecialchars(\Core\Security::old('observaciones')); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="display: flex; gap: var(--space-3); justify-content: flex-end; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Animal
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </main>

    <script>
        // Inicializar campos de venta al cargar
        toggleCamposVenta();
    </script>
</body>
</html>