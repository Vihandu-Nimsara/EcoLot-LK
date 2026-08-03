<section class="flagged-requests-page">

    <div class="page-toolbar">

        <div>
            <h1>Hazardous Request Review</h1>

            <p>
                Review e-waste pickup requests flagged as potentially hazardous before collection.
            </p>
        </div>

    </div>

    <section class="filter-card compact-filter-card">

        <form class="filter-form" data-flagged-filter-form>

            <div class="filter-grid">

                <div class="form-group">

                    <label for="campaign">
                        Campaign
                    </label>

                    <select id="campaign" name="campaign">

                        <option value="">
                            All Campaigns
                        </option>

                        <option value="campaign-1">
                            Colombo Municipal E-Waste Campaign — 8/2026
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

                        <option value="SCH-0010">
                            SCH-0010 — Wellawatte — 30 Aug 2026
                        </option>

                        <option value="SCH-0009">
                            SCH-0009 — Rajagiriya — 23 Aug 2026
                        </option>

                        <option value="SCH-0008">
                            SCH-0008 — Narahenpita — 19 Aug 2026
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="review-status">
                        Review Status
                    </label>

                    <select id="review-status" name="review_status">

                        <option value="">
                            All Review Statuses
                        </option>

                        <option value="PENDING_REVIEW">
                            Pending Review
                        </option>

                        <option value="APPROVED">
                            Approved
                        </option>

                        <option value="REJECTED">
                            Rejected
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

    <section class="flagged-list-card">

        <div class="list-heading">

            <div>

                <h2>
                    Flagged Requests
                </h2>

                <p>
                    Requests requiring officer review before they can proceed to collection.
                </p>

                <span class="filter-result-count officer-result-count" data-filter-result-count role="status" aria-live="polite">2 requests shown</span>

            </div>

        </div>

        <div class="flagged-table-wrapper">

            <table class="flagged-table">

                <thead>

                    <tr>

                        <th>Request ID</th>

                        <th>User</th>

                        <th>Collection Schedule</th>

                        <th>Item Category</th>

                        <th>Hazard Reason</th>

                        <th>Submitted Date</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody data-flagged-table-body>

                    <tr
                        data-request-id="REQ-1045"
                        data-campaign="campaign-1"
                        data-schedule="SCH-0010"
                        data-review-status="PENDING_REVIEW"
                    >

                        <td>
                            REQ-1045
                        </td>

                        <td>
                            Kasun Perera
                        </td>

                        <td>
                            <strong>SCH-0010</strong>
                            <span class="table-subtext">Wellawatte · 30 Aug 2026</span>
                        </td>

                        <td>
                            Refrigerator
                        </td>

                        <td>
                            Possible refrigerant or hazardous coolant.
                        </td>

                        <td>
                            12 Aug 2026
                        </td>

                        <td>

                            <span class="review-status pending">
                                PENDING REVIEW
                            </span>

                        </td>

                        <td>

                            <button type="button" class="review-btn">
                                Review
                            </button>

                        </td>

                    </tr>

                    <tr
                        data-request-id="REQ-1048"
                        data-campaign="campaign-1"
                        data-schedule="SCH-0009"
                        data-review-status="PENDING_REVIEW"
                    >

                        <td>
                            REQ-1048
                        </td>

                        <td>
                            Dilini Silva
                        </td>

                        <td>
                            <strong>SCH-0009</strong>
                            <span class="table-subtext">Rajagiriya · 23 Aug 2026</span>
                        </td>

                        <td>
                            CRT Monitor
                        </td>

                        <td>
                            May contain leaded glass and hazardous components.
                        </td>

                        <td>
                            14 Aug 2026
                        </td>

                        <td>

                            <span class="review-status pending">
                                PENDING REVIEW
                            </span>

                        </td>

                        <td>

                            <button type="button" class="review-btn">
                                Review
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="flagged-empty-state officer-empty-state" data-flagged-empty-state hidden>
            No flagged requests match the selected filters.
        </div>

    </section>

</section>

<div class="review-dialog officer-dialog" data-review-dialog hidden>
    <section
        class="review-dialog-card officer-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="review-dialog-title"
    >
        <div class="review-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-review-request-id>Flagged request</span>
                <h2 id="review-dialog-title">Review Hazardous Request</h2>
                <p>Confirm whether this request can proceed to the assigned collection schedule.</p>
            </div>

            <button
                type="button"
                class="review-dialog-close officer-dialog-close"
                aria-label="Close request review"
                data-close-review-dialog
            >×</button>
        </div>

        <div class="review-request-summary">
            <div><span>User</span><strong data-review-user></strong></div>
            <div><span>Schedule</span><strong data-review-schedule></strong></div>
            <div><span>Item Category</span><strong data-review-category></strong></div>
            <div class="review-summary-wide"><span>Hazard Reason</span><strong data-review-reason></strong></div>
        </div>

        <form class="review-form" data-review-form>
            <div class="form-group">
                <label for="review-decision">Review Decision</label>
                <select id="review-decision" name="decision" required>
                    <option value="PENDING_REVIEW">Pending Review</option>
                    <option value="APPROVED">Approve for Collection</option>
                    <option value="REJECTED">Reject Request</option>
                </select>
            </div>

            <div class="form-group">
                <label for="review-note">Officer Note</label>
                <textarea
                    id="review-note"
                    name="note"
                    rows="4"
                    maxlength="500"
                    placeholder="Add handling instructions or a reason for the decision"
                ></textarea>
                <small>A note is required when rejecting a request.</small>
            </div>

            <p class="review-form-error officer-form-error" role="alert" data-review-form-error hidden></p>

            <div class="review-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-review-dialog>Cancel</button>
                <button type="submit" class="primary-btn">Save Review</button>
            </div>
        </form>
    </section>
</div>

<div class="review-toast officer-toast" role="status" aria-live="polite" data-review-toast hidden>
    Review decision saved in this browser.
</div>
