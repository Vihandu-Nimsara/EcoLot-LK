<?php
$e = static fn ($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$label = static fn ($value): string => ucwords(strtolower(str_replace('_', ' ', (string)$value)));
$path = $basePath . '/admin/categories-items';
$options = static function (array $choices, string $selected) use ($e, $label): void {
    foreach ($choices as $choice) { ?>
        <option value="<?= $e($choice) ?>" <?= $choice === $selected ? 'selected' : '' ?>><?= $e($label($choice)) ?></option>
    <?php }
};
$selectedCategoryId = (int)($selectedCategory['category_id'] ?? 0);
$item = $editItem ?? null;
$activeCategories = array_values(array_filter($categories, static fn ($c) => $c['category_status'] === 'ACTIVE' && AdminCatalogueService::fixedName($c['category_name'])));
$excludedItem = $item && AdminCatalogueService::excludedCategory($item);
$nameLocked = $item && !empty($item['name_locked']);
?>
<section class="category-items-page">
    <div class="category-items-header">
        <div><h1>Category &amp; Item Management</h1><p>Maintain the e-waste catalogue and default collection policies.</p></div>
    </div>
    <?php if ($unavailable): ?>
        <p class="catalogue-feedback catalogue-error" role="alert">The catalogue is temporarily unavailable. Please try again later.</p>
    <?php else: ?>
    <?php if ($notice): ?><p class="catalogue-feedback" role="status"><?= $e($notice) ?></p><?php endif; ?>
    <div class="catalogue-summary">
        <div class="catalogue-summary-card"><span class="catalogue-summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 10 5-10 5L2 8l10-5Z M2 12l10 5 10-5 M2 16l10 5 10-5"/></svg></span><div><span>Total categories</span><strong><?= count($categories) ?></strong><small>Standard classifications (read-only)</small></div></div>
        <div class="catalogue-summary-card"><span class="catalogue-summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="13" rx="2"/><path d="M12 16v5 M7 21h10"/></svg></span><div><span>Total e-waste items</span><strong><?= count($items) ?></strong><small>Registered items in the catalogue</small></div></div>
    </div>
    <nav class="catalogue-tabs" aria-label="Catalogue sections" data-catalogue-tabs>
        <a id="catalogue-categories-tab" href="#categories" data-catalogue-tab="categories">Categories (<?= count($categories) ?>)</a>
        <a id="catalogue-items-tab" href="#items" data-catalogue-tab="items">E-Waste Items (<?= count($items) ?>)</a>
    </nav>
    <section class="data-card categories-card" id="categories">
        <div class="data-card-header"><div><h2>Categories (<?= count($categories) ?>)</h2><p>Standard classifications are read-only. Item counts include inactive items.</p></div></div>
        <form class="light-filter" data-client-filter data-rows="[data-category-row]" data-empty="[data-category-filter-empty]" data-result="[data-category-filter-result]">
            <div class="light-filter-controls"><div class="filter-field"><label for="category-search">Search categories</label><input id="category-search" name="search" type="search" placeholder="Category name"></div><div class="filter-field"><label for="category-status-filter">Status</label><select id="category-status-filter" name="status"><option value="">All</option><?php $options(['ACTIVE','INACTIVE'], ''); ?></select></div></div>
        </form>
        <p class="filter-result" data-category-filter-result role="status" aria-live="polite"></p>
        <div class="data-table-wrapper categories-table-wrapper"><table class="categories-table">
            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Items</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($categories as $c): ?>
                <tr data-category-row data-search="<?= $e($c['category_name'] . ' ' . $c['description']) ?>" data-status="<?= $e($c['category_status']) ?>">
                    <td data-label="ID">#<?= $e($c['category_id']) ?></td><td data-label="Name"><?= $e($c['category_name']) ?></td><td data-label="Description"><?= $e($c['description'] ?? '—') ?></td><td data-label="Items" class="center-cell"><?= $e($c['item_count']) ?></td>
                    <td data-label="Status"><span class="status-badge <?= $c['category_status'] === 'ACTIVE' ? 'status-active' : 'catalogue-inactive' ?>"><?= $e($label($c['category_status'])) ?></span></td>
                    <td data-label="Action"><a class="table-action-btn" href="<?= $e($path . '?category=' . $c['category_id'] . '#items') ?>">View items</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <p class="filtered-empty-state" data-category-filter-empty <?= $categories ? 'hidden' : '' ?>>No categories match the current filters.</p>
    </section>
    <dialog open class="catalogue-dialog" id="item-form" aria-labelledby="catalogue-dialog-title" data-catalogue-dialog data-editing="<?= $item ? '1' : '0' ?>" data-failed="<?= $failure ? '1' : '0' ?>">
        <div class="management-section">
            <div class="catalogue-dialog-heading"><div class="section-heading"><h2 id="catalogue-dialog-title"><?= $item ? 'Edit E-Waste Item' : 'Create E-Waste Item' ?></h2><p>Configure item availability, collection policy and default risk.</p></div><a class="catalogue-close" href="<?= $e($path . '#items') ?>" data-catalogue-close aria-label="Close item form">&times;</a></div>
            <?php if ($failure): ?><p class="catalogue-feedback catalogue-error" role="alert"><?= $e($failure) ?></p><?php endif; ?>
            <?php if (!$item && !$activeCategories): ?><p class="catalogue-hint">No active standard category is available. Contact the system maintainer.</p><?php else: ?>
            <form class="catalogue-form" data-catalogue-item-form method="post" action="<?= $e($path . '/items' . ($item ? '/' . $item['waste_item_id'] : '')) ?>">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                <?php if ($item): ?>
                    <label>Category<input value="<?= $e($item['category_name']) ?>" readonly></label>
                    <input type="hidden" name="category_id" value="<?= $e($item['category_id']) ?>">
                    <p class="catalogue-hint">Existing items stay in their original category to preserve linked records.</p>
                <?php else: ?>
                    <label>Category<select name="category_id" required><option value="">Select category</option><?php foreach ($activeCategories as $c): ?><option value="<?= $e($c['category_id']) ?>" data-excluded="<?= AdminCatalogueService::excludedCategory($c) ? '1' : '0' ?>"><?= $e($c['category_name']) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <label>Item name<input name="item_name" maxlength="160" required value="<?= $e($item['item_name'] ?? '') ?>" <?= $nameLocked ? 'readonly' : '' ?>></label>
                <p class="catalogue-hint"><?= $nameLocked ? 'Name locked: linked to pickup records or risk rules.' : 'Use a distinct name; edit only to correct the same item type.' ?></p>
                <div class="catalogue-form-grid">
                    <?php if ($excludedItem): ?>
                    <label>Collection policy<input value="Do Not Collect" readonly></label><input type="hidden" name="collection_status" value="DO_NOT_COLLECT">
                    <?php else: ?>
                    <label>Collection policy<select name="collection_status" required><option value="">Select collection policy</option><?php $options(['ACCEPTED','REVIEW_REQUIRED','DO_NOT_COLLECT'], $item['collection_status'] ?? ''); ?></select></label>
                    <?php endif; ?>
                    <label>Default risk<select name="default_risk_level" required><option value="">Select default risk</option><?php $options(['LOW','MEDIUM','HIGH'], $item['default_risk_level'] ?? ''); ?></select></label>
                    <label>Item status<select name="item_status"><?php $options(['ACTIVE','INACTIVE'], $item['item_status'] ?? 'INACTIVE'); ?></select></label>
                </div>
                <div class="catalogue-guidance"><strong>Collection guidance</strong><ul><li>Choose policy and risk using approved collection guidance.</li><li>Excluded category items must be Do Not Collect.</li><li>New items start inactive unless you choose Active. Inactive and Do Not Collect items cannot be selected for new pickups.</li><li>Active condition-specific risk rules take precedence over default risk. Existing requests are not automatically cancelled.</li></ul></div>
                <div class="catalogue-actions"><a class="header-action-btn" data-catalogue-close href="<?= $e($path . '#items') ?>">Cancel</a><button class="primary-action-btn" type="submit"><?= $item ? 'Save Item' : 'Create Item' ?></button></div>
            </form>
            <?php endif; ?>
        </div>
    </dialog>
    <section class="data-card items-card" id="items">
        <div class="data-card-header catalogue-list-heading"><div><h2>E-Waste Items (<?= count($items) ?>)</h2><p>Review and edit collection policies and item availability.</p></div><?php if ($activeCategories): ?><a class="primary-action-btn" href="<?= $e($path . '#item-form') ?>" data-catalogue-add>+ Add Item</a><?php endif; ?></div>
        <form class="light-filter" data-catalogue-item-filter>
            <div class="light-filter-controls">
                <div class="filter-field"><label for="item-search">Search items</label><input id="item-search" name="search" type="search" placeholder="Item name"></div>
                <div class="filter-field"><label for="item-category">Category</label><select id="item-category" name="category"><option value="">All</option><?php foreach ($categories as $c): ?><option value="<?= $e($c['category_id']) ?>" <?= (int)$c['category_id'] === $selectedCategoryId ? 'selected' : '' ?>><?= $e($c['category_name']) ?></option><?php endforeach; ?></select></div>
                <div class="filter-field"><label for="item-status-filter">Item status</label><select id="item-status-filter" name="status"><option value="">All</option><?php $options(['ACTIVE','INACTIVE'], ''); ?></select></div>
                <div class="filter-field"><label for="item-policy-filter">Collection policy</label><select id="item-policy-filter" name="policy"><option value="">All</option><?php $options(['ACCEPTED','REVIEW_REQUIRED','DO_NOT_COLLECT'], ''); ?></select></div>
                <div class="filter-field"><label for="item-risk-filter">Default risk</label><select id="item-risk-filter" name="risk"><option value="">All</option><?php $options(['LOW','MEDIUM','HIGH'], ''); ?></select></div>
            </div>
        </form>
        <p class="filter-result" data-item-filter-result role="status" aria-live="polite"></p>
        <div class="data-table-wrapper items-table-wrapper"><table class="items-table catalogue-items-table">
            <thead><tr><th>ID</th><th>Category</th><th>Item</th><th>Collection Policy</th><th>Default Risk</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($items as $i): ?>
                <?php $policyClass = match ($i['collection_status']) { 'REVIEW_REQUIRED' => 'status-review', 'DO_NOT_COLLECT' => 'status-rejected', default => 'status-accepted' }; ?>
                <tr data-item-row data-search="<?= $e($i['item_name'] . ' ' . $i['category_name']) ?>" data-category="<?= $e($i['category_id']) ?>" data-status="<?= $e($i['item_status']) ?>" data-policy="<?= $e($i['collection_status']) ?>" data-risk="<?= $e($i['default_risk_level']) ?>">
                    <td data-label="ID">#<?= $e($i['waste_item_id']) ?></td><td data-label="Category"><?= $e($i['category_name']) ?><?php if ($i['category_status'] !== 'ACTIVE'): ?><small class="catalogue-hint">Category inactive</small><?php endif; ?></td><td data-label="Item"><?= $e($i['item_name']) ?></td>
                    <td data-label="Collection Policy"><span class="status-badge <?= $policyClass ?>"><?= $e($label($i['collection_status'])) ?></span></td><td data-label="Default Risk"><span class="risk-badge risk-<?= $e(strtolower($i['default_risk_level'])) ?>"><?= $e($label($i['default_risk_level'])) ?></span></td>
                    <td data-label="Status"><span class="status-badge <?= $i['item_status'] === 'ACTIVE' ? 'status-active' : 'catalogue-inactive' ?>"><?= $e($label($i['item_status'])) ?></span></td><td data-label="Action"><a class="table-action-btn" href="<?= $e($path . '?edit_item=' . $i['waste_item_id'] . '#item-form') ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <p class="filtered-empty-state" data-item-filter-empty <?= $items ? 'hidden' : '' ?>>No items match the current filters.</p>
        <div class="catalogue-pagination" data-catalogue-pagination hidden>
            <label for="catalogue-page-size">Items per page <select id="catalogue-page-size"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select></label>
            <nav aria-label="Item pagination" class="catalogue-page-buttons"><button type="button" data-page-prev aria-label="Previous page">&lsaquo;</button><span data-page-numbers></span><button type="button" data-page-next aria-label="Next page">&rsaquo;</button></nav>
        </div>
    </section>
    <script defer src="<?= $e($basePath . '/assets/js/admin/catalogue.js') ?>"></script>
    <?php endif; ?>
</section>
