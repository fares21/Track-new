<?php
use App\core\Helpers;
if (empty($events)): ?>
  <div class="empty"><?=Helpers::t('no_events')?></div>
<?php else: ?>
  <ul class="v-timeline">
    <?php foreach ($events as $ev): ?>
      <li>
        <div class="dot"></div>
        <div class="content">
          <div class="row">
            <span class="time"><?=Helpers::e(date('Y-m-d H:i', strtotime($ev['event_time'])))?></span>
            <span class="status"><?=Helpers::e($ev['status_text'])?></span>
          </div>
          <div class="location"><?=Helpers::e($ev['location'])?></div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
