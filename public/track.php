<?php
session_start();
$cfg = include __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/core/Autoloader.php';

use App\core\Helpers;
use App\controllers\TrackController;

Helpers::langInit($cfg);

$controller = new TrackController($cfg);
$controller->track();
