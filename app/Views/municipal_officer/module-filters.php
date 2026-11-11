<?php
// Included only by the two Officer modules; all filter values have been validated by the controller.
$escape=static fn($value): string=>htmlspecialchars($value===null?'Not recorded':(string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$url=$basePath.'/officer/'.($module==='elots'?'e-lots':'feedback');
$link=static fn(array $changes): string=>$url.'?'.http_build_query(array_replace($filters,$changes));
?>
<?php foreach ($errors as $error): ?><p class="officer-form-error" role="alert"><?= $escape($error) ?></p><?php endforeach; ?>
<?php if ($notice): ?><p class="module-notice" role="status"><?= $escape($notice) ?></p><?php endif; ?>
<section class="<?= $module==='elots'?'elot-filter-card':'feedback-list-card' ?> compact-filter-card">
<form method="get" action="<?= $escape($url) ?>" class="officer-module-filters">
    <div class="form-group"><label for="module-search">Search</label><input id="module-search" name="q" value="<?= $escape($filters['q']) ?>" maxlength="100" placeholder="<?= $module==='elots'?'Code or title':'Message or submitter' ?>"></div>
    <div class="form-group"><label for="module-status">Status</label><select id="module-status" name="status"><option value="">All statuses</option>
        <?php foreach ($module==='elots'?OfficerELot::STATUSES:OfficerFeedback::STATUSES as $status): ?><option value="<?= $escape($status) ?>" <?= $filters['status']===$status?'selected':'' ?>><?= $escape(str_replace('_',' ',$status)) ?></option><?php endforeach; ?></select></div>
    <?php if ($module==='elots'): ?><div class="form-group"><label for="module-category">Category</label><select id="module-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['category_id'] ?>" <?= $filters['category']==$category['category_id']?'selected':'' ?>><?= $escape($category['category_name']) ?></option><?php endforeach; ?></select></div>
    <?php else: ?><div class="form-group"><label for="module-type">Type</label><select id="module-type" name="type"><option value="">All types</option><?php foreach (OfficerFeedback::TYPES as $type): ?><option <?= $filters['type']===$type?'selected':'' ?>><?= $escape($type) ?></option><?php endforeach; ?></select></div><?php endif; ?>
    <div class="form-group"><label for="module-sort">Sort</label><select id="module-sort" name="sort"><option value="newest">Newest first</option><option value="oldest" <?= $filters['sort']==='oldest'?'selected':'' ?>>Oldest first</option></select></div>
    <div class="filter-actions"><button class="primary-btn" type="submit">Apply Filters</button><a class="secondary-btn" href="<?= $escape($url) ?>">Clear</a></div>
</form>
</section>
<p class="officer-result-count"><?= (int)$listing['total'] ?> matching records · <?= count($listing['rows']) ?> shown · Page <?= (int)$filters['page'] ?></p>
<nav class="module-pagination" aria-label="Pagination">
    <?php if ($filters['page']>1): ?><a class="secondary-btn" href="<?= $escape($link(['page'=>$filters['page']-1])) ?>">Previous</a><?php endif; ?>
    <?php if ($filters['page']*20<$listing['total']): ?><a class="secondary-btn" href="<?= $escape($link(['page'=>$filters['page']+1])) ?>">Next</a><?php endif; ?>
</nav>
