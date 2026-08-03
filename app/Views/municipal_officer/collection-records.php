<section class="collection-records-page">

    <div class="page-toolbar">

        <div>
            <h1>Collection Records</h1>

            <p>
                Review collector submissions grouped by collection schedule and verify completed collections.
            </p>
        </div>

    </div>

    <section class="records-filter-card compact-filter-card">

        <form class="records-filter-form" data-records-filter-form>

            <div class="records-filter-grid">

                <div class="form-group">

                    <label for="campaign">
                        Campaign
                    </label>

                    <select id="campaign" name="campaign">

                        <option value="">
                            All Campaigns
                        </option>

                        <option value="campaign-2">
                            July E-Waste Collection Campaign — 7/2026
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="schedule">
                        Collection Schedule
                    </label>

                    <select id="schedule" name="schedule">

                        <option value="">
                            All Schedules
                        </option>

                        <option value="SCH-0004">
                            SCH-0004 — Rajagiriya — 23 Jul 2026
                        </option>

                        <option value="SCH-0003">
                            SCH-0003 — Kollupitiya — 16 Jul 2026
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="verification-status">
                        Verification Status
                    </label>

                    <select id="verification-status" name="verification_status">

                        <option value="">
                            All Statuses
                        </option>

                        <option value="PENDING">
                            Pending Verification
                        </option>

                        <option value="VERIFIED">
                            Verified
                        </option>

                    </select>

                </div>

            </div>

            <div class="filter-actions">

                <button
                    type="reset"
                    class="secondary-btn">
                    Clear
                </button>

                <button
                    type="submit"
                    class="primary-btn">
                    Apply Filters
                </button>

            </div>

        </form>

    </section>

    <section class="records-list-card">

        <div class="records-list-heading">

            <div>

                <h2>Schedule Collection Summary</h2>

                <p>
                    Each row summarizes collection records submitted for one area collection schedule.
                </p>

                <span class="record-result-count officer-result-count" data-record-result-count role="status" aria-live="polite">2 records shown</span>

            </div>

        </div>

        <div class="records-table-wrapper">

            <table class="records-table">

                <thead>

                    <tr>
                        <th>Schedule &amp; Area</th>
                        <th>Collection Date</th>
                        <th>Progress</th>
                        <th>Outstanding</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody data-records-table-body>

                    <tr
                        data-record-id="CR-0009"
                        data-campaign="campaign-2"
                        data-schedule="SCH-0004"
                        data-verification-status="PENDING"
                    >

                        <td>
                            <strong>SCH-0004</strong>
                            <span class="table-subtext">Rajagiriya</span>
                        </td>

                        <td>
                            23 Jul 2026
                        </td>

                        <td>
                            <strong>3 collected</strong>
                            <span class="table-subtext">4 assigned · 1 partial</span>
                        </td>

                        <td>
                            <strong>0 pending</strong>
                            <span class="table-subtext">0 failed</span>
                        </td>

                        <td>
                            31.50 kg
                        </td>

                        <td>

                            <span class="verification-status pending">
                                PENDING
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="record-action-btn primary-action">
                                Review
                            </button>

                        </td>

                    </tr>

                    <tr
                        data-record-id="CR-0007"
                        data-campaign="campaign-2"
                        data-schedule="SCH-0003"
                        data-verification-status="VERIFIED"
                    >

                        <td>
                            <strong>SCH-0003</strong>
                            <span class="table-subtext">Kollupitiya</span>
                        </td>

                        <td>
                            16 Jul 2026
                        </td>

                        <td>
                            <strong>2 collected</strong>
                            <span class="table-subtext">2 assigned · 0 partial</span>
                        </td>

                        <td>
                            <strong>0 pending</strong>
                            <span class="table-subtext">0 failed</span>
                        </td>

                        <td>
                            18.00 kg
                        </td>

                        <td>

                            <span class="verification-status verified">
                                VERIFIED
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="record-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="records-empty-state officer-empty-state" data-records-empty-state hidden>
            No collection records match the selected filters.
        </div>

    </section>

</section>

<div class="record-dialog officer-dialog" data-record-dialog hidden>
    <section
        class="record-dialog-card officer-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="record-dialog-title"
    >
        <div class="record-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-record-dialog-eyebrow>Collection record</span>
                <h2 id="record-dialog-title" data-record-dialog-title>Review Collection Records</h2>
                <p data-record-dialog-description>Check the submitted collection details before verification.</p>
            </div>

            <button
                type="button"
                class="record-dialog-close officer-dialog-close"
                aria-label="Close collection record details"
                data-close-record-dialog
            >×</button>
        </div>

        <div class="record-summary-grid">
            <div><span>Schedule</span><strong data-record-schedule></strong></div>
            <div><span>Area</span><strong data-record-area></strong></div>
            <div><span>Collection Date</span><strong data-record-date></strong></div>
            <div><span>Total Weight</span><strong data-record-weight></strong></div>
        </div>

        <div class="record-submissions-section">
            <div class="record-submissions-heading">
                <h3>Collector Submissions</h3>
                <span data-submission-count></span>
            </div>

            <div class="record-submissions-table-wrapper">
                <table class="record-submissions-table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Resident</th>
                            <th>Weight</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody data-record-submissions></tbody>
                </table>
            </div>
        </div>

        <form class="record-review-form" data-record-review-form>
            <div class="record-review-grid">
                <div class="form-group">
                    <label for="record-verification-status">Verification Status</label>
                    <select id="record-verification-status" name="status" required>
                        <option value="PENDING">PENDING VERIFICATION</option>
                        <option value="VERIFIED">VERIFIED</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="record-verification-note">Verification Note</label>
                    <textarea
                        id="record-verification-note"
                        name="note"
                        rows="3"
                        maxlength="240"
                        placeholder="Add a short verification note"
                    ></textarea>
                </div>
            </div>

            <p class="record-form-error officer-form-error" role="alert" data-record-form-error hidden></p>

            <div class="record-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-record-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-record-submit>Save Verification</button>
            </div>
        </form>
    </section>
</div>

<div class="record-toast officer-toast" role="status" aria-live="polite" data-record-toast hidden>
    Collection record verification saved in this browser.
</div>
