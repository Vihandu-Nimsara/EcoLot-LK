<?php
$items = $items ?? []; $selected = $selected ?? null; $editCondition = $editCondition ?? null;
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$url = rtrim($basePath, '/') . '/admin/risk-rules';
$levels = AdminRiskRuleService::LEVELS;
$conditions = AdminRiskRuleService::CONDITIONS;
$counts = ['rules'=>0,'active'=>0,'high'=>0,'profiles'=>0]; $categories = [];
foreach ($items as $item) {
    $categories[$item['category_id']] = $item['category_name'];
    $counts['rules'] += count($item['rules']);
    $active = array_filter($item['rules'], static fn($r) => $r['rule_status'] === 'ACTIVE');
    $counts['active'] += count($active);
    $counts['high'] += count(array_filter($active, static fn($r) => $r['risk_level'] === 'HIGH'));
    $counts['profiles'] += $active ? 1 : 0;
}
?>
<section class="risk-rules-page" data-risk-page>
    <div class="risk-page-intro"><h1>Risk Rules Management</h1><p>Manage condition-specific risk profiles. Rules can raise, but never lower, the catalogue default risk.</p></div>
    <?php if (!empty($loadError)): ?><p class="rr-notice rr-error" role="alert"><?= $e($loadError) ?></p><?php endif; ?>
    <?php if (!empty($notice)): ?><p class="rr-notice <?= $notice['type'] === 'error' ? 'rr-error' : '' ?>" role="status"><?= $e($notice['message']) ?></p><?php endif; ?>
    <?php if (empty($loadError)): ?>
    <div class="rr-metrics">
        <?php foreach (['Catalogue items'=>count($items),'Configured rules'=>$counts['rules'],'Active rules'=>$counts['active'],'Active high-risk rules'=>$counts['high']] as $label=>$value): ?>
        <section class="rr-metric"><span><?= $e($label) ?></span><strong><?= $e($value) ?></strong></section>
        <?php endforeach; ?>
    </div>
    <div class="rr-layout">
        <section class="risk-card rr-catalogue">
            <div class="risk-card-header"><div><h2>E-Waste Items</h2><p><?= $e($counts['profiles']) ?> items with active rules</p></div></div>
            <div class="rr-filters">
                <label for="rr-search">Search items</label><input type="search" id="rr-search" placeholder="Item name or category" data-rr-search>
                <label for="rr-category">Category</label><select id="rr-category" data-rr-category><option value="">All categories</option><?php foreach ($categories as $id=>$name): ?><option value="<?= $e($id) ?>"><?= $e($name) ?></option><?php endforeach; ?></select>
                <label for="rr-coverage">Rule coverage</label><select id="rr-coverage" data-rr-coverage><option value="">All items</option><option value="none">No active rules</option><option value="some">Some active rules</option><option value="all">All conditions covered</option></select>
            </div>
            <p class="rr-muted" data-rr-count aria-live="polite"><?= count($items) ?> items</p>
            <nav class="rr-item-list" aria-label="Item risk profiles">
            <?php foreach ($items as $item): $activeCount = count(array_filter($item['rules'],static fn($r)=>$r['rule_status']==='ACTIVE')); $isSelected = $selected && (string)$selected['waste_item_id']===(string)$item['waste_item_id']; ?>
                <a class="rr-item <?= $isSelected ? 'rr-selected' : '' ?>" href="<?= $e($url.'?item='.$item['waste_item_id']) ?>" <?= $isSelected ? 'aria-current="page"' : '' ?> data-rr-item data-search="<?= $e($item['item_name'].' '.$item['category_name']) ?>" data-category="<?= $e($item['category_id']) ?>" data-coverage="<?= $activeCount===0?'none':($activeCount===3?'all':'some') ?>">
                    <strong><?= $e($item['item_name']) ?></strong><span><?= $e($item['category_name']) ?> · <?= $activeCount ?>/3 active rules</span>
                    <?php if (!AdminRiskRuleService::eligible($item)): ?><span>Unavailable for pickup</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            </nav>
            <p data-rr-empty hidden>No items match these filters.</p>
            <?php if (!$items): ?><p>No catalogue items are available.</p><?php endif; ?>
        </section>
        <section class="risk-card rr-profile" aria-label="Selected risk profile">
            <?php if (!$selected): ?><h2>Select an item</h2><p>Choose an item from the catalogue to see its risk profile.</p>
            <?php else: $itemId = $selected['waste_item_id']; $default = $selected['default_risk_level']; $eligible = AdminRiskRuleService::eligible($selected); ?>
                <div class="risk-card-header"><div><h2><?= $e($selected['item_name']) ?></h2><p><?= $e($selected['category_name']) ?></p></div></div>
                <div class="rr-default"><div><span>Default risk</span><strong><?= $e($default) ?></strong></div><div><span>Collection policy</span><strong><?= $e(str_replace('_',' ',$selected['collection_status'])) ?></strong></div><div><span>Item status</span><strong><?= $e($selected['item_status']) ?></strong></div></div>
                <?php if (!$eligible): ?><p class="rr-notice">This item is unavailable for new pickups. Its rules are read-only here.</p><?php endif; ?>
                <h3>Condition-specific rules</h3>
                <p class="rr-muted">Inactive or absent rules use the default risk. A higher catalogue default always takes priority.</p>
                <?php foreach ($conditions as $condition): $rule = $selected['rules'][$condition] ?? null; $risk = AdminRiskRuleService::effective($default,$rule); ?>
                    <article class="rr-condition">
                        <div class="rr-condition-top"><div><h3><?= $e(ucfirst(strtolower($condition))) ?></h3><span class="rr-muted"><?= !$rule?'No custom rule':($rule['rule_status']==='INACTIVE'?'Inactive rule':($rule['risk_level']!==$risk?'Active rule · default floor applied':'Active rule')) ?></span></div><span class="risk-badge risk-<?= strtolower($risk) ?>"><?= $e($risk) ?></span><?php if ($eligible): ?><a class="table-action-btn rr-edit" href="<?= $e($url.'?item='.$itemId.'&condition='.$condition) ?>"><?= $rule?'Edit rule':'Set rule' ?></a><?php endif; ?></div>
                        <p class="rr-outcome"><?= $e(AdminRiskRuleService::outcome($selected,$risk)) ?></p>
                        <?php if ($rule): ?><p class="rr-guidance"><strong>Saved guidance (<?= $e(strtolower($rule['rule_status'])) ?>):</strong> <?= $e($rule['action_note']) ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <p class="rr-muted">Guidance is stored for reference in this Admin page; it is not an automated rejection rule or currently displayed in Officer/Collector screens. Actual item handling requires approved safety guidance.</p>
                <?php if ($eligible && in_array($editCondition,$conditions,true)): $rule = $selected['rules'][$editCondition] ?? null; $initialRisk = $rule['risk_level'] ?? ''; ?>
                <dialog class="rr-drawer" data-rr-dialog open aria-labelledby="rr-dialog-title">
                    <div class="rr-dialog-header"><h2 id="rr-dialog-title"><?= $rule?'Edit':'Set' ?> Risk Rule</h2><a class="table-action-btn" href="<?= $e($url.'?item='.$itemId) ?>" aria-label="Close rule editor">Close</a></div>
                    <p><?= $e($selected['item_name']) ?> · <?= $e(ucfirst(strtolower($editCondition))) ?></p>
                    <p class="rr-muted">Default risk: <?= $e($default) ?>. Condition and item are fixed.</p>
                    <form method="post" action="<?= $e($url.'/'.$itemId.'/'.$editCondition) ?>" class="rr-form" data-rr-form data-default="<?= $e($default) ?>" data-policy="<?= $e($selected['collection_status']) ?>" data-before="<?= $e(AdminRiskRuleService::effective($default,$rule)) ?>">
                        <input type="hidden" name="_csrf_token" value="<?= $e(Csrf::token()) ?>"><input type="hidden" name="revision" value="<?= $e(AdminRiskRuleService::revision($selected,$rule)) ?>">
                        <label for="rr-level">Risk level</label><select id="rr-level" name="risk_level" required><option value="">Select risk level</option><?php foreach ($levels as $i=>$level): if ($i < array_search($default,$levels,true)) continue; ?><option value="<?= $e($level) ?>" <?= $initialRisk===$level?'selected':'' ?>><?= $e(ucfirst(strtolower($level))) ?></option><?php endforeach; ?></select>
                        <label for="rr-note">Handling guidance</label><textarea id="rr-note" name="action_note" required maxlength="500" rows="5" placeholder="Enter approved handling guidance"><?= $e($rule['action_note']??'') ?></textarea>
                        <label for="rr-status">Rule status</label><select id="rr-status" name="rule_status"><option value="INACTIVE" <?= ($rule['rule_status']??'INACTIVE')==='INACTIVE'?'selected':'' ?>>Inactive (use default)</option><option value="ACTIVE" <?= ($rule['rule_status']??'')==='ACTIVE'?'selected':'' ?>>Active</option></select>
                        <div class="rr-preview" aria-live="polite" data-rr-preview>Choose risk level and status to preview the result.</div>
                        <label class="rr-confirm"><input type="checkbox" name="confirm_reduction" value="1"> I confirm any reduction of the effective risk is supported by approved safety guidance.</label>
                        <div class="rr-dialog-actions"><a class="table-action-btn" href="<?= $e($url.'?item='.$itemId) ?>">Cancel</a><button class="create-rule-btn" type="submit">Save Rule</button></div>
                    </form>
                </dialog>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>
</section>
<script defer src="<?= $e(rtrim($basePath,'/').'/assets/js/admin/risk-rules.js?v='.Asset::version('js/admin/risk-rules.js')) ?>"></script>
