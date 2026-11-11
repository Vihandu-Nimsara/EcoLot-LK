<?php $module='feedback'; ?>
<section class="feedback-page">
<div class="page-toolbar"><div><h1>Feedback & Complaints</h1><p>Review public complaints, suggestions and compliments in your schedule areas.</p></div></div>
<?php require __DIR__.'/module-filters.php'; ?>
<section class="feedback-list-card"><h2>Feedback Records</h2>
<?php if (!$listing['rows']): ?><p class="officer-empty-state">No accessible feedback matches these filters.</p><?php else: ?>
<div class="feedback-table-wrapper"><table class="feedback-table"><thead><tr><th>Feedback / Type</th><th>Submitter</th><th>Related request</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($listing['rows'] as $feedback): ?><tr><td>#<?= (int)$feedback['feedback_id'] ?><br><?= $escape($feedback['feedback_type']) ?></td><td><?= $escape($feedback['public_name']) ?></td><td><?= $feedback['request_id']===null?'General feedback':'#'.(int)$feedback['request_id'] ?><br><?= $escape($feedback['area_name']) ?></td><td><?= $escape($feedback['submitted_at']) ?></td><td><span class="feedback-status <?= $escape(strtolower(str_replace('_','-',$feedback['feedback_status']))) ?>"><?= $escape($feedback['feedback_status']) ?></span></td><td><a class="secondary-btn" href="<?= $escape($link(['id'=>$feedback['feedback_id']])) ?>#module-details">View Details</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php if ($selected): $feedback=$selected; ?>
<section class="feedback-list-card officer-module-details" id="module-details"><h2>Feedback #<?= (int)$feedback['feedback_id'] ?></h2>
<div class="feedback-summary-grid-dialog"><?php foreach (['feedback_type'=>'Type','feedback_status'=>'Status','public_name'=>'Submitted by','submitted_at'=>'Submitted','reviewer_name'=>'Reviewing Officer','reviewed_at'=>'Last reviewed'] as $field=>$label): ?><div><span><?= $escape($label) ?></span><strong><?= $escape($feedback[$field]) ?></strong></div><?php endforeach; ?></div>
<h3>Public message</h3><p class="module-message"><?= $escape($feedback['message']) ?></p>
<?php if ($feedback['request_id']!==null): ?><div class="related-request-preview"><p>Request #<?= (int)$feedback['request_id'] ?> · <?= $escape($feedback['request_status']) ?><br>Schedule #<?= (int)$feedback['schedule_id'] ?> · <?= $escape($feedback['area_name']) ?> · <?= $escape($feedback['collection_date']) ?></p></div><?php else: ?><p>General feedback; no related request.</p><?php endif; ?>
<h3>Officer response</h3><p class="module-message"><?= $escape($feedback['officer_response']) ?></p>
<?php $transitions=OfficerFeedback::transitions($feedback['feedback_status']); if ($transitions): ?>
<form class="feedback-review-form" method="post" action="<?= $escape($basePath.'/officer/feedback/'.$feedback['feedback_id'].'/review') ?>" data-officer-confirm="Save this feedback response and status?">
<input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="expected" value="<?= $escape($feedback['feedback_status']) ?>">
<div class="form-group"><label for="feedback-status">Next status</label><select name="status" id="feedback-status"><?php foreach ($transitions as $status): ?><option><?= $escape($status) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="feedback-response">Officer response (required to resolve/close; maximum 2000 characters)</label><textarea id="feedback-response" name="response" rows="5" maxlength="2000"><?= $escape($feedback['officer_response']??'') ?></textarea></div><button class="primary-btn">Save Update</button>
</form><?php else: ?><p>This feedback is closed and read-only.</p><?php endif; ?>
</section><?php endif; ?></section>
