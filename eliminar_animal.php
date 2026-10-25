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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="assets/css/styles.css">
    </head>
    <body>
        <header class="main-header">
            <div class="container">
                <h1><i class="fas fa-trash"></i> Confirmar Eliminación</h1>
            </div>
        </header>

        <div class="container">
            <div class="card-custom" style="max-width: 600px; margin: 2rem auto;">
                <div class="text-center mb-4">
                    <?php
                    $imagenPrincipal = obtenerImagenPrincipal($id);
                    if ($imagenPrincipal): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($imagenPrincipal['nombre_archivo']); ?>"
                             class="animal-img-table" alt="Animal" style="max-width: 200px;">
                    <?php else: ?>
                        <div style="width:150px;height:150px;background:#f0f0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:4rem;margin:0 auto;">
                            🐄
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="text-center mb-3">
                    <?php echo htmlspecialchars($animal['codigo']); ?> - <?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?>
                </h3>

                <p class="text-center text-muted mb-4">
                    Tipo: <?php echo ucfirst($animal['tipo']); ?> |
                    Sexo: <?php echo ucfirst($animal['sexo']); ?> |
                    Edad: <?php echo calcularEdad($animal['fecha_nacimiento']); ?>
                </p>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¡Atención!</strong> Esta acción eliminará permanentemente el animal y todas sus imágenes, pesajes y relaciones genealógicas. No se puede deshacer.
                </div>

                <form method="POST">
                    <?= \Core\Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Sí, Eliminar Definitivamente
                        </button>
                        <a href="ver_animal.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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