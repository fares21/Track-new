<?php
namespace Appcore;

class Helpers {
    public static function baseUrl(array $cfg): string {
        if (!empty($cfg['BASE_URL'])) return rtrim($cfg['BASE_URL'], '/');
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = rtrim(str_replace(basename($scriptName), '', $scriptName), '/');
        // نفترض أن الجذر هو /public
        $base = rtrim($dir, '/');
        return "$protocol://$host$base";
    }

    public static function e(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function json(array $data, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function langInit(array $cfg): string {
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            if (in_array($lang, $cfg['SUPPORTED_LANGS'])) {
                $_SESSION['lang'] = $lang;
            }
        }
        return $_SESSION['lang'] ?? $cfg['DEFAULT_LANG'];
    }

    public static function t(string $key, array $repl = []) {
        $lang = $_SESSION['lang'] ?? 'ar';
        static $dict = [];
        if (!isset($dict[$lang])) {
            $path = __DIR__ . '/../../lang/' . $lang . '.php';
            $dict[$lang] = file_exists($path) ? include $path : [];
        }
        $text = $dict[$lang][$key] ?? $key;
        foreach ($repl as $k => $v) {
            $text = str_replace('{' . $k . '}', $v, $text);
        }
        return $text;
    }

    public static function asset(string $path, array $cfg): string {
        return self::baseUrl($cfg) . '/assets/' . ltrim($path, '/');
    }

    public static function url(string $path, array $cfg): string {
        return self::baseUrl($cfg) . '/' . ltrim($path, '/');
    }

    public static function now(): string {
        return date('Y-m-d H:i:s');
    }

    public static function sha256(string $data): string {
        return hash('sha256', $data);
    }

    public static function randomId(int $len = 46): string {
        return rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');
    }
}
