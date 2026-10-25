<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Control Ganadero'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1><i class="fas fa-cow"></i> <?php echo $headerTitle ?? 'Control Ganadero'; ?></h1>
            <?php if (isset($headerSubtitle)): ?>
                <p><?php echo $headerSubtitle; ?></p>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="container">
        <!-- Menú de navegación -->
        <nav class="menu-nav">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <a href="index.php" class="btn btn-primary w-100">
                        <i class="fas fa-list"></i> <span class="d-none d-md-inline">Inventario</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="crear_animal.php" class="btn btn-success w-100">
                        <i class="fas fa-plus-circle"></i> <span class="d-none d-md-inline">Nuevo</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="prenez.php" class="btn btn-warning w-100">
                        <i class="fas fa-heartbeat"></i> <span class="d-none d-md-inline">Preñez</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="reportes.php" class="btn btn-info w-100">
                        <i class="fas fa-chart-bar"></i> <span class="d-none d-md-inline">Reportes</span>
                    </a>
                </div>
            </div>
        </nav>