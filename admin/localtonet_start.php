<?php
require '../vendor/autoload.php';

use GuzzleHttp\Client;

$apiToken  = 'H8A3Steh5vGbsmDdK2iBUgEjfYlVT6zk0pqJWo4wMRPXa'; // Ganti dengan API token milikmu
$authToken = 'oGv1wpXmLYOflne5R8NCKtkzyrMAa3TF4';              // Ganti dengan Auth token

try {
    $client = new Client([
        'base_uri' => 'https://localtonet.com',
        'headers' => [
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type'  => 'application/json'
        ]
    ]);

    // 1. Ambil serverCode yang tersedia
    $serverRes = $client->get('/api/GetAvailableServers');
    $serverData = json_decode($serverRes->getBody(), true);

    if ($serverData['hasError'] || empty($serverData['result'])) {
        throw new Exception("Tidak ada serverCode yang tersedia.");
    }

    $serverCode = $serverData['result'][0]['serverCode'];

    // 2. Buat tunnel dengan serverCode yang valid
    $response = $client->post('/api/CreateHttpRandomSubDomainTunnel', [
        'json' => [
            'ip'           => '127.0.0.1',
            'port'         => 80,
            'serverCode'   => $serverCode,
            'authToken'    => $authToken,
            'protocolType' => 1 // HTTP
        ]
    ]);

    $data = json_decode($response->getBody(), true);

    if (!$data['hasError']) {
        echo "<h3>✅ Tunnel berhasil dibuat!</h3>";
        echo "<ul>";
        echo "<li><strong>URL:</strong> <a href='https://{$data['result']['url']}' target='_blank'>{$data['result']['url']}</a></li>";
        echo "<li><strong>Tunnel ID:</strong> {$data['result']['id']}</li>";
        echo "<li><strong>Server:</strong> {$serverCode}</li>";
        echo "</ul>";
    } else {
        echo "<h3>❌ Gagal membuat tunnel:</h3><pre>";
        print_r($data['errors']);
        echo "</pre>";
    }

} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "<h3>🚫 Client Error:</h3><pre>";
    echo $e->getResponse()->getBody()->getContents();
    echo "</pre>";
} catch (Exception $e) {
    echo "<h3>🚫 Terjadi kesalahan:</h3><pre>{$e->getMessage()}</pre>";
}
