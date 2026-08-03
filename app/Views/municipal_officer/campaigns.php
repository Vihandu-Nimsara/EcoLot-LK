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
                        <th>Created At</th>
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
                            8 / 2026
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
                            2026-07-09<br>
                            13:57:05
                        </td>

                        <td>
                            <button class="edit-btn">
                                Edit
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td>#2</td>

                        <td>
                            July E-Waste Collection Campaign
                        </td>

                        <td>
                            7 / 2026
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
                            2026-07-09<br>
                            13:57:05
                        </td>

                        <td>
                            <button class="edit-btn">
                                Edit
                            </button>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</section>

<div class="campaign-dialog" data-campaign-dialog hidden>
    <section
        class="campaign-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="campaign-dialog-title"
    >
        <div class="campaign-dialog-header">
            <div>
                <span class="dialog-eyebrow">New monthly campaign</span>
                <h2 id="campaign-dialog-title">Create Campaign</h2>
                <p>Set up the campaign period. Area schedules can be added after creation.</p>
            </div>

            <button
                type="button"
                class="campaign-dialog-close"
                aria-label="Close create campaign form"
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
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="campaign-period">Month / Year</label>
                <input id="campaign-period" name="period" type="month" required>
            </div>

            <div class="campaign-status-note">
                <span class="status open">OPEN</span>
                <p>New campaigns start as open. You can close the campaign later when scheduling is complete.</p>
            </div>

            <div class="campaign-dialog-actions">
                <button type="button" class="secondary-btn" data-close-campaign-dialog>Cancel</button>
                <button type="submit" class="primary-btn">Create Campaign</button>
            </div>
        </form>
    </section>
</div>

<div class="campaign-toast" role="status" aria-live="polite" data-campaign-toast hidden>
    Campaign created and added to the list.
</div>
