<?php
require_once 'includes/functions.php';

// Verificar CSRF en POST
\Core\Security::requireCsrf();

$id = $_POST['id'] ?? ($_GET['id'] ?? 0);

if (!$id) {
    \Core\Security::setFlash('error', 'ID de animal no válido');
    header('Location: index.php');
    exit;
}

// Si es GET, mostrar confirmación
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $animal = obtenerAnimal($id);
    if (!$animal) {
        \Core\Security::setFlash('error', 'Animal no encontrado');
        header('Location: index.php');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Eliminar Animal - Control Ganadero</title>

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
                <h1><i class="fas fa-trash"></i> Confirmar Eliminación</h1>
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
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-body" style="text-align: center; padding: var(--space-8);">
                    <!-- Imagen del animal -->
                    <div style="margin-bottom: var(--space-6);">
                        <?php
                        $imagenPrincipal = obtenerImagenPrincipal($id);
                        if ($imagenPrincipal): ?>
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($imagenPrincipal['nombre_archivo']); ?>"
                                 style="max-width: 200px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; background: var(--color-gray-100); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 4rem; margin: 0 auto;">
                                🐄
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 style="margin-bottom: var(--space-2); color: var(--color-gray-900);">
                        <?php echo htmlspecialchars($animal['codigo']); ?> - <?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?>
                    </h2>

                    <p class="text-muted" style="margin-bottom: var(--space-6);">
                        Tipo: <strong><?php echo ucfirst($animal['tipo']); ?></strong> |
                        Sexo: <strong><?php echo ucfirst($animal['sexo']); ?></strong> |
                        Edad: <strong><?php echo calcularEdad($animal['fecha_nacimiento']); ?></strong>
                    </p>

                    <div class="alert alert-warning" style="text-align: left; margin-bottom: var(--space-6);">
                        <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                            <i class="fas fa-exclamation-triangle alert-icon" style="margin-top: 2px;"></i>
                            <div>
                                <strong>¡Atención!</strong> Esta acción eliminará permanentemente el animal y <strong>todas sus imágenes, pesajes y relaciones genealógicas</strong>. No se puede deshacer.
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <?= \Core\Security::csrfField() ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div style="display: flex; gap: var(--space-3); justify-content: center; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-trash"></i> Sí, Eliminar Definitivamente
                            </button>
                            <a href="ver_animal.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// POST: eliminar
try {
    eliminarAnimal($id);
    \Core\Security::setFlash('success', 'Animal eliminado exitosamente');
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    \Core\Security::setFlash('error', $e->getMessage());
    header('Location: ver_animal.php?id=' . $id);
    exit;
}