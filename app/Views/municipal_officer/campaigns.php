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
                    <tr data-campaign-period="2026-08" data-created-date="2026-07-09">
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

                    <tr>
                        <td>#1</td>

                        <td>
                            Colombo Municipal E-Waste Campaign
                        </td>

                        <td>
                            Aug 2026
                        </td>

                        <td>
                            <span class="status open">
                                OPEN
                            </span>
                        </td>

                        <td>
                            Nadeesha Perera
                        </td>

                        <td>
                            09 Jul 2026
                        </td>

                        <td>
                            <button type="button" class="edit-btn">
                                Edit
                            </button>
                        </td>
                    </tr>

                    <tr data-campaign-period="2026-07" data-created-date="2026-07-09">
                        <td>#2</td>

                        <td>
                            July E-Waste Collection Campaign
                        </td>

                        <td>
                            Jul 2026
                        </td>

                        <td>
                            <span class="status closed">
                                CLOSED
                            </span>
                        </td>

                        <td>
                            Nadeesha Perera
                        </td>

                        <td>
                            09 Jul 2026
                        </td>

                        <td>
                            <button type="button" class="edit-btn">
                                Edit
                            </button>
                        </td>
                    </tr>

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

        <form class="campaign-create-form" data-campaign-form>
            <div class="form-group">
                <label for="campaign-name">Campaign Name</label>
                <input
                    id="campaign-name"
                    name="name"
                    type="text"
                    placeholder="e.g. August E-Waste Collection"
                    minlength="3"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="campaign-period">Month / Year</label>
                <input id="campaign-period" name="period" type="month" required>
            </div>

            <div class="form-group campaign-status-field">
                <label for="campaign-status">Campaign Status</label>
                <select id="campaign-status" name="status" required>
                    <option value="OPEN">OPEN</option>
                    <option value="CLOSED">CLOSED</option>
                </select>
                <small data-campaign-status-message>Open campaigns are available when creating area schedules.</small>
            </div>

            <p class="campaign-form-error officer-form-error" role="alert" data-campaign-form-error hidden></p>

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
