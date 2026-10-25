<?php
/**
 * Security.php - Utilidades de seguridad para PHP puro
 * CSRF, Validación, Sanitización, Rate Limit simple, Flash Messages
 */
declare(strict_types=1);

namespace Core;

class Security
{
    // ==================== CSRF ====================
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function verifyCsrfToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
    }

    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::verifyCsrfToken($token)) {
                http_response_code(403);
                self::setFlash('error', 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.');
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/');
                exit;
            }
        }
    }

    // ==================== SANITIZACIÓN ====================
    public static function sanitize(string $input, int $flags = ENT_QUOTES): string
    {
        return htmlspecialchars(trim($input), $flags, 'UTF-8');
    }

    public static function sanitizeArray(array $data): array
    {
        return array_map([self::class, 'sanitize'], $data);
    }

    public static function stripTags(string $input, string $allowed = ''): string
    {
        return strip_tags(trim($input), $allowed);
    }

    // ==================== VALIDACIÓN ====================
    /**
     * Valida un array de datos contra reglas.
     * Reglas soportadas:
     *   required, email, numeric, integer, decimal, date, date_after:YYYY-MM-DD,
     *   date_before:YYYY-MM-DD, min:N, max:N, min_len:N, max_len:N,
     *   in:val1,val2,..., unique:table,column,except_id,
     *   file, file_mimes:jpg,png,gif, file_max:N (KB),
     *   boolean, regex:/pattern/
     *
     * @return array ['valid' => bool, 'errors' => [campo => [mensajes]]]
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleParts = explode('|', $ruleString);
            $fieldLabel = self::fieldLabel($field);

            foreach ($ruleParts as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $error = self::checkRule($field, $value, $rule, $param, $fieldLabel, $data);
                if ($error) {
                    $errors[$field][] = $error;
                    break; // primer error por campo
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private static function checkRule(string $field, mixed $value, string $rule, ?string $param, string $label, array $allData): ?string
    {
        switch ($rule) {
            case 'required':
                if ($value === '' || $value === null || (is_array($value) && empty($value))) {
                    return "El campo {$label} es obligatorio.";
                }
                break;

            case 'email':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "El campo {$label} debe ser un email válido.";
                }
                break;

            case 'numeric':
                if ($value !== '' && !is_numeric($value)) {
                    return "El campo {$label} debe ser numérico.";
                }
                break;

            case 'integer':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "El campo {$label} debe ser un entero.";
                }
                break;

            case 'decimal':
                if ($value !== '' && !preg_match('/^\d+(\.\d+)?$/', $value)) {
                    return "El campo {$label} debe ser un número decimal.";
                }
                break;

            case 'date':
                if ($value !== '' && !$self::isValidDate($value)) {
                    return "El campo {$label} debe ser una fecha válida (YYYY-MM-DD).";
                }
                break;

            case 'date_after':
                if ($value !== '' && $param && strtotime($value) <= strtotime($param)) {
                    return "El campo {$label} debe ser posterior a {$param}.";
                }
                break;

            case 'date_before':
                if ($value !== '' && $param && strtotime($value) >= strtotime($param)) {
                    return "El campo {$label} debe ser anterior a {$param}.";
                }
                break;

            case 'min':
                if ($value !== '' && is_numeric($value) && (float)$value < (float)$param) {
                    return "El campo {$label} debe ser mayor o igual a {$param}.";
                }
                break;

            case 'max':
                if ($value !== '' && is_numeric($value) && (float)$value > (float)$param) {
                    return "El campo {$label} debe ser menor o igual a {$param}.";
                }
                break;

            case 'min_len':
                if ($value !== '' && mb_strlen((string)$value) < (int)$param) {
                    return "El campo {$label} debe tener al menos {$param} caracteres.";
                }
                break;

            case 'max_len':
                if ($value !== '' && mb_strlen((string)$value) > (int)$param) {
                    return "El campo {$label} no debe exceder {$param} caracteres.";
                }
                break;

            case 'in':
                if ($value !== '' && $param) {
                    $allowed = explode(',', $param);
                    if (!in_array($value, $allowed, true)) {
                        return "El campo {$label} tiene un valor no permitido.";
                    }
                }
                break;

            case 'unique':
                // unique:table,column,except_id (opcional)
                if ($value !== '' && $param) {
                    [$table, $column, $exceptId] = array_pad(explode(',', $param), 3, null);
                    if (self::existsInDb($table, $column, $value, $exceptId)) {
                        return "El {$label} ya está en uso.";
                    }
                }
                break;

            case 'file':
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                    return "El campo {$label} requiere un archivo.";
                }
                break;

            case 'file_mimes':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $allowed = explode(',', $param ?? '');
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES[$field]['tmp_name']);
                    finfo_close($finfo);
                    if (!in_array($mime, $allowed, true)) {
                        return "El archivo {$label} debe ser de tipo: " . implode(', ', $allowed);
                    }
                }
                break;

            case 'file_max':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $maxKb = (int)$param * 1024;
                    if ($_FILES[$field]['size'] > $maxKb) {
                        return "El archivo {$label} no debe superar {$param} KB.";
                    }
                }
                break;

            case 'boolean':
                if (!in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    return "El campo {$label} debe ser verdadero o falso.";
                }
                break;

            case 'regex':
                if ($value !== '' && $param && !preg_match($param, $value)) {
                    return "El campo {$label} tiene un formato inválido.";
                }
                break;
        }
        return null;
    }

    private static function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private static function existsInDb(string $table, string $column, string $value, ?string $exceptId): bool
    {
        require_once __DIR__ . '/../config/database.php';
        $db = \Database::getInstance()->getConnection();
        $sql = "SELECT 1 FROM `{$table}` WHERE `{$column}` = ?";
        $params = [$value];
        if ($exceptId) {
            $sql .= " AND `id` != ?";
            $params[] = $exceptId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    private static function fieldLabel(string $field): string
    {
        $labels = [
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'tipo' => 'Tipo',
            'sexo' => 'Sexo',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'peso_actual' => 'Peso actual',
            'raza' => 'Raza',
            'color' => 'Color',
            'madre_id' => 'Madre',
            'padre_id' => 'Padre',
            'estado_venta' => 'Estado de venta',
            'precio_venta' => 'Precio de venta',
            'fecha_venta' => 'Fecha de venta',
            'comprador' => 'Comprador',
            'observaciones' => 'Observaciones',
            'vaca_id' => 'Vaca',
            'toro_id' => 'Toro',
            'fecha_servicio' => 'Fecha de servicio',
            'fecha_parto_estimada' => 'Fecha de parto estimada',
            'tipo_servicio' => 'Tipo de servicio',
            'estado' => 'Estado',
            'ternero_id' => 'Ternero',
            'fecha_parto_real' => 'Fecha de parto real',
            'fecha_pesaje' => 'Fecha de pesaje',
            'peso' => 'Peso',
            'imagenes' => 'Imágenes',
            'descripciones' => 'Descripciones',
        ];
        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    // ==================== FLASH MESSAGES ====================
    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function hasFlash(string $type): bool
    {
        return !empty($_SESSION['flash'][$type] ?? []);
    }

    // ==================== RATE LIMIT SIMPLE ====================
    /**
     * Limita peticiones por IP + acción (ej: login, crear_animal)
     * @param string $action nombre de la acción
     * @param int $maxRequests máximas peticiones
     * @param int $windowSeconds ventana de tiempo
     */
    // ==================== LOGGING SIMPLE ====================
    public static function log(string $level, string $message, array $context = []): void
    {
        $logDir = __DIR__ . '/../storage/logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . 'app-' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function logInfo(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function logDebug(string $message, array $context = []): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::log('debug', $message, $context);
        }
    }

    // ==================== RATE LIMIT SIMPLE ====================
    public static function rateLimit(string $action, int $maxRequests = 30, int $windowSeconds = 60): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "ratelimit_{$action}_{$ip}";
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Limpiar entradas antiguas
        $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => $now - $t < $windowSeconds);

        if (count($_SESSION[$key]) >= $maxRequests) {
            return false;
        }

        $_SESSION[$key][] = $now;
        return true;
    }

    // ==================== HELPERS DE ENTRADA ====================
    public static function old(string $field, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$field] ?? $default;
    }

    public static function saveOld(array $data): void
    {
        $_SESSION['old'] = $data;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['old']);
    }
}