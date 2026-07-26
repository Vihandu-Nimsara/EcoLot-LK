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

        <form class="filter-form">

            <div class="filter-grid">

                <div class="form-group">

                    <label for="campaign">
                        Campaign
                    </label>

                    <select id="campaign" name="campaign">

                        <option value="">
                            All Campaigns
                        </option>

                        <option value="1">
                            July 2026 Campaign
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
                            SCH-0010 - Wellawatte - 30 Jul 2026
                        </option>

                        <option value="SCH-0009">
                            SCH-0009 - Rajagiriya - 23 Jul 2026
                        </option>

                        <option value="SCH-0008">
                            SCH-0008 - Narahenpita - 19 Jul 2026
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

            </div>

        </div>

        <div class="flagged-table-wrapper">

            <table class="flagged-table">

                <thead>

                    <tr>

                        <th>Request ID</th>

                        <th>User</th>

                        <th>Area</th>

                        <th>Item Category</th>

                        <th>Hazard Reason</th>

                        <th>Submitted Date</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>
                            REQ-1045
                        </td>

                        <td>
                            QA Public User
                        </td>

                        <td>
                            Egoda Uyana
                        </td>

                        <td>
                            Refrigerator
                        </td>

                        <td>
                            Possible refrigerant or hazardous coolant.
                        </td>

                        <td>
                            20 Jul 2026
                        </td>

                        <td>

                            <span class="review-status pending">
                                PENDING REVIEW
                            </span>

                        </td>

                        <td>

                            <button class="review-btn">
                                Review
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>
                            REQ-1048
                        </td>

                        <td>
                            QA Public User
                        </td>

                        <td>
                            Katubedda
                        </td>

                        <td>
                            CRT Monitor
                        </td>

                        <td>
                            May contain leaded glass and hazardous components.
                        </td>

                        <td>
                            20 Jul 2026
                        </td>

                        <td>

                            <span class="review-status pending">
                                PENDING REVIEW
                            </span>

                        </td>

                        <td>

                            <button class="review-btn">
                                Review
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>
