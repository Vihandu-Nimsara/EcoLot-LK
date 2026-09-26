<?php
$escape = static fn ($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$recordId = $request['collection_record_id'];
$recordUrl = $basePath . '/collector/collection-records' . ($recordId ? '/' . $recordId : '');
$value = static fn ($candidate, $fallback = '') => is_scalar($candidate) ? $candidate : $fallback;
?>
<section class="collector-workspace">
    <header class="collector-page-heading"><div><span class="collector-eyebrow">Collection workspace</span><h1>Collection Record · Request #<?= $escape($request['request_id']) ?></h1>
        <p><?= $escape(CollectionRecord::label(CollectionRecord::status($request))) ?><?= $request['collection_submitted_at'] ? ' · Submitted ' . $escape($request['collection_submitted_at']) : '' ?></p></div>
        <a class="collector-secondary-button" href="<?= $escape($basePath . '/collector/schedules/' . $request['schedule_id']) ?>">Back to Schedule</a></header>
    <?php if ($error): ?><p class="collector-feedback collector-feedback--error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="collector-feedback collector-feedback--success" role="status"><?= $escape($notice) ?></p><?php endif; ?>
    <section class="collector-table-card collector-detail-card"><h2>Original Request Details — Read Only</h2>
        <dl class="collector-details-grid"><div><dt>Public user</dt><dd><?= $escape($request['full_name']) ?></dd></div>
            <div><dt>Pickup address</dt><dd><?= $escape($request['pickup_address']) ?></dd></div>
            <div><dt>Schedule / Collection date</dt><dd>#<?= $escape($request['schedule_id']) ?> / <?= $escape($request['collection_date']) ?></dd></div>
            <div><dt>Area</dt><dd><?= $escape($request['area_name']) ?></dd></div>
            <div><dt>Request / Risk review</dt><dd><?= $escape(ucfirst(strtolower(str_replace('_', ' ', $request['request_status'])))) ?> / <?= $escape(ucfirst(strtolower(str_replace('_', ' ', $request['risk_review_status'])))) ?></dd></div></dl>
        <div class="collector-table-wrap"><table class="collector-table"><thead><tr><th scope="col">Item</th><th scope="col">Requested Quantity</th><th scope="col">Estimated Weight (kg)</th><th scope="col">Requested Condition</th><th scope="col">Condition Note</th></tr></thead><tbody>
        <?php foreach ($request['items'] as $item): ?><tr><td><?= $escape($item['item_name']) ?></td><td><?= $escape($item['quantity']) ?></td><td><?= $escape($item['estimated_weight_kg']) ?></td><td><?= $escape(ucfirst(strtolower(str_replace('_', ' ', $item['item_condition'])))) ?></td><td><?= $escape($item['condition_note']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
    <section class="collector-table-card collector-detail-card"><h2>Collection Record</h2>
        <?php if (!$editable): ?><p>This request is read-only. Submitted records and requests awaiting Officer review cannot be changed.</p><?php endif; ?>
        <?php if ($request['verification_note']): ?><p>Officer note: <?= $escape($request['verification_note']) ?></p><?php endif; ?>
        <form id="collection-form" class="collector-record-form" method="post" action="<?= $escape($recordUrl . ($recordId ? '/update' : '')) ?>">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <?php if (!$recordId): ?><input type="hidden" name="request_id" value="<?= $escape($request['request_id']) ?>"><?php endif; ?>
            <fieldset <?= !$editable ? 'disabled' : '' ?>><legend>Actual collection information</legend>
                <p>Enter zero quantity for uncollected items, leaving weight and condition blank. Collected items require positive weight. Save changes before submitting the schedule.</p>
                <?php foreach ($request['items'] as $item): $itemId = $item['request_item_id']; $prior = $editable && is_array($old['items'][$itemId] ?? null) ? $old['items'][$itemId] : []; ?>
                <fieldset class="collector-item-fields"><legend><?= $escape($item['item_name']) ?></legend><div class="collector-details-grid">
                    <label>Actual quantity (maximum <?= $escape($item['quantity']) ?>)<input required type="number" min="0" max="<?= $escape($item['quantity']) ?>" step="1" name="items[<?= $escape($itemId) ?>][actual_quantity]" value="<?= $escape($value($prior['actual_quantity'] ?? $item['actual_quantity'])) ?>"></label>
                    <label>Actual weight (kg)<input type="number" min="0" max="9999999.999" step="0.001" name="items[<?= $escape($itemId) ?>][actual_weight_kg]" value="<?= $escape($value($prior['actual_weight_kg'] ?? $item['actual_weight_kg'])) ?>"></label>
                    <label>Actual condition<select name="items[<?= $escape($itemId) ?>][actual_condition]">
                        <?php foreach (['' => 'Not collected', 'WORKING' => 'Working', 'DAMAGED' => 'Damaged', 'UNKNOWN' => 'Unknown'] as $code => $label): ?><option value="<?= $escape($code) ?>" <?= ($prior['actual_condition'] ?? $item['actual_condition'] ?? '') === $code ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Item notes<textarea maxlength="500" name="items[<?= $escape($itemId) ?>][notes]"><?= $escape($value($prior['notes'] ?? $item['notes'])) ?></textarea></label>
                </div></fieldset><?php endforeach; ?>
                <p>Pickup result is calculated from the recorded item quantities: <?= $escape(CollectionRecord::label($request['pickup_result'])) ?></p>
                <label>Collector notes<textarea name="collector_note" maxlength="500"><?= $escape($editable ? ($old['collector_note'] ?? $request['collector_note']) : $request['collector_note']) ?></textarea></label>
                <?php if ($editable): ?><button type="submit" class="collector-primary-button"><?= $recordId ? 'Save Changes' : 'Save Draft' ?></button><?php endif; ?>
            </fieldset>
        </form>
        <?php if ($recordId && $editable): ?>
        <div class="collector-modal-actions">
            <form method="post" action="<?= $escape($recordUrl . '/delete') ?>" data-confirm="Permanently delete this draft collection record?"><input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><button class="collector-danger-button" type="submit">Delete Draft</button></form>
        </div><?php endif; ?>
    </section>
</section>
