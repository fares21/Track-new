<?php
namespace Appcore;

class RateLimiter {
    private $dir;
    private $limit;
    private $window = 3600; // 1 ساعة

    public function __construct(string $dir, int $limit) {
        $this->dir = rtrim($dir, '/');
        $this->limit = $limit;
        if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
    }

    private function path(string $key): string {
        return $this->dir . '/' . preg_replace('/[^A-Za-z0-9:.-]/', '_', $key) . '.json';
    }

    public function hit(string $key): bool {
        $file = $this->path($key);
        $now = time();
        $data = ['start' => $now, 'count' => 0];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
            if (($data['start'] + $this->window) < $now) {
                $data = ['start' => $now, 'count' => 0];
            }
        }
        $data['count']++;
        file_put_contents($file, json_encode($data));
        return $data['count'] <= $this->limit;
    }

    public function remaining(string $key): int {
        $file = $this->path($key);
        if (!file_exists($file)) return $this->limit;
        $data = json_decode(file_get_contents($file), true) ?: ['count'=>0];
        return max(0, $this->limit - ($data['count'] ?? 0));
    }
}
