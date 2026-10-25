<?php
/**
 * Configuración de la aplicación
 */

// Entorno
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', APP_ENV !== 'production');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/control_ganadero');

// Timezone
date_default_timezone_set('America/Guayaquil');

// Configuración de subida
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif']);

// Paginación
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Logging
define('LOG_DIR', __DIR__ . '/../storage/logs/');

// Rate limiting por defecto
define('RATE_LIMIT_DEFAULT_MAX', 30);
define('RATE_LIMIT_DEFAULT_WINDOW', 60); // segundos

// BD - usar PDO con excepciones
// (Configuración real en config/database.php)