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

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar old input para repoblar formulario en caso de error
    \Core\Security::saveOld($_POST);

    try {
        // Validación con nueva clase Validator
        $rules = [
            'codigo' => 'required|max_len:20|unique:animales,codigo,' . $id,
            'tipo' => 'required|in:vaca,ternero,toro',
            'sexo' => 'required|in:hembra,macho',
            'fecha_nacimiento' => 'required|date|date_before:' . date('Y-m-d'),
            'peso_actual' => 'decimal|min:0|max:9999.99',
            'precio_venta' => 'decimal|min:0|max:999999.99',
            'fecha_venta' => 'date',
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        }

        if (empty($errores)) {
            // Actualizar animal
            actualizarAnimal($id, $_POST, null);
            \Core\Security::clearOld();
            \Core\Security::setFlash('success', 'Animal actualizado exitosamente');
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
    <title>Editar Animal - <?php echo htmlspecialchars($animal['codigo']); ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
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
                <h1><i class="fas fa-edit"></i> Editar Animal - <?php echo htmlspecialchars($animal['codigo']); ?></h1>
            </div>
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
            <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Información Básica</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-hashtag"></i> Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-input" name="codigo" required value="<?php echo htmlspecialchars($animal['codigo']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-tag"></i> Nombre</label>
                            <input type="text" class="form-input" name="nombre" value="<?php echo htmlspecialchars(\Core\Security::old('nombre', $animal['nombre'])); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-cow"></i> Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="vaca" <?php echo \Core\Security::old('tipo', $animal['tipo']) === 'vaca' ? 'selected' : ''; ?>>Vaca</option>
                                <option value="ternero" <?php echo \Core\Security::old('tipo', $animal['tipo']) === 'ternero' ? 'selected' : ''; ?>>Ternero</option>
                                <option value="toro" <?php echo \Core\Security::old('tipo', $animal['tipo']) === 'toro' ? 'selected' : ''; ?>>Toro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span></label>
                            <select name="sexo" class="form-select" required>
                                <option value="hembra" <?php echo \Core\Security::old('sexo', $animal['sexo']) === 'hembra' ? 'selected' : ''; ?>>Hembra</option>
                                <option value="macho" <?php echo \Core\Security::old('sexo', $animal['sexo']) === 'macho' ? 'selected' : ''; ?>>Macho</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-birthday-cake"></i> Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" class="form-input" name="fecha_nacimiento" required value="<?php echo $animal['fecha_nacimiento']; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-weight"></i> Peso Actual (kg)</label>
                            <input type="number" step="0.01" class="form-input" name="peso_actual" value="<?php echo \Core\Security::old('peso_actual', $animal['peso_actual']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-dna"></i> Raza</label>
                            <input type="text" class="form-input" name="raza" value="<?php echo htmlspecialchars(\Core\Security::old('raza', $animal['raza'])); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-palette"></i> Color</label>
                            <input type="text" class="form-input" name="color" value="<?php echo htmlspecialchars(\Core\Security::old('color', $animal['color'])); ?>">
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
                                    <option value="<?php echo $vaca['id']; ?>" <?php echo \Core\Security::old('madre_id', $animal['madre_id']) == $vaca['id'] ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $toro['id']; ?>" <?php echo \Core\Security::old('padre_id', $animal['padre_id']) == $toro['id'] ? 'selected' : ''; ?>>
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
                                <option value="disponible" <?php echo \Core\Security::old('estado_venta', $animal['estado_venta']) === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="reservado" <?php echo \Core\Security::old('estado_venta', $animal['estado_venta']) === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                                <option value="vendido" <?php echo \Core\Security::old('estado_venta', $animal['estado_venta']) === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-dollar-sign"></i> Precio de Venta ($)</label>
                            <input type="number" step="0.01" class="form-input" name="precio_venta" value="<?php echo \Core\Security::old('precio_venta', $animal['precio_venta']); ?>">
                        </div>
                    </div>

                    <div id="campos-venta" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-calendar"></i> Fecha de Venta</label>
                                <input type="date" class="form-input" name="fecha_venta" value="<?php echo \Core\Security::old('fecha_venta', $animal['fecha_venta']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Comprador</label>
                                <input type="text" class="form-input" name="comprador" value="<?php echo htmlspecialchars(\Core\Security::old('comprador', $animal['comprador'])); ?>">
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
                    <div style="background: var(--color-success-light); border: 1px solid var(--color-success); border-radius: var(--radius-lg); padding: var(--space-5); text-align: center;">
                        <p style="margin-bottom: var(--space-3); color: var(--color-success-dark);">
                            <strong>Las imágenes ahora se gestionan desde una página dedicada</strong>
                        </p>
                        <a href="gestionar_imagenes.php?animal_id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-images"></i> Ir a Gestionar Imágenes
                        </a>
                        <p class="form-hint" style="margin-top: var(--space-3);">Podrás agregar múltiples imágenes, establecer la principal y eliminar las que no necesites</p>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note"></i> Observaciones</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-input" name="observaciones" rows="4"><?php echo htmlspecialchars(\Core\Security::old('observaciones', $animal['observaciones'])); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="display: flex; gap: var(--space-3); justify-content: flex-end; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="ver_animal.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </main>

    <script>
        toggleCamposVenta();
    </script>
</body>
</html>