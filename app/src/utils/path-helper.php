<?php
/**
 * Helper functions for handling paths consistently across environments
 */

if (!function_exists('normalize_path')) {
    /**
     * Normalize a path to use the correct directory separators
     * 
     * @param string $path The path to normalize
     * @return string The normalized path
     */
    function normalize_path(string $path): string {
        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);
        // Remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        return $path;
    }
}

if (!function_exists('ensure_path_exists')) {
    /**
     * Ensure a directory path exists, creating it if needed
     * 
     * @param string $path Directory path to check/create
     * @param int $permissions Permissions to set if creating directory
     * @return bool True if directory exists or was created
     */
    function ensure_path_exists(string $path, int $permissions = 0755): bool {
        $path = normalize_path($path);
        
        if (!file_exists($path)) {
            return mkdir($path, $permissions, true);
        }
        
        return true;
    }
}

if (!function_exists('get_full_path')) {
    /**
     * Get the full path from a relative path
     * 
     * @param string $path The relative path
     * @param string $basePath The base path
     * @return string The full path
     */
    function get_full_path(string $path, string $basePath = null): string {
        if ($basePath === null) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        }
        
        return normalize_path($basePath . '/' . $path);
    }
}
