<?php
/**
 * Logger Class - Logs events, errors, at security issues
 * 
 * @file logger.php
 */

class Logger {
    private static $logDir;
    
    /**
     * Initialize log directory
     */
    public static function init() {
        self::$logDir = __DIR__ . '/logs/';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        $htaccess = self::$logDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
    }
    
    public static function error($message, $context = []) { 
        self::write('ERROR', $message, $context); 
    }
    
    public static function warning($message, $context = []) { 
        self::write('WARNING', $message, $context); 
    }
    
    public static function info($message, $context = []) { 
        self::write('INFO', $message, $context); 
    }
    
    public static function security($message, $context = []) { 
        self::write('SECURITY', $message, $context); 
    }
    
    public static function login($message, $context = []) { 
        self::write('LOGIN', $message, $context); 
    }
    
    /**
     * Write log entry
     */
    private static function write($level, $message, $context) {
        self::init();
        $logFile = self::$logDir . date('Y-m-d') . ".log";
        
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100);
        $context['user_id'] = $_SESSION['customer_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['Admin_ID'] ?? null;
        $context['uri'] = $_SERVER['REQUEST_URI'] ?? '';
        
        $entry = sprintf(
            "[%s] [%s] %s | %s\n",
            date('Y-m-d H:i:s'),
            str_pad($level, 8),
            $message,
            json_encode($context, JSON_UNESCAPED_SLASHES)
        );
        
        error_log($entry, 3, $logFile);
    }
}
?>