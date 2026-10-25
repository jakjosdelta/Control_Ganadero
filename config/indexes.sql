-- =====================================================
-- ÍNDICES ADICIONALES PARA MEJORAR RENDIMIENTO
-- Ejecutar en MySQL: mysql -u root -p control_ganadero < config/indexes.sql
-- =====================================================

USE control_ganadero;

-- Tabla animales: consultas frecuentes por tipo + estado_venta (listados con filtros)
CREATE INDEX IF NOT EXISTS idx_animales_tipo_estado ON animales(tipo, estado_venta);

-- Tabla animales: ordenamiento por fecha_registro (listados)
CREATE INDEX IF NOT EXISTS idx_animales_fecha_registro ON animales(fecha_registro DESC);

-- Tabla animales: búsqueda por código, nombre, raza (filtro buscar)
CREATE INDEX IF NOT EXISTS idx_animales_busqueda ON animales(codigo, nombre, raza);

-- Tabla animales: genealogía (madre/padre) - ya existen idx_madre, idx_padre
-- Pero añadimos índice compuesto para consultas de hijos
CREATE INDEX IF NOT EXISTS idx_animales_genealogia ON animales(madre_id, padre_id);

-- Tabla prenez: consultas activas por fecha de parto (dashboard, notificaciones)
CREATE INDEX IF NOT EXISTS idx_prenez_activas_fecha ON prenez(estado, fecha_parto_estimada);

-- Tabla prenez: consulta por vaca + estado (gestión de preñez)
CREATE INDEX IF NOT EXISTS idx_prenez_vaca_estado ON prenez(vaca_id, estado);

-- Tabla prenez: consulta por toro (historial de monta)
CREATE INDEX IF NOT EXISTS idx_prenez_toro ON prenez(toro_id);

-- Tabla pesajes: historial por animal ordenado por fecha
CREATE INDEX IF NOT EXISTS idx_pesajes_animal_fecha ON pesajes(animal_id, fecha_pesaje DESC);

-- Tabla imagenes_animales: imagen principal por animal
CREATE INDEX IF NOT EXISTS idx_imagenes_principal ON imagenes_animales(animal_id, es_principal, orden);

-- Tabla imagenes_animales: ordenamiento por animal + orden
CREATE INDEX IF NOT EXISTS idx_imagenes_animal_orden ON imagenes_animales(animal_id, orden);

-- Estadísticas: conteos rápidos por tipo (caché de estadísticas)
-- No son índices críticos pero pueden ayudar en consultas de reporte
-- CREATE INDEX IF NOT EXISTS idx_animales_tipo ON animales(tipo); -- Ya existe
-- CREATE INDEX IF NOT EXISTS idx_animales_estado ON animales(estado_venta); -- Ya existe

-- =====================================================
-- VERIFICAR ÍNDICES EXISTENTES
-- =====================================================
-- SHOW INDEX FROM animales;
-- SHOW INDEX FROM prenez;
-- SHOW INDEX FROM pesajes;
-- SHOW INDEX FROM imagenes_animales;

-- =====================================================
-- ANALIZAR TABLAS DESPUÉS DE CREAR ÍNDICES
-- =====================================================
-- ANALYZE TABLE animales, prenez, pesajes, imagenes_animales;