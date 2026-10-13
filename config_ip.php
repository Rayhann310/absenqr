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
            'filter_ip'   => false,
            'allowed_ips' => [],
            'qr_base_url' => ''
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
    function saveNetworkSetting($filter_ip, $allowed_ips, $qr_base_url = null) {
        $file = __DIR__ . '/network_setting.json';
        $existing = getNetworkSetting();

        if ($allowed_ips !== null) {
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
            $existing['allowed_ips'] = array_values(array_unique($cleaned_ips));
        }

        if ($filter_ip !== null) {
            $existing['filter_ip'] = (bool)$filter_ip;
        }

        if ($qr_base_url !== null) {
            $existing['qr_base_url'] = trim($qr_base_url);
        }

        $existing['updated_at'] = date('Y-m-d H:i:s');

        return file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT)) !== false;
    }
}

if (!function_exists('isLocalhostHost')) {
    function isLocalhostHost($host) {
        if (empty($host)) return true;
        $cleanHost = preg_replace('/:\d+$/', '', strtolower(trim($host)));
        return in_array($cleanHost, ['localhost', '127.0.0.1', '::1', '[::1]']);
    }
}

if (!function_exists('isLocalhostUrl')) {
    function isLocalhostUrl($url) {
        if (empty($url)) return true;
        $parsed = parse_url(trim($url));
        $host = $parsed['host'] ?? '';
        return isLocalhostHost($host);
    }
}

if (!function_exists('getPrimaryLocalIP')) {
    /**
     * Mendapatkan IP address lokal (Wi-Fi/LAN) komputer yang terhubung ke router.
     * Menggunakan socket UDP ringkas tanpa mengirim data ke internet.
     */
    function getPrimaryLocalIP() {
        // Coba socket UDP via stream_socket_client (otomatis memilih adapter default gateway/Wi-Fi)
        $stream = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);
        if ($stream) {
            $name = @stream_socket_get_name($stream, false);
            @fclose($stream);
            if ($name) {
                $ip = explode(':', $name)[0];
                if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '127.0.0.1') {
                    return $ip;
                }
            }
        }

        // Fallback untuk Windows (ipconfig)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec('ipconfig', $lines);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (preg_match('/IPv4 Address[ .]*: ([\d\.]+)/i', $line, $m)) {
                        $ip = trim($m[1]);
                        if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254.')) {
                            return $ip;
                        }
                    }
                }
            }
        }

        return null;
    }
}

if (!function_exists('getQRBaseURL')) {
    /**
     * Mendapatkan URL dasar untuk QR Code absensi agar selalu valid dan
     * bisa diakses oleh HP karyawan saat discan (tidak pernah localhost).
     */
    function getQRBaseURL() {
        // 1. Cek setting manual dari Pengaturan Sistem (network_setting.json)
        $setting = getNetworkSetting();
        if (!empty($setting['qr_base_url'])) {
            $customUrl = trim($setting['qr_base_url']);
            if (!isLocalhostUrl($customUrl)) {
                return rtrim($customUrl, '/');
            }
        }

        // 2. Cek konfigurasi APP_URL dari file .env jika diset
        if (function_exists('env')) {
            $envUrl = env('APP_URL');
            if (!empty($envUrl) && !isLocalhostUrl($envUrl)) {
                return rtrim($envUrl, '/');
            }
        }

        // 3. Deteksi host saat ini dari request web
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Hitung subfolder aplikasi secara akurat (misal /absenqr)
        $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
        $appDir  = rtrim(str_replace('\\', '/', __DIR__), '/');
        $baseDir = '';
        if (!empty($docRoot) && strpos($appDir, $docRoot) === 0) {
            $baseDir = substr($appDir, strlen($docRoot));
        } else {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $baseDir = rtrim(dirname($scriptDir), '/\\');
        }
        if ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') {
            $baseDir = '';
        }

        // Jika request dibuka BUKAN dari localhost (misal via IP Wi-Fi atau domain publik)
        if (!empty($host) && !isLocalhostHost($host)) {
            return rtrim($protocol . $host . $baseDir, '/');
        }

        // 4. Jika dibuka dari localhost (laptop/layar kantor):
        // Prioritaskan IP Wi-Fi komputer agar HP di jaringan Wi-Fi yang sama dapat mengakses sistem
        $localIP = getPrimaryLocalIP();
        if (!empty($localIP)) {
            $port = $_SERVER['SERVER_PORT'] ?? 80;
            $portSuffix = ($port != 80 && $port != 443) ? ":$port" : "";
            return "http://" . $localIP . $portSuffix . $baseDir;
        }

        // 5. Fallback ke domain publik resmi
        return 'https://pssikabbogor.my.id' . $baseDir;
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
