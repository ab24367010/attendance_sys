<?php
/**
 * Environment Variable Loader
 * Loads .env file and makes variables available via getenv() and $_ENV
 */

class Env {
    private static $loaded = false;

    /**
     * Load .env file from given directory
     * @param string $path Directory containing .env file
     */
    public static function load($path) {
        if (self::$loaded) {
            return;
        }

        $envFile = rtrim($path, '/') . '/.env';

        if (!file_exists($envFile)) {
            throw new Exception(".env file not found at: " . $envFile);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Set environment variable
                if (!array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable with optional default value
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string booleans to actual booleans
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        return $value;
    }
}
