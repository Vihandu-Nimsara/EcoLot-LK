<h3>Recorded management history</h3>
<?php if (!$history): ?><p>No management audit entries are recorded. Current decision metadata is shown above.</p><?php else: ?>
<div class="module-history">
<?php foreach ($history as $event): $before=$event['change']['before']; $after=$event['change']['after']; ?>
<article><strong><?= $escape($event['officer_name']) ?></strong> · <?= $escape($event['created_at']) ?>
<p><?= $escape($before['status']??null) ?> → <?= $escape($after['status']??$after['lot_status']??null) ?></p>
<?php if (isset($before['response'])): ?><p>Previous response: <?= $escape($before['response']) ?></p><?php endif; ?>
<?php if (isset($after['response'])): ?><p>Response: <?= $escape($after['response']) ?></p><?php endif; ?>
<?php if (isset($before['verification_note']) && $before['verification_note']!==($after['verification_note']??null)): ?><p>Previous Officer note: <?= $escape($before['verification_note']) ?></p><?php endif; ?>
<?php if (isset($after['verification_note'])): ?><p>Officer note: <?= $escape($after['verification_note']) ?></p><?php endif; ?>
<?php if (isset($after['winning_bid_id'])): ?><p>Winning bid #<?= (int)$after['winning_bid_id'] ?></p><?php endif; ?>
<?php if (isset($after['handover'])): ?><p>Handover: <?= $escape($after['handover']['handover_status']) ?> · <?= $escape($after['handover']['handover_date']) ?><br><?= $escape($after['handover']['remarks']) ?></p><?php endif; ?>
</article>
<?php endforeach; ?></div><?php endif; ?>
