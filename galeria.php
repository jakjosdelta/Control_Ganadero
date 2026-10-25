<?php
require_once 'includes/functions.php';

$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'animal_id' => $_GET['animal_id'] ?? '',
    'buscar' => $_GET['buscar'] ?? ''
];

$db = getDB();

// Construir query con filtros
$where = [];
$params = [];

if ($filtros['tipo']) {
    $where[] = "a.tipo = ?";
    $params[] = $filtros['tipo'];
}

if ($filtros['animal_id']) {
    $where[] = "i.animal_id = ?";
    $params[] = $filtros['animal_id'];
}

if ($filtros['buscar']) {
    $where[] = "(a.codigo LIKE ? OR a.nombre LIKE ? OR a.raza LIKE ?)";
    $searchTerm = "%{$filtros['buscar']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener imágenes con info del animal
$sql = "
    SELECT i.*, a.codigo, a.nombre, a.tipo, a.raza
    FROM imagenes_animales i
    INNER JOIN animales a ON i.animal_id = a.id
    {$whereSql}
    ORDER BY i.fecha_subida DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$imagenes = $stmt->fetchAll();

// Para filtros: lista de animales y tipos
$animales = $db->query("SELECT id, codigo, nombre, tipo FROM animales ORDER BY codigo")->fetchAll();
$tipos = ['vaca' => 'Vaca', 'ternero' => 'Ternero', 'toro' => 'Toro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería Global - Control Ganadero</title>

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
            <div>
                <h1><i class="fas fa-images"></i> Galería Global</h1>
                <p class="subtitle">Todas las imágenes del ganado - <?php echo count($imagenes); ?> fotos</p>
            </div>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link"><i class="fas fa-list"></i> Inventario</a>
                <a href="crear_animal.php" class="nav-link"><i class="fas fa-plus-circle"></i> Nuevo Animal</a>
                <a href="prenez.php" class="nav-link"><i class="fas fa-heartbeat"></i> Preñez</a>
                <a href="reportes.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reportes</a>
                <a href="galeria.php" class="nav-link active"><i class="fas fa-images"></i> Galería</a>
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

        <!-- Filtros -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
            </div>
            <div class="card-body" style="padding: var(--space-5);">
                <form method="GET" class="form-row" style="align-items: end; flex-wrap: wrap; gap: var(--space-4);">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label class="form-label"><i class="fas fa-search"></i> Buscar</label>
                        <input type="text" class="form-input" name="buscar"
                               value="<?php echo htmlspecialchars($filtros['buscar']); ?>"
                               placeholder="Código, nombre, raza...">
                    </div>
                    <div class="form-group" style="min-width: 160px;">
                        <label class="form-label"><i class="fas fa-tag"></i> Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($tipos as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filtros['tipo'] === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="min-width: 200px;">
                        <label class="form-label"><i class="fas fa-cow"></i> Animal</label>
                        <select name="animal_id" class="form-select">
                            <option value="">Todos los animales</option>
                            <?php foreach ($animales as $animal): ?>
                                <option value="<?php echo $animal['id']; ?>" <?php echo $filtros['animal_id'] == $animal['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($animal['codigo'] . ' - ' . ($animal['nombre'] ?: 'Sin nombre') . ' (' . ucfirst($animal['tipo']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: var(--space-2);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="galeria.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grid de imágenes -->
        <?php if (!empty($imagenes)): ?>
        <div class="gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-4);">
            <?php foreach ($imagenes as $img): ?>
            <article class="gallery-item card" style="position: relative; border-radius: var(--radius-lg); overflow: hidden; transition: all var(--transition-base); cursor: pointer;"
                 onclick="window.location.href='ver_animal.php?id=<?php echo $img['animal_id']; ?>'">
                <?php if ($img['es_principal']): ?>
                    <span class="badge badge-primary" style="position: absolute; top: var(--space-2); right: var(--space-2); z-index: 10; font-size: var(--text-xs);">
                        <i class="fas fa-star"></i> Principal
                    </span>
                <?php endif; ?>

                <div style="aspect-ratio: 4/3; overflow: hidden; background: var(--color-gray-100);">
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($img['nombre_archivo']); ?>"
                         alt="<?php echo htmlspecialchars($img['descripcion'] ?: 'Imagen del animal'); ?>"
                         style="width: 100%; height: 100%; object-fit: cover; transition: transform var(--transition-slow);"
                         loading="lazy">
                </div>

                <div class="card-body" style="padding: var(--space-3);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); margin-bottom: var(--space-1);">
                        <span class="badge badge-<?php echo $img['tipo']; ?>" style="font-size: var(--text-xs);">
                            <?php echo ucfirst($img['tipo']); ?>
                        </span>
                        <span style="font-size: var(--text-xs); color: var(--color-gray-500);">
                            <?php echo date('d/m/Y', strtotime($img['fecha_subida'])); ?>
                        </span>
                    </div>
                    <div style="font-weight: 600; color: var(--color-gray-900); margin-bottom: var(--space-1);">
                        <a href="ver_animal.php?id=<?php echo $img['animal_id']; ?>" style="color: inherit; text-decoration: none;">
                            <?php echo htmlspecialchars($img['codigo']); ?>
                        </a>
                        <?php if ($img['nombre']): ?>
                            <span style="font-weight: 400; color: var(--color-gray-600); font-size: var(--text-sm);"> - <?php echo htmlspecialchars($img['nombre']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($img['descripcion']): ?>
                        <p style="font-size: var(--text-sm); color: var(--color-gray-600); margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($img['descripcion']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Overlay hover -->
                <div class="gallery-overlay" style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,18,0.85), transparent 50%); display: flex; flex-direction: column; justify-content: flex-end; padding: var(--space-3); opacity: 0; transition: opacity var(--transition-fast); color: white; pointer-events: none;">
                    <div style="display: flex; gap: var(--space-2);">
                        <a href="ver_animal.php?id=<?php echo $img['animal_id']; ?>" class="gallery-action-btn" style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); background: rgba(255,255,255,0.9); color: var(--color-gray-900); border: none; font-size: var(--text-sm); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all var(--transition-fast); text-decoration: none;" onclick="event.stopPropagation();">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="gestionar_imagenes.php?animal_id=<?php echo $img['animal_id']; ?>" class="gallery-action-btn" style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); background: rgba(255,255,255,0.9); color: var(--color-gray-900); border: none; font-size: var(--text-sm); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all var(--transition-fast); text-decoration: none;" onclick="event.stopPropagation();">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <style>
            .gallery-item:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-xl);
            }
            .gallery-item:hover .gallery-overlay { opacity: 1; }
            .gallery-item:hover img { transform: scale(1.05); }
            .gallery-action-btn:hover { background: white; transform: scale(1.1); }
            .gallery-item:hover .gallery-action-btn { opacity: 1; }
        </style>

        <?php else: ?>
        <div class="card empty-state" style="text-align: center; padding: var(--space-16);">
            <div class="empty-state-icon" style="font-size: 4rem;">🖼️</div>
            <h3 class="empty-state-title">No hay imágenes</h3>
            <p class="empty-state-text">
                <?php if ($filtros['buscar'] || $filtros['tipo'] || $filtros['animal_id']): ?>
                    No se encontraron imágenes con esos filtros.
                    <a href="galeria.php" class="btn btn-primary" style="margin-top: var(--space-4); display: inline-flex;">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </a>
                <?php else: ?>
                    Aún no se han subido imágenes. Ve a <a href="crear_animal.php" class="btn btn-primary" style="margin-top: var(--space-4); display: inline-flex;"><i class="fas fa-plus"></i> Crear un animal</a> y agrega sus fotos.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>