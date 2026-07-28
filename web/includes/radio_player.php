<div class="radio-widget" id="radio-widget" data-src="<?= h(setting('radio_stream_url')) ?>">
  <button class="radio-toggle" id="radio-toggle" aria-label="Reproducir radio" aria-pressed="false">
    <span class="radio-eq" aria-hidden="true"><i></i><i></i><i></i></span>
  </button>
  <div class="radio-info">
    <span class="radio-label">EN VIVO</span>
    <span class="radio-station"><?= h(setting('radio_station_name')) ?></span>
  </div>
  <button class="radio-mute" id="radio-mute" aria-label="Silenciar">🔊</button>
  <audio id="radio-audio" preload="none" src="<?= h(setting('radio_stream_url')) ?>"></audio>
</div>
