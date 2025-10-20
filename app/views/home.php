<?php
use App\core\{Helpers,Security};
?>
<section class="hero">
  <h1><?=Helpers::t('hero_title')?></h1>
  <p class="muted"><?=Helpers::t('hero_subtitle')?></p>
  <?php if (!empty($error)): ?>
    <div class="alert error"><?=Helpers::e($error)?></div>
  <?php endif; ?>
  <form method="post" action="<?=Helpers::url('track.php', $cfg)?>" class="search-box">
    <input type="hidden" name="csrf" value="<?=Security::csrfToken()?>">
    <input type="text" name="num" placeholder="<?=Helpers::t('placeholder_number')?>" required>
    <button type="submit"><?=Helpers::t('btn_track')?></button>
  </form>
  <div class="hints">
    <small><?=Helpers::t('example')?>: 1Z12345E1512345676</small>
  </div>
</section>
