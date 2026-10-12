<?php
/**
 * Simple & Robust .env Environment Loader
 * Memuat variabel lingkungan dari file .env tanpa ketergantungan library eksternal
 */

if (!function_exists('loadEnv')) {
    function loadEnv($filePath = null) {
        if ($filePath === null) {
            $filePath = __DIR__ . '/.env';
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris komentar
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                continue;
            }

            // Pisahkan KEY dan VALUE
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            // Bersihkan kutip ganda atau kutip tunggal jika ada
            $len = strlen($val);
            if ($len >= 2) {
                if (($val[0] === '"' && $val[$len - 1] === '"') ||
                    ($val[0] === "'" && $val[$len - 1] === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            // Simpan ke $_ENV, $_SERVER, dan putenv
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("{$key}={$val}");
        }

        return true;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }

        $lower = strtolower(trim((string)$val));
        if ($lower === 'true' || $lower === '(true)') return true;
        if ($lower === 'false' || $lower === '(false)') return false;
        if ($lower === 'null' || $lower === '(null)') return null;
        if ($lower === 'empty' || $lower === '(empty)') return '';

        return $val;
    }
}

// Otomatis jalankan pemuatan jika file .env ada
loadEnv();
