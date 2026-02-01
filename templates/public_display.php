<!DOCTYPE html>
<html lang="<?php p($_['lang'] ?? 'de'); ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Signage</title>
  <?php
    $urlGen = \OC::$server->getURLGenerator();
    $nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
  ?>
  <link rel="stylesheet" href="<?php p($urlGen->linkTo('digitalsignage', 'css/display.css')); ?>">
  <style>
    :root {
      --primary-blue: <?php p($_['color_primary'] ?? '#0066cc'); ?>;
      --bg-primary: <?php p($_['color_bg'] ?? '#f8f9fa'); ?>;
      --text-primary: <?php p($_['color_text'] ?? '#2c3e50'); ?>;
      --gradient-start: <?php p($_['color_gradient_start'] ?? '#0066cc'); ?>;
      --gradient-end: <?php p($_['color_gradient_end'] ?? '#3399ff'); ?>;
    }
  </style>
</head>
<body data-is-public="<?php p(isset($_['token']) ? 'true' : 'false'); ?>"
      data-public-token="<?php p($_['token'] ?? ''); ?>"
      data-base-url="<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/')); ?>">
  <?php if ((!isset($_['show_display_name']) || $_['show_display_name'] === '1')): ?>
  <div class="display-header">
    <h1 id="display-title"><?php p(empty($_['display_name']) ? 'Digital Signage' : $_['display_name']); ?></h1>
    <button id="fullscreen-btn" class="fullscreen-button" title="Vollbild">⛶</button>
  </div>
  <?php else: ?>
  <button id="fullscreen-btn" class="fullscreen-button fullscreen-no-header" title="Vollbild">⛶</button>
  <?php endif; ?>

  <div class="container">
    <div class="left">
      <div class="slideshow-box">
        <div class="slideshow loading" id="slideshow"></div>
      </div>
      <div class="weather-box">
        <div class="weather" id="weather">Loading weather...</div>
      </div>
    </div>
    <div class="right">
      <div class="calendar" id="calendar">Loading calendar...</div>
    </div>
  </div>
  <script nonce="<?php p($nonce); ?>" src="<?php p($urlGen->linkTo('digitalsignage', 'js/ical.min.js')); ?>"></script>
  <script nonce="<?php p($nonce); ?>" src="<?php p($urlGen->linkTo('digitalsignage', 'js/display.js')); ?>" defer></script>
</body>
</html>
