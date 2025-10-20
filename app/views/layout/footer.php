<?php
use App\core\Helpers;
?>
</main>
<footer class="container footer">
  <p>&copy; <?=date('Y')?> TrackDZ</p>
</footer>
<script>
  window.TRACKDZ = {
    baseUrl: "<?=Helpers::baseUrl($cfg)?>",
    i18n: {
      refreshing: "<?=Helpers::t('refreshing')?>"
    }
  }
</script>
<script src="<?=Helpers::asset('app.js', $cfg)?>"></script>
</body>
</html>
