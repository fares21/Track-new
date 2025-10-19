<?php
namespace Appcore;

class Security {
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(): bool {
        $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
        return hash_equals($_SESSION['csrf'] ?? '', $token);
    }
}
