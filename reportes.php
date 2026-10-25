<?php
require_once 'includes/functions.php';

$db = getDB();

// Estadísticas generales
$stats = [
    'total_animales' => $db->query("SELECT COUNT(*) as total FROM animales")->fetch()['total'],
    'total_vacas' => $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'vaca'")->fetch()['total'],
    'total_terneros' => $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'ternero'")->fetch()['total'],
    'total_toros' => $db->query("SELECT COUNT(*) as total FROM animales WHERE tipo = 'toro'")->fetch()['total'],
    'disponibles' => $db->query("SELECT COUNT(*) as total FROM animales WHERE estado_venta = 'disponible'")->fetch()['total'],
    'vendidos' => $db->query("SELECT COUNT(*) as total FROM animales WHERE estado_venta = 'vendido'")->fetch()['total'],
    'prenez_activas' => $db->query("SELECT COUNT(*) as total FROM prenez WHERE estado IN ('confirmada', 'sospechosa')")->fetch()['total'],
    'partos_mes' => $db->query("SELECT COUNT(*) as total FROM prenez WHERE MONTH(fecha_parto_estimada) = MONTH(CURDATE()) AND YEAR(fecha_parto_estimada) = YEAR(CURDATE()) AND estado IN ('confirmada', 'sospechosa')")->fetch()['total']
];

// Distribución por raza
$razas = $db->query("SELECT raza, COUNT(*) as cantidad FROM animales WHERE raza IS NOT NULL AND raza != '' GROUP BY raza ORDER BY cantidad DESC LIMIT 10")->fetchAll();

// Animales más pesados
$masPesados = $db->query("SELECT id, codigo, nombre, tipo, peso_actual FROM animales WHERE peso_actual IS NOT NULL ORDER BY peso_actual DESC LIMIT 10")->fetchAll();

// Ventas recientes
$ventasRecientes = $db->query("SELECT codigo, nombre, tipo, precio_venta, fecha_venta, comprador FROM animales WHERE estado_venta = 'vendido' ORDER BY fecha_venta DESC LIMIT 10")->fetchAll();

// Próximos partos
$proximosPartos = $db->query("SELECT pr.fecha_parto_estimada, a.codigo, a.nombre, DATEDIFF(pr.fecha_parto_estimada, CURDATE()) as dias_restantes FROM prenez pr INNER JOIN animales a ON pr.vaca_id = a.id WHERE pr.estado IN ('confirmada', 'sospechosa') AND DATEDIFF(pr.fecha_parto_estimada, CURDATE()) >= 0 ORDER BY pr.fecha_parto_estimada ASC LIMIT 10")->fetchAll();

// Peso promedio por tipo
$pesoPorTipo = $db->query("SELECT tipo, AVG(peso_actual) as peso_promedio, COUNT(*) as cantidad FROM animales WHERE peso_actual IS NOT NULL GROUP BY tipo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Control Ganadero</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Progress bars para distribución por raza */
        .stat-bar {
            background: var(--color-gray-200);
            height: 28px;
            border-radius: var(--radius-full);
            overflow: hidden;
            margin: var(--space-2) 0;
        }
        .stat-bar-fill {
            background: var(--color-primary);
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 var(--space-3);
            color: white;
            font-size: var(--text-sm);
            font-weight: 600;
            transition: width 0.5s ease;
        }
        .stat-row {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-3);
        }
        .stat-row-label {
            min-width: 140px;
            font-weight: 500;
            color: var(--color-gray-700);
        }
        .stat-row-value {
            font-weight: 600;
            color: var(--color-gray-900);
            min-width: 60px;
            text-align: right;
        }
        .stat-row-bar {
            flex: 1;
        }

        @media print {
            .main-header, .nav-menu, .btn, .card-header { display: none !important; }
            body { background: white !important; }
            .card { box-shadow: none !important; border: 1px solid var(--color-gray-200) !important; break-inside: avoid; }
            .stats-grid { display: grid !important; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h1>
            </div>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link"><i class="fas fa-list"></i> Inventario</a>
                <a href="crear_animal.php" class="nav-link"><i class="fas fa-plus-circle"></i> Nuevo Animal</a>
                <a href="prenez.php" class="nav-link"><i class="fas fa-heartbeat"></i> Preñez</a>
                <a href="reportes.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reportes</a>
                <a href="galeria.php" class="nav-link"><i class="fas fa-images"></i> Galería</a>
                <button onclick="window.print()" class="btn btn-secondary" style="margin-left: auto;">
                    <i class="fas fa-print"></i> Imprimir
                </button>
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

        <!-- Estadísticas Generales -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Estadísticas Generales</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-cows"></i></div>
                        <div class="stat-value"><?php echo $stats['total_animales']; ?></div>
                        <div class="stat-label">Total Animales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-cow"></i></div>
                        <div class="stat-value"><?php echo $stats['total_vacas']; ?></div>
                        <div class="stat-label">Vacas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="fas fa-baby"></i></div>
                        <div class="stat-value"><?php echo $stats['total_terneros']; ?></div>
                        <div class="stat-label">Terneros</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber"><i class="fas fa-bull"></i></div>
                        <div class="stat-value"><?php echo $stats['total_toros']; ?></div>
                        <div class="stat-label">Toros</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-tag"></i></div>
                        <div class="stat-value"><?php echo $stats['disponibles']; ?></div>
                        <div class="stat-label">Disponibles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-money-bill"></i></div>
                        <div class="stat-value"><?php echo $stats['vendidos']; ?></div>
                        <div class="stat-label">Vendidos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-heartbeat"></i></div>
                        <div class="stat-value"><?php echo $stats['prenez_activas']; ?></div>
                        <div class="stat-label">Preñeces Activas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-value"><?php echo $stats['partos_mes']; ?></div>
                        <div class="stat-label">Partos este Mes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primera fila de tarjetas -->
        <div class="grid" style="grid-template-columns: 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
            <style>
                @media (min-width: 992px) {
                    .grid { grid-template-columns: 1fr 1fr; }
                }
            </style>

            <!-- Distribución por Raza -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dna"></i> Distribución por Raza</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($razas)): ?>
                        <?php
                        $maxCantidad = max(array_column($razas, 'cantidad'));
                        foreach ($razas as $raza):
                            $porcentaje = ($raza['cantidad'] / $maxCantidad) * 100;
                        ?>
                        <div class="stat-row">
                            <span class="stat-row-label"><?php echo htmlspecialchars($raza['raza']); ?></span>
                            <div class="stat-row-bar">
                                <div class="stat-bar">
                                    <div class="stat-bar-fill" style="width: <?php echo $porcentaje; ?>%;"><?php echo round($porcentaje); ?>%</div>
                                </div>
                            </div>
                            <span class="stat-row-value"><?php echo $raza['cantidad']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding: var(--space-8);">
                            <div class="empty-state-icon">📊</div>
                            <h3 class="empty-state-title">No hay datos de razas</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Peso Promedio por Tipo -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-weight"></i> Peso Promedio por Tipo</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($pesoPorTipo)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Peso Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pesoPorTipo as $peso): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo $peso['tipo']; ?>">
                                                <?php echo ucfirst($peso['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $peso['cantidad']; ?></td>
                                        <td><strong><?php echo number_format($peso['peso_promedio'], 2); ?> kg</strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: var(--space-8);">
                            <div class="empty-state-icon">⚖️</div>
                            <h3 class="empty-state-title">No hay datos de peso</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Segunda fila de tarjetas -->
        <div class="grid" style="grid-template-columns: 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
            <style>
                @media (min-width: 992px) {
                    .grid { grid-template-columns: 1fr 1fr; }
                }
            </style>

            <!-- Animales Más Pesados -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> Animales Más Pesados (Top 10)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($masPesados)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Peso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($masPesados as $index => $animal): ?>
                                    <tr>
                                        <td><strong><?php echo $index + 1; ?></strong></td>
                                        <td>
                                            <a href="ver_animal.php?id=<?php echo $animal['id']; ?>" style="color: var(--color-primary); font-weight: 600;">
                                                <?php echo htmlspecialchars($animal['codigo']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></td>
                                        <td><span class="badge badge-<?php echo $animal['tipo']; ?>"><?php echo ucfirst($animal['tipo']); ?></span></td>
                                        <td><strong><?php echo number_format($animal['peso_actual'], 2); ?> kg</strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: var(--space-8);">
                            <div class="empty-state-icon">🏆</div>
                            <h3 class="empty-state-title">No hay datos</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximos Partos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Próximos Partos</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($proximosPartos)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Vaca</th>
                                        <th>Fecha Estimada</th>
                                        <th>Días Restantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximosPartos as $parto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parto['codigo'] . ' - ' . ($parto['nombre'] ?: 'Sin nombre')); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($parto['fecha_parto_estimada'])); ?></td>
                                        <td>
                                            <strong style="color: <?php echo $parto['dias_restantes'] <= 30 ? 'var(--color-warning)' : 'var(--color-success)'; ?>;">
                                                <?php echo $parto['dias_restantes']; ?> días
                                            </strong>
                                            <?php if ($parto['dias_restantes'] <= 30): ?>
                                                <span class="badge badge-warning" style="margin-left: var(--space-2); font-size: var(--text-xs);">Próximo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: var(--space-8);">
                            <div class="empty-state-icon">📅</div>
                            <h3 class="empty-state-title">No hay partos próximos</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ventas Recientes -->
        <?php if (!empty($ventasRecientes)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Ventas Recientes</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Precio</th>
                                <th>Fecha</th>
                                <th>Comprador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasRecientes as $venta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($venta['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($venta['nombre'] ?: '-'); ?></td>
                                <td><span class="badge badge-<?php echo $venta['tipo']; ?>"><?php echo ucfirst($venta['tipo']); ?></span></td>
                                <td><strong>$<?php echo number_format($venta['precio_venta'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?></td>
                                <td><?php echo htmlspecialchars($venta['comprador'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>