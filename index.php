<?php
require_once 'includes/functions.php';

// Obtener filtros y paginación
$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'buscar' => $_GET['buscar'] ?? ''
];
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;

// Obtener animales
$resultado = obtenerAnimales($filtros, $pagina, $porPagina);

// Obtener notificaciones de partos próximos
$notificaciones = obtenerNotificacionesPartos();

// Estadísticas
$db = getDB();
$totalVacas = $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'vaca'")->fetch()['total'];
$totalTerneros = $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'ternero'")->fetch()['total'];
$totalToros = $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'toro'")->fetch()['total'];
$prenezActivas = $db->query("SELECT COUNT(*) as total FROM prenez WHERE estado IN ('confirmada', 'sospechosa')")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Ganadero</title>
    
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
            <h1><i class="fas fa-cow"></i> Control Ganadero</h1>
            <p>Sistema de Gestión de Ganado - JosephG</p>
        </div>
    </header>

    <div class="container">
        <?php
        $flashes = \Core\Security::getFlashes();
        if (!empty($flashes['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php foreach ($flashes['success'] as $msg): ?>
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($flashes['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php foreach ($flashes['error'] as $msg): ?>
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($msg); ?>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Menú de navegación -->
        <nav class="menu-nav">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <a href="index.php" class="btn btn-primary w-100">
                        <i class="fas fa-list"></i> Inventario
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="crear_animal.php" class="btn btn-success w-100">
                        <i class="fas fa-plus-circle"></i> Nuevo Animal
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="prenez.php" class="btn btn-warning w-100">
                        <i class="fas fa-heartbeat"></i> Preñez
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="reportes.php" class="btn btn-info w-100">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Notificaciones de partos próximos -->
        <?php if (!empty($notificaciones)): ?>
        <div class="notificaciones fade-in">
            <h3><i class="fas fa-exclamation-triangle"></i> Partos Próximos (siguientes 30 días)</h3>
            <?php foreach ($notificaciones as $notif): ?>
            <div class="notificacion-item">
                <strong><?php echo htmlspecialchars($notif['codigo_vaca']); ?> - <?php echo htmlspecialchars($notif['nombre_vaca']); ?></strong>
                - Fecha estimada: <?php echo date('d/m/Y', strtotime($notif['fecha_parto_estimada'])); ?>
                (<?php echo $notif['dias_restantes']; ?> días restantes)
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <h3>Total Vacas</h3>
                <div class="numero"><?php echo $totalVacas; ?></div>
            </div>
            <div class="stat-card fade-in">
                <h3>Total Terneros</h3>
                <div class="numero"><?php echo $totalTerneros; ?></div>
            </div>
            <div class="stat-card fade-in">
                <h3>Total Toros</h3>
                <div class="numero"><?php echo $totalToros; ?></div>
            </div>
            <div class="stat-card fade-in">
                <h3>Preñeces Activas</h3>
                <div class="numero"><?php echo $prenezActivas; ?></div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card-custom">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
            <form method="GET" class="filtros-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Buscar</label>
                            <input type="text" class="form-control" name="buscar" 
                                   value="<?php echo htmlspecialchars($filtros['buscar']); ?>" 
                                   placeholder="Código, nombre o raza...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="">Todos</option>
                                <option value="vaca" <?php echo $filtros['tipo'] === 'vaca' ? 'selected' : ''; ?>>Vacas</option>
                                <option value="ternero" <?php echo $filtros['tipo'] === 'ternero' ? 'selected' : ''; ?>>Terneros</option>
                                <option value="toro" <?php echo $filtros['tipo'] === 'toro' ? 'selected' : ''; ?>>Toros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Estado Venta</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="disponible" <?php echo $filtros['estado'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="vendido" <?php echo $filtros['estado'] === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                                <option value="reservado" <?php echo $filtros['estado'] === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 me-1">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar Filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- VISTA DE ESCRITORIO: Tabla tradicional -->
        <div class="animal-list-desktop">
            <div class="table-responsive-custom">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Sexo</th>
                            <th>Edad</th>
                            <th>Peso</th>
                            <th>Raza</th>
                            <th>Madre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultado['animales'] as $animal): ?>
                        <tr class="fade-in">
                            <td>
                                <?php 
                                $imagenPrincipal = obtenerImagenPrincipal($animal['id']);
                                if ($imagenPrincipal): ?>
                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($imagenPrincipal['nombre_archivo']); ?>" 
                                         class="animal-img-table" alt="Animal">
                                <?php else: ?>
                                    <div style="width:60px;height:60px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;">
                                        🐄
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($animal['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></td>
                            <td>
                                <span class="badge-custom badge-<?php echo $animal['tipo']; ?>">
                                    <?php echo ucfirst($animal['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($animal['sexo']); ?></td>
                            <td><?php echo calcularEdad($animal['fecha_nacimiento']); ?></td>
                            <td><?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) . ' kg' : '-'; ?></td>
                            <td><?php echo htmlspecialchars($animal['raza'] ?: '-'); ?></td>
                            <td><?php echo $animal['nombre_madre'] ? htmlspecialchars($animal['codigo_madre']) : '-'; ?></td>
                            <td>
                                <span class="badge-custom badge-<?php echo $animal['estado_venta']; ?>">
                                    <?php echo ucfirst($animal['estado_venta']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="ver_animal.php?id=<?php echo $animal['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_animal.php?id=<?php echo $animal['id']; ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="eliminar_animal.php?id=<?php echo $animal['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('¿Está seguro de eliminar este animal?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($resultado['animales'])): ?>
                        <tr>
                            <td colspan="11" class="text-center" style="padding:3rem;">
                                <i class="fas fa-inbox fa-3x mb-3" style="color:#ccc;"></i>
                                <p style="color:#999;">No se encontraron animales</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- VISTA MÓVIL: Tarjetas -->
        <div class="animal-list-mobile">
            <?php foreach ($resultado['animales'] as $animal): ?>
            <div class="animal-card-mobile fade-in">
                <div class="animal-card-header">
                    <?php 
                    $imagenPrincipal = obtenerImagenPrincipal($animal['id']);
                    if ($imagenPrincipal): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($imagenPrincipal['nombre_archivo']); ?>" 
                             class="animal-img-mobile" alt="Animal">
                    <?php else: ?>
                        <div class="animal-img-placeholder">🐄</div>
                    <?php endif; ?>
                    
                    <div class="animal-card-title">
                        <h3><?php echo htmlspecialchars($animal['codigo']); ?></h3>
                        <p><?php echo htmlspecialchars($animal['nombre'] ?: 'Sin nombre'); ?></p>
                        <div class="mt-1">
                            <span class="badge-custom badge-<?php echo $animal['tipo']; ?>">
                                <?php echo ucfirst($animal['tipo']); ?>
                            </span>
                            <span class="badge-custom badge-<?php echo $animal['estado_venta']; ?>">
                                <?php echo ucfirst($animal['estado_venta']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="animal-card-body">
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-venus-mars"></i> Sexo
                        </span>
                        <span class="animal-info-value">
                            <?php echo ucfirst($animal['sexo']); ?>
                        </span>
                    </div>
                    
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-birthday-cake"></i> Edad
                        </span>
                        <span class="animal-info-value">
                            <?php echo calcularEdad($animal['fecha_nacimiento']); ?>
                        </span>
                    </div>
                    
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-weight"></i> Peso
                        </span>
                        <span class="animal-info-value">
                            <?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) . ' kg' : '-'; ?>
                        </span>
                    </div>
                    
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-dna"></i> Raza
                        </span>
                        <span class="animal-info-value">
                            <?php echo htmlspecialchars($animal['raza'] ?: '-'); ?>
                        </span>
                    </div>
                    
                    <?php if ($animal['nombre_madre']): ?>
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-female"></i> Madre
                        </span>
                        <span class="animal-info-value">
                            <?php echo htmlspecialchars($animal['codigo_madre']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($animal['precio_venta']): ?>
                    <div class="animal-info-item">
                        <span class="animal-info-label">
                            <i class="fas fa-dollar-sign"></i> Precio
                        </span>
                        <span class="animal-info-value">
                            $<?php echo number_format($animal['precio_venta'], 2); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="animal-card-footer">
                    <a href="ver_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                    <a href="editar_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="eliminar_animal.php?id=<?php echo $animal['id']; ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Está seguro de eliminar este animal?')">
                        <i class="fas fa-trash"></i> Eliminar
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($resultado['animales'])): ?>
            <div class="card-custom text-center" style="padding:3rem;">
                <i class="fas fa-inbox fa-4x mb-3" style="color:#ccc;"></i>
                <h5 style="color:#999;">No se encontraron animales</h5>
                <p style="color:#999;">Intenta cambiar los filtros de búsqueda</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($resultado['paginas'] > 1): ?>
        <div class="pagination-custom">
            <?php if ($pagina > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $resultado['paginas']; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($pagina < $resultado['paginas']): ?>
                <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])); ?>">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>