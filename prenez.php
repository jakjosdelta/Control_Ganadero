<?php
require_once 'includes/functions.php';

// Verificar CSRF en POST
\Core\Security::requireCsrf();

$mensaje = [];
$errores = [];

// Crear preñez
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_prenez'])) {
    \Core\Security::saveOld($_POST);

    try {
        $rules = [
            'vaca_id' => 'required',
            'fecha_servicio' => 'required|date',
            'fecha_parto_estimada' => 'required|date|date_after:' . date('Y-m-d'),
            'tipo_servicio' => 'required|in:monta_natural,inseminacion',
            'estado' => 'required|in:confirmada,sospechosa',
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        }

        if (empty($errores)) {
            crearPrenez($_POST);
            \Core\Security::clearOld();
            \Core\Security::setFlash('success', 'Preñez registrada exitosamente');
            header('Location: prenez.php');
            exit;
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

// Finalizar preñez
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_prenez'])) {
    \Core\Security::saveOld($_POST);

    try {
        $rules = [
            'prenez_id' => 'required',
            'ternero_id' => 'required',
            'fecha_parto_real' => 'required|date',
        ];

        $validation = \Core\Security::validate($_POST, $rules);

        if (!$validation['valid']) {
            $errores = [];
            foreach ($validation['errors'] as $fieldErrors) {
                $errores = array_merge($errores, $fieldErrors);
            }
        }

        if (empty($errores)) {
            finalizarPrenez($_POST['prenez_id'], $_POST['ternero_id'], $_POST['fecha_parto_real']);
            \Core\Security::clearOld();
            \Core\Security::setFlash('success', 'Parto registrado exitosamente');
            header('Location: prenez.php');
            exit;
        }
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

$filtro = $_GET['estado'] ?? '';
$preneces = obtenerPreneces(['estado' => $filtro]);
$vacas = obtenerVacas();
$toros = obtenerToros();

// Necesitamos la función obtenerImagenPrincipal
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Preñez - Control Ganadero</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
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
        .mensaje {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .errores {
            background: #ffebee;
            border: 1px solid #ef5350;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-primary {
            background: #2e7d32;
            color: white;
        }
        .btn-secondary {
            background: #757575;
            color: white;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .tabla-container {
            background: white;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        th {
            background: #2e7d32;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-confirmada { background: #e8f5e9; color: #2e7d32; }
        .badge-sospechosa { background: #fff9c4; color: #f57f17; }
        .badge-finalizada { background: #e3f2fd; color: #1976d2; }
        .badge-perdida { background: #ffebee; color: #c62828; }
        .alerta {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 8px;
        }
        .filtros {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filtros a {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            background: #f5f5f5;
        }
        .filtros a.activo {
            background: #2e7d32;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 8px;
            padding: 30px;
        }
        .section-title {
            font-size: 1.3em;
            color: #2e7d32;
            margin: 20px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2e7d32;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🤰 Gestión de Preñez y Partos</h1>
        </div>
    </header>
    
    <div class="container">
        <div class="menu">
            <a href="index.php">📋 Inventario</a>
            <a href="crear_animal.php">➕ Nuevo Animal</a>
            <a href="prenez.php">🤰 Gestión de Preñez</a>
            <a href="reportes.php">📊 Reportes</a>
        </div>
        
        <?php
        $flashes = \Core\Security::getFlashes();
        if (!empty($flashes['success'])): ?>
            <div class="mensaje">
                <?php foreach ($flashes['success'] as $msg): ?>
                    <strong>✅ <?php echo htmlspecialchars($msg); ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashes['error'])): ?>
            <div class="errores">
                <?php foreach ($flashes['error'] as $msg): ?>
                    <strong>❌ <?php echo htmlspecialchars($msg); ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="errores">
                <strong>⚠️ Errores:</strong>
                <ul><?php foreach ($errores as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="margin-bottom:20px;">➕ Registrar Nueva Preñez</h2>
            <form method="POST">
                <?= \Core\Security::csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Vaca *</label>
                        <select name="vaca_id" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($vacas as $vaca): ?>
                                <option value="<?php echo $vaca['id']; ?>" <?php echo \Core\Security::old('vaca_id') == $vaca['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vaca['codigo'] . ' - ' . $vaca['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Toro</label>
                        <select name="toro_id">
                            <option value="">Sin toro específico</option>
                            <?php foreach ($toros as $toro): ?>
                                <option value="<?php echo $toro['id']; ?>" <?php echo \Core\Security::old('toro_id') == $toro['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($toro['codigo'] . ' - ' . $toro['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Servicio *</label>
                        <input type="date" name="fecha_servicio" required value="<?php echo \Core\Security::old('fecha_parto_real', date('Y-m-d')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Parto Estimada *</label>
                        <input type="date" name="fecha_parto_estimada" required value="<?php echo \Core\Security::old('fecha_parto_estimada', date('Y-m-d', strtotime('+280 days'))); ?>">
                        <small style="color:#666;">Gestación promedio: 280 días</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Servicio</label>
                        <select name="tipo_servicio">
                            <option value="monta_natural" <?php echo \Core\Security::old('tipo_servicio', 'monta_natural') === 'monta_natural' ? 'selected' : ''; ?>>Monta Natural</option>
                            <option value="inseminacion" <?php echo \Core\Security::old('tipo_servicio') === 'inseminacion' ? 'selected' : ''; ?>>Inseminación Artificial</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="sospechosa" <?php echo \Core\Security::old('estado', 'sospechosa') === 'sospechosa' ? 'selected' : ''; ?>>Sospechosa</option>
                            <option value="confirmada" <?php echo \Core\Security::old('estado') === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3"></textarea>
                </div>
                
                <button type="submit" name="crear_prenez" class="btn btn-primary">💾 Registrar Preñez</button>
            </form>
        </div>
        
        <div class="filtros">
            <strong>Filtrar por estado:</strong>
            <a href="prenez.php" class="<?php echo $filtro === '' ? 'activo' : ''; ?>">Todas</a>
            <a href="prenez.php?estado=sospechosa" class="<?php echo $filtro === 'sospechosa' ? 'activo' : ''; ?>">Sospechosas</a>
            <a href="prenez.php?estado=confirmada" class="<?php echo $filtro === 'confirmada' ? 'activo' : ''; ?>">Confirmadas</a>
            <a href="prenez.php?estado=finalizada" class="<?php echo $filtro === 'finalizada' ? 'activo' : ''; ?>">Finalizadas</a>
            <a href="prenez.php?estado=perdida" class="<?php echo $filtro === 'perdida' ? 'activo' : ''; ?>">Perdidas</a>
        </div>
        
        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>Vaca</th>
                        <th>Toro</th>
                        <th>Fecha Servicio</th>
                        <th>Fecha Parto Est.</th>
                        <th>Días Restantes</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Ternero</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preneces as $prenez): ?>
                    <tr class="<?php echo ($prenez['dias_restantes'] <= 30 && $prenez['dias_restantes'] >= 0 && $prenez['estado'] !== 'finalizada') ? 'alerta' : ''; ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php 
                                $imgVaca = obtenerImagenPrincipal($prenez['vaca_id']);
                                if ($imgVaca): ?>
                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($imgVaca['nombre_archivo']); ?>" 
                                         style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                                        🐄
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <a href="ver_animal.php?id=<?php echo $prenez['vaca_id']; ?>" style="color:#2e7d32;font-weight:600;">
                                        <?php echo htmlspecialchars($prenez['codigo_vaca']); ?>
                                    </a>
                                    <div style="font-size:12px;color:#666;">
                                        <?php echo htmlspecialchars($prenez['nombre_vaca'] ?: 'Sin nombre'); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($prenez['codigo_toro']): ?>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php 
                                    $imgToro = obtenerImagenPrincipal($prenez['toro_id']);
                                    if ($imgToro): ?>
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($imgToro['nombre_archivo']); ?>" 
                                             style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                    <?php else: ?>
                                        <div style="width:50px;height:50px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                                            🐂
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="ver_animal.php?id=<?php echo $prenez['toro_id']; ?>" style="color:#2e7d32;font-weight:600;">
                                            <?php echo htmlspecialchars($prenez['codigo_toro']); ?>
                                        </a>
                                        <div style="font-size:12px;color:#666;">
                                            <?php echo htmlspecialchars($prenez['nombre_toro'] ?: 'Sin nombre'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color:#999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($prenez['fecha_servicio'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prenez['fecha_parto_estimada'])); ?></td>
                        <td>
                            <?php if ($prenez['estado'] === 'finalizada'): ?>
                                <span style="color:#666;">Finalizada</span>
                            <?php else: ?>
                                <?php 
                                $dias = $prenez['dias_restantes'];
                                $color = $dias <= 30 ? '#f57c00' : '#666';
                                ?>
                                <strong style="color:<?php echo $color; ?>;">
                                    <?php echo $dias >= 0 ? $dias . ' días' : 'Vencida (' . abs($dias) . ' días)'; ?>
                                </strong>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $prenez['tipo_servicio'] === 'monta_natural' ? 'Monta' : 'IA'; ?></td>
                        <td><span class="badge badge-<?php echo $prenez['estado']; ?>"><?php echo ucfirst($prenez['estado']); ?></span></td>
                        <td>
                            <?php if ($prenez['codigo_ternero']): ?>
                                <a href="ver_animal.php?id=<?php echo $prenez['ternero_id']; ?>" style="color:#2e7d32;">
                                    <?php echo htmlspecialchars($prenez['codigo_ternero']); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prenez['estado'] !== 'finalizada' && $prenez['estado'] !== 'perdida'): ?>
                                <button onclick="mostrarModalParto(<?php echo $prenez['id']; ?>, '<?php echo htmlspecialchars($prenez['codigo_vaca']); ?>')" class="btn btn-primary btn-small">
                                    Registrar Parto
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($preneces)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:30px;color:#999;">
                            No hay preñeces registradas
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Registrar Parto -->
    <div id="modalParto" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom:20px;">🐄 Registrar Parto</h2>
            <div id="infoVaca" style="background:#f5f5f5;padding:10px;border-radius:5px;margin-bottom:20px;"></div>
            <form method="POST">
                <?= \Core\Security::csrfField() ?>
                <input type="hidden" name="prenez_id" id="prenez_id">
                
                <div class="form-group">
                    <label>Fecha Real del Parto *</label>
                    <input type="date" name="fecha_parto_real" required value="<?php echo \Core\Security::old('fecha_parto_real', date('Y-m-d')); ?>">
                </div>
                
                <div class="form-group">
                    <label>Ternero Registrado *</label>
                    <select name="ternero_id" required>
                        <option value="">Seleccione el ternero...</option>
                        <?php
                        $db = getDB();
                        $terneros = $db->query("SELECT id, codigo, nombre FROM animales WHERE tipo = 'ternero' AND NOT EXISTS (SELECT 1 FROM prenez WHERE ternero_id = animales.id) ORDER BY fecha_registro DESC")->fetchAll();
                        foreach ($terneros as $ternero): ?>
                            <option value="<?php echo $ternero['id']; ?>" <?php echo \Core\Security::old('ternero_id') == $ternero['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ternero['codigo'] . ' - ' . ($ternero['nombre'] ?: 'Sin nombre')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#666;">Si el ternero no está en la lista, <a href="crear_animal.php" target="_blank" style="color:#2e7d32;">créelo aquí</a></small>
                </div>
                
                <div style="margin-top:20px;">
                    <button type="submit" name="finalizar_prenez" class="btn btn-primary">✅ Registrar Parto</button>
                    <button type="button" onclick="cerrarModalParto()" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function mostrarModalParto(prenezId, codigoVaca) {
            document.getElementById('prenez_id').value = prenezId;
            document.getElementById('infoVaca').innerHTML = '<strong>Vaca:</strong> ' + codigoVaca;
            document.getElementById('modalParto').style.display = 'block';
        }
        
        function cerrarModalParto() {
            document.getElementById('modalParto').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalParto');
            if (event.target === modal) {
                cerrarModalParto();
            }
        }
    </script>
</body>
</html>