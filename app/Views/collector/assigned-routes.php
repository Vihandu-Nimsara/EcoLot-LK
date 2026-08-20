<?php
declare(strict_types=1);
?>

<section class="collector-workspace" data-collector-page="schedules">
    <header class="collector-page-heading">
        <div>
            <span class="collector-eyebrow">Collection workspace</span>
            <h1>Assigned Schedules</h1>
            <p>Review your assigned schedules, open each approved request, and record the actual collection details.</p>
        </div>

        <div class="collector-heading-summary" aria-live="polite">
            <strong id="collector-schedule-count">0 schedules</strong>
            <span id="collector-next-schedule">Next: —</span>
        </div>
    </header>

    <div id="collector-feedback" class="collector-feedback" role="status" aria-live="polite" hidden></div>

    <section aria-labelledby="schedule-list-title">
        <h2 id="schedule-list-title" class="sr-only">Assigned collection schedules</h2>
        <div id="collector-schedule-grid" class="collector-schedule-grid"></div>
        <div id="collector-schedule-empty" class="collector-empty-state" hidden>
            <strong>No assigned schedules</strong>
            <span>There are no active collection schedules assigned to this collector.</span>
        </div>
    </section>

    <section id="collector-selected-schedule" class="collector-selected-schedule" aria-live="polite"></section>
</section>

<?php require __DIR__ . '/partials/request-details-modal.php'; ?>