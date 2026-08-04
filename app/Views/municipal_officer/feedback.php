<section class="feedback-page">

    <div class="page-toolbar">

        <div>
            <h1>Feedback & Complaints</h1>

            <p>
                Review and manage feedback or complaints submitted by public users.
            </p>
        </div>

    </div>

    <section class="feedback-status-tabs" aria-label="Filter feedback by status">

        <button type="button" class="feedback-tab active" data-feedback-status="" aria-pressed="true">
            All
            <span data-feedback-count>4</span>
        </button>

        <button type="button" class="feedback-tab" data-feedback-status="OPEN" aria-pressed="false">
            Open
            <span data-feedback-count>1</span>
        </button>

        <button type="button" class="feedback-tab" data-feedback-status="IN_REVIEW" aria-pressed="false">
            In Review
            <span data-feedback-count>1</span>
        </button>

        <button type="button" class="feedback-tab" data-feedback-status="RESOLVED" aria-pressed="false">
            Resolved
            <span data-feedback-count>1</span>
        </button>

        <button type="button" class="feedback-tab" data-feedback-status="CLOSED" aria-pressed="false">
            Closed
            <span>1</span>
        </button>

    </section>

    <section class="feedback-list-card">

        <div class="feedback-list-heading">

            <div>
                <h2>Feedback Records</h2>

                <p>
                    View feedback details and track the current review status.
                </p>

                <span class="feedback-result-count officer-result-count" data-feedback-result-count role="status" aria-live="polite">4 records shown</span>
            </div>

        </div>

        <div class="feedback-table-wrapper">

            <table class="feedback-table">

                <thead>

                    <tr>
                        <th>Feedback ID</th>
                        <th>Submitted By</th>
                        <th>Related Request</th>
                        <th>Subject</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody data-feedback-table-body>

                    <tr data-feedback-id="FB-001" data-feedback-status="OPEN" data-message="The scheduled collection vehicle did not arrive during the confirmed pickup window. Please check and provide a new collection time.">

                        <td>
                            FB-001
                        </td>

                        <td>
                            <div class="user-cell">
                                <span class="user-name">
                                    Kasun Perera
                                </span>

                                <span class="user-email">
                                    kasun.perera@example.com
                                </span>
                            </div>
                        </td>

                        <td>
                            <button type="button" class="request-link" data-related-request>
                                REQ-1036
                            </button>
                        </td>

                        <td>
                            Missed scheduled pickup
                        </td>

                        <td>
                            09 Jul 2026
                            <br>
                            13:57
                        </td>

                        <td>
                            <span class="feedback-status open">
                                OPEN
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="feedback-action-btn primary-action">
                                Review
                            </button>
                        </td>

                    </tr>

                    <tr data-feedback-id="FB-002" data-feedback-status="IN_REVIEW" data-message="Please confirm whether the collection team will arrive in the morning or afternoon so someone can be available.">

                        <td>
                            FB-002
                        </td>

                        <td>
                            <div class="user-cell">
                                <span class="user-name">
                                    Dilini Silva
                                </span>

                                <span class="user-email">
                                    dilini.silva@example.com
                                </span>
                            </div>
                        </td>

                        <td>
                            <button type="button" class="request-link" data-related-request>
                                REQ-1040
                            </button>
                        </td>

                        <td>
                            Collection time clarification
                        </td>

                        <td>
                            09 Jul 2026
                            <br>
                            13:57
                        </td>

                        <td>
                            <span class="feedback-status in-review">
                                IN REVIEW
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="feedback-action-btn secondary-action">
                                Continue
                            </button>
                        </td>

                    </tr>

                    <tr data-feedback-id="FB-003" data-feedback-status="RESOLVED" data-message="The collection issue was resolved after the officer contacted the assigned team. Thank you for the support.">

                        <td>
                            FB-003
                        </td>

                        <td>
                            <div class="user-cell">
                                <span class="user-name">
                                    Amal Fernando
                                </span>

                                <span class="user-email">
                                    amal.fernando@example.com
                                </span>
                            </div>
                        </td>

                        <td>
                            <button type="button" class="request-link" data-related-request>
                                REQ-1043
                            </button>
                        </td>

                        <td>
                            Pickup issue resolved
                        </td>

                        <td>
                            08 Jul 2026
                            <br>
                            16:20
                        </td>

                        <td>
                            <span class="feedback-status resolved">
                                RESOLVED
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="feedback-action-btn secondary-action">
                                View
                            </button>
                        </td>

                    </tr>

                    <tr data-feedback-id="FB-004" data-feedback-status="CLOSED" data-message="The collection service was professional and the team handled all items carefully.">

                        <td>
                            FB-004
                        </td>

                        <td>
                            <div class="user-cell">
                                <span class="user-name">
                                    Ishara Jayasinghe
                                </span>

                                <span class="user-email">
                                    ishara.jayasinghe@example.com
                                </span>
                            </div>
                        </td>

                        <td>
                            <button type="button" class="request-link" data-related-request>
                                REQ-1044
                            </button>
                        </td>

                        <td>
                            General collection feedback
                        </td>

                        <td>
                            07 Jul 2026
                            <br>
                            11:45
                        </td>

                        <td>
                            <span class="feedback-status closed">
                                CLOSED
                            </span>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="feedback-action-btn secondary-action">
                                View
                            </button>
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="feedback-empty-state officer-empty-state" data-feedback-empty-state hidden>
            No feedback records match this status.
        </div>

    </section>

</section>

<div class="feedback-dialog officer-dialog" data-feedback-dialog hidden>
    <section class="feedback-dialog-card officer-dialog-card" role="dialog" aria-modal="true" aria-labelledby="feedback-dialog-title">
        <div class="feedback-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-feedback-dialog-eyebrow>Feedback record</span>
                <h2 id="feedback-dialog-title" data-feedback-dialog-title>Review Feedback</h2>
                <p data-feedback-dialog-description>Review the public submission and record an officer response.</p>
            </div>
            <button type="button" class="feedback-dialog-close officer-dialog-close" aria-label="Close feedback details" data-close-feedback-dialog>×</button>
        </div>

        <div class="feedback-summary-grid-dialog">
            <div><span>Submitted By</span><strong data-dialog-feedback-user></strong></div>
            <div><span>Related Request</span><strong data-dialog-feedback-request></strong></div>
            <div><span>Submitted Date</span><strong data-dialog-feedback-date></strong></div>
        </div>

        <div class="feedback-message-card">
            <span>Subject</span>
            <strong data-dialog-feedback-subject></strong>
            <p data-dialog-feedback-message></p>
        </div>

        <div class="related-request-preview" data-related-request-preview hidden>
            <div><span>Request</span><strong data-preview-request-id></strong></div>
            <div><span>Schedule</span><strong data-preview-schedule></strong></div>
            <div><span>Request Status</span><strong data-preview-request-status></strong></div>
        </div>

        <form class="feedback-review-form" data-feedback-review-form>
            <div class="form-group">
                <label for="feedback-review-status">Feedback Status</label>
                <select id="feedback-review-status" name="status" required>
                    <option value="OPEN">OPEN</option>
                    <option value="IN_REVIEW">IN REVIEW</option>
                    <option value="RESOLVED">RESOLVED</option>
                    <option value="CLOSED">CLOSED</option>
                </select>
            </div>
            <div class="form-group">
                <label for="feedback-officer-response">Officer Response</label>
                <textarea id="feedback-officer-response" name="response" rows="4" maxlength="320" placeholder="Write the response or resolution note"></textarea>
            </div>
            <p class="feedback-form-error officer-form-error" role="alert" data-feedback-form-error hidden></p>
            <div class="feedback-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-feedback-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-feedback-submit>Save Update</button>
            </div>
        </form>
    </section>
</div>

<div class="feedback-toast officer-toast" role="status" aria-live="polite" data-feedback-toast hidden>Feedback update saved in this browser.</div>
