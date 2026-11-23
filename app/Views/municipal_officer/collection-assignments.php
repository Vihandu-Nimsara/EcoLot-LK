<?php
$escape = static fn ($value) =>
    htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$assignmentStatusLabels = [
    'CLOSED' => 'Unassigned',
    'ASSIGNED' => 'Assigned',
    'IN_PROGRESS' => 'In Progress',
    'COLLECTION_SUBMITTED' => 'Submitted for Review',
    'COMPLETED' => 'Completed',
];
?>

<section class="routes-page">
    <div class="page-toolbar"><div><h1>Collection Assignments</h1><p>Assign one active collector and available vehicle after intake closes and all risk reviews are resolved.</p></div></div>
    <?php if ($notice): ?><p role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <?php foreach ($errors as $error): ?><p role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
    <p>Each resource can serve one schedule per collection date. Unassign before replacing resources. Collection work locks assignments. Overdue closed schedules remain visible for follow-up.</p>
    <section class="routes-list-card">
        <label for="assignment-search">Search campaign, area, schedule or collector</label><input type="search" id="assignment-search">
        <div class="routes-table-wrapper"><table class="routes-table">
            <thead><tr><th>Schedule / Campaign</th><th>Area / Date</th><th>Requests</th><th>Collector / Vehicle</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr data-assignment-row>
                    <td><a href="<?= $escape($basePath) ?>/officer/area-schedules/<?= (int) $schedule['schedule_id'] ?>">SCH-<?= (int) $schedule['schedule_id'] ?></a><br><?= $escape($schedule['campaign_name']) ?></td>
                    <td><?= $escape($schedule['area_name']) ?><br><?= $escape($schedule['collection_date']) ?></td>
                    <td><?= (int) $schedule['ready_requests'] ?> collectable<br><?= (int) $schedule['pending_reviews'] ?> awaiting review</td>
                    <td><?= $escape($schedule['collector_name'] ?? 'Not assigned') ?><br><?= $escape($schedule['vehicle_number'] ?? 'No vehicle') ?><br><?= $escape($schedule['assigned_at']) ?></td>              
                    <td>
                        <span
                            class="table-status"
                            data-status="<?= $escape($schedule['schedule_status']) ?>"
                        >
                            <?= $escape(
                                ($schedule['collection_status'] ?? null) === 'REJECTED'
                                    ? 'Collection rejected'
                                    : ($assignmentStatusLabels[$schedule['schedule_status']] ?? $schedule['schedule_status'])
                            ) ?>
                        </span>
                    </td>

                    <td>
                    <?php if (!$schedule['has_work'] && $schedule['schedule_status'] === 'CLOSED' && !$schedule['assignment_id'] && $schedule['ready_requests'] > 0 && !$schedule['pending_reviews']): ?>
                        <details class="table-action-panel"><summary>Assign resources</summary><form method="post" action="<?= $escape($basePath) ?>/officer/collection-assignments/<?= (int) $schedule['schedule_id'] ?>">
                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="action" value="assign">
                            <label>Collector <select name="collector" required><option value="">Select collector</option><?php foreach ($collectors as $collector): ?><option value="<?= (int) $collector['user_id'] ?>"><?= $escape($collector['full_name']) ?></option><?php endforeach; ?></select></label>
                            <label>Vehicle <select name="vehicle" required><option value="">Select vehicle</option><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int) $vehicle['vehicle_id'] ?>"><?= $escape($vehicle['vehicle_number']) ?></option><?php endforeach; ?></select></label>
                            <button type="submit" class="primary-btn">Assign</button>
                        </form></details>
                    
                <?php elseif (
                    !$schedule['has_work']
                    && $schedule['schedule_status'] === 'ASSIGNED'
                    && $schedule['assignment_id']
                ): ?>

                    <form
                        method="post"
                        action="<?= $escape($basePath) ?>/officer/collection-assignments/<?= (int) $schedule['schedule_id'] ?>"
                        data-unassign-form
                        data-schedule-label="<?= $escape('SCH-' . $schedule['schedule_id'] . ' — ' . $schedule['area_name']) ?>"
                    >
                        <input
                            type="hidden"
                            name="_csrf_token"
                            value="<?= $escape($csrfToken) ?>"
                        >

                        <input type="hidden" name="action" value="unassign">

                        <noscript>Enable JavaScript to confirm unassignment.</noscript>
                        <button type="submit" class="secondary-btn" data-unassign-trigger disabled>
                            Unassign
                        </button>
                    </form>

                <?php elseif (
                    $schedule['has_work']
                    && ($schedule['collection_status'] ?? null) === 'DRAFT'
                ): ?>

                    <span class="assignment-lock-reason">
                        <?php $recordCount = (int) $schedule['saved_record_count']; ?>
                        <?php if ($recordCount === 0): ?>
                            Empty collector draft exists; safe reconciliation is required before unassignment.
                        <?php else: ?>
                            Collector draft exists (<?= $recordCount ?> saved <?= $recordCount === 1 ? 'record' : 'records' ?>).
                            Unassignment is locked to protect collection data.
                        <?php endif; ?>
                        <?php if (($recordCount > 0 && $schedule['schedule_status'] !== 'IN_PROGRESS')
                            || !in_array($schedule['schedule_status'], ['ASSIGNED', 'IN_PROGRESS'], true)): ?>
                            Schedule and draft status are inconsistent; review required.
                        <?php endif; ?>
                    </span>

                    <?php else: ?>

                        <?php if (($schedule['collection_status'] ?? null) === 'REJECTED'): ?>

                            <span>Collection rejected — assignment locked; Officer follow-up required.</span>

                        <?php elseif (($schedule['collection_status'] ?? null) === 'VERIFIED'): ?>

                            <span>Collection verified — assignment locked.</span>

                        <?php elseif (($schedule['collection_status'] ?? null) === 'PENDING'): ?>

                            <span>Awaiting Officer verification — assignment locked.</span>

                        <?php elseif ($schedule['schedule_status'] === 'IN_PROGRESS'): ?>

                            <span>Collection started — assignment locked.</span>

                        <?php elseif ($schedule['schedule_status'] === 'COLLECTION_SUBMITTED'): ?>

                            <span>Submitted for review — assignment locked.</span>

                        <?php elseif ($schedule['schedule_status'] === 'COMPLETED'): ?>

                            <span>Collection completed — no changes allowed.</span>

                        <?php elseif ($schedule['has_work']): ?>

                            <span>Collection data exists — assignment locked.</span>

                        <?php elseif ($schedule['schedule_status'] === 'CLOSED'): ?>

                            <?php if ((int) $schedule['pending_reviews'] > 0): ?>

                                <span>Resolve pending risk reviews first.</span>

                            <?php elseif ((int) $schedule['ready_requests'] === 0): ?>

                                <span>No collectable requests available.</span>

                            <?php else: ?>

                                <span>Assignment unavailable — check schedule.</span>

                            <?php endif; ?>

                        <?php elseif ($schedule['schedule_status'] === 'ASSIGNED'): ?>

                            <span>Active assignment unavailable — check schedule.</span>

                        <?php else: ?>

                            <span>No assignment action available.</span>

                        <?php endif; ?>

                    <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p data-assignment-empty <?= $schedules ? 'hidden' : '' ?>>No matching collection schedules.</p>
    </section>
</section>


<dialog
    id="unassign-confirm-dialog"
    class="unassign-dialog"
    aria-labelledby="unassign-dialog-title"
    aria-describedby="unassign-dialog-description"
>
    <div class="officer-dialog-card unassign-dialog-card">

        <div class="officer-dialog-header">
            <div>
                <span class="dialog-eyebrow">
                    COLLECTION ASSIGNMENT
                </span>

                <h2 id="unassign-dialog-title">
                    Unassign resources?
                </h2>

                <p id="unassign-dialog-description">
                    Are you sure you want to remove the
                    collector and vehicle from this schedule?
                </p>
            </div>

            <button
                type="button"
                class="officer-dialog-close"
                data-unassign-close
                aria-label="Close dialog"
            >
                &times;
            </button>
        </div>

        <div class="unassign-dialog-content">

            <div class="unassign-warning">
                <strong>Selected schedule</strong>
                <span id="unassign-schedule-name"></span>
            </div>

            <p class="unassign-dialog-note">
                The schedule will return to Unassigned.
                Assignment history will be retained.
            </p>

        </div>

        <div class="officer-dialog-actions">

            <button
                type="button"
                class="secondary-btn"
                data-unassign-close
                autofocus
            >
                Keep assignment
            </button>

            <button
                type="button"
                class="unassign-danger-btn"
                id="confirm-unassign"
            >
                Unassign resources
            </button>

        </div>

    </div>
</dialog>

