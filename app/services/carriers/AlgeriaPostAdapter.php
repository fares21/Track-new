<?php
namespace App\services\carriers;

class AlgeriaPostAdapter implements CarrierInterface {
    public function detect(string $trackingNumber): ?string {
        // E.g. رمز بريدي جزائري غالباً يبدأ بـ "RR", "EE", "CP" وينتهي بـ "DZ"
        if (preg_match('/^(RR|EE|CP)[A-Z0-9]{9}DZ$/', $trackingNumber)) {
            return 'algeriapost';
        }
        return null;
    }

    public function fetch(string $trackingNumber, ?string $carrierCode = null): array {
        // محاكاة لغياب API رسمي؛ توليد بيانات وهمية واقعية
        $seed = crc32($trackingNumber);
        $days = ($seed % 5) + 2;
        $base = time() - (5 * 86400);
        $events = [
            ['time' => date('Y-m-d H:i:s', $base), 'location' => 'Alger Centre', 'status' => 'Created'],
            ['time' => date('Y-m-d H:i:s', $base + 86400), 'location' => 'Sorting Facility', 'status' => 'Picked Up'],
            ['time' => date('Y-m-d H:i:s', $base + 3 * 86400), 'location' => 'Transit DZ', 'status' => 'In Transit'],
            ['time' => date('Y-m-d H:i:s', $base + 4 * 86400), 'location' => 'Local DZ', 'status' => 'Out for Delivery'],
        ];
        $status = 'In Transit';
        if ($seed % 3 === 0) {
            $status = 'Delivered';
            $events[] = ['time' => date('Y-m-d H:i:s', $base + 5 * 86400), 'location' => 'Recipient DZ', 'status' => 'Delivered'];
        }
        return [
            'mock' => true,
            'carrier' => 'algeriapost',
            'status' => $status,
            'eta' => date('Y-m-d', time() + $days * 86400),
            'events' => $events,
        ];
    }

    public function parseResponse(array $raw): array {
        $events = $raw['events'] ?? [];
        usort($events, fn($a,$b)=> strcmp($a['time'], $b['time']));
        return [
            'carrier_code' => 'algeriapost',
            'current_status' => $raw['status'] ?? 'In Transit',
            'expected_delivery' => $raw['eta'] ?? null,
            'events' => $events,
            'last_update' => end($events)['time'] ?? date('Y-m-d H:i:s'),
        ];
    }

    public function providerUrl(string $trackingNumber, ?string $carrierCode = null): ?string {
        return null;
    }
}
