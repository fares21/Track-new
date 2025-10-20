<?php
use App\core\Helpers;
$meta = json_decode($item['metadata'] ?? '{}', true) ?: [];
$schema = [
  '@context' => 'https://schema.org',
  '@type' => 'ParcelDelivery',
  'trackingNumber' => $item['track_number'],
  'provider' => [
    '@type' => 'Organization',
    'name' => strtoupper($item['carrier_code'] ?? 'Unknown')
  ],
  'expectedArrivalUntil' => $meta['expected_delivery'] ?? null,
  'deliveryStatus' => $item['last_status']
];
?>
<script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?></script>

<section>
  <div class="card">
    <div class="card-header">
      <div>
        <h2><?=Helpers::t('tracking_number')?>: <?=Helpers::e($item['track_number'])?></h2>
        <p class="muted"><?=Helpers::t('carrier')?>: <?=Helpers::e(strtoupper($item['carrier_code'] ?? ''))?></p>
      </div>
      <div class="status">
        <span class="badge"><?=Helpers::e($item['last_status'])?></span>
        <?php if (!empty($meta['expected_delivery'])): ?>
          <span class="eta"><?=Helpers::t('eta')?>: <?=Helpers::e($meta['expected_delivery'])?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="actions">
      <?php if (!empty($meta['provider_url'])): ?>
        <a class="btn" target="_blank" rel="noopener" href="<?=Helpers::e($meta['provider_url'])?>"><?=Helpers::t('view_on_carrier')?></a>
      <?php endif; ?>
      <a class="btn outline" href="<?=Helpers::url('track/' . urlencode($item['track_number']) . '?force=1', $cfg)?>"><?=Helpers::t('btn_refresh')?></a>
    </div>

    <div id="timeline" data-number="<?=Helpers::e($item['track_number'])?>" class="timeline">
      <?php include __DIR__ . '/partials/timeline.php'; ?>
    </div>

    <div class="muted tiny">
      <span><?=Helpers::t('last_update')?>: <?=Helpers::e($item['last_update_at'])?></span>
      <?php if (!empty($meta['proof'])): ?>
        <span class="sep">•</span>
        <span><?=Helpers::t('proof_hash')?>: <?=Helpers::e($meta['proof']['hash'])?></span>
      <?php endif; ?>
    </div>
  </div>
</section>
