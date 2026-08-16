<?php $assignedSchedulesUrl = htmlspecialchars($basePath . '/collector/dashboard', ENT_QUOTES, 'UTF-8'); ?>
<section class="collector-selection-required" aria-live="polite">
    <strong>Collector setup is no longer required.</strong>
    <p>Your schedules are loaded from the signed-in collector assignment.</p>
    <a class="primary-btn" href="<?= $assignedSchedulesUrl ?>">Open Assigned Schedules</a>
</section>

<script>
    window.location.replace(<?= json_encode($basePath . '/collector/dashboard', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
