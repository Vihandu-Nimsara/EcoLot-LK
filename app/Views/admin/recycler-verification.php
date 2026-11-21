<?php
$e=static fn($value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$profiles=$recyclerProfiles??[];
$states=['PENDING'=>'Pending','VERIFIED'=>'Verified','REJECTED'=>'Rejected','SUSPENDED'=>'Suspended'];
$requested=$_GET['status']??'ALL';$current=is_string($requested)?strtoupper($requested):'ALL';
if (!isset($states[$current])) $current='ALL';
$counts=array_fill_keys(array_keys($states),0);
foreach ($profiles as $profile) if (isset($counts[$profile['status']])) $counts[$profile['status']]++;
?>
<section class="recycler-verification-page">
    <div class="verification-header"><div><h1>Recycler Verification</h1><p>Review registration evidence, requested permissions and final approval readiness.</p></div></div>
    <section class="verification-card">
        <form class="light-filter" data-client-filter data-rows="[data-recycler-row]" data-empty="[data-recycler-filter-empty]" data-result="[data-recycler-filter-result]">
            <div class="quick-filters" role="group" aria-label="Filter by registration status">
                <?php foreach (['ALL'=>'All']+$states as $key=>$caption): ?><button class="quick-filter" type="button" aria-pressed="<?= $current===$key?'true':'false' ?>" data-filter-name="status" data-filter-value="<?= $key==='ALL'?'':$key ?>"><?= $caption ?> (<?= $key==='ALL'?count($profiles):$counts[$key] ?>)</button><?php endforeach; ?>
            </div>
            <div class="light-filter-controls"><div class="filter-field"><label for="recycler-search">Search recyclers</label><input type="search" id="recycler-search" name="search" placeholder="Company, contact or licence number"></div></div>
        </form>
        <p class="filter-result" data-recycler-filter-result role="status" aria-live="polite"></p>
        <?php if (!$profiles): ?><p class="empty-state-card">No Recycler applications have been submitted.</p><?php else: ?>
        <div class="report-table-wrapper recycler-verification-table-wrapper" role="region" aria-label="Recycler applications — scroll horizontally to view all columns" tabindex="0"><table class="report-table recycler-verification-table"><thead><tr><th scope="col">Company</th><th scope="col">Contact</th><th scope="col">CEA licence</th><th scope="col">District</th><th scope="col">Submitted</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead><tbody>
            <?php foreach ($profiles as $profile): $status=$profile['status'];$statusClass=match($status){'VERIFIED'=>'status-verified','PENDING'=>'status-pending',default=>'status-rejected'}; ?>
            <tr data-recycler-row data-status="<?= $e($status) ?>" data-search="<?= $e($profile['company_name'].' '.$profile['contact_person'].' '.($profile['license_no']??'')) ?>">
                <td><strong class="table-primary-text"><?= $e($profile['company_name']) ?></strong><span class="table-secondary-text">Recycler #<?= (int)$profile['recycler_id'] ?></span></td>
                <td><span class="table-contact-name"><?= $e($profile['contact_person']) ?></span><span class="table-secondary-text"><?= $e($profile['email']??'No email recorded') ?></span><span class="table-secondary-text"><?= $e($profile['phone']) ?></span></td>
                <td><span class="table-primary-text"><?= $e($profile['license_no']??'Not submitted') ?></span><span class="table-secondary-text"><?= $e($profile['license_expiry']??'No expiry recorded') ?></span></td>
                <td><?= $e($profile['district']) ?></td><td><?= $e($profile['submitted_at']) ?></td><td><span class="status-badge <?= $statusClass ?>"><?= $e($states[$status]??ucfirst(strtolower($status))) ?></span></td>
                <td><a class="action-btn secondary-btn-style" href="<?= $e($basePath) ?>/admin/recycler-verification/<?= (int)$profile['recycler_id'] ?>"><?= $status==='PENDING'?'Review application':'View record' ?></a></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><p class="filtered-empty-state" data-recycler-filter-empty hidden>No applications match these filters.</p>
        <?php endif; ?>
    </section>
</section>
