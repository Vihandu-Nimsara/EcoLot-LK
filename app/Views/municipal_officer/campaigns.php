<?php $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
<?php if ($notice): ?><p role="status"><?= $escape($notice) ?></p><?php endif; ?>
<section class="campaigns-page">
    <div class="page-toolbar">
        <div>
            <h1>Campaigns</h1>
            <p>Create and manage monthly municipal e-waste collection campaigns.</p>
        </div>

        <div class="toolbar-actions">
            <button type="button" class="primary-btn create-campaign-trigger" data-open-campaign-dialog>
                <span aria-hidden="true">+</span>
                Create Campaign
            </button>
        </div>
    </div>

    <div class="campaigns-card">
        <div class="campaigns-context">
            EcoLot LK Municipal Council
        </div>

        <div class="campaign-table-wrapper">

            <table class="campaign-table">

                <thead>
                    <tr>
                        <th>Campaign ID</th>
                        <th>Name</th>
                        <th>Month / Year</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody data-campaign-table-body>
                    <?php foreach ($campaigns as $campaign): ?>
                    <tr data-campaign-id="<?= (int) $campaign['campaign_id'] ?>" data-name="<?= $escape($campaign['campaign_name']) ?>" data-period="<?= $escape(substr($campaign['campaign_month'], 0, 7)) ?>" data-status="<?= $escape($campaign['campaign_status']) ?>">
                        <td><?= (int) $campaign['campaign_id'] ?></td>
                        <td><?= $escape($campaign['campaign_name']) ?></td>
                        <td><?= $escape(substr($campaign['campaign_month'], 0, 7)) ?></td>
                        <td><?= $escape($campaign['campaign_status']) ?></td>
                        <td><?= $escape($campaign['officer_name']) ?></td>
                        <td><?= $escape($campaign['created_at']) ?></td>
                        <td><button type="button" class="edit-btn">Edit</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$campaigns): ?><tr><td colspan="7">No campaigns yet.</td></tr><?php endif; ?>
                </tbody>

            </table>

        </div>

    </div>

</section>

<div class="campaign-dialog officer-dialog" data-campaign-dialog hidden>
    <section
        class="campaign-dialog-card officer-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="campaign-dialog-title"
    >
        <div class="campaign-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-campaign-dialog-eyebrow>New monthly campaign</span>
                <h2 id="campaign-dialog-title" data-campaign-dialog-title>Create Campaign</h2>
                <p data-campaign-dialog-description>Set up the campaign period. Area schedules can be added after creation.</p>
            </div>

            <button
                type="button"
                class="campaign-dialog-close officer-dialog-close"
                aria-label="Close campaign form"
                data-close-campaign-dialog
            >×</button>
        </div>

        <form class="campaign-create-form" data-campaign-form method="post" action="<?= $escape($basePath) ?>/officer/campaigns" data-errors="<?= $errors ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="campaign_id" value="<?= $escape($values['campaign_id'] ?? '') ?>">
            <div class="form-group">
                <label for="campaign-name">Campaign Name</label>
                <input
                    id="campaign-name"
                    name="name" value="<?= $escape($values['name'] ?? '') ?>"
                    type="text"
                    placeholder="e.g. August E-Waste Collection"
                    minlength="3"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="campaign-period">Month / Year</label>
                <input id="campaign-period" name="period" type="month" value="<?= $escape($values['period'] ?? '') ?>" required>
            </div>

            <div class="form-group campaign-status-field">
                <label for="campaign-status">Campaign Status</label>
                <select id="campaign-status" name="status" required>
                    <option value="OPEN">OPEN</option>
                    <option value="CLOSED" <?= ($values['status'] ?? '') === 'CLOSED' ? 'selected' : '' ?>>CLOSED</option>
                </select>
                <small data-campaign-status-message>Open campaigns are available when creating area schedules.</small>
            </div>

            <p class="campaign-form-error officer-form-error" role="alert" data-campaign-form-error <?= !$errors ? 'hidden' : '' ?>><?= $escape(implode(' ', $errors)) ?></p>

            <div class="campaign-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-campaign-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-campaign-submit>Create Campaign</button>
            </div>
        </form>
    </section>
</div>

<div class="campaign-toast officer-toast" role="status" aria-live="polite" data-campaign-toast hidden>
    <span data-campaign-toast-message>Campaign created and added to the list.</span>
</div>
