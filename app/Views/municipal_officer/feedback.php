<section class="feedback-page">
    <div class="page-toolbar"><div><h1>Feedback Management</h1></div></div>
    <?php require __DIR__.'/module-filters.php'; ?>

    <section class="feedback-list-card officer-feedback-results">
        <h2>Feedback</h2>
        <p class="officer-result-count"><?= (int) $listing['total'] ?> matching records</p>
        <?php if (!$listing['rows']): ?>
            <p class="officer-empty-state">No feedback matches these filters.</p>
        <?php else: ?>
            <div class="feedback-table-wrapper"><table class="feedback-table">
                <thead><tr><th scope="col">Feedback</th><th scope="col">Submitted by</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                <tbody>
                <?php foreach ($listing['rows'] as $feedback): ?>
                    <tr>
                        <td><strong><?= $escape(ucfirst(strtolower($feedback['feedback_type']))) ?> #<?= (int) $feedback['feedback_id'] ?></strong>
                            <small class="feedback-secondary-text"><?= $escape(mb_strimwidth($feedback['message'], 0, 100, '…', 'UTF-8')) ?></small>
                        </td>
                        <td><?= $escape($feedback['public_name']) ?><small class="feedback-secondary-text"><?= $escape($feedback['submitted_at']) ?></small></td>
                        <td><span class="feedback-status <?= $escape(strtolower(str_replace('_', '-', $feedback['feedback_status']))) ?>" data-status="<?= $escape($feedback['feedback_status']) ?>"><?= $escape(OfficerFeedback::LABELS[$feedback['feedback_status']]) ?></span></td>
                        <td><a class="secondary-btn" href="<?= $escape($link(['id' => $feedback['feedback_id']])) ?>#module-details"><?= $feedback['feedback_status'] === 'CLOSED' ? 'View Details' : 'Review Feedback' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
        <?php if ($listing['pages'] > 1): ?>
            <nav class="module-pagination" aria-label="Feedback pages">
                <?php if ($filters['page'] > 1): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $filters['page'] - 1])) ?>">Previous</a><?php endif; ?>
                <span>Page <?= (int) $filters['page'] ?> of <?= (int) $listing['pages'] ?></span>
                <?php if ($filters['page'] < $listing['pages']): ?><a class="secondary-btn" href="<?= $escape($link(['page' => $filters['page'] + 1])) ?>">Next</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <?php if ($selected): $feedback = $selected; $transitions = OfficerFeedback::transitions($feedback['feedback_status']); ?>
        <dialog class="feedback-review-dialog" id="module-details" aria-labelledby="feedback-dialog-title" data-feedback-dialog open>
            <header class="feedback-dialog-header">
                <div>
                    <h2 id="feedback-dialog-title"><?= $transitions ? 'Review Feedback' : 'Feedback Details' ?></h2>
                    <p class="feedback-secondary-text"><?= $escape(ucfirst(strtolower($feedback['feedback_type']))) ?> #<?= (int) $feedback['feedback_id'] ?></p>
                </div>
                <a class="secondary-btn" data-feedback-dismiss href="<?= $escape($link()) ?>" autofocus>Close</a>
            </header>
            <div class="feedback-dialog-body">
                <?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
                <?php if ($notice): ?><p class="module-notice" role="status"><?= $escape($notice) ?></p><?php endif; ?>
                <span class="feedback-status <?= $escape(strtolower(str_replace('_', '-', $feedback['feedback_status']))) ?>"><?= $escape(OfficerFeedback::LABELS[$feedback['feedback_status']]) ?></span>
                <p class="feedback-secondary-text"><?= $escape($feedback['public_name']) ?> · <?= $escape($feedback['submitted_at']) ?></p>
                <p class="feedback-public-message"><?= $escape($feedback['message']) ?></p>
                <?php if ($feedback['request_id'] !== null): ?>
                    <details class="feedback-disclosure">
                        <summary>Related request #<?= (int) $feedback['request_id'] ?></summary>
                        <p class="feedback-secondary-text"><?= $escape($feedback['request_status']) ?> · SCH-<?= (int) $feedback['schedule_id'] ?> · <?= $escape($feedback['area_name']) ?> · <?= $escape($feedback['collection_date']) ?></p>
                    </details>
                <?php endif; ?>

                <?php if ($transitions): ?>
                    <form class="feedback-review-form" method="post" action="<?= $escape($url . '/' . $feedback['feedback_id'] . '/review?' . http_build_query($filters)) ?>" data-officer-confirm="Save this feedback status and response?">
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="expected" value="<?= $escape($feedback['feedback_status']) ?>">
                        <div class="form-group"><label for="feedback-status">Next status</label><select name="status" id="feedback-status">
                            <?php foreach ($transitions as $status): ?><option value="<?= $escape($status) ?>" <?= $formValue('status') === $status ? 'selected' : '' ?>><?= $escape(OfficerFeedback::LABELS[$status]) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="form-group"><label for="feedback-response">Officer response</label><textarea id="feedback-response" name="response" rows="4" maxlength="2000" aria-describedby="feedback-response-help"><?= $escape($formValue('response', $feedback['officer_response'] ?? '')) ?></textarea>
                            <small id="feedback-response-help" class="feedback-secondary-text">Required to resolve or close feedback.</small>
                        </div>
                        <div class="feedback-form-actions"><button class="primary-btn" data-confirm-trigger disabled>Save Update</button></div>
                    </form>
                    <noscript><p>Enable JavaScript to confirm feedback updates.</p></noscript>
                <?php elseif ($feedback['officer_response'] !== null && $feedback['officer_response'] !== ''): ?>
                    <h3 class="feedback-response-title">Officer response</h3>
                    <p class="module-message"><?= $escape($feedback['officer_response']) ?></p>
                <?php endif; ?>
                <?php if ($feedback['history']): ?>
                    <details class="feedback-disclosure">
                        <summary>Management history</summary>
                        <?php $history = $feedback['history']; $showHistoryHeading = false; require __DIR__.'/module-history.php'; ?>
                    </details>
                <?php elseif ($feedback['reviewer_name'] !== null): ?>
                    <p class="feedback-secondary-text">Reviewed by <?= $escape($feedback['reviewer_name']) ?> · <?= $escape($feedback['reviewed_at']) ?></p>
                <?php endif; ?>
            </div>
        </dialog>
        <?php require __DIR__.'/module-confirmation.php'; ?>
    <?php elseif ($notice): ?><p class="module-notice" role="status"><?= $escape($notice) ?></p><?php endif; ?>
</section>
