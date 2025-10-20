<?php
namespace App\services\carriers;

class AfterShipAdapter implements CarrierInterface {
    private $apiKey;

    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey;
    }

    public function detect(string $trackingNumber): ?string {
        if (empty($this->apiKey)) return null;
        $url = 'https://api.aftership.com/v4/couriers/detect';
        $res = $this->request($url, ['tracking' => ['tracking_number' => $trackingNumber]]);
        $list = $res['data']['couriers'] ?? [];
        return $list[0]['slug'] ?? null;
    }

    public function fetch(string $trackingNumber, ?string $carrierCode = null): array {
        if (empty($this->apiKey)) return ['error' => 'no_api_key'];
        // إنشاء تتبع إذا لم يكن موجوداً
        $createUrl = 'https://api.aftership.com/v4/trackings';
        $payload = ['tracking' => ['tracking_number' => $trackingNumber, 'slug' => $carrierCode]];
        $this->request($createUrl, $payload, 'POST', [201, 200, 409]);
        // جلب الحالة
        $slugPart = $carrierCode ? $carrierCode . '/' : '';
        $getUrl = 'https://api.aftership.com/v4/trackings/' . $slugPart . $trackingNumber;
        return $this->request($getUrl, null, 'GET');
    }

    public function parseResponse(array $raw): array {
        $t = $raw['data']['tracking'] ?? [];
        $checkpoints = $t['checkpoints'] ?? [];
        $events = [];
        foreach ($checkpoints as $cp) {
            $events[] = [
                'time' => isset($cp['checkpoint_time']) ? date('Y-m-d H:i:s', strtotime($cp['checkpoint_time'])) : null,
                'location' => trim(($cp['city'] ?? '') . (isset($cp['country_name']) ? ', ' . $cp['country_name'] : ''), ', '),
                'status' => $cp['message'] ?? ($cp['tag'] ?? 'Update'),
            ];
        }
        usort($events, fn($a,$b)=> strcmp($a['time'] ?? '', $b['time'] ?? ''));
        $status = $t['tag'] ?? ($t['subtag'] ?? ($t['status'] ?? 'In Transit'));
        $expected = $t['expected_delivery'] ?? null;
        $carrier = $t['slug'] ?? null;
        $lastUpdate = $t['updated_at'] ?? ($events ? end($events)['time'] : date('Y-m-d H:i:s'));
        return [
            'carrier_code' => $carrier,
            'current_status' => $status,
            'expected_delivery' => $expected ? date('Y-m-d', strtotime($expected)) : null,
            'events' => array_values(array_filter($events, fn($e)=>!empty($e['time']))),
            'last_update' => date('Y-m-d H:i:s', strtotime($lastUpdate)),
        ];
    }

    public function providerUrl(string $trackingNumber, ?string $carrierCode = null): ?string {
        return 'https://track.aftership.com/' . ($carrierCode ? $carrierCode . '/' : '') . $trackingNumber;
    }

    private function request(string $url, ?array $payload = null, string $method = 'POST', array $okCodes = [200,201]) {
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'aftership-api-key: ' . $this->apiKey
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!in_array($code, $okCodes)) {
            return ['error' => 'api_error', 'code' => $code, 'raw' => $resp];
        }
        return json_decode($resp, true) ?: [];
    }
}
