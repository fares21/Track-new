<?php
namespace Appcore;

class View {
    public static function render(string $view, array $data = []) {
        extract($data);
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/' . $view . '.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
