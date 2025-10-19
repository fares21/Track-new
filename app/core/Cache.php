<?php
namespace Appcore;

class Cache {
    private $dir;
    private $ttl;

    public function __construct(string $dir, int $ttl = 300) {
        $this->dir = rtrim($dir, '/');
        $this->ttl = $ttl;
        if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
    }

    private function path(string $key): string {
        return $this->dir . '/' . preg_replace('/[^A-Za-z0-9-_]/', '_', $key) . '.cache.json';
        }

    public function get(string $key) {
        $file = $this->path($key);
        if (!file_exists($file)) return null;
        if (filemtime($file) + $this->ttl < time()) return null;
        $data = file_get_contents($file);
        return json_decode($data, true);
    }

    public function set(string $key, $value): bool {
        $file = $this->path($key);
        return (bool)file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    public function delete(string $key): void {
        $file = $this->path($key);
        if (file_exists($file)) @unlink($file);
    }
}
