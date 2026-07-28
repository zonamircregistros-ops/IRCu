<?php
declare(strict_types=1);

/**
 * Monitoreo de errores propio (sin servicios de terceros): registra
 * errores PHP y excepciones no capturadas en la tabla error_log para
 * poder revisarlos desde /admin/errors.php.
 */
function log_error_to_db(string $severity, string $message, ?string $file, ?int $line): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO error_log (severity, message, file, line, request_uri)
             VALUES (:severity, :message, :file, :line, :uri)'
        );
        $stmt->execute([
            'severity' => $severity,
            'message' => substr($message, 0, 500),
            'file' => $file,
            'line' => $line,
            'uri' => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
        ]);
    } catch (\Throwable $e) {
        // Si el logger falla (p.ej. sin DB), no hacemos nada más: no
        // queremos que el logger de errores genere un loop de errores.
    }
}

set_error_handler(function (int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
    $severityNames = [
        E_WARNING => 'warning', E_NOTICE => 'notice', E_DEPRECATED => 'deprecated',
        E_USER_WARNING => 'warning', E_USER_NOTICE => 'notice', E_USER_DEPRECATED => 'deprecated',
    ];
    log_error_to_db($severityNames[$errno] ?? 'error', $errstr, $errfile, $errline);
    return false;
});

set_exception_handler(function (\Throwable $e): void {
    log_error_to_db('exception', $e->getMessage() . ' (' . get_class($e) . ')', $e->getFile(), $e->getLine());
    error_log('[uncaught] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Ocurrió un error inesperado. Ya quedó registrado y lo vamos a revisar.';
});
