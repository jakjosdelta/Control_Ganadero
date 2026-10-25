<?php
require_once __DIR__ . '/../config/database.php';

// Funciones de validación
function validarCodigo($codigo, $id = null) {
    $db = getDB();
    $sql = "SELECT id FROM animales WHERE codigo = ?";
    if ($id) {
        $sql .= " AND id != ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo, $id]);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo]);
    }
    return $stmt->rowCount() === 0;
}

function validarImagen($file) {
    $errores = [];
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $tamanoMax = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $errores[] = "Error al subir el archivo";
    }
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (!in_array($file['type'], $tiposPermitidos)) {
            $errores[] = "Tipo de archivo no permitido. Solo JPG, PNG y GIF";
        }
        if ($file['size'] > $tamanoMax) {
            $errores[] = "El archivo es demasiado grande. Máximo 5MB";
        }
    }
    
    return $errores;
}

function subirImagen($file, $imagenAnterior = null) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $imagenAnterior;
    }
    
    $errores = validarImagen($file);
    if (!empty($errores)) {
        throw new Exception(implode(", ", $errores));
    }
    
    // Eliminar imagen anterior si existe
    if ($imagenAnterior && file_exists(UPLOAD_DIR . $imagenAnterior)) {
        unlink(UPLOAD_DIR . $imagenAnterior);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('animal_') . '.' . $extension;
    $rutaDestino = UPLOAD_DIR . $nombreArchivo;
    
    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        throw new Exception("Error al guardar la imagen");
    }
    
    return $nombreArchivo;
}

function eliminarImagen($nombreArchivo) {
    if ($nombreArchivo && file_exists(UPLOAD_DIR . $nombreArchivo)) {
        unlink(UPLOAD_DIR . $nombreArchivo);
    }
}

// Funciones CRUD de Animales
function obtenerAnimales($filtros = [], $pagina = 1, $porPagina = 10) {
    $db = getDB();
    $where = [];
    $params = [];
    
    if (!empty($filtros['tipo'])) {
        $where[] = "a.tipo = ?";
        $params[] = $filtros['tipo'];
    }
    
    if (!empty($filtros['estado'])) {
        $where[] = "a.estado_venta = ?";
        $params[] = $filtros['estado'];
    }
    
    if (!empty($filtros['buscar'])) {
        $where[] = "(a.codigo LIKE ? OR a.nombre LIKE ? OR a.raza LIKE ?)";
        $buscar = '%' . $filtros['buscar'] . '%';
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $sqlCount = "SELECT COUNT(*) as total FROM animales a $whereClause";
    $stmtCount = $db->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = $stmtCount->fetch()['total'];
    
    // Obtener registros
    $offset = ($pagina - 1) * $porPagina;
    $sql = "SELECT a.*, 
            m.nombre as nombre_madre, m.codigo as codigo_madre,
            p.nombre as nombre_padre, p.codigo as codigo_padre
            FROM animales a
            LEFT JOIN animales m ON a.madre_id = m.id
            LEFT JOIN animales p ON a.padre_id = p.id
            $whereClause
            ORDER BY a.fecha_registro DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $porPagina;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return [
        'animales' => $stmt->fetchAll(),
        'total' => $total,
        'paginas' => ceil($total / $porPagina),
        'pagina_actual' => $pagina
    ];
}

function obtenerAnimal($id) {
    $db = getDB();
    $sql = "SELECT a.*, 
            m.nombre as nombre_madre, m.codigo as codigo_madre,
            p.nombre as nombre_padre, p.codigo as codigo_padre
            FROM animales a
            LEFT JOIN animales m ON a.madre_id = m.id
            LEFT JOIN animales p ON a.padre_id = p.id
            WHERE a.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function crearAnimal($datos, $imagen = null) {
    $db = getDB();
    
    // Validar código único
    if (!validarCodigo($datos['codigo'])) {
        throw new Exception("El código ya existe");
    }
    
    $sql = "INSERT INTO animales (codigo, nombre, tipo, sexo, fecha_nacimiento, peso_actual, 
            raza, color, madre_id, padre_id, estado_venta, precio_venta, observaciones, imagen) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $datos['codigo'],
        $datos['nombre'],
        $datos['tipo'],
        $datos['sexo'],
        $datos['fecha_nacimiento'],
        $datos['peso_actual'] ?: null,
        $datos['raza'] ?: null,
        $datos['color'] ?: null,
        $datos['madre_id'] ?: null,
        $datos['padre_id'] ?: null,
        $datos['estado_venta'] ?: 'disponible',
        $datos['precio_venta'] ?: null,
        $datos['observaciones'] ?: null,
        $imagen
    ]);
    
    return $db->lastInsertId();
}

function actualizarAnimal($id, $datos, $imagen = null) {
    $db = getDB();
    
    // Validar código único
    if (!validarCodigo($datos['codigo'], $id)) {
        throw new Exception("El código ya existe");
    }
    
    $sql = "UPDATE animales SET codigo = ?, nombre = ?, tipo = ?, sexo = ?, 
            fecha_nacimiento = ?, peso_actual = ?, raza = ?, color = ?, 
            madre_id = ?, padre_id = ?, estado_venta = ?, precio_venta = ?, 
            fecha_venta = ?, comprador = ?, observaciones = ?, imagen = ?
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $datos['codigo'],
        $datos['nombre'],
        $datos['tipo'],
        $datos['sexo'],
        $datos['fecha_nacimiento'],
        $datos['peso_actual'] ?: null,
        $datos['raza'] ?: null,
        $datos['color'] ?: null,
        $datos['madre_id'] ?: null,
        $datos['padre_id'] ?: null,
        $datos['estado_venta'] ?: 'disponible',
        $datos['precio_venta'] ?: null,
        $datos['fecha_venta'] ?: null,
        $datos['comprador'] ?: null,
        $datos['observaciones'] ?: null,
        $imagen ?: $datos['imagen_actual'],
        $id
    ]);
}

function eliminarAnimal($id) {
    $db = getDB();
    
    // Obtener imagen para eliminar
    $animal = obtenerAnimal($id);
    if ($animal && $animal['imagen']) {
        eliminarImagen($animal['imagen']);
    }
    
    $sql = "DELETE FROM animales WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$id]);
}

// Funciones de Preñez
function obtenerPreneces($filtros = []) {
    $db = getDB();
    $where = [];
    $params = [];
    
    if (!empty($filtros['estado'])) {
        $where[] = "pr.estado = ?";
        $params[] = $filtros['estado'];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT pr.*, 
            v.codigo as codigo_vaca, v.nombre as nombre_vaca,
            t.codigo as codigo_toro, t.nombre as nombre_toro,
            ter.codigo as codigo_ternero, ter.nombre as nombre_ternero,
            DATEDIFF(pr.fecha_parto_estimada, CURDATE()) as dias_restantes
            FROM prenez pr
            INNER JOIN animales v ON pr.vaca_id = v.id
            LEFT JOIN animales t ON pr.toro_id = t.id
            LEFT JOIN animales ter ON pr.ternero_id = ter.id
            $whereClause
            ORDER BY pr.fecha_parto_estimada ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function obtenerPrenez($id) {
    $db = getDB();
    $sql = "SELECT pr.*, 
            v.codigo as codigo_vaca, v.nombre as nombre_vaca,
            t.codigo as codigo_toro, t.nombre as nombre_toro
            FROM prenez pr
            INNER JOIN animales v ON pr.vaca_id = v.id
            LEFT JOIN animales t ON pr.toro_id = t.id
            WHERE pr.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function crearPrenez($datos) {
    $db = getDB();
    
    $sql = "INSERT INTO prenez (vaca_id, toro_id, fecha_servicio, fecha_parto_estimada, 
            tipo_servicio, estado, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $datos['vaca_id'],
        $datos['toro_id'] ?: null,
        $datos['fecha_servicio'],
        $datos['fecha_parto_estimada'],
        $datos['tipo_servicio'],
        $datos['estado'] ?: 'sospechosa',
        $datos['observaciones'] ?: null
    ]);
    
    return $db->lastInsertId();
}

function finalizarPrenez($id, $terneroId, $fechaParto) {
    $db = getDB();
    $sql = "UPDATE prenez SET estado = 'finalizada', ternero_id = ?, 
            fecha_parto_real = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$terneroId, $fechaParto, $id]);
}

function obtenerNotificacionesPartos() {
    $db = getDB();
    $sql = "SELECT pr.id, pr.fecha_parto_estimada, 
            v.codigo as codigo_vaca, v.nombre as nombre_vaca,
            DATEDIFF(pr.fecha_parto_estimada, CURDATE()) as dias_restantes
            FROM prenez pr
            INNER JOIN animales v ON pr.vaca_id = v.id
            WHERE pr.estado IN ('confirmada', 'sospechosa')
            AND DATEDIFF(pr.fecha_parto_estimada, CURDATE()) <= 30
            AND DATEDIFF(pr.fecha_parto_estimada, CURDATE()) >= 0
            ORDER BY pr.fecha_parto_estimada ASC";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

// Funciones de Pesajes
function registrarPesaje($animalId, $fecha, $peso, $observaciones = null) {
    $db = getDB();
    $sql = "INSERT INTO pesajes (animal_id, fecha_pesaje, peso, observaciones) 
            VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute([$animalId, $fecha, $peso, $observaciones]);
    
    // Actualizar peso actual del animal
    if ($resultado) {
        $sqlUpdate = "UPDATE animales SET peso_actual = ? WHERE id = ?";
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->execute([$peso, $animalId]);
    }
    
    return $resultado;
}

function obtenerPesajes($animalId) {
    $db = getDB();
    $sql = "SELECT * FROM pesajes WHERE animal_id = ? ORDER BY fecha_pesaje DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId]);
    return $stmt->fetchAll();
}

// Funciones para selectores
function obtenerVacas() {
    $db = getDB();
    $sql = "SELECT id, codigo, nombre FROM animales WHERE tipo = 'vaca' AND sexo = 'hembra' ORDER BY nombre";
    return $db->query($sql)->fetchAll();
}

function obtenerToros() {
    $db = getDB();
    $sql = "SELECT id, codigo, nombre FROM animales WHERE tipo IN ('toro', 'ternero') AND sexo = 'macho' ORDER BY nombre";
    return $db->query($sql)->fetchAll();
}

function obtenerHijos($animalId) {
    $db = getDB();
    $sql = "SELECT id, codigo, nombre, tipo, fecha_nacimiento 
            FROM animales 
            WHERE madre_id = ? OR padre_id = ? 
            ORDER BY fecha_nacimiento DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId, $animalId]);
    return $stmt->fetchAll();
}

function calcularEdad($fechaNacimiento) {
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    
    if ($edad->y > 0) {
        return $edad->y . ' año' . ($edad->y > 1 ? 's' : '') . 
               ($edad->m > 0 ? ' y ' . $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '') : '');
    } elseif ($edad->m > 0) {
        return $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
    } else {
        return $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
    }
}

// ========== FUNCIONES PARA MÚLTIPLES IMÁGENES ==========

function agregarImagenAnimal($animalId, $file, $descripcion = null) {
    $db = getDB();
    
    $errores = validarImagen($file);
    if (!empty($errores)) {
        throw new Exception(implode(", ", $errores));
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('animal_' . $animalId . '_') . '.' . $extension;
    $rutaDestino = UPLOAD_DIR . $nombreArchivo;
    
    // Redimensionar y comprimir imagen
    redimensionarImagen($file['tmp_name'], $rutaDestino, 1200, 1200, 85);
    
    // Obtener el siguiente orden
    $sqlOrden = "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente_orden 
                 FROM imagenes_animales WHERE animal_id = ?";
    $stmtOrden = $db->prepare($sqlOrden);
    $stmtOrden->execute([$animalId]);
    $orden = $stmtOrden->fetch()['siguiente_orden'];
    
    // Si es la primera imagen, hacerla principal
    $sqlCount = "SELECT COUNT(*) as total FROM imagenes_animales WHERE animal_id = ?";
    $stmtCount = $db->prepare($sqlCount);
    $stmtCount->execute([$animalId]);
    $esPrimera = $stmtCount->fetch()['total'] == 0;
    
    $sql = "INSERT INTO imagenes_animales (animal_id, nombre_archivo, es_principal, orden, descripcion) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId, $nombreArchivo, $esPrimera ? 1 : 0, $orden, $descripcion]);
    
    return $db->lastInsertId();
}

function redimensionarImagen($rutaOrigen, $rutaDestino, $maxAncho, $maxAlto, $calidad) {
    // Obtener información de la imagen
    $info = getimagesize($rutaOrigen);
    if (!$info) {
        throw new Exception("Archivo no es una imagen válida");
    }
    
    list($anchoOriginal, $altoOriginal, $tipo) = $info;
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($maxAncho / $anchoOriginal, $maxAlto / $altoOriginal);
    
    // Si la imagen ya es más pequeña, usar dimensiones originales
    if ($ratio >= 1) {
        $nuevoAncho = $anchoOriginal;
        $nuevoAlto = $altoOriginal;
    } else {
        $nuevoAncho = round($anchoOriginal * $ratio);
        $nuevoAlto = round($altoOriginal * $ratio);
    }
    
    // Crear imagen desde el archivo según el tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagenOriginal = imagecreatefromjpeg($rutaOrigen);
            break;
        case IMAGETYPE_PNG:
            $imagenOriginal = imagecreatefrompng($rutaOrigen);
            break;
        case IMAGETYPE_GIF:
            $imagenOriginal = imagecreatefromgif($rutaOrigen);
            break;
        default:
            throw new Exception("Tipo de imagen no soportado");
    }
    
    // Crear imagen nueva redimensionada
    $imagenNueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    
    // Preservar transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagenNueva, false);
        imagesavealpha($imagenNueva, true);
        $transparente = imagecolorallocatealpha($imagenNueva, 255, 255, 255, 127);
        imagefilledrectangle($imagenNueva, 0, 0, $nuevoAncho, $nuevoAlto, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled($imagenNueva, $imagenOriginal, 0, 0, 0, 0, 
                       $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
    
    // Guardar imagen optimizada
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagenNueva, $rutaDestino, $calidad);
            break;
        case IMAGETYPE_PNG:
            // PNG: calidad de 0-9 (invertido)
            $calidadPNG = round((100 - $calidad) / 11);
            imagepng($imagenNueva, $rutaDestino, $calidadPNG);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagenNueva, $rutaDestino);
            break;
    }
    
    // Liberar memoria
    imagedestroy($imagenOriginal);
    imagedestroy($imagenNueva);
    
    // Verificar que el tamaño final sea menor a 1.5MB
    if (filesize($rutaDestino) > 1.5 * 1024 * 1024) {
        // Si aún es muy grande, reducir más la calidad
        if ($tipo == IMAGETYPE_JPEG) {
            $imagenTemp = imagecreatefromjpeg($rutaDestino);
            imagejpeg($imagenTemp, $rutaDestino, 70);
            imagedestroy($imagenTemp);
        }
    }
    
    return true;
}

function obtenerImagenesAnimal($animalId) {
    $db = getDB();
    $sql = "SELECT * FROM imagenes_animales WHERE animal_id = ? ORDER BY orden ASC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId]);
    return $stmt->fetchAll();
}

function obtenerImagenPrincipal($animalId) {
    $db = getDB();
    $sql = "SELECT * FROM imagenes_animales WHERE animal_id = ? AND es_principal = 1 LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId]);
    $imagen = $stmt->fetch();
    
    // Si no hay imagen principal, obtener la primera
    if (!$imagen) {
        $sql = "SELECT * FROM imagenes_animales WHERE animal_id = ? ORDER BY orden ASC, id ASC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$animalId]);
        $imagen = $stmt->fetch();
    }
    
    return $imagen;
}

function establecerImagenPrincipal($imagenId, $animalId) {
    $db = getDB();
    
    // Quitar principal a todas las imágenes del animal
    $sql = "UPDATE imagenes_animales SET es_principal = 0 WHERE animal_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$animalId]);
    
    // Establecer la nueva principal
    $sql = "UPDATE imagenes_animales SET es_principal = 1 WHERE id = ? AND animal_id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$imagenId, $animalId]);
}

function eliminarImagenAnimal($imagenId, $animalId) {
    $db = getDB();
    
    // Obtener información de la imagen
    $sql = "SELECT * FROM imagenes_animales WHERE id = ? AND animal_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$imagenId, $animalId]);
    $imagen = $stmt->fetch();
    
    if (!$imagen) {
        throw new Exception("Imagen no encontrada");
    }
    
    $eraPrincipal = $imagen['es_principal'];
    
    // Eliminar archivo físico
    if (file_exists(UPLOAD_DIR . $imagen['nombre_archivo'])) {
        unlink(UPLOAD_DIR . $imagen['nombre_archivo']);
    }
    
    // Eliminar registro
    $sql = "DELETE FROM imagenes_animales WHERE id = ? AND animal_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$imagenId, $animalId]);
    
    // Si era la principal, establecer otra como principal
    if ($eraPrincipal) {
        $sql = "SELECT id FROM imagenes_animales WHERE animal_id = ? ORDER BY orden ASC, id ASC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$animalId]);
        $nuevaPrincipal = $stmt->fetch();
        
        if ($nuevaPrincipal) {
            establecerImagenPrincipal($nuevaPrincipal['id'], $animalId);
        }
    }
    
    return true;
}

function actualizarOrdenImagenes($animalId, $ordenIds) {
    $db = getDB();
    
    foreach ($ordenIds as $orden => $imagenId) {
        $sql = "UPDATE imagenes_animales SET orden = ? WHERE id = ? AND animal_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$orden, $imagenId, $animalId]);
    }
    
    return true;
}

function actualizarDescripcionImagen($imagenId, $animalId, $descripcion) {
    $db = getDB();
    $sql = "UPDATE imagenes_animales SET descripcion = ? WHERE id = ? AND animal_id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$descripcion, $imagenId, $animalId]);
}
?>