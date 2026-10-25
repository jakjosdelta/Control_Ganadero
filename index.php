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
                <h1><i class="fas fa-cow"></i> Control Ganadero</h1>
                <p class="subtitle">Sistema de Gestión de Ganado - JosephG</p>
            </div>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link active"><i class="fas fa-list"></i> Inventario</a>
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

        <!-- Notificaciones de partos próximos -->
        <?php if (!empty($notificaciones)): ?>
        <div class="card" style="margin-bottom: var(--space-6); border-left: 4px solid var(--color-warning);">
            <div class="card-header" style="background: var(--color-warning-light); border-bottom: none;">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Partos Próximos (siguientes 30 días)</h3>
            </div>
            <div class="card-body" style="padding: var(--space-4);">
                <ul style="list-style: none; display: grid; gap: var(--space-2);">
                    <?php foreach ($notificaciones as $notif): ?>
                    <li style="display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3); background: var(--color-gray-50); border-radius: var(--radius-md);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-full); background: var(--color-warning-light); color: var(--color-warning); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-cow"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-weight: 600; color: var(--color-gray-900);">
                                <?php echo htmlspecialchars($notif['codigo_vaca'] . ' - ' . $notif['nombre_vaca']); ?>
                            </div>
                            <div style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                Parto estimado: <strong><?php echo date('d/m/Y', strtotime($notif['fecha_parto_estimada'])); ?></strong>
                                <span style="color: var(--color-warning);">(<?php echo $notif['dias_restantes']; ?> días)</span>
                            </div>
                        </div>
                        <a href="ver_animal.php?id=<?php echo $notif['vaca_id']; ?>" class="btn btn-sm btn-outline btn-primary">Ver</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estadísticas Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-cow"></i></div>
                <div class="stat-value"><?php echo $totalVacas; ?></div>
                <div class="stat-label">Total Vacas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-baby"></i></div>
                <div class="stat-value"><?php echo $totalTerneros; ?></div>
                <div class="stat-label">Total Terneros</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fas fa-bull"></i></div>
                <div class="stat-value"><?php echo $totalToros; ?></div>
                <div class="stat-label">Total Toros</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-heartbeat"></i></div>
                <div class="stat-value"><?php echo $prenezActivas; ?></div>
                <div class="stat-label">Preñeces Activas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            </div>
            <div class="card-body" style="padding: var(--space-5);">
                <form method="GET" class="grid grid-4" style="align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label"><i class="fas fa-search"></i> Buscar</label>
                        <input type="text" class="form-input" name="buscar"
                               value="<?php echo htmlspecialchars($filtros['buscar']); ?>"
                               placeholder="Código, nombre, raza...">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label"><i class="fas fa-tag"></i> Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="vaca" <?php echo $filtros['tipo'] === 'vaca' ? 'selected' : ''; ?>>Vacas</option>
                            <option value="ternero" <?php echo $filtros['tipo'] === 'ternero' ? 'selected' : ''; ?>>Terneros</option>
                            <option value="toro" <?php echo $filtros['tipo'] === 'toro' ? 'selected' : ''; ?>>Toros</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label"><i class="fas fa-dollar-sign"></i> Estado Venta</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="disponible" <?php echo $filtros['estado'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                            <option value="vendido" <?php echo $filtros['estado'] === 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                            <option value="reservado" <?php echo $filtros['estado'] === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: var(--space-2);">
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- VISTA ESCRITORIO: Tabla -->
        <div class="animal-list-desktop">
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Sexo</th>
                                <th>Edad</th>
                                <th>Peso (kg)</th>
                                <th>Raza</th>
                                <th>Madre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultado['animales'] as $animal): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($animal['imagen_principal'])): ?>
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($animal['imagen_principal']); ?>"
                                             class="animal-img-table" alt="">
                                    <?php else: ?>
                                        <div class="animal-img-placeholder">🐄</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($animal['codigo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></td>
                                <td><span class="badge badge-<?php echo $animal['tipo']; ?>"><?php echo ucfirst($animal['tipo']); ?></span></td>
                                <td><?php echo ucfirst($animal['sexo']); ?></td>
                                <td><?php echo calcularEdad($animal['fecha_nacimiento']); ?></td>
                                <td><?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($animal['raza'] ?: '-'); ?></td>
                                <td><?php echo $animal['nombre_madre'] ? htmlspecialchars($animal['codigo_madre']) : '-'; ?></td>
                                <td><span class="badge badge-<?php echo $animal['estado_venta']; ?>"><?php echo ucfirst($animal['estado_venta']); ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="ver_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-secondary" title="Ver"><i class="fas fa-eye"></i></a>
                                        <a href="editar_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-secondary" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="eliminar_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este animal?')" title="Eliminar"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($resultado['animales'])): ?>
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📭</div>
                                        <h3 class="empty-state-title">No se encontraron animales</h3>
                                        <p class="empty-state-text">Intenta cambiar los filtros de búsqueda o <a href="crear_animal.php" class="btn btn-primary"><i class="fas fa-plus"></i> Crear el primero</a></p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VISTA MÓVIL: Tarjetas -->
        <div class="animal-list-mobile">
            <?php foreach ($resultado['animales'] as $animal): ?>
            <article class="animal-card">
                <header class="animal-card-header">
                    <?php if (!empty($animal['imagen_principal'])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($animal['imagen_principal']); ?>"
                             class="animal-card-image" alt="<?php echo htmlspecialchars($animal['codigo']); ?>">
                    <?php else: ?>
                        <div class="animal-card-placeholder">🐄</div>
                    <?php endif; ?>
                    <div class="animal-card-badges">
                        <span class="badge badge-<?php echo $animal['tipo']; ?>"><?php echo ucfirst($animal['tipo']); ?></span>
                        <span class="badge badge-<?php echo $animal['estado_venta']; ?>"><?php echo ucfirst($animal['estado_venta']); ?></span>
                    </div>
                </header>
                <div class="animal-card-content">
                    <div class="animal-card-title">
                        <div>
                            <div class="animal-card-code"><?php echo htmlspecialchars($animal['codigo']); ?></div>
                            <?php if ($animal['nombre']): ?>
                            <div class="animal-card-name"><?php echo htmlspecialchars($animal['nombre']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="animal-card-details">
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-venus-mars"></i> Sexo</span>
                            <span class="animal-detail-value"><?php echo ucfirst($animal['sexo']); ?></span>
                        </div>
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-birthday-cake"></i> Edad</span>
                            <span class="animal-detail-value"><?php echo calcularEdad($animal['fecha_nacimiento']); ?></span>
                        </div>
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-weight"></i> Peso</span>
                            <span class="animal-detail-value"><?php echo $animal['peso_actual'] ? number_format($animal['peso_actual'], 2) . ' kg' : '-'; ?></span>
                        </div>
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-dna"></i> Raza</span>
                            <span class="animal-detail-value"><?php echo htmlspecialchars($animal['raza'] ?: '-'); ?></span>
                        </div>
                        <?php if ($animal['nombre_madre']): ?>
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-female"></i> Madre</span>
                            <span class="animal-detail-value"><?php echo htmlspecialchars($animal['codigo_madre']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($animal['precio_venta']): ?>
                        <div class="animal-detail">
                            <span class="animal-detail-label"><i class="fas fa-dollar-sign"></i> Precio</span>
                            <span class="animal-detail-value">$<?php echo number_format($animal['precio_venta'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <footer class="animal-card-actions">
                    <a href="ver_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Ver</a>
                    <a href="editar_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> Editar</a>
                    <a href="eliminar_animal.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este animal?')"><i class="fas fa-trash"></i> Eliminar</a>
                </footer>
            </article>
            <?php endforeach; ?>

            <?php if (empty($resultado['animales'])): ?>
            <div class="card empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">📭</div>
                <h3 class="empty-state-title">No se encontraron animales</h3>
                <p class="empty-state-text">Intenta cambiar los filtros de búsqueda o <a href="crear_animal.php" class="btn btn-primary" style="margin-top: var(--space-4); display: inline-flex;"><i class="fas fa-plus"></i> Crear el primero</a></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($resultado['paginas'] > 1): ?>
        <nav class="pagination" aria-label="Paginación">
            <?php if ($pagina > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])); ?>" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $pagina - 2);
            $end = min($resultado['paginas'], $pagina + 2);
            if ($start > 1) echo '<span class="ellipsis">…</span>';
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="active" aria-current="page"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($end < $resultado['paginas']) echo '<span class="ellipsis">…</span>'; ?>

            <?php if ($pagina < $resultado['paginas']): ?>
                <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])); ?>" aria-label="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </main>

    </body>
</html>