<section class="reports-page">

    <div class="page-toolbar">

        <div>
            <h1>Municipal Reports</h1>

            <p>
                Review key municipal e-waste collection performance and activity.
            </p>
        </div>

        <div class="toolbar-actions">

            <button
                type="button"
                class="secondary-btn print-report-btn"
                data-print-report>
                Print Report
            </button>

        </div>

    </div>

    <section class="report-filter-card compact-filter-card">

    <form class="report-filter-form" data-report-filter-form>

        <div class="report-filter-grid">

            <div class="form-group">

                <label for="campaign">
                    Monthly Campaign
                </label>

                <select id="campaign" name="campaign">

                    <option value="">
                        All Campaigns
                    </option>

                    <option value="july-2026">
                        July E-Waste Collection Campaign — 7/2026
                    </option>

                    <option value="august-2026">
                        Colombo Municipal E-Waste Campaign — 8/2026
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

    <section class="report-summary-grid">

    <article class="report-summary-card">

        <span class="summary-label">
            Scheduled Areas
        </span>

        <strong class="summary-value" data-report-metric="scheduledAreas">
            3
        </strong>

        <small>
            Areas included in this campaign
        </small>

    </article>

    <article class="report-summary-card">

        <span class="summary-label">
            Open Schedules
        </span>

        <strong class="summary-value" data-report-metric="openSchedules">
            3
        </strong>

        <small>
            Accepting pickup requests
        </small>

    </article>

    <article class="report-summary-card">

        <span class="summary-label">
            Submitted Requests
        </span>

        <strong class="summary-value" data-report-metric="submittedRequests">
            18
        </strong>

        <small>
            Submitted for this campaign
        </small>

    </article>

    <article class="report-summary-card">

        <span class="summary-label">
            Verified Collections
        </span>

        <strong class="summary-value" data-report-metric="verifiedCollections">
            12
        </strong>

        <small>
            Verified pickup records
        </small>

    </article>

    <article class="report-summary-card">

        <span class="summary-label">
            Active E-Lots
        </span>

        <strong class="summary-value" data-report-metric="activeElots">
            2
        </strong>

        <small>
            Open or awarded E-Lots
        </small>

    </article>

    <article class="report-summary-card">

        <span class="summary-label">
            Total Collected Weight
        </span>

        <strong class="summary-value" data-report-metric="totalWeight">
            49.50 kg
        </strong>

        <small>
            Verified collection weight
        </small>

    </article>

</section>

    <section class="report-card">

        <div class="report-card-heading">

            <div>
                <h2>Request Status Summary</h2>

                <p>
                    Current request progress for the selected reporting period.
                </p>
            </div>

        </div>

        <div class="status-summary-grid">

            <article class="status-summary-item">

                <span>Submitted</span>

                <strong data-request-metric="submitted">18</strong>

            </article>

            <article class="status-summary-item">

                <span>Approved</span>

                <strong data-request-metric="approved">12</strong>

            </article>

            <article class="status-summary-item">

                <span>Assigned</span>

                <strong data-request-metric="assigned">9</strong>

            </article>

            <article class="status-summary-item">

                <span>Collected</span>

                <strong data-request-metric="collected">7</strong>

            </article>

            <article class="status-summary-item">

                <span>Rejected</span>

                <strong data-request-metric="rejected">2</strong>

            </article>

        </div>

    </section>

    <section class="report-card">

        <div class="report-card-heading">

            <div>
                <h2>Collection Performance</h2>

                <p>
                    Schedule-level collection and verification performance.
                </p>
            </div>

        </div>

        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>
                        <th>Schedule</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Assigned Stops</th>
                        <th>Collected</th>
                        <th>Pending</th>
                        <th>Verified</th>
                        <th>Total Weight</th>
                    </tr>

                </thead>

                <tbody data-report-performance-body>

                    <tr>

                        <td>SCH-0004</td>

                        <td>Rajagiriya</td>

                        <td>23 Jul 2026</td>

                        <td>4</td>

                        <td>3</td>

                        <td>1</td>

                        <td>3</td>

                        <td>31.50 kg</td>

                    </tr>

                    <tr>

                        <td>SCH-0003</td>

                        <td>Kollupitiya</td>

                        <td>16 Jul 2026</td>

                        <td>2</td>

                        <td>2</td>

                        <td>0</td>

                        <td>2</td>

                        <td>18.00 kg</td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

    <section class="report-card">

        <div class="report-card-heading">

            <div>
                <h2>E-Lot & Bid Summary</h2>

                <p>
                    Current E-Lot bidding and award activity.
                </p>
            </div>

        </div>

        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>
                        <th>E-Lot Code</th>
                        <th>Category</th>
                        <th>Weight</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>Winner</th>
                    </tr>

                </thead>

                <tbody data-report-elots-body>

                    <tr>

                        <td>EL-001</td>

                        <td>Domestic E-Waste</td>

                        <td>5.50 kg</td>

                        <td>2</td>

                        <td>

                            <span class="report-status open">
                                OPEN FOR BIDDING
                            </span>

                        </td>

                        <td>
                            Not Selected
                        </td>

                    </tr>

                    <tr>

                        <td>EL-002</td>

                        <td>Office E-Waste</td>

                        <td>9.00 kg</td>

                        <td>2</td>

                        <td>

                            <span class="report-status awarded">
                                AWARDED
                            </span>

                        </td>

                        <td>
                            GreenCycle Lanka (Pvt) Ltd
                        </td>

                    </tr>

                    <tr>

                        <td>EL-003</td>

                        <td>Industrial E-Waste</td>

                        <td>10.00 kg</td>

                        <td>0</td>

                        <td>

                            <span class="report-status completed">
                                COMPLETED
                            </span>

                        </td>

                        <td>
                            Ceylon Circular Metals (Pvt) Ltd
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

    <section class="report-card feedback-summary-card">

        <div class="report-card-heading">

            <div>
                <h2>Feedback Summary</h2>

                <p>
                    Current public feedback and complaint status.
                </p>
            </div>

            <a
                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/officer/feedback"
                class="secondary-btn feedback-link">
                View Feedback
            </a>

        </div>

        <div class="feedback-summary-grid">

            <article class="feedback-summary-item">

                <span>Open</span>

                <strong data-feedback-metric="open">3</strong>

            </article>

            <article class="feedback-summary-item">

                <span>In Review</span>

                <strong data-feedback-metric="inReview">2</strong>

            </article>

            <article class="feedback-summary-item">

                <span>Resolved</span>

                <strong data-feedback-metric="resolved">8</strong>

            </article>

            <article class="feedback-summary-item">

                <span>Closed</span>

                <strong data-feedback-metric="closed">4</strong>

            </article>

        </div>

    </section>

</section>
