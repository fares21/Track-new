<?php
namespace App\controllers;

use App\core\{View,Helpers,Security,Cache,RateLimiter};
use App\models\TrackModel;
use App\services\carriers\{CarrierInterface,AfterShipAdapter,SeventeenTrackAdapter,AlgeriaPostAdapter};

class TrackController {
    private $cfg;
    private $model;
    private $cache;
    private $rate;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
        $this->model = new TrackModel($cfg);
        $this->cache = new Cache(__DIR__ . '/../../storage/cache', $cfg['CACHE_TTL_SECONDS']);
        $this->rate = new RateLimiter(__DIR__ . '/../../storage/ratelimit', $cfg['RATE_LIMIT_PER_HOUR']);
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = Helpers::randomId(18);
        }
    }

    private function carriers(): array {
        return [
            'aftership' => new AfterShipAdapter($this->cfg['AFTERSHIP_API_KEY']),
            '17track' => new SeventeenTrackAdapter($this->cfg['SEVENTEENTRACK_API_KEY']),
            'algeriapost' => new AlgeriaPostAdapter(),
        ];
    }

    private function heuristicDetect(string $num): ?string {
        // أمثلة بسيطة: UPS يبدأ بـ 1Z...
        if (preg_match('/^1Z[0-9A-Z]{16}$/', $num)) return 'ups';
        // DHL يبدأ بـ JVGL ... أو أرقام 10-11
        if (preg_match('/^[0-9]{10,11}$/', $num)) return 'dhl';
        // بريد الجزائر
        if (preg_match('/^(RR|EE|CP)[A-Z0-9]{9}DZ$/', $num)) return 'algeriapost';
        return null;
    }

    private function unifiedFetch(string $num, ?string $prefCarrier = null): array {
        $cacheKey = 'track_' . $num;
        $cached = $this->cache->get($cacheKey);
        if ($cached) return $cached;

        $detected = $prefCarrier ?: $this->heuristicDetect($num);

        // جرب AfterShip للكشف إن توفر
        if (!$detected && !empty($this->cfg['AFTERSHIP_API_KEY'])) {
            $detected = $this->carriers()['aftership']->detect($num) ?: null;
        }

        // جلب عبر أفضل مزود متاح
        $raw = null;
        $provider = null;
        $carrierCode = $detected;

        // بريد الجزائر محاكاة
        if ($detected === 'algeriapost') {
            $provider = 'algeriapost';
            $raw = $this->carriers()['algeriapost']->fetch($num, 'algeriapost');
            $parsed = $this->carriers()['algeriapost']->parseResponse($raw);
        } else {
            // حاول AfterShip
            if (!empty($this->cfg['AFTERSHIP_API_KEY'])) {
                $provider = 'aftership';
                $raw = $this->carriers()['aftership']->fetch($num, $carrierCode);
                if (empty($raw['error'])) {
                    $parsed = $this->carriers()['aftership']->parseResponse($raw);
                }
            }
            // احتياطي 17Track
            if (empty($parsed) && !empty($this->cfg['SEVENTEENTRACK_API_KEY'])) {
                $provider = '17track';
                $raw = $this->carriers()['17track']->fetch($num, $carrierCode);
                if (empty($raw['error'])) {
                    $parsed = $this->carriers()['17track']->parseResponse($raw);
                }
            }
        }

        // وضع العرض التجريبي
        if (empty($parsed) && $this->cfg['DEMO_MODE']) {
            if ($num === '1Z12345E1512345676') {
                $parsed = [
                    'carrier_code' => 'ups',
                    'current_status' => 'In Transit',
                    'expected_delivery' => date('Y-m-d', time() + 2*86400),
                    'events' => [
                        ['time'=>date('Y-m-d H:i:s', time()-5*86400), 'location'=>'Origin Facility', 'status'=>'Created'],
                        ['time'=>date('Y-m-d H:i:s', time()-4*86400), 'location'=>'Warehouse A', 'status'=>'Picked Up'],
                        ['time'=>date('Y-m-d H:i:s', time()-2*86400), 'location'=>'Transit Hub', 'status'=>'In Transit'],
                        ['time'=>date('Y-m-d H:i:s', time()-1*86400), 'location'=>'Local Center', 'status'=>'Out for Delivery'],
                    ],
                    'last_update' => date('Y-m-d H:i:s', time()-3600),
                ];
                $provider = 'demo';
            } else {
                // fallback بسيط
                $parsed = [
                    'carrier_code' => $detected ?: 'unknown',
                    'current_status' => 'In Transit',
                    'expected_delivery' => null,
                    'events' => [
                        ['time'=>date('Y-m-d H:i:s', time()-2*86400), 'location'=>'Origin', 'status'=>'Created'],
                        ['time'=>date('Y-m-d H:i:s', time()-1*86400), 'location'=>'Hub', 'status'=>'In Transit'],
                    ],
                    'last_update' => date('Y-m-d H:i:s'),
                ];
                $provider = 'demo';
            }
        }

        $result = [
            'provider' => $provider,
            'carrier_code' => $parsed['carrier_code'] ?? ($carrierCode ?: 'unknown'),
            'current_status' => $parsed['current_status'] ?? 'In Transit',
            'expected_delivery' => $parsed['expected_delivery'] ?? null,
            'events' => $parsed['events'] ?? [],
            'last_update' => $parsed['last_update'] ?? date('Y-m-d H:i:s'),
            'provider_url' => $this->providerUrl($provider, $num, $carrierCode),
        ];

        $this->cache->set($cacheKey, $result);
        return $result;
    }

    private function providerUrl(?string $provider, string $num, ?string $carrierCode): ?string {
        if ($provider === 'aftership') return $this->carriers()['aftership']->providerUrl($num, $carrierCode);
        if ($provider === '17track') return $this->carriers()['17track']->providerUrl($num, $carrierCode);
        return null;
    }

    private function computeProof(array $data): array {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $hash = Helpers::sha256($payload);
        $ipfs = 'ipfs://'.substr($hash, 0, 46); // محاكاة
        return ['hash' => $hash, 'ipfs' => $ipfs, 'generated_at' => Helpers::now()];
    }

    public function home() {
        View::render('home', [
            'title' => Helpers::t('title_home'),
            'csrf' => Security::csrfToken(),
            'cfg' => $this->cfg,
        ]);
    }

    public function track() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'track_' . $ip;
        if (!$this->rate->hit($key)) {
            Helpers::json(['ok'=>false, 'error'=>'rate_limited', 'remaining'=>$this->rate->remaining($key)], 429);
        }

        $num = trim($_GET['num'] ?? $_POST['num'] ?? '');
        $num = preg_replace('/\s+/', '', $num);
        $force = isset($_GET['force']) ? true : false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Security::verifyCsrf()) {
            Helpers::json(['ok'=>false, 'error'=>'invalid_csrf'], 400);
        }

        if ($num === '') {
            View::render('home', [
                'title' => Helpers::t('title_home'),
                'error' => Helpers::t('err_required'),
                'csrf' => Security::csrfToken(),
                'cfg' => $this->cfg,
            ]);
            return;
        }

        // جلب أو إنشاء العنصر
        $item = $this->model->findByNumber($num);
        $detectedCarrier = $item['carrier_code'] ?? ($this->heuristicDetect($num) ?: 'unknown');

        // تحديث إذا قديم
        $stale = true;
        if ($item && !$force) {
            $last = strtotime($item['last_update_at'] ?? '1970-01-01');
            $stale = $last < time() - ($this->cfg['REFRESH_INTERVAL_MINUTES'] * 60);
        }

        if (!$item) {
            $data = $this->unifiedFetch($num, $detectedCarrier);
            $meta = [
                'expected_delivery' => $data['expected_delivery'],
                'provider_url' => $data['provider_url'],
            ];
            if ($this->cfg['WEB3_PROOF']) {
                $meta['proof'] = $this->computeProof($data);
            }
            $itemId = $this->model->createItem($num, $data['carrier_code'], [
                'last_status' => $data['current_status'],
                'last_update_at' => $data['last_update'],
                ...$meta
            ]);
            $this->model->replaceEvents($itemId, $data['events']);
            $item = $this->model->findByNumber($num);
        } elseif ($stale) {
            $data = $this->unifiedFetch($num, $detectedCarrier);
            $meta = json_decode($item['metadata'] ?? '{}', true) ?: [];
            $meta['expected_delivery'] = $data['expected_delivery'];
            $meta['provider_url'] = $data['provider_url'];
            if ($this->cfg['WEB3_PROOF']) {
                $meta['proof'] = $this->computeProof($data);
            }
            $this->model->updateItem((int)$item['id'], $data['current_status'], $data['last_update'], $meta);
            $this->model->replaceEvents((int)$item['id'], $data['events']);
            $item = $this->model->findByNumber($num);
        }

        // أضف إلى قائمة المستخدم
        $this->model->addToUserList($_SESSION['user_id'], (int)$item['id']);

        // AJAX تحديث
        if (isset($_GET['ajax'])) {
            $events = $this->model->getEvents((int)$item['id']);
            Helpers::json([
                'ok' => true,
                'number' => $item['track_number'],
                'carrier' => $item['carrier_code'],
                'current_status' => $item['last_status'],
                'last_update' => $item['last_update_at'],
                'expected_delivery' => (json_decode($item['metadata'] ?? '{}', true)['expected_delivery'] ?? null),
                'events' => $events,
            ]);
        }

        $events = $this->model->getEvents((int)$item['id']);
        View::render('track', [
            'title' => Helpers::t('title_track', ['num'=>$item['track_number']]),
            'item' => $item,
            'events' => $events,
            'cfg' => $this->cfg,
        ]);
    }

    public function myList() {
        // حذف من القائمة
        if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
            if (!Security::verifyCsrf()) {
                Helpers::json(['ok'=>false, 'error'=>'invalid_csrf'], 400);
            }
            $this->model->removeFromUserList($_SESSION['user_id'], (int)$_GET['id']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $list = $this->model->getUserList($_SESSION['user_id']);
        View::render('my', [
            'title' => Helpers::t('title_my'),
            'list' => $list,
            'cfg' => $this->cfg,
        ]);
    }

    public function cron() {
        $secret = $_GET['secret'] ?? '';
        if ($secret !== $this->cfg['CRON_SECRET']) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $items = $this->model->dueForRefresh($this->cfg['REFRESH_INTERVAL_MINUTES']);
        $updated = 0;
        foreach ($items as $item) {
            $data = $this->unifiedFetch($item['track_number'], $item['carrier_code']);
            $meta = json_decode($item['metadata'] ?? '{}', true) ?: [];
            $meta['expected_delivery'] = $data['expected_delivery'];
            $meta['provider_url'] = $data['provider_url'];
            if ($this->cfg['WEB3_PROOF']) {
                $meta['proof'] = $this->computeProof($data);
            }
            $this->model->updateItem((int)$item['id'], $data['current_status'], $data['last_update'], $meta);
            $this->model->replaceEvents((int)$item['id'], $data['events']);
            $updated++;
        }
        Helpers::json(['ok'=>true, 'updated'=>$updated]);
    }

    public function webhook() {
        // يدعم AfterShip و 17Track إن توفر payload
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?: [];
        $carrier = $_GET['carrier'] ?? ($_SERVER['HTTP_X_CARRIER'] ?? '');
        $trackingNumber = $json['data']['tracking']['tracking_number'] ?? ($json['data'][0]['number'] ?? null);

        if (!$trackingNumber) {
            Helpers::json(['ok'=>false, 'error'=>'no_tracking_number'], 400);
        }

        $data = null;
        if (stripos($carrier, 'aftership') !== false) {
            $parsed = (new AfterShipAdapter($this->cfg['AFTERSHIP_API_KEY']))->parseResponse($json);
            $data = $parsed;
        } elseif (stripos($carrier, '17track') !== false) {
            $parsed = (new SeventeenTrackAdapter($this->cfg['SEVENTEENTRACK_API_KEY']))->parseResponse($json);
            $data = $parsed;
        }

        if (!$data) {
            Helpers::json(['ok'=>false, 'error'=>'unsupported_carrier'], 400);
        }

        $item = $this->model->findByNumber($trackingNumber);
        if (!$item) {
            $itemId = $this->model->createItem($trackingNumber, $data['carrier_code'], [
                'last_status' => $data['current_status'],
                'last_update_at' => $data['last_update'],
                'expected_delivery' => $data['expected_delivery'],
            ]);
        } else {
            $meta = json_decode($item['metadata'] ?? '{}', true) ?: [];
            $meta['expected_delivery'] = $data['expected_delivery'];
            if ($this->cfg['WEB3_PROOF']) {
                $meta['proof'] = $this->computeProof($data);
            }
            $this->model->updateItem((int)$item['id'], $data['current_status'], $data['last_update'], $meta);
            $itemId = (int)$item['id'];
        }
        $this->model->replaceEvents($itemId, $data['events']);
        Helpers::json(['ok'=>true]);
    }
}
