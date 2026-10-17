<?php
$selectedType = (string) ($draft['feedback_type'] ?? '');
$selectedRequest = (string) ($draft['related_request'] ?? '');
$typeLabels = ['COMPLAINT' => 'Complaint', 'SUGGESTION' => 'Suggestion', 'COMPLIMENT' => 'Compliment'];
?>
<section class="feedback-form-page">
    <div class="page-toolbar">
        <div>
            <h1>Feedback &amp; Support</h1>
            <p>Send a complaint, suggestion, or compliment to the EcoLot LK team.</p>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="form-alert form-alert-error" role="alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="form-alert form-alert-success" role="status"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="surface-card feedback-form" method="post" action="<?= $basePath ?>/user/feedback" data-feedback-form>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-field">
            <label for="feedback-type">Feedback Type</label>
            <select id="feedback-type" name="feedback_type" required>
                <option value="">Select type</option>
                <?php foreach ($typeLabels as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="related-request">Related Request (optional)</label>
            <select id="related-request" name="related_request">
                <option value="">No related request</option>
                <?php foreach ($requests as $request): ?>
                    <option value="<?= (int) $request['request_id'] ?>" <?= $selectedRequest === (string) $request['request_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($request['code'] . ' - ' . $request['collection_date'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="feedback-message">Message</label>
            <textarea id="feedback-message" name="message" rows="6" minlength="10" maxlength="2000"
                placeholder="Enter your message" required><?= htmlspecialchars((string) ($draft['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="feedback-counter" data-feedback-counter>0 / 2000</p>
        </div>

        <div class="form-actions">
            <a href="<?= $basePath ?>/user/dashboard" class="secondary-btn">Cancel</a>
            <button type="submit" class="primary-btn">Submit Feedback</button>
        </div>
    </form>

    <div class="surface-card feedback-history">
        <h2>My Previous Feedback</h2>
        <?php if (empty($history)): ?>
            <p>You have not sent any feedback yet.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Request</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime((string) $item['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($typeLabels[$item['feedback_type']] ?? (string) $item['feedback_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $item['request_id'] !== null ? 'REQ-' . (int) $item['request_id'] : '—' ?></td>
                            <td>
                                <span class="feedback-message"><?= htmlspecialchars((string) $item['message'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($item['officer_response'])): ?>
                                    <p class="feedback-response"><strong>Response:</strong> <?= htmlspecialchars((string) $item['officer_response'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', (string) $item['feedback_status']))), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>