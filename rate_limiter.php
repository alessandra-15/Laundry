<?php
/**
 * Rate Limiter - Prevents brute-force attacks
 * 
 * @file rate_limiter.php
 */

/**
 * Get rate limit directory (temporary folder)
 */
function get_rate_limit_dir() {
    $dir = sys_get_temp_dir() . '/laundry_ratelimit/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Check rate limit
 * 
 * @param string $key - Unique identifier (e.g., IP address + action)
 * @param int $max_attempts - Maximum allowed attempts
 * @param int $time_window - Time window in seconds
 * @return bool|string - True if allowed, error message if blocked
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 900) {
    $file = get_rate_limit_dir() . md5($key) . '.json';
    
    $data = ['attempts' => 0, 'first_attempt' => time()];
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    if (time() - $data['first_attempt'] > $time_window) {
        $data = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    
    if ($data['attempts'] > $max_attempts) {
        $remaining = $time_window - (time() - $data['first_attempt']);
        $minutes = ceil($remaining / 60);
        return "Too many attempts. Please try again in {$minutes} minute(s).";
    }
    
    return true;
}

/**
 * Clear rate limit (pag successful login)
 */
function clear_rate_limit($key) {
    $file = get_rate_limit_dir() . md5($key) . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Get remaining attempts
 */
function get_remaining_attempts($key, $max_attempts = 5) {
    $file = get_rate_limit_dir() . md5($key) . '.json';
    if (!file_exists($file)) {
        return $max_attempts;
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) return $max_attempts;
    return max(0, $max_attempts - $data['attempts']);
}
?>