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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Preñez - Control Ganadero</title>

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
                <h1><i class="fas fa-heartbeat"></i> Gestión de Preñez y Partos</h1>
            </div>
        </div>
        <div class="container">
            <nav class="nav-menu" aria-label="Navegación principal">
                <a href="index.php" class="nav-link"><i class="fas fa-list"></i> Inventario</a>
                <a href="crear_animal.php" class="nav-link"><i class="fas fa-plus-circle"></i> Nuevo Animal</a>
                <a href="prenez.php" class="nav-link active"><i class="fas fa-heartbeat"></i> Preñez</a>
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
                    <strong>⚠️ Errores:</strong>
                    <ul style="margin: var(--space-2) 0 0 var(--space-4);">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario Registrar Nueva Preñez -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Registrar Nueva Preñez</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= \Core\Security::csrfField() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-cow"></i> Vaca <span class="text-danger">*</span></label>
                            <select name="vaca_id" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($vacas as $vaca): ?>
                                    <option value="<?php echo $vaca['id']; ?>" <?php echo \Core\Security::old('vaca_id') == $vaca['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vaca['codigo'] . ' - ' . $vaca['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-bull"></i> Toro</label>
                            <select name="toro_id" class="form-select">
                                <option value="">Sin toro específico</option>
                                <?php foreach ($toros as $toro): ?>
                                    <option value="<?php echo $toro['id']; ?>" <?php echo \Core\Security::old('toro_id') == $toro['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($toro['codigo'] . ' - ' . $toro['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar"></i> Fecha de Servicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-input" name="fecha_servicio" required value="<?php echo \Core\Security::old('fecha_servicio', date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-check"></i> Fecha Parto Estimada <span class="text-danger">*</span></label>
                            <input type="date" class="form-input" name="fecha_parto_estimada" required value="<?php echo \Core\Security::old('fecha_parto_estimada', date('Y-m-d', strtotime('+280 days'))); ?>">
                            <p class="form-hint">Gestación promedio: 280 días</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-seedling"></i> Tipo de Servicio</label>
                            <select name="tipo_servicio" class="form-select">
                                <option value="monta_natural" <?php echo \Core\Security::old('tipo_servicio', 'monta_natural') === 'monta_natural' ? 'selected' : ''; ?>>Monta Natural</option>
                                <option value="inseminacion" <?php echo \Core\Security::old('tipo_servicio') === 'inseminacion' ? 'selected' : ''; ?>>Inseminación Artificial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-flag"></i> Estado</label>
                            <select name="estado" class="form-select">
                                <option value="sospechosa" <?php echo \Core\Security::old('estado', 'sospechosa') === 'sospechosa' ? 'selected' : ''; ?>>Sospechosa</option>
                                <option value="confirmada" <?php echo \Core\Security::old('estado') === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-comment"></i> Observaciones</label>
                        <textarea class="form-input" name="observaciones" rows="3"></textarea>
                    </div>

                    <div style="margin-top: var(--space-4);">
                        <button type="submit" name="crear_prenez" class="btn btn-primary">
                            <i class="fas fa-save"></i> Registrar Preñez
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-body" style="padding: var(--space-4);">
                <strong style="margin-right: var(--space-3);">Filtrar por estado:</strong>
                <a href="prenez.php" class="btn btn-sm <?php echo $filtro === '' ? 'btn-primary' : 'btn-outline btn-secondary'; ?>">Todas</a>
                <a href="prenez.php?estado=sospechosa" class="btn btn-sm <?php echo $filtro === 'sospechosa' ? 'btn-primary' : 'btn-outline btn-secondary'; ?>">Sospechosas</a>
                <a href="prenez.php?estado=confirmada" class="btn btn-sm <?php echo $filtro === 'confirmada' ? 'btn-primary' : 'btn-outline btn-secondary'; ?>">Confirmadas</a>
                <a href="prenez.php?estado=finalizada" class="btn btn-sm <?php echo $filtro === 'finalizada' ? 'btn-primary' : 'btn-outline btn-secondary'; ?>">Finalizadas</a>
                <a href="prenez.php?estado=perdida" class="btn btn-sm <?php echo $filtro === 'perdida' ? 'btn-primary' : 'btn-outline btn-secondary'; ?>">Perdidas</a>
            </div>
        </div>

        <!-- Tabla de Preñeces -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Lista de Preñeces</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Imagen</th>
                                <th>Vaca</th>
                                <th style="width: 80px;">Imagen</th>
                                <th>Toro</th>
                                <th>Fecha Servicio</th>
                                <th>Fecha Parto Est.</th>
                                <th>Días Restantes</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Ternero</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preneces as $prenez): ?>
                            <tr <?php echo ($prenez['dias_restantes'] <= 30 && $prenez['dias_restantes'] >= 0 && $prenez['estado'] !== 'finalizada') ? 'style="background: var(--color-warning-light);"' : ''; ?>>
                                <td>
                                    <?php
                                    $imgVaca = obtenerImagenPrincipal($prenez['vaca_id']);
                                    if ($imgVaca): ?>
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($imgVaca['nombre_archivo']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-md);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--color-gray-100); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🐄</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="ver_animal.php?id=<?php echo $prenez['vaca_id']; ?>" style="color: var(--color-primary); font-weight: 600;">
                                        <?php echo htmlspecialchars($prenez['codigo_vaca']); ?>
                                    </a>
                                    <div style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                        <?php echo htmlspecialchars($prenez['nombre_vaca'] ?: 'Sin nombre'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($prenez['toro_id']): ?>
                                        <?php
                                        $imgToro = obtenerImagenPrincipal($prenez['toro_id']);
                                        if ($imgToro): ?>
                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($imgToro['nombre_archivo']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-md);">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: var(--color-gray-100); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🐂</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--color-gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($prenez['toro_id']): ?>
                                        <a href="ver_animal.php?id=<?php echo $prenez['toro_id']; ?>" style="color: var(--color-primary); font-weight: 600;">
                                            <?php echo htmlspecialchars($prenez['codigo_toro']); ?>
                                        </a>
                                        <div style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                            <?php echo htmlspecialchars($prenez['nombre_toro'] ?: 'Sin nombre'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--color-gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($prenez['fecha_servicio'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($prenez['fecha_parto_estimada'])); ?></td>
                                <td>
                                    <?php if ($prenez['estado'] === 'finalizada'): ?>
                                        <span style="color: var(--color-gray-500);">Finalizada</span>
                                    <?php else: ?>
                                        <?php
                                        $dias = $prenez['dias_restantes'];
                                        $color = $dias <= 30 ? 'var(--color-warning)' : 'var(--color-gray-500)';
                                        ?>
                                        <strong style="color: <?php echo $color; ?>;">
                                            <?php echo $dias >= 0 ? $dias . ' días' : 'Vencida (' . abs($dias) . ' días)'; ?>
                                        </strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $prenez['tipo_servicio'] === 'monta_natural' ? 'info' : 'secondary'; ?>">
                                        <?php echo $prenez['tipo_servicio'] === 'monta_natural' ? 'Monta' : 'IA'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $prenez['estado']; ?>">
                                        <?php echo ucfirst($prenez['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($prenez['codigo_ternero']): ?>
                                        <a href="ver_animal.php?id=<?php echo $prenez['ternero_id']; ?>" style="color: var(--color-primary);">
                                            <?php echo htmlspecialchars($prenez['codigo_ternero']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--color-gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($prenez['estado'] !== 'finalizada' && $prenez['estado'] !== 'perdida'): ?>
                                        <button onclick="mostrarModalParto(<?php echo $prenez['id']; ?>, '<?php echo htmlspecialchars($prenez['codigo_vaca'], ENT_QUOTES); ?>')" class="btn btn-sm btn-primary">
                                            <i class="fas fa-baby"></i> Parto
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($preneces)): ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: var(--space-10); color: var(--color-gray-500);">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🤰</div>
                                        <h3 class="empty-state-title">No hay preñeces registradas</h3>
                                        <p class="empty-state-text">Usa el formulario de arriba para registrar la primera</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Registrar Parto -->
    <div class="modal" id="modalParto" style="display: none;">
        <div class="modal-backdrop" onclick="cerrarModalParto()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-baby"></i> Registrar Parto</h2>
                <button class="modal-close" onclick="cerrarModalParto()" aria-label="Cerrar"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="infoVaca" style="background: var(--color-gray-50); padding: var(--space-3); border-radius: var(--radius-md); margin-bottom: var(--space-4);"></div>
                <form method="POST">
                    <?= \Core\Security::csrfField() ?>
                    <input type="hidden" name="prenez_id" id="prenez_id">

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Fecha Real del Parto <span class="text-danger">*</span></label>
                        <input type="date" class="form-input" name="fecha_parto_real" required value="<?php echo \Core\Security::old('fecha_parto_real', date('Y-m-d')); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Ternero Registrado <span class="text-danger">*</span></label>
                        <select name="ternero_id" class="form-select" required>
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
                        <p class="form-hint">Si el ternero no está en la lista, <a href="crear_animal.php" target="_blank" style="color: var(--color-primary);">críelo aquí</a></p>
                    </div>

                    <div style="margin-top: var(--space-4); display: flex; gap: var(--space-3); justify-content: flex-end;">
                        <button type="submit" name="finalizar_prenez" class="btn btn-success">
                            <i class="fas fa-check"></i> Registrar Parto
                        </button>
                        <button type="button" onclick="cerrarModalParto()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function mostrarModalParto(prenezId, codigoVaca) {
            document.getElementById('prenez_id').value = prenezId;
            document.getElementById('infoVaca').innerHTML = '<strong><i class="fas fa-cow"></i> Vaca:</strong> ' + codigoVaca;
            document.getElementById('modalParto').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalParto() {
            document.getElementById('modalParto').style.display = 'none';
            document.body.style.overflow = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalParto');
            if (event.target === modal) {
                cerrarModalParto();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalParto();
            }
        });
    </script>
</body>
</html>