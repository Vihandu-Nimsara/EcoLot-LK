<?php
$escape = static fn ($value): string => htmlspecialchars($value === null ? 'Not recorded' : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$url = $basePath . '/officer/feedback';
$formValue = static fn (string $key, string $fallback = ''): string =>
    isset($formInput[$key]) && is_string($formInput[$key]) ? $formInput[$key] : $fallback;
$link = static fn (array $changes = []): string => $url . '?' . http_build_query(array_replace($filters, $changes));
?>
<section class="feedback-list-card compact-filter-card">
    <form method="get" action="<?= $escape($url) ?>" class="officer-module-filters">
        <div class="form-group"><label for="module-status">Status</label><select id="module-status" name="status">
            <option value="">All statuses</option>
            <?php foreach (OfficerFeedback::LABELS as $status => $label): ?><option value="<?= $escape($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label for="module-type">Type</label><select id="module-type" name="type">
            <option value="">All types</option>
            <?php foreach (OfficerFeedback::TYPES as $type): ?><option value="<?= $escape($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= $escape(ucfirst(strtolower($type))) ?></option><?php endforeach; ?>
        </select></div>
        <div class="filter-actions"><button class="primary-btn" type="submit">Apply Filters</button><a class="secondary-btn" href="<?= $escape($url) ?>">Clear</a></div>
    </form>
</section>
