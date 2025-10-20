<?php
use App\core\{Helpers,Security};
?>
<section>
  <h2><?=Helpers::t('my_tracks')?></h2>
  <?php if (empty($list)): ?>
    <div class="empty"><?=Helpers::t('my_empty')?></div>
  <?php else: ?>
    <div class="list">
      <?php foreach ($list as $row): $meta = json_decode($row['metadata'] ?? '{}', true) ?: []; ?>
        <div class="list-item">
          <div>
            <a class="num" href="<?=Helpers::url('track/' . urlencode($row['track_number']), $cfg)?>"><?=Helpers::e($row['track_number'])?></a>
            <div class="muted tiny"><?=Helpers::t('carrier')?>: <?=Helpers::e(strtoupper($row['carrier_code']))?></div>
          </div>
          <div class="right">
            <span class="badge"><?=Helpers::e($row['last_status'] ?? '')?></span>
            <?php if (!empty($meta['expected_delivery'])): ?>
              <span class="eta tiny"><?=Helpers::t('eta')?>: <?=Helpers::e($meta['expected_delivery'])?></span>
            <?php endif; ?>
            <a class="btn danger" href="<?=Helpers::url('my?action=delete&id=' . (int)$row['link_id'] . '&csrf=' . urlencode(Security::csrfToken()), $cfg)?>"><?=Helpers::t('delete')?></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
