<?php
namespace App\services\carriers;

class SeventeenTrackAdapter implements CarrierInterface {
    private $apiKey;

    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey;
    }

    public function detect(string $trackingNumber): ?string {
        // 17Track عادة يكتشف تلقائيًا ضمن استعلام التتبع.
        return null;
    }

    public function fetch(string $trackingNumber, ?string $carrierCode = null): array {
        if (empty($this->apiKey)) return ['error' => 'no_api_key'];
        $url = 'https://api.17track.net/track/v2/gettrackinfo';
        $payload = [
            'data' => [
                ['number' => $trackingNumber, 'carrier' => $carrierCode]
            ]
        ];
        $resp = $this->request($url, $payload);
        return $resp;
    }

    public function parseResponse(array $raw): array {
        $d = $raw['data'][0] ?? [];
        $events = [];
        foreach (($d['track'] ?? []) as $ev) {
            $events[] = [
                'time' => isset($ev['time']) ? date('Y-m-d H:i:s', strtotime($ev['time'])) : null,
                'location' => $ev['location'] ?? '',
                'status' => $ev['status'] ?? ($ev['desc'] ?? 'Update'),
            ];
        }
        usort($events, fn($a,$b)=> strcmp($a['time'] ?? '', $b['time'] ?? ''));
        $status = $d['status'] ?? 'In Transit';
        $expected = $d['eta'] ?? null;
        $carrier = $d['carrier'] ?? null;
        $lastUpdate = $events ? end($events)['time'] : date('Y-m-d H:i:s');
        return [
            'carrier_code' => $carrier,
            'current_status' => $status,
            'expected_delivery' => $expected ? date('Y-m-d', strtotime($expected)) : null,
            'events' => array_values(array_filter($events, fn($e)=>!empty($e['time']))),
            'last_update' => $lastUpdate,
        ];
    }

    public function providerUrl(string $trackingNumber, ?string $carrierCode = null): ?string {
        return 'https://t.17track.net/en#nums=' . urlencode($trackingNumber);
    }

    private function request(string $url, array $payload) {
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            '17token: ' . $this->apiKey
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            return ['error' => 'api_error', 'code' => $code, 'raw' => $resp];
        }
        return json_decode($resp, true) ?: [];
    }
}
