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
$masPesados = $db->query("SELECT codigo, nombre, tipo, peso_actual FROM animales WHERE peso_actual IS NOT NULL ORDER BY peso_actual DESC LIMIT 10")->fetchAll();

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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        header {
            background: linear-gradient(135deg, #2e7d32, #43a047);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .menu {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .menu a {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .numero {
            font-size: 42px;
            font-weight: bold;
            color: #2e7d32;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #2e7d32;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2e7d32;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .barra {
            background: #e0e0e0;
            height: 25px;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        .barra-fill {
            background: #2e7d32;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        @media print {
            .menu { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>📊 Reportes y Estadísticas</h1>
        </div>
    </header>
    
    <div class="container">
        <div class="menu">
            <a href="index.php">📋 Inventario</a>
            <a href="crear_animal.php">➕ Nuevo Animal</a>
            <a href="prenez.php">🤰 Gestión de Preñez</a>
            <a href="reportes.php">📊 Reportes</a>
            <button onclick="window.print()" style="background:#757575;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;">🖨️ Imprimir</button>
        </div>
        
        <h2 style="margin-bottom:20px;color:#2e7d32;">📈 Estadísticas Generales</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Animales</h3>
                <div class="numero"><?php echo $stats['total_animales']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Vacas</h3>
                <div class="numero"><?php echo $stats['total_vacas']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Terneros</h3>
                <div class="numero"><?php echo $stats['total_terneros']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Toros</h3>
                <div class="numero"><?php echo $stats['total_toros']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Disponibles</h3>
                <div class="numero"><?php echo $stats['disponibles']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Vendidos</h3>
                <div class="numero"><?php echo $stats['vendidos']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Preñeces Activas</h3>
                <div class="numero"><?php echo $stats['prenez_activas']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Partos este Mes</h3>
                <div class="numero"><?php echo $stats['partos_mes']; ?></div>
            </div>
        </div>
        
        <div class="cards-grid">
            <div class="card">
                <h2>🏆 Distribución por Raza</h2>
                <?php if (!empty($razas)): ?>
                    <?php 
                    $maxCantidad = max(array_column($razas, 'cantidad'));
                    foreach ($razas as $raza): 
                        $porcentaje = ($raza['cantidad'] / $maxCantidad) * 100;
                    ?>
                        <div style="margin-bottom:15px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                <strong><?php echo htmlspecialchars($raza['raza']); ?></strong>
                                <span><?php echo $raza['cantidad']; ?> animales</span>
                            </div>
                            <div class="barra">
                                <div class="barra-fill" style="width:<?php echo $porcentaje; ?>%;">
                                    <?php echo round($porcentaje); ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">No hay datos de razas</div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>⚖️ Peso Promedio por Tipo</h2>
                <?php if (!empty($pesoPorTipo)): ?>
                    <table>
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
                                <td><?php echo ucfirst($peso['tipo']); ?></td>
                                <td><?php echo $peso['cantidad']; ?></td>
                                <td><strong><?php echo number_format($peso['peso_promedio'], 2); ?> kg</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No hay datos de peso</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="cards-grid">
            <div class="card">
                <h2>🥇 Animales Más Pesados</h2>
                <?php if (!empty($masPesados)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($masPesados as $animal): ?>
                            <tr>
                                <td><a href="ver_animal.php?id=<?php echo $animal['id'] ?? '#'; ?>" style="color:#2e7d32;"><?php echo htmlspecialchars($animal['codigo']); ?></a></td>
                                <td><?php echo htmlspecialchars($animal['nombre'] ?: '-'); ?></td>
                                <td><?php echo ucfirst($animal['tipo']); ?></td>
                                <td><strong><?php echo number_format($animal['peso_actual'], 2); ?> kg</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No hay datos</div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>📅 Próximos Partos</h2>
                <?php if (!empty($proximosPartos)): ?>
                    <table>
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
                                    <strong style="color:<?php echo $parto['dias_restantes'] <= 30 ? '#f57c00' : '#2e7d32'; ?>;">
                                        <?php echo $parto['dias_restantes']; ?> días
                                    </strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No hay partos próximos</div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($ventasRecientes)): ?>
        <div class="card">
            <h2>💰 Ventas Recientes</h2>
            <table>
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
                        <td><?php echo ucfirst($venta['tipo']); ?></td>
                        <td><strong>$<?php echo number_format($venta['precio_venta'], 2); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?></td>
                        <td><?php echo htmlspecialchars($venta['comprador'] ?: '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>