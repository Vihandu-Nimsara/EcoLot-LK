<?php $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
<section class="flagged-requests-page">
    <div class="page-toolbar"><div><h1>Hazardous Request Review</h1><p>Review item risks before resource assignment. Decisions are final here; all times use Sri Lanka time.</p></div></div>
    <?php if ($notice): ?><p role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <?php foreach ($errors as $error): ?><p role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
    <section class="filter-card compact-filter-card">
        <form data-review-filter>
            <label for="review-status">Review status</label>
            <select id="review-status"><option value="">All</option><option>PENDING</option><option>APPROVED</option><option>REJECTED</option></select>
            <label for="review-search">Campaign, schedule or user</label>
            <input id="review-search" type="search">
            <button type="reset" class="secondary-btn">Clear</button>
        </form>
    </section>
    <section class="flagged-list-card">
        <p>Pending reviews on cancelled requests remain in history but cannot be actioned.</p>
        <div class="flagged-table-wrapper"><table class="flagged-table">
            <thead><tr><th>Request / User</th><th>Campaign / Schedule</th><th>Items and risk</th><th>Review / Request status</th><th>Decision</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr data-risk-status="<?= $escape($request['risk_review_status']) ?>">
                    <td>REQ-<?= (int) $request['request_id'] ?><br><?= $escape($request['full_name']) ?><br><?= $escape($request['submitted_at']) ?><br><?= $escape($request['pickup_address']) ?></td>
                    <td><?= $escape($request['campaign_name']) ?><br><a href="<?= $escape($basePath) ?>/officer/area-schedules/<?= (int) $request['schedule_id'] ?>">SCH-<?= (int) $request['schedule_id'] ?></a><br><?= $escape($request['area_name']) ?><br><?= $escape($request['collection_date']) ?></td>
                    <td><?php foreach ($request['items'] as $item): ?>
                        <p><?= $escape($item['item_name']) ?> × <?= (int) $item['quantity'] ?><br>Risk: <?= $escape($item['applied_risk_level']) ?>; condition: <?= $escape($item['item_condition']) ?><br><?= $escape($item['condition_note']) ?></p>
                    <?php endforeach; ?><?php if (!$request['items']): ?>No item details recorded.<?php endif; ?></td>
                    <td><?= $escape($request['risk_review_status']) ?><br><?= $escape($request['request_status']) ?></td>
                    <td>
                    <?php if ($request['request_status'] === 'PENDING_REVIEW' && $request['risk_review_status'] === 'PENDING' && in_array($request['schedule_status'], ['PLANNED','OPEN','CLOSED'], true)): ?>
                        <form method="post" action="<?= $escape($basePath) ?>/officer/flagged-requests/<?= (int) $request['request_id'] ?>/review">
                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                            <label>Decision <select name="decision" required><option value="">Select decision</option><option value="APPROVED">Approve for collection</option><option value="REJECTED">Reject</option></select></label>
                            <label>Officer note <textarea name="note" maxlength="500" rows="3" placeholder="Handling instructions; reason required for rejection"></textarea></label>
                            <button class="primary-btn" type="submit">Save Review</button>
                        </form>
                    <?php else: ?>
                        <?= $escape($request['reviewer_name'] ?? 'No decision recorded') ?><br><?= $escape($request['reviewed_at']) ?><br><?= $escape($request['review_note']) ?>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p data-review-empty <?= $requests ? 'hidden' : '' ?>>No flagged requests match.</p>
    </section>
</section>
