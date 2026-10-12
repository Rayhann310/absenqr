<?php
/**
 * Konfigurasi & Helper Deteksi IP dan Jaringan Kantor
 * Digunakan untuk memvalidasi akses absensi dari jaringan kantor
 */

if (!function_exists('getClientIP')) {
    function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy / Load Balancer
            'HTTP_X_REAL_IP',        // Nginx / Reverse Proxy
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'            // Default PHP
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('getNetworkSetting')) {
    function getNetworkSetting() {
        $file = __DIR__ . '/network_setting.json';
        $default = [
            'filter_ip' => false,
            'allowed_ips' => []
        ];

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return array_merge($default, $data);
            }
        }

        return $default;
    }
}

if (!function_exists('saveNetworkSetting')) {
    function saveNetworkSetting($filter_ip, $allowed_ips) {
        $file = __DIR__ . '/network_setting.json';
        if (!is_array($allowed_ips)) {
            $allowed_ips = preg_split('/[\r\n,]+/', (string)$allowed_ips);
        }

        $cleaned_ips = [];
        foreach ($allowed_ips as $ip) {
            $ip = trim($ip);
            if (!empty($ip)) {
                $cleaned_ips[] = $ip;
            }
        }

        $data = [
            'filter_ip' => (bool)$filter_ip,
            'allowed_ips' => array_values(array_unique($cleaned_ips)),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }
}

if (!function_exists('isClientAllowed')) {
    function isClientAllowed() {
        $setting = getNetworkSetting();
        // Jika filter dinonaktifkan, izinkan semua (aman dan tidak lock-out)
        if (empty($setting['filter_ip'])) {
            return true;
        }

        $allowed = (array) ($setting['allowed_ips'] ?? []);
        // Jika filter aktif tapi daftar IP masih kosong, tetap izinkan agar sistem tidak terkunci
        if (empty($allowed)) {
            return true;
        }

        $clientIp = getClientIP();
        foreach ($allowed as $ip) {
            $ip = trim($ip);
            if ($ip === $clientIp) {
                return true;
            }
            // Dukungan subnet CIDR (misal 192.168.1.0/24)
            if (strpos($ip, '/') !== false && function_exists('ipCIDRCheck') && ipCIDRCheck($clientIp, $ip)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('ipCIDRCheck')) {
    function ipCIDRCheck($ip, $cidr) {
        if (strpos($cidr, '/') === false) return false;
        list($subnet, $mask) = explode('/', $cidr);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        return ($ipLong & $maskLong) == ($subnetLong & $maskLong);
    }
}
