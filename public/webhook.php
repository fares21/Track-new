<?php
// POST /webhook.php?carrier=aftership|17track
session_start();
$cfg = include __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/core/Autoloader.php';

use App\controllers\TrackController;

$controller = new TrackController($cfg);
$controller->webhook();
