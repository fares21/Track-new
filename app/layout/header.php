<?php
use App\core\Helpers;
$lang = $_SESSION['lang'] ?? 'ar';
$cfg = $cfg ?? [];
$base = Helpers::baseUrl($cfg);
$title = $title ?? $cfg['APP_NAME'];
$desc = Helpers::t('meta_description');
$canonical = $base . ($_SERVER['REQUEST_URI'] ?? '');
?>
<!doctype html>
<html lang="<?=$lang?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=Helpers::e($title)?></title>
<meta name="description" content="<?=Helpers::e($desc)?>">
<link rel="icon" href="<?=Helpers::asset('logo.svg', $cfg)?>">
<link rel="stylesheet" href="<?=Helpers::asset('style.css', $cfg)?>">
<link rel="canonical" href="<?=Helpers::e($canonical)?>">
<meta name="theme-color" content="#0ea5e9">
</head>
<body>
<header class="container header">
  <a href="<?=Helpers::url('', $cfg)?>" class="brand">
    <img src="<?=Helpers::asset('logo.svg', $cfg)?>" alt="TrackDZ" class="logo">
    <span>TrackDZ</span>
  </a>
  <nav class="nav">
    <a href="<?=Helpers::url('', $cfg)?>"><?=Helpers::t('nav_home')?></a>
    <a href="<?=Helpers::url('my', $cfg)?>"><?=Helpers::t('nav_my')?></a>
    <div class="lang">
      <a href="?lang=ar">AR</a>
      <a href="?lang=fr">FR</a>
      <a href="?lang=en">EN</a>
    </div>
  </nav>
</header>
<main class="container">
